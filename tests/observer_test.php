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
 * Unit tests for event observers
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
 * Test case for event observers
 */
class observer_test extends \advanced_testcase {

    /**
     * Setup before each test
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test course_module_completion_updated observer
     */
    public function test_course_module_completion_updated() {
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
            'completionview' => 1,
        ]);

        // Link module to lesson.
        $module = new \stdClass();
        $module->lessonid = $lessonid;
        $module->cmid = $assign->cmid;
        $module->timecreated = time();
        $DB->insert_record('attendance_suap_lesson_modules', $module);

        // Initially no progress record.
        $this->assertFalse($DB->record_exists('attendance_suap_user_progress', [
            'lessonid' => $lessonid,
            'userid' => $user->id,
        ]));

        // Simulate completion update.
        $completion = new \completion_info($course);
        $cm = get_coursemodule_from_id('assign', $assign->cmid);
        
        // Mark as viewed (which triggers completion).
        $completion->update_state($cm, COMPLETION_COMPLETE, $user->id);

        // Progress record should now exist and be updated.
        $progress = $DB->get_record('attendance_suap_user_progress', [
            'lessonid' => $lessonid,
            'userid' => $user->id,
        ]);
        
        $this->assertNotEmpty($progress);
    }

    /**
     * Test course_module_deleted observer
     */
    public function test_course_module_deleted() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

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

        // Create assignment.
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        // Link module to lesson.
        $module = new \stdClass();
        $module->lessonid = $lessonid;
        $module->cmid = $assign->cmid;
        $module->timecreated = time();
        $moduleid = $DB->insert_record('attendance_suap_lesson_modules', $module);

        // Verify module link exists.
        $this->assertTrue($DB->record_exists('attendance_suap_lesson_modules', ['id' => $moduleid]));

        // Delete the course module.
        course_delete_module($assign->cmid);

        // Module link should be removed.
        $this->assertFalse($DB->record_exists('attendance_suap_lesson_modules', ['id' => $moduleid]));
    }
}
