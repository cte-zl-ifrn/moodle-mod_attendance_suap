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
 * Event observers for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_attendance_suap;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class
 */
class observer {

    /**
     * Observer for course_module_completion_updated event
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        global $DB;

        $cmid = $event->contextinstanceid;
        $userid = $event->relateduserid;

        // Get all lessons that use this module.
        $lessonids = attendance_suap_get_lessons_by_cmid($cmid);

        // Update progress for each lesson.
        foreach ($lessonids as $lessonid) {
            attendance_suap_update_user_progress($lessonid, $userid);
        }
    }

    /**
     * Observer for course_module_deleted event
     *
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;

        $cmid = $event->contextinstanceid;

        // Remove module associations.
        $modules = $DB->get_records('attendance_suap_lesson_modules', ['cmid' => $cmid]);
        
        foreach ($modules as $module) {
            // Delete the module association.
            $DB->delete_records('attendance_suap_lesson_modules', ['id' => $module->id]);
            
            // Update progress for all users in this lesson.
            $lesson = $DB->get_record('attendance_suap_lessons', ['id' => $module->lessonid]);
            if ($lesson) {
                $day = $DB->get_record('attendance_suap_days', ['id' => $lesson->dayid]);
                if ($day) {
                    $attendance = $DB->get_record('attendance_suap', ['id' => $day->attendanceid]);
                    if ($attendance) {
                        // Get course context.
                        $coursecontext = \context_course::instance($attendance->course);
                        $users = get_enrolled_users($coursecontext);
                        
                        foreach ($users as $user) {
                            attendance_suap_update_user_progress($module->lessonid, $user->id);
                        }
                    }
                }
            }
        }
    }
}
