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
 * Unit tests for (some of) plagiarism/turnitin/classes/modules/turnitin_forum.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2017 Turnitin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;
use plagiarism_turnitin\modules\turnitin_forum;

/**
 * Tests for API comms class
 *
 * @package turnitin
 */
#[CoversClass(turnitin_forum::class)]
final class turnitin_forum_test extends \advanced_testcase {
    /** @var stdClass created in setUp. */
    protected $forum;

    /** @var stdClass created in setUp. */
    protected $discussion;

    /** @var stdClass created in setUp. */
    protected $post;

    /**
     * Create a course and forum module instance
     */
    public function setUp(): void {
        parent::setUp();

        // Create a course, user and a forum.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = new \stdClass();
        $record->course = $course->id;
        $this->forum = $this->getDataGenerator()->create_module('forum', $record);

        // Add discussion to course.
        $record = new \stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->forum = $this->forum->id;
        $this->discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to discussion.
        $record = new \stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->forum = $this->forum->id;
        $record->discussion = $this->discussion->id;
        $this->post = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);
    }

    /**
     * Moodle stores forum post content as HTML, so special characters are encoded as entities
     * (e.g. "&" becomes "&amp;"). Verify that html_to_text() — used in the forum submission
     * pipeline — decodes these entities, preventing literal "&amp;" from appearing in the
     * Turnitin Document Viewer.
     */
    public function test_forum_post_html_entities_are_decoded_for_submission(): void {
        $this->resetAfterTest();

        // Moodle's TinyMCE/Atto editor stores "&" as "&amp;" in forum_posts.message.
        $htmlmessage = '<p>Cats &amp; dogs are great. 2 &lt; 3 and 4 &gt; 1.</p>';
        $plaintextcontent = html_to_text($htmlmessage);

        $this->assertStringContainsString('&', $plaintextcontent);
        $this->assertStringNotContainsString('&amp;', $plaintextcontent);
        $this->assertStringNotContainsString('&lt;', $plaintextcontent);
        $this->assertStringNotContainsString('&gt;', $plaintextcontent);
        $this->assertStringContainsString('<', $plaintextcontent);
        $this->assertStringContainsString('>', $plaintextcontent);
    }

    /**
     * Test to check that content returned by set content is the same as passed in array.
     */
    public function test_to_check_content_in_array_is_returned_by_set_content(): void {

        $this->resetAfterTest(true);

        // Create module object.
        $moduleobject = new turnitin_forum();

        $params = [
            'content' => $this->post->message,
        ];

        $content = $moduleobject->set_content($params);
        $this->assertEquals($content, $this->post->message);
    }

    /**
     * Test to check that content returned by set content is taken from database
     * if post id is passed in.
     */
    public function test_to_check_content_from_database_is_returned_by_set_content_if_postid_present(): void {

        $this->resetAfterTest(true);

        // Create module object.
        $moduleobject = new turnitin_forum();

        $params = [
            'content' => 'content should not come back',
            'postid' => $this->post->id,
        ];

        $content = $moduleobject->set_content($params);
        $this->assertEquals($content, $this->post->message);
    }

    /**
     * Test that get_submission_content returns the post text, title, filename and createSubmission
     * api method for a first-time submission (no externalid yet).
     */
    public function test_get_submission_content_returns_content_for_new_submission(): void {
        $this->resetAfterTest();

        $queueditem = $this->make_queued_item($this->post->userid, $this->post->id, null);
        $cm = get_coursemodule_from_instance('forum', $this->forum->id);

        $result = (new turnitin_forum())->get_submission_content($queueditem, $cm, 1);

        $expectedtitle = 'forumpost_' . $this->post->userid . '_' . $cm->id . '_' . $cm->instance
            . '_' . $this->post->id . '.txt';

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
        $this->assertEquals(html_to_text($this->post->message), $result['textcontent']);
        $this->assertEquals($expectedtitle, $result['title']);
        $this->assertEquals($result['title'], $result['filename']);
    }

    /**
     * Test that HTML entities in the forum post message are decoded to plain text before
     * submission to Turnitin. Moodle's editor stores "&" as "&amp;" — Turnitin should receive
     * the literal character, not the entity.
     */
    public function test_get_submission_content_decodes_html_entities(): void {
        $this->resetAfterTest();

        global $DB;
        $DB->set_field('forum_posts', 'message', '<p>Cats &amp; dogs. 2 &lt; 3.</p>', ['id' => $this->post->id]);

        $queueditem = $this->make_queued_item($this->post->userid, $this->post->id, null);
        $cm = get_coursemodule_from_instance('forum', $this->forum->id);

        $result = (new turnitin_forum())->get_submission_content($queueditem, $cm, 1);

        $this->assertStringContainsString('&', $result['textcontent']);
        $this->assertStringNotContainsString('&amp;', $result['textcontent']);
        $this->assertStringContainsString('<', $result['textcontent']);
        $this->assertStringNotContainsString('&lt;', $result['textcontent']);
    }

    /**
     * Test that get_submission_content uses replaceSubmission when the post has already been
     * submitted to Turnitin (externalid present) and report generation is set to re-check on
     * resubmission (report_gen > 0).
     */
    public function test_get_submission_content_uses_replace_when_resubmitting_with_report_gen(): void {
        $this->resetAfterTest();

        $queueditem = $this->make_queued_item($this->post->userid, $this->post->id, 'tii-abc-123');
        $cm = get_coursemodule_from_instance('forum', $this->forum->id);

        $result = (new turnitin_forum())->get_submission_content($queueditem, $cm, 1);

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('replaceSubmission', $result['apimethod']);
    }

    /**
     * Test that get_submission_content falls back to createSubmission when report_gen is 0,
     * even if an externalid exists. Turnitin requires a fresh submission in this mode.
     */
    public function test_get_submission_content_uses_create_when_report_gen_is_zero(): void {
        $this->resetAfterTest();

        $queueditem = $this->make_queued_item($this->post->userid, $this->post->id, 'tii-abc-123');
        $cm = get_coursemodule_from_instance('forum', $this->forum->id);

        $result = (new turnitin_forum())->get_submission_content($queueditem, $cm, 0);

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
    }

    /**
     * Test that get_submission_content returns errorcode 9 when the forum post cannot be found,
     * so the submission can be safely marked as errored without crashing the queue.
     */
    public function test_get_submission_content_returns_error_when_post_not_found(): void {
        $this->resetAfterTest();

        $queueditem = $this->make_queued_item($this->post->userid, 999999, null);
        $cm = get_coursemodule_from_instance('forum', $this->forum->id);

        $result = (new turnitin_forum())->get_submission_content($queueditem, $cm, 1);

        $this->assertEquals(9, $result['errorcode']);
        $this->assertNull($result['textcontent']);
        $this->assertNull($result['title']);
        $this->assertNull($result['filename']);
    }

    /**
     * Build a minimal queued item stdClass as would be read from plagiarism_turnitin_files.
     *
     * @param int $userid
     * @param int $itemid
     * @param string|null $externalid
     */
    private function make_queued_item(int $userid, int $itemid, ?string $externalid): \stdClass {
        $item = new \stdClass();
        $item->userid = $userid;
        $item->itemid = $itemid;
        $item->externalid = $externalid;
        $item->identifier = '';
        return $item;
    }
}
