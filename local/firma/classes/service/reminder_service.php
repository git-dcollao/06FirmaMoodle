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

use core\message\message;
use dml_exception;
use moodle_database;

/**
 * Dispatches reminder notifications for pending signatures.
 */
class reminder_service {
    /** @var moodle_database */
    protected $db;

    public function __construct(?moodle_database $db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * Finds pending signatures that require a reminder and emits Moodle messages.
     *
     * Respects the configured reminder interval: will not send to the same user
     * more often than the configured period (in seconds, default 24 h).
     *
     * @throws dml_exception
     */
    public function process(): void {
        // Configured interval in seconds (default 86400 = 24 h).
        $interval = (int) get_config('local_firma', 'reminderinterval') ?: DAYSECS;
        $cutoff = time() - $interval;

        // Select pending signatures that either have never been reminded
        // or whose last reminder was sent before the cutoff timestamp.
        $sql = "SELECT s.id, s.userid, s.templateversionid
                FROM {local_firma_signatures} s
                WHERE s.status = :status
                  AND (
                      NOT EXISTS (
                          SELECT 1 FROM {local_firma_reminders} r WHERE r.signatureid = s.id
                      )
                      OR (
                          SELECT MAX(r2.sentat) FROM {local_firma_reminders} r2
                          WHERE r2.signatureid = s.id
                      ) <= :cutoff
                  )";
        $pending = $this->db->get_records_sql($sql, ['status' => 'pending', 'cutoff' => $cutoff]);

        foreach ($pending as $signature) {
            $this->send_message($signature->userid, $signature->templateversionid, $signature->id);
            $this->log_reminder($signature->id);
        }
    }

    protected function send_message(int $userid, int $templateversionid, int $signatureid): void {
        $version = $this->db->get_record('local_firma_templatever', ['id' => $templateversionid]);
        $signurl = '';
        if ($version) {
            $signurl = (new \moodle_url('/local/firma/sign.php', ['versionid' => $templateversionid]))->out(false);
        }

        $message = new message();
        $message->component = 'local_firma';
        $message->name = 'reminder';
        $message->userfrom = get_admin();
        $message->userto = $userid;
        $message->subject = get_string('reminder_subject', 'local_firma');
        $body = get_string('reminder_body', 'local_firma');
        if ($signurl) {
            $body .= "\n\n" . $signurl;
        }
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = text_to_html($body, false, false, true);
        $message->smallmessage = get_string('reminder_subject', 'local_firma');
        $message->notification = 1;
        $message->contexturl = $signurl;
        $message->contexturlname = get_string('signatures', 'local_firma');

        message_send($message);
    }

    protected function log_reminder(int $signatureid): void {
        $record = (object) [
            'signatureid' => $signatureid,
            'sentat' => time(),
            'type' => 'automatic',
            'status' => 'sent',
            'message' => '',
        ];
        $this->db->insert_record('local_firma_reminders', $record);
    }
}
