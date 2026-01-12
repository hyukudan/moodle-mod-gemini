<?php
namespace mod_gemini\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use mod_gemini\service\gemini_client;

class generate_content extends adhoc_task {

    // Custom data needed: queueid
    
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/gemini/lib.php'); // For functions if needed

        $data = $this->get_custom_data();
        $queueid = $data->queueid;

        // Get Queue Record
        $queue = $DB->get_record('gemini_queue', ['id' => $queueid]);
        if (!$queue) {
            return; // Record deleted?
        }

        // Update status to Processing
        $queue->status = 1;
        $queue->timemodified = time();
        $DB->update_record('gemini_queue', $queue);

        try {
            $gemini = $DB->get_record('gemini', ['id' => $queue->geminiid], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('gemini', $gemini->id, $gemini->course, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);

            $client = new gemini_client();
            $generated_content = '';
            $mp3_data = null;
            $type = $queue->type;
            $prompt = $queue->prompt;

            // --- GENERATION LOGIC (Moved from ajax.php) ---
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
                    $messages = [
                        ['role' => 'system', 'content' => 'Write a short, engaging audio script explaining the topic. Use a conversational tone. Keep it under 2 minutes when spoken.'],
                        ['role' => 'user', 'content' => $prompt]
                    ];
                    $generated_content = $client->generate_content($messages);
                    // TTS
                    try {
                        $mp3_data = $client->generate_speech_mp3($generated_content);
                    } catch (\Exception $e) {
                        mtrace('TTS Failed: ' . $e->getMessage());
                    }
                    break;
                case 'quiz':
                    $json_content = $client->generate_quiz_questions($prompt);
                    $qdata = json_decode($json_content);
                    if (!$qdata || !isset($qdata->questions)) {
                        throw new \moodle_exception('invalidjson', 'mod_gemini');
                    }
                    // XML Construction
                    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<quiz>' . PHP_EOL;
                    $xml .= '  <question type="category">' . PHP_EOL;
                    $xml .= '    <category><text>$course$/Generated Quizzes/'.s($prompt).'</text></category>' . PHP_EOL;
                    $xml .= '  </question>' . PHP_EOL;
                    foreach ($qdata->questions as $q) {
                        $xml .= '  <question type="multichoice">' . PHP_EOL;
                        $xml .= '    <name><text>' . s($q->name) . '</text></name>' . PHP_EOL;
                        $xml .= '    <questiontext format="html"><text><![CDATA[' . $q->questiontext . ']]></text></questiontext>' . PHP_EOL;
                        $xml .= '    <defaultgrade>1.0000000</defaultgrade>' . PHP_EOL;
                        $xml .= '    <penalty>0.3333333</penalty>' . PHP_EOL;
                        $xml .= '    <hidden>0</hidden>' . PHP_EOL;
                        $xml .= '    <single>true</single>' . PHP_EOL;
                        $xml .= '    <shuffleanswers>true</shuffleanswers>' . PHP_EOL;
                        $xml .= '    <answernumbering>abc</answernumbering>' . PHP_EOL;
                        $xml .= '    <answer fraction="100" format="html">' . PHP_EOL;
                        $xml .= '      <text><![CDATA[' . $q->correct_answer . ']]></text>' . PHP_EOL;
                        $xml .= '      <feedback format="html"><text>Correct!</text></feedback>' . PHP_EOL;
                        $xml .= '    </answer>' . PHP_EOL;
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
            }

            // --- SAVE CONTENT ---
            $record = new \stdClass();
            $record->geminiid = $gemini->id;
            $record->type = $type;
            $record->content = $generated_content;
            $record->timecreated = time();
            $record->timemodified = time();

            // Check existing (overwrite or create new? For now overwrite)
            $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id));
            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('gemini_content', $record);
                $content_id = $existing->id;
            } else {
                $content_id = $DB->insert_record('gemini_content', $record);
            }

            // --- SAVE FILES ---
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

            // --- SUCCESS ---
            $queue->status = 2; // Done
            $queue->timemodified = time();
            $DB->update_record('gemini_queue', $queue);

            // Notify User (Optional, but good practice)
            $msg = new \core\message\message();
            $msg->component = 'mod_gemini';
            $msg->name = 'generation_complete';
            $msg->userfrom = \core_user::get_noreply_user();
            $msg->userto = $queue->userid;
            $msg->subject = 'Gemini Content Ready: ' . $queue->type;
            $msg->fullmessage = 'Your content for "' . $prompt . '" has been generated successfully.';
            $msg->fullmessageformat = FORMAT_MARKDOWN;
            $msg->fullmessagehtml = '<p>Your content for <strong>' . s($prompt) . '</strong> is ready.</p>';
            $msg->smallmessage = 'Content Ready';
            message_send($msg);

        } catch (\Exception $e) {
            $queue->status = -1; // Error
            $queue->errormessage = $e->getMessage();
            $queue->timemodified = time();
            $DB->update_record('gemini_queue', $queue);
            mtrace("Error in Gemini Task: " . $e->getMessage());
        }
    }
}
