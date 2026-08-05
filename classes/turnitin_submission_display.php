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

namespace plagiarism_turnitin;

/**
 * Renders the Turnitin submission status links shown alongside each submission.
 *
 * All methods are static and receive a submission_link_context value object, keeping
 * rendering logic completely separate from data assembly. This makes each branch
 * independently testable without a live Moodle page or Turnitin API connection.
 *
 * Usage (from get_links() after data assembly):
 *   $ctx = new submission_link_context();
 *   // ... populate $ctx properties ...
 *   $html = turnitin_submission_display::render($ctx);
 *
 * @package   plagiarism_turnitin
 * @copyright Turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turnitin_submission_display {
    /**
     * Render the full submission status block for one submission row.
     *
     * Dispatches to the appropriate private render method based on the submission's
     * statuscode. Returns an empty string when the viewer does not have access.
     *
     * @param submission_link_context $ctx All data needed to render the status block.
     * @return string HTML fragment.
     */
    public static function render(submission_link_context $ctx): string {
        global $OUTPUT;

        // Only tutors and group members who own the submission can see the links.
        if (!$ctx->istutor && !in_array($ctx->vieweruserid, $ctx->submissionusers)) {
            return '';
        }

        if (!$ctx->plagiarismfile) {
            return self::render_no_submission($ctx);
        }

        $statuscode = $ctx->plagiarismfile->statuscode;

        if ($statuscode === 'success' || ($statuscode === 'error' && $ctx->plagiarismfile->errorcode == 13)) {
            return self::render_success($ctx);
        }

        if ($statuscode === 'error') {
            return self::render_error($ctx);
        }

        if ($statuscode === 'deleted') {
            return self::render_deleted($ctx);
        }

        if ($statuscode === 'queued') {
            return self::render_queued();
        }

        // Default: pending.
        return self::render_pending();
    }

    /**
     * Render the success state: Turnitin ID, similarity score, GradeMark, student-read
     * indicator, rubric link and Peermark links.
     *
     * @param submission_link_context $ctx
     * @return string HTML fragment.
     */
    public static function render_success(submission_link_context $ctx): string {
        global $OUTPUT, $CFG;

        $output = '';

        if ($ctx->istutor || $ctx->submissionuserid == $ctx->vieweruserid) {
            $output .= \html_writer::tag(
                'div',
                $OUTPUT->pix_icon(
                    'turnitin-icon',
                    get_string('turnitinid', 'plagiarism_turnitin') . ': ' . $ctx->plagiarismfile->externalid,
                    'plagiarism_turnitin',
                    ['class' => 'icon_size']
                ) . get_string('turnitinid', 'plagiarism_turnitin') . ': ' . $ctx->plagiarismfile->externalid,
                ['class' => 'turnitin_status']
            );
        }

        // Similarity score and Originality Report link.
        $canseeor = $ctx->istutor || (
            in_array($ctx->vieweruserid, $ctx->submissionusers) && $ctx->showstudentreport
        );
        $hasorscore = (is_null($ctx->plagiarismfile->orcapable) || $ctx->plagiarismfile->orcapable == 1)
            && !is_null($ctx->plagiarismfile->similarityscore);

        if ($canseeor && $hasorscore) {
            $output .= self::render_similarity_score($ctx);
        }

        // Not-OR-capable indicator.
        if ($ctx->plagiarismfile->orcapable == 0 && !is_null($ctx->plagiarismfile->orcapable)) {
            $notorlink = \html_writer::tag('div', 'x', [
                'title' => get_string('notorcapable', 'plagiarism_turnitin'),
                'class' => 'tii_tooltip score_colour score_colour_ score_no_orcapable',
            ]);
            $useropenclass = ($ctx->vieweruserid == $ctx->submissionuserid || $ctx->istutor)
                ? 'pp_origreport_open' : '';
            $output .= \html_writer::tag('div', $notorlink, ['class' => 'row_score pp_origreport ' . $useropenclass]);
        }

        // GradeMark link.
        $blindon    = $ctx->blindon;
        $gradeexists = $ctx->gradeexists;
        $released   = (!$blindon) && ($ctx->gradesreleased && (!empty($ctx->plagiarismfile->gm_feedback) || $gradeexists));
        $canseegm   = $ctx->usegrademark
            && ($ctx->istutor || ($ctx->submissionuserid == $ctx->vieweruserid && $released))
            && !empty($ctx->gradeitem);

        if ($canseegm) {
            $gmicon = \html_writer::tag(
                'div',
                $OUTPUT->pix_icon('icon-edit', get_string('grademark', 'plagiarism_turnitin'), 'plagiarism_turnitin'),
                [
                    'title'    => get_string('grademark', 'plagiarism_turnitin'),
                    'class'    => 'pp_grademark_open tii_tooltip grademark_'
                        . $ctx->plagiarismfile->externalid . '_' . $ctx->cmid,
                    'tabindex' => '0',
                    'role'     => 'link',
                ]
            );
            $gmicon  .= \html_writer::tag('div', '', ['class' => 'launch_form grademark_form_' . $ctx->plagiarismfile->externalid]);
            $output  .= \html_writer::tag('div', $gmicon, ['class' => 'grade_icon']);
        }

        // Student-read indicator (tutors only).
        if ($ctx->istutor) {
            $studentread = (!empty($ctx->plagiarismfile->student_read)) ? $ctx->plagiarismfile->student_read : 0;
            if ($studentread > 0) {
                $readicon = $OUTPUT->pix_icon(
                    'icon-student-read',
                    get_string('student_read', 'plagiarism_turnitin') . ' ' . userdate($studentread),
                    'plagiarism_turnitin'
                );
            } else {
                $readicon = $OUTPUT->pix_icon(
                    'icon-dot',
                    get_string('student_notread', 'plagiarism_turnitin'),
                    'plagiarism_turnitin'
                );
            }
            $output .= \html_writer::tag('div', $readicon, ['class' => 'student_read_icon']);
        }

        // Peermark reviews link (uses pre-fetched session data, no API call here).
        if ($ctx->enablepeermark && !empty($ctx->peermarkassignments)) {
            $peermarksactive = false;
            foreach ($ctx->peermarkassignments as $pm) {
                if (time() > $pm->dtstart) {
                    $peermarksactive = true;
                    break;
                }
            }

            if (($ctx->istutor && count($ctx->peermarkassignments) > 0) || (!$ctx->istutor && $peermarksactive)) {
                $peermarkreviewslink = \html_writer::tag('span', '', [
                    'title' => get_string('launchpeermarkreviews', 'plagiarism_turnitin'),
                    'class' => 'peermark_reviews_pp_launch tii_tooltip',
                    'id'    => 'peermark_reviews_form',
                ]);
                $output .= \html_writer::tag('div', $peermarkreviewslink, ['class' => 'row_peermark_reviews']);
            }
        }

        return $output;
    }

    /**
     * Render the error state: error icon and appropriate message or resubmit link.
     *
     * @param submission_link_context $ctx
     * @return string HTML fragment.
     */
    public static function render_error(submission_link_context $ctx): string {
        global $OUTPUT;

        $errorcode = (isset($ctx->plagiarismfile->errorcode)) ? $ctx->plagiarismfile->errorcode : 0;

        // Upgrade legacy file-too-large records that predate errorcode 2.
        if ($errorcode == 0 && $ctx->submissiontype == 'file') {
            if ($ctx->filesize > PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE) {
                $errorcode = 2;
            }
        }

        if ($errorcode == 0) {
            $langstring  = $ctx->istutor ? 'ppsubmissionerrorseelogs' : 'ppsubmissionerrorstudent';
            $errorstring = empty($ctx->plagiarismfile->errormsg)
                ? get_string($langstring, 'plagiarism_turnitin')
                : $ctx->plagiarismfile->errormsg;
        } else {
            $errorstring = get_string(
                'errorcode' . $errorcode,
                'plagiarism_turnitin',
                [
                    'maxfilesize' => display_size(PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE),
                    'externalid'  => $ctx->plagiarismfile->externalid,
                ]
            );
        }

        $erroricon = \html_writer::tag(
            'div',
            $OUTPUT->pix_icon('x-red', $errorstring, 'plagiarism_turnitin'),
            ['title' => $errorstring, 'class' => 'tii_tooltip tii_error_icon']
        );

        $output = '';

        if (!$ctx->istutor) {
            $output .= \html_writer::tag('div', $erroricon . ' ' . $errorstring, ['class' => 'warning clear']);
        } else if ($errorcode == 3) {
            $output .= \html_writer::tag('div', $erroricon, ['class' => 'clear']);
        } else {
            // Resubmit link for tutors.
            $output .= \html_writer::tag(
                'div',
                $erroricon . ' ' . get_string('resubmittoturnitin', 'plagiarism_turnitin'),
                ['class' => 'clear plagiarism_turnitin_resubmit_link', 'id' => 'pp_resubmit_' . $ctx->plagiarismfile->id]
            );
            $output .= \html_writer::tag(
                'div',
                $OUTPUT->pix_icon('loading', $errorstring, 'plagiarism_turnitin')
                    . ' ' . get_string('resubmitting', 'plagiarism_turnitin'),
                ['class' => 'pp_resubmitting hidden']
            );
            $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin') . ': '
                . get_string('pending', 'plagiarism_turnitin');
            $output .= \html_writer::tag(
                'div',
                $OUTPUT->pix_icon('turnitin-icon', $statusstr, 'plagiarism_turnitin', ['class' => 'icon_size']) . $statusstr,
                ['class' => 'turnitin_status hidden']
            );

            // Hidden forum post content for resubmission.
            if ($ctx->submissiontype === 'forum_post' && !empty($ctx->submissioncontent)) {
                $output .= \html_writer::tag(
                    'div',
                    chunk_split(base64_encode($ctx->submissioncontent), 64),
                    ['class' => 'hidden', 'id' => 'content_' . $ctx->plagiarismfile->id]
                );
            }
        }

        return $output;
    }

    /**
     * Render the deleted state: status icon with deletion reason.
     *
     * @param submission_link_context $ctx
     * @return string HTML fragment.
     */
    public static function render_deleted(submission_link_context $ctx): string {
        global $OUTPUT;

        $errorcode = (isset($ctx->plagiarismfile->errorcode)) ? $ctx->plagiarismfile->errorcode : 0;
        if ($errorcode == 0) {
            $langstring  = $ctx->istutor ? 'ppsubmissionerrorseelogs' : 'ppsubmissionerrorstudent';
            $errorstring = empty($ctx->plagiarismfile->errormsg)
                ? get_string($langstring, 'plagiarism_turnitin')
                : $ctx->plagiarismfile->errormsg;
        } else {
            $errorstring = get_string(
                'errorcode' . $errorcode,
                'plagiarism_turnitin',
                display_size(PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE)
            );
        }

        $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin') . ': '
            . get_string('deleted', 'plagiarism_turnitin') . '<br />';
        $statusstr .= get_string('because', 'plagiarism_turnitin') . '<br />"' . $errorstring . '"';

        return \html_writer::tag(
            'div',
            $OUTPUT->pix_icon('turnitin-icon', $statusstr, 'plagiarism_turnitin', ['class' => 'icon_size']) . $statusstr,
            ['class' => 'turnitin_status']
        );
    }

    /**
     * Render the queued state: status icon with "queued" label.
     *
     * @return string HTML fragment.
     */
    public static function render_queued(): string {
        global $OUTPUT;

        $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin') . ': '
            . get_string('queued', 'plagiarism_turnitin');

        return \html_writer::tag(
            'div',
            $OUTPUT->pix_icon('turnitin-icon', $statusstr, 'plagiarism_turnitin', ['class' => 'icon_size']) . $statusstr,
            ['class' => 'turnitin_status']
        );
    }

    /**
     * Render the pending state: status icon with "pending" label.
     *
     * @return string HTML fragment.
     */
    public static function render_pending(): string {
        global $OUTPUT;

        $statusstr = get_string('turnitinstatus', 'plagiarism_turnitin') . ': '
            . get_string('pending', 'plagiarism_turnitin');

        return \html_writer::tag(
            'div',
            $OUTPUT->pix_icon('turnitin-icon', $statusstr, 'plagiarism_turnitin', ['class' => 'icon_size']) . $statusstr,
            ['class' => 'turnitin_status']
        );
    }

    /**
     * Render the case where no submission row exists yet — shows a EULA-not-accepted
     * error icon when a tutor is viewing a student who hasn't accepted the EULA.
     *
     * @param submission_link_context $ctx
     * @return string HTML fragment.
     */
    public static function render_no_submission(submission_link_context $ctx): string {
        global $OUTPUT;

        if (
            $ctx->istutor
            && $ctx->vieweruserid != $ctx->submissionuserid
            && !$ctx->submittereulaccepted
        ) {
            $erroricon = \html_writer::tag(
                'div',
                $OUTPUT->pix_icon('doc-x-grey', get_string('errorcode3', 'plagiarism_turnitin'), 'plagiarism_turnitin'),
                ['title' => get_string('errorcode3', 'plagiarism_turnitin'), 'class' => 'tii_tooltip tii_error_icon']
            );
            return \html_writer::tag('div', $erroricon, ['class' => 'clear']);
        }

        return '';
    }

    /**
     * Render the similarity score badge and Originality Report launch div.
     *
     * Extracted from render_success() for clarity — this is the most conditional
     * piece of the success branch.
     *
     * @param submission_link_context $ctx
     * @return string HTML fragment.
     */
    private static function render_similarity_score(submission_link_context $ctx): string {
        global $OUTPUT, $CFG;

        if ($ctx->plagiarismfile->statuscode === 'pending') {
            $orscorehtml = \html_writer::tag('div', '&nbsp;', [
                'title' => get_string('pending', 'plagiarism_turnitin'),
                'class' => 'tii_tooltip origreport_score score_colour score_colour_',
            ]);
        } else {
            $transmatch = ($ctx->plagiarismfile->transmatch == 1) ? ' EN' : '';

            if (is_null($ctx->plagiarismfile->similarityscore)) {
                $score      = '&nbsp;';
                $titlescore = get_string('pending', 'plagiarism_turnitin');
                $class      = 'score_colour_';
            } else {
                $score      = $ctx->plagiarismfile->similarityscore . '%';
                $titlescore = $ctx->plagiarismfile->similarityscore . '% '
                    . get_string('similarity', 'plagiarism_turnitin');
                $roundup    = fn($n, $x = 25) => (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
                $class      = 'score_colour_' . $roundup($ctx->plagiarismfile->similarityscore);
            }

            $orscorehtml = \html_writer::tag(
                'div',
                $score . $transmatch,
                ['title' => $titlescore, 'class' => 'tii_tooltip origreport_score score_colour ' . $class]
            );
        }

        $orscorehtml .= \html_writer::tag('div', '', [
            'class' => 'launch_form origreport_form_' . $ctx->plagiarismfile->externalid,
        ]);

        if ($ctx->cmmodname === 'forum') {
            $orscorehtml .= \html_writer::tag(
                'div',
                $CFG->wwwroot . '/plagiarism/turnitin/extras.php?cmid=' . $ctx->cmid,
                ['class' => 'origreport_forum_launch origreport_forum_launch_' . $ctx->plagiarismfile->externalid]
            );
        }

        $useropenclass = ($ctx->vieweruserid == $ctx->submissionuserid || $ctx->istutor)
            ? 'pp_origreport_open' : '';

        if ($ctx->isnonsubmitterforgroupassign) {
            return '';
        }

        $ordivclass = 'row_score pp_origreport ' . $useropenclass
            . ' origreport_' . $ctx->plagiarismfile->externalid . '_' . $ctx->cmid;

        return \html_writer::tag('div', $orscorehtml, ['class' => $ordivclass, 'tabindex' => '0', 'role' => 'link']);
    }
}
