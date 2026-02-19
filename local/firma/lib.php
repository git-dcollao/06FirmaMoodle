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

// Load Composer dependencies if available.
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once($autoloader);
}

use local_firma\service\template_manager;

defined('LOCAL_FIRMA_MODULE_LINK') || define('LOCAL_FIRMA_MODULE_LINK', '/local/firma/manage.php');

/**
 * Extends the navigation within a course by adding the signature manager link for users with capability.
 *
 * @param global_navigation $navigation
 * @param stdClass $course
 * @param context_course $context
 * @return void
 */
function local_firma_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('local/firma:managetemplates', $context)) {
        return;
    }

    // Try adding to localplugins first, fall back to course root.
    $parentnode = $navigation->find('localplugins', navigation_node::TYPE_CONTAINER);
    if (!$parentnode) {
        $parentnode = $navigation;
    }

    $url = new moodle_url(LOCAL_FIRMA_MODULE_LINK, ['courseid' => $course->id]);
    $parentnode->add(
        get_string('pluginname', 'local_firma'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_firma',
        new pix_icon('i/report', '')
    );
}

/**
 * Returns a singleton instance of the template manager service.
 * Keeping this helper in lib.php avoids repeated container wiring elsewhere.
 *
 * @return template_manager
 */
function local_firma_template_manager(): template_manager {
    static $manager = null;
    if (!$manager) {
        $manager = new template_manager();
    }
    return $manager;
}

/**
 * File serving handler for local_firma file areas.
 */
function local_firma_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login();

    $allowedareas = ['templatepdf', 'signedpdf', 'signatureqr'];
    if (!in_array($filearea, $allowedareas, true)) {
        send_file_not_found();
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_firma', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
