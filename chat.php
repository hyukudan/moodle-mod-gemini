<?php
define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/gemini/lib.php');

$id = required_param('id', PARAM_INT); // Gemini ID (instance)
$question = required_param('question', PARAM_RAW);
$history_json = optional_param('history', '[]', PARAM_RAW);

$gemini = $DB->get_record('gemini', array('id' => $id), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('gemini', $gemini->id, $gemini->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($gemini->course, true, $cm);
require_sesskey();

$response = ['success' => false, 'answer' => ''];

try {
    $content = $DB->get_record('gemini_content', array('geminiid' => $gemini->id), '*', MUST_EXIST);
    $client = new \mod_gemini\service\gemini_client();
    
    $history = json_decode($history_json, true);
    
    $answer = $client->chat_with_content($content->content, $question, $history);
    
    $response['success'] = true;
    $response['answer'] = $answer;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
die();
