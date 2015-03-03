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

// This file keeps track of upgrades to
// the plagiarism Turnitin module

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_plagiarism_turnitin_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2013081202) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field1 = new xmldb_field('transmatch', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, 0, 'legacyteacher');
        $field2 = new xmldb_field('lastmodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, 0, 'transmatch');
        $field3 = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'lastmodified');
        $field4 = new xmldb_field('submissiontype', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'grade');
        $field5 = new xmldb_field('similarityscore', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'statuscode');

        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }
        if (!$dbman->field_exists($table, $field5)) {
            $dbman->add_field($table, $field5);
        } else {
            $dbman->change_field_type($table, $field5);
            $dbman->change_field_default($table, $field5);
        }

        upgrade_plugin_savepoint(true, 2013081202, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2014012403) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field = new xmldb_field('orcapable', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'submissiontype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_default($table, $field);
        }
        upgrade_plugin_savepoint(true, 2014012403, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2014012405) {
        if ($turnitinsetting = $DB->get_record('config_plugins', array('name' => 'turnitin_use', 'plugin' => 'plagiarism'))) {
            if ($turnitinsetting->value == 1) {
                $supported_mods = array('assign', 'forum', 'workshop');
                foreach ($supported_mods as $mod) {
                    $configfield = new object();
                    $configfield->value = 1;
                    $configfield->plugin = 'plagiarism';
                    $configfield->name = 'turnitin_use_mod_'.$mod;
                    $DB->insert_record('config_plugins', $configfield);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2014012405, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2014012406) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field = new xmldb_field('errorcode', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'orcapable');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('errormsg', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'errorcode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2014012406, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2014012413) {
        // Add new indexes to tables
        $table = new xmldb_table('plagiarism_turnitin_files');
        $index = new xmldb_index('externalid', XMLDB_INDEX_NOTUNIQUE, array('externalid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Alter certain datatypes incase install was made before install.xml was updated.
        $field1 = new xmldb_field('transmatch', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, 0, 'legacyteacher');
        $field2 = new xmldb_field('lastmodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, 0, 'transmatch');
        $field3 = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'lastmodified');
        $field4 = new xmldb_field('submissiontype', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'grade');
        $field5 = new xmldb_field('similarityscore', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'statuscode');

        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        } else {
            $dbman->change_field_precision($table, $field1);
        }

        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        } else {
            $dbman->change_field_precision($table, $field2);
        }

        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        } else {
            $dbman->change_field_precision($table, $field3);
        }

        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        } else {
            $dbman->change_field_precision($table, $field4);
        }

        if (!$dbman->field_exists($table, $field5)) {
            $dbman->add_field($table, $field5);
        } else {
            $dbman->change_field_type($table, $field5);
            $dbman->change_field_default($table, $field5);
            $dbman->change_field_precision($table, $field5);
        }

        upgrade_plugin_savepoint(true, 2014012413, 'plagiarism', 'turnitin');
    }

    return $result;
}