<?php
// This file is part of Moodle - http://moodle.org/.
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

namespace local_firma\service;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

use coding_exception;
use context_course;
use dml_exception;
use moodle_database;
use moodle_url;
use stdClass;

/**
 * Handles CRUD operations for templates and versions.
 */
class template_manager {
    /** @var moodle_database */
    protected $db;

    public function __construct(?moodle_database $db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * Creates or updates a template shell.
     *
     * @param stdClass $data
     * @return int template id
     * @throws dml_exception
     */
    public function save_template(stdClass $data): int {
        global $USER;
        $record = (object) [
            'courseid' => $data->courseid,
            'name' => $data->name,
            'description' => $data->description ?? '',
            'sectionid' => $data->sectionid ?? 0,
            'type' => $data->type ?? 'module',
            'active' => empty($data->active) ? 0 : 1,
            'usermodified' => $USER->id,
            'timemodified' => time(),
        ];

        if (!empty($data->id)) {
            $record->id = $data->id;
            $this->db->update_record('local_firma_templates', $record);
            return $record->id;
        }

        $record->usercreated = $USER->id;
        $record->timecreated = time();
        return $this->db->insert_record('local_firma_templates', $record);
    }

    /**
     * Fetches a template by id.
     *
     * @param int $id
     * @return stdClass
     * @throws \dml_missing_record_exception if template not found
     * @throws \moodle_exception if invalid ID
     */
    public function get_template(int $id): stdClass {
        if (empty($id) || $id <= 0) {
            throw new \moodle_exception('invalidtemplateid', 'local_firma');
        }
        return $this->db->get_record('local_firma_templates', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Returns templates for a course ordered by name.
     *
     * @param context_course $context
     * @return array
     * @throws dml_exception
     */
    public function list_course_templates(context_course $context): array {
        $params = ['courseid' => $context->instanceid];
        return $this->db->get_records('local_firma_templates', $params, 'name ASC');
    }

    /**
     * Returns template versions ordered by most recent.
     *
     * @param int $templateid
     * @return array
     * @throws dml_exception
     */
    public function list_versions(int $templateid): array {
        return $this->db->get_records('local_firma_templatever', ['templateid' => $templateid], 'version DESC');
    }

    /**
     * Fetches a specific version record.
     */
    public function get_version(int $versionid): stdClass {
        return $this->db->get_record('local_firma_templatever', ['id' => $versionid], '*', MUST_EXIST);
    }

    /**
     * Creates a version entry (PDF + metadata).
     *
     * @param stdClass $template
     * @param stdClass $data
     * @return int version id
     * @throws dml_exception
     */
    public function create_version(stdClass $template, stdClass $data): int {
        global $USER;
        $record = (object) [
            'templateid' => $template->id,
            'version' => $data->version ?: $this->next_version_number($template->id),
            'fieldsjson' => json_encode($this->build_fields_payload($data)),
            'requiredactivitiesjson' => json_encode($this->extract_required_activities($data)),
            'completionrule' => 'all',
            'status' => 'draft',
            'usercreated' => $USER->id,
            'timecreated' => time(),
        ];
        return $this->db->insert_record('local_firma_templatever', $record);
    }

    /**
     * Updates the field layout for an existing version.
     */
    public function update_version_fields(int $versionid, array $fields): void {
        $payload = (object) [
            'id' => $versionid,
            'fieldsjson' => json_encode($this->normalise_fields_array($fields)),
            'timemodified' => time(),
        ];
        $this->db->update_record('local_firma_templatever', $payload);
    }

    /**
     * Calculates next version number.
     */
    public function next_version_number(int $templateid): int {
        $max = $this->db->get_field_sql('SELECT MAX(version) FROM {local_firma_templatever} WHERE templateid = :templateid', ['templateid' => $templateid]);
        return (int)$max + 1;
    }

    /**
     * Generates a signed URL to manage template versions within a course.
     */
    public function get_manage_url(int $templateid): moodle_url {
        return new moodle_url('/local/firma/versions.php', ['id' => $templateid]);
    }

    /**
     * Returns display-friendly course sections.
     */
    public function get_course_sections(int $courseid): array {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $sections[$section->id] = get_section_name($course, $section);
        }
        return $sections;
    }

    /**
     * Returns all visible course modules for checklist selection.
     */
    public function get_course_modules(int $courseid): array {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $options = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            $options[$cm->id] = format_string($cm->get_formatted_name());
        }
        return $options;
    }

    /**
     * Returns dropdown options for field sources (user/course/custom data).
     */
    public function get_field_sources(): array {
        $options = [
            'fullname' => get_string('field_source_fullname', 'local_firma'),
            'firstname' => get_string('field_source_firstname', 'local_firma'),
            'lastname' => get_string('field_source_lastname', 'local_firma'),
            'idnumber' => get_string('field_source_idnumber', 'local_firma'),
            'email' => get_string('field_source_email', 'local_firma'),
            'customtext' => get_string('field_source_customtext', 'local_firma'),
            'coursefullname' => get_string('field_source_coursefullname', 'local_firma'),
            'datesigned' => get_string('field_source_datesigned', 'local_firma'),
            'signature' => get_string('field_source_signature', 'local_firma'),
            'qr' => get_string('field_source_qr', 'local_firma'),
        ];

        $customfields = $this->db->get_records('user_info_field', null, 'name ASC');
        foreach ($customfields as $field) {
            $options['profile:' . $field->shortname] = get_string('field_source_profile', 'local_firma', $field->name);
        }

        return $options;
    }

    /**
     * Converts repeated form arrays into structured field payload used by PDF renderer.
     */
    protected function build_fields_payload(stdClass $data): array {
        if (empty($data->fieldlabel)) {
            return [];
        }

        $fields = [];
        foreach ($data->fieldlabel as $idx => $label) {
            $fields[] = [
                'label' => $label,
                'source' => $data->fieldsource[$idx] ?? 'customtext',
                'page' => (int)($data->fieldpage[$idx] ?? 1),
                'x' => (float)($data->fieldx[$idx] ?? 0),
                'y' => (float)($data->fieldy[$idx] ?? 0),
                'fontsize' => (float)($data->fieldfontsize[$idx] ?? 12),
                'width' => (float)($data->fieldwidth[$idx] ?? 0),
                'value' => $data->fieldvalue[$idx] ?? '',
            ];
        }

        return $this->normalise_fields_array($fields);
    }

    /**
     * Normalises the required activity ids coming from the form.
     */
    protected function extract_required_activities(stdClass $data): array {
        if (empty($data->requiredactivities) || !is_array($data->requiredactivities)) {
            return [];
        }

        $clean = array_filter($data->requiredactivities, static function($value) {
            return $value !== '' && $value !== null;
        });

        return array_values(array_map('intval', $clean));
    }

    /**
     * Shared sanitiser for field structures coming from forms or the visual editor.
     */
    protected function normalise_fields_array(array $fields): array {
        if (empty($fields)) {
            return [];
        }

        $options = array_keys($this->get_field_sources());
        $allowed = array_flip($options);

        $clean = [];
        foreach ($fields as $field) {
            $label = trim(clean_param($field['label'] ?? '', PARAM_TEXT));
            if ($label === '') {
                continue;
            }

            $source = $field['source'] ?? 'customtext';
            if (!isset($allowed[$source])) {
                $source = 'customtext';
            }

            $clean[] = [
                'label' => $label,
                'source' => $source,
                'page' => max(1, (int)($field['page'] ?? 1)),
                'x' => (float)($field['x'] ?? 0),
                'y' => (float)($field['y'] ?? 0),
                'fontsize' => (float)($field['fontsize'] ?? 12) ?: 12.0,
                'width' => max(0, (float)($field['width'] ?? 0)),
                'value' => trim(clean_param($field['value'] ?? '', PARAM_TEXT)),
            ];
        }

        return array_values($clean);
    }
}
