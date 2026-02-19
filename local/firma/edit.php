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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

use local_firma\form\template_form;

$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/firma:managetemplates', $context);

$templatemanager = local_firma_template_manager();
$template = $id ? $templatemanager->get_template($id) : null;
$sections = $templatemanager->get_course_sections($courseid);

$customdata = [
    'course' => $course,
    'sections' => $sections,
];
$form = new template_form(null, $customdata);
if ($template) {
    $form->set_data($template);
}

$returnurl = new moodle_url('/local/firma/manage.php', ['courseid' => $courseid]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $templateid = $templatemanager->save_template($data);
    
    // If creating a new template, redirect to versions page to create first version.
    if (!$id) {
        if (empty($templateid) || $templateid <= 0) {
            throw new moodle_exception('error:templatenotsaved', 'local_firma');
        }
        redirect(new moodle_url('/local/firma/versions.php', ['id' => $templateid]));
    }
    
    redirect($returnurl, get_string('changessaved'));
}

$PAGE->set_url('/local/firma/edit.php', ['courseid' => $courseid, 'id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_templates', 'local_firma'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

echo $OUTPUT->heading($template ? get_string('edit') : get_string('addtemplate', 'local_firma'));

$form->display();

echo $OUTPUT->footer();
