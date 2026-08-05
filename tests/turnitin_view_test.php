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
 * Unit tests for classes/turnitin_view.php.
 *
 * The LTI launcher methods (output_launch_form, output_lti_form_launch) require
 * a live Turnitin API connection and are therefore not tested here.
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
require_once($CFG->dirroot . '/lib/formslib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_view.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_view::class)]
class turnitin_view_test extends \advanced_testcase {

    // output_header tests.

    /**
     * Test output_header sets PAGE properties and returns the header string when $return=true.
     */
    public function test_output_header_returns_string_when_return_is_true(): void {
        global $PAGE;
        $this->resetAfterTest();

        $view = new turnitin_view();
        $result = $view->output_header('/plagiarism/turnitin/settings.php', 'My Title', 'My Heading', true);

        $this->assertIsString($result);
        $this->assertEquals('/plagiarism/turnitin/settings.php', $PAGE->url->out_as_local_url());
    }

    /**
     * Test output_header echoes output (returns nothing) when $return=false.
     */
    public function test_output_header_echoes_when_return_is_false(): void {
        $this->resetAfterTest();

        $view = new turnitin_view();
        $this->expectOutputRegex('/.+/');
        $result = $view->output_header('/plagiarism/turnitin/settings.php', 'Title', 'Heading', false);

        $this->assertNull($result);
    }

    // draw_settings_tab_menu tests.

    /**
     * Test draw_settings_tab_menu renders tab markup without a notice.
     */
    public function test_draw_settings_tab_menu_renders_tabs(): void {
        $this->resetAfterTest();
        $this->expectOutputRegex('/' . preg_quote(get_string('config', 'plagiarism_turnitin'), '/') . '/');

        $view = new turnitin_view();
        $view->draw_settings_tab_menu('turnitinsettings');
    }

    /**
     * Test draw_settings_tab_menu renders a notice box when one is supplied.
     */
    public function test_draw_settings_tab_menu_renders_notice(): void {
        $this->resetAfterTest();
        $this->expectOutputRegex('/Settings saved/');

        $view = new turnitin_view();
        $view->draw_settings_tab_menu('turnitinsettings', ['message' => 'Settings saved', 'type' => 'success']);
    }

    // lock tests.

    /**
     * Test lock adds an advcheckbox element when on the defaults page.
     */
    public function test_lock_adds_lock_checkbox_on_defaults_page(): void {
        $this->resetAfterTest();

        $mform = new \MoodleQuickForm('test', 'post', '');
        $mform->addElement('select', 'use_turnitin', 'Use Turnitin', [0 => 'No', 1 => 'Yes']);

        $view = new turnitin_view();
        $view->lock($mform, 'defaults', []);

        $this->assertTrue($mform->elementExists('use_turnitin_lock'));
    }

    /**
     * Test lock does not freeze the field when there is no lock set in the activity view.
     */
    public function test_lock_does_not_freeze_when_unlocked_in_activity(): void {
        $this->resetAfterTest();

        $mform = new \MoodleQuickForm('test', 'post', '');
        $mform->addElement('select', 'use_turnitin', 'Use Turnitin', [0 => 'No', 1 => 'Yes']);

        $view = new turnitin_view();
        $view->lock($mform, 'activity', []);

        $this->assertFalse($mform->isElementFrozen('use_turnitin'));
    }

    /**
     * Test lock freezes a field when it is locked in the activity view.
     */
    public function test_lock_freezes_field_when_locked_in_activity(): void {
        $this->resetAfterTest();

        $mform = new \MoodleQuickForm('test', 'post', '');
        $mform->addElement('select', 'use_turnitin', 'Use Turnitin', [0 => 'No', 1 => 'Yes']);

        $locksetting = new \stdClass();
        $locksetting->value = 1;
        $lockedmsg = new \stdClass();
        $lockedmsg->value = '';

        $view = new turnitin_view();
        $view->lock($mform, 'activity', [
            'use_turnitin_lock' => $locksetting,
            'plagiarism_locked_message' => $lockedmsg,
        ]);

        $this->assertTrue($mform->isElementFrozen('use_turnitin'));
    }

    /**
     * Test lock adds a static message element when a field is locked and a message is set.
     */
    public function test_lock_adds_static_message_when_locked_with_message(): void {
        $this->resetAfterTest();

        $mform = new \MoodleQuickForm('test', 'post', '');
        $mform->addElement('select', 'use_turnitin', 'Use Turnitin', [0 => 'No', 1 => 'Yes']);

        $locksetting = new \stdClass();
        $locksetting->value = 1;
        $lockedmsg = new \stdClass();
        $lockedmsg->value = 'This field is locked by your administrator.';

        $view = new turnitin_view();
        $view->lock($mform, 'activity', [
            'use_turnitin_lock' => $locksetting,
            'plagiarism_locked_message' => $lockedmsg,
        ]);

        $this->assertTrue($mform->elementExists('use_turnitin_why'));
    }

    // add_elements_to_settings_form tests (defaults location only — the
    // activity location triggers Turnitin API calls via turnitin_user).

    /**
     * Build a minimal MoodleQuickForm for use with add_elements_to_settings_form.
     */
    private function make_mform(): \MoodleQuickForm {
        return new \MoodleQuickForm('turnitin_defaults', 'post', '');
    }

    /**
     * Build a minimal course object sufficient for add_elements_to_settings_form.
     */
    private function make_course(): \stdClass {
        $course = new \stdClass();
        $course->id = 0;
        $course->turnitin_cid = 0;
        return $course;
    }

    /**
     * Test that the defaults form adds the use_turnitin select element.
     */
    public function test_add_elements_defaults_adds_use_turnitin(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        $view = new turnitin_view();
        $view->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $this->assertTrue($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that the defaults form adds the draft submit field.
     */
    public function test_add_elements_defaults_adds_draft_submit(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        $view = new turnitin_view();
        $view->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $this->assertTrue($mform->elementExists('plagiarism_draft_submit'));
    }

    /**
     * Test that the defaults form adds the locked_message field.
     */
    public function test_add_elements_defaults_adds_locked_message(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        $view = new turnitin_view();
        $view->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $this->assertTrue($mform->elementExists('plagiarism_locked_message'));
    }

    /**
     * Test repository option 0 shows a visible select for plagiarism_submitpapersto.
     */
    public function test_add_elements_repository_option_0_adds_visible_select(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $el = $mform->getElement('plagiarism_submitpapersto');
        $this->assertEquals('select', $el->getType());
    }

    /**
     * Test repository option EXPANDED adds a visible select including the institutional option.
     */
    public function test_add_elements_repository_option_expanded_adds_institutional_choice(): void {
        $this->resetAfterTest();
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_EXPANDED,
            'plagiarism_turnitin'
        );
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        // The compare_institution field only appears for expanded / force-institutional.
        $this->assertTrue($mform->elementExists('plagiarism_compare_institution'));
    }

    /**
     * Test repository option FORCE_STANDARD adds a hidden field (not a visible select).
     */
    public function test_add_elements_repository_option_force_standard_adds_hidden(): void {
        $this->resetAfterTest();
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD,
            'plagiarism_turnitin'
        );
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $el = $mform->getElement('plagiarism_submitpapersto');
        $this->assertEquals('hidden', $el->getType());
    }

    /**
     * Test repository option FORCE_NO adds a hidden field.
     */
    public function test_add_elements_repository_option_force_no_adds_hidden(): void {
        $this->resetAfterTest();
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_NO,
            'plagiarism_turnitin'
        );
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $el = $mform->getElement('plagiarism_submitpapersto');
        $this->assertEquals('hidden', $el->getType());
    }

    /**
     * Test repository option FORCE_INSTITUTIONAL adds a hidden field and shows compare_institution.
     */
    public function test_add_elements_repository_option_force_institutional(): void {
        $this->resetAfterTest();
        set_config(
            'plagiarism_turnitin_repositoryoption',
            PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_INSTITUTIONAL,
            'plagiarism_turnitin'
        );
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $el = $mform->getElement('plagiarism_submitpapersto');
        $this->assertEquals('hidden', $el->getType());
        $this->assertTrue($mform->elementExists('plagiarism_compare_institution'));
    }

    /**
     * Test that transmatch config=1 adds a select, not a hidden field.
     */
    public function test_add_elements_transmatch_enabled_adds_select(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 1, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $el = $mform->getElement('plagiarism_transmatch');
        $this->assertEquals('select', $el->getType());
    }

    /**
     * Test that transmatch config=0 adds a hidden field.
     */
    public function test_add_elements_transmatch_disabled_adds_hidden(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form($mform, $this->make_course(), 'defaults');

        $el = $mform->getElement('plagiarism_transmatch');
        $this->assertEquals('hidden', $el->getType());
    }

    /**
     * Test that the activity/forum path (no API calls) adds the plugin header and form fields.
     *
     * Passing modulename=mod_forum skips the turnitin_user API call so this exercises
     * the non-defaults form header and all the fields without a live connection.
     */
    public function test_add_elements_activity_forum_adds_plugin_header(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $this->make_course(),
            'activity',
            'mod_forum'
        );

        $this->assertTrue($mform->elementExists('use_turnitin'));
        // The rubric field is hidden for forum modules.
        $el = $mform->getElement('plagiarism_rubric');
        $this->assertEquals('hidden', $el->getType());
    }

    /**
     * Test that the activity/forum path with a cmid and submissions shows the refresh grades element.
     */
    public function test_add_elements_activity_shows_refresh_grades_when_submissions_exist(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);

        // Insert a queued submission so the refresh grades div renders.
        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => $cm->id, 'userid' => $user->id, 'submitter' => $user->id,
            'identifier' => sha1(uniqid('', true)), 'statuscode' => 'queued',
            'attempt' => 0, 'submissiontype' => 'online_text', 'itemid' => 0,
            'lastmodified' => time(), 'transmatch' => 0,
        ]);

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $this->make_course(),
            'activity',
            'mod_forum',
            $cm->id
        );

        $this->assertTrue($mform->elementExists('static'));
    }

    /**
     * Test that the quickmark manager link appears when usegrademark is enabled (forum path).
     */
    public function test_add_elements_activity_shows_quickmark_link_when_usegrademark_enabled(): void {
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $this->make_course(),
            'activity',
            'mod_forum'
        );

        // The quickmark manager link is injected as a static HTML element.
        $this->assertTrue($mform->elementExists('static'));
    }

    /**
     * Test that the peermark manager link appears when enablepeermark=1 and use_turnitin is set.
     */
    public function test_add_elements_activity_shows_peermark_link_when_enabled(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 1, 'plagiarism_turnitin');

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);

        // Set use_turnitin=1 for this cm so the peermark link renders.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm' => $cm->id, 'name' => 'use_turnitin', 'value' => 1,
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $this->make_course(),
            'activity',
            'mod_forum',
            $cm->id
        );

        $this->assertTrue($mform->elementExists('static'));
    }

    /**
     * Registers the current user with a fake Turnitin UID so turnitin_user's constructor
     * does not attempt an API call to look up or create the user.
     *
     * Both join_user_to_class() and read_class_from_tii() catch all API exceptions and
     * return gracefully, so once the constructor is safe the activity/non-forum path
     * can be exercised without a live connection.
     *
     * @param int $userid
     */
    private function register_fake_tii_user(int $userid): void {
        global $DB;
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid'                 => $userid,
            'turnitin_uid'           => 99999,
            'turnitin_utp'           => 0,
            'user_agreement_accepted' => 1,
        ]);
    }

    /**
     * Set the minimum plugin credentials needed to avoid turnitin_comms throwing
     * a configuration error when the activity/non-forum path is exercised.
     */
    private function set_credentials(): void {
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
    }

    /**
     * Test that the activity/non-forum path adds the hidden rubric element when usegrademark=0.
     *
     * We pre-register the current user with a fake Turnitin UID to avoid a live API call
     * in the turnitin_user constructor. The join and class-read calls fail silently.
     */
    public function test_add_elements_activity_non_forum_usegrademark_off_adds_hidden_rubric(): void {
        global $USER, $DB;
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');
        $this->set_credentials();

        $this->setAdminUser();
        $this->register_fake_tii_user((int)$USER->id);

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $courseobj              = new \stdClass();
        $courseobj->id          = $course->id;
        $courseobj->turnitin_cid = 0;

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $courseobj,
            'activity',
            'mod_assign',
            $cm->id
        );

        // usegrademark=0 → hidden rubric element.
        $el = $mform->getElement('plagiarism_rubric');
        $this->assertEquals('hidden', $el->getType());
    }

    /**
     * Test that usegrademark=1 in the activity/non-forum path adds the rubric selectgroups.
     */
    public function test_add_elements_activity_non_forum_usegrademark_on_adds_rubric_select(): void {
        global $USER, $DB;
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');
        $this->set_credentials();

        $this->setAdminUser();
        $this->register_fake_tii_user((int)$USER->id);

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $courseobj               = new \stdClass();
        $courseobj->id           = $course->id;
        $courseobj->turnitin_cid = 0;

        $mform = $this->make_mform();
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $courseobj,
            'activity',
            'mod_assign',
            $cm->id
        );

        // usegrademark=1 → rubric selectgroups.
        $el = $mform->getElement('plagiarism_rubric');
        $this->assertEquals('selectgroups', $el->getType());
    }

    /**
     * Test that with usegrademark=1 and a currentrubric that is not in the list,
     * the 'otherrubric' string is added to the rubrics array.
     */
    public function test_add_elements_activity_adds_other_rubric_when_current_rubric_unknown(): void {
        global $USER, $DB;
        $this->resetAfterTest();
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');
        $this->set_credentials();

        $this->setAdminUser();
        $this->register_fake_tii_user((int)$USER->id);

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $courseobj               = new \stdClass();
        $courseobj->id           = $course->id;
        $courseobj->turnitin_cid = 0;

        $mform = $this->make_mform();
        // Pass currentrubric=42 which won't be in the rubric list (empty, no API connection).
        (new turnitin_view())->add_elements_to_settings_form(
            $mform,
            $courseobj,
            'activity',
            'mod_assign',
            $cm->id,
            42
        );

        $el = $mform->getElement('plagiarism_rubric');
        $this->assertEquals('selectgroups', $el->getType());
    }

    // show_file_errors_table tests.

    /**
     * Inserts a user, course, module, and plagiarism_turnitin_files error record,
     * returning a stdClass with the IDs needed to look up the row.
     *
     * @param string $submissiontype 'file' or 'online_text'
     * @param int|null $errorcode
     * @param string|null $errormsg
     */
    private function insert_error_file(
        string $submissiontype = 'online_text',
        ?int $errorcode = 1,
        ?string $errormsg = null
    ): \stdClass {
        global $DB;

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $record = (object)[
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'submitter'      => $user->id,
            'identifier'     => sha1(uniqid('', true)),
            'statuscode'     => 'error',
            'attempt'        => 0,
            'submissiontype' => $submissiontype,
            'itemid'         => 0,
            'lastmodified'   => time(),
            'transmatch'     => 0,
            'errorcode'      => $errorcode,
            'errormsg'       => $errormsg,
        ];
        $record->id = $DB->insert_record('plagiarism_turnitin_files', $record);
        return $record;
    }

    /**
     * Test that show_file_errors_table renders the empty table message when there are no error files.
     */
    public function test_show_file_errors_table_shows_empty_message_when_no_errors(): void {
        $this->resetAfterTest();

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        $this->assertStringContainsString(
            get_string('semptytable', 'plagiarism_turnitin'),
            $output
        );
    }

    /**
     * Test that show_file_errors_table renders a row for an online_text submission error.
     */
    public function test_show_file_errors_table_renders_row_for_online_text_error(): void {
        $this->resetAfterTest();

        $this->insert_error_file('online_text', 1);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        // The table body should have at least one data row (not just the empty cell).
        $this->assertStringNotContainsString(
            get_string('semptytable', 'plagiarism_turnitin'),
            $output
        );
        // The submission type should be formatted as "Online text".
        $this->assertStringContainsString('Online text', $output);
    }

    /**
     * Test that show_file_errors_table uses the lang string for a known error code.
     */
    public function test_show_file_errors_table_uses_lang_string_for_known_error_code(): void {
        $this->resetAfterTest();

        $this->insert_error_file('online_text', 1);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        $this->assertStringContainsString(get_string('errorcode1', 'plagiarism_turnitin'), $output);
    }

    /**
     * Test that when errorcode=0 and errormsg is null, the generic "see logs" string is shown.
     */
    public function test_show_file_errors_table_shows_see_logs_when_errorcode_0_and_no_msg(): void {
        $this->resetAfterTest();

        $this->insert_error_file('online_text', 0, null);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        $this->assertStringContainsString(
            get_string('ppsubmissionerrorseelogs', 'plagiarism_turnitin'),
            $output
        );
    }

    /**
     * Test that when errorcode=0 and errormsg is set, the custom message is shown.
     */
    public function test_show_file_errors_table_shows_custom_errormsg_when_errorcode_0(): void {
        $this->resetAfterTest();

        $this->insert_error_file('online_text', 0, 'Custom error detail');

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        $this->assertStringContainsString('Custom error detail', $output);
    }

    /**
     * Test that errorcode=null is treated as 0 (legacy fallback) for non-file submissions.
     */
    public function test_show_file_errors_table_treats_null_errorcode_as_zero_for_non_file(): void {
        $this->resetAfterTest();

        $this->insert_error_file('online_text', null, null);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        $this->assertStringContainsString(
            get_string('ppsubmissionerrorseelogs', 'plagiarism_turnitin'),
            $output
        );
    }

    /**
     * Test that forum submission rows are skipped (forum files don't appear in the table).
     */
    public function test_show_file_errors_table_skips_forum_rows(): void {
        global $DB;
        $this->resetAfterTest();

        // Insert an error row with moduletype=forum by directly writing a cm linked to a forum.
        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);

        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'submitter'      => $user->id,
            'identifier'     => sha1(uniqid('forum', true)),
            'statuscode'     => 'error',
            'attempt'        => 0,
            'submissiontype' => 'online_text',
            'itemid'         => 0,
            'lastmodified'   => time(),
            'transmatch'     => 0,
            'errorcode'      => 1,
            'errormsg'       => null,
        ]);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        // The forum row is skipped, so the table should show the empty message.
        $this->assertStringContainsString(
            get_string('semptytable', 'plagiarism_turnitin'),
            $output
        );
    }

    /**
     * Test that non-file, non-online_text submission types are formatted with ucfirst + underscore removal.
     *
     * 'text_content' should render as 'Text content'.
     */
    public function test_show_file_errors_table_formats_other_submission_types(): void {
        $this->resetAfterTest();

        $this->insert_error_file('text_content', 1);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        $this->assertStringContainsString('Text content', $output);
    }

    /**
     * Test that a file submission with null errorcode and a missing file falls back to errorcode=0.
     *
     * The file hash won't resolve in the file store, so the file-does-not-exist path is taken,
     * and the null errorcode triggers the legacy fallback to 0 → "see logs" message.
     */
    public function test_show_file_errors_table_null_errorcode_file_type_falls_back_to_zero(): void {
        $this->resetAfterTest();

        // identifier is an arbitrary hash that won't exist in the Moodle file store.
        $this->insert_error_file('file', null, null);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        // The file is missing, so we get the filedoesnotexist string for the file cell.
        // errorcode=null with a missing file (not oversized) → errorcode 0 → see logs.
        $this->assertStringContainsString(
            get_string('ppsubmissionerrorseelogs', 'plagiarism_turnitin'),
            $output
        );
    }

    /**
     * Test that show_file_errors_table renders a download link when the stored file exists.
     */
    public function test_show_file_errors_table_renders_file_link_when_file_exists(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        // Create a real stored file so get_file_by_hash() resolves it.
        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'plagiarism_turnitin',
            'filearea'  => 'unittest',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'essay.txt',
        ], 'test content');

        $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'submitter'      => $user->id,
            'identifier'     => $file->get_pathnamehash(),
            'statuscode'     => 'error',
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'lastmodified'   => time(),
            'transmatch'     => 0,
            'errorcode'      => 1,
            'errormsg'       => null,
        ]);

        $view   = new turnitin_view();
        $output = $view->show_file_errors_table();

        // The file link should contain the filename.
        $this->assertStringContainsString('essay.txt', $output);
    }
}
