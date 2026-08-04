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
 * Unit tests for (some of) plagiarism/turnitin/classes/modules/turnitin_quiz.php.
 *
 * @package    plagiarism_turnitin
 * @copyright  2017 Turnitin
 * @copyright  2022 The University of Southern Queensland
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitin/lib.php');

use PHPUnit\Framework\Attributes\CoversClass;
use plagiarism_turnitin\modules\turnitin_quiz;

/**
 * Tests for Turnitin quiz class.
 *
 * @package plagiarism_turnitin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(turnitin_quiz::class)]
final class turnitin_quiz_test extends \advanced_testcase {
    /** @var stdClass Quiz instance created in setUp. */
    protected $quiz;

    /** @var stdClass User created in setUp. */
    protected $user;

    /** @var stdClass Course created in setUp. */
    protected $course;

    /** @var int The attempt id created in setUp. */
    protected $attemptid;

    /**
     * Create a course, user, quiz with one essay question and a submitted attempt.
     * Lifted largely from mod_quiz_attempt_testcase.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->user   = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $this->quiz = $generator->create_instance([
            'course'     => $this->course->id,
            'grade'      => 100,
            'sumgrades'  => 1,
            'layout'     => '1,0',
        ]);

        $quizobj = \mod_quiz\quiz_settings::create($this->quiz->id, $this->user->id);
        $quba    = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat      = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('essay', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $this->quiz, 1, 1);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $this->user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);

        // Submit an essay answer so get_response_summary() returns non-empty content,
        // which is required for the submission content tests to find a matching slot.
        // process_submitted_actions takes an array keyed by slot number.
        $attemptobj->process_submitted_actions($timenow, false, [
            1 => ['answer' => 'This is my essay answer.', 'answerformat' => FORMAT_PLAIN],
        ]);

        $attemptobj->process_submit($timenow, false);
        $attemptobj->process_grade_submission($timenow);

        $this->attemptid = $attempt->id;
    }

    /**
     * Proves that essay response marks are correctly updated.
     */
    public function test_update_mark(): void {
        $attemptobj = \mod_quiz\quiz_attempt::create($this->attemptid);
        $this->assertEquals(0.0, $attemptobj->get_sum_marks());
        $this->assertEquals(0.0, quiz_get_best_grade($this->quiz, $this->user->id));

        $tiiquiz    = new turnitin_quiz();
        $answer     = $attemptobj->get_question_attempt(1)->get_response_summary();
        $identifier = sha1($answer . 1);
        $tiiquiz->update_mark($this->attemptid, $identifier, $this->user->id, 75, $this->quiz->grade);

        $attemptobj = \mod_quiz\quiz_attempt::create($this->attemptid);
        $this->assertEquals(0.75, $attemptobj->get_sum_marks());
        $this->assertEquals(75.0, quiz_get_best_grade($this->quiz, $this->user->id));
    }

    // Get_submission_content tests.

    /**
     * Test that get_submission_content returns the stripped answer text, a correctly
     * formatted title, and createSubmission for a first-time submission.
     */
    public function test_get_submission_content_returns_content_for_new_submission(): void {
        $this->resetAfterTest();

        [$cm, $queueditem, $attemptobj] = $this->create_quiz_submission(null);

        $result = (new turnitin_quiz())->get_submission_content($queueditem, $cm, $this->user->id, 1);

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
        $this->assertNotEmpty($result['textcontent']);
        // Strip tags should have removed any HTML from the response summary.
        $this->assertEquals(strip_tags($result['textcontent']), $result['textcontent']);
        $expectedtitle = 'quizanswer_' . $this->user->id . '_' . $cm->id . '_'
            . $cm->instance . '_' . $queueditem->itemid . '.txt';
        $this->assertEquals($expectedtitle, $result['title']);
        $this->assertEquals($result['title'], $result['filename']);
    }

    /**
     * Test that get_submission_content uses replaceSubmission when an externalid exists
     * and report_gen > 0.
     */
    public function test_get_submission_content_uses_replace_when_resubmitting_with_report_gen(): void {
        $this->resetAfterTest();

        [$cm, $queueditem] = $this->create_quiz_submission('tii-existing-id');

        $result = (new turnitin_quiz())->get_submission_content($queueditem, $cm, $this->user->id, 1);

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('replaceSubmission', $result['apimethod']);
    }

    /**
     * Test that get_submission_content uses createSubmission when report_gen is 0,
     * even if an externalid exists.
     */
    public function test_get_submission_content_uses_create_when_report_gen_is_zero(): void {
        $this->resetAfterTest();

        [$cm, $queueditem] = $this->create_quiz_submission('tii-existing-id');

        $result = (new turnitin_quiz())->get_submission_content($queueditem, $cm, $this->user->id, 0);

        $this->assertEquals(0, $result['errorcode']);
        $this->assertEquals('createSubmission', $result['apimethod']);
    }

    /**
     * Test that get_submission_content returns errorcode 14 when the attempt cannot
     * be found, so the submission is safely marked as errored.
     */
    public function test_get_submission_content_returns_error_when_attempt_not_found(): void {
        $this->resetAfterTest();

        $cm         = get_coursemodule_from_instance('quiz', $this->quiz->id);
        $queueditem = $this->make_queued_item(999999, 'nonexistent-hash', null);

        $result = (new turnitin_quiz())->get_submission_content($queueditem, $cm, $this->user->id, 1);

        $this->assertEquals(14, $result['errorcode']);
        $this->assertNull($result['textcontent']);
        $this->assertNull($result['title']);
        $this->assertNull($result['filename']);
    }

    /**
     * Test that get_submission_content returns errorcode 9 when the attempt exists but
     * no slot matches the identifier hash — e.g. the submission has been modified.
     */
    public function test_get_submission_content_returns_error_when_slot_not_matched(): void {
        $this->resetAfterTest();

        [$cm, $queueditem] = $this->create_quiz_submission(null);
        $queueditem->identifier = 'does-not-match-any-slot';

        $result = (new turnitin_quiz())->get_submission_content($queueditem, $cm, $this->user->id, 1);

        $this->assertEquals(9, $result['errorcode']);
    }

    // Helpers.

    /**
     * Build the identifier hash for slot 1 of the attempt created in setUp(), and return
     * the cm and a queued item ready for get_submission_content().
     *
     * @param string|null $externalid
     * @return array [cm, queueditem, attemptobj]
     */
    private function create_quiz_submission(?string $externalid): array {
        $cm         = get_coursemodule_from_instance('quiz', $this->quiz->id);
        $attemptobj = \mod_quiz\quiz_attempt::create($this->attemptid);

        // Build the identifier the same way lib.php does when it queues the submission.
        $slot       = 1;
        $identifier = sha1('quiz_attempt user' . $attemptobj->get_userid()
            . ' cm' . $cm->id
            . ' slot' . $slot
            . ' attempt' . $attemptobj->get_attempt_number());

        $queueditem = $this->make_queued_item($this->attemptid, $identifier, $externalid);

        return [$cm, $queueditem, $attemptobj];
    }

    /**
     * Build a minimal queued item as would be read from plagiarism_turnitin_files.
     */
    private function make_queued_item(int $itemid, string $identifier, ?string $externalid): \stdClass {
        $item             = new \stdClass();
        $item->itemid     = $itemid;
        $item->identifier = $identifier;
        $item->externalid = $externalid;
        return $item;
    }
}
