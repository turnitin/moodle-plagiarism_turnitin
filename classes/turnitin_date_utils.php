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
 * Date calculation utilities for Turnitin assignment synchronisation.
 *
 * @package   plagiarism_turnitin
 * @copyright 2025 Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

/**
 * Calculates the start, post, and due dates sent to Turnitin when syncing an assignment.
 *
 * Extracted from plagiarism_plugin_turnitin::sync_tii_assignment() so each date
 * rule can be unit-tested independently.
 *
 * @package plagiarism_turnitin
 */
class turnitin_date_utils {
    /**
     * Resolve the assignment start date to send to Turnitin.
     *
     * Preference order: allowsubmissionsfromdate → timeavailable → cm->added.
     * The result is then clamped so it is no older than 11 months ago (Turnitin
     * rejects start dates more than 1 year in the past).
     *
     * @param \stdClass $moduledata  Module record (assign/forum/etc.).
     * @param \stdClass $cm          Course module record (needs ->added).
     * @return int Unix timestamp.
     */
    public static function start_date(\stdClass $moduledata, \stdClass $cm): int {
        if (!empty($moduledata->allowsubmissionsfromdate)) {
            $dtstart = $moduledata->allowsubmissionsfromdate;
        } else if (!empty($moduledata->timeavailable)) {
            $dtstart = $moduledata->timeavailable;
        } else {
            $dtstart = $cm->added;
        }

        // Clamp dates older than 1 year to 11 months ago so Turnitin accepts them.
        if ($dtstart <= strtotime('-1 year')) {
            $dtstart = strtotime('-11 months');
        }

        return (int)$dtstart;
    }

    /**
     * Resolve the feedback release (post) date to send to Turnitin.
     *
     * Rules applied in order:
     * - Forum modules: post date stays 0 (Turnitin default).
     * - Grade item hidden=1: post date is 6 months from now.
     * - Grade item hidden=0 with assign markingworkflow: released → -5 min, else +6 months.
     * - Grade item hidden=0 (no workflow): post date equals start date.
     * - Grade item hidden=<timestamp>: post date equals that timestamp.
     * - Blind marking on assign (identities unrevealed): post date is 6 months from now.
     * - Blind marking on coursework: post date is 6 months from now.
     * - Post date is always at least 1 second after start date.
     *
     * @param \stdClass      $cm          Course module record (needs ->modname, ->instance, ->course, ->id).
     * @param \stdClass      $moduledata  Module record (needs ->blindmarking, ->revealidentities,
     *                                   ->markingworkflow as applicable).
     * @param int            $dtstart     Start date timestamp (from start_date()).
     * @param \stdClass|null $gradeitem   Row from grade_items for this CM, or null when none exists.
     * @param bool           $gradesreleased Whether any assign_user_flags rows have workflowstate='released'.
     * @return int Unix timestamp.
     */
    public static function post_date(
        \stdClass $cm,
        \stdClass $moduledata,
        int $dtstart,
        ?\stdClass $gradeitem,
        bool $gradesreleased = false
    ): int {
        $dtpost = 0;

        if ($cm->modname !== 'forum' && $gradeitem !== null) {
            switch ($gradeitem->hidden) {
                case 1:
                    $dtpost = strtotime('+6 months');
                    break;
                case 0:
                    $dtpost = $dtstart;
                    // If any grades have been released early via marking workflow, set post date to have passed.
                    if ($cm->modname === 'assign' && !empty($moduledata->markingworkflow)) {
                        $dtpost = $gradesreleased ? strtotime('-5 minutes') : strtotime('+6 month');
                    }
                    break;
                default:
                    $dtpost = $gradeitem->hidden;
                    break;
            }
        }

        // Push out post date when blind marking is active and identities are not yet revealed.
        if ($cm->modname === 'assign' && !empty($moduledata->blindmarking) && empty($moduledata->revealidentities)) {
            $dtpost = strtotime('+6 months');
        }

        if ($cm->modname === 'coursework' && !empty($moduledata->blindmarking)) {
            $dtpost = strtotime('+6 months');
        }

        // Ensure post date is at least 1 second after the start date.
        $dtstartplus1sec = new \DateTime("@$dtstart");
        $dtstartplus1sec->add(new \DateInterval('PT1S'));
        if ($dtpost < $dtstartplus1sec->getTimestamp()) {
            $dtpost = $dtstartplus1sec->getTimestamp();
        }

        return (int)$dtpost;
    }

    /**
     * Resolve the assignment due date to send to Turnitin.
     *
     * Rules applied in order:
     * - Use moduledata->duedate when set, otherwise 0.
     * - Cap at 1 year from now (Turnitin limitation).
     * - Must be after start date; if not, set to start date +1 month.
     * - When $submittoturnitin is true and the date is in the past, set to tomorrow.
     *
     * @param \stdClass $moduledata       Module record (needs ->duedate).
     * @param int       $dtstart          Start date timestamp (from start_date()).
     * @param bool      $submittoturnitin Whether a submission is being sent to Turnitin right now.
     * @return int Unix timestamp.
     */
    public static function due_date(\stdClass $moduledata, int $dtstart, bool $submittoturnitin = false): int {
        $dtdue = (!empty($moduledata->duedate)) ? (int)$moduledata->duedate : 0;

        // Cap due dates more than 1 year ahead.
        if ($dtdue > strtotime('+1 year')) {
            $dtdue = strtotime('+1 year');
        }

        // Due date must be after start date.
        if ($dtdue <= $dtstart) {
            $dtdue = strtotime('+1 month', $dtstart);
        }

        // When submitting, ensure the due date is always in the future.
        if ($dtdue <= time() && $submittoturnitin) {
            $dtdue = strtotime('+1 day');
        }

        return (int)$dtdue;
    }
}
