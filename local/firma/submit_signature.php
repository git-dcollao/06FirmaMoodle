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

require_sesskey();
require_post();

$versionid = required_param('versionid', PARAM_INT);
$signaturedata = required_param('signaturedata', PARAM_RAW);

if (empty($signaturedata)) {
    throw new moodle_exception('signature_required', 'local_firma');
}

$version = $DB->get_record('local_firma_templatever', ['id' => $versionid], '*', MUST_EXIST);
$template = $DB->get_record('local_firma_templates', ['id' => $version->templateid], '*', MUST_EXIST);
$course = get_course($template->courseid);

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/firma:sign', $context);

$service = new \local_firma\service\signature_service();
$checklist = new \local_firma\service\checklist_service();
[$eligible] = $checklist->evaluate($context, $version, $USER->id);

if (!$eligible) {
    throw new moodle_exception('signing_locked', 'local_firma');
}

$service->store_signature($context, $versionid, $USER->id, $signaturedata);

redirect(new moodle_url('/local/firma/sign.php', ['versionid' => $versionid]), get_string('signature_success', 'local_firma'));
