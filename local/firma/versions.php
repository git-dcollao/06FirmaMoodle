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
require_once($CFG->libdir . '/filelib.php');

use local_firma\form\version_form;

$templateid = required_param('id', PARAM_INT);

if (empty($templateid) || $templateid <= 0) {
    throw new moodle_exception('invalidtemplateid', 'local_firma', new moodle_url('/local/firma/index.php'));
}

$templatemanager = local_firma_template_manager();
try {
    $template = $templatemanager->get_template($templateid);
} catch (dml_missing_record_exception $e) {
    throw new moodle_exception('templatenotfound', 'local_firma', new moodle_url('/local/firma/index.php'));
}
$course = get_course($template->courseid);

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/firma:managetemplates', $context);

$baseurl = new moodle_url('/local/firma/versions.php', ['id' => $templateid]);
$fileoptions = [
    'maxfiles' => 1,
    'subdirs' => 0,
    'accepted_types' => ['.pdf'],
];

$customdata = [
    'templateid' => $templateid,
    'activities' => $templatemanager->get_course_modules($course->id),
    'fieldoptions' => $templatemanager->get_field_sources(),
    'fileoptions' => $fileoptions,
    'fieldrepeats' => 3,
];
$form = new version_form($baseurl, $customdata);

$draftdata = (object) [
    'templateid' => $templateid,
    'version' => $templatemanager->next_version_number($templateid),
];
file_prepare_standard_filemanager($draftdata, 'pdffile', $fileoptions, $context, 'local_firma', 'templatepdf', 0);
$form->set_data($draftdata);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/firma/manage.php', ['courseid' => $course->id]));
}

if ($data = $form->get_data()) {
    $versionid = $templatemanager->create_version($template, $data);
    file_postupdate_standard_filemanager($data, 'pdffile', $fileoptions, $context, 'local_firma', 'templatepdf', $versionid);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_firma', 'templatepdf', $versionid, '', false);
    if ($files) {
        $file = reset($files);
        $DB->update_record('local_firma_templatever', (object) [
            'id' => $versionid,
            'fileid' => $file->get_id(),
        ]);
    }

    redirect($baseurl, get_string('changessaved'));
}

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manageversions', 'local_firma'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($template->name));

$backurl = new moodle_url('/local/firma/manage.php', ['courseid' => $course->id]);
echo html_writer::link($backurl, get_string('back'));

echo $OUTPUT->box_start();
$form->display();
echo $OUTPUT->box_end();

$versions = $templatemanager->list_versions($templateid);

if ($versions) {
    $PAGE->requires->js_call_amd('local_firma/versionstable', 'init', [[
        'title' => get_string('preview_modal_title', 'local_firma'),
    ]]);
}

if ($versions) {
    echo html_writer::start_tag('table', ['class' => 'generaltable mt-3']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('form_version', 'local_firma')) .
        html_writer::tag('th', get_string('status_active', 'local_firma')) .
        html_writer::tag('th', get_string('form_requiredactivities', 'local_firma')) .
        html_writer::tag('th', get_string('versions_createdat', 'local_firma')) .
        html_writer::tag('th', get_string('actions', 'local_firma'))
    );
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($versions as $version) {
        $activities = json_decode($version->requiredactivitiesjson ?? '[]', true) ?: [];
        $statuskey = 'status_' . $version->status;
        $status = get_string_manager()->string_exists($statuskey, 'local_firma') ?
            get_string($statuskey, 'local_firma') : s($version->status);

        $actionbuttons = '';
        if (!empty($version->fileid)) {
            $editorurl = new moodle_url('/local/firma/fieldeditor.php', ['versionid' => $version->id]);
            $actionbuttons .= html_writer::link($editorurl, get_string('fieldeditor_launch', 'local_firma'), [
                'class' => 'btn btn-secondary btn-sm mr-1'
            ]);

            $previewurl = new moodle_url('/local/firma/preview.php', [
                'versionid' => $version->id,
                'mode' => 'render',
                'sesskey' => sesskey(),
            ]);
            $actionbuttons .= html_writer::tag('button', get_string('preview_pdf', 'local_firma'), [
                'type' => 'button',
                'class' => 'btn btn-link btn-sm local-firma-preview-trigger',
                'data-firma-preview' => $previewurl->out(false),
            ]);
        } else {
            $actionbuttons = html_writer::span(get_string('preview_notavailable', 'local_firma'), 'text-muted');
        }

        echo html_writer::tag('tr',
            html_writer::tag('td', (int)$version->version) .
            html_writer::tag('td', $status) .
            html_writer::tag('td', count($activities)) .
            html_writer::tag('td', userdate($version->timecreated)) .
            html_writer::tag('td', $actionbuttons)
        );
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo $OUTPUT->notification(get_string('noversions', 'local_firma'), 'info');
}

echo $OUTPUT->footer();
