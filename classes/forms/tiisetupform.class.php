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

require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
require_once($CFG->libdir."/formslib.php");

class tiisetupform extends moodleform {

    // Define the form.
    public function definition() {
        global $DB;

        // TODO: Change this to be PP config.
        $config = turnitintooltwo_admin_config();

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
                    'turnitin_use_mod_'.$mod,
                    get_string('useturnitin_mod', 'plagiarism_turnitin', ucfirst($mod)),
                    '',
                    null,
                    array(0, 1)
                );
            }
        }
        // TODO: Ensure the coursework module appears.

        $mform->addElement('header', 'plagiarism_turnitinconfig', get_string('tiiaccountconfig', 'plagiarism_turnitin'));
        $mform->setExpanded('plagiarism_turnitinconfig');

        $mform->addElement('text', 'plagiarism_accountid', get_string('turnitinaccountid', 'plagiarism_turnitin'));
        $mform->setType('plagiarism_accountid', PARAM_TEXT);

        $mform->addElement('passwordunmask', 'plagiarism_secretkey', get_string('turnitinsecretkey', 'plagiarism_turnitin'));

        $options = array(
            'https://api.turnitin.com' => 'Turnitin Global',
            'https://api.turnitinuk.com' => 'Turnitin UK',
            'https://sandbox.turnitin.com' => 'Sandbox'
        );
        $mform->addElement('select', 'plagiarism_apiurl', get_string('turnitinapiurl', 'plagiarism_turnitin'), $options);

        $mform->addElement('button', 'connection_test', get_string("connecttest", 'plagiarism_turnitin'));

        $mform->addElement('header', 'plagiarism_debugginglogs', get_string('tiidebugginglogs', 'plagiarism_turnitin'));
        $mform->setExpanded('plagiarism_debugginglogs');

        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));
        $diagnosticoptions = array(
            0 => get_string('diagnosticoptions_0', 'turnitintooltwo'),
            1 => get_string('diagnosticoptions_1', 'turnitintooltwo'),
            2 => get_string('diagnosticoptions_2', 'turnitintooltwo')
        );

        // Debugging and logging settings.
        $mform->addElement('select', 'plagiarism_enablediagnostic', get_string('turnitindiagnostic', 'plagiarism_turnitin'), $ynoptions);
        $mform->addElement('static', 'plagiarism_enablediagnostic_desc', null, get_string('turnitindiagnostic_desc', 'plagiarism_turnitin'));

        $mform->addElement('select', 'plagiarism_enableperformancelogs', get_string('enableperformancelogs', 'plagiarism_turnitin'), $diagnosticoptions);
        $mform->addElement('static', 'plagiarism_enableperformancelogs_desc', null, get_string('enableperformancelogs_desc', 'plagiarism_turnitin'));

        $mform->addElement('header', 'plagiarism_accountsettings', get_string('tiiaccountsettings', 'plagiarism_turnitin'));
        $mform->setExpanded('plagiarism_accountsettings');

        $mform->addElement('html', '<div class="tii_checkagainstnote">'.get_string('tiiaccountsettings_desc', 'plagiarism_turnitin').'</div>');

        // Turnitin account settings.
        $mform->addElement('select', 'plagiarism_usegrademark', get_string('turnitinusegrademark', 'plagiarism_turnitin'), $ynoptions);
        $mform->addElement('static', 'plagiarism_usegrademark_desc', null, get_string('turnitinusegrademark_desc', 'plagiarism_turnitin'));
        $mform->setDefault('plagiarism_usegrademark', 1);

        $mform->addElement('select', 'plagiarism_enablepeermark', get_string('turnitinenablepeermark', 'plagiarism_turnitin'), $ynoptions);
        $mform->addElement('static', 'plagiarism_enablepeermark_desc', null, get_string('turnitinenablepeermark_desc', 'plagiarism_turnitin'));
        $mform->setDefault('plagiarism_enablepeermark', 1);

        $mform->addElement('select', 'plagiarism_useerater', get_string('turnitinuseerater', 'plagiarism_turnitin'), $ynoptions);
        $mform->addElement('static', 'plagiarism_useerater_desc', null, get_string('turnitinuseerater_desc', 'plagiarism_turnitin'));
        $mform->setDefault('plagiarism_useerater', 0);

        $mform->addElement('select', 'plagiarism_transmatch', get_string('transmatch', 'plagiarism_turnitin'), $ynoptions);
        $mform->addElement('static', 'plagiarism_transmatch_desc', null, get_string('transmatch_desc', 'plagiarism_turnitin'));
        $mform->setDefault('plagiarism_transmatch', 0);

        $repositoryoptions = array(
            0 => get_string('repositoryoptions_0', 'turnitintooltwo'),
            1 => get_string('repositoryoptions_1', 'turnitintooltwo'),
            2 => get_string('repositoryoptions_2', 'turnitintooltwo'),
            3 => get_string('repositoryoptions_3', 'turnitintooltwo')
        );

        $mform->addElement('select', 'plagiarism_repositoryoption', get_string('turnitinrepositoryoptions', 'plagiarism_turnitin'), $repositoryoptions);
        $mform->addElement('static', 'plagiarism_repositoryoption_desc', null, get_string('turnitinrepositoryoptions_desc', 'plagiarism_turnitin'));
        $mform->setDefault('plagiarism_repositoryoption', 0);

        // Miscellaneous settings.
        $mform->addElement('header', 'plagiarism_miscsettings', get_string('tiimiscsettings', 'plagiarism_turnitin'));
        $mform->setExpanded('plagiarism_miscsettings');

        $mform->addElement('textarea', 'plagiarism_agreement', get_string("turnitintooltwoagreement", "plagiarism_turnitin"), 'wrap="virtual" rows="10" cols="50"');
        $mform->addElement('static', 'plagiarism_agreement_desc', null, get_string('turnitintooltwoagreement_desc', 'plagiarism_turnitin'));

        // Student data privacy settings.
        $mform->addElement('header', 'plagiarism_privacy', get_string('studentdataprivacy', 'plagiarism_turnitin'));
        $mform->setExpanded('plagiarism_privacy');

        if ($DB->count_records('turnitintooltwo_users') > 0 AND isset($config->enablepseudo)) {
            $enablepseudooptions = ($config->enablepseudo == 1) ? array(1 => get_string('yes')) : array(0 => get_string('no'));
        } else if ($DB->count_records('turnitintooltwo_users') > 0) {
            $enablepseudooptions = array( 0 => get_string('no', 'turnitintooltwo'));
        } else {
            $enablepseudooptions = $ynoptions;
        }

        $mform->addElement('select', 'plagiarism_enablepseudo', get_string('enablepseudo', 'plagiarism_turnitin'), $enablepseudooptions);
        $mform->addElement('static', 'plagiarism_enablepseudo_desc', null, get_string('enablepseudo_desc', 'plagiarism_turnitin'));
        $mform->setDefault('plagiarism_enablepseudo', 0);

        if (isset($config->enablepseudo) AND $config->enablepseudo) {
            $mform->addElement('text', 'plagiarism_pseudofirstname', get_string('pseudofirstname', 'plagiarism_turnitin'));
            $mform->addElement('static', 'plagiarism_pseudofirstname_desc', null, get_string('pseudofirstname_desc', 'plagiarism_turnitin'));
            $mform->setType('plagiarism_pseudofirstname', PARAM_TEXT);
            $mform->setDefault('plagiarism_pseudofirstname', PLAGIARISM_TURNITIN_DEFAULT_PSEUDO_FIRSTNAME);

            $lnoptions = array( 0 => get_string('user') );

            $userprofiles = $DB->get_records('user_info_field');
            foreach ($userprofiles as $profile) {
                $lnoptions[$profile->id] = get_string('profilefield', 'admin').': '.$profile->name;
            }

            $mform->addElement('select', 'plagiarism_pseudolastname', get_string('pseudolastname', 'plagiarism_turnitin'), $lnoptions);
            $mform->addElement('static', 'plagiarism_pseudolastname_desc', null, get_string('pseudolastname_desc', 'plagiarism_turnitin'));
            $mform->setType('plagiarism_pseudolastname', PARAM_TEXT);
            $mform->setDefault('plagiarism_pseudolastname', 0);

            $mform->addElement('select', 'plagiarism_lastnamegen', get_string('pseudolastnamegen', 'plagiarism_turnitin'), $ynoptions);
            $mform->addElement('static', 'plagiarism_lastnamegen_desc', null, get_string('pseudolastnamegen_desc', 'plagiarism_turnitin'));
            $mform->setType('plagiarism_lastnamegen', PARAM_TEXT);
            $mform->setDefault('plagiarism_lastnamegen', 0);

            $mform->addElement('text', 'plagiarism_pseudosalt', get_string('pseudoemailsalt', 'plagiarism_turnitin'));
            $mform->addElement('static', 'plagiarism_pseudosalt_desc', null, get_string('pseudoemailsalt_desc', 'plagiarism_turnitin'));
            $mform->setType('plagiarism_pseudosalt', PARAM_TEXT);

            $mform->addElement('text', 'plagiarism_pseudoemaildomain', get_string('pseudoemaildomain', 'plagiarism_turnitin'));
            $mform->addElement('static', 'plagiarism_pseudoemaildomain_desc', null, get_string('pseudoemaildomain_desc', 'plagiarism_turnitin'));
            $mform->setType('plagiarism_pseudoemaildomain', PARAM_TEXT);
        }

        $this->add_action_buttons();
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

    /**
     * Save the plugin config data
     */
    public function save($data) {
        // Save whether the plugin is enabled for individual modules.
        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modpath) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $property = "turnitin_use_mod_" . $mod;
                ${ "turnitin_use_mod_" . "$mod" } = (!empty($data->$property)) ? $data->$property : 0;
                set_config('turnitin_use_mod_'.$mod, ${ "turnitin_use_mod_" . "$mod" }, 'plagiarism');
                if (${ "turnitin_use_mod_" . "$mod" }) {
                    set_config('turnitin_use', 1, 'plagiarism');
                }
            }
        }

        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_accountid");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_secretkey");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_apiurl");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_enablediagnostic");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_enableperformancelogs");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_usegrademark");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_enablepeermark");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_useerater");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_transmatch");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_repositoryoption");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_agreement");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_enablepseudo");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_pseudofirstname");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_pseudolastname");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_lastnamegen");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_pseudosalt");
        plagiarism_plugin_turnitin::plagiarism_set_config($data, "plagiarism_pseudoemaildomain");
    }
}
