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

defined('MOODLE_INTERNAL') || die();


class backup_plagiarism_turnitin_plugin extends backup_plagiarism_plugin {

    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();

        $plugin_element = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($plugin_element);

        // Add module config elements
        $turnitin_configs = new backup_nested_element('turnitin_configs');
        $turnitin_config = new backup_nested_element('turnitin_config', array('id'), array('name', 'value'));
        $plugin_element->add_child($turnitin_configs);
        $turnitin_configs->add_child($turnitin_config);
        $turnitin_config->set_source_table('plagiarism_turnitin_config', array('cm' => backup::VAR_PARENTID));

        // Add file elements if required
        if ($this->get_setting_value('userinfo')) {
            $turnitin_files = new backup_nested_element('turnitin_files');
            $turnitin_file = new backup_nested_element('turnitin_file', array('id'),
                                array('userid', 'identifier', 'externalid', 'externalstatus', 'statuscode', 'similarityscore', 'transmatch', 'lastmodified', 'grade', 'submissiontype'));
            $plugin_element->add_child($turnitin_files);
            $turnitin_files->add_child($turnitin_file);

            $turnitin_file->set_source_table('plagiarism_turnitin_files', array('cm' => backup::VAR_PARENTID));
        }
        return $plugin;
    }

    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();

        $plugin_element = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($plugin_element);

        // Add courses from Turnitintool table
        $turnitin_courses = new backup_nested_element('turnitin_courses');
        $turnitin_course = new backup_nested_element('turnitin_course', array('id'), array('courseid', 'ownerid', 'turnitin_ctl', 'turnitin_cid', 'course_type'));
        $plugin_element->add_child($turnitin_courses);
        $turnitin_courses->add_child($turnitin_course);

        $turnitin_course->set_source_table('turnitintooltwo_courses', array('courseid'=> backup::VAR_COURSEID, 'course_type' => backup_helper::is_sqlparam('PP')));
        return $plugin;
    }
}