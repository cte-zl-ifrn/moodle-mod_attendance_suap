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
 * English language strings for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Attendance SUAP';
$string['modulename'] = 'Attendance SUAP';
$string['modulenameplural'] = 'Attendance SUAP';
$string['modulename_help'] = 'Attendance tracking module based on activity completion, integrated with SUAP.';

// Capabilities.
$string['attendance_suap:addinstance'] = 'Add a new Attendance SUAP instance';
$string['attendance_suap:manage'] = 'Manage days, lessons and modules';
$string['attendance_suap:viewmatrix'] = 'View attendance matrix';
$string['attendance_suap:viewprogress'] = 'View progress';

// General.
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['intro'] = 'Introduction';

// Days.
$string['days'] = 'Days';
$string['day'] = 'Day';
$string['addday'] = 'Add day';
$string['editday'] = 'Edit day';
$string['deleteday'] = 'Delete day';
$string['data_inicio'] = 'Start date';
$string['data_fim'] = 'End date';
$string['confirmdeleteday'] = 'Are you sure you want to delete this day?';

// Lessons.
$string['lessons'] = 'Lessons';
$string['lesson'] = 'Lesson';
$string['addlesson'] = 'Add lesson';
$string['editlesson'] = 'Edit lesson';
$string['deletelesson'] = 'Delete lesson';
$string['plano'] = 'Lesson plan';
$string['confirmdeletelesson'] = 'Are you sure you want to delete this lesson?';

// Modules.
$string['modules'] = 'Modules';
$string['module'] = 'Module';
$string['addmodule'] = 'Add module';
$string['deletemodule'] = 'Remove module';
$string['selectmodule'] = 'Select activity/resource';
$string['confirmdeletemodul'] = 'Are you sure you want to remove this module?';

// Progress.
$string['progress'] = 'Progress';
$string['completed'] = 'Completed';
$string['incomplete'] = 'Incomplete';
$string['notstarted'] = 'Not started';
$string['inprogress'] = 'In progress';

// Matrix.
$string['matrix'] = 'Attendance Matrix';
$string['viewmatrix'] = 'View Matrix';
$string['student'] = 'Student';
$string['group'] = 'Group';
$string['total'] = 'Total';
$string['export'] = 'Export';
$string['exportcsv'] = 'Export CSV';
$string['exporthtml'] = 'Export HTML';

// Settings.
$string['tendencia_threshold'] = 'Trend threshold';
$string['tendencia_threshold_desc'] = 'Notification threshold for trend analysis (default: 0.90)';
$string['notification_roles'] = 'Notification roles';
$string['notification_roles_desc'] = 'Roles that receive notifications (comma-separated)';

// Notifications.
$string['notification_subject'] = 'Attendance alert';
$string['notification_message'] = 'Your attendance progress is below expected. Current: {$a->current}, Expected: {$a->expected}';

// Errors.
$string['error_daterange'] = 'End date must be after start date';
$string['error_nodays'] = 'No days configured';
$string['error_nolessons'] = 'No lessons configured';
$string['error_nomodules'] = 'No modules configured';

// Tasks.
$string['notificationtask'] = 'Send attendance notifications';
