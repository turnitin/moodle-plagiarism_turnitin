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

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Unit tests for per-module default settings feature.
 *
 * Tests cover:
 *  - get_plagiarism_supported_modules()
 *  - get_enabled_supported_modules()
 *  - get_settings() with a module (loading per-module defaults)
 *  - get_settings() lock enforcement using per-module lock records
 *  - get_settings() for an existing activity with per-module lock override
 *  - Saving per-module defaults to plagiarism_turnitin_config
 *  - Loading per-module defaults for display (prefix-strip logic)
 *  - Isolation: module defaults do not bleed into other modules
 *
 * @package    plagiarism_turnitin
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('plagiarism_plugin_turnitin::get_plagiarism_supported_modules')]
#[CoversFunction('plagiarism_plugin_turnitin::get_enabled_supported_modules')]
#[CoversFunction('plagiarism_plugin_turnitin::get_settings')]
#[CoversFunction('plagiarism_plugin_turnitin::get_module_defaults')]
final class default_settings_per_module_test extends \advanced_testcase {
    /** @var \plagiarism_plugin_turnitin */
    private \plagiarism_plugin_turnitin $plugin;

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->plugin = new \plagiarism_plugin_turnitin();
    }

    /**
     * Insert a cm=NULL config record using an already-prefixed name.
     *
     * @param string $name  e.g. 'assign_use_turnitin'
     * @param mixed  $value
     */
    private function insert_default(string $name, $value): void {
        global $DB;
        $record              = new \stdClass();
        $record->cm          = null;
        $record->name        = $name;
        $record->value       = $value;
        $record->config_hash = $record->cm . $record->name;
        $DB->insert_record('plagiarism_turnitin_config', $record);
    }

    /**
     * Insert a per-activity config record.
     *
     * @param int    $cmid
     * @param string $name  plain field name, e.g. 'use_turnitin'
     * @param mixed  $value
     */
    private function insert_cm_setting(int $cmid, string $name, $value): void {
        global $DB;
        $record              = new \stdClass();
        $record->cm          = $cmid;
        $record->name        = $name;
        $record->value       = $value;
        $record->config_hash = $cmid . '_' . $name;
        $DB->insert_record('plagiarism_turnitin_config', $record);
    }

    /**
     * Enable a module in the Turnitin plugin config.
     *
     * @param string $modulename module name e.g. 'mod_assign' (the admin config key uses the bare name)
     */
    private function enable_module(string $modulename): void {
        set_config('plagiarism_turnitin_' . $modulename, 1, 'plagiarism_turnitin');
    }

    /**
     * Test that the returned list is sorted alphabetically.
     */
    public function test_get_plagiarism_supported_modules_is_sorted(): void {
        $modules = $this->plugin->get_plagiarism_supported_modules();
        $sorted  = $modules;
        sort($sorted);

        $this->assertEquals($sorted, $modules, 'Supported modules list should be sorted alphabetically');
    }

    /**
     * Test that every module returned by get_plagiarism_supported_modules() actually
     * declares FEATURE_PLAGIARISM, and that at least one such module is present,
     * and the module names contain prefix mod_.
     */
    public function test_get_plagiarism_supported_modules_contains_known_modules(): void {
        // Dynamically collect all installed modules that declare FEATURE_PLAGIARISM.
        $expectedmodules = [];
        foreach (array_keys(\core_component::get_plugin_list('mod')) as $mod) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $expectedmodules[] = 'mod_' . $mod;
            }
        }

        if (empty($expectedmodules)) {
            $this->markTestSkipped('No installed module declares FEATURE_PLAGIARISM support.');
        }

        $modules = $this->plugin->get_plagiarism_supported_modules();

        foreach ($expectedmodules as $modcomponent) {
            $this->assertContains(
                $modcomponent,
                $modules,
                "Module '$modcomponent' declares FEATURE_PLAGIARISM but is missing from get_plagiarism_supported_modules()."
            );
        }
    }

    /**
     * Test that no modules are returned when none are enabled.
     */
    public function test_get_enabled_supported_modules_empty_when_none_enabled(): void {
        $enabled = $this->plugin->get_enabled_supported_modules();

        $this->assertIsArray($enabled);
        $this->assertEmpty($enabled);
    }

    /**
     * Test that only the explicitly enabled module appears.
     * get_enabled_supported_modules() returns component-format names e.g. 'mod_assign'.
     */
    public function test_get_enabled_supported_modules_returns_enabled_only(): void {
        $this->enable_module('mod_assign');

        $enabled = $this->plugin->get_enabled_supported_modules();

        $this->assertCount(1, $enabled);
        $this->assertContains('mod_assign', $enabled);
        $this->assertNotContains('mod_forum', $enabled);
    }

    /**
     * Test that multiple enabled modules all appear.
     */
    public function test_get_enabled_supported_modules_multiple_enabled(): void {
        $this->enable_module('mod_assign');
        $this->enable_module('mod_forum');

        $enabled = $this->plugin->get_enabled_supported_modules();

        $this->assertCount(2, $enabled);
        $this->assertContains('mod_assign', $enabled);
        $this->assertContains('mod_forum', $enabled);
    }

    /**
     * Test that a module not declaring FEATURE_PLAGIARISM is never returned
     * even if its config key is set.
     */
    public function test_get_enabled_supported_modules_ignores_non_plagiarism_modules(): void {
        // Dynamically locate the first installed module that does NOT declare FEATURE_PLAGIARISM.
        $nonsupportingmod = null;
        foreach (array_keys(\core_component::get_plugin_list('mod')) as $mod) {
            if (!plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $nonsupportingmod = $mod;
                break;
            }
        }

        if ($nonsupportingmod === null) {
            $this->markTestSkipped('No installed module found that lacks FEATURE_PLAGIARISM support.');
        }

        $modcomponent = 'mod_' . $nonsupportingmod;
        set_config('plagiarism_turnitin_' . $modcomponent, 1, 'plagiarism_turnitin');

        $enabled = $this->plugin->get_enabled_supported_modules();

        $this->assertNotContains(
            $modcomponent,
            $enabled,
            "Module '$modcomponent' does not declare FEATURE_PLAGIARISM and must not appear in enabled modules."
        );
    }

    /**
     * Test that get_settings(null, true, 'mod_assign') returns mod_assign-prefixed
     * records with the prefix stripped.
     */
    public function test_get_settings_returns_module_defaults_stripped(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_assign_plagiarism_show_student_report', 0);
        // Forum record must NOT bleed through.
        $this->insert_default('mod_forum_use_turnitin', 0);

        $settings = $this->plugin->get_settings(null, true, 'mod_assign');

        $this->assertArrayHasKey('use_turnitin', $settings);
        $this->assertEquals(1, $settings['use_turnitin']);
        $this->assertArrayHasKey('plagiarism_show_student_report', $settings);
        $this->assertEquals(0, $settings['plagiarism_show_student_report']);
        $this->assertArrayNotHasKey('mod_forum_use_turnitin', $settings);
    }

    /**
     * Test that mod_assign and mod_forum modules return different values for the
     * same field when they have different defaults stored.
     */
    public function test_get_settings_isolates_module_defaults(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_forum_use_turnitin', 0);

        $assignsettings = $this->plugin->get_settings(null, true, 'mod_assign');
        $forumsettings  = $this->plugin->get_settings(null, true, 'mod_forum');

        $this->assertEquals(1, $assignsettings['use_turnitin']);
        $this->assertEquals(0, $forumsettings['use_turnitin']);
    }

    /**
     * Test that calling get_settings without a module returns all cm=NULL
     */
    public function test_get_settings_without_module_returns_all_defaults(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_forum_use_turnitin', 0);

        $settings = $this->plugin->get_settings();

        $this->assertArrayHasKey('mod_assign_use_turnitin', $settings);
        $this->assertArrayHasKey('mod_forum_use_turnitin', $settings);
    }

    /**
     * Test that a locked mod_assign default overrides an activity's own value.
     */
    public function test_get_settings_lock_overrides_activity_value(): void {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        // Activity stores use_turnitin = 0.
        $this->insert_cm_setting($cm->id, 'use_turnitin', 0);

        // Per-module default locked to 1.
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_assign_use_turnitin_lock', 1);

        $settings = $this->plugin->get_settings($cm->id, true, 'mod_assign');

        // Lock must override activity's 0 → enforce 1.
        $this->assertEquals(1, $settings['use_turnitin']);
    }

    /**
     * Test that an unlocked default does NOT override an activity's own value.
     */
    public function test_get_settings_unlocked_default_does_not_override(): void {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $this->insert_cm_setting($cm->id, 'plagiarism_compare_internet', 0);

        // Default says 1 but lock = 0 (not locked).
        $this->insert_default('mod_assign_plagiarism_compare_internet', 1);
        $this->insert_default('mod_assign_plagiarism_compare_internet_lock', 0);

        $settings = $this->plugin->get_settings($cm->id, true, 'mod_assign');

        // Activity's own 0 must be preserved.
        $this->assertEquals(0, $settings['plagiarism_compare_internet']);
    }

    /**
     * Test get_settings should show course module settings, not default settings.
     */
    public function test_get_settings_use_locked_values_false_skips_enforcement(): void {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $this->insert_cm_setting($cm->id, 'use_turnitin', 0);
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_assign_use_turnitin_lock', 1);

        $settings = $this->plugin->get_settings($cm->id, false, 'mod_assign');

        // Activity's own value is returned.
        $this->assertEquals(0, $settings['use_turnitin']);
    }

    /**
     * Test that the display loading logic strips the module prefix correctly
     * and excludes records from other modules.
     */
    public function test_display_loading_strips_prefix(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_assign_plagiarism_show_student_report', 0);
        $this->insert_default('mod_assign_plagiarism_locked_message', 'Locked by admin');
        $this->insert_default('mod_forum_use_turnitin', 0);

        $moddefaults = $this->plugin->get_module_defaults('mod_assign');

        $this->assertArrayHasKey('use_turnitin', $moddefaults);
        $this->assertEquals(1, $moddefaults['use_turnitin']);
        $this->assertArrayHasKey('plagiarism_show_student_report', $moddefaults);
        $this->assertEquals(0, $moddefaults['plagiarism_show_student_report']);
        $this->assertArrayHasKey('plagiarism_locked_message', $moddefaults);
        $this->assertEquals('Locked by admin', $moddefaults['plagiarism_locked_message']);

        // Forum key and prefixed key must not appear.
        $this->assertArrayNotHasKey('mod_forum_use_turnitin', $moddefaults);
        $this->assertArrayNotHasKey('mod_assign_use_turnitin', $moddefaults);
    }

    /**
     * Test display loading returns an empty array when no records exist for
     * the requested module.
     */
    public function test_display_loading_empty_for_unknown_module(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);

        $moddefaults = $this->plugin->get_module_defaults('mod_quiz');

        $this->assertEmpty($moddefaults);
    }

    /**
     * Test a full round-trip: write per-module defaults, then read them back
     * via get_settings and confirm values are correct.
     */
    public function test_round_trip_save_and_load_defaults(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_assign_plagiarism_report_gen', 2);
        $this->insert_default('mod_assign_plagiarism_compare_internet', 1);
        $this->insert_default('mod_assign_plagiarism_compare_student_papers', 0);

        $settings = $this->plugin->get_settings(null, true, 'mod_assign');

        $this->assertEquals(1, $settings['use_turnitin']);
        $this->assertEquals(2, $settings['plagiarism_report_gen']);
        $this->assertEquals(1, $settings['plagiarism_compare_internet']);
        $this->assertEquals(0, $settings['plagiarism_compare_student_papers']);
    }

    /**
     * Test that mod_assign and mod_forum return independent default sets after writing
     * different values for each.
     */
    public function test_round_trip_independent_defaults_per_module(): void {
        $this->insert_default('mod_assign_use_turnitin', 1);
        $this->insert_default('mod_assign_plagiarism_compare_internet', 1);

        $this->insert_default('mod_forum_use_turnitin', 0);
        $this->insert_default('mod_forum_plagiarism_compare_internet', 0);

        $assign = $this->plugin->get_settings(null, true, 'mod_assign');
        $forum  = $this->plugin->get_settings(null, true, 'mod_forum');

        $this->assertEquals(1, $assign['use_turnitin']);
        $this->assertEquals(1, $assign['plagiarism_compare_internet']);
        $this->assertEquals(0, $forum['use_turnitin']);
        $this->assertEquals(0, $forum['plagiarism_compare_internet']);
    }
}
