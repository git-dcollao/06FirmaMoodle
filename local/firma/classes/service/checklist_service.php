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

use completion_info;
use context_course;
use dml_exception;
use moodle_database;
use stdClass;

/**
 * Aggregates completion and progress data to determine signature eligibility.
 */
class checklist_service {
    /** @var moodle_database */
    protected $db;

    public function __construct(?moodle_database $db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * Evaluates whether a user meets the requirements stored in a template version.
     *
     * @param context_course $context
     * @param stdClass $templateversion record
     * @param int $userid
     * @return array tuple [bool eligible, array items]
     * @throws dml_exception
     */
    public function evaluate(context_course $context, stdClass $templateversion, int $userid): array {
        $course    = get_course($context->instanceid);
        $completion = new completion_info($course);
        $modinfo   = get_fast_modinfo($course, $userid);
        $data      = json_decode($templateversion->requiredactivitiesjson ?? '[]', false) ?? [];
        $items     = [];
        $eligible  = true;

        foreach ($data as $cmid) {
            $cmid = (int)$cmid;
            // Obtener el cm_info desde modinfo (no depende de completion_info).
            if (!isset($modinfo->cms[$cmid])) {
                continue;
            }
            $cm = $modinfo->cms[$cmid];

            $item = (object)[
                'cmid'     => $cmid,
                'name'     => format_string($cm->name),
                'complete' => false,
                'progress' => 0,
            ];

            // get_data() acepta un cm_info directamente.
            $status = $completion->get_data($cm, false, $userid);
            if (!empty($status->completionstate)) {
                $item->complete  = true;
                $item->progress  = 100;
            } else {
                $eligible       = false;
                $item->progress = $this->fetch_progress($templateversion->id, $cmid, $userid);
            }
            $items[] = $item;
        }

        return [$eligible, $items];
    }

    /**
     * Retrieves custom progress stored in local_firma_progress.
     */
    protected function fetch_progress(int $templateversionid, int $cmid, int $userid): int {
        $params = [
            'templateversionid' => $templateversionid,
            'cmid' => $cmid,
            'userid' => $userid,
        ];
        $record = $this->db->get_record('local_firma_progress', $params);
        return $record->progress ?? 0;
    }
}
