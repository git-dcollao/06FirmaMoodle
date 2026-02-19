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

namespace local_firma\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\user_preference_provider;
use dml_exception;

/**
 * GDPR provider.
 */
class provider implements plugin_provider, user_preference_provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_firma_templates', [
            'courseid' => 'privacy:metadata:templates:courseid',
            'name' => 'privacy:metadata:templates:name',
        ], 'privacy:metadata:templates');

        $collection->add_database_table('local_firma_signatures', [
            'userid' => 'privacy:metadata:signatures:userid',
            'signedpdfid' => 'privacy:metadata:signatures:signedpdfid',
            'ip' => 'privacy:metadata:signatures:ip',
            'useragent' => 'privacy:metadata:signatures:useragent',
            'signaturehash' => 'privacy:metadata:signatures:signaturehash',
            'pdfhash' => 'privacy:metadata:signatures:pdfhash',
            'token' => 'privacy:metadata:signatures:token',
        ], 'privacy:metadata:signatures');

        $collection->add_database_table('local_firma_reminders', [
            'signatureid' => 'privacy:metadata:reminders:signatureid',
            'sentat' => 'privacy:metadata:reminders:sentat',
        ], 'privacy:metadata:reminders');

        return $collection;
    }

    public static function export_user_preferences(int $userid): void {
        // No user preferences stored yet.
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        // Not implemented; will be added when bulk deletes are required.
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        $writer = writer::with_context($contextlist->current());
        $writer->export_data([], (object) []);
    }

    public static function delete_data_for_all_users_in_context(
        \\context $context
    ): void {
        // To be implemented when requirements are finalized.
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // To be implemented when requirements are finalized.
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        // To be implemented when requirements are finalized.
    }
}
