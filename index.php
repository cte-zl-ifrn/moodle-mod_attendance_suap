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
 * List of all attendance_suap instances in course
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');

$params = ['id' => $id];
$PAGE->set_url('/mod/attendance_suap/index.php', $params);
$PAGE->set_title($course->shortname.': '.get_string('modulenameplural', 'mod_attendance_suap'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('modulenameplural', 'mod_attendance_suap'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_attendance_suap'));

$attendances = get_all_instances_in_course('attendance_suap', $course);

if (empty($attendances)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_attendance_suap')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
    exit;
}

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('description'),
];

foreach ($attendances as $attendance) {
    $cm = get_coursemodule_from_instance('attendance_suap', $attendance->id, $course->id);
    $context = context_module::instance($cm->id);
    
    $link = html_writer::link(
        new moodle_url('/mod/attendance_suap/view.php', ['id' => $cm->id]),
        format_string($attendance->name)
    );
    
    $description = format_module_intro('attendance_suap', $attendance, $cm->id);
    
    $table->data[] = [$link, $description];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
