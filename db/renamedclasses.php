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
 * Class renames for Moodle's autoloader cache.
 *
 * Maps old global class names to their new namespaced equivalents, so that
 * Moodle's component class map remains consistent after the classes/ refactor.
 *
 * @package   plagiarism_turnitin
 * @copyright Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
$renamedclasses = [
    'pp_receipt_message'              => 'plagiarism_turnitin\\digitalreceipt\\pp_receipt_message',
    'turnitin_activitysettingsform'   => 'plagiarism_turnitin\\turnitin_activitysettingsform',
    'turnitin_assign'                 => 'plagiarism_turnitin\\modules\\turnitin_assign',
    'turnitin_assignment'             => 'plagiarism_turnitin\\turnitin_assignment',
    'turnitin_class'                  => 'plagiarism_turnitin\\turnitin_class',
    'turnitin_comms'                  => 'plagiarism_turnitin\\turnitin_comms',
    'turnitin_coursework'             => 'plagiarism_turnitin\\modules\\turnitin_coursework',
    'turnitin_defaultsettingsform'    => 'plagiarism_turnitin\\turnitin_defaultsettingsform',
    'turnitin_form'                   => 'plagiarism_turnitin\\turnitin_form',
    'turnitin_forum'                  => 'plagiarism_turnitin\\modules\\turnitin_forum',
    'turnitin_logger'                 => 'plagiarism_turnitin\\turnitin_logger',
    'turnitin_quiz'                   => 'plagiarism_turnitin\\modules\\turnitin_quiz',
    'turnitin_setupform'              => 'plagiarism_turnitin\\turnitin_setupform',
    'turnitin_submission'             => 'plagiarism_turnitin\\turnitin_submission',
    'turnitin_user'                   => 'plagiarism_turnitin\\turnitin_user',
    'turnitin_view'                   => 'plagiarism_turnitin\\turnitin_view',
    'turnitin_workshop'               => 'plagiarism_turnitin\\modules\\turnitin_workshop',
];
