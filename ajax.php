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
require_capability('moodle/course:manageactivities', $context);

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($action === 'generate') {
        if (empty($prompt) || empty($type)) {
            throw new moodle_exception('missingparam');
        }

        $client = new \mod_gemini\service\gemini_client();
        $generated_content = '';
        $mp3_data = null;

        switch ($type) {
            case 'presentation':
                $generated_content = $client->generate_presentation($prompt);
                break;
            case 'flashcards':
                $generated_content = $client->generate_flashcards($prompt);
                break;
            case 'summary':
                $generated_content = $client->generate_summary($prompt);
                break;
            case 'audio':
                // 1. Generate Script
                $messages = [
                    ['role' => 'system', 'content' => 'Write a short, engaging audio script explaining the topic. Use a conversational tone. Keep it under 2 minutes when spoken.'],
                    ['role' => 'user', 'content' => $prompt]
                ];
                $generated_content = $client->generate_content($messages);

                // 2. Generate Audio File (TTS)
                try {
                    $mp3_data = $client->generate_speech_mp3($generated_content);
                } catch (Exception $e) {
                    debugging('TTS Generation failed: ' . $e->getMessage());
                }
                break;
            case 'quiz':
                $json_content = $client->generate_quiz_questions($prompt);
                $data = json_decode($json_content);
                
                if (!$data || !isset($data->questions)) {
                    throw new moodle_exception('invalidjson', 'mod_gemini');
                }

                // Convert JSON to Moodle XML
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
                $xml .= '<quiz>' . PHP_EOL;
                $xml .= '  <question type="category">' . PHP_EOL;
                $xml .= '    <category><text>$course$/Generated Quizzes/'.s($prompt).'</text></category>' . PHP_EOL;
                $xml .= '  </question>' . PHP_EOL;

                foreach ($data->questions as $q) {
                    $xml .= '  <question type="multichoice">' . PHP_EOL;
                    $xml .= '    <name><text>' . s($q->name) . '</text></name>' . PHP_EOL;
                    $xml .= '    <questiontext format="html"><text><![CDATA[' . $q->questiontext . ']]></text></questiontext>' . PHP_EOL;
                    $xml .= '    <defaultgrade>1.0000000</defaultgrade>' . PHP_EOL;
                    $xml .= '    <penalty>0.3333333</penalty>' . PHP_EOL;
                    $xml .= '    <hidden>0</hidden>' . PHP_EOL;
                    $xml .= '    <single>true</single>' . PHP_EOL;
                    $xml .= '    <shuffleanswers>true</shuffleanswers>' . PHP_EOL;
                    $xml .= '    <answernumbering>abc</answernumbering>' . PHP_EOL;

                    // Correct answer
                    $xml .= '    <answer fraction="100" format="html">' . PHP_EOL;
                    $xml .= '      <text><![CDATA[' . $q->correct_answer . ']]></text>' . PHP_EOL;
                    $xml .= '      <feedback format="html"><text>Correct!</text></feedback>' . PHP_EOL;
                    $xml .= '    </answer>' . PHP_EOL;

                    // Incorrect answers
                    foreach ($q->incorrect_answers as $bad) {
                        $xml .= '    <answer fraction="0" format="html">' . PHP_EOL;
                        $xml .= '      <text><![CDATA[' . $bad . ']]></text>' . PHP_EOL;
                        $xml .= '      <feedback format="html"><text>Incorrect.</text></feedback>' . PHP_EOL;
                        $xml .= '    </answer>' . PHP_EOL;
                    }
                    $xml .= '  </question>' . PHP_EOL;
                }
                $xml .= '</quiz>';
                $generated_content = $xml;
                break;

            default:
                throw new moodle_exception('invalidtype', 'mod_gemini');
        }

        // Save to database
        $record = new stdClass();
        $record->geminiid = $gemini->id;
        $record->type = $type;
        $record->content = $generated_content;
        $record->timecreated = time();
        $record->timemodified = time();

        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id));
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('gemini_content', $record);
            $content_id = $existing->id;
        } else {
            $content_id = $DB->insert_record('gemini_content', $record);
        }

        // Post-processing for Files
        $fs = get_file_storage();
        
        if ($type === 'audio' && isset($mp3_data)) {
             $fs->delete_area_files($context->id, 'mod_gemini', 'audio', $content_id);
             $fileinfo = [
                'contextid' => $context->id,
                'component' => 'mod_gemini',
                'filearea'  => 'audio',
                'itemid'    => $content_id,
                'filepath'  => '/',
                'filename'  => 'generated_audio.mp3',
                'timecreated' => time(),
                'timemodified' => time(),
             ];
             $fs->create_file_from_string($fileinfo, $mp3_data);
        }
        elseif ($type === 'quiz') {
             $fs->delete_area_files($context->id, 'mod_gemini', 'quiz', $content_id);
             $fileinfo = [
                'contextid' => $context->id,
                'component' => 'mod_gemini',
                'filearea'  => 'quiz',
                'itemid'    => $content_id,
                'filepath'  => '/',
                'filename'  => 'quiz-export.xml',
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $fs->create_file_from_string($fileinfo, $generated_content);
        }

        $response['success'] = true;

    } elseif ($action === 'reset') {
        $DB->delete_records('gemini_content', array('geminiid' => $gemini->id));
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_gemini', 'audio');
        $fs->delete_area_files($context->id, 'mod_gemini', 'quiz');
        $response['success'] = true;

    } elseif ($action === 'update') {
        $content_text = required_param('content', PARAM_RAW);
        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id), '*', MUST_EXIST);
        
        if (in_array($existing->type, ['presentation', 'flashcards', 'quiz'])) {
            // Basic validation
        }

        $record = new stdClass();
        $record->id = $existing->id;
        $record->content = $content_text;
        $record->timemodified = time();
        $DB->update_record('gemini_content', $record);
        $response['success'] = true;

    } elseif ($action === 'tools_rubric') {
        // Teacher Tool: Generate Rubric
        // We use the existing content/prompt as context
        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id));
        $topic = $prompt; // Passed from JS, or we could derive it
        
        if (!$topic && $existing) {
             // Try to infer topic from content snippet if prompt missing
             $topic = "the generated content"; 
        }

        $client = new \mod_gemini\service\gemini_client();
        $rubric_html = $client->generate_rubric($topic);
        
        $response['success'] = true;
        $response['data'] = ['html' => $rubric_html];
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
die();