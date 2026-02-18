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
 * Backup steps for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step for attendance_suap backup
 */
class backup_attendance_suap_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define structure
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Define each element.
        $attendance = new backup_nested_element('attendance_suap', ['id'], [
            'name', 'intro', 'introformat', 'tendencia_threshold',
            'timecreated', 'timemodified',
        ]);

        $days = new backup_nested_element('days');
        $day = new backup_nested_element('day', ['id'], [
            'name', 'data_inicio', 'data_fim', 'timecreated', 'timemodified',
        ]);

        $lessons = new backup_nested_element('lessons');
        $lesson = new backup_nested_element('lesson', ['id'], [
            'name', 'description', 'plano', 'sortorder', 'timecreated', 'timemodified',
        ]);

        $modules = new backup_nested_element('lesson_modules');
        $module = new backup_nested_element('lesson_module', ['id'], [
            'cmid', 'timecreated',
        ]);

        $progress = new backup_nested_element('user_progress');
        $userprogress = new backup_nested_element('progress', ['id'], [
            'userid', 'completed_modules', 'total_modules', 'status',
            'timecreated', 'timemodified',
        ]);

        // Build the tree.
        $attendance->add_child($days);
        $days->add_child($day);

        $day->add_child($lessons);
        $lessons->add_child($lesson);

        $lesson->add_child($modules);
        $modules->add_child($module);

        $lesson->add_child($progress);
        $progress->add_child($userprogress);

        // Define sources.
        $attendance->set_source_table('attendance_suap', ['id' => backup::VAR_ACTIVITYID]);
        $day->set_source_table('attendance_suap_days', ['attendanceid' => backup::VAR_PARENTID]);
        $lesson->set_source_table('attendance_suap_lessons', ['dayid' => backup::VAR_PARENTID]);
        $module->set_source_table('attendance_suap_lesson_modules', ['lessonid' => backup::VAR_PARENTID]);
        $userprogress->set_source_table('attendance_suap_user_progress', ['lessonid' => backup::VAR_PARENTID]);

        // Define id annotations.
        $module->annotate_ids('course_module', 'cmid');
        $userprogress->annotate_ids('user', 'userid');

        // Define file annotations.
        $attendance->annotate_files('mod_attendance_suap', 'intro', null);

        return $this->prepare_activity_structure($attendance);
    }
}
