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
 * Extra helper methods for plagiarism_turnitin component
 *
 * @package   plagiarism_turnitin
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Override the repository option if necessary depending on the configuration setting.
 * @param $submitpapersto int - The repository to submit to.
 * @return $submitpapersto int - The repository to submit to.
 */
function plagiarism_turnitin_override_repository($submitpapersto) {
    $config = turnitintooltwo_admin_config();

    switch ($config->repositoryoption) {
        case PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_STANDARD; // Force Standard Repository.
            $submitpapersto = PLAGIARISM_TURNITIN_SUBMIT_TO_STANDARD_REPOSITORY;
            break;
        case PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_NO; // Force No Repository.
            $submitpapersto = PLAGIARISM_TURNITIN_SUBMIT_TO_NO_REPOSITORY;
            break;
        case PLAGIARISM_TURNITIN_ADMIN_REPOSITORY_OPTION_FORCE_INSTITUTIONAL; // Force Individual Repository.
            $submitpapersto = PLAGIARISM_TURNITIN_SUBMIT_TO_INSTITUTIONAL_REPOSITORY;
            break;
    }

    return $submitpapersto;
}