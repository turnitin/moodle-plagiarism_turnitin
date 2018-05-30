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
 * @package   turnitintooltwo
 * @copyright 2012 iParadigms LLC
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_plagiarism_turnitin_upgrade($oldversion) {
    global $DB, $CFG;

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

        upgrade_dm_successful_uploads();

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
                $supportedmods = array('assign', 'forum', 'workshop');
                foreach ($supportedmods as $mod) {
                    $configfield = new stdClass();
                    $configfield->value = 1;
                    $configfield->plugin = 'plagiarism';
                    $configfield->name = 'turnitin_use_mod_'.$mod;
                    if (!$DB->get_record('config_plugins', array('name' => 'turnitin_use_mod_'.$mod))) {
                        $DB->insert_record('config_plugins', $configfield);
                    }
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
        // Add new indexes to tables.
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

    if ($oldversion < 2015040107) {
        upgrade_dm_successful_uploads();
    }

    if ($oldversion < 2015040107) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field = new xmldb_field('submitter', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2015040107, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2015040110) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field = new xmldb_field('student_read', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'errormsg');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2015040110, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2016011101) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field = new xmldb_field('gm_feedback', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, true, false, 0, 'student_read');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2016011104) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        $field = new xmldb_field('duedate_report_refresh', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, true, false, 0, 'gm_feedback');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2016011105) {
        $table = new xmldb_table('plagiarism_turnitin_config');

        // Drop cm key so that we can modify the field to allow null values.
        $key = new xmldb_key('cm', XMLDB_KEY_FOREIGN, array('cm'), 'course_modules', array('id'));
        $dbman->drop_key($table, $key);

        // Alter cm to allow null values.
        $field = new xmldb_field('cm', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, null, 'id');
        $dbman->change_field_notnull($table, $field);

        // Update 0 to null for defaults.
        $DB->execute("UPDATE ".$CFG->prefix."plagiarism_turnitin_config SET cm = NULL WHERE cm = 0");

        // Re-add foreign key for cm field.
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2016011105, 'plagiarism', 'turnitin');
    }

    if ($oldversion < 2016091402) {
        $table = new xmldb_table('plagiarism_turnitin_files');
        // Remove fields no longer needed.
        $field1 = new xmldb_field('externalstatus');
        $field2 = new xmldb_field('apimd5');
        $field3 = new xmldb_field('legacyteacher');
        if ($dbman->field_exists($table, $field1)) {
            $dbman->drop_field($table, $field1);
        }
        if ($dbman->field_exists($table, $field2)) {
            $dbman->drop_field($table, $field2);
        }
        if ($dbman->field_exists($table, $field3)) {
            $dbman->drop_field($table, $field3);
        }

        // Add new itemid field.
        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'externalid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2017013101) {
        $table = new xmldb_table('plagiarism_turnitin_files');

        // Due to an inconsistency with install and upgrade scripts, some users will
        // have submitter and student_read defaulting to 0 and not allowing null.
        // Alter submitter to allow null values.
        $field = new xmldb_field('submitter', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'userid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        } else {
            $dbman->add_field($table, $field);
        }

        // Alter student_read to allow null values.
        $field = new xmldb_field('student_read', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'errormsg');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        } else {
            $dbman->add_field($table, $field);
        }

        // Update fields to NULL as per default if necessary.
        $DB->set_field('plagiarism_turnitin_files', 'submitter', null, array('submitter' => 0));
        $DB->set_field('plagiarism_turnitin_files', 'student_read', null, array('student_read' => 0));

        // Remove old PP event from the database if it exists.
        $DB->delete_records('task_scheduled', array('component' => 'plagiarism_turnitin', 'classname' => '\plagiarism_turnitin\task\plagiarism_turnitin_task'));
    }

    if ($oldversion < 2017012601) {
        // Add new column that has to be unique.
        $table = new xmldb_table('plagiarism_turnitin_config');
        $field = new xmldb_field('config_hash', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'value');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Retrospectively update the new column to be id for previous configs.
        $DB->execute("UPDATE ".$CFG->prefix."plagiarism_turnitin_config SET config_hash = id WHERE config_hash IS NULL");

        // Add hash as key after update.
        $key = new xmldb_key('config_hash', XMLDB_KEY_UNIQUE, array('config_hash'));
        $dbman->add_key($table, $key);
    }

    if ($oldversion < 2018041307) {
        // If V2 is installed, copy the settings across to PP.
        if ($DB->get_record('config_plugins', array('plugin' => 'mod_turnitintool'))) {
            // Get the settings for the V2 plugin.
            $data = turnitintooltwo_admin_config();

            plagiarism_plugin_turnitin::plagiarism_set_config($data, "accountid");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "secretkey");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "apiurl");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "enablediagnostic");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "enableperformancelogs");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "usegrademark");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "enablepeermark");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "useerater");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "transmatch");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "repositoryoption");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "agreement");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "enablepseudo");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "pseudofirstname");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "pseudolastname");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "lastnamegen");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "pseudosalt");
            plagiarism_plugin_turnitin::plagiarism_set_config($data, "pseudoemaildomain");
        }
    }

    return $result;
}

function upgrade_dm_successful_uploads() {
    global $DB, $CFG;

    // Update successful submissions from Dan Marsden's plugin with incorrect statuscode.
    $DB->execute("UPDATE ".$CFG->prefix."plagiarism_turnitin_files SET statuscode = 'success',
        lastmodified = ".time()." WHERE statuscode = '51'");

    // Update the lastmodified timestamp from all successful submissions from Dan Marsden's plugin.
    $DB->execute("UPDATE ".$CFG->prefix."plagiarism_turnitin_files SET lastmodified = ".time()."
        WHERE statuscode = 'success' AND lastmodified = 0");

    // Update error codes with submissions from Dan Marsden's plugin.
    $DB->execute("UPDATE ".$CFG->prefix."plagiarism_turnitin_files SET statuscode = 'error'
        WHERE statuscode != 'success' AND statuscode != 'pending'");
}
