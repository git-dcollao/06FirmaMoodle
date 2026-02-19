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

$versionid = required_param('versionid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$templatemanager = local_firma_template_manager();
$version = $templatemanager->get_version($versionid);
$template = $templatemanager->get_template($version->templateid);
$course = get_course($template->courseid);

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/firma:managetemplates', $context);

$fs = get_file_storage();
$file = null;
if (!empty($version->fileid)) {
    $file = $fs->get_file_by_id($version->fileid);
}
if (!$file) {
    $files = $fs->get_area_files($context->id, 'local_firma', 'templatepdf', $versionid, '', false);
    if ($files) {
        $file = reset($files);
    }
}

if ($action === 'save' && confirm_sesskey()) {
    $raw = required_param('fieldsjson', PARAM_RAW_TRIMMED);
    $fields = json_decode($raw, true);
    if (!is_array($fields)) {
        throw new moodle_exception('invalidjson', 'error');
    }
    $templatemanager->update_version_fields($versionid, $fields);
    redirect(new moodle_url('/local/firma/fieldeditor.php', ['versionid' => $versionid]), get_string('fieldeditor_saved', 'local_firma'));
}

$fields = json_decode($version->fieldsjson ?? '[]', true) ?: [];
$backurl = new moodle_url('/local/firma/versions.php', ['id' => $template->id]);

$PAGE->set_url(new moodle_url('/local/firma/fieldeditor.php', ['versionid' => $versionid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('fieldeditor', 'local_firma'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/local/firma/styles.css'));

// Preparar datos para pasar al JavaScript vía atributos HTML
$editordata = [];
if ($file) {
    $pdfurl = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        'local_firma',
        'templatepdf',
        $versionid,
        $file->get_filepath(),
        $file->get_filename()
    );
    $editordata = [
        'pdfUrl' => $pdfurl->out(false),
        'fields' => array_values($fields),
        'fieldOptions' => $templatemanager->get_field_sources(),
    ];
    
    // Inicializar el módulo AMD sin pasar datos grandes
    $PAGE->requires->js_call_amd('local_firma/fieldeditor', 'init');
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($template->name) . ' · v' . (int)$version->version);

echo html_writer::link($backurl, get_string('back'));

echo $OUTPUT->box(get_string('fieldeditor_instructions', 'local_firma'));

if (!$file) {
    echo $OUTPUT->notification(get_string('preview_notavailable', 'local_firma'), 'error');
    echo $OUTPUT->footer();
    exit;
}

?>
<form method="post" id="local-firma-fieldeditor-form">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="fieldsjson" id="local-firma-fieldsjson">
    <div class="local-firma-fieldeditor" 
         data-region="local-firma-fieldeditor"
         data-editor-config="<?php echo htmlspecialchars(json_encode($editordata), ENT_QUOTES, 'UTF-8'); ?>"
         data-strings="<?php echo htmlspecialchars(json_encode([
             'addField' => get_string('fieldeditor_addfield', 'local_firma'),
             'deleteField' => get_string('fieldeditor_delete', 'local_firma'),
             'coordinates' => get_string('fieldeditor_coordinates', 'local_firma'),
             'pageLabel' => get_string('fieldeditor_page', 'local_firma'),
             'gridLabel' => get_string('fieldeditor_grid', 'local_firma'),
             'fieldLabel' => get_string('field_label', 'local_firma'),
             'fieldSource' => get_string('field_source', 'local_firma'),
             'fieldPage' => get_string('field_page', 'local_firma'),
             'fieldX' => get_string('field_x', 'local_firma'),
             'fieldY' => get_string('field_y', 'local_firma'),
             'fieldFont' => get_string('field_fontsize', 'local_firma'),
             'fieldWidth' => get_string('field_width', 'local_firma'),
             'fieldValue' => get_string('field_value', 'local_firma'),
         ]), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="local-firma-editor-stage">
            <div class="local-firma-editor-toolbar">
                <label>
                    <?php echo get_string('fieldeditor_page', 'local_firma'); ?>
                    <select id="local-firma-editor-page"></select>
                </label>
                <label>
                    <input type="checkbox" id="local-firma-editor-grid">
                    <?php echo get_string('fieldeditor_grid', 'local_firma'); ?>
                </label>
            </div>
            <div class="local-firma-editor-canvas">
                <canvas id="local-firma-editor-canvas"></canvas>
                <div class="local-firma-field-overlay" data-region="overlay"></div>
            </div>
        </div>
        <div class="local-firma-fieldlist" id="local-firma-fieldlist"></div>
    </div>
    <div class="local-firma-fieldeditor-actions">
        <button type="button" class="btn btn-secondary" id="local-firma-add-field"><?php echo get_string('fieldeditor_addfield', 'local_firma'); ?></button>
        <button type="submit" class="btn btn-primary"><?php echo get_string('fieldeditor_save', 'local_firma'); ?></button>
    </div>
</form>
<?php

echo $OUTPUT->footer();
