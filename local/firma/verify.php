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

$token = required_param('token', PARAM_ALPHANUMEXT);

$PAGE->set_url('/local/firma/verify.php', ['token' => $token]);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('pluginname', 'local_firma'));
$PAGE->set_heading(get_string('pluginname', 'local_firma'));

$record = $DB->get_record('local_firma_signatures', ['token' => $token], '*', IGNORE_MISSING);

if (!$record) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('verification_notfound', 'local_firma'), 'error');
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo html_writer::tag('h3', get_string('verification_title', 'local_firma'));
echo html_writer::start_tag('dl');
echo html_writer::tag('dt', get_string('verification_status', 'local_firma'));
echo html_writer::tag('dd', format_string($record->status));
echo html_writer::tag('dt', get_string('verification_signedat', 'local_firma'));
echo html_writer::tag('dd', $record->signedat ? userdate($record->signedat) : '-');
echo html_writer::tag('dt', get_string('verification_pdfhash', 'local_firma'));
echo html_writer::tag('dd', $record->pdfhash);
echo html_writer::tag('dt', get_string('verification_signaturehash', 'local_firma'));
echo html_writer::tag('dd', $record->signaturehash);
echo html_writer::end_tag('dl');

if (!empty($record->signedpdfid) && isloggedin() && has_capability('local/firma:downloadall', $systemcontext)) {
    $fs = get_file_storage();
    if ($file = $fs->get_file_by_id($record->signedpdfid)) {
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            'local_firma',
            'signedpdf',
            $record->id,
            $file->get_filepath(),
            $file->get_filename()
        );
        echo html_writer::link($url, get_string('verification_download', 'local_firma'));
    }
}

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
