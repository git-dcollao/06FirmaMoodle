<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied WARRANTY of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_firma\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Moodle form for creating or editing template shells.
 */
class template_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $sections = $this->_customdata['sections'];

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('form_name', 'local_firma'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement('textarea', 'description', get_string('form_description', 'local_firma'), ['rows' => 3, 'cols' => 64]);
        $mform->setType('description', PARAM_TEXT);

        $sectionoptions = [0 => get_string('form_section_none', 'local_firma')] + $sections;
        $mform->addElement('select', 'sectionid', get_string('form_section', 'local_firma'), $sectionoptions);
        $mform->setDefault('sectionid', 0);

        $typeoptions = [
            'module' => get_string('template_type_module', 'local_firma'),
            'coursefinal' => get_string('template_type_coursefinal', 'local_firma'),
        ];
        $mform->addElement('select', 'type', get_string('form_type', 'local_firma'), $typeoptions);
        $mform->setDefault('type', 'module');

        $mform->addElement('advcheckbox', 'active', get_string('form_active', 'local_firma'));
        $mform->setDefault('active', 1);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
