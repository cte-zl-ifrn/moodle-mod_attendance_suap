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
 * View page for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/attendance_suap/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$action = optional_param('action', 'view', PARAM_ALPHA);

if ($id) {
    $cm = get_coursemodule_from_id('attendance_suap', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $attendance = $DB->get_record('attendance_suap', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    throw new moodle_exception('missingparameter');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/attendance_suap/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($attendance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($attendance->name));

// Display intro if available.
if (trim(strip_tags($attendance->intro))) {
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
    echo format_module_intro('attendance_suap', $attendance, $cm->id);
    echo $OUTPUT->box_end();
}

// Check capabilities and display appropriate view.
if (has_capability('mod/attendance_suap:manage', $context)) {
    // Teacher view - management interface.
    echo html_writer::tag('h3', get_string('days', 'mod_attendance_suap'));
    
    $manageurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id]);
    $matrixurl = new moodle_url('/mod/attendance_suap/matrix.php', ['id' => $cm->id]);
    
    echo html_writer::link($manageurl, get_string('manage', 'core'), ['class' => 'btn btn-primary']);
    echo ' ';
    echo html_writer::link($matrixurl, get_string('viewmatrix', 'mod_attendance_suap'), ['class' => 'btn btn-secondary']);
    
    // Display days.
    $days = $DB->get_records('attendance_suap_days', ['attendanceid' => $attendance->id], 'data_inicio');
    if (!empty($days)) {
        $table = new html_table();
        $table->head = [
            get_string('name', 'mod_attendance_suap'),
            get_string('data_inicio', 'mod_attendance_suap'),
            get_string('data_fim', 'mod_attendance_suap'),
            get_string('lessons', 'mod_attendance_suap'),
        ];
        
        foreach ($days as $day) {
            $lessoncount = $DB->count_records('attendance_suap_lessons', ['dayid' => $day->id]);
            $row = [
                format_string($day->name),
                userdate($day->data_inicio, get_string('strftimedatefullshort', 'langconfig')),
                userdate($day->data_fim, get_string('strftimedatefullshort', 'langconfig')),
                $lessoncount,
            ];
            $table->data[] = $row;
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('error_nodays', 'mod_attendance_suap'));
    }
    
} else if (has_capability('mod/attendance_suap:viewprogress', $context)) {
    // Student view - progress display.
    echo html_writer::tag('h3', get_string('progress', 'mod_attendance_suap'));
    
    $days = $DB->get_records('attendance_suap_days', ['attendanceid' => $attendance->id], 'data_inicio');
    
    if (!empty($days)) {
        foreach ($days as $day) {
            echo html_writer::start_tag('div', ['class' => 'card mb-3']);
            echo html_writer::start_tag('div', ['class' => 'card-body']);
            
            echo html_writer::tag('h4', format_string($day->name));
            echo html_writer::tag('p', 
                userdate($day->data_inicio, get_string('strftimedatefullshort', 'langconfig')) . ' - ' . 
                userdate($day->data_fim, get_string('strftimedatefullshort', 'langconfig'))
            );
            
            $lessons = $DB->get_records('attendance_suap_lessons', ['dayid' => $day->id], 'sortorder');
            if (!empty($lessons)) {
                foreach ($lessons as $lesson) {
                    $progress = attendance_suap_calculate_lesson_progress($lesson->id, $USER->id);
                    
                    echo html_writer::start_tag('div', ['class' => 'ml-3 mb-2']);
                    echo html_writer::tag('h5', format_string($lesson->name));
                    
                    // Progress bar.
                    $percentage = round($progress['percentage']);
                    echo html_writer::start_tag('div', ['class' => 'progress']);
                    echo html_writer::tag('div', $percentage . '%', [
                        'class' => 'progress-bar',
                        'role' => 'progressbar',
                        'style' => 'width: ' . $percentage . '%',
                        'aria-valuenow' => $percentage,
                        'aria-valuemin' => '0',
                        'aria-valuemax' => '100',
                    ]);
                    echo html_writer::end_tag('div');
                    
                    echo html_writer::tag('p', 
                        $progress['completed'] . ' / ' . $progress['total'] . ' ' . 
                        get_string('modules', 'mod_attendance_suap')
                    );
                    
                    echo html_writer::end_tag('div');
                }
            }
            
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        }
    } else {
        echo html_writer::tag('p', get_string('error_nodays', 'mod_attendance_suap'));
    }
}

echo $OUTPUT->footer();
