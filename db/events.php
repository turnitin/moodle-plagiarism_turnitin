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

/*
 * Events that don't exist anymore:
 *  - files_done
 *  - content_uploaded
 */

$handlers = array();

$observers = array(
    array(
        'eventname'   => '\core\event\course_reset_ended',
        'callback'    => 'plagiarism_plugin_turnitin::course_reset',
        'includefile' => '/plagiarism/turnitin/lib.php'
    ),
    array(
        'eventname'   => '\core\event\assessable_uploaded',
        'callback'    => 'plagiarism_turnitin_event_file_uploaded',
        'includefile' => '/plagiarism/turnitin/lib.php'
    ),
    array(
        'eventname'   => '\core\event\assessable_submitted',
        'callback'    => 'plagiarism_turnitin_event_assessable_submitted',
        'includefile' => '/plagiarism/turnitin/lib.php'
    ),
    array(
        'eventname' => '\core\event\course_module_created',
        'callback'    => 'plagiarism_turnitin_event_mod_created',
        'includefile' => '/plagiarism/turnitin/lib.php'
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback'    => 'plagiarism_turnitin_event_mod_updated',
        'includefile' => '/plagiarism/turnitin/lib.php'
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback'    => 'plagiarism_turnitin_event_mod_deleted',
        'includefile' => '/plagiarism/turnitin/lib.php'
    ),
);