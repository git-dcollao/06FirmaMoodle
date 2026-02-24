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

// Intentar cargar FPDI desde el vendor local si existe, para asegurar que la clase esté disponible.
$localvendor = __DIR__ . '/vendor/autoload.php';
if (file_exists($localvendor)) {
    require_once($localvendor);
}

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

$versionid = optional_param('versionid', 0, PARAM_INT);
$editingversion = null;

if ($versionid) {
    try {
        $editingversion = $templatemanager->get_version($versionid);
        if ($editingversion->templateid != $templateid) {
             throw new moodle_exception('invalidtemplateid', 'local_firma');
        }
    } catch (Exception $e) {
        $versionid = 0;
        $editingversion = null;
    }
}

$baseurlparams = ['id' => $templateid];
if ($versionid) {
    $baseurlparams['versionid'] = $versionid;
}
$baseurl = new moodle_url('/local/firma/versions.php', $baseurlparams);

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
    'versionid' => $versionid,
];

if ($editingversion) {
    // Intentar leer las dimensiones del PDF para mostrarlas como referencia.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_firma', 'templatepdf', $editingversion->id, 'itemid, filepath, filename', false);
    if ($files) {
        $file = reset($files);
        // Copiamos a temporal porque FPDI necesita una ruta de archivo local.
        $tempfile = $file->copy_content_to_temp();
        try {
            // Usamos la clase FPDI compatible con TCPDF que viene en el paquete.
            // La ruta completa de la clase depende del autoloader, pero probamos la estándar.
            if (class_exists('\setasign\Fpdi\Tcpdf\Fpdi')) {
                $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
                $pdf->setSourceFile($tempfile);
                $tplIdx = $pdf->importPage(1);
                $size = $pdf->getTemplateSize($tplIdx);
                
                // Agregamos las dimensiones a customdata (por defecto en mm).
                if (isset($size['width']) && isset($size['height'])) {
                    $customdata['pdf_width'] = round($size['width'], 2);
                    $customdata['pdf_height'] = round($size['height'], 2);
                    // Conversión aproximada a píxeles (96 DPI: 1 mm = 3.7795 px)
                    $customdata['pdf_width_px'] = round($size['width'] * 3.7795);
                    $customdata['pdf_height_px'] = round($size['height'] * 3.7795);
                    $customdata['pdf_orientation'] = $size['orientation'] ?? 'P';
                }
            }
        } catch (Exception $e) {
            // Si falla la lectura del PDF (ej. versión incompatible), fallamos silenciosamente 
            // y simplemente no mostramos las dimensiones.
        }
        // Limpiar archivo temporal.
        @unlink($tempfile);
    }

    $fields = json_decode($editingversion->fieldsjson ?? '[]', true);
    if (!empty($fields)) {
        $customdata['fieldrepeats'] = count($fields) + 1;
    }
}

$form = new version_form($baseurl, $customdata);

if ($editingversion) {
    $draftdata = (object) [
        'id' => $editingversion->id,
        'templateid' => $templateid,
        'version' => $editingversion->version,
        'requiredactivities' => json_decode($editingversion->requiredactivitiesjson ?? '[]', true),
    ];
    
    $fields = json_decode($editingversion->fieldsjson ?? '[]', true);
    if (!empty($fields)) {
        $draftdata->fieldlabel = [];
        $draftdata->fieldsource = [];
        $draftdata->fieldpage = [];
        $draftdata->fieldx = [];
        $draftdata->fieldy = [];
        $draftdata->fieldfontsize = [];
        $draftdata->fieldwidth = [];
        $draftdata->fieldvalue = [];

        foreach ($fields as $idx => $field) {
            $draftdata->fieldlabel[$idx] = $field['label'];
            $draftdata->fieldsource[$idx] = $field['source'];
            $draftdata->fieldpage[$idx] = $field['page'];
            $draftdata->fieldx[$idx] = $field['x'];
            $draftdata->fieldy[$idx] = $field['y'];
            $draftdata->fieldfontsize[$idx] = $field['fontsize'];
            $draftdata->fieldwidth[$idx] = $field['width'] ?? 0;
            $draftdata->fieldvalue[$idx] = $field['value'] ?? '';
        }
    }

    file_prepare_standard_filemanager($draftdata, 'pdffile', $fileoptions, $context, 'local_firma', 'templatepdf', $editingversion->id);
    $form->set_data($draftdata);
} else {
    $draftdata = (object) [
        'templateid' => $templateid,
        'version' => $templatemanager->next_version_number($templateid),
    ];
    file_prepare_standard_filemanager($draftdata, 'pdffile', $fileoptions, $context, 'local_firma', 'templatepdf', 0);
    $form->set_data($draftdata);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/firma/manage.php', ['courseid' => $course->id]));
}

if ($data = $form->get_data()) {
    // CORRECCIÓN: Moodle puede filtrar valores de select dentro de repeat_elements.
    // Forzamos fieldsource directamente desde $_POST para garantizar exactitud.
    $data->fieldsource = optional_param_array('fieldsource', [], PARAM_RAW);

    if ($editingversion) {
        // Handle update
        file_postupdate_standard_filemanager($data, 'pdffile', $fileoptions, $context, 'local_firma', 'templatepdf', $versionid);
        
        // If a new file was uploaded, update fileid record
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_firma', 'templatepdf', $versionid, '', false);
        if ($files) {
            $file = reset($files); // Get the first (and only) file
            // Update fileid only if it changed (or always, harmless)
             $DB->set_field('local_firma_templatever', 'fileid', $file->get_id(), ['id' => $versionid]);
        }
        
        $templatemanager->update_version($editingversion, $data);
        redirect(new moodle_url('/local/firma/versions.php', ['id' => $templateid]), get_string('changessaved'));

    } else {
        // Handle create
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
            $editurl = new moodle_url('/local/firma/versions.php', ['id' => $templateid, 'versionid' => $version->id]);
            $actionbuttons .= html_writer::link($editurl, get_string('edit'), [
                'class' => 'btn btn-primary btn-sm mr-1'
            ]);

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
