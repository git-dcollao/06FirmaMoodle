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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Course-level entry point for local_firma.
 * Students see available documents and their signing status.
 * Teachers also see the management button.
 *
 * @package    local_firma
 * @copyright  2026 Daniel Collao Vivanco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);
$course  = get_course($courseid);

// Debe tener al menos una de las dos capacidades.
if (!has_capability('local/firma:sign', $context) &&
    !has_capability('local/firma:managetemplates', $context)) {
    throw new required_capability_exception($context, 'local/firma:sign', 'nopermissions', '');
}

$PAGE->set_url('/local/firma/course.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_firma'));
$PAGE->set_heading(format_string($course->fullname));

// Cargar versiones activas del curso.
$versions = $DB->get_records_sql(
    "SELECT v.id AS versionid, v.version, v.fieldsjson, t.name AS templatename, t.id AS templateid
       FROM {local_firma_templatever} v
       JOIN {local_firma_templates} t ON t.id = v.templateid
      WHERE t.courseid = :courseid
        AND t.active   = 1
        AND v.status   = 'active'
      ORDER BY t.name, v.version DESC",
    ['courseid' => $courseid]
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_firma'));

// Sección para docentes: acceso a gestión.
if (has_capability('local/firma:managetemplates', $context)) {
    $manageurl = new moodle_url('/local/firma/manage.php', ['courseid' => $courseid]);
    echo html_writer::div(
        html_writer::link($manageurl, get_string('manage_templates', 'local_firma'),
            ['class' => 'btn btn-outline-primary btn-sm']),
        'mb-4'
    );
}

if (empty($versions)) {
    echo $OUTPUT->notification(get_string('course_no_documents', 'local_firma'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$fs = get_file_storage();

echo html_writer::start_tag('div', ['class' => 'list-group']);

foreach ($versions as $v) {
    // Buscar si el usuario ya firmó esta versión.
    $sig = $DB->get_record('local_firma_signatures', [
        'templateversionid' => $v->versionid,
        'userid'            => $USER->id,
        'status'            => 'signed',
    ]);

    $signed    = !empty($sig);
    $pdfavail  = $signed && !empty($sig->signedpdfid);

    // Badge de estado.
    if ($signed) {
        $badge = html_writer::tag('span', get_string('status_signed', 'local_firma'),
            ['class' => 'badge bg-success ms-2']);
    } else {
        $badge = html_writer::tag('span', get_string('status_pending', 'local_firma'),
            ['class' => 'badge bg-warning text-dark ms-2']);
    }

    // Botones de acción.
    $signurl = new moodle_url('/local/firma/sign.php', ['versionid' => $v->versionid]);
    $actions = html_writer::link($signurl,
        $signed ? get_string('signature_resign', 'local_firma') : get_string('btn_sign', 'local_firma'),
        ['class' => 'btn btn-sm ' . ($signed ? 'btn-outline-secondary' : 'btn-primary')]
    );

    if ($pdfavail) {
        $pdffile = $fs->get_file_by_id($sig->signedpdfid);
        if ($pdffile) {
            $dlurl = moodle_url::make_pluginfile_url(
                $pdffile->get_contextid(),
                'local_firma',
                'signedpdf',
                $sig->id,
                $pdffile->get_filepath(),
                $pdffile->get_filename(),
                true
            );
            $actions .= ' ' . html_writer::link($dlurl,
                get_string('signature_download_pdf', 'local_firma'),
                ['class' => 'btn btn-sm btn-success', 'target' => '_blank']
            );
        }
    }

    // Fecha de firma si existe.
    $meta = '';
    if ($signed && !empty($sig->signedat)) {
        $meta = html_writer::tag('small',
            get_string('signature_signedat', 'local_firma') . ': ' . userdate($sig->signedat),
            ['class' => 'text-muted d-block mt-1']
        );
    }

    echo html_writer::div(
        html_writer::div(
            html_writer::tag('strong', format_string($v->templatename)) .
            html_writer::tag('span', ' v' . (int)$v->version, ['class' => 'text-muted']) .
            $badge .
            $meta,
            'd-flex flex-column'
        ) .
        html_writer::div($actions, 'mt-2 mt-md-0 ms-md-auto d-flex align-items-center gap-2'),
        'list-group-item list-group-item-action d-flex flex-column flex-md-row align-items-start align-items-md-center py-3'
    );
}

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
