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
 * Unit tests for mod_attendance_suap lib functions
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_attendance_suap;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/attendance_suap/lib.php');

/**
 * Test case for lib functions
 */
class lib_test extends \advanced_testcase {

    /**
     * Setup before each test
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test attendance_suap_add_instance
     */
    public function test_add_instance() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        
        $data = new \stdClass();
        $data->course = $course->id;
        $data->name = 'Test Attendance';
        $data->intro = 'Test intro';
        $data->introformat = FORMAT_HTML;

        $id = attendance_suap_add_instance($data);

        $this->assertGreaterThan(0, $id);
        
        $record = $DB->get_record('attendance_suap', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('Test Attendance', $record->name);
        $this->assertEquals(0.90, $record->tendencia_threshold);
    }

    /**
     * Test attendance_suap_update_instance
     */
    public function test_update_instance() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        
        $data = new \stdClass();
        $data->course = $course->id;
        $data->name = 'Test Attendance';
        $data->intro = 'Test intro';
        $data->introformat = FORMAT_HTML;

        $id = attendance_suap_add_instance($data);

        $data->instance = $id;
        $data->name = 'Updated Attendance';
        $data->tendencia_threshold = 0.85;

        $result = attendance_suap_update_instance($data);
        $this->assertTrue($result);

        $record = $DB->get_record('attendance_suap', ['id' => $id]);
        $this->assertEquals('Updated Attendance', $record->name);
        $this->assertEquals(0.85, $record->tendencia_threshold);
    }

    /**
     * Test attendance_suap_delete_instance
     */
    public function test_delete_instance() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        
        $data = new \stdClass();
        $data->course = $course->id;
        $data->name = 'Test Attendance';
        $data->intro = 'Test intro';
        $data->introformat = FORMAT_HTML;

        $id = attendance_suap_add_instance($data);

        // Create related records.
        $day = new \stdClass();
        $day->attendanceid = $id;
        $day->name = 'Day 1';
        $day->data_inicio = time();
        $day->data_fim = time() + 86400;
        $day->timecreated = time();
        $day->timemodified = time();
        $dayid = $DB->insert_record('attendance_suap_days', $day);

        $result = attendance_suap_delete_instance($id);
        $this->assertTrue($result);

        $this->assertFalse($DB->record_exists('attendance_suap', ['id' => $id]));
        $this->assertFalse($DB->record_exists('attendance_suap_days', ['id' => $dayid]));
    }

    /**
     * Test attendance_suap_calculate_lesson_progress
     */
    public function test_calculate_lesson_progress() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create attendance instance.
        $data = new \stdClass();
        $data->course = $course->id;
        $data->name = 'Test Attendance';
        $data->intro = 'Test intro';
        $data->introformat = FORMAT_HTML;
        $attendanceid = attendance_suap_add_instance($data);

        // Create day.
        $day = new \stdClass();
        $day->attendanceid = $attendanceid;
        $day->name = 'Day 1';
        $day->data_inicio = time();
        $day->data_fim = time() + 86400;
        $day->timecreated = time();
        $day->timemodified = time();
        $dayid = $DB->insert_record('attendance_suap_days', $day);

        // Create lesson.
        $lesson = new \stdClass();
        $lesson->dayid = $dayid;
        $lesson->name = 'Lesson 1';
        $lesson->sortorder = 0;
        $lesson->timecreated = time();
        $lesson->timemodified = time();
        $lessonid = $DB->insert_record('attendance_suap_lessons', $lesson);

        // Create assignment with completion.
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        // Link module to lesson.
        $module = new \stdClass();
        $module->lessonid = $lessonid;
        $module->cmid = $assign->cmid;
        $module->timecreated = time();
        $DB->insert_record('attendance_suap_lesson_modules', $module);

        // Test progress calculation.
        $progress = attendance_suap_calculate_lesson_progress($lessonid, $user->id);
        
        $this->assertEquals(0, $progress['completed']);
        $this->assertEquals(1, $progress['total']);
        $this->assertEquals(0, $progress['percentage']);
    }

    /**
     * Test attendance_suap_update_user_progress
     */
    public function test_update_user_progress() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create attendance instance.
        $data = new \stdClass();
        $data->course = $course->id;
        $data->name = 'Test Attendance';
        $data->intro = 'Test intro';
        $data->introformat = FORMAT_HTML;
        $attendanceid = attendance_suap_add_instance($data);

        // Create day.
        $day = new \stdClass();
        $day->attendanceid = $attendanceid;
        $day->name = 'Day 1';
        $day->data_inicio = time();
        $day->data_fim = time() + 86400;
        $day->timecreated = time();
        $day->timemodified = time();
        $dayid = $DB->insert_record('attendance_suap_days', $day);

        // Create lesson.
        $lesson = new \stdClass();
        $lesson->dayid = $dayid;
        $lesson->name = 'Lesson 1';
        $lesson->sortorder = 0;
        $lesson->timecreated = time();
        $lesson->timemodified = time();
        $lessonid = $DB->insert_record('attendance_suap_lessons', $lesson);

        // Update user progress.
        $result = attendance_suap_update_user_progress($lessonid, $user->id);
        $this->assertTrue($result);

        $progress = $DB->get_record('attendance_suap_user_progress', [
            'lessonid' => $lessonid,
            'userid' => $user->id,
        ]);
        
        $this->assertNotEmpty($progress);
        $this->assertEquals('notstarted', $progress->status);
        $this->assertEquals(0, $progress->completed_modules);
        $this->assertEquals(0, $progress->total_modules);
    }
}
