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
 * Day form for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_attendance_suap\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding/editing days
 */
class day_form extends \moodleform {

    /**
     * Define form
     */
    public function definition() {
        $mform = $this->_form;

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'attendanceid');
        $mform->setType('attendanceid', PARAM_INT);
        
        $mform->addElement('hidden', 'dayid');
        $mform->setType('dayid', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('name', 'mod_attendance_suap'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Start date.
        $mform->addElement('date_selector', 'data_inicio', get_string('data_inicio', 'mod_attendance_suap'));
        $mform->addRule('data_inicio', null, 'required', null, 'client');

        // End date.
        $mform->addElement('date_selector', 'data_fim', get_string('data_fim', 'mod_attendance_suap'));
        $mform->addRule('data_fim', null, 'required', null, 'client');

        $this->add_action_buttons();
    }

    /**
     * Validate form
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['data_fim'] <= $data['data_inicio']) {
            $errors['data_fim'] = get_string('error_daterange', 'mod_attendance_suap');
        }

        return $errors;
    }
}
