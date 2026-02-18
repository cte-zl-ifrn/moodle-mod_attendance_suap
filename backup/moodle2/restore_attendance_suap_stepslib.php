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
 * Restore steps for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step for attendance_suap restore
 */
class restore_attendance_suap_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure
     *
     * @return array
     */
    protected function define_structure() {

        $paths = [];

        $paths[] = new restore_path_element('attendance_suap', '/activity/attendance_suap');
        $paths[] = new restore_path_element('attendance_suap_day', '/activity/attendance_suap/days/day');
        $paths[] = new restore_path_element('attendance_suap_lesson', '/activity/attendance_suap/days/day/lessons/lesson');
        $paths[] = new restore_path_element('attendance_suap_lesson_module', 
            '/activity/attendance_suap/days/day/lessons/lesson/lesson_modules/lesson_module');
        $paths[] = new restore_path_element('attendance_suap_user_progress', 
            '/activity/attendance_suap/days/day/lessons/lesson/user_progress/progress');

        return $paths;
    }

    /**
     * Process attendance_suap
     *
     * @param array $data
     */
    protected function process_attendance_suap($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('attendance_suap', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process day
     *
     * @param array $data
     */
    protected function process_attendance_suap_day($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->attendanceid = $this->get_new_parentid('attendance_suap');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('attendance_suap_days', $data);
        $this->set_mapping('attendance_suap_day', $oldid, $newitemid);
    }

    /**
     * Process lesson
     *
     * @param array $data
     */
    protected function process_attendance_suap_lesson($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dayid = $this->get_new_parentid('attendance_suap_day');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('attendance_suap_lessons', $data);
        $this->set_mapping('attendance_suap_lesson', $oldid, $newitemid);
    }

    /**
     * Process lesson module
     *
     * @param array $data
     */
    protected function process_attendance_suap_lesson_module($data) {
        global $DB;

        $data = (object)$data;

        $data->lessonid = $this->get_new_parentid('attendance_suap_lesson');
        $data->cmid = $this->get_mappingid('course_module', $data->cmid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        if ($data->cmid) {
            $DB->insert_record('attendance_suap_lesson_modules', $data);
        }
    }

    /**
     * Process user progress
     *
     * @param array $data
     */
    protected function process_attendance_suap_user_progress($data) {
        global $DB;

        $data = (object)$data;

        $data->lessonid = $this->get_new_parentid('attendance_suap_lesson');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->userid) {
            $DB->insert_record('attendance_suap_user_progress', $data);
        }
    }

    /**
     * Post execution actions
     */
    protected function after_execute() {
        $this->add_related_files('mod_attendance_suap', 'intro', null);
    }
}
