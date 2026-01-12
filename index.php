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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * List of all Gemini AI Content instances in a course.
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/gemini/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url('/mod/gemini/index.php', ['id' => $id]);
$PAGE->set_title($course->shortname . ': ' . get_string('modulenameplural', 'mod_gemini'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('modulenameplural', 'mod_gemini'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_gemini'));

$geminis = get_all_instances_in_course('gemini', $course);

if (empty($geminis)) {
    notice(
        get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_gemini')),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head = [$strsectionname, get_string('name'), get_string('description')];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head = [get_string('name'), get_string('description')];
    $table->align = ['left', 'left'];
}

foreach ($geminis as $gemini) {
    $cm = get_coursemodule_from_instance('gemini', $gemini->id);
    $context = context_module::instance($cm->id);

    if (!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', $context)) {
        continue;
    }

    $class = $cm->visible ? '' : 'dimmed';
    $link = html_writer::link(
        new moodle_url('/mod/gemini/view.php', ['id' => $cm->id]),
        format_string($gemini->name),
        ['class' => $class]
    );

    $description = format_module_intro('gemini', $gemini, $cm->id);

    if ($usesections) {
        $table->data[] = [get_section_name($course, $gemini->section), $link, $description];
    } else {
        $table->data[] = [$link, $description];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
