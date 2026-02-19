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
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('local/firma:managetemplates', $context);

$PAGE->set_url('/local/firma/manage.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_templates', 'local_firma'));
$PAGE->set_heading(format_string(get_course($courseid)->fullname));

$templatemanager = local_firma_template_manager();
$templates = $templatemanager->list_course_templates($context);
$sections = $templatemanager->get_course_sections($courseid);
$addurl = new moodle_url('/local/firma/edit.php', ['courseid' => $courseid]);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manage_templates', 'local_firma'));

echo $OUTPUT->single_button($addurl, get_string('addtemplate', 'local_firma'), 'get');

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::tag('tr',
    html_writer::tag('th', get_string('templates', 'local_firma')) .
    html_writer::tag('th', get_string('form_type', 'local_firma')) .
    html_writer::tag('th', get_string('form_section', 'local_firma')) .
    html_writer::tag('th', get_string('versions', 'local_firma')) .
    html_writer::tag('th', get_string('status_active', 'local_firma')) .
    html_writer::tag('th', get_string('actions', 'local_firma'))
);
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');
foreach ($templates as $template) {
    $manageurl = $templatemanager->get_manage_url($template->id);
    $status = $template->active ? get_string('status_active', 'local_firma') : get_string('status_inactive', 'local_firma');
    $versionscount = $DB->count_records('local_firma_templatever', ['templateid' => $template->id]);
    $sectionname = $template->sectionid && isset($sections[$template->sectionid]) ? $sections[$template->sectionid] : get_string('form_section_none', 'local_firma');
    $editurl = new moodle_url('/local/firma/edit.php', ['courseid' => $courseid, 'id' => $template->id]);
    $typekey = 'template_type_' . $template->type;
    $type = get_string_manager()->string_exists($typekey, 'local_firma') ? get_string($typekey, 'local_firma') : s($template->type);
    echo html_writer::tag('tr',
        html_writer::tag('td', html_writer::link($manageurl, format_string($template->name))) .
        html_writer::tag('td', $type) .
        html_writer::tag('td', format_string($sectionname)) .
        html_writer::tag('td', $versionscount) .
        html_writer::tag('td', $status) .
        html_writer::tag('td',
            html_writer::link($manageurl, get_string('manageversions', 'local_firma')) . ' | ' .
            html_writer::link($editurl, get_string('edit'))
        )
    );
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
