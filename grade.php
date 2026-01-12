<?php
define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/gemini/lib.php');

$id = required_param('id', PARAM_INT); // Course Module ID (cmid)

$cm = get_coursemodule_from_id('gemini', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$gemini = $DB->get_record('gemini', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();

$response = ['success' => false];

// Only for students
if (!is_siteadmin() && !has_capability('mod/gemini:view', $context)) {
    die();
}

// Update Grades
$grade = new stdClass();
$grade->userid = $USER->id;
$grade->rawgrade = 100; // Full marks for completion
$grade->feedback = 'Great job completing the flashcards!';
$grade->timecreated = time();
$grade->timemodified = time();

// Use Moodle Grade API
gemini_update_grades($gemini, $USER->id, $grade);

$response['success'] = true;
$response['message'] = 'Grade saved';

header('Content-Type: application/json');
echo json_encode($response);
die();
