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
 * Unit tests for classes/turnitin_settings.php.
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
 * Tests for turnitin_settings.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_settings::class)]
final class turnitin_settings_test extends \advanced_testcase {
    // Admin_config tests.

    /**
     * Test that admin_config returns a stdClass with all plugin config values.
     */
    public function test_admin_config_returns_plugin_config(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_accountid', '12345', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');

        $config = turnitin_settings::admin_config();

        $this->assertInstanceOf(\stdClass::class, $config);
        $this->assertEquals('12345', $config->plagiarism_turnitin_accountid);
        $this->assertEquals('https://api.turnitin.com', $config->plagiarism_turnitin_apiurl);
    }

    // Module_enabled tests.

    /**
     * Test that module_enabled returns the configured value for a module type.
     */
    public function test_module_enabled_returns_configured_value(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', '1', 'plagiarism_turnitin');

        $this->assertEquals('1', turnitin_settings::module_enabled('mod_assign'));
    }

    /**
     * Test that module_enabled returns false when the module is not configured.
     */
    public function test_module_enabled_returns_false_when_not_configured(): void {
        $this->resetAfterTest();

        $this->assertFalse(turnitin_settings::module_enabled('mod_assign'));
    }

    // Fields tests.

    /**
     * Test that fields returns all expected per-CM setting field names.
     */
    public function test_fields_returns_expected_field_names(): void {
        $fields = turnitin_settings::fields();

        $this->assertIsArray($fields);
        $this->assertContains('use_turnitin', $fields);
        $this->assertContains('plagiarism_report_gen', $fields);
        $this->assertContains('plagiarism_compare_internet', $fields);
        $this->assertContains('plagiarism_submitpapersto', $fields);
        // Update this count if a field is added to or removed from the settings form.
        $this->assertCount(16, $fields);
    }

    // For_cm tests.

    /**
     * Test that for_cm with a null cmid returns only the site-wide default settings,
     * which is how the site defaults page reads its current values.
     */
    public function test_for_cm_with_null_returns_site_defaults(): void {
        global $DB;
        $this->resetAfterTest();

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => null,
            'name'        => 'plagiarism_compare_internet',
            'value'       => '1',
            'config_hash' => 'plagiarism_compare_internet',
        ]);

        $settings = turnitin_settings::for_cm(null);

        $this->assertEquals('1', $settings['plagiarism_compare_internet']);
    }

    /**
     * Test that for_cm returns per-CM settings from the database.
     */
    public function test_for_cm_returns_settings_for_cm(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->cmid,
            'name'        => 'use_turnitin',
            'value'       => '1',
            'config_hash' => $cm->cmid . '_use_turnitin',
        ]);

        $settings = turnitin_settings::for_cm($cm->cmid);

        $this->assertEquals('1', $settings['use_turnitin']);
    }

    /**
     * Test that a site-wide locked value overrides the per-CM setting.
     */
    public function test_for_cm_locked_site_value_overrides_cm_setting(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        // Store a CM-level value.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->cmid,
            'name'        => 'plagiarism_compare_internet',
            'value'       => '0',
            'config_hash' => $cm->cmid . '_plagiarism_compare_internet',
        ]);

        // Store a site-wide default with a lock.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => null,
            'name'        => 'plagiarism_compare_internet',
            'value'       => '1',
            'config_hash' => 'plagiarism_compare_internet',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => null,
            'name'        => 'plagiarism_compare_internet_lock',
            'value'       => '1',
            'config_hash' => 'plagiarism_compare_internet_lock',
        ]);

        $settings = turnitin_settings::for_cm($cm->cmid);

        // Locked site value (1) should win over the CM value (0).
        $this->assertEquals('1', $settings['plagiarism_compare_internet']);
    }

    /**
     * Test that an unlocked site default does not override the per-CM setting.
     */
    public function test_for_cm_unlocked_site_value_does_not_override_cm_setting(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->cmid,
            'name'        => 'plagiarism_compare_internet',
            'value'       => '0',
            'config_hash' => $cm->cmid . '_plagiarism_compare_internet',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => null,
            'name'        => 'plagiarism_compare_internet',
            'value'       => '1',
            'config_hash' => 'plagiarism_compare_internet',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => null,
            'name'        => 'plagiarism_compare_internet_lock',
            'value'       => '0',
            'config_hash' => 'plagiarism_compare_internet_lock',
        ]);

        $settings = turnitin_settings::for_cm($cm->cmid);

        // Unlocked: CM value (0) should be preserved.
        $this->assertEquals('0', $settings['plagiarism_compare_internet']);
    }

    /**
     * Test that passing uselockedvalues=false returns raw CM settings without
     * applying site-wide locks, used during initial module creation.
     */
    public function test_for_cm_without_locked_values_returns_raw_settings(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->cmid,
            'name'        => 'plagiarism_compare_internet',
            'value'       => '0',
            'config_hash' => $cm->cmid . '_plagiarism_compare_internet',
        ]);
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => null,
            'name'        => 'plagiarism_compare_internet_lock',
            'value'       => '1',
            'config_hash' => 'plagiarism_compare_internet_lock',
        ]);

        $settings = turnitin_settings::for_cm($cm->cmid, false);

        // Even though the site lock is on, raw CM value should be returned.
        $this->assertEquals('0', $settings['plagiarism_compare_internet']);
    }

    // Set_config tests.

    /**
     * Test that set_config saves a value when the full prefixed property name is given.
     */
    public function test_set_config_saves_full_property_name(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->plagiarism_turnitin_accountid = '99999';

        turnitin_settings::set_config($data, 'plagiarism_turnitin_accountid');

        $this->assertEquals('99999', turnitin_settings::admin_config()->plagiarism_turnitin_accountid);
    }

    /**
     * Test that set_config accepts a short property name and prepends the prefix automatically.
     */
    public function test_set_config_prepends_prefix_for_short_property_name(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->secretkey = 'ABCDEFGH';

        turnitin_settings::set_config($data, 'secretkey');

        $this->assertEquals('ABCDEFGH', turnitin_settings::admin_config()->plagiarism_turnitin_secretkey);
    }

    /**
     * Test that set_config does nothing when the property is not present on the data object.
     */
    public function test_set_config_does_nothing_when_property_absent(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->something = 'value';

        turnitin_settings::set_config($data, 'notpresent');

        $config = turnitin_settings::admin_config();
        $this->assertFalse(isset($config->plagiarism_turnitin_notpresent));
    }

    // Is_plugin_configured tests.

    /**
     * Test that is_plugin_configured returns false when no credentials are set.
     */
    public function test_is_plugin_configured_returns_false_when_not_configured(): void {
        $this->resetAfterTest();

        $this->assertFalse(turnitin_settings::is_plugin_configured());
    }

    /**
     * Test that is_plugin_configured returns false when only some credentials are set.
     */
    public function test_is_plugin_configured_returns_false_with_partial_config(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        // Secretkey intentionally not set.

        $this->assertFalse(turnitin_settings::is_plugin_configured());
    }

    /**
     * Test that is_plugin_configured returns true when all three required
     * credentials are present.
     */
    public function test_is_plugin_configured_returns_true_when_fully_configured(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');

        $this->assertTrue(turnitin_settings::is_plugin_configured());
    }

    // Save_for_cm tests.

    /**
     * Test that save_for_cm does nothing when the module type has Turnitin disabled,
     * so no config rows are written for activities on unsupported module types.
     */
    public function test_save_for_cm_does_nothing_when_module_not_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        // Intentionally not setting plagiarism_turnitin_mod_assign — module is disabled.
        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        $data = (object)[
            'modulename'   => 'assign',
            'coursemodule' => $cm->cmid,
            'use_turnitin' => 1,
        ];

        turnitin_settings::save_for_cm($data);

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_config', ['cm' => $cm->cmid]));
    }

    /**
     * Test that save_for_cm inserts a new config row when no row exists yet for
     * this cm/field combination.
     */
    public function test_save_for_cm_inserts_new_row_when_field_not_set(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', '1', 'plagiarism_turnitin');

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        $data = (object)[
            'modulename'   => 'assign',
            'coursemodule' => $cm->cmid,
            'use_turnitin' => 1,
        ];

        turnitin_settings::save_for_cm($data);

        $row = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->cmid, 'name' => 'use_turnitin']);
        $this->assertNotFalse($row);
        $this->assertEquals(1, $row->value);
    }

    /**
     * Test that save_for_cm updates an existing config row rather than inserting
     * a duplicate when the field already has a value.
     */
    public function test_save_for_cm_updates_existing_row(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', '1', 'plagiarism_turnitin');

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        // Pre-insert a row so save_for_cm should update, not insert.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->cmid,
            'name'        => 'use_turnitin',
            'value'       => 0,
            'config_hash' => $cm->cmid . '_use_turnitin',
        ]);

        $data = (object)[
            'modulename'   => 'assign',
            'coursemodule' => $cm->cmid,
            'use_turnitin' => 1,
        ];

        turnitin_settings::save_for_cm($data);

        // Should still be exactly one row, with the updated value.
        $this->assertEquals(1, $DB->count_records(
            'plagiarism_turnitin_config',
            ['cm' => $cm->cmid, 'name' => 'use_turnitin']
        ));
        $row = $DB->get_record('plagiarism_turnitin_config', ['cm' => $cm->cmid, 'name' => 'use_turnitin']);
        $this->assertEquals(1, $row->value);
    }

    /**
     * Test that save_for_cm skips fields that are not present in $data, so
     * partially-submitted forms don't wipe unrelated settings.
     */
    public function test_save_for_cm_skips_fields_absent_from_data(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_assign', '1', 'plagiarism_turnitin');

        $cm = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->getDataGenerator()->create_course()->id,
        ]);

        // Data only contains use_turnitin — other fields like plagiarism_report_gen are absent.
        $data = (object)[
            'modulename'   => 'assign',
            'coursemodule' => $cm->cmid,
            'use_turnitin' => 1,
        ];

        turnitin_settings::save_for_cm($data);

        // Only use_turnitin should have been written.
        $this->assertFalse($DB->record_exists(
            'plagiarism_turnitin_config',
            ['cm' => $cm->cmid, 'name' => 'plagiarism_report_gen']
        ));
    }

    // Has_comparison_options tests.

    /**
     * Test that has_comparison_options returns false when all four comparison
     * options are disabled, which prevents submitting to Turnitin.
     */
    public function test_has_comparison_options_returns_false_when_all_disabled(): void {
        $settings = [
            'plagiarism_compare_student_papers' => 0,
            'plagiarism_compare_internet'       => 0,
            'plagiarism_compare_journals'       => 0,
            'plagiarism_compare_institution'    => 0,
        ];

        $this->assertFalse(turnitin_settings::has_comparison_options($settings));
    }

    /**
     * Test that has_comparison_options returns true when at least one option is on.
     */
    public function test_has_comparison_options_returns_true_when_one_enabled(): void {
        $settings = [
            'plagiarism_compare_student_papers' => 0,
            'plagiarism_compare_internet'       => 1,
            'plagiarism_compare_journals'       => 0,
            'plagiarism_compare_institution'    => 0,
        ];

        $this->assertTrue(turnitin_settings::has_comparison_options($settings));
    }

    /**
     * Test that has_comparison_options returns false when the keys are absent,
     * treating missing settings as disabled.
     */
    public function test_has_comparison_options_returns_false_when_keys_absent(): void {
        $this->assertFalse(turnitin_settings::has_comparison_options([]));
    }

    // Should_skip_draft tests.

    /**
     * Test that should_skip_draft returns true when draft submissions are on,
     * the draft submit setting is 1, and the event is a file upload — meaning
     * the submission should be held back from Turnitin until final submission.
     */
    public function test_should_skip_draft_returns_true_when_draft_upload(): void {
        $moduledata = (object)['submissiondrafts' => 1];
        $settings   = ['plagiarism_draft_submit' => 1];

        $this->assertTrue(turnitin_settings::should_skip_draft($moduledata, $settings, 'file_uploaded'));
        $this->assertTrue(turnitin_settings::should_skip_draft($moduledata, $settings, 'content_uploaded'));
    }

    /**
     * Test that should_skip_draft returns false when submissiondrafts is off,
     * even if the draft submit setting and event type would otherwise match.
     */
    public function test_should_skip_draft_returns_false_when_drafts_disabled(): void {
        $moduledata = (object)['submissiondrafts' => 0];
        $settings   = ['plagiarism_draft_submit' => 1];

        $this->assertFalse(turnitin_settings::should_skip_draft($moduledata, $settings, 'file_uploaded'));
    }

    /**
     * Test that should_skip_draft returns false when plagiarism_draft_submit is 0,
     * meaning drafts should be submitted immediately to Turnitin.
     */
    public function test_should_skip_draft_returns_false_when_draft_submit_is_zero(): void {
        $moduledata = (object)['submissiondrafts' => 1];
        $settings   = ['plagiarism_draft_submit' => 0];

        $this->assertFalse(turnitin_settings::should_skip_draft($moduledata, $settings, 'file_uploaded'));
    }

    /**
     * Test that should_skip_draft returns false for the assessable_submitted
     * event even when draft mode is on — final submissions always go through.
     */
    public function test_should_skip_draft_returns_false_for_final_submission_event(): void {
        $moduledata = (object)['submissiondrafts' => 1];
        $settings   = ['plagiarism_draft_submit' => 1];

        $this->assertFalse(turnitin_settings::should_skip_draft($moduledata, $settings, 'assessable_submitted'));
    }

    // Should_process_event tests.

    /**
     * Test that should_process_event returns false when use_turnitin is disabled
     * for the activity, preventing the event from being processed.
     */
    public function test_should_process_event_returns_false_when_turnitin_disabled(): void {
        $settings = ['use_turnitin' => 0];

        $this->assertFalse(turnitin_settings::should_process_event($settings, '1'));
    }

    /**
     * Test that should_process_event returns false when the module type is not
     * enabled for Turnitin, even if the individual activity has it turned on.
     */
    public function test_should_process_event_returns_false_when_module_not_enabled(): void {
        $settings = ['use_turnitin' => 1];

        $this->assertFalse(turnitin_settings::should_process_event($settings, ''));
    }

    /**
     * Test that should_process_event returns true when both use_turnitin is on
     * and the module type is enabled.
     */
    public function test_should_process_event_returns_true_when_both_enabled(): void {
        $settings = ['use_turnitin' => 1];

        $this->assertTrue(turnitin_settings::should_process_event($settings, '1'));
    }
}
