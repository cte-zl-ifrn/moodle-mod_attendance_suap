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
 * Attendance matrix report for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/attendance_suap/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$export = optional_param('export', '', PARAM_ALPHA); // Export format.

$cm = get_coursemodule_from_id('attendance_suap', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$attendance = $DB->get_record('attendance_suap', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/attendance_suap:viewmatrix', $context);

$PAGE->set_url('/mod/attendance_suap/matrix.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($attendance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Get all students.
$coursecontext = context_course::instance($course->id);
$students = get_enrolled_users($coursecontext, 'mod/attendance_suap:viewprogress');

// Get all days and lessons.
$days = $DB->get_records('attendance_suap_days', ['attendanceid' => $attendance->id], 'data_inicio');

$matrixdata = [];
foreach ($students as $student) {
    $studentdata = [
        'id' => $student->id,
        'fullname' => fullname($student),
        'days' => [],
        'total' => 0,
    ];
    
    $totallessons = 0;
    $totalcompleted = 0;
    
    foreach ($days as $day) {
        $lessons = $DB->get_records('attendance_suap_lessons', ['dayid' => $day->id], 'sortorder');
        
        $daycompleted = 0;
        $daylessons = count($lessons);
        
        foreach ($lessons as $lesson) {
            $progress = attendance_suap_calculate_lesson_progress($lesson->id, $student->id);
            if ($progress['percentage'] == 100) {
                $daycompleted++;
            }
            $totallessons++;
            $totalcompleted += ($progress['percentage'] / 100);
        }
        
        $daypercentage = $daylessons > 0 ? ($daycompleted / $daylessons) * 100 : 0;
        
        $studentdata['days'][$day->id] = [
            'name' => format_string($day->name),
            'percentage' => round($daypercentage, 1),
        ];
    }
    
    $studentdata['total'] = $totallessons > 0 ? round(($totalcompleted / $totallessons) * 100, 1) : 0;
    $matrixdata[] = $studentdata;
}

// Handle exports.
if ($export == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_matrix_' . time() . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers.
    $headers = [get_string('student', 'mod_attendance_suap')];
    foreach ($days as $day) {
        $headers[] = format_string($day->name);
    }
    $headers[] = get_string('total', 'mod_attendance_suap');
    fputcsv($output, $headers);
    
    // Data.
    foreach ($matrixdata as $row) {
        $csvrow = [$row['fullname']];
        foreach ($days as $day) {
            $csvrow[] = $row['days'][$day->id]['percentage'] . '%';
        }
        $csvrow[] = $row['total'] . '%';
        fputcsv($output, $csvrow);
    }
    
    fclose($output);
    exit;
}

if ($export == 'html') {
    // Print-friendly HTML export.
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>' . format_string($attendance->name) . ' - ' . get_string('matrix', 'mod_attendance_suap') . '</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        @media print {
            .no-print { display: none; }
        }
    </style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>' . format_string($attendance->name) . '</h1>';
    echo '<h2>' . get_string('matrix', 'mod_attendance_suap') . '</h2>';
    echo '<button class="no-print" onclick="window.print()">Print</button>';
    
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>' . get_string('student', 'mod_attendance_suap') . '</th>';
    foreach ($days as $day) {
        echo '<th>' . format_string($day->name) . '</th>';
    }
    echo '<th>' . get_string('total', 'mod_attendance_suap') . '</th>';
    echo '</tr></thead>';
    
    echo '<tbody>';
    foreach ($matrixdata as $row) {
        echo '<tr>';
        echo '<td>' . $row['fullname'] . '</td>';
        foreach ($days as $day) {
            echo '<td>' . $row['days'][$day->id]['percentage'] . '%</td>';
        }
        echo '<td><strong>' . $row['total'] . '%</strong></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    
    echo '</body>';
    echo '</html>';
    exit;
}

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('matrix', 'mod_attendance_suap'));

// Export buttons.
echo html_writer::start_tag('div', ['class' => 'mb-3']);
$exportcsvurl = new moodle_url('/mod/attendance_suap/matrix.php', ['id' => $cm->id, 'export' => 'csv']);
$exporthtmlurl = new moodle_url('/mod/attendance_suap/matrix.php', ['id' => $cm->id, 'export' => 'html']);
echo html_writer::link($exportcsvurl, get_string('exportcsv', 'mod_attendance_suap'), ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link($exporthtmlurl, get_string('exporthtml', 'mod_attendance_suap'), ['class' => 'btn btn-secondary', 'target' => '_blank']);
echo html_writer::end_tag('div');

if (!empty($matrixdata)) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    
    // Headers.
    $headers = [get_string('student', 'mod_attendance_suap')];
    foreach ($days as $day) {
        $headers[] = format_string($day->name);
    }
    $headers[] = get_string('total', 'mod_attendance_suap');
    $table->head = $headers;
    
    // Data.
    foreach ($matrixdata as $row) {
        $tablerow = [$row['fullname']];
        foreach ($days as $day) {
            $percentage = $row['days'][$day->id]['percentage'];
            $color = $percentage >= 75 ? 'green' : ($percentage >= 50 ? 'orange' : 'red');
            $tablerow[] = html_writer::tag('span', $percentage . '%', ['style' => 'color: ' . $color]);
        }
        $tablerow[] = html_writer::tag('strong', $row['total'] . '%');
        $table->data[] = $tablerow;
    }
    
    echo html_writer::table($table);
} else {
    echo html_writer::tag('p', get_string('nostudentsfound', 'error'));
}

echo $OUTPUT->footer();
