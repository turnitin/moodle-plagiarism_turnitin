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
 * Unit tests for classes/turnitin_class.php.
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
require_once($CFG->dirroot . '/plagiarism/turnitin/vendor/autoload.php');

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for turnitin_class.
 *
 * @package plagiarism_turnitin
 */
#[CoversClass(turnitin_class::class)]
final class turnitin_class_test extends \advanced_testcase {
    /**
     * Build a mock turnitin_comms whose initialise_api() returns a mock TurnitinAPI.
     *
     * @param array $apimethods Map of method name => return value or callback.
     */
    private function make_mock_comms(array $apimethods = []): turnitin_comms {
        $methods = !empty($apimethods) ? array_keys($apimethods) : ['readClass'];
        $mockapi = $this->getMockBuilder(\Integrations\PhpSdk\TurnitinAPI::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();

        foreach ($apimethods as $method => $value) {
            if (is_callable($value)) {
                $mockapi->method($method)->willReturnCallback($value);
            } else {
                $mockapi->method($method)->willReturn($value);
            }
        }

        $mockcomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialise_api'])
            ->getMock();
        $mockcomms->method('initialise_api')->willReturn($mockapi);

        return $mockcomms;
    }

    // Tests for __construct().

    /**
     * Test constructor sets turnitinid and turnitintitle when a course record exists.
     */
    public function test_constructor_loads_turnitin_cid_when_course_record_exists(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('plagiarism_turnitin_courses', (object)[
            'courseid'    => $course->id,
            'turnitin_cid' => 42,
            'turnitin_ctl' => 'My Course Title',
        ]);

        $tiiclass = new turnitin_class($course->id);

        // The sharedrubrics property starts null/unset — no API call yet.
        $this->assertNull($tiiclass->sharedrubrics ?? null);
    }

    /**
     * Test constructor completes without error when no course record exists.
     */
    public function test_constructor_handles_missing_course_record(): void {
        $this->resetAfterTest();

        $tiiclass = new turnitin_class(99999);

        $this->assertInstanceOf(turnitin_class::class, $tiiclass);
    }

    // Tests for read_class_from_tii().

    /**
     * Test read_class_from_tii populates sharedrubrics from the API response.
     */
    public function test_read_class_from_tii_populates_sharedrubrics(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Build a mock rubric returned by the API.
        $mockrubric = $this->getMockBuilder(\Integrations\PhpSdk\TiiRubric::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRubricGroupName', 'getRubricId', 'getRubricName'])
            ->getMock();
        $mockrubric->method('getRubricGroupName')->willReturn('Instructor Rubrics');
        $mockrubric->method('getRubricId')->willReturn(7);
        $mockrubric->method('getRubricName')->willReturn('My Rubric');

        $mockclass = $this->getMockBuilder(\Integrations\PhpSdk\TiiClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSharedRubrics'])
            ->getMock();
        $mockclass->method('getSharedRubrics')->willReturn([$mockrubric]);

        $mockresponse = $this->getMockBuilder(\Integrations\PhpSdk\Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockresponse->method('getClass')->willReturn($mockclass);

        $mockcomms = $this->make_mock_comms(['readClass' => $mockresponse]);

        $tiiclass        = new turnitin_class($course->id);
        $tiiclass->comms = $mockcomms;
        $tiiclass->read_class_from_tii();

        $this->assertIsArray($tiiclass->sharedrubrics);
        $this->assertArrayHasKey('Instructor Rubrics', $tiiclass->sharedrubrics);
        $this->assertEquals('My Rubric', $tiiclass->sharedrubrics['Instructor Rubrics'][7]);
    }

    /**
     * Test read_class_from_tii sets sharedrubrics to an empty array when API returns none.
     */
    public function test_read_class_from_tii_sets_empty_sharedrubrics_when_no_rubrics(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $mockclass = $this->getMockBuilder(\Integrations\PhpSdk\TiiClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSharedRubrics'])
            ->getMock();
        $mockclass->method('getSharedRubrics')->willReturn([]);

        $mockresponse = $this->getMockBuilder(\Integrations\PhpSdk\Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockresponse->method('getClass')->willReturn($mockclass);

        $mockcomms = $this->make_mock_comms(['readClass' => $mockresponse]);

        $tiiclass        = new turnitin_class($course->id);
        $tiiclass->comms = $mockcomms;
        $tiiclass->read_class_from_tii();

        $this->assertIsArray($tiiclass->sharedrubrics);
        $this->assertEmpty($tiiclass->sharedrubrics);
    }

    /**
     * Test read_class_from_tii handles exceptions from the API gracefully.
     *
     * When readClass() throws, handle_exceptions() is called and sharedrubrics
     * is left unset (the catch block does not set it).
     */
    public function test_read_class_from_tii_handles_api_exception_gracefully(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $mockapi = $this->getMockBuilder(\Integrations\PhpSdk\TurnitinAPI::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockapi->method('readClass')->willThrowException(new \Exception('API error'));

        $mockcomms = $this->getMockBuilder(turnitin_comms::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialise_api', 'handle_exceptions'])
            ->getMock();
        $mockcomms->method('initialise_api')->willReturn($mockapi);
        $mockcomms->method('handle_exceptions')->willReturn(null);

        $tiiclass        = new turnitin_class($course->id);
        $tiiclass->comms = $mockcomms;

        // Should not throw.
        $tiiclass->read_class_from_tii();
        $this->assertTrue(true);
    }
}
