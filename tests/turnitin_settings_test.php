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
}
