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
 * Management interface for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/attendance_suap/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$action = optional_param('action', '', PARAM_ALPHA);
$dayid = optional_param('dayid', 0, PARAM_INT);
$lessonid = optional_param('lessonid', 0, PARAM_INT);
$moduleid = optional_param('moduleid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('attendance_suap', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$attendance = $DB->get_record('attendance_suap', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/attendance_suap:manage', $context);

$PAGE->set_url('/mod/attendance_suap/manage.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($attendance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Process actions.
if ($action == 'deleteday' && $dayid && confirm_sesskey()) {
    $DB->delete_records('attendance_suap_days', ['id' => $dayid]);
    redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id]), 
        get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action == 'deletelesson' && $lessonid && confirm_sesskey()) {
    $DB->delete_records('attendance_suap_lessons', ['id' => $lessonid]);
    redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'dayid' => $dayid]), 
        get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action == 'deletemodule' && $moduleid && confirm_sesskey()) {
    $DB->delete_records('attendance_suap_lesson_modules', ['id' => $moduleid]);
    redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'lessonid' => $lessonid]), 
        get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Handle day form.
if ($action == 'addday' || $action == 'editday') {
    $dayform = new \mod_attendance_suap\form\day_form();
    $dayform->set_data([
        'id' => $cm->id,
        'attendanceid' => $attendance->id,
        'dayid' => $dayid,
    ]);
    
    if ($dayid) {
        $day = $DB->get_record('attendance_suap_days', ['id' => $dayid], '*', MUST_EXIST);
        $dayform->set_data($day);
    }
    
    if ($dayform->is_cancelled()) {
        redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id]));
    } else if ($data = $dayform->get_data()) {
        $time = time();
        if ($data->dayid) {
            $data->id = $data->dayid;
            $data->timemodified = $time;
            $DB->update_record('attendance_suap_days', $data);
        } else {
            $data->attendanceid = $attendance->id;
            $data->timecreated = $time;
            $data->timemodified = $time;
            $DB->insert_record('attendance_suap_days', $data);
        }
        redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id]), 
            get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading($action == 'addday' ? get_string('addday', 'mod_attendance_suap') : get_string('editday', 'mod_attendance_suap'));
    $dayform->display();
    echo $OUTPUT->footer();
    exit;
}

// Handle lesson form.
if ($action == 'addlesson' || $action == 'editlesson') {
    if (!$dayid) {
        throw new moodle_exception('missingparameter');
    }
    
    $lessonform = new \mod_attendance_suap\form\lesson_form();
    $lessonform->set_data([
        'id' => $cm->id,
        'dayid' => $dayid,
        'lessonid' => $lessonid,
    ]);
    
    if ($lessonid) {
        $lesson = $DB->get_record('attendance_suap_lessons', ['id' => $lessonid], '*', MUST_EXIST);
        $lessonform->set_data($lesson);
    }
    
    if ($lessonform->is_cancelled()) {
        redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'dayid' => $dayid]));
    } else if ($data = $lessonform->get_data()) {
        $time = time();
        if ($data->lessonid) {
            $data->id = $data->lessonid;
            $data->timemodified = $time;
            $DB->update_record('attendance_suap_lessons', $data);
        } else {
            $data->dayid = $dayid;
            $data->timecreated = $time;
            $data->timemodified = $time;
            $DB->insert_record('attendance_suap_lessons', $data);
        }
        redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'dayid' => $dayid]), 
            get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading($action == 'addlesson' ? get_string('addlesson', 'mod_attendance_suap') : get_string('editlesson', 'mod_attendance_suap'));
    $lessonform->display();
    echo $OUTPUT->footer();
    exit;
}

// Handle module form.
if ($action == 'addmodule') {
    if (!$lessonid) {
        throw new moodle_exception('missingparameter');
    }
    
    $moduleform = new \mod_attendance_suap\form\module_form(null, ['lessonid' => $lessonid]);
    $moduleform->set_data([
        'id' => $cm->id,
        'lessonid' => $lessonid,
    ]);
    
    if ($moduleform->is_cancelled()) {
        redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'lessonid' => $lessonid]));
    } else if ($data = $moduleform->get_data()) {
        $record = new stdClass();
        $record->lessonid = $lessonid;
        $record->cmid = $data->cmid;
        $record->timecreated = time();
        $DB->insert_record('attendance_suap_lesson_modules', $record);
        
        redirect(new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'lessonid' => $lessonid]), 
            get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('addmodule', 'mod_attendance_suap'));
    $moduleform->display();
    echo $OUTPUT->footer();
    exit;
}

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($attendance->name) . ' - ' . get_string('manage', 'core'));

// Show lesson details if lessonid is set.
if ($lessonid) {
    $lesson = $DB->get_record('attendance_suap_lessons', ['id' => $lessonid], '*', MUST_EXIST);
    $day = $DB->get_record('attendance_suap_days', ['id' => $lesson->dayid], '*', MUST_EXIST);
    
    $backurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'dayid' => $day->id]);
    echo html_writer::link($backurl, '← ' . get_string('back'), ['class' => 'btn btn-secondary mb-3']);
    
    echo html_writer::tag('h3', format_string($lesson->name));
    
    // Add module button.
    $addmoduleurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'action' => 'addmodule', 'lessonid' => $lessonid]);
    echo html_writer::link($addmoduleurl, get_string('addmodule', 'mod_attendance_suap'), ['class' => 'btn btn-primary mb-3']);
    
    // List modules.
    $modules = $DB->get_records('attendance_suap_lesson_modules', ['lessonid' => $lessonid]);
    if (!empty($modules)) {
        $table = new html_table();
        $table->head = [get_string('module', 'mod_attendance_suap'), get_string('actions')];
        
        foreach ($modules as $module) {
            $cm_module = get_coursemodule_from_id('', $module->cmid);
            if ($cm_module) {
                $deleteurl = new moodle_url('/mod/attendance_suap/manage.php', [
                    'id' => $cm->id,
                    'action' => 'deletemodule',
                    'moduleid' => $module->id,
                    'lessonid' => $lessonid,
                    'sesskey' => sesskey(),
                ]);
                
                $actions = html_writer::link($deleteurl, get_string('delete'), 
                    ['class' => 'btn btn-sm btn-danger']);
                
                $table->data[] = [$cm_module->get_formatted_name(), $actions];
            }
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('error_nomodules', 'mod_attendance_suap'));
    }
    
} else if ($dayid) {
    // Show day details.
    $day = $DB->get_record('attendance_suap_days', ['id' => $dayid], '*', MUST_EXIST);
    
    $backurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id]);
    echo html_writer::link($backurl, '← ' . get_string('back'), ['class' => 'btn btn-secondary mb-3']);
    
    echo html_writer::tag('h3', format_string($day->name));
    
    // Add lesson button.
    $addlessonurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'action' => 'addlesson', 'dayid' => $dayid]);
    echo html_writer::link($addlessonurl, get_string('addlesson', 'mod_attendance_suap'), ['class' => 'btn btn-primary mb-3']);
    
    // List lessons.
    $lessons = $DB->get_records('attendance_suap_lessons', ['dayid' => $dayid], 'sortorder');
    if (!empty($lessons)) {
        $table = new html_table();
        $table->head = [
            get_string('name', 'mod_attendance_suap'),
            get_string('modules', 'mod_attendance_suap'),
            get_string('actions'),
        ];
        
        foreach ($lessons as $lesson) {
            $modulecount = $DB->count_records('attendance_suap_lesson_modules', ['lessonid' => $lesson->id]);
            
            $viewurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'lessonid' => $lesson->id]);
            $editurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'action' => 'editlesson', 'lessonid' => $lesson->id, 'dayid' => $dayid]);
            $deleteurl = new moodle_url('/mod/attendance_suap/manage.php', [
                'id' => $cm->id,
                'action' => 'deletelesson',
                'lessonid' => $lesson->id,
                'dayid' => $dayid,
                'sesskey' => sesskey(),
            ]);
            
            $actions = html_writer::link($viewurl, get_string('view'), ['class' => 'btn btn-sm btn-info']) . ' ';
            $actions .= html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-secondary']) . ' ';
            $actions .= html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-sm btn-danger']);
            
            $table->data[] = [format_string($lesson->name), $modulecount, $actions];
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('error_nolessons', 'mod_attendance_suap'));
    }
    
} else {
    // Show all days.
    echo html_writer::tag('h3', get_string('days', 'mod_attendance_suap'));
    
    // Add day button.
    $adddayurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'action' => 'addday']);
    echo html_writer::link($adddayurl, get_string('addday', 'mod_attendance_suap'), ['class' => 'btn btn-primary mb-3']);
    
    // List days.
    $days = $DB->get_records('attendance_suap_days', ['attendanceid' => $attendance->id], 'data_inicio');
    if (!empty($days)) {
        $table = new html_table();
        $table->head = [
            get_string('name', 'mod_attendance_suap'),
            get_string('data_inicio', 'mod_attendance_suap'),
            get_string('data_fim', 'mod_attendance_suap'),
            get_string('lessons', 'mod_attendance_suap'),
            get_string('actions'),
        ];
        
        foreach ($days as $day) {
            $lessoncount = $DB->count_records('attendance_suap_lessons', ['dayid' => $day->id]);
            
            $viewurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'dayid' => $day->id]);
            $editurl = new moodle_url('/mod/attendance_suap/manage.php', ['id' => $cm->id, 'action' => 'editday', 'dayid' => $day->id]);
            $deleteurl = new moodle_url('/mod/attendance_suap/manage.php', [
                'id' => $cm->id,
                'action' => 'deleteday',
                'dayid' => $day->id,
                'sesskey' => sesskey(),
            ]);
            
            $actions = html_writer::link($viewurl, get_string('view'), ['class' => 'btn btn-sm btn-info']) . ' ';
            $actions .= html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-secondary']) . ' ';
            $actions .= html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-sm btn-danger']);
            
            $table->data[] = [
                format_string($day->name),
                userdate($day->data_inicio, get_string('strftimedatefullshort', 'langconfig')),
                userdate($day->data_fim, get_string('strftimedatefullshort', 'langconfig')),
                $lessoncount,
                $actions,
            ];
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('error_nodays', 'mod_attendance_suap'));
    }
}

echo $OUTPUT->footer();
