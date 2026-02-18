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
 * Library functions for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function attendance_suap_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPS:
            return true;
        default:
            return null;
    }
}

/**
 * Add attendance_suap instance
 *
 * @param stdClass $data
 * @param mod_attendance_suap_mod_form $mform
 * @return int new instance id
 */
function attendance_suap_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    
    if (!isset($data->tendencia_threshold)) {
        $data->tendencia_threshold = 0.90;
    }

    $data->id = $DB->insert_record('attendance_suap', $data);

    return $data->id;
}

/**
 * Update attendance_suap instance
 *
 * @param stdClass $data
 * @param mod_attendance_suap_mod_form $mform
 * @return bool success
 */
function attendance_suap_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('attendance_suap', $data);
}

/**
 * Delete attendance_suap instance
 *
 * @param int $id
 * @return bool success
 */
function attendance_suap_delete_instance($id) {
    global $DB;

    if (!$attendance = $DB->get_record('attendance_suap', ['id' => $id])) {
        return false;
    }

    // Delete all related records.
    $days = $DB->get_records('attendance_suap_days', ['attendanceid' => $id]);
    foreach ($days as $day) {
        $lessons = $DB->get_records('attendance_suap_lessons', ['dayid' => $day->id]);
        foreach ($lessons as $lesson) {
            $DB->delete_records('attendance_suap_lesson_modules', ['lessonid' => $lesson->id]);
            $DB->delete_records('attendance_suap_user_progress', ['lessonid' => $lesson->id]);
        }
        $DB->delete_records('attendance_suap_lessons', ['dayid' => $day->id]);
    }
    $DB->delete_records('attendance_suap_days', ['attendanceid' => $id]);
    $DB->delete_records('attendance_suap', ['id' => $id]);

    return true;
}

/**
 * Calculate user progress for a lesson
 *
 * @param int $lessonid
 * @param int $userid
 * @return array [completed_modules, total_modules, percentage]
 */
function attendance_suap_calculate_lesson_progress($lessonid, $userid) {
    global $DB;

    $modules = $DB->get_records('attendance_suap_lesson_modules', ['lessonid' => $lessonid]);
    $total = count($modules);
    $completed = 0;

    if ($total == 0) {
        return ['completed' => 0, 'total' => 0, 'percentage' => 0];
    }

    foreach ($modules as $module) {
        $completion = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $module->cmid,
            'userid' => $userid,
        ]);
        
        if ($completion && $completion->completionstate > 0) {
            $completed++;
        }
    }

    $percentage = ($total > 0) ? ($completed / $total) * 100 : 0;

    return [
        'completed' => $completed,
        'total' => $total,
        'percentage' => $percentage,
    ];
}

/**
 * Update user progress for a lesson
 *
 * @param int $lessonid
 * @param int $userid
 * @return bool success
 */
function attendance_suap_update_user_progress($lessonid, $userid) {
    global $DB;

    $progress = attendance_suap_calculate_lesson_progress($lessonid, $userid);
    
    $status = 'incomplete';
    if ($progress['percentage'] == 100) {
        $status = 'completed';
    } else if ($progress['percentage'] > 0) {
        $status = 'inprogress';
    } else {
        $status = 'notstarted';
    }

    $record = $DB->get_record('attendance_suap_user_progress', [
        'lessonid' => $lessonid,
        'userid' => $userid,
    ]);

    $time = time();
    if ($record) {
        $record->completed_modules = $progress['completed'];
        $record->total_modules = $progress['total'];
        $record->status = $status;
        $record->timemodified = $time;
        return $DB->update_record('attendance_suap_user_progress', $record);
    } else {
        $record = new stdClass();
        $record->lessonid = $lessonid;
        $record->userid = $userid;
        $record->completed_modules = $progress['completed'];
        $record->total_modules = $progress['total'];
        $record->status = $status;
        $record->timecreated = $time;
        $record->timemodified = $time;
        return $DB->insert_record('attendance_suap_user_progress', $record);
    }
}

/**
 * Get all lessons that contain a specific course module
 *
 * @param int $cmid
 * @return array lesson IDs
 */
function attendance_suap_get_lessons_by_cmid($cmid) {
    global $DB;
    
    $modules = $DB->get_records('attendance_suap_lesson_modules', ['cmid' => $cmid]);
    $lessonids = [];
    foreach ($modules as $module) {
        $lessonids[] = $module->lessonid;
    }
    
    return $lessonids;
}
