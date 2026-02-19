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

/**
 * Main index page for firma plugin - lists courses where user can manage templates.
 *
 * @package    local_firma
 * @copyright  2026 Daniel Collao Vivanco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$PAGE->set_url('/local/firma/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_firma'));
$PAGE->set_heading(get_string('pluginname', 'local_firma'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'local_firma'));

echo html_writer::tag('p', get_string('index_description', 'local_firma'));

// Get all courses where user has capability to manage templates.
$courses = enrol_get_my_courses();
$hascourses = false;

echo html_writer::start_tag('div', ['class' => 'course-list']);

foreach ($courses as $course) {
    $context = context_course::instance($course->id);
    
    if (has_capability('local/firma:managetemplates', $context)) {
        $hascourses = true;
        
        $manageurl = new moodle_url('/local/firma/manage.php', ['courseid' => $course->id]);
        $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
        
        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', format_string($course->fullname), ['class' => 'card-title']);
        if (!empty($course->summary)) {
            echo html_writer::tag('p', format_text($course->summary, FORMAT_HTML), ['class' => 'card-text']);
        }
        echo html_writer::link($manageurl, get_string('manage_templates', 'local_firma'), ['class' => 'btn btn-primary mr-2']);
        echo html_writer::link($courseurl, get_string('gotocourse', 'local_firma'), ['class' => 'btn btn-secondary']);
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

if (!$hascourses) {
    echo $OUTPUT->notification(get_string('nocourseswithaccess', 'local_firma'), 'info');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
