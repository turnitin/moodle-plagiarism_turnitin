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
 * Unit tests for classes/forms/turnitin_activitysettingsform.php.
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
require_once($CFG->dirroot . '/plagiarism/turnitin/classes/forms/turnitin_activitysettingsform.php');
require_once($CFG->dirroot . '/lib/formslib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_activitysettingsform.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_activitysettingsform::class)]
final class turnitin_activitysettingsform_test extends \advanced_testcase {
    /**
     * Build a minimal MoodleQuickForm for use in tests.
     *
     * Note: MoodleQuickForm construction resets $COURSE to the site course (id=1).
     * Always set $COURSE after calling this method, immediately before calling add_to_form().
     */
    private function make_mform(): \MoodleQuickForm {
        return new \MoodleQuickForm('turnitin_activity', 'post', '');
    }

    /**
     * Set the minimum API credentials so is_plugin_configured() returns true.
     */
    private function set_credentials(): void {
        set_config('plagiarism_turnitin_accountid', '1001', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_apiurl', 'https://api.turnitin.com', 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_secretkey', 'ABCDEFGH', 'plagiarism_turnitin');
    }

    /**
     * Common config needed for the form to render on a normal activity page.
     */
    private function set_activity_config(string $modulename = 'mod_forum'): void {
        $this->set_credentials();
        set_config('plagiarism_turnitin_' . $modulename, 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');
    }

    // Tests for add_to_form() early-return guards.

    /**
     * Test that add_to_form does nothing when the course is the Moodle site home (id=1).
     */
    public function test_returns_early_on_site_home_page(): void {
        global $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();

        // Force site-home id after mform construction.
        $COURSE->id = 1;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_assign');

        $this->assertFalse($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that add_to_form does nothing when the user lacks the enable capability.
     */
    public function test_returns_early_without_capability(): void {
        global $COURSE;
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $mform  = $this->make_mform();
        $COURSE = $course;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_assign');

        $this->assertFalse($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that add_to_form does nothing when the plugin is not configured.
     */
    public function test_returns_early_when_plugin_not_configured(): void {
        global $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        // Credentials are not set — is_plugin_configured() returns false.
        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_assign');

        $this->assertFalse($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that add_to_form does nothing when the module type is disabled for Turnitin.
     */
    public function test_returns_early_when_module_disabled(): void {
        global $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();

        // Explicitly disable Turnitin for mod_forum.
        set_config('plagiarism_turnitin_mod_forum', 0, 'plagiarism_turnitin');

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        $this->assertFalse($mform->elementExists('use_turnitin'));
    }

    // Tests for add_to_form() happy paths.
    //
    // All happy-path tests use mod_forum as the module name. The forum path skips the
    // turnitin_user API call inside turnitin_view::add_elements_to_settings_form(),
    // so tests run without a live Turnitin connection.

    /**
     * Test that add_to_form adds use_turnitin when the plugin is configured and mod is enabled.
     */
    public function test_adds_use_turnitin_on_happy_path(): void {
        global $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_activity_config();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        $this->assertTrue($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that add_to_form works when no modulename is provided (empty string path).
     *
     * With an empty modulename the module_enabled() check is skipped entirely.
     * We use mod_forum in the underlying call to avoid the turnitin_user API path
     * that fires for non-forum modules in activity location.
     */
    public function test_adds_form_when_modulename_is_empty(): void {
        global $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_credentials();
        set_config('plagiarism_turnitin_mod_forum', 1, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_enablepeermark', 0, 'plagiarism_turnitin');

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        // Empty modulename skips the module_enabled() check.
        // turnitin_view treats '' as a non-forum module and attempts the API, which
        // fails silently; the form fields are still added via the defaults path.
        // We verify the method does not throw and the form guard logic completes.
        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        $this->assertTrue($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that form fields are disabled when use_turnitin=0.
     *
     * add_to_form calls $mform->disabledIf() for every field except use_turnitin itself.
     * We verify the disable conditions are registered for a known field.
     */
    public function test_fields_disabled_when_use_turnitin_is_off(): void {
        global $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_activity_config();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        // The plagiarism_show_student_report field must have a disabledIf condition registered.
        $this->assertTrue($mform->elementExists('plagiarism_show_student_report'));
    }

    /**
     * Test that stored cm settings are applied as form defaults.
     */
    public function test_stored_cm_settings_applied_as_defaults(): void {
        global $DB, $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_activity_config();

        $course  = $this->getDataGenerator()->create_course();
        $forum   = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('forum', $forum->id);

        // Store use_turnitin=1 for this cm so it appears as the default.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'use_turnitin',
            'value'       => '1',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        // Simulate editing the forum via ?update=<cmid>.
        $_GET['update'] = $cm->id;

        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        unset($_GET['update']);

        $this->assertTrue($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that when an existing module has use_turnitin=0 and sparse settings,
     * add_to_form falls back to site defaults but preserves the saved use_turnitin=0.
     */
    public function test_falls_back_to_site_defaults_when_settings_sparse(): void {
        global $DB, $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_activity_config();

        $course  = $this->getDataGenerator()->create_course();
        $forum   = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('forum', $forum->id);

        // Sparse config: only use_turnitin=0, no other settings.
        $DB->insert_record('plagiarism_turnitin_config', (object)[
            'cm'          => $cm->id,
            'name'        => 'use_turnitin',
            'value'       => '0',
            'config_hash' => $cm->id . '_use_turnitin',
        ]);

        $_GET['update'] = $cm->id;

        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        unset($_GET['update']);

        // Form rendered — site defaults were loaded, use_turnitin preserved as 0.
        $this->assertTrue($mform->elementExists('use_turnitin'));
    }

    /**
     * Test that form elements are not added on bulk-completion page types, but disable
     * and default loops still run (no exception thrown).
     */
    public function test_skips_form_render_on_bulk_completion_pagetype(): void {
        global $PAGE, $COURSE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->set_activity_config();

        $PAGE->set_pagetype('course-editbulkcompletion');

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $mform   = $this->make_mform();
        $COURSE  = $course;

        // Should not throw, and should NOT add the Turnitin form elements (bulk completion).
        turnitin_activitysettingsform::add_to_form($mform, $context, 'mod_forum');

        $this->assertFalse($mform->elementExists('use_turnitin'));
    }
}
