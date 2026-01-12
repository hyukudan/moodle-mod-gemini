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
 * Adhoc task to generate AI content for mod_gemini
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

        // Check if maximum retries exceeded (5 retries max)
        if ($queue->retries >= 5) {
            $queue->status = -1; // Permanently failed
            $queue->errormessage = get_string('maxretries', 'mod_gemini');
            $queue->timemodified = time();
            $DB->update_record('gemini_queue', $queue);
            mtrace("Queue ID {$queueid}: Maximum retries exceeded, marking as permanently failed.");
            return;
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
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \moodle_exception('invalidjson', 'mod_gemini');
                    }
                    if (!$qdata || !isset($qdata->questions)) {
                        throw new \moodle_exception('invalidjson', 'mod_gemini');
                    }
                    // Helper function to escape CDATA content and prevent XML injection
                    $escape_cdata = function($text) {
                        return str_replace(']]>', ']]]]><![CDATA[>', $text);
                    };
                    // XML Construction
                    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<quiz>' . PHP_EOL;
                    $xml .= '  <question type="category">' . PHP_EOL;
                    $xml .= '    <category><text>$course$/Generated Quizzes/'.s($prompt).'</text></category>' . PHP_EOL;
                    $xml .= '  </question>' . PHP_EOL;
                    foreach ($qdata->questions as $q) {
                        $xml .= '  <question type="multichoice">' . PHP_EOL;
                        $xml .= '    <name><text>' . s($q->name) . '</text></name>' . PHP_EOL;
                        $xml .= '    <questiontext format="html"><text><![CDATA[' . $escape_cdata($q->questiontext) . ']]></text></questiontext>' . PHP_EOL;
                        $xml .= '    <defaultgrade>1.0000000</defaultgrade>' . PHP_EOL;
                        $xml .= '    <penalty>0.3333333</penalty>' . PHP_EOL;
                        $xml .= '    <hidden>0</hidden>' . PHP_EOL;
                        $xml .= '    <single>true</single>' . PHP_EOL;
                        $xml .= '    <shuffleanswers>true</shuffleanswers>' . PHP_EOL;
                        $xml .= '    <answernumbering>abc</answernumbering>' . PHP_EOL;
                        $xml .= '    <answer fraction="100" format="html">' . PHP_EOL;
                        $xml .= '      <text><![CDATA[' . $escape_cdata($q->correct_answer) . ']]></text>' . PHP_EOL;
                        $xml .= '      <feedback format="html"><text>Correct!</text></feedback>' . PHP_EOL;
                        $xml .= '    </answer>' . PHP_EOL;
                        foreach ($q->incorrect_answers as $bad) {
                            $xml .= '    <answer fraction="0" format="html">' . PHP_EOL;
                            $xml .= '      <text><![CDATA[' . $escape_cdata($bad) . ']]></text>' . PHP_EOL;
                            $xml .= '      <feedback format="html"><text>Incorrect.</text></feedback>' . PHP_EOL;
                            $xml .= '    </answer>' . PHP_EOL;
                        }
                        $xml .= '  </question>' . PHP_EOL;
                    }
                    $xml .= '</quiz>';
                    $generated_content = $xml;
                    break;
            }

            // --- SAVE CONTENT WITH VERSIONING ---
            // Wrap versioning operations in a transaction for data integrity
            $transaction = $DB->start_delegated_transaction();
            try {
                // Get the current active version
                $current = $DB->get_record('gemini_content',
                    array('geminiid' => $gemini->id, 'is_current' => 1));

                // Determine the next version number
                $next_version = 1;
                if ($current) {
                    $next_version = $current->version + 1;

                    // Mark the current version as no longer current
                    $current->is_current = 0;
                    $DB->update_record('gemini_content', $current);
                }

                // Create new version record
                $record = new \stdClass();
                $record->geminiid = $gemini->id;
                $record->type = $type;
                $record->content = $generated_content;
                $record->prompt = $prompt;
                $record->version = $next_version;
                $record->parent_id = $current ? $current->id : null;
                $record->is_current = 1;
                $record->timecreated = time();
                $record->timemodified = time();

                $content_id = $DB->insert_record('gemini_content', $record);

                // Get file storage instance (used for cleanup and file saving)
                $fs = get_file_storage();

                // Clean up old versions - keep only the last 10 versions
                $all_versions = $DB->get_records('gemini_content',
                    array('geminiid' => $gemini->id),
                    'version DESC',
                    'id, version',
                    10); // Skip first 10 (most recent)

                if (count($all_versions) >= 10) {
                    $versions_to_keep = array_keys($all_versions);
                    list($in_sql, $params) = $DB->get_in_or_equal($versions_to_keep, SQL_PARAMS_NAMED, 'param', false);
                    $params['geminiid'] = $gemini->id;

                    // Get IDs of versions to delete
                    $old_versions = $DB->get_records_select('gemini_content',
                        "geminiid = :geminiid AND id $in_sql",
                        $params,
                        '',
                        'id');

                    // Delete old versions and their associated files
                    foreach ($old_versions as $old) {
                        // Delete associated files
                        $fs->delete_area_files($context->id, 'mod_gemini', 'audio', $old->id);
                        $fs->delete_area_files($context->id, 'mod_gemini', 'quiz', $old->id);
                        // Delete record
                        $DB->delete_records('gemini_content', array('id' => $old->id));
                    }
                }

                // Commit transaction
                $transaction->allow_commit();
            } catch (\Exception $transaction_exception) {
                $transaction->rollback($transaction_exception);
                throw $transaction_exception;
            }

            // --- SAVE FILES ---
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
            // Delete the queue record on success (no need to keep it)
            $DB->delete_records('gemini_queue', ['id' => $queueid]);
            mtrace("Queue ID {$queueid}: Content generated successfully, queue record deleted.");

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
            // Log the actual error for debugging
            debugging('Content generation error (Queue ID ' . $queueid . '): ' . $e->getMessage(), DEBUG_DEVELOPER);

            // Increment retry counter
            $queue->retries++;
            // Store generic error message for users
            $queue->errormessage = get_string('contentgenerationfailed', 'mod_gemini');
            $queue->timemodified = time();

            // Check if we should retry or permanently fail
            if ($queue->retries >= 5) {
                // Maximum retries reached - permanently fail
                $queue->status = -1; // Permanently failed
                $DB->update_record('gemini_queue', $queue);
                mtrace("Queue ID {$queueid}: Failed after {$queue->retries} retries. Error: " . $e->getMessage());

                // Optionally notify user of permanent failure
                try {
                    $msg = new \core\message\message();
                    $msg->component = 'mod_gemini';
                    $msg->name = 'generation_complete';
                    $msg->userfrom = \core_user::get_noreply_user();
                    $msg->userto = $queue->userid;
                    $msg->subject = 'Gemini Content Generation Failed';
                    $msg->fullmessage = 'Content generation for "' . $queue->prompt . '" failed after maximum retries. Please contact your administrator.';
                    $msg->fullmessageformat = FORMAT_PLAIN;
                    $msg->fullmessagehtml = '<p>Content generation for <strong>' . s($queue->prompt) . '</strong> failed after maximum retries. Please contact your administrator.</p>';
                    $msg->smallmessage = 'Generation Failed';
                    message_send($msg);
                } catch (\Exception $msg_exception) {
                    // Ignore message sending errors
                    mtrace("Failed to send failure notification: " . $msg_exception->getMessage());
                }
            } else {
                // Retry with exponential backoff
                // Calculate delay: 2^retries minutes (1min, 2min, 4min, 8min, 16min)
                $delay_minutes = pow(2, $queue->retries);
                $delay_seconds = $delay_minutes * 60;

                // Reset status to pending for retry
                $queue->status = 0; // Pending
                $DB->update_record('gemini_queue', $queue);

                mtrace("Queue ID {$queueid}: Retry {$queue->retries}/5 scheduled in {$delay_minutes} minute(s). Error: " . $e->getMessage());

                // Create a new adhoc task for retry with delay
                $retry_task = new \mod_gemini\task\generate_content();
                $retry_task->set_custom_data(['queueid' => $queueid]);
                $retry_task->set_next_run_time(time() + $delay_seconds);
                \core\task\manager::queue_adhoc_task($retry_task);
            }
        }
    }
}
