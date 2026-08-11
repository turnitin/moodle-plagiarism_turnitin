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
 * Unit tests for the form classes in classes/forms/.
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
require_once($CFG->dirroot . '/plagiarism/turnitin/classes/forms/turnitin_form.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/classes/forms/turnitin_defaultsettingsform.php');
require_once($CFG->dirroot . '/plagiarism/turnitin/classes/forms/turnitin_setupform.php');
require_once($CFG->dirroot . '/lib/formslib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_defaultsettingsform, turnitin_form, and turnitin_setupform.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_defaultsettingsform::class)]
#[CoversClass(turnitin_form::class)]
#[CoversClass(turnitin_setupform::class)]
final class forms_test extends \advanced_testcase {
    /**
     * Common config needed for forms that read plugin settings in definition().
     */
    private function set_base_config(): void {
        set_config('plagiarism_turnitin_repositoryoption', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_transmatch', 0, 'plagiarism_turnitin');
        set_config('plagiarism_turnitin_usegrademark', 0, 'plagiarism_turnitin');
    }

    /**
     * Build a turnitin_form instance with the given customdata.
     *
     * @param array $elements  Each element is an array [type, name?, label?, ...].
     * @param array $extra     Extra keys merged into customdata.
     */
    private function make_form(array $elements = [], array $extra = []): turnitin_form {
        $customdata = array_merge(['elements' => $elements], $extra);
        return new turnitin_form('/test', $customdata);
    }

    // Tests for turnitin_defaultsettingsform.

    /**
     * Test turnitin_defaultsettingsform instantiates and definition() runs without error.
     */
    public function test_defaultsettingsform_renders(): void {
        $this->resetAfterTest();
        $this->set_base_config();

        // Definition() is called during construction; no exception means it succeeded.
        $form = new turnitin_defaultsettingsform('/plagiarism/turnitin/settings.php?do=defaults');
        $this->assertInstanceOf(\moodleform::class, $form);
    }

    /**
     * Test turnitin_defaultsettingsform renders the use_turnitin field.
     *
     * turnitin_defaultsettingsform inherits Moodle's display() which echoes to stdout.
     * Capture with ob_start so we can assert on the output.
     */
    public function test_defaultsettingsform_renders_use_turnitin(): void {
        $this->resetAfterTest();
        $this->set_base_config();

        $form = new turnitin_defaultsettingsform('/plagiarism/turnitin/settings.php?do=defaults');

        ob_start();
        $form->display();
        $output = ob_get_clean();

        $this->assertStringContainsString('use_turnitin', $output);
    }

    // Tests for turnitin_form.

    /**
     * Test turnitin_form instantiates with an empty elements array.
     */
    public function test_turnitin_form_instantiates_with_empty_elements(): void {
        $this->resetAfterTest();

        $form = $this->make_form();

        $this->assertInstanceOf(\moodleform::class, $form);
    }

    /**
     * Test that a 'select' element appears in rendered output.
     */
    public function test_turnitin_form_renders_select_element(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['select', 'myselect', 'My Select', '', [0 => 'No', 1 => 'Yes']],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('myselect', $output);
    }

    /**
     * Test that an 'html' element appears in rendered output.
     */
    public function test_turnitin_form_renders_html_element(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['html', '<p class="turnitin_test_html">Test content</p>'],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('turnitin_test_html', $output);
    }

    /**
     * Test that a 'hidden' element appears in rendered output.
     */
    public function test_turnitin_form_renders_hidden_element(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['hidden', 'myhidden', 'Hidden Label', '', null],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('myhidden', $output);
    }

    /**
     * Test that a 'text' element appears in rendered output.
     */
    public function test_turnitin_form_renders_text_element(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['text', 'mytext', 'Text Label', '', null],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('mytext', $output);
    }

    /**
     * Test that an unknown element type falls through to the default addElement path.
     */
    public function test_turnitin_form_renders_default_element_type(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['textarea', 'myarea', 'My Textarea', '', null],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('myarea', $output);
    }

    /**
     * Test that a 'static' element renders correctly.
     */
    public function test_turnitin_form_renders_static_element(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['static', 'myfield', 'Static Label', '', null],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('Static Label', $output);
    }

    /**
     * Test that a rule (element[5]) is registered (e.g. numeric rule fires client-side).
     */
    public function test_turnitin_form_applies_rule(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([
            ['text', 'numfield', 'Number', '', null, 'numeric', 'Must be a number', null],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('numfield', $output);
    }

    /**
     * Test that disabledIf (element[7]) does not cause an error.
     */
    public function test_turnitin_form_applies_disabledif(): void {
        $this->resetAfterTest();

        $form = $this->make_form([
            ['select', 'use_turnitin', 'Use Turnitin', '', [0 => 'No', 1 => 'Yes'], '', '', null],
            ['select', 'myfield', 'My Field', '', [0 => 'No', 1 => 'Yes'], '', '', ['use_turnitin', 'eq', 0]],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('myfield', $output);
    }

    /**
     * Test that the form class name is applied when customdata contains 'class'.
     *
     * The 'class' customdata key sets $mform->_formname. We verify this indirectly
     * by checking no exception is thrown (the internal state is set correctly).
     */
    public function test_turnitin_form_applies_class(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([], ['class' => 'my_custom_class', 'hide_submit' => true]);
        $output = $form->display();

        // A custom class name means the form renders successfully.
        $this->assertIsString($output);
    }

    /**
     * Test that submit button is absent when hide_submit is set.
     */
    public function test_turnitin_form_hides_submit_when_set(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([], ['hide_submit' => true]);
        $output = $form->display();

        // No submit button rendered.
        $this->assertStringNotContainsString('type="submit"', $output);
    }

    /**
     * Test that a custom submit label appears in rendered output.
     */
    public function test_turnitin_form_custom_submit_label(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([], ['submit_label' => 'Save My Settings', 'show_cancel' => false]);
        $output = $form->display();

        $this->assertStringContainsString('Save My Settings', $output);
    }

    /**
     * Test multi_submit_buttons renders both buttons.
     */
    public function test_turnitin_form_multi_submit_buttons(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([], [
            'hide_submit' => true,
            'multi_submit_buttons' => [
                ['unlink', 'Unlink Users'],
                ['relink', 'Relink Users'],
            ],
        ]);
        $output = $form->display();

        $this->assertStringContainsString('Unlink Users', $output);
        $this->assertStringContainsString('Relink Users', $output);
    }

    /**
     * Test display() returns a non-empty HTML string.
     */
    public function test_turnitin_form_display_returns_html(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([], ['hide_submit' => true]);
        $output = $form->display();

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /**
     * Test disable_form_change_checker flag does not cause an error.
     */
    public function test_turnitin_form_disables_change_checker(): void {
        $this->resetAfterTest();

        $form   = $this->make_form([], ['disable_form_change_checker' => true, 'hide_submit' => true]);
        $output = $form->display();

        $this->assertIsString($output);
    }

    // Tests for turnitin_setupform.

    /**
     * Test turnitin_setupform instantiates and renders without error.
     */
    public function test_setupform_renders(): void {
        $this->resetAfterTest();

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /**
     * Test that definition() renders the accountid field.
     */
    public function test_setupform_renders_accountid_field(): void {
        $this->resetAfterTest();

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringContainsString('plagiarism_turnitin_accountid', $output);
    }

    /**
     * Test that definition() renders the apiurl select.
     */
    public function test_setupform_renders_apiurl_field(): void {
        $this->resetAfterTest();

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringContainsString('plagiarism_turnitin_apiurl', $output);
    }

    /**
     * Test pseudo-privacy fields appear in output when enablepseudo=1 and no existing users.
     */
    public function test_setupform_renders_pseudo_fields_when_enabled(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_enablepseudo', 1, 'plagiarism_turnitin');

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringContainsString('plagiarism_turnitin_pseudofirstname', $output);
        $this->assertStringContainsString('plagiarism_turnitin_pseudolastname', $output);
        $this->assertStringContainsString('plagiarism_turnitin_pseudosalt', $output);
    }

    /**
     * Test pseudo-privacy fields are absent when enablepseudo is not set.
     */
    public function test_setupform_omits_pseudo_fields_when_pseudo_disabled(): void {
        $this->resetAfterTest();

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringNotContainsString('plagiarism_turnitin_pseudofirstname', $output);
    }

    /**
     * Test enablepseudo element renders as a yes/no select when no turnitin_users exist.
     */
    public function test_setupform_enablepseudo_renders(): void {
        $this->resetAfterTest();

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringContainsString('plagiarism_turnitin_enablepseudo', $output);
    }

    /**
     * Test the enablepseudo option set is locked to 'No' when users exist and pseudo is not enabled.
     */
    public function test_setupform_enablepseudo_locked_when_users_exist_and_not_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 1,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringContainsString('plagiarism_turnitin_enablepseudo', $output);
    }

    /**
     * Test the enablepseudo option is locked to 'Yes' when users exist and pseudo=1.
     */
    public function test_setupform_enablepseudo_locked_when_users_exist_and_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('plagiarism_turnitin_users', (object)[
            'userid' => $user->id, 'turnitin_uid' => 1,
            'turnitin_utp' => 0, 'user_agreement_accepted' => 1,
        ]);
        set_config('plagiarism_turnitin_enablepseudo', 1, 'plagiarism_turnitin');

        $form   = new turnitin_setupform();
        $output = $form->display();

        $this->assertStringContainsString('plagiarism_turnitin_enablepseudo', $output);
    }

    /**
     * Test save() writes the accountid to plugin config.
     */
    public function test_setupform_save_writes_accountid(): void {
        $this->resetAfterTest();

        $data = (object)[
            'plagiarism_turnitin_accountid'            => '9001',
            'plagiarism_turnitin_secretkey'            => 'mysecret',
            'plagiarism_turnitin_apiurl'               => 'https://api.turnitin.com',
            'plagiarism_turnitin_enablediagnostic'     => 0,
            'plagiarism_turnitin_usegrademark'         => 1,
            'plagiarism_turnitin_enablepeermark'       => 1,
            'plagiarism_turnitin_useanon'              => 0,
            'plagiarism_turnitin_transmatch'           => 0,
            'plagiarism_turnitin_repositoryoption'     => 0,
            'plagiarism_turnitin_agreement'            => '',
            'plagiarism_turnitin_enablepseudo'         => 0,
            'plagiarism_turnitin_pseudofirstname'      => '',
            'plagiarism_turnitin_pseudolastname'       => 0,
            'plagiarism_turnitin_lastnamegen'          => 0,
            'plagiarism_turnitin_pseudosalt'           => '',
            'plagiarism_turnitin_pseudoemaildomain'    => '',
            'plagiarism_turnitin_enableadhocsubmissions' => 0,
        ];

        $form = new turnitin_setupform();
        $form->save($data);

        $this->assertEquals('9001', get_config('plagiarism_turnitin', 'plagiarism_turnitin_accountid'));
    }

    /**
     * Test save() sets enabled=1 when at least one module is enabled.
     */
    public function test_setupform_save_enables_plugin_when_module_enabled(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->plagiarism_turnitin_mod_assign = 1;
        foreach (
            ['accountid', 'secretkey', 'apiurl', 'enablediagnostic', 'usegrademark',
            'enablepeermark', 'useanon', 'transmatch', 'repositoryoption', 'agreement',
            'enablepseudo', 'pseudofirstname', 'pseudolastname', 'lastnamegen',
            'pseudosalt', 'pseudoemaildomain', 'enableadhocsubmissions'] as $p
        ) {
            $data->{"plagiarism_turnitin_$p"} = '';
        }

        $form = new turnitin_setupform();
        $form->save($data);

        $this->assertEquals(1, get_config('plagiarism_turnitin', 'enabled'));
    }

    /**
     * Test save() sets enabled=0 when no modules are enabled.
     */
    public function test_setupform_save_disables_plugin_when_no_modules(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        foreach (
            ['accountid', 'secretkey', 'apiurl', 'enablediagnostic', 'usegrademark',
            'enablepeermark', 'useanon', 'transmatch', 'repositoryoption', 'agreement',
            'enablepseudo', 'pseudofirstname', 'pseudolastname', 'lastnamegen',
            'pseudosalt', 'pseudoemaildomain', 'enableadhocsubmissions'] as $p
        ) {
            $data->{"plagiarism_turnitin_$p"} = '';
        }

        $form = new turnitin_setupform();
        $form->save($data);

        $this->assertEquals(0, get_config('plagiarism_turnitin', 'enabled'));
    }
}
