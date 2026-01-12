<?php
define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/gemini/lib.php');

$id = required_param('id', PARAM_INT); // Gemini Instance ID
$action = required_param('action', PARAM_ALPHA);
$prompt = optional_param('prompt', '', PARAM_RAW);
$type = optional_param('type', '', PARAM_ALPHA);

// Validate session and permissions
$gemini = $DB->get_record('gemini', array('id' => $id), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('gemini', $gemini->id, $gemini->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($gemini->course, true, $cm);
require_sesskey();
require_capability('moodle/course:manageactivities', $context); // Default permission for most actions

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($action === 'generate') {
        if (empty($prompt) || empty($type)) {
            throw new moodle_exception('missingparam');
        }

        // 1. Create Queue Record
        $queue = new stdClass();
        $queue->geminiid = $gemini->id;
        $queue->userid = $USER->id;
        $queue->type = $type;
        $queue->prompt = $prompt;
        $queue->status = 0; // Pending
        $queue->timecreated = time();
        $queue->timemodified = time();
        $queueid = $DB->insert_record('gemini_queue', $queue);

        // 2. Schedule Adhoc Task
        $task = new \mod_gemini\task\generate_content();
        $task->set_custom_data(['queueid' => $queueid]);
        \core\task\manager::queue_adhoc_task($task);

        $response['success'] = true;
        $response['message'] = 'Task queued';
        $response['data'] = ['queueid' => $queueid];

    } elseif ($action === 'check_status') {
        // Return pending/processing tasks for this gemini instance
        $tasks = $DB->get_records_select('gemini_queue', 
            'geminiid = ? AND status IN (0, 1)', 
            [$gemini->id],
            'timecreated DESC'
        );
        
        // Also check if we have any completed ones recently to notify frontend to reload
        $completed = $DB->get_records_select('gemini_queue',
             'geminiid = ? AND status = 2 AND timemodified > ?',
             [$gemini->id, time() - 60], // Completed in last minute
             'timecreated DESC',
             '*',
             0,
             1
        );
        
        // Check for Errors
        $errors = $DB->get_records_select('gemini_queue',
             'geminiid = ? AND status = -1 AND timemodified > ?',
             [$gemini->id, time() - 60],
             'timecreated DESC'
        );

        $response['success'] = true;
        $response['data'] = [
            'pending_count' => count($tasks),
            'tasks' => array_values($tasks),
            'has_newly_completed' => !empty($completed),
            'errors' => array_values($errors)
        ];

    } elseif ($action === 'reset') {
        $DB->delete_records('gemini_content', array('geminiid' => $gemini->id));
        $DB->delete_records('gemini_queue', array('geminiid' => $gemini->id)); // Clear queue too
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_gemini', 'audio');
        $fs->delete_area_files($context->id, 'mod_gemini', 'quiz');
        $response['success'] = true;

    } elseif ($action === 'update') {
        $content_text = required_param('content', PARAM_RAW);
        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id), '*', MUST_EXIST);
        
        $record = new stdClass();
        $record->id = $existing->id;
        $record->content = $content_text;
        $record->timemodified = time();
        $DB->update_record('gemini_content', $record);
        $response['success'] = true;

    } elseif ($action === 'tools_rubric') {
        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id));
        $topic = $prompt; 
        if (!$topic && $existing) { $topic = "the generated content"; }

        $client = new \mod_gemini\service\gemini_client();
        $rubric_html = $client->generate_rubric($topic);
        $response['success'] = true;
        $response['data'] = ['html' => $rubric_html];
        
    } elseif ($action === 'grade_completion') {
        // Allow students
        // Note: The capability check at top is strict 'manageactivities', so we need to bypass for this action
        // or refactor permissions. Since we already require login, we can do:
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Special handling for grade_completion permission override
if ($action === 'grade_completion') {
    try {
        require_capability('mod/gemini:view', $context);
        $grade = new stdClass();
        $grade->userid = $USER->id;
        $grade->rawgrade = 100; 
        gemini_update_grades($gemini, $USER->id, $grade);
        $response['success'] = true;
    } catch (Exception $e) {
         $response['success'] = false;
         $response['message'] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
die();
