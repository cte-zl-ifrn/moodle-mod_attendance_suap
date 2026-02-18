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
 * Notification task for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_attendance_suap\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to send attendance notifications
 */
class notification_task extends \core\task\scheduled_task {

    /**
     * Get task name
     *
     * @return string
     */
    public function get_name() {
        return get_string('notificationtask', 'mod_attendance_suap');
    }

    /**
     * Execute task
     */
    public function execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/message/lib.php');

        mtrace('Starting attendance notification task...');

        // Get all attendance instances.
        $attendances = $DB->get_records('attendance_suap');

        foreach ($attendances as $attendance) {
            $this->process_attendance_notifications($attendance);
        }

        mtrace('Attendance notification task completed.');
    }

    /**
     * Process notifications for a specific attendance instance
     *
     * @param stdClass $attendance
     */
    private function process_attendance_notifications($attendance) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $attendance->course]);
        if (!$course) {
            return;
        }

        $coursecontext = \context_course::instance($course->id);
        $students = get_enrolled_users($coursecontext, 'mod/attendance_suap:viewprogress');

        // Get all days for this attendance.
        $days = $DB->get_records('attendance_suap_days', ['attendanceid' => $attendance->id], 'data_inicio');
        if (empty($days)) {
            return;
        }

        // Calculate total lessons.
        $totallessons = 0;
        $starttime = null;
        $endtime = null;

        foreach ($days as $day) {
            if ($starttime === null || $day->data_inicio < $starttime) {
                $starttime = $day->data_inicio;
            }
            if ($endtime === null || $day->data_fim > $endtime) {
                $endtime = $day->data_fim;
            }
            
            $lessons = $DB->get_records('attendance_suap_lessons', ['dayid' => $day->id]);
            $totallessons += count($lessons);
        }

        if ($totallessons == 0 || $starttime === null) {
            return;
        }

        $currenttime = time();
        $dayspassed = max(0, ($currenttime - $starttime) / 86400);
        $totaldays = max(1, ($endtime - $starttime) / 86400);

        foreach ($students as $student) {
            $this->check_student_progress($student, $attendance, $totallessons, $dayspassed, $totaldays);
        }
    }

    /**
     * Check progress for a specific student
     *
     * @param stdClass $student
     * @param stdClass $attendance
     * @param int $totallessons
     * @param float $dayspassed
     * @param float $totaldays
     */
    private function check_student_progress($student, $attendance, $totallessons, $dayspassed, $totaldays) {
        global $DB;

        // Count completed lessons.
        $sql = "SELECT COUNT(*)
                FROM {attendance_suap_user_progress} p
                JOIN {attendance_suap_lessons} l ON l.id = p.lessonid
                JOIN {attendance_suap_days} d ON d.id = l.dayid
                WHERE d.attendanceid = ?
                AND p.userid = ?
                AND p.status = 'completed'";
        
        $completedlessons = $DB->count_records_sql($sql, [$attendance->id, $student->id]);

        // Calculate expected progress.
        $expectedlessons = ($dayspassed / $totaldays) * $totallessons;
        $threshold = $attendance->tendencia_threshold;

        // Check if notification should be sent.
        if ($completedlessons < ($threshold * $expectedlessons)) {
            $this->send_notification($student, $attendance, $completedlessons, $expectedlessons);
        }
    }

    /**
     * Send notification to student
     *
     * @param stdClass $student
     * @param stdClass $attendance
     * @param int $current
     * @param float $expected
     */
    private function send_notification($student, $attendance, $current, $expected) {
        $message = new \core\message\message();
        $message->component = 'mod_attendance_suap';
        $message->name = 'notification';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $student;
        $message->subject = get_string('notification_subject', 'mod_attendance_suap');
        
        $a = new \stdClass();
        $a->current = round($current, 1);
        $a->expected = round($expected, 1);
        $message->fullmessage = get_string('notification_message', 'mod_attendance_suap', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '';
        $message->smallmessage = '';
        $message->notification = 1;

        message_send($message);
        
        mtrace("Notification sent to user {$student->id} for attendance {$attendance->id}");
    }
}
