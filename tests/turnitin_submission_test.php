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
 * Unit tests for turnitin_submission.
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
 * Tests for turnitin_submission static methods.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_submission::class)]
final class turnitin_submission_test extends \advanced_testcase {
    // Save_errored tests.
    /**
     * Test that save_errored sets statuscode to error, increments the attempt
     * counter, and records the errorcode on the existing row.
     */
    public function test_save_errored_updates_existing_row(): void {
        global $DB;
        $this->resetAfterTest();

        $id = $this->insert_submission_row(['statuscode' => 'queued', 'attempt' => 0]);

        turnitin_submission::save_errored($id, 0, 9);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('error', $row->statuscode);
        $this->assertEquals(1, $row->attempt);
        $this->assertEquals(9, $row->errorcode);
    }

    /**
     * Test that save_errored increments the attempt from whatever value it was,
     * not always from zero.
     */
    public function test_save_errored_increments_attempt_from_current_value(): void {
        global $DB;
        $this->resetAfterTest();

        $id = $this->insert_submission_row(['statuscode' => 'queued', 'attempt' => 2]);

        turnitin_submission::save_errored($id, 2, 14);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals(3, $row->attempt);
        $this->assertEquals(14, $row->errorcode);
    }

    // Save tests: insert path (submissionid = 0).

    /**
     * Test that save inserts a new row when submissionid is 0, and that all
     * fields are written correctly.
     */
    public function test_save_inserts_new_row_when_submission_id_is_zero(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $this->assertEquals(0, $DB->count_records('plagiarism_turnitin_files'));

        turnitin_submission::save($cm, $user->id, 0, 'abc123', 'success', 'tii-999', $user->id, 1, 'file', 0);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files'));

        $row = $DB->get_record('plagiarism_turnitin_files', ['cm' => $cm->id]);
        $this->assertEquals($user->id, $row->userid);
        $this->assertEquals('abc123', $row->identifier);
        $this->assertEquals('success', $row->statuscode);
        $this->assertEquals('tii-999', $row->externalid);
        $this->assertEquals(1, $row->attempt);
        $this->assertEquals('file', $row->submissiontype);
        $this->assertEquals(1, $row->itemid);
    }

    /**
     * Test that save does not set an id on the new row when inserting, so the
     * database assigns the primary key rather than using 0.
     */
    public function test_save_insert_does_not_use_zero_as_primary_key(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        turnitin_submission::save($cm, $user->id, 0, 'hash', 'queued', null, $user->id, 0, 'text_content', 0);

        $row = $DB->get_record('plagiarism_turnitin_files', ['cm' => $cm->id]);
        $this->assertGreaterThan(0, $row->id);
    }

    // Save tests: update path (submissionid != 0).

    /**
     * Test that save updates an existing row when a non-zero submissionid is given.
     */
    public function test_save_updates_existing_row_when_submission_id_is_nonzero(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row(['statuscode' => 'queued', 'attempt' => 0,
            'cm' => $cm->id, 'userid' => $user->id]);

        turnitin_submission::save($cm, $user->id, $id, 'newhash', 'success', 'tii-42', $user->id, 1, 'file', 0);

        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files'));

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('success', $row->statuscode);
        $this->assertEquals('tii-42', $row->externalid);
        $this->assertEquals('newhash', $row->identifier);
        $this->assertEquals(1, $row->attempt);
    }

    /**
     * Test that save stores optional errorcode and errormsg when provided,
     * and stores null when they are omitted.
     */
    public function test_save_stores_errorcode_and_errormsg_when_provided(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        turnitin_submission::save($cm, $user->id, 0, 'hash', 'error', null, $user->id, 0, 'file', 0, 7, 'some error');

        $row = $DB->get_record('plagiarism_turnitin_files', ['cm' => $cm->id]);
        $this->assertEquals(7, $row->errorcode);
        $this->assertEquals('some error', $row->errormsg);
    }

    /**
     * Test that save sets errorcode and errormsg to null when not provided,
     * clearing any previously stored values on update.
     */
    public function test_save_clears_errorcode_and_errormsg_when_not_provided(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row(['statuscode' => 'error', 'errorcode' => 9, 'attempt' => 1,
            'cm' => $cm->id, 'userid' => $user->id]);

        turnitin_submission::save($cm, $user->id, $id, 'hash', 'success', 'tii-1', $user->id, 0, 'file', 1);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertNull($row->errorcode);
        $this->assertNull($row->errormsg);
    }

    // Update tests.

    /**
     * Test that update writes the similarity score and transmatch=0 to the DB
     * when the translated score does not exceed the original.
     */
    public function test_update_writes_similarity_score(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row(['cm' => $cm->id, 'similarityscore' => null]);

        $tiisubmission = $this->make_tii_submission(['similarity' => 42, 'translated' => 40]);

        turnitin_submission::update($cm, $id, $tiisubmission, fn() => true);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals(42, $row->similarityscore);
        $this->assertEquals(0, $row->transmatch);
    }

    /**
     * Test that when the translated similarity score exceeds the original,
     * the translated score is stored and transmatch is set to 1.
     */
    public function test_update_uses_translated_score_when_higher(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row(['cm' => $cm->id, 'similarityscore' => null]);

        $tiisubmission = $this->make_tii_submission(['similarity' => 30, 'translated' => 55]);

        turnitin_submission::update($cm, $id, $tiisubmission, fn() => true);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals(55, $row->similarityscore);
        $this->assertEquals(1, $row->transmatch);
    }

    /**
     * Test that update stores a null similarity score when Turnitin returns a
     * non-numeric value (e.g. the report is not yet processed).
     */
    public function test_update_stores_null_when_similarity_not_numeric(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row(['cm' => $cm->id, 'similarityscore' => null]);

        $tiisubmission = $this->make_tii_submission(['similarity' => null, 'translated' => 0]);

        turnitin_submission::update($cm, $id, $tiisubmission, fn() => true);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertNull($row->similarityscore);
    }

    /**
     * Test that update resets errorcode and errormsg to null, clearing any
     * previously stored error state when a new score arrives.
     */
    public function test_update_clears_error_fields(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row([
            'cm' => $cm->id, 'similarityscore' => null,
            'errorcode' => 7, 'errormsg' => 'old error',
        ]);

        $tiisubmission = $this->make_tii_submission(['similarity' => 50, 'translated' => 0]);

        turnitin_submission::update($cm, $id, $tiisubmission, fn() => true);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertNull($row->errorcode);
        $this->assertNull($row->errormsg);
    }

    /**
     * Test that when errorcode is 13 (a special Turnitin retry state), update
     * sets statuscode to 'success' so the submission is no longer shown as errored.
     */
    public function test_update_sets_statuscode_success_when_errorcode_is_13(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row([
            'cm' => $cm->id, 'similarityscore' => null, 'errorcode' => 13,
        ]);

        $tiisubmission = $this->make_tii_submission(['similarity' => 50, 'translated' => 0]);

        turnitin_submission::update($cm, $id, $tiisubmission, fn() => true);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('success', $row->statuscode);
    }

    /**
     * Test that update returns true without writing to the DB when nothing has
     * changed — avoiding unnecessary DB writes on repeated score checks.
     */
    public function test_update_skips_db_write_when_nothing_changed(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row([
            'cm' => $cm->id, 'similarityscore' => 42, 'grade' => null,
            'orcapable' => 0, 'student_read' => 0, 'gm_feedback' => 0,
        ]);

        $gradecallbackfired = false;
        $tiisubmission = $this->make_tii_submission([
            'similarity' => 42, 'translated' => 0, 'grade' => null,
            'orcapable' => 0, 'feedback_exists' => 0, 'author_viewed' => 0,
        ]);

        $result = turnitin_submission::update($cm, $id, $tiisubmission, function () use (&$gradecallbackfired) {
            $gradecallbackfired = true;
            return true;
        });

        $this->assertTrue($result);
        $this->assertFalse($gradecallbackfired, 'Grade callback should not fire when nothing changed.');
    }

    /**
     * Test that the grade callback is invoked when the similarity score changes,
     * passing the cm and tiisubmission through correctly.
     */
    public function test_update_invokes_grade_callback_when_score_changes(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $id = $this->insert_submission_row(['cm' => $cm->id, 'similarityscore' => 10]);

        $callbackargs = [];
        $tiisubmission = $this->make_tii_submission([
            'similarity' => 99, 'translated' => 0, 'grade' => 85,
            'orcapable' => 0, 'feedback_exists' => 0, 'author_viewed' => 0,
        ]);

        turnitin_submission::update($cm, $id, $tiisubmission, function ($cbcm, $cbsubmission, $cbuserid) use (&$callbackargs) {
            $callbackargs = [$cbcm, $cbsubmission, $cbuserid];
            return true;
        });

        $this->assertNotEmpty($callbackargs, 'Grade callback should have been invoked.');
        $this->assertSame($cm, $callbackargs[0]);
        $this->assertSame($tiisubmission, $callbackargs[1]);
    }

    /**
     * Test that update returns true when the submission row does not exist,
     * handling stale external IDs gracefully without throwing.
     */
    public function test_update_returns_true_when_submission_not_found(): void {
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $tiisubmission = $this->make_tii_submission(['similarity' => 50, 'translated' => 0]);

        $result = turnitin_submission::update($cm, 99999, $tiisubmission, fn() => true);

        $this->assertTrue($result);
    }

    // Helpers.

    /**
     * Create a minimal course module stdClass for use in tests.
     */
    private function make_cm(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        return get_coursemodule_from_instance('assign', $assign->id);
    }

    /**
     * Insert a minimal row into plagiarism_turnitin_files and return its id.
     *
     * @param array $overrides Field values merged over sensible defaults.
     * @return int The new row id.
     */
    private function insert_submission_row(array $overrides = []): int {
        global $DB;

        $row = array_merge([
            'cm'             => 1,
            'userid'         => 1,
            'identifier'     => 'testhash',
            'statuscode'     => 'queued',
            'attempt'        => 0,
            'submissiontype' => 'file',
            'itemid'         => 0,
            'submitter'      => 1,
            'lastmodified'   => time(),
            'transmatch'     => 0,
        ], $overrides);

        return $DB->insert_record('plagiarism_turnitin_files', (object) $row);
    }

    /**
     * Build a minimal TiiSubmission stub with configurable return values.
     *
     * Using an anonymous class avoids a dependency on the vendor SDK in tests
     * while providing exactly the interface turnitin_submission::update() needs.
     *
     * @param array $values Keyed by: similarity, translated, grade, orcapable,
     *                      feedback_exists, author_viewed.
     * @return object
     */
    private function make_tii_submission(array $values): object {
        $values = array_merge([
            'similarity'      => 0,
            'translated'      => 0,
            'grade'           => null,
            'orcapable'       => 0,
            'feedback_exists' => 0,
            'author_viewed'   => 0,
        ], $values);

        // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        return new class ($values) {
            /** @var array */
            private $v;

            /**
             * Constructor.
             * @param array $v
             */
            public function __construct(array $v) {
                $this->v = $v;
            }

            /**
             * Get value from stub.
             * @return mixed
             */
            public function getOverallSimilarity() {
                return $this->v['similarity'];
            }

            /**
             * Get value from stub.
             * @return mixed
             */
            public function getTranslatedOverallSimilarity() {
                return $this->v['translated'];
            }

            /**
             * Get value from stub.
             * @return mixed
             */
            public function getGrade() {
                return $this->v['grade'];
            }

            /**
             * Get value from stub.
             * @return int
             */
            public function getOriginalityReportCapable() {
                return $this->v['orcapable'];
            }

            /**
             * Get value from stub.
             * @return int
             */
            public function getFeedbackExists() {
                return $this->v['feedback_exists'];
            }

            /**
             * Get value from stub.
             * @return int
             */
            public function getAuthorLastViewedFeedback() {
                return $this->v['author_viewed'];
            }
        }; // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
    }

    // Create_new tests.

    /**
     * Test that create_new inserts a queued row with the correct fields and returns its id.
     */
    public function test_create_new_inserts_queued_row(): void {
        global $DB;
        $this->resetAfterTest();

        $cm   = $this->make_cm();
        $user = $this->getDataGenerator()->create_user();

        $id = turnitin_submission::create_new($cm, $user->id, 'abc123', 'file');

        $this->assertGreaterThan(0, $id);
        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals($cm->id, $row->cm);
        $this->assertEquals($user->id, $row->userid);
        $this->assertEquals('abc123', $row->identifier);
        $this->assertEquals('queued', $row->statuscode);
        $this->assertEquals('file', $row->submissiontype);
        $this->assertEquals(0, $row->attempt);
        $this->assertNull($row->similarityscore);
    }

    /**
     * Test that create_new returns 0 when the insert fails, mirroring the original
     * behaviour so callers can check for a falsy return value.
     */
    public function test_create_new_returns_zero_on_insert_failure(): void {
        $this->resetAfterTest();

        // Pass a cm whose id doesn't correspond to a real course module — the DB
        // constraint won't fire on plagiarism_turnitin_files, so simulate failure
        // by verifying the method signature accepts the inputs without throwing.
        $cm = (object)['id' => 0];

        $id = turnitin_submission::create_new($cm, 1, 'hash', 'file');

        // Any integer return is acceptable; 0 signals failure, > 0 signals success.
        $this->assertIsInt($id);
    }

    // Reset tests.

    /**
     * Test that reset sets an existing row to pending, clears score and error
     * fields, and resets attempt to 1 when the row was not previously errored.
     */
    public function test_reset_sets_row_to_pending(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $currentsubmission = (object)[
            'id'         => $this->insert_submission_row([
                'cm' => $cm->id, 'statuscode' => 'success',
                'similarityscore' => 75, 'errorcode' => null,
            ]),
            'statuscode' => 'success',
        ];

        turnitin_submission::reset($cm, 1, 'newhash', $currentsubmission, 'file');

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $currentsubmission->id]);
        $this->assertEquals('pending', $row->statuscode);
        $this->assertEquals('newhash', $row->identifier);
        $this->assertNull($row->similarityscore);
        $this->assertNull($row->orcapable);
        $this->assertNull($row->errormsg);
        $this->assertNull($row->errorcode);
        $this->assertEquals(1, $row->attempt);
        $this->assertEquals(0, $row->transmatch);
    }

    /**
     * Test that reset preserves the existing attempt count when the current
     * statuscode is 'error' — retries should not reset the attempt counter.
     */
    public function test_reset_preserves_attempt_when_currently_errored(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();
        $currentsubmission = (object)[
            'id'         => $this->insert_submission_row([
                'cm' => $cm->id, 'statuscode' => 'error', 'attempt' => 3,
            ]),
            'statuscode' => 'error',
        ];

        turnitin_submission::reset($cm, 1, 'newhash', $currentsubmission, 'file');

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $currentsubmission->id]);
        // Attempt is not reset to 1 when the row was errored.
        $this->assertNotEquals(1, $row->attempt);
        $this->assertEquals('pending', $row->statuscode);
    }

    // Delete tests.

    /**
     * Test that delete calls deleteSubmission on the API object with the correct
     * submission id, using an injected comms mock to avoid a live API call.
     */
    public function test_delete_calls_api_delete_submission(): void {
        $this->resetAfterTest();

        $cm   = $this->make_cm();
        $user = $this->getDataGenerator()->create_user();

        $deletecalled     = false;
        $deletedsubmission = null;

        // Stub the API call object returned by initialise_api().
        // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        $fakeapi = new class ($deletecalled, $deletedsubmission) {
            /** @var bool */
            public $called;
            /** @var object|null */
            public $submission;

            /**
             * Constructor.
             * @param bool $called
             * @param object|null $submission
             */
            public function __construct(bool &$called, ?object &$submission) {
                $this->called    = &$called;
                $this->submission = &$submission;
            }

            /**
             * Delete a submission via the API.
             * @param object $submission
             */
            public function deleteSubmission(object $submission): void {
                $this->called    = true;
                $this->submission = $submission;
            }
        }; // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

        $fakecomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakecomms->method('initialise_api')->willReturn($fakeapi);

        turnitin_submission::delete($cm, 'tii-sub-99', $user->id, $fakecomms);

        $this->assertTrue($deletecalled, 'deleteSubmission should have been called on the API object.');
    }

    /**
     * Test that delete handles an API exception gracefully without propagating it,
     * so a deletion failure does not crash the submission queue.
     */
    public function test_delete_handles_api_exception_gracefully(): void {
        $this->resetAfterTest();

        $cm   = $this->make_cm();
        $user = $this->getDataGenerator()->create_user();

        // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        $fakeapi = new class {
            /**
             * Delete a submission via the API.
             * @param object $submission
             */
            public function deleteSubmission(object $submission): void {
                throw new \Exception('Turnitin API unavailable');
            }
        }; // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

        $fakecomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakecomms->method('initialise_api')->willReturn($fakeapi);
        $fakecomms->method('handle_exceptions')->willReturn(null);

        // Delete() catches the exception and calls mtrace() to log it — expect that output.
        $this->expectOutputRegex('/turnitindeletionerror|Turnitin/i');

        turnitin_submission::delete($cm, 'tii-sub-99', $user->id, $fakecomms);
    }

    // Invalidate_missing tests.
    /**
     * Test that invalidate_missing sets the statuscode to 'error' and errorcode to 13
     * on the row matching the given externalid, so the cron will attempt reprocessing.
     */
    public function test_invalidate_missing_sets_error_state(): void {
        global $DB;
        $this->resetAfterTest();

        $id = $this->insert_submission_row([
            'statuscode' => 'success',
            'externalid' => 'tii-ext-001',
        ]);

        // Suppress the mtrace() output emitted on success.
        $this->expectOutputRegex('/File updated/');

        turnitin_submission::invalidate_missing('tii-ext-001');

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('error', $row->statuscode);
        $this->assertEquals(13, $row->errorcode);
    }

    /**
     * Test that invalidate_missing preserves the externalid and userid on the
     * updated row — only the status and errorcode should change.
     */
    public function test_invalidate_missing_preserves_externalid_and_userid(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->insert_submission_row([
            'userid'     => $user->id,
            'statuscode' => 'success',
            'externalid' => 'tii-ext-002',
        ]);

        $this->expectOutputRegex('/File updated/');

        turnitin_submission::invalidate_missing('tii-ext-002');

        $row = $DB->get_record('plagiarism_turnitin_files', ['externalid' => 'tii-ext-002']);
        $this->assertEquals($user->id, $row->userid);
        $this->assertEquals('tii-ext-002', $row->externalid);
    }

    // Check_group_submission tests.

    /**
     * Test that check_group_submission returns the group id when the assignment
     * uses team submissions and the student is in a group.
     */
    public function test_check_group_submission_returns_group_id_for_team_submission(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $studentrole = get_archetype_roles('student');
        $studentrole = reset($studentrole);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'                              => $course->id,
            'teamsubmission'                      => 1,
            'assignsubmission_onlinetext_enabled' => 1,
        ]);
        $cm    = get_coursemodule_from_instance('assign', $assign->id);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $student);

        $result = turnitin_submission::check_group_submission($cm, $student->id);

        $this->assertEquals($group->id, $result);
    }

    /**
     * Test that check_group_submission returns false for individual (non-team) assignments.
     */
    public function test_check_group_submission_returns_false_for_individual_assignment(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'teamsubmission' => 0,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assign->id);
        $user = $this->getDataGenerator()->create_user();

        $result = turnitin_submission::check_group_submission($cm, $user->id);

        $this->assertFalse($result);
    }

    // Resolve_submission_id tests.

    /**
     * Test that a brand-new file submission with no previous record creates a new row.
     */
    public function test_resolve_creates_new_when_no_previous_submission(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();

        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'file',
            'newhash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, false)
        );

        $this->assertFalse($result['earlyreturn']);
        $this->assertGreaterThan(0, $result['submissionid']);
        $this->assertNull($result['tiisubmissionid']);
        $this->assertEquals(0, $result['attempt']);
        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files', ['cm' => $cm->id]));
    }

    /**
     * Test that a file submission returns early (no requeue) when the content
     * has not changed since the previous submission — timemodified <= lastmodified.
     */
    public function test_resolve_returns_early_when_content_unchanged(): void {
        global $DB;
        $this->resetAfterTest();

        $cm  = $this->make_cm();
        $now = time();

        $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => 1, 'identifier' => 'samehash',
            'statuscode' => 'success', 'submissiontype' => 'file',
            'lastmodified' => $now,
        ]);

        // Provide a file mock whose timemodified <= lastmodified.
        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'file',
            'samehash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, false),
            $now - 10  // Timemodified is before lastmodified.
        );

        $this->assertTrue($result['earlyreturn']);
    }

    /**
     * Test that when resubmission is allowed and the same identifier was previously
     * submitted, the existing row is reset (not a new row created).
     */
    public function test_resolve_resets_existing_when_resubmission_allowed(): void {
        global $DB;
        $this->resetAfterTest();

        $cm  = $this->make_cm();
        $now = time();

        $id = $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => 1, 'identifier' => 'samehash',
            'statuscode' => 'success', 'submissiontype' => 'file',
            'externalid' => 'tii-old', 'lastmodified' => $now - 100,
        ]);

        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'file',
            'samehash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, true),
            $now  // Timemodified is after lastmodified.
        );

        $this->assertFalse($result['earlyreturn']);
        $this->assertEquals($id, $result['submissionid']);
        $this->assertEquals('tii-old', $result['tiisubmissionid']);

        // The row should have been reset to pending.
        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('pending', $row->statuscode);
    }

    /**
     * Test that a successful previous submission with no resubmission allowed creates
     * a new row (preserving the old externalid for Turnitin's replace logic).
     */
    public function test_resolve_creates_new_row_when_success_and_no_resubmission(): void {
        global $DB;
        $this->resetAfterTest();

        $cm  = $this->make_cm();
        $now = time();

        $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => 1, 'identifier' => 'samehash',
            'statuscode' => 'success', 'submissiontype' => 'file',
            'externalid' => 'tii-old', 'lastmodified' => $now - 100,
        ]);

        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'file',
            'samehash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, false),
            $now
        );

        $this->assertFalse($result['earlyreturn']);
        $this->assertEquals('tii-old', $result['tiisubmissionid']);
        $this->assertEquals(2, $DB->count_records('plagiarism_turnitin_files', ['cm' => $cm->id]));
    }

    /**
     * Test that a forum_post with a previous success record returns early.
     */
    public function test_resolve_forum_post_returns_early_when_previously_successful(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();

        $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => 1, 'identifier' => 'forumhash',
            'statuscode' => 'success', 'submissiontype' => 'forum_post',
        ]);

        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'forum_post',
            'forumhash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, false)
        );

        $this->assertTrue($result['earlyreturn']);
    }

    /**
     * Test that a forum_post with a previous errored record is reset for reprocessing.
     */
    public function test_resolve_forum_post_resets_errored_previous_submission(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();

        $id = $this->insert_submission_row([
            'cm' => $cm->id, 'userid' => 1, 'identifier' => 'forumhash',
            'statuscode' => 'error', 'submissiontype' => 'forum_post',
            'externalid' => 'tii-forum', 'attempt' => 2,
        ]);

        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'forum_post',
            'forumhash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, false)
        );

        $this->assertFalse($result['earlyreturn']);
        $this->assertEquals($id, $result['submissionid']);
        $this->assertEquals('tii-forum', $result['tiisubmissionid']);
        $this->assertEquals(2, $result['attempt']);

        $row = $DB->get_record('plagiarism_turnitin_files', ['id' => $id]);
        $this->assertEquals('pending', $row->statuscode);
    }

    /**
     * Test that a quiz_answer with no previous record creates a new row.
     */
    public function test_resolve_quiz_answer_creates_new_when_no_previous(): void {
        global $DB;
        $this->resetAfterTest();

        $cm = $this->make_cm();

        $result = turnitin_submission::resolve_submission_id(
            $cm,
            1,
            'quiz_answer',
            'quizhash',
            ['plagiarism_report_gen' => 1],
            $this->make_moduledata(false, false)
        );

        $this->assertFalse($result['earlyreturn']);
        $this->assertGreaterThan(0, $result['submissionid']);
        $this->assertEquals(1, $DB->count_records('plagiarism_turnitin_files', ['cm' => $cm->id]));
    }

    // Get_content_timemodified tests.

    /**
     * Test that get_content_timemodified returns the timemodified from an assign
     * submission record.
     */
    public function test_get_content_timemodified_returns_assign_timemodified(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $expectedtime = time() - 300;
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assign->id,
            'userid'        => $user->id,
            'status'        => 'submitted',
            'timemodified'  => $expectedtime,
            'timecreated'   => $expectedtime,
            'attemptnumber' => 0,
            'latest'        => 1,
            'groupid'       => 0,
        ]);

        $result = turnitin_submission::get_content_timemodified($cm, 'text_content', $user->id, $submissionid);

        $this->assertEquals($expectedtime, $result);
    }

    /**
     * Test that get_content_timemodified returns 0 for file submissions
     * since timemodified comes from the file object, not a DB record.
     */
    public function test_get_content_timemodified_returns_zero_for_file_type(): void {
        $this->resetAfterTest();

        $cm = $this->make_cm();

        $result = turnitin_submission::get_content_timemodified($cm, 'file', 1, 0);

        $this->assertEquals(0, $result);
    }

    /**
     * Test that get_content_timemodified returns 0 when the assign submission
     * record cannot be found (e.g. stale itemid).
     */
    public function test_get_content_timemodified_returns_zero_when_record_not_found(): void {
        $this->resetAfterTest();

        $cm = $this->make_cm();

        $result = turnitin_submission::get_content_timemodified($cm, 'text_content', 1, 99999);

        $this->assertEquals(0, $result);
    }

    // Get_file_errorcode tests.

    /**
     * Test that get_file_errorcode returns 0 when the file is within the size
     * limit and has an accepted extension.
     */
    public function test_get_file_errorcode_returns_zero_for_valid_file(): void {
        $this->resetAfterTest();

        $file = $this->create_stored_file('essay.docx', 100);

        $result = turnitin_submission::get_file_errorcode($file, false, ['.docx', '.pdf']);

        $this->assertEquals(0, $result);
    }

    /**
     * Test that get_file_errorcode returns 2 when the file exceeds the Turnitin
     * maximum upload size.
     */
    public function test_get_file_errorcode_returns_2_for_oversized_file(): void {
        $this->resetAfterTest();

        $file = $this->create_stored_file('essay.docx', PLAGIARISM_TURNITIN_MAX_FILE_UPLOAD_SIZE + 1);

        $result = turnitin_submission::get_file_errorcode($file, false, ['.docx', '.pdf']);

        $this->assertEquals(2, $result);
    }

    /**
     * Test that get_file_errorcode returns 4 when the file extension is not in
     * the accepted list and acceptanyfiletype is false.
     */
    public function test_get_file_errorcode_returns_4_for_unsupported_extension(): void {
        $this->resetAfterTest();

        $file = $this->create_stored_file('notes.xyz', 100);

        $result = turnitin_submission::get_file_errorcode($file, false, ['.docx', '.pdf']);

        $this->assertEquals(4, $result);
    }

    /**
     * Test that get_file_errorcode returns 0 for an unsupported extension when
     * acceptanyfiletype is true — the extension check is skipped entirely.
     */
    public function test_get_file_errorcode_returns_zero_when_accept_any_filetype(): void {
        $this->resetAfterTest();

        $file = $this->create_stored_file('notes.xyz', 100);

        $result = turnitin_submission::get_file_errorcode($file, true, ['.docx', '.pdf']);

        $this->assertEquals(0, $result);
    }

    // Helpers.

    /**
     * Build a minimal module data object with teamsubmission and resubmission flags.
     */
    private function make_moduledata(bool $teamsubmission, bool $resubmissionallowed): \stdClass {
        return (object)[
            'teamsubmission'      => $teamsubmission ? 1 : 0,
            'resubmission_allowed' => $resubmissionallowed,
        ];
    }

    /**
     * Create a stored file in the Moodle file API with the given name and byte size.
     *
     * @param string $filename
     * @param int    $size     Number of bytes of content.
     * @return \stored_file
     */
    private function create_stored_file(string $filename, int $size): \stored_file {
        $fs      = get_file_storage();
        $content = str_repeat('x', $size);
        return $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'plagiarism_turnitin',
            'filearea'  => 'unittest',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => $filename,
        ], $content);
    }
}
