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

$templateversionid = required_param('versionid', PARAM_INT);

$version = $DB->get_record('local_firma_templatever', ['id' => $templateversionid], '*', MUST_EXIST);
$template = $DB->get_record('local_firma_templates', ['id' => $version->templateid], '*', MUST_EXIST);

require_login($template->courseid);
$context = context_course::instance($template->courseid);
require_capability('local/firma:sign', $context);

$PAGE->set_url('/local/firma/sign.php', ['versionid' => $templateversionid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('signatures', 'local_firma'));
$PAGE->set_heading(format_string(get_course($template->courseid)->fullname));

$checklistservice = new \local_firma\service\checklist_service();
[$eligible, $items] = $checklistservice->evaluate($context, $version, $USER->id);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('signatures', 'local_firma'));

if (!$eligible) {
    echo $OUTPUT->notification(get_string('signing_locked', 'local_firma'), 'warning');
}

echo html_writer::start_tag('table', ['class' => 'generaltable firma-checklist']);
echo html_writer::start_tag('thead');
echo html_writer::tag('tr',
    html_writer::tag('th', get_string('checklist_activity', 'local_firma')) .
    html_writer::tag('th', get_string('checklist_status', 'local_firma')) .
    html_writer::tag('th', get_string('checklist_progress', 'local_firma'))
);
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');
foreach ($items as $item) {
    $label = $item->complete ? get_string('status_signed', 'local_firma') : get_string('status_pending', 'local_firma');
    echo html_writer::tag('tr',
        html_writer::tag('td', format_string($item->name ?? $item->cmid)) .
        html_writer::tag('td', $label) .
        html_writer::tag('td', $item->progress . '%')
    );
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

if ($eligible) {
    $formurl = new moodle_url('/local/firma/submit_signature.php');
    $canvasid = 'local-firma-pad';
    $inputid = 'local-firma-data';
    $submitid = 'local-firma-submit';
    $clearid = 'local-firma-clear';

    $html = html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'versionid', 'value' => $templateversionid]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'signaturedata', 'id' => $inputid]);
    $html .= html_writer::tag('label', get_string('signing_canvas_label', 'local_firma'), ['for' => $canvasid]);
    $html .= html_writer::tag('canvas', '', ['id' => $canvasid, 'width' => 600, 'height' => 200, 'class' => 'firma-canvas']);
    $html .= html_writer::start_div('firma-actions');
    $html .= html_writer::tag('button', get_string('signature_clear', 'local_firma'), ['type' => 'button', 'id' => $clearid, 'class' => 'btn btn-secondary']);
    $html .= html_writer::tag('button', get_string('signature_submit', 'local_firma'), ['type' => 'submit', 'id' => $submitid, 'class' => 'btn btn-primary']);
    $html .= html_writer::end_div();
    $html .= html_writer::end_tag('form');

    echo html_writer::div($html, 'firma-signature-area');

    $PAGE->requires->js_call_amd('local_firma/signature', 'init', [
        'canvasid' => $canvasid,
        'inputid' => $inputid,
        'submitid' => $submitid,
        'clearid' => $clearid,
        'message' => get_string('signature_required', 'local_firma'),
    ]);
}

echo $OUTPUT->footer();
