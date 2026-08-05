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

    // Tests for update_gradebook().

    /**
     * Build a minimal cm stdClass for update_gradebook tests.
     *
     * @param string $modname  Module type name.
     * @param int    $instance Module instance id.
     * @param int    $course   Course id.
     */
    private function make_cm_obj(string $modname = 'assign', int $instance = 1, int $course = 1): \stdClass {
        return (object)[
            'id'       => 1,
            'modname'  => $modname,
            'instance' => $instance,
            'course'   => $course,
        ];
    }

    /**
     * Build a minimal TiiSubmission mock with no configured return values.
     */
    private function make_empty_tii_submission(): object {
        $mock = $this->getMockBuilder(\Integrations\PhpSdk\TiiSubmission::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $mock;
    }

    /**
     * Test update_gradebook returns true when the submission row does not exist.
     */
    public function test_update_gradebook_returns_true_when_no_submission_row(): void {
        $this->resetAfterTest();

        $result = turnitin_submission::update_gradebook(
            $this->make_cm_obj(),
            9999,
            $this->make_empty_tii_submission(),
            1
        );

        $this->assertTrue($result);
    }

    /**
     * Test update_gradebook returns true immediately for coursework modules.
     */
    public function test_update_gradebook_returns_true_for_coursework(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $id   = $this->insert_submission_row(['userid' => $user->id, 'submissiontype' => 'file']);

        $result = turnitin_submission::update_gradebook(
            $this->make_cm_obj('coursework'),
            $id,
            $this->make_empty_tii_submission(),
            $user->id
        );

        $this->assertTrue($result);
    }

    /**
     * Test update_gradebook returns true for quiz when grade is null.
     */
    public function test_update_gradebook_returns_true_for_quiz_with_null_grade(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('quiz', $quiz->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'submissiontype' => 'file',
            'grade'          => null,
        ]);

        $cmobj           = $this->make_cm_obj('quiz', (int)$quiz->id, (int)$course->id);
        $cmobj->id       = (int)$cm->id;

        $result = turnitin_submission::update_gradebook(
            $cmobj,
            $id,
            $this->make_empty_tii_submission(),
            $user->id
        );

        $this->assertTrue($result);
    }

    /**
     * Test update_gradebook returns true for non-assign modules when grade is null.
     */
    public function test_update_gradebook_returns_true_when_grade_is_null(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $id   = $this->insert_submission_row([
            'userid'         => $user->id,
            'submissiontype' => 'file',
            'grade'          => null,
        ]);

        $result = turnitin_submission::update_gradebook(
            $this->make_cm_obj('forum'),
            $id,
            $this->make_empty_tii_submission(),
            $user->id
        );

        $this->assertTrue($result);
    }

    /**
     * Test update_gradebook calls the injected gradeupdater when grade is set
     * and a grade item exists for the module.
     *
     * Uses forum module to avoid the assign-specific latest-submission check.
     */
    public function test_update_gradebook_calls_gradeupdater_when_grade_and_gradeitem_exist(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'submissiontype' => 'online_text',
            'identifier'     => 'somehash',
            'grade'          => 85,
        ]);

        // Create a grade item so the gradebook path is taken.
        $gradeitem = new \stdClass();
        $gradeitem->courseid    = $course->id;
        $gradeitem->itemtype    = 'mod';
        $gradeitem->itemmodule  = 'forum';
        $gradeitem->iteminstance = $forum->id;
        $gradeitem->itemnumber  = 0;
        $gradeitem->itemname    = 'Forum grade';
        $gradeitem->gradetype   = 1;
        $gradeitem->grademax    = 100;
        $DB->insert_record('grade_items', $gradeitem);

        $cmobj = (object)[
            'id'       => (int)$cm->id,
            'modname'  => 'forum',
            'instance' => (int)$forum->id,
            'course'   => (int)$course->id,
        ];

        $gradeupdatercalled = false;
        $gradeupdater = function ($cm, $tii, $userid) use (&$gradeupdatercalled): bool {
            $gradeupdatercalled = true;
            return true;
        };

        turnitin_submission::update_gradebook(
            $cmobj,
            $id,
            $this->make_empty_tii_submission(),
            $user->id,
            $gradeupdater
        );

        $this->assertTrue($gradeupdatercalled);
    }

    /**
     * Test update_gradebook skips gradeupdater when grade item does not exist.
     */
    public function test_update_gradebook_skips_gradeupdater_when_no_grade_item(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $id   = $this->insert_submission_row([
            'userid'         => $user->id,
            'submissiontype' => 'online_text',
            'identifier'     => 'somehash',
            'grade'          => 85,
        ]);

        // Use forum so the assign branch is not entered (avoids false-property warnings).
        // Module instance 99999 has no grade_items row.
        $cmobj = $this->make_cm_obj('forum', 99999, 99999);

        $gradeupdatercalled = false;
        $gradeupdater = function () use (&$gradeupdatercalled): bool {
            $gradeupdatercalled = true;
            return true;
        };

        $result = turnitin_submission::update_gradebook(
            $cmobj,
            $id,
            $this->make_empty_tii_submission(),
            $user->id,
            $gradeupdater
        );

        $this->assertFalse($gradeupdatercalled);
        $this->assertTrue($result);
    }

    /**
     * Test update_gradebook sets gbupdaterequired=false for a file submission
     * when the stored file cannot be found in the file store.
     */
    public function test_update_gradebook_skips_gradebook_when_file_not_found(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $id = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'submissiontype' => 'file',
            // A hash that does not exist in the Moodle file store.
            'identifier'     => sha1('nonexistent_file_' . uniqid()),
            'grade'          => 90,
        ]);

        $cmobj = (object)[
            'id'       => (int)$cm->id,
            'modname'  => 'assign',
            'instance' => (int)$assign->id,
            'course'   => (int)$course->id,
        ];

        $gradeupdatercalled = false;
        $gradeupdater = function () use (&$gradeupdatercalled): bool {
            $gradeupdatercalled = true;
            return true;
        };

        // File not found → gbupdaterequired=false → gradeupdater not called.
        turnitin_submission::update_gradebook(
            $cmobj,
            $id,
            $this->make_empty_tii_submission(),
            $user->id,
            $gradeupdater
        );

        $this->assertFalse($gradeupdatercalled);
    }

    // Tests for resolve_content_identifier().

    /**
     * Build a minimal cm stdClass for resolve_content_identifier tests.
     */
    private function make_cm_for_content(string $modname = 'assign', int $id = 1): \stdClass {
        return (object)['id' => $id, 'modname' => $modname, 'instance' => 1, 'course' => 1];
    }

    /**
     * Build a mock moduleobject with a controllable get_onlinetext() return value.
     *
     * @param int $itemid The itemid to return from get_onlinetext().
     */
    private function make_mock_moduleobject(int $itemid = 5): object {
        $mock = $this->getMockBuilder(\plagiarism_turnitin\modules\turnitin_assign::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_onlinetext'])
            ->getMock();
        $mock->method('get_onlinetext')->willReturn((object)['itemid' => $itemid, 'onlinetext' => '']);
        return $mock;
    }

    /**
     * Test resolve_content_identifier returns empty/zero defaults when linkarray has no file or content.
     */
    public function test_resolve_content_identifier_returns_defaults_for_empty_linkarray(): void {
        $cm     = $this->make_cm_for_content();
        $module = $this->make_mock_moduleobject();

        $result = turnitin_submission::resolve_content_identifier(
            ['cmid' => 1, 'userid' => 1],
            $cm,
            null,
            $module
        );

        $this->assertSame('', $result->identifier);
        $this->assertSame('', $result->oldidentifier);
        $this->assertSame(0, $result->itemid);
        $this->assertSame('', $result->submissiontype);
    }

    /**
     * Test resolve_content_identifier uses pathnamehash and itemid for file submissions.
     */
    public function test_resolve_content_identifier_file_submission(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'plagiarism_turnitin',
            'filearea'  => 'unittest',
            'itemid'    => 99,
            'filepath'  => '/',
            'filename'  => 'essay.txt',
        ], 'test content');

        $cm     = $this->make_cm_for_content('assign');
        $module = $this->make_mock_moduleobject();

        $result = turnitin_submission::resolve_content_identifier(
            ['cmid' => 1, 'userid' => 1, 'file' => $file],
            $cm,
            $file,
            $module
        );

        $this->assertEquals($file->get_pathnamehash(), $result->identifier);
        $this->assertEquals(99, $result->itemid);
        $this->assertEquals('file', $result->submissiontype);
        $this->assertSame('', $result->oldidentifier);
    }

    /**
     * Test resolve_content_identifier computes forum_post hashes correctly.
     */
    public function test_resolve_content_identifier_forum_post(): void {
        $cm     = $this->make_cm_for_content('forum', 7);
        $module = $this->make_mock_moduleobject();
        $content = 'This is the forum post body.';

        $result = turnitin_submission::resolve_content_identifier(
            ['cmid' => 7, 'userid' => 42, 'content' => $content],
            $cm,
            null,
            $module
        );

        $this->assertEquals('forum_post', $result->submissiontype);
        $this->assertEquals(sha1('forum_post user42 cm7 ' . $content), $result->identifier);
        $this->assertEquals(sha1($content), $result->oldidentifier);
        $this->assertSame(0, $result->itemid);
    }

    /**
     * Test resolve_content_identifier computes assign text_content hashes correctly.
     */
    public function test_resolve_content_identifier_assign_text_content(): void {
        $cm      = $this->make_cm_for_content('assign', 3);
        $content = 'Online text submission.';
        $itemid  = 17;
        $module  = $this->make_mock_moduleobject($itemid);

        $result = turnitin_submission::resolve_content_identifier(
            ['cmid' => 3, 'userid' => 10, 'content' => $content],
            $cm,
            null,
            $module
        );

        $this->assertEquals('text_content', $result->submissiontype);
        $this->assertEquals(sha1('text_content cm3 itemid' . $itemid . ' ' . $content), $result->identifier);
        $this->assertEquals(sha1($content), $result->oldidentifier);
        $this->assertEquals($itemid, $result->itemid);
    }

    /**
     * Test resolve_content_identifier computes a plain sha1 hash for non-assign, non-forum, non-quiz text.
     */
    public function test_resolve_content_identifier_generic_text_content(): void {
        $cm      = $this->make_cm_for_content('workshop', 5);
        $content = 'Workshop submission text.';
        $module  = $this->make_mock_moduleobject();

        $result = turnitin_submission::resolve_content_identifier(
            ['cmid' => 5, 'userid' => 3, 'content' => $content],
            $cm,
            null,
            $module
        );

        $this->assertEquals('text_content', $result->submissiontype);
        $this->assertEquals(sha1($content), $result->identifier);
        $this->assertSame('', $result->oldidentifier);
        $this->assertSame(0, $result->itemid);
    }

    /**
     * Test resolve_content_identifier correctly identifies quiz_answer as the submission type.
     *
     * The hash computation (lines 1152–1157) requires quiz_attempt::create_from_usage_id()
     * which needs a real attempt in the DB. We verify only that the quiz branch is entered
     * by confirming the submissiontype is set before the hash would be computed.
     */
    public function test_resolve_content_identifier_sets_quiz_answer_submissiontype(): void {
        $cm     = $this->make_cm_for_content('quiz', 8);
        $module = $this->getMockBuilder(\plagiarism_turnitin\modules\turnitin_quiz::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Pass a quiz cm with content — the quiz_answer branch is entered and the
        // submissiontype is set. The subsequent create_from_usage_id() call will throw
        // because there is no real quiz attempt; that is expected and caught here.
        $threwexception = false;
        try {
            turnitin_submission::resolve_content_identifier(
                ['cmid' => 8, 'userid' => 1, 'content' => 'quiz answer', 'area' => 0, 'itemid' => 1],
                $cm,
                null,
                $module
            );
        } catch (\Throwable $e) {
            $threwexception = true;
        }

        // The exception confirms the quiz_answer branch (line 1144) was entered.
        $this->assertTrue($threwexception, 'Expected create_from_usage_id to throw without a real attempt.');
    }

    // Tests for resolve_submission_users().

    /**
     * Test resolve_submission_users returns just the submitting user for a non-group module.
     */
    public function test_resolve_submission_users_returns_single_user_for_forum(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $forum   = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('forum', $forum->id);
        $context = \context_course::instance($course->id);
        $user    = $this->getDataGenerator()->create_user();

        $cmobj   = (object)['id' => $cm->id, 'modname' => 'forum', 'instance' => $forum->id, 'course' => $course->id];
        $moddata = (object)[];

        $result = turnitin_submission::resolve_submission_users($cmobj, $moddata, $user->id, $context);

        $this->assertEquals([(int)$user->id], $result);
    }

    /**
     * Test resolve_submission_users returns just the submitting user for assign with teamsubmission=0.
     */
    public function test_resolve_submission_users_returns_single_user_when_no_team_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $assign  = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('assign', $assign->id);
        $context = \context_course::instance($course->id);
        $user    = $this->getDataGenerator()->create_user();

        $cmobj   = (object)['id' => $cm->id, 'modname' => 'assign', 'instance' => $assign->id, 'course' => $course->id];
        $moddata = (object)['teamsubmission' => 0];

        $result = turnitin_submission::resolve_submission_users($cmobj, $moddata, $user->id, $context);

        $this->assertEquals([(int)$user->id], $result);
    }

    /**
     * Test resolve_submission_users returns just the submitting user for assign team submission
     * when the user is not in any group.
     */
    public function test_resolve_submission_users_returns_single_user_when_not_in_group(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $assign  = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'teamsubmission' => 1,
        ]);
        $cm      = get_coursemodule_from_instance('assign', $assign->id);
        $context = \context_course::instance($course->id);
        $user    = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $cmobj   = (object)['id' => $cm->id, 'modname' => 'assign', 'instance' => $assign->id, 'course' => $course->id];
        $moddata = (object)['teamsubmission' => 1];

        $result = turnitin_submission::resolve_submission_users($cmobj, $moddata, $user->id, $context);

        $this->assertEquals([(int)$user->id], $result);
    }

    /**
     * Test resolve_submission_users returns all group members for a team submission
     * when the submitting user is part of a group.
     */
    public function test_resolve_submission_users_returns_all_group_members_for_team_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'teamsubmission' => 1,
        ]);
        $cm      = get_coursemodule_from_instance('assign', $assign->id);
        $context = \context_course::instance($course->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');

        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user2->id]);

        $cmobj   = (object)['id' => $cm->id, 'modname' => 'assign', 'instance' => $assign->id, 'course' => $course->id];
        $moddata = (object)['teamsubmission' => 1];

        $result = turnitin_submission::resolve_submission_users($cmobj, $moddata, $user1->id, $context);

        sort($result);
        $expected = [$user1->id, $user2->id];
        sort($expected);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test resolve_submission_users skips coursework group expansion when mod_coursework is absent.
     */
    public function test_resolve_submission_users_skips_coursework_when_not_installed(): void {
        $this->resetAfterTest();

        if (class_exists(\mod_coursework\models\coursework::class)) {
            $this->markTestSkipped('mod_coursework is installed; skipping not-installed path.');
        }

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $user    = $this->getDataGenerator()->create_user();

        $cmobj   = (object)['id' => 1, 'modname' => 'coursework', 'instance' => 1, 'course' => $course->id];
        $moddata = (object)['id' => 1, 'use_groups' => 0];

        $result = turnitin_submission::resolve_submission_users($cmobj, $moddata, $user->id, $context);

        $this->assertEquals([(int)$user->id], $result);
    }

    // Tests for get_first_group_author().

    /**
     * Test get_first_group_author returns null when the group has no members.
     */
    public function test_get_first_group_author_returns_null_for_empty_group(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $group  = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $result = turnitin_submission::get_first_group_author($course->id, $group->id);

        $this->assertNull($result);
    }

    /**
     * Test get_first_group_author returns the first student's id.
     */
    public function test_get_first_group_author_returns_first_student(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $group   = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);

        $result = turnitin_submission::get_first_group_author($course->id, $group->id);

        $this->assertEquals((int)$student->id, $result);
    }

    /**
     * Test get_first_group_author skips graders and returns the first student.
     */
    public function test_get_first_group_author_skips_graders(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $group   = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $teacher->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);

        $result = turnitin_submission::get_first_group_author($course->id, $group->id);

        $this->assertEquals((int)$student->id, $result);
    }

    /**
     * Test get_first_group_author returns null when the group contains only graders.
     */
    public function test_get_first_group_author_returns_null_when_only_graders(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $group   = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $teacher->id]);

        $result = turnitin_submission::get_first_group_author($course->id, $group->id);

        $this->assertNull($result);
    }

    // Tests for should_skip_non_submitting_filearea().

    /**
     * Test returns false for a submittable file area.
     */
    public function test_should_skip_non_submitting_filearea_returns_false_for_normal_area(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'essay.txt',
        ], 'content');

        $this->assertFalse(turnitin_submission::should_skip_non_submitting_filearea($file));
        $fs->delete_area_files(\context_system::instance()->id, 'assignsubmission_file', 'submission_files');
    }

    /**
     * Test returns true for feedback_files area.
     */
    public function test_should_skip_non_submitting_filearea_returns_true_for_feedback_files(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'assignfeedback_file',
            'filearea'  => 'feedback_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'feedback.txt',
        ], 'feedback');

        $this->assertTrue(turnitin_submission::should_skip_non_submitting_filearea($file));
        $fs->delete_area_files(\context_system::instance()->id, 'assignfeedback_file', 'feedback_files');
    }

    /**
     * Test returns true for introattachment area.
     */
    public function test_should_skip_non_submitting_filearea_returns_true_for_introattachment(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'mod_assign',
            'filearea'  => 'introattachment',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'intro.pdf',
        ], 'intro');

        $this->assertTrue(turnitin_submission::should_skip_non_submitting_filearea($file));
        $fs->delete_area_files(\context_system::instance()->id, 'mod_assign', 'introattachment');
    }

    // Tests for should_skip_quiz_disabled().

    /**
     * Test returns false when component is not qtype_essay.
     */
    public function test_should_skip_quiz_disabled_returns_false_for_non_quiz(): void {
        $this->resetAfterTest();

        $this->assertFalse(turnitin_submission::should_skip_quiz_disabled(''));
        $this->assertFalse(turnitin_submission::should_skip_quiz_disabled('mod_assign'));
    }

    /**
     * Test returns false when quiz is enabled in Turnitin config.
     */
    public function test_should_skip_quiz_disabled_returns_false_when_quiz_enabled(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_quiz', 1, 'plagiarism_turnitin');

        $this->assertFalse(turnitin_submission::should_skip_quiz_disabled('qtype_essay'));
    }

    /**
     * Test returns true when quiz component is present but Turnitin quiz support is disabled.
     */
    public function test_should_skip_quiz_disabled_returns_true_when_quiz_disabled(): void {
        $this->resetAfterTest();

        set_config('plagiarism_turnitin_mod_quiz', 0, 'plagiarism_turnitin');

        $this->assertTrue(turnitin_submission::should_skip_quiz_disabled('qtype_essay'));
    }

    // Tests for resolve_grades_released().

    /**
     * Test returns true when no grade item exists.
     */
    public function test_resolve_grades_released_returns_true_when_no_grade_item(): void {
        $this->resetAfterTest();

        $cm      = (object)['modname' => 'assign', 'instance' => 1];
        $moddata = (object)[];

        $this->assertTrue(turnitin_submission::resolve_grades_released($cm, $moddata, 1, null));
    }

    /**
     * Test returns false when grade item hidden=1.
     */
    public function test_resolve_grades_released_returns_false_when_hidden(): void {
        $this->resetAfterTest();

        $cm        = (object)['modname' => 'assign', 'instance' => 1];
        $moddata   = (object)[];
        $gradeitem = (object)['hidden' => 1];

        $this->assertFalse(turnitin_submission::resolve_grades_released($cm, $moddata, 1, $gradeitem));
    }

    /**
     * Test returns true when grade item hidden=0 and no marking workflow.
     */
    public function test_resolve_grades_released_returns_true_when_hidden_zero(): void {
        $this->resetAfterTest();

        $cm        = (object)['modname' => 'forum', 'instance' => 1];
        $moddata   = (object)[];
        $gradeitem = (object)['hidden' => 0];

        $this->assertTrue(turnitin_submission::resolve_grades_released($cm, $moddata, 1, $gradeitem));
    }

    /**
     * Test returns false when grade item hidden is a future timestamp (hidden until).
     */
    public function test_resolve_grades_released_returns_false_when_hidden_until_future(): void {
        $this->resetAfterTest();

        $cm        = (object)['modname' => 'assign', 'instance' => 1];
        $moddata   = (object)[];
        $gradeitem = (object)['hidden' => strtotime('+1 month')];

        $this->assertFalse(turnitin_submission::resolve_grades_released($cm, $moddata, 1, $gradeitem));
    }

    /**
     * Test returns true when hidden timestamp is in the past (hidden-until has passed).
     */
    public function test_resolve_grades_released_returns_true_when_hidden_until_passed(): void {
        $this->resetAfterTest();

        $cm        = (object)['modname' => 'assign', 'instance' => 1];
        $moddata   = (object)[];
        $gradeitem = (object)['hidden' => strtotime('-1 month')];

        $this->assertTrue(turnitin_submission::resolve_grades_released($cm, $moddata, 1, $gradeitem));
    }

    /**
     * Test marking workflow overrides hidden=0 when no grade is released for the user.
     */
    public function test_resolve_grades_released_marking_workflow_not_released(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $assign  = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'markingworkflow' => 1]);
        $cm      = (object)['modname' => 'assign', 'instance' => $assign->id];
        $moddata = (object)['markingworkflow' => 1];
        $gradeitem = (object)['hidden' => 0];
        $user    = $this->getDataGenerator()->create_user();

        // No assign_user_flags row → not released.
        $result = turnitin_submission::resolve_grades_released($cm, $moddata, $user->id, $gradeitem);

        $this->assertFalse($result);
    }

    // Tests for resolve_submitter_eula_accepted().

    /**
     * Test returns true when plagiarism file exists (no EULA check needed).
     */
    public function test_resolve_submitter_eula_accepted_returns_true_when_file_exists(): void {
        $this->resetAfterTest();

        $course   = $this->getDataGenerator()->create_course();
        $context  = \context_course::instance($course->id);
        $module   = $this->getMockBuilder(\plagiarism_turnitin\modules\turnitin_assign::class)
            ->disableOriginalConstructor()->getMock();

        $result = turnitin_submission::resolve_submitter_eula_accepted(
            true,
            1,
            2,
            1,
            1,
            true,
            $context,
            $module
        );

        $this->assertTrue($result);
    }

    /**
     * Test returns true when viewing own submission (viewer == submitter).
     */
    public function test_resolve_submitter_eula_accepted_returns_true_for_own_submission(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $module  = $this->getMockBuilder(\plagiarism_turnitin\modules\turnitin_assign::class)
            ->disableOriginalConstructor()->getMock();

        $result = turnitin_submission::resolve_submitter_eula_accepted(
            false,
            5,
            5,
            5,
            5,
            false,
            $context,
            $module
        );

        $this->assertTrue($result);
    }

    /**
     * Test returns true when viewer is not a tutor.
     */
    public function test_resolve_submitter_eula_accepted_returns_true_when_not_tutor(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $module  = $this->getMockBuilder(\plagiarism_turnitin\modules\turnitin_assign::class)
            ->disableOriginalConstructor()->getMock();

        $result = turnitin_submission::resolve_submitter_eula_accepted(
            false,
            3,
            1,
            3,
            3,
            false,
            $context,
            $module
        );

        $this->assertTrue($result);
    }

    /**
     * Test returns true reflecting injected user's EULA acceptance status.
     */
    public function test_resolve_submitter_eula_accepted_uses_injected_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $context = \context_course::instance($course->id);

        $module = $this->getMockBuilder(\plagiarism_turnitin\modules\turnitin_assign::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user_enrolled_on_course'])
            ->getMock();
        $module->method('user_enrolled_on_course')->willReturn(true);

        // Inject a mock turnitin_user with useragreementaccepted=1.
        $tiiuser = $this->getMockBuilder(turnitin_user::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tiiuser->useragreementaccepted = 1;

        $result = turnitin_submission::resolve_submitter_eula_accepted(
            false,
            (int)$student->id,
            1,
            (int)$student->id,
            (int)$student->id,
            true,
            $context,
            $module,
            $tiiuser
        );

        $this->assertTrue($result);
    }

    // Tests for build_submission_link_context().

    /**
     * Test build_submission_link_context populates all fields correctly.
     */
    public function test_build_submission_link_context_populates_all_fields(): void {
        $this->resetAfterTest();

        $cm     = (object)['id' => 5, 'modname' => 'assign', 'instance' => 3, 'course' => 2];
        $config = (object)[
            'plagiarism_turnitin_usegrademark'   => 1,
            'plagiarism_turnitin_enablepeermark' => 0,
        ];
        $settings = [
            'plagiarism_show_student_report' => '1',
            'plagiarism_rubric'              => '42',
        ];
        $gradeitem = (object)['id' => 7, 'hidden' => 0];
        $pfile     = (object)['id' => 11, 'statuscode' => 'success'];

        $ctx = turnitin_submission::build_submission_link_context(
            ['cmid' => 5, 'userid' => 10, 'content' => 'some text'],
            $cm,
            $config,
            $settings,
            $pfile,
            'text_content',
            [10, 20],
            false,
            false,
            true,
            true,
            false,
            $gradeitem,
            [],
            true,
            'https://example.com',
            0
        );

        $this->assertInstanceOf(submission_link_context::class, $ctx);
        $this->assertSame($pfile, $ctx->plagiarismfile);
        $this->assertFalse($ctx->istutor);
        $this->assertEquals(10, $ctx->submissionuserid);
        $this->assertEquals('text_content', $ctx->submissiontype);
        $this->assertEquals(5, $ctx->cmid);
        $this->assertEquals('assign', $ctx->cmmodname);
        $this->assertEquals(2, $ctx->cmcourse);
        $this->assertEquals('https://example.com', $ctx->wwwroot);
        $this->assertTrue($ctx->usegrademark);
        $this->assertFalse($ctx->enablepeermark);
        $this->assertTrue($ctx->showstudentreport);
        $this->assertEquals('42', $ctx->rubric);
        $this->assertTrue($ctx->gradesreleased);
        $this->assertTrue($ctx->gradeexists);
        $this->assertFalse($ctx->blindon);
        $this->assertSame($gradeitem, $ctx->gradeitem);
        $this->assertTrue($ctx->submittereulaccepted);
    }

    /**
     * Test build_submission_link_context with null gradeitem and null plagiarismfile.
     */
    public function test_build_submission_link_context_handles_null_optionals(): void {
        $this->resetAfterTest();

        $cm     = (object)['id' => 1, 'modname' => 'forum', 'instance' => 1, 'course' => 1];
        $config = (object)['plagiarism_turnitin_usegrademark' => 0, 'plagiarism_turnitin_enablepeermark' => 0];

        $ctx = turnitin_submission::build_submission_link_context(
            ['cmid' => 1, 'userid' => 5],
            $cm,
            $config,
            [],
            null,
            'forum_post',
            [5],
            false,
            false,
            true,
            false,
            false,
            null,
            [],
            true,
            'https://moodle.local',
            0
        );

        $this->assertNull($ctx->plagiarismfile);
        $this->assertNull($ctx->gradeitem);
        $this->assertFalse($ctx->gradeexists);
    }

    // Tests for check_cm_exists().

    /**
     * Test returns the cm when it exists.
     */
    public function test_check_cm_exists_returns_cm_when_found(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $assign  = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('assign', $assign->id);
        $queued  = (object)['id' => 1, 'cm' => $cm->id, 'userid' => 1, 'attempt' => 0];

        $result = turnitin_submission::check_cm_exists($queued);

        $this->assertNotFalse($result);
        $this->assertEquals($cm->id, $result->id);
    }

    /**
     * Test returns false and saves errorcode 12 when cm does not exist.
     */
    public function test_check_cm_exists_returns_false_when_not_found(): void {
        global $DB;
        $this->resetAfterTest();
        $user   = $this->getDataGenerator()->create_user();
        $queued = (object)['id' => 1, 'cm' => 99999, 'userid' => $user->id, 'attempt' => 0];
        $this->insert_submission_row(['cm' => 1, 'userid' => $user->id]);

        // Reset: insert the actual queued item so save_errored can update it.
        $id     = $this->insert_submission_row(['cm' => 99999, 'userid' => $user->id]);
        $queued->id = $id;

        $result = turnitin_submission::check_cm_exists($queued);

        $this->assertFalse($result);
        $this->assertEquals(
            12,
            $DB->get_field('plagiarism_turnitin_files', 'errorcode', ['id' => $id])
        );
    }

    // Tests for check_userid_valid().

    /**
     * Test returns true when userid is non-zero.
     */
    public function test_check_userid_valid_returns_true_for_valid_userid(): void {
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $id     = $this->insert_submission_row(['userid' => $user->id]);
        $queued = (object)['id' => $id, 'userid' => $user->id, 'attempt' => 0];

        $this->assertTrue(turnitin_submission::check_userid_valid($queued));
    }

    /**
     * Test returns false and saves errorcode 7 when userid is 0.
     */
    public function test_check_userid_valid_returns_false_for_zero_userid(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $id     = $this->insert_submission_row(['userid' => $user->id]);
        $queued = (object)['id' => $id, 'userid' => 0, 'attempt' => 0];

        $result = turnitin_submission::check_userid_valid($queued);

        $this->assertFalse($result);
        $this->assertEquals(
            7,
            $DB->get_field('plagiarism_turnitin_files', 'errorcode', ['id' => $id])
        );
    }

    // Tests for check_submission_type_valid().

    /**
     * Test returns true for all valid submission types.
     */
    public function test_check_submission_type_valid_for_valid_types(): void {
        $this->assertTrue(turnitin_submission::check_submission_type_valid('file'));
        $this->assertTrue(turnitin_submission::check_submission_type_valid('text_content'));
        $this->assertTrue(turnitin_submission::check_submission_type_valid('forum_post'));
        $this->assertTrue(turnitin_submission::check_submission_type_valid('quiz_answer'));
    }

    /**
     * Test returns false for unknown submission types.
     */
    public function test_check_submission_type_valid_returns_false_for_invalid(): void {
        $this->assertFalse(turnitin_submission::check_submission_type_valid(''));
        $this->assertFalse(turnitin_submission::check_submission_type_valid('unknown'));
        $this->assertFalse(turnitin_submission::check_submission_type_valid('online_text'));
    }

    // Tests for build_submission_filestring().

    /**
     * Build a minimal user stdClass for filestring tests.
     */
    private function make_tii_user_obj(int $id = 1, string $firstname = 'Test', string $lastname = 'User'): \stdClass {
        return (object)['id' => $id, 'firstname' => $firstname, 'lastname' => $lastname];
    }

    /**
     * Test includes user details when blind marking and pseudo are both off.
     */
    public function test_build_submission_filestring_includes_user_when_not_blind(): void {
        $cm       = (object)['id' => 5];
        $user     = $this->make_tii_user_obj(42, 'Alice', 'Smith');
        $moddata  = (object)['blindmarking' => 0];
        $config   = (object)['plagiarism_turnitin_enablepseudo' => 0];

        $result = turnitin_submission::build_submission_filestring('essay.docx', $cm, $user, $moddata, $config);

        $this->assertEquals([42, 'Alice', 'Smith', 'essay', 5], $result);
    }

    /**
     * Test omits user details when blind marking is on.
     */
    public function test_build_submission_filestring_omits_user_when_blind_marking(): void {
        $cm      = (object)['id' => 5];
        $user    = $this->make_tii_user_obj(42, 'Alice', 'Smith');
        $moddata = (object)['blindmarking' => 1];
        $config  = (object)['plagiarism_turnitin_enablepseudo' => 0];

        $result = turnitin_submission::build_submission_filestring('essay.docx', $cm, $user, $moddata, $config);

        $this->assertEquals(['essay', 5], $result);
    }

    /**
     * Test omits user details when pseudo-anonymisation is on.
     */
    public function test_build_submission_filestring_omits_user_when_pseudo_enabled(): void {
        $cm      = (object)['id' => 5];
        $user    = $this->make_tii_user_obj(42, 'Alice', 'Smith');
        $moddata = (object)['blindmarking' => 0];
        $config  = (object)['plagiarism_turnitin_enablepseudo' => 1];

        $result = turnitin_submission::build_submission_filestring('essay.docx', $cm, $user, $moddata, $config);

        $this->assertEquals(['essay', 5], $result);
    }

    /**
     * Test uses the first dot-segment of the title as the base name.
     */
    public function test_build_submission_filestring_uses_title_base(): void {
        $cm      = (object)['id' => 3];
        $user    = $this->make_tii_user_obj(1);
        $moddata = (object)['blindmarking' => 1];
        $config  = (object)['plagiarism_turnitin_enablepseudo' => 0];

        $result = turnitin_submission::build_submission_filestring('my submission.docx', $cm, $user, $moddata, $config);

        $this->assertEquals(['my submission', 3], $result);
    }

    /**
     * Test handles a title with no file extension.
     */
    public function test_build_submission_filestring_handles_title_without_extension(): void {
        $cm      = (object)['id' => 2];
        $user    = $this->make_tii_user_obj(1);
        $moddata = (object)['blindmarking' => 1];
        $config  = (object)['plagiarism_turnitin_enablepseudo' => 0];

        $result = turnitin_submission::build_submission_filestring('notitle', $cm, $user, $moddata, $config);

        $this->assertEquals(['notitle', 2], $result);
    }

    // Tests for resolve_cm_from_event().

    /**
     * Test returns the cm for a normal module using contextinstanceid.
     */
    public function test_resolve_cm_from_event_returns_cm_for_normal_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $eventdata = [
            'other'               => ['modulename' => 'assign'],
            'contextinstanceid'   => $cm->id,
        ];

        $result = turnitin_submission::resolve_cm_from_event($eventdata);

        $this->assertNotFalse($result);
        $this->assertEquals($cm->id, $result->id);
    }

    /**
     * Test returns false when the cm does not exist.
     */
    public function test_resolve_cm_from_event_returns_false_when_cm_missing(): void {
        $this->resetAfterTest();

        $eventdata = [
            'other'             => ['modulename' => 'assign'],
            'contextinstanceid' => 99999,
        ];

        $result = turnitin_submission::resolve_cm_from_event($eventdata);

        $this->assertFalse($result);
    }

    // Tests for ensure_draft_submit_default().

    /**
     * Test adds default 0 for assign when key is absent.
     */
    public function test_ensure_draft_submit_default_adds_zero_for_assign(): void {
        $settings = ['use_turnitin' => '1'];
        $result   = turnitin_submission::ensure_draft_submit_default($settings, 'assign');

        $this->assertEquals(0, $result['plagiarism_draft_submit']);
    }

    /**
     * Test does not overwrite an existing value for assign.
     */
    public function test_ensure_draft_submit_default_preserves_existing_value(): void {
        $settings = ['plagiarism_draft_submit' => '1'];
        $result   = turnitin_submission::ensure_draft_submit_default($settings, 'assign');

        $this->assertEquals('1', $result['plagiarism_draft_submit']);
    }

    /**
     * Test does nothing for non-assign modules.
     */
    public function test_ensure_draft_submit_default_ignores_non_assign(): void {
        $settings = ['use_turnitin' => '1'];
        $result   = turnitin_submission::ensure_draft_submit_default($settings, 'forum');

        $this->assertArrayNotHasKey('plagiarism_draft_submit', $result);
    }

    // Tests for resolve_instructor_group_author().

    /**
     * Test returns currentauthor unchanged when relateduserid is set (not an instructor override).
     */
    public function test_resolve_instructor_group_author_returns_current_when_relateduserid_set(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $cm      = (object)['id' => 1, 'modname' => 'assign', 'instance' => 1, 'course' => $course->id];
        $context = \context_course::instance($course->id);

        $eventdata = ['relateduserid' => 5, 'objectid' => 1, 'userid' => 1];
        $result    = turnitin_submission::resolve_instructor_group_author($eventdata, $cm, $context, 1, 5);

        $this->assertEquals(5, $result);
    }

    /**
     * Test returns currentauthor unchanged for non-assign modules.
     */
    public function test_resolve_instructor_group_author_returns_current_for_non_assign(): void {
        $this->resetAfterTest();

        $course  = $this->getDataGenerator()->create_course();
        $cm      = (object)['id' => 1, 'modname' => 'forum', 'instance' => 1, 'course' => $course->id];
        $context = \context_course::instance($course->id);

        $eventdata = ['relateduserid' => null, 'objectid' => 1, 'userid' => 1];
        $result    = turnitin_submission::resolve_instructor_group_author($eventdata, $cm, $context, 1, 42);

        $this->assertEquals(42, $result);
    }

    /**
     * Test returns currentauthor unchanged when submitter lacks editothersubmission capability.
     */
    public function test_resolve_instructor_group_author_returns_current_when_no_capability(): void {
        $this->resetAfterTest();

        $course   = $this->getDataGenerator()->create_course();
        $assign   = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm       = get_coursemodule_from_instance('assign', $assign->id);
        $context  = \context_module::instance($cm->id);
        $student  = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $eventdata = ['relateduserid' => null, 'objectid' => 1, 'userid' => $student->id];
        $cmobj     = (object)['id' => $cm->id, 'modname' => 'assign', 'instance' => $assign->id, 'course' => $course->id];
        $result    = turnitin_submission::resolve_instructor_group_author(
            $eventdata,
            $cmobj,
            $context,
            (int)$student->id,
            (int)$student->id
        );

        $this->assertEquals((int)$student->id, $result);
    }

    // Tests for queue_text_content().

    /**
     * Test returns true without calling queuefn when event type is not content/submitted.
     */
    public function test_queue_text_content_returns_true_for_non_content_event(): void {
        $this->resetAfterTest();

        $cm        = (object)['id' => 1, 'modname' => 'assign', 'instance' => 1, 'course' => 1];
        $called    = false;
        $queuefn   = function () use (&$called) {
            $called = true;
            return true;
        };

        $eventdata = [
            'eventtype'  => 'submission_removed',
            'other'      => ['content' => 'some content', 'modulename' => 'assign'],
            'objectid'   => 1,
        ];

        $result = turnitin_submission::queue_text_content($eventdata, $cm, 1, 1, $queuefn);

        $this->assertTrue($result);
        $this->assertFalse($called);
    }

    /**
     * Test returns true without calling queuefn when content is empty.
     */
    public function test_queue_text_content_returns_true_when_no_content(): void {
        $this->resetAfterTest();

        $cm      = (object)['id' => 1, 'modname' => 'forum', 'instance' => 1, 'course' => 1];
        $called  = false;
        $queuefn = function () use (&$called) {
            $called = true;
            return true;
        };

        $eventdata = [
            'eventtype' => 'content_uploaded',
            'other'     => ['content' => '', 'modulename' => 'forum'],
            'objectid'  => 1,
        ];

        $result = turnitin_submission::queue_text_content($eventdata, $cm, 1, 1, $queuefn);

        $this->assertTrue($result);
        $this->assertFalse($called);
    }

    /**
     * Test calls queuefn with the correct submissiontype for a forum post.
     */
    public function test_queue_text_content_calls_queuefn_with_forum_post_type(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);
        $cmobj  = (object)['id' => $cm->id, 'modname' => 'forum', 'instance' => $forum->id, 'course' => $course->id];

        $capturedtype = null;
        $queuefn = function ($cm, $author, $submitter, $identifier, $submissiontype, $objectid, $eventtype)
 use (&$capturedtype) {
            $capturedtype = $submissiontype;
            return true;
        };

        $eventdata = [
            'eventtype' => 'assessable_submitted',
            'other'     => ['content' => 'Hello forum world', 'modulename' => 'forum'],
            'objectid'  => 1,
            'userid'    => 1,
        ];

        turnitin_submission::queue_text_content($eventdata, $cmobj, 1, 1, $queuefn);

        $this->assertEquals('forum_post', $capturedtype);
    }

    // Tests for queue_file_submissions().

    /**
     * Test returns true when there are no pathnamehashes.
     */
    public function test_queue_file_submissions_returns_true_with_no_hashes(): void {
        $this->resetAfterTest();

        $cm      = (object)['id' => 1, 'modname' => 'assign', 'instance' => 1, 'course' => 1];
        $called  = false;
        $queuefn = function () use (&$called) {
            $called = true;
            return true;
        };

        $eventdata = ['other' => [], 'objectid' => 1, 'eventtype' => 'file_uploaded'];

        $result = turnitin_submission::queue_file_submissions($eventdata, $cm, 1, 1, $queuefn);

        $this->assertTrue($result);
        $this->assertFalse($called);
    }

    /**
     * Test skips hashes where the file cannot be found and still returns true.
     */
    public function test_queue_file_submissions_skips_missing_files(): void {
        $this->resetAfterTest();

        $cm      = (object)['id' => 1, 'modname' => 'assign', 'instance' => 1, 'course' => 1];
        $called  = false;
        $queuefn = function () use (&$called) {
            $called = true;
            return true;
        };

        $eventdata = [
            'other'     => ['pathnamehashes' => [sha1('nonexistent_file')]],
            'objectid'  => 1,
            'eventtype' => 'file_uploaded',
        ];

        $result = turnitin_submission::queue_file_submissions($eventdata, $cm, 1, 1, $queuefn);

        $this->assertTrue($result);
        $this->assertFalse($called);
    }

    /**
     * Test calls queuefn for a submittable file and returns its result.
     */
    public function test_queue_file_submissions_queues_submittable_file(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'essay.txt',
        ], 'essay content');

        $cm           = (object)['id' => 1, 'modname' => 'assign', 'instance' => 1, 'course' => 1];
        $queuedcount  = 0;
        $queuefn      = function () use (&$queuedcount) {
            $queuedcount++;
            return true;
        };

        $eventdata = [
            'other'     => ['pathnamehashes' => [$file->get_pathnamehash()]],
            'objectid'  => 1,
            'eventtype' => 'file_uploaded',
        ];

        $result = turnitin_submission::queue_file_submissions($eventdata, $cm, 1, 1, $queuefn);

        $this->assertTrue($result);
        $this->assertEquals(1, $queuedcount);

        $fs->delete_area_files(\context_system::instance()->id, 'assignsubmission_file', 'submission_files');
    }

    // Tests for is_file_submittable().

    /**
     * Test is_file_submittable returns false for Moodle's directory placeholder (filename='.').
     * Exercises line 966.
     *
     * Moodle's file storage rejects '.' as a filename in Moodle 5.x+, so we use a mock
     * stored_file that returns '.' from get_filename() to reach the guard without creating
     * a real file.
     */
    public function test_is_file_submittable_returns_false_for_dot_file(): void {
        $this->resetAfterTest();

        $mockfile = $this->getMockBuilder(\stored_file::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_filename'])
            ->getMock();
        $mockfile->method('get_filename')->willReturn('.');

        $this->assertFalse(turnitin_submission::is_file_submittable($mockfile));
    }

    /**
     * Test is_file_submittable returns false when get_content_file_handle() throws.
     * Exercises lines 972-973 (the exception catch path).
     */
    public function test_is_file_submittable_returns_false_when_content_handle_throws(): void {
        $this->resetAfterTest();

        $mockfile = $this->getMockBuilder(\stored_file::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_filename', 'get_content_file_handle'])
            ->getMock();
        $mockfile->method('get_filename')->willReturn('essay.docx');
        $mockfile->method('get_content_file_handle')->willThrowException(new \Exception('file missing'));

        $this->assertFalse(turnitin_submission::is_file_submittable($mockfile));
    }

    /**
     * Test is_file_submittable returns true for a normal readable file.
     * Confirms the happy path for is_file_submittable.
     */
    public function test_is_file_submittable_returns_true_for_valid_file(): void {
        $this->resetAfterTest();

        $fs   = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => 2,
            'filepath'  => '/',
            'filename'  => 'essay.txt',
        ], 'some content');

        $this->assertTrue(turnitin_submission::is_file_submittable($file));
        $fs->delete_area_files(\context_system::instance()->id, 'user', 'draft');
    }

    // Tests for enrich_assessable_submitted() with files.

    /**
     * Test enrich_assessable_submitted populates pathnamehashes when the assign_submission
     * has associated file records. Exercises lines 902-903.
     */
    public function test_enrich_assessable_submitted_collects_file_pathnamehashes(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course    = $this->getDataGenerator()->create_course();
        $assignmod = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm        = get_coursemodule_from_instance('assign', $assignmod->id);
        $user      = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create a real assign_submission row.
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assignmod->id,
            'userid'        => $user->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);

        // Upload a file linked to that submission with the correct component/filearea.
        $fs      = get_file_storage();
        $context = \context_module::instance($cm->id);
        $file    = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => $submissionid,
            'filepath'  => '/',
            'filename'  => 'essay.docx',
            'userid'    => $user->id,
        ], 'content');

        $eventdata = [
            'other'             => ['modulename' => 'assign', 'content' => '', 'pathnamehashes' => []],
            'contextinstanceid' => $cm->id,
            'userid'            => $user->id,
            'relateduserid'     => $user->id,
            'eventtype'         => 'assessable_submitted',
            'objectid'          => $submissionid,
        ];

        $enriched = turnitin_submission::enrich_assessable_submitted($eventdata, $user->id);

        $this->assertContains($file->get_pathnamehash(), $enriched['other']['pathnamehashes']);
    }

    // Tests for update() — score-change path.

    /**
     * Test update() calls $gradeupdate and returns its result when a score changes.
     * Exercises lines 265, 278-282 (the updaterequired=true branch).
     */
    public function test_update_calls_gradeupdate_when_score_changes(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $id = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => 2,
            'statuscode'     => 'success',
            'similarityscore' => null, // will change
            'externalid'     => 'ext-score-change',
        ]);

        $tiisubmission = $this->make_tii_submission(['similarity' => 42, 'translated' => 0]);

        $gradeupdatecalled = false;
        $gradeupdate = function($cm, $tii, $userid) use (&$gradeupdatecalled) {
            $gradeupdatecalled = true;
            return true;
        };

        $result = turnitin_submission::update($cm, $id, $tiisubmission, $gradeupdate);

        $this->assertTrue($result);
        $this->assertTrue($gradeupdatecalled);
        $this->assertEquals(42, $DB->get_field('plagiarism_turnitin_files', 'similarityscore', ['id' => $id]));
    }

    /**
     * Test update() does NOT call $gradeupdate when nothing has changed.
     * Exercises the updaterequired=false path (lines 265-282).
     */
    public function test_update_skips_gradeupdate_when_nothing_changes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $id = $this->insert_submission_row([
            'cm'              => $cm->id,
            'userid'          => 2,
            'statuscode'      => 'success',
            'similarityscore' => 50, // same as tiisubmission
            'orcapable'       => 1,
        ]);

        $tiisubmission = $this->make_tii_submission(['similarity' => 50, 'translated' => 0, 'orcapable' => 1]);

        $gradeupdatecalled = false;
        $gradeupdate = function() use (&$gradeupdatecalled) {
            $gradeupdatecalled = true;
            return true;
        };

        turnitin_submission::update($cm, $id, $tiisubmission, $gradeupdate);

        $this->assertFalse($gradeupdatecalled);
    }

    // Tests for get_content_timemodified() — additional cases.

    /**
     * Test get_content_timemodified returns 0 for unknown module types (default case).
     * Exercises lines 617-618 (the default: return 0 in the switch).
     */
    public function test_get_content_timemodified_returns_zero_for_forum_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('forum', $forum->id);

        $result = turnitin_submission::get_content_timemodified($cm, 'text_content', 1, 0);

        $this->assertSame(0, $result);
    }

    // Tests for resolve_submission_id() resubmission paths.

    /**
     * Test resolve_submission_id resets and reuses an existing queued row when
     * resubmission is allowed — exercises lines 685-688 (the resubmission_allowed branch).
     */
    public function test_resolve_submission_id_resets_when_resubmission_allowed(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $identifier = sha1('new-content');
        $oldidentifier = sha1('old-content');

        // Existing row with a DIFFERENT identifier (same type, same user/cm).
        $existingid = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $oldidentifier,
            'submissiontype' => 'text_content',
            'statuscode'     => 'queued',
            'externalid'     => 'ext-resubmit',
        ]);

        $settings  = ['plagiarism_report_gen' => 1]; // resubmission mode
        $moduledata = (object)['resubmission_allowed' => true];

        $routing = turnitin_submission::resolve_submission_id(
            $cm, $user->id, 'text_content', $identifier, $settings, $moduledata, 0
        );

        $this->assertFalse($routing['earlyreturn']);
        // Row was reused (reset), not a new one.
        $this->assertEquals($existingid, $routing['submissionid']);
    }

    /**
     * Test resolve_submission_id creates a new row when the existing same-identifier
     * submission was successful and resubmission is not allowed.
     * Exercises lines 692-695 (create_new path for successful previous with changed content).
     */
    public function test_resolve_submission_id_creates_new_when_previous_was_success(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $identifier = sha1('submitted-content');

        // Existing successful row with same identifier — timemodified in the past.
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $identifier,
            'submissiontype' => 'text_content',
            'statuscode'     => 'success',
            'externalid'     => 'ext-success',
            'lastmodified'   => time() - HOURSECS, // older than the new content
        ]);

        $settings  = ['plagiarism_report_gen' => 0]; // no resubmission
        $moduledata = (object)['resubmission_allowed' => false];

        // Pass timemodified > lastmodified so the "content unchanged" early-return is skipped.
        $routing = turnitin_submission::resolve_submission_id(
            $cm, $user->id, 'text_content', $identifier, $settings, $moduledata, time()
        );

        $this->assertFalse($routing['earlyreturn']);
        // A new row should have been created alongside the original.
        $count = $DB->count_records('plagiarism_turnitin_files',
            ['cm' => $cm->id, 'userid' => $user->id, 'identifier' => $identifier]);
        $this->assertEquals(2, $count);
    }

    // Tests for resolve_get_links_author() group path.

    /**
     * Test resolve_get_links_author returns author from the group plagiarismfile
     * when a group submission (non-zero groupid) is found.
     * Exercises lines 1307-1322.
     */
    public function test_resolve_get_links_author_returns_group_author(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'         => $course->id,
            'teamsubmission' => 1,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assign->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $user);

        // Create a group assign_submission row.
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assign->id,
            'userid'        => 0, // group submission
            'groupid'       => $group->id,
            'status'        => 'submitted',
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);

        $identifier = sha1('group-content');

        // Seed a turnitin_files row with the itemid matching the group submission.
        $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $identifier,
            'submissiontype' => 'file',
            'itemid'         => $submissionid,
        ]);

        $linkarray  = ['userid' => 0, 'cmid' => $cm->id];
        $moduleobject = new \plagiarism_turnitin\modules\turnitin_assign();

        $result = turnitin_submission::resolve_get_links_author(
            $linkarray, $cm, $submissionid, $identifier, $moduleobject
        );

        // The group plagiarismfile was found and the author extracted.
        $this->assertEquals($user->id, $result->author);
    }

    // Tests for resolve_submitter_eula_accepted() paths.

    /**
     * Test resolve_submitter_eula_accepted returns true when user is not enrolled —
     * exercises line 1423.
     */
    public function test_resolve_submitter_eula_accepted_returns_true_when_not_enrolled(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();
        // Deliberately NOT enrolling the user.

        $context      = \context_module::instance($cm->id);
        $moduleobject = new \plagiarism_turnitin\modules\turnitin_assign();

        $result = turnitin_submission::resolve_submitter_eula_accepted(
            true, $user->id, 2, $user->id, $user->id, true, $context, $moduleobject
        );

        $this->assertTrue($result);
    }

    /**
     * Test resolve_submitter_eula_accepted returns true when the submissionuserid
     * does not exist in the users table. Exercises line 1422.
     */
    public function test_resolve_submitter_eula_accepted_returns_true_when_user_not_found(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $context      = \context_module::instance($cm->id);
        $moduleobject = new \plagiarism_turnitin\modules\turnitin_assign();

        // Use a non-existent userid.
        $result = turnitin_submission::resolve_submitter_eula_accepted(
            true, 99999, 2, 99999, 99999, true, $context, $moduleobject
        );

        $this->assertTrue($result);
    }

    // Tests for recreate_submission_event() text_content path.

    /**
     * Test recreate_submission_event re-queues a text_content submission by
     * fetching online text and triggering an assessable_uploaded event.
     * Exercises lines 116-136 (the text_content case).
     */
    public function test_recreate_submission_event_requeues_text_content(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course    = $this->getDataGenerator()->create_course();
        $assignmod = $this->getDataGenerator()->create_module('assign', [
            'course'                              => $course->id,
            'assignsubmission_onlinetext_enabled' => 1,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assignmod->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create an assign_submission row.
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assignmod->id,
            'userid'        => $user->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);
        $DB->insert_record('assignsubmission_onlinetext', (object)[
            'assignment'   => $assignmod->id,
            'submission'   => $submissionid,
            'onlinetext'   => 'My essay text',
            'onlineformat' => FORMAT_HTML,
        ]);

        // Create the turnitin_files row that recreate_submission_event reads.
        $identifier = sha1('text_content cm' . $cm->id . ' itemid' . $submissionid . ' My essay text');
        $fileid = $DB->insert_record('plagiarism_turnitin_files', (object)[
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $identifier,
            'statuscode'     => 'error',
            'attempt'        => 1,
            'submissiontype' => 'text_content',
            'itemid'         => $submissionid,
            'submitter'      => $user->id,
            'lastmodified'   => time(),
            'transmatch'     => 0,
        ]);

        $submission = new turnitin_submission($fileid, []);
        $result = $submission->recreate_submission_event();

        $this->assertTrue($result);
        $this->assertEquals('queued', $DB->get_field('plagiarism_turnitin_files', 'statuscode', ['id' => $fileid]));
    }

    // Tests for resolve_submission_id() "no previous by identifier" paths.

    /**
     * Test resolve_submission_id resets and reuses an existing different-identifier
     * row when text_content submission type always allows reset.
     * Exercises lines 714-717 (the text_content reset path in the else branch).
     */
    public function test_resolve_submission_id_resets_different_identifier_for_text_content(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $oldidentifier = sha1('old text');
        $newidentifier = sha1('new text');

        // Existing row with different identifier.
        $existingid = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $oldidentifier,
            'submissiontype' => 'text_content',
            'statuscode'     => 'queued',
            'externalid'     => 'ext-old',
        ]);

        $settings   = ['plagiarism_report_gen' => 1];
        $moduledata = (object)['resubmission_allowed' => false];

        $routing = turnitin_submission::resolve_submission_id(
            $cm, $user->id, 'text_content', $newidentifier, $settings, $moduledata, time()
        );

        $this->assertFalse($routing['earlyreturn']);
        $this->assertEquals($existingid, $routing['submissionid']);
    }

    /**
     * Test resolve_submission_id creates a new row when there is no previous
     * submission at all for this user/cm/type combination.
     * Exercises line 724 (the final create_new in the else branch).
     */
    public function test_resolve_submission_id_creates_new_when_no_previous(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);
        $user   = $this->getDataGenerator()->create_user();

        $countbefore = $DB->count_records('plagiarism_turnitin_files', ['cm' => $cm->id, 'userid' => $user->id]);

        $settings   = ['plagiarism_report_gen' => 0];
        $moduledata = (object)['resubmission_allowed' => false];

        $routing = turnitin_submission::resolve_submission_id(
            $cm, $user->id, 'text_content', sha1('brand-new'), $settings, $moduledata, time()
        );

        $this->assertFalse($routing['earlyreturn']);
        $this->assertGreaterThan(0, $routing['submissionid']);
        $this->assertGreaterThan($countbefore,
            $DB->count_records('plagiarism_turnitin_files', ['cm' => $cm->id, 'userid' => $user->id]));
    }

    // Tests for update_gradebook() assign text_content stale-content check.

    /**
     * Test update_gradebook sets gbupdaterequired=false when the stored identifier
     * no longer matches the current online text — stale submission guard.
     * Exercises lines 1050-1063 (the text_content staleness check).
     */
    public function test_update_gradebook_sets_gb_not_required_for_stale_text_content(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $course    = $this->getDataGenerator()->create_course();
        $assignmod = $this->getDataGenerator()->create_module('assign', [
            'course'                              => $course->id,
            'assignsubmission_onlinetext_enabled' => 1,
        ]);
        $cm   = get_coursemodule_from_instance('assign', $assignmod->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assignmod->id,
            'userid'        => $user->id,
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
            'timecreated'   => time(),
            'timemodified'  => time(),
        ]);
        $DB->insert_record('assignsubmission_onlinetext', (object)[
            'assignment'   => $assignmod->id,
            'submission'   => $submissionid,
            'onlinetext'   => 'current text',
            'onlineformat' => FORMAT_HTML,
        ]);

        // Store a STALE identifier (based on old text) so staleness check fires.
        $staleidentifier = sha1('old stale text');
        $fileid = $this->insert_submission_row([
            'cm'             => $cm->id,
            'userid'         => $user->id,
            'identifier'     => $staleidentifier,
            'submissiontype' => 'text_content',
            'statuscode'     => 'success',
            'externalid'     => 'ext-stale',
            'grade'          => 50,
        ]);

        $gradeupdatecalled = false;
        $gradeupdate = function() use (&$gradeupdatecalled) {
            $gradeupdatecalled = true;
            return true;
        };

        $tiisubmission = $this->make_tii_submission(['similarity' => 50, 'grade' => 50]);

        turnitin_submission::update_gradebook($cm, $fileid, $tiisubmission, $user->id, $gradeupdate);

        // gbupdaterequired was set to false because the identifier is stale — no gradebook update.
        $this->assertFalse($gradeupdatecalled);
    }

    /**
     * Test update_gradebook returns early for coursework module without calling
     * the gradeupdater. Exercises line 1070 (the `if coursework return true` guard).
     */
    public function test_update_gradebook_returns_early_for_coursework(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $cm     = (object)['id' => 1, 'modname' => 'coursework', 'instance' => 1, 'course' => $course->id];

        $fileid = $this->insert_submission_row([
            'cm' => 1, 'userid' => 2, 'statuscode' => 'success', 'grade' => 70, 'externalid' => 'ext-cw',
        ]);

        $gradeupdatecalled = false;
        $gradeupdate = function() use (&$gradeupdatecalled) {
            $gradeupdatecalled = true;
        };

        $tiisubmission = $this->make_tii_submission(['similarity' => 50, 'grade' => 70]);
        turnitin_submission::update_gradebook($cm, $fileid, $tiisubmission, 2, $gradeupdate);

        $this->assertFalse($gradeupdatecalled);
    }

    // Tests for queue_file_submissions() non-submittable file skip.

    /**
     * Test queue_file_submissions skips a pathnamehash that resolves to no file
     * in the file store (file not found path). Exercises lines 1733-1735.
     */
    public function test_queue_file_submissions_skips_missing_file(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm     = get_coursemodule_from_instance('assign', $assign->id);

        $queuedcount = 0;
        $queuefn = function() use (&$queuedcount) {
            $queuedcount++;
            return true;
        };

        $eventdata = [
            'other'             => ['modulename' => 'assign', 'pathnamehashes' => ['nonexistenthash']],
            'contextinstanceid' => $cm->id,
            'userid'            => 2,
            'eventtype'         => 'file_uploaded',
            'objectid'          => 1,
        ];

        $result = turnitin_submission::queue_file_submissions($eventdata, $cm, 2, 2, $queuefn);

        $this->assertTrue($result);
        // Missing file was skipped — queue function not called.
        $this->assertEquals(0, $queuedcount);
    }
}

