<?php
// This file is part of Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_firmasettings', get_string('pluginname', 'local_firma'));

    $settings->add(new admin_setting_configtext(
        'local_firma/maxpdfpages',
        get_string('settings_maxpdfpages', 'local_firma'),
        get_string('settings_maxpdfpages_desc', 'local_firma'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configduration(
        'local_firma/reminderinterval',
        get_string('settings_reminderinterval', 'local_firma'),
        get_string('settings_reminderinterval_desc', 'local_firma'),
        7 * DAYSECS
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_firma/enableqr',
        get_string('settings_enableqr', 'local_firma'),
        get_string('settings_enableqr_desc', 'local_firma'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
