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

use local_firma\service\signature_service;

$versionid = required_param('versionid', PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);

$templatemanager = local_firma_template_manager();
$version = $templatemanager->get_version($versionid);
$template = $templatemanager->get_template($version->templateid);
$course = get_course($template->courseid);

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/firma:managetemplates', $context);

if ($mode === 'render') {
    require_sesskey();
    $service = new signature_service();
    $path = $service->generate_preview_pdf($versionid, $USER);
    // Enviar como PDF inline (sin forzar descarga).
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="preview.pdf"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=60');
    readfile($path);
    @unlink($path);
    exit;
}

// Mostrar página HTML con el PDF incrustado.
$PAGE->set_url('/local/firma/preview.php', ['versionid' => $versionid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('preview_pdf', 'local_firma'));
$PAGE->set_heading($course->fullname);

$backurl = new moodle_url('/local/firma/versions.php', ['id' => $template->id]);

$renderurl = new moodle_url('/local/firma/preview.php', [
    'versionid' => $versionid,
    'mode'      => 'render',
    'sesskey'   => sesskey(),
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('preview_pdf', 'local_firma'));
echo html_writer::tag('div',
    html_writer::empty_tag('iframe', [
        'src'         => $renderurl->out(false),
        'style'       => 'width:100%;height:80vh;border:1px solid #ccc;border-radius:4px;',
        'title'       => get_string('preview_pdf', 'local_firma'),
    ]),
    ['style' => 'width:100%;']
);
echo html_writer::div(
    html_writer::link($backurl, get_string('back'), ['class' => 'btn btn-secondary mt-3']),
    'mt-2'
);
echo $OUTPUT->footer();
