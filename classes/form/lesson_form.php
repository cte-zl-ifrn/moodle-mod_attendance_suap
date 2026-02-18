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
 * Lesson form for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_attendance_suap\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding/editing lessons
 */
class lesson_form extends \moodleform {

    /**
     * Define form
     */
    public function definition() {
        $mform = $this->_form;

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'dayid');
        $mform->setType('dayid', PARAM_INT);
        
        $mform->addElement('hidden', 'lessonid');
        $mform->setType('lessonid', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('name', 'mod_attendance_suap'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Description.
        $mform->addElement('textarea', 'description', get_string('description', 'mod_attendance_suap'), 
            ['rows' => 5, 'cols' => 60]);
        $mform->setType('description', PARAM_TEXT);

        // Lesson plan.
        $mform->addElement('textarea', 'plano', get_string('plano', 'mod_attendance_suap'), 
            ['rows' => 5, 'cols' => 60]);
        $mform->setType('plano', PARAM_TEXT);

        // Sort order.
        $mform->addElement('text', 'sortorder', get_string('order'), ['size' => '5']);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $this->add_action_buttons();
    }
}
