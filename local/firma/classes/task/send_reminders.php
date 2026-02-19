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

namespace local_firma\task;

use core\task\scheduled_task;
use local_firma\service\reminder_service;

/**
 * Cron task that dispatches reminders for pending signatures.
 */
class send_reminders extends scheduled_task {
    public function get_name(): string {
        return get_string('task_send_reminders', 'local_firma');
    }

    public function execute(): void {
        $service = new reminder_service();
        $service->process();
    }
}
