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
     * @throws dml_exception
     */
    public function process(): void {
        $sql = "SELECT s.id, s.userid, s.templateversionid
                FROM {local_firma_signatures} s
                WHERE s.status = :status";
        $pending = $this->db->get_records_sql($sql, ['status' => 'pending']);

        foreach ($pending as $signature) {
            $this->send_message($signature->userid, $signature->templateversionid, $signature->id);
            $this->log_reminder($signature->id);
        }
    }

    protected function send_message(int $userid, int $templateversionid, int $signatureid): void {
        $message = new message();
        $message->component = 'local_firma';
        $message->name = 'reminder';
        $message->userfrom = get_admin();
        $message->userto = $userid;
        $message->subject = get_string('reminder_subject', 'local_firma');
        $message->fullmessage = get_string('reminder_body', 'local_firma');
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = text_to_html($message->fullmessage, false, false, true);
        $message->smallmessage = $message->fullmessage;
        $message->notification = 1;

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
