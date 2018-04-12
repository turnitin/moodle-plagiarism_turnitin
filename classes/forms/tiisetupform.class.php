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
 * Plugin setup form for plagiarism_turnitin component
 *
 * @package   plagiarism_turnitin
 * @copyright 2018 David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir."/formslib.php");

class tiisetupform extends moodleform {

    // Define the form.
    public function definition() {
        $mform =& $this->_form;

        $mform->disable_form_change_checker();

        $mform->addElement('header', 'config', get_string('turnitinconfig', 'plagiarism_turnitin'));
        $mform->addElement('html', get_string('tiiexplain', 'plagiarism_turnitin'));

        $mform->addElement('advcheckbox', 'turnitin_use', get_string('useturnitin', 'plagiarism_turnitin'), '', null, array(0, 1));

        // Loop through all modules that support Plagiarism.
        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modpath) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $mform->addElement('advcheckbox',
                    'turnitinmodenabled'.$mod,
                    get_string('useturnitin_mod', 'plagiarism_turnitin', ucfirst($mod)),
                    '',
                    null,
                    array(0, 1)
                );
            }
        }
        //TODO: Ensure the coursework module appears.

        $mform->addElement('header', 'turnitinconfig', get_string('tiiaccountconfig', 'plagiarism_turnitin'));

        $this->add_action_buttons(false);
    }

    /**
     * Display the form, saving the contents of the output buffer overriding Moodle's
     * display function that prints to screen when called
     *
     * @return the form as an object to print to screen at our convenience
     */
    public function display() {
        ob_start();
        parent::display();
        $form = ob_get_contents();
        ob_end_clean();

        return $form;
    }
}
