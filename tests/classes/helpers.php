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
 * Test helpers for the turnitin_assignment test suite.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Suppress mtrace() output during tests by acting as a no-op wrapper.
 * Registered via $CFG->mtrace_wrapper in setUp() — must be in the global
 * namespace so Moodle can find it with function_exists().
 *
 * @param string $string The output string (ignored).
 * @param string $eol    The end-of-line character (ignored).
 * @return bool
 */
function plagiarism_turnitin_mtrace(string $string, string $eol): bool {
    return true;
}
