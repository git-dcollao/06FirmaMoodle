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

namespace local_firma\service;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_user;
use dml_exception;
use moodle_database;
use moodle_exception;
use moodle_url;
use stored_file;
use local_firma\event\signature_completed;
use setasign\Fpdi\Tcpdf\Fpdi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Writer\PngWriter;
use stdClass;
use Throwable;

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Generates PDFs with embedded signatures and QR codes.
 */
class signature_service {
    /** @var moodle_database */
    protected $db;

    public function __construct(?moodle_database $db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * Generates a temporary preview PDF for teachers.
     *
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function generate_preview_pdf(int $templateversionid, stdClass $user): string {
        $version = $this->db->get_record('local_firma_templatever', ['id' => $templateversionid], '*', MUST_EXIST);
        $template = $this->db->get_record('local_firma_templates', ['id' => $version->templateid], '*', MUST_EXIST);
        $course = get_course($template->courseid);
        $context = context_course::instance($course->id);

        $templatefile = $this->get_template_file($context->id, $templateversionid);
        $fields = json_decode($version->fieldsjson ?? '[]', true) ?: [];
        $profiledata = $this->fetch_profile_data($user->id);

        $signaturepath = $this->create_preview_signature_image();
        $qrpath = null;
        if (!empty(get_config('local_firma', 'enableqr'))) {
            $qrpath = $this->create_qr_image($course->idnumber ?? $course->shortname ?? (string)$course->id);
        }

        $output = $this->render_pdf_document($templatefile, $fields, $signaturepath, $qrpath, $user, $course, $profiledata);
        $this->cleanup_temps([$signaturepath, $qrpath]);

        return $output;
    }

    /**
     * Stores a signature, renders the PDF, embeds QR and returns the record id.
     *
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function store_signature(context_course $context, int $templateversionid, int $userid, string $signaturepng): int {
        $version = $this->db->get_record('local_firma_templatever', ['id' => $templateversionid], '*', MUST_EXIST);
        $template = $this->db->get_record('local_firma_templates', ['id' => $version->templateid], '*', MUST_EXIST);
        $course = get_course($template->courseid);
        $user = core_user::get_user($userid, '*', MUST_EXIST);

        $fields = json_decode($version->fieldsjson ?? '[]', true) ?: [];
        $signaturebinary = $this->decode_signature($signaturepng);
        $signaturehash = hash('sha256', $signaturebinary);

        $record = $this->upsert_signature_record($templateversionid, $userid, $signaturehash);

        $fs = get_file_storage();
        $templatefile = $this->get_template_file($context->id, $templateversionid);
        $signaturepath = $this->write_temp_file('sig_', $signaturebinary);
        $qrpath = null;

        if (!empty(get_config('local_firma', 'enableqr'))) {
            $verifyurl = new moodle_url('/local/firma/verify.php', ['token' => $record->token]);
            $qrpath = $this->create_qr_image($verifyurl->out(false));
        }

        $profiledata = $this->fetch_profile_data($userid);
        $outputpath = $this->render_pdf_document(
            $templatefile,
            $fields,
            $signaturepath,
            $qrpath,
            $user,
            $course,
            $profiledata
        );

        $filerecord = [
            'contextid' => $context->id,
            'component' => 'local_firma',
            'filearea' => 'signedpdf',
            'itemid' => $record->id,
            'filepath' => '/',
            'filename' => 'signed_' . $record->id . '.pdf',
        ];
        $storedfile = $fs->create_file_from_pathname($filerecord, $outputpath);

        $record->signedpdfid = $storedfile->get_id();
        $record->pdfhash = hash_file('sha256', $outputpath);
        $record->status = 'signed';
        $record->timemodified = time();
        $this->db->update_record('local_firma_signatures', $record);

        $event = signature_completed::create([
            'context' => $context,
            'objectid' => $record->id,
            'relateduserid' => $userid,
        ]);
        $event->trigger();

        $this->cleanup_temps([$signaturepath, $qrpath, $outputpath]);

        return $record->id;
    }

    /**
     * Ensures a signature record exists and is ready for file generation.
     */
    protected function upsert_signature_record(int $templateversionid, int $userid, string $signaturehash): stdClass {
        $existing = $this->db->get_record('local_firma_signatures', [
            'templateversionid' => $templateversionid,
            'userid' => $userid,
        ]);

        $now = time();
        $payload = [
            'templateversionid' => $templateversionid,
            'userid' => $userid,
            'signedpdfid' => $existing->signedpdfid ?? 0,
            'signedat' => $now,
            'token' => $existing->token ?? $this->generate_token(),
            'ip' => $this->get_client_ip(),
            'useragent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'signaturehash' => $signaturehash,
            'pdfhash' => $existing->pdfhash ?? '',
            'status' => 'pending',
            'completiondata' => $existing->completiondata ?? null,
            'timemodified' => $now,
        ];

        if ($existing) {
            $payload['id'] = $existing->id;
            $payload['timecreated'] = $existing->timecreated;
            $this->db->update_record('local_firma_signatures', $payload);
        } else {
            $payload['timecreated'] = $now;
            $payload['id'] = $this->db->insert_record('local_firma_signatures', (object)$payload);
        }

        return (object)$payload;
    }

    /**
     * Retrieves the stored template PDF for a given version.
     *
     * @throws moodle_exception
     */
    protected function get_template_file(int $contextid, int $templateversionid): stored_file {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'local_firma', 'templatepdf', $templateversionid, '', false);
        if (!$files) {
            throw new moodle_exception('error_template_missing', 'local_firma');
        }
        return reset($files);
    }

    /**
     * Builds a signed PDF and returns the temporary file path.
     *
     * @param stored_file $templatefile
     * @param array $fields
    * @param string|null $signaturepath
     * @param string|null $qrpath
     * @param stdClass $user
     * @param stdClass $course
     * @param array $profiledata
     * @return string
     * @throws moodle_exception
     */
    protected function render_pdf_document(
        stored_file $templatefile,
        array $fields,
        ?string $signaturepath,
        ?string $qrpath,
        stdClass $user,
        stdClass $course,
        array $profiledata
    ): string {
        $templatepath = $templatefile->copy_content_to_temp('local_firma', 'template_' . $templatefile->get_id());
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        $pagecount = $pdf->setSourceFile($templatepath);
        for ($page = 1; $page <= $pagecount; $page++) {
            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            foreach ($fields as $field) {
                $fieldpage = max(1, (int)($field['page'] ?? 1));
                if ($fieldpage !== $page) {
                    continue;
                }

                $source = $field['source'] ?? 'customtext';
                $x = (float)($field['x'] ?? 0);
                $y = (float)($field['y'] ?? 0);
                $sizeparam = (float)($field['fontsize'] ?? 12) ?: 12;
                $width = (float)($field['width'] ?? 0);

                switch ($source) {
                    case 'signature':
                        if ($signaturepath) {
                            $pdf->Image($signaturepath, $x, $y, $sizeparam ?: 40);
                        }
                        break;
                    case 'qr':
                        if ($qrpath) {
                            $pdf->Image($qrpath, $x, $y, $sizeparam ?: 30);
                        }
                        break;
                    default:
                        $value = $this->resolve_field_value($source, $field, $user, $course, $profiledata);
                        if ($value === '') {
                            break;
                        }
                        $pdf->SetFont('helvetica', '', max(6, $sizeparam));
                        $pdf->SetXY($x, $y);
                        if ($width > 0) {
                            $pdf->MultiCell($width, 0, $value, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
                        } else {
                            $pdf->Write(0, $value);
                        }
                        break;
                }
            }
        }

        $outputpath = tempnam(sys_get_temp_dir(), 'signed_');
        $pdf->Output($outputpath, 'F');
        @unlink($templatepath);

        return $outputpath;
    }

    /**
     * Resolves a field value based on its source.
     */
    protected function resolve_field_value(string $source, array $field, stdClass $user, stdClass $course, array $profiledata): string {
        switch ($source) {
            case 'fullname':
                return fullname($user);
            case 'firstname':
                return $user->firstname ?? '';
            case 'lastname':
                return $user->lastname ?? '';
            case 'idnumber':
                return $user->idnumber ?? '';
            case 'email':
                return $user->email ?? '';
            case 'coursefullname':
                return format_string($course->fullname);
            case 'datesigned':
                return userdate(time());
            case 'customtext':
                return $field['value'] ?? '';
            default:
                if (str_starts_with($source, 'profile:')) {
                    $key = substr($source, 8);
                    return $profiledata[$key] ?? '';
                }
                return '';
        }
    }

    /**
     * Returns custom profile data indexed by shortname.
     */
    protected function fetch_profile_data(int $userid): array {
        $record = profile_user_record($userid, false) ?? new stdClass();
        return (array)$record;
    }

    protected function write_temp_file(string $prefix, string $binary): string {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($path, $binary);
        return $path;
    }

    protected function create_qr_image(string $url): ?string {
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->size(280)
                ->margin(5)
                ->build();
            $path = tempnam(sys_get_temp_dir(), 'qr_');
            $result->saveToFile($path);
            return $path;
        } catch (Throwable $e) {
            debugging('QR generation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    protected function create_preview_signature_image(): ?string {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $img = imagecreatetruecolor(420, 140);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $border = imagecolorallocate($img, 0, 86, 179);
        $textcolor = imagecolorallocate($img, 20, 20, 20);
        imagefilledrectangle($img, 0, 0, 419, 139, $bg);
        imagerectangle($img, 0, 0, 419, 139, $border);
        imagestring($img, 5, 30, 60, 'Firma previa (demo)', $textcolor);

        $path = tempnam(sys_get_temp_dir(), 'sig_preview_');
        imagepng($img, $path);
        imagedestroy($img);
        return $path;
    }

    protected function cleanup_temps(array $paths): void {
        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Decodes the base64 signature payload.
     */
    protected function decode_signature(string $data): string {
        if (strpos($data, 'base64,') !== false) {
            [, $data] = explode('base64,', $data, 2);
        }
        $binary = base64_decode($data ?? '', true);
        if ($binary === false || $binary === '') {
            throw new moodle_exception('error_signature_decode', 'local_firma');
        }
        return $binary;
    }

    protected function generate_token(): string {
        return bin2hex(random_bytes(16));
    }

    protected function get_client_ip(): string {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return '';
    }
}
