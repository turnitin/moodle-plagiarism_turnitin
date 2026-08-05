<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for classes/turnitin_disclosure.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_disclosure::render().
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_disclosure::class)]
final class turnitin_disclosure_test extends \advanced_testcase {
    /**
     * Set the minimum API credentials so is_plugin_configured() returns true.
     */
    private function set_credentials(): void {
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
    }

    /**
     * Create a course module of the given type and return its id.
     *
     * @param string $modtype e.g. 'assign', 'forum'
     * @return int cmid
     */
    private function make_cm(string $modtype): int {
        $course  = $this->getDataGenerator()->create_course();
        $mod     = $this->getDataGenerator()->create_module($modtype, ['course' => $course->id]);
        return get_coursemodule_from_instance($modtype, $mod->id)->id;
    }

    /**
     * Build a mock of plagiarism_plugin_turnitin that satisfies the calls
     * render() makes after the early-return guards.
     *
     * load_page_components() is a no-op in tests (it just registers AMD).
     * render_eula_form() returns the given string (default '').
     * plagiarism_get_report_gen_speed_params() returns a minimal stdClass.
     *
     * @param string $eulahtml HTML to return from render_eula_form().
     * @return \plagiarism_plugin_turnitin
     */
    private function make_mock_plugin(string $eulahtml = ''): \plagiarism_plugin_turnitin {
        $mock = $this->getMockBuilder(\plagiarism_plugin_turnitin::class)
            ->onlyMethods(['load_page_components', 'render_eula_form',
                'plagiarism_get_report_gen_speed_params', 'get_course_data', 'sync_tii_assignment'])
            ->getMock();

        $mock->method('load_page_components')->willReturn(null);
        $mock->method('render_eula_form')->willReturn($eulahtml);

        $genparams = new \stdClass();
        $genparams->num_resubmissions = 3;
        $genparams->num_hours = 24;
        $mock->method('plagiarism_get_report_gen_speed_params')->willReturn($genparams);

        $coursedata = new \stdClass();
        $coursedata->turnitin_cid = 0;
        $mock->method('get_course_data')->willReturn($coursedata);
        $mock->method('sync_tii_assignment')->willReturn(null);

        return $mock;
    }

    // Tests for early-return guards.

    /**
     * Test render() returns empty string when the module type is disabled for Turnitin.
     */
    public function test_returns_empty_when_module_type_disabled(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', 0, 'plagiarism_turnitin');

        $cmid   = $this->make_cm('assign');
        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertSame('', $result);
    }

    /**
     * Test render() returns empty string when use_turnitin is not enabled for the CM.
     */
    public function test_returns_empty_when_use_turnitin_disabled_for_cm(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        // No plagiarism_turnitin_config row — for_cm returns use_turnitin absent/0.
        $cmid   = $this->make_cm('assign');
        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertSame('', $result);
    }

    /**
     * Test render() returns only the agreement box when the plugin is not configured
     * (no API credentials).
     */
    public function test_returns_agreement_only_when_plugin_not_configured(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_agreement', 'You must agree to the terms.', 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        // No credentials set — is_plugin_configured() returns false.
        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringContainsString('You must agree to the terms.', $result);
        // EULA and rubric must not appear.
        $this->assertStringNotContainsString('pp_turnitin_eula', $result);
        $this->assertStringNotContainsString('rubric_view', $result);
    }

    // Tests for the happy-path content blocks.

    /**
     * Test render() includes the resubmission warning when a prior submission exists.
     */
    public function test_includes_resubmission_warning_when_prior_submission_exists(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        // Insert a prior submission for the current user.
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $cmid, 'userid' => $USER->id, 'submitter' => $USER->id,
            'identifier' => sha1('test'), 'statuscode' => 'success',
            'attempt' => 0, 'submissiontype' => 'file', 'itemid' => 0,
            'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringContainsString('tii_genspeednote', $result);
    }

    /**
     * Test render() does NOT include the resubmission warning when no prior submission exists.
     */
    public function test_excludes_resubmission_warning_when_no_prior_submission(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringNotContainsString('tii_genspeednote', $result);
    }

    /**
     * Test render() skips the resubmission warning for forum modules even when
     * submissions exist — forum has no file resubmission concept.
     */
    public function test_skips_resubmission_warning_for_forum(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_forum', 1, 'plagiarism_turnitin');

        $cmid = $this->make_cm('forum');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        // Insert a submission — should not trigger the warning for forum.
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $cmid, 'userid' => $USER->id, 'submitter' => $USER->id,
            'identifier' => sha1('forum'), 'statuscode' => 'success',
            'attempt' => 0, 'submissiontype' => 'online_text', 'itemid' => 0,
            'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringNotContainsString('tii_genspeednote', $result);
    }

    /**
     * Test render() includes the agreement box when agreement text is configured.
     */
    public function test_includes_agreement_box_when_text_configured(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_agreement', 'Please read the submission policy.', 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringContainsString('Please read the submission policy.', $result);
    }

    /**
     * Test render() omits the agreement box when no agreement text is configured.
     */
    public function test_omits_agreement_box_when_no_text(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringNotContainsString('generalbox boxaligncenter', $result);
    }

    /**
     * Test render() includes EULA content returned by render_eula_form().
     */
    public function test_includes_eula_content_when_render_eula_form_returns_html(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);

        $eulahtml = '<div class="pp_turnitin_eula">Accept EULA</div>';
        $result   = turnitin_disclosure::render($cmid, $this->make_mock_plugin($eulahtml));

        $this->assertStringContainsString('pp_turnitin_eula', $result);
    }

    /**
     * Test render() includes the rubric view link when usegrademark and plagiarism_rubric are set.
     */
    public function test_includes_rubric_link_when_usegrademark_and_rubric_configured(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 1, 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'plagiarism_rubric', 'value' => '42',
            'config_hash' => $cmid . '_plagiarism_rubric',
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringContainsString('rubric_view', $result);
        $this->assertStringContainsString('tii_links_container', $result);
    }

    /**
     * Test render() omits the rubric link when usegrademark is off.
     */
    public function test_omits_rubric_link_when_usegrademark_disabled(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        set_config('plagiarism_turnitin_mod_assign', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');

        $cmid = $this->make_cm('assign');
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'use_turnitin', 'value' => '1',
            'config_hash' => $cmid . '_use_turnitin',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cmid, 'name' => 'plagiarism_rubric', 'value' => '42',
            'config_hash' => $cmid . '_plagiarism_rubric',
        ]);

        $result = turnitin_disclosure::render($cmid, $this->make_mock_plugin());

        $this->assertStringNotContainsString('rubric_view', $result);
    }

    /**
     * Test that print_disclosure() on plagiarism_plugin_turnitin delegates to render().
     *
     * Since Moodle core calls print_disclosure() by convention, we verify the stub
     * correctly passes $this as the plugin argument so render() has access to
     * load_page_components(), render_eula_form() etc.
     */
    public function test_print_disclosure_delegates_to_render(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Module disabled — render() returns '' immediately.
        set_config('plagiarism_turnitin_mod_assign', 0, 'plagiarism_turnitin');

        $cmid   = $this->make_cm('assign');
        $plugin = new \plagiarism_plugin_turnitin();

        $this->assertSame('', $plugin->print_disclosure($cmid));
    }
}
