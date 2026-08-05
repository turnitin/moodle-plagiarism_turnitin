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
 * Unit tests for turnitin_submission methods extracted from event_handler.
 *
 * @package    plagiarism_turnitin
 * @copyright  Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.PHPUnit.TestCaseCovers

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_submission methods extracted from event_handler.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_submission::class)]
final class turnitin_submission_event_test extends \advanced_testcase {
    // Calculate_content_identifier tests.

    /**
     * Test that forum posts produce an identifier that encodes the author, cm
     * and content, making it unique per user and activity.
     */
    public function test_calculate_content_identifier_forum_includes_author_and_cm(): void {
        $cm      = (object)['id' => 5, 'modname' => 'forum'];
        $content = 'My forum post text';
        $author  = 42;

        $result   = turnitin_submission::calculate_content_identifier($cm, $author, $content, 0);
        $expected = sha1('forum_post user' . $author . ' cm' . $cm->id . ' ' . $content);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that assign text_content submissions encode the cm id and itemid so
     * different attempts produce different identifiers.
     */
    public function test_calculate_content_identifier_assign_includes_cm_and_itemid(): void {
        $cm      = (object)['id' => 7, 'modname' => 'assign'];
        $content = 'My essay text';
        $author  = 10;
        $itemid  = 99;

        $result   = turnitin_submission::calculate_content_identifier($cm, $author, $content, $itemid);
        $expected = sha1('text_content cm' . $cm->id . ' itemid' . $itemid . ' ' . $content);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that non-forum, non-assign modules (e.g. workshop) produce a simple
     * SHA1 of the content only.
     */
    public function test_calculate_content_identifier_other_modules_use_content_only(): void {
        $cm      = (object)['id' => 3, 'modname' => 'workshop'];
        $content = 'My workshop submission';

        $result   = turnitin_submission::calculate_content_identifier($cm, 1, $content, 0);
        $expected = sha1($content);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that two different authors produce different forum identifiers, confirming
     * the author is part of the hash input.
     */
    public function test_calculate_content_identifier_forum_differs_by_author(): void {
        $cm      = (object)['id' => 5, 'modname' => 'forum'];
        $content = 'Same content';

        $id1 = turnitin_submission::calculate_content_identifier($cm, 1, $content, 0);
        $id2 = turnitin_submission::calculate_content_identifier($cm, 2, $content, 0);

        $this->assertNotEquals($id1, $id2);
    }

    /**
     * Test that two different assign itemids produce different identifiers.
     */
    public function test_calculate_content_identifier_assign_differs_by_itemid(): void {
        $cm      = (object)['id' => 7, 'modname' => 'assign'];
        $content = 'Same essay';

        $id1 = turnitin_submission::calculate_content_identifier($cm, 1, $content, 10);
        $id2 = turnitin_submission::calculate_content_identifier($cm, 1, $content, 20);

        $this->assertNotEquals($id1, $id2);
    }

    // Remove_queued_for_submission tests.

    /**
     * Test that remove_queued_for_submission deletes only the queued record
     * matching the given cm, userid and itemid.
     */
    public function test_remove_queued_deletes_matching_queued_record(): void {
        global $DB;
        $this->resetAfterTest();

        $idqueued = $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => 10, 'userid' => 1, 'itemid' => 5, 'identifier' => 'h1',
            'statuscode' => 'queued', 'attempt' => 0, 'submissiontype' => 'file',
            'submitter' => 1, 'lastmodified' => time(), 'transmatch' => 0,
        ]);

        turnitin_submission::remove_queued_for_submission(10, 1, 5);

        $this->assertFalse($DB->record_exists('plagiarism_turnitin_files', ['id' => $idqueued]));
    }

    /**
     * Test that remove_queued_for_submission does not delete records with a
     * different statuscode (e.g. 'success') — only queued ones should go.
     */
    public function test_remove_queued_preserves_non_queued_records(): void {
        global $DB;
        $this->resetAfterTest();

        $idsuccess = $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm' => 10, 'userid' => 1, 'itemid' => 5, 'identifier' => 'h2',
            'statuscode' => 'success', 'attempt' => 1, 'submissiontype' => 'file',
            'submitter' => 1, 'lastmodified' => time(), 'transmatch' => 0,
        ]);

        turnitin_submission::remove_queued_for_submission(10, 1, 5);

        $this->assertTrue($DB->record_exists('plagiarism_turnitin_files', ['id' => $idsuccess]));
    }
}
