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
 * Module form for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_attendance_suap\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding modules to lessons
 */
class module_form extends \moodleform {

    /**
     * Define form
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $customdata = $this->_customdata;

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'lessonid');
        $mform->setType('lessonid', PARAM_INT);

        // Get course modules.
        $lessonid = $customdata['lessonid'];
        $lesson = $DB->get_record('attendance_suap_lessons', ['id' => $lessonid]);
        $day = $DB->get_record('attendance_suap_days', ['id' => $lesson->dayid]);
        $attendance = $DB->get_record('attendance_suap', ['id' => $day->attendanceid]);
        
        // Get all course modules.
        $modinfo = get_fast_modinfo($attendance->course);
        $modules = [];
        
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->completion > 0) { // Only modules with completion tracking.
                // Check if already added to this lesson.
                $exists = $DB->record_exists('attendance_suap_lesson_modules', [
                    'lessonid' => $lessonid,
                    'cmid' => $cm->id,
                ]);
                
                if (!$exists) {
                    $modules[$cm->id] = $cm->get_formatted_name();
                }
            }
        }

        // Module selection.
        $mform->addElement('select', 'cmid', get_string('selectmodule', 'mod_attendance_suap'), $modules);
        $mform->addRule('cmid', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('addmodule', 'mod_attendance_suap'));
    }
}
