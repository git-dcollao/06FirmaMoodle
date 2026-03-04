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

// Buscar si el usuario ya realizó una firma registrada para esta versión.
$sigrecord = $DB->get_record('local_firma_signatures', [
    'templateversionid' => $templateversionid,
    'userid'            => $USER->id,
    'status'            => 'signed',
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('signatures', 'local_firma'));

// ── Bloque: PDF firmado disponible ─────────────────────────────────────────
if ($sigrecord && !empty($sigrecord->signedpdfid)) {
    $fs = get_file_storage();
    $pdffile = $fs->get_file_by_id($sigrecord->signedpdfid);
    if ($pdffile) {
        $downloadurl = moodle_url::make_pluginfile_url(
            $pdffile->get_contextid(),
            'local_firma',
            'signedpdf',
            $sigrecord->id,
            $pdffile->get_filepath(),
            $pdffile->get_filename(),
            true   // forcedownload
        );
        $verifyurl = new moodle_url('/local/firma/verify.php', ['token' => $sigrecord->token]);

        echo html_writer::div(
            html_writer::tag('h5', get_string('signature_already_signed', 'local_firma'), ['class' => 'mb-2']) .
            html_writer::tag('p',
                get_string('signature_signedat', 'local_firma') . ': ' .
                html_writer::tag('strong', userdate($sigrecord->signedat))
            ) .
            html_writer::div(
                html_writer::link($downloadurl, get_string('signature_download_pdf', 'local_firma'),
                    ['class' => 'btn btn-success me-2', 'target' => '_blank']) .
                html_writer::link($verifyurl, get_string('verify', 'local_firma'),
                    ['class' => 'btn btn-outline-secondary', 'target' => '_blank']),
                'mt-3'
            ),
            'alert alert-success'
        );
    }
}

// ── Bloque: checklist de actividades ───────────────────────────────────────
if (!empty($items)) {
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
        $statuslabel = $item->complete
            ? html_writer::tag('span', get_string('status_complete', 'local_firma'), ['class' => 'badge bg-success'])
            : html_writer::tag('span', get_string('status_pending', 'local_firma'), ['class' => 'badge bg-warning text-dark']);
        echo html_writer::tag('tr',
            html_writer::tag('td', format_string($item->name ?? $item->cmid)) .
            html_writer::tag('td', $statuslabel) .
            html_writer::tag('td', $item->progress . '%')
        );
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

// ── Bloque: formulario de firma (solo si es elegible) ──────────────────────
if ($eligible) {
    // Si ya firmó, mostrar sección colapsable para volver a firmar.
    $sectiontitle = $sigrecord
        ? get_string('signature_resign', 'local_firma')
        : get_string('signing_canvas_label', 'local_firma');

    if ($sigrecord) {
        echo html_writer::tag('p',
            html_writer::tag('a', '▸ ' . $sectiontitle, [
                'href'           => '#firma-resign-block',
                'data-bs-toggle' => 'collapse',
                'class'          => 'text-muted small',
            ])
        );
        echo '<div class="collapse" id="firma-resign-block">';
    }

    $formurl = new moodle_url('/local/firma/submit_signature.php');
    $canvasid = 'local-firma-pad';
    $inputid  = 'local-firma-data';
    $submitid = 'local-firma-submit';
    $clearid  = 'local-firma-clear';

    $html  = html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',       'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'versionid',     'value' => $templateversionid]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'signaturedata', 'id'    => $inputid]);
    $html .= html_writer::tag('label', get_string('signing_canvas_label', 'local_firma'), ['for' => $canvasid, 'class' => 'fw-semibold']);
    $html .= html_writer::tag('canvas', '', ['id' => $canvasid, 'width' => 600, 'height' => 200, 'class' => 'firma-canvas']);
    $html .= html_writer::start_div('firma-actions mt-2');
    $html .= html_writer::tag('button', get_string('signature_clear', 'local_firma'),  ['type' => 'button', 'id' => $clearid,  'class' => 'btn btn-secondary me-2']);
    $html .= html_writer::tag('button', get_string('signature_submit', 'local_firma'), ['type' => 'submit', 'id' => $submitid, 'class' => 'btn btn-primary']);
    $html .= html_writer::end_div();
    $html .= html_writer::end_tag('form');

    echo html_writer::div($html, 'firma-signature-area mt-3');

    if ($sigrecord) {
        echo '</div>'; // cierre collapse
    }

    $PAGE->requires->js_call_amd('local_firma/signature', 'init', [[
        'canvasid' => $canvasid,
        'inputid'  => $inputid,
        'submitid' => $submitid,
        'clearid'  => $clearid,
        'message'  => get_string('signature_required', 'local_firma'),
    ]]);
}

echo $OUTPUT->footer();
