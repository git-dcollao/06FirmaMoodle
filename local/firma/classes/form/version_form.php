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
 * Form for creating template versions (PDF + field layout + checklist).
 */
class version_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $activities = $this->_customdata['activities'];
        $fieldoptions = $this->_customdata['fieldoptions'];
        $fileoptions = $this->_customdata['fileoptions'];
        $repeatcount = $this->_customdata['fieldrepeats'] ?? 3;

        $mform->addElement('hidden', 'templateid', $this->_customdata['templateid']);
        $mform->setType('templateid', PARAM_INT);

        $mform->addElement('text', 'version', get_string('form_version', 'local_firma'), ['size' => 10]);
        $mform->setType('version', PARAM_INT);

        $mform->addElement('filemanager', 'pdffile_filemanager', get_string('form_pdffile', 'local_firma'), null, $fileoptions);
        $mform->addRule('pdffile_filemanager', null, 'required', null, 'client');

        $mform->addElement('autocomplete', 'requiredactivities', get_string('form_requiredactivities', 'local_firma'), $activities, [
            'multiple' => true,
            'noselectionstring' => get_string('form_requiredactivities_none', 'local_firma'),
        ]);
        $mform->setType('requiredactivities', PARAM_RAW);

        $repeatarray = [];
        $repeatoptions = [];

        // Header visual para cada campo.
        $repeatarray[] = $mform->createElement('header', 'fieldheader', get_string('field_header', 'local_firma') . ' {no}');
        $repeatoptions['fieldheader']['expanded'] = true;

        $repeatarray[] = $mform->createElement('text', 'fieldlabel', get_string('field_label', 'local_firma'), ['size' => 40]);
        $repeatoptions['fieldlabel']['type'] = PARAM_TEXT;

        $repeatarray[] = $mform->createElement('select', 'fieldsource', get_string('field_source', 'local_firma'), $fieldoptions);
        $repeatoptions['fieldsource']['default'] = 'fullname';

        $repeatarray[] = $mform->createElement('static', 'coordlabel', '', '<strong>' . get_string('fieldeditor_coordinates', 'local_firma') . '</strong>');

        $repeatarray[] = $mform->createElement('text', 'fieldpage', get_string('field_page', 'local_firma'), ['size' => 5]);
        $repeatoptions['fieldpage']['type'] = PARAM_INT;
        $repeatoptions['fieldpage']['default'] = 1;

        $repeatarray[] = $mform->createElement('text', 'fieldx', get_string('field_x', 'local_firma'), ['size' => 5]);
        $repeatoptions['fieldx']['type'] = PARAM_INT;
        $repeatoptions['fieldx']['default'] = 0;

        $repeatarray[] = $mform->createElement('text', 'fieldy', get_string('field_y', 'local_firma'), ['size' => 5]);
        $repeatoptions['fieldy']['type'] = PARAM_INT;
        $repeatoptions['fieldy']['default'] = 0;

        $repeatarray[] = $mform->createElement('static', 'sizelabel', '', '<strong>' . get_string('field_size', 'local_firma') . '</strong>');

        $repeatarray[] = $mform->createElement('text', 'fieldfontsize', get_string('field_fontsize', 'local_firma'), ['size' => 5]);
        $repeatoptions['fieldfontsize']['type'] = PARAM_FLOAT;
        $repeatoptions['fieldfontsize']['default'] = 12;

        $repeatarray[] = $mform->createElement('text', 'fieldwidth', get_string('field_width', 'local_firma'), ['size' => 5]);
        $repeatoptions['fieldwidth']['type'] = PARAM_FLOAT;
        $repeatoptions['fieldwidth']['default'] = 100;

        $repeatarray[] = $mform->createElement('text', 'fieldvalue', get_string('field_value', 'local_firma'), ['size' => 40]);
        $repeatoptions['fieldvalue']['type'] = PARAM_TEXT;

        $repeatarray[] = $mform->createElement('html', '<hr style="margin: 15px 0; border-top: 2px dashed #ddd;">');

        $this->repeat_elements(
            $repeatarray,
            $repeatcount,
            $repeatoptions,
            'field_repeats',
            'field_add_fields',
            1,
            get_string('addfield', 'local_firma'),
            true
        );

        $mform->addElement('static', 'fieldhelp', '', get_string('field_help', 'local_firma'));

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
