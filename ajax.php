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
 * AJAX handler for mod_gemini.
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

// Actions that students can perform (view capability only)
$student_actions = ['grade_completion', 'chat', 'chat_clear'];

if (!in_array($action, $student_actions)) {
    require_capability('moodle/course:manageactivities', $context); // Default permission for teacher actions
} else {
    require_capability('mod/gemini:view', $context); // Students only need view capability for chat
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($action === 'generate') {
        if (empty($prompt) || empty($type)) {
            throw new moodle_exception('missingparam');
        }

        // Rate limiting: max 10 requests per hour per user.
        $rate_limit = 10;
        $time_window = 3600; // 1 hour.

        // Use MUC cache for rate limiting to improve performance.
        $cache = cache::make('mod_gemini', 'ratelimit');
        $cache_key = 'user_' . $USER->id;

        // Get current counter from cache.
        $rate_data = $cache->get($cache_key);

        if ($rate_data === false) {
            // Cache miss - initialize counter with current timestamp.
            $rate_data = [
                'count' => 0,
                'window_start' => time()
            ];
        } else {
            // Check if time window has expired.
            if (time() - $rate_data['window_start'] >= $time_window) {
                // Reset counter for new time window.
                $rate_data = [
                    'count' => 0,
                    'window_start' => time()
                ];
            }
        }

        // Check rate limit.
        if ($rate_data['count'] >= $rate_limit) {
            throw new moodle_exception('ratelimit', 'mod_gemini', '',
                get_string('ratelimit_exceeded', 'mod_gemini'));
        }

        // Increment counter and update cache.
        $rate_data['count']++;
        $cache->set($cache_key, $rate_data);

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
        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id, 'is_current' => 1), '*', MUST_EXIST);

        $record = new stdClass();
        $record->id = $existing->id;
        $record->content = $content_text;
        $record->timemodified = time();
        $DB->update_record('gemini_content', $record);
        $response['success'] = true;

    } elseif ($action === 'tools_rubric') {
        $existing = $DB->get_record('gemini_content', array('geminiid' => $gemini->id, 'is_current' => 1));
        $topic = $prompt;
        if (!$topic && $existing) { $topic = "the generated content"; }

        $client = new \mod_gemini\service\gemini_client();
        $rubric_html = $client->generate_rubric($topic);
        // Sanitize LLM output to prevent XSS.
        $rubric_html = format_text($rubric_html, FORMAT_HTML, ['context' => $context]);
        $response['success'] = true;
        $response['data'] = ['html' => $rubric_html];
        
    } elseif ($action === 'chat') {
        // Interactive chat with AI about the generated content
        $message = required_param('message', PARAM_RAW);
        $message = clean_param(trim($message), PARAM_TEXT);

        if (empty($message)) {
            throw new moodle_exception('missingparam', '', '', 'message');
        }

        // Limit message length for safety
        if (strlen($message) > 1000) {
            $message = substr($message, 0, 1000);
        }

        // Check if content exists
        $existing_content = $DB->get_record('gemini_content', array('geminiid' => $gemini->id, 'is_current' => 1));
        if (!$existing_content) {
            throw new moodle_exception('chat_no_content', 'mod_gemini');
        }

        // Rate limiting for chat: max 30 messages per hour per user
        $chat_rate_limit = 30;
        $chat_time_window = 3600;

        $cache = cache::make('mod_gemini', 'ratelimit');
        $chat_cache_key = 'chat_user_' . $USER->id;

        $chat_rate_data = $cache->get($chat_cache_key);

        if ($chat_rate_data === false) {
            $chat_rate_data = [
                'count' => 0,
                'window_start' => time()
            ];
        } else {
            if (time() - $chat_rate_data['window_start'] >= $chat_time_window) {
                $chat_rate_data = [
                    'count' => 0,
                    'window_start' => time()
                ];
            }
        }

        if ($chat_rate_data['count'] >= $chat_rate_limit) {
            throw new moodle_exception('chat_ratelimit', 'mod_gemini');
        }

        $chat_rate_data['count']++;
        $cache->set($chat_cache_key, $chat_rate_data);

        // Get chat history from session
        $session_key = 'gemini_chat_' . $gemini->id . '_' . $USER->id;
        if (!isset($SESSION->$session_key)) {
            $SESSION->$session_key = [];
        }
        $chat_history = $SESSION->$session_key;

        // Prepare context content (extract text from various content types)
        $context_text = '';
        switch ($existing_content->type) {
            case 'presentation':
                $data = json_decode($existing_content->content);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \moodle_exception('invalidjson', 'mod_gemini');
                }
                if ($data && isset($data->slides)) {
                    $context_text = "Presentation: " . ($data->title ?? 'Untitled') . "\n\n";
                    foreach ($data->slides as $i => $slide) {
                        $context_text .= "Slide " . ($i + 1) . ": " . $slide->title . "\n";
                        $context_text .= strip_tags($slide->content) . "\n";
                        if (!empty($slide->notes)) {
                            $context_text .= "Notes: " . $slide->notes . "\n";
                        }
                        $context_text .= "\n";
                    }
                }
                break;
            case 'flashcards':
                $data = json_decode($existing_content->content);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \moodle_exception('invalidjson', 'mod_gemini');
                }
                if ($data && isset($data->cards)) {
                    $context_text = "Flashcards about: " . ($data->topic ?? 'Various topics') . "\n\n";
                    foreach ($data->cards as $i => $card) {
                        $context_text .= "Card " . ($i + 1) . ":\n";
                        $context_text .= "Q: " . $card->front . "\n";
                        $context_text .= "A: " . $card->back . "\n\n";
                    }
                }
                break;
            case 'summary':
            case 'audio':
                $context_text = strip_tags($existing_content->content);
                break;
            case 'quiz':
                $context_text = "Quiz questions:\n" . strip_tags($existing_content->content);
                break;
            default:
                $context_text = strip_tags($existing_content->content);
        }

        // Truncate context if too long (keep under 8000 chars to leave room for response)
        if (strlen($context_text) > 8000) {
            $context_text = substr($context_text, 0, 8000) . "\n...[content truncated]";
        }

        // Call AI
        $client = new \mod_gemini\service\gemini_client();
        $ai_response = $client->chat_with_content($context_text, $message, $chat_history);

        // Escape the response for display (prevent XSS)
        $ai_response_safe = format_text($ai_response, FORMAT_PLAIN, ['context' => $context]);

        // Update session history (keep last 10 exchanges = 20 messages)
        $chat_history[] = ['role' => 'user', 'content' => $message];
        $chat_history[] = ['role' => 'assistant', 'content' => $ai_response];

        // Trim history to last 20 messages
        if (count($chat_history) > 20) {
            $chat_history = array_slice($chat_history, -20);
        }
        $SESSION->$session_key = $chat_history;

        $response['success'] = true;
        $response['data'] = [
            'response' => $ai_response_safe,
            'history_count' => count($chat_history)
        ];

    } elseif ($action === 'chat_clear') {
        // Clear chat history from session
        $session_key = 'gemini_chat_' . $gemini->id . '_' . $USER->id;
        $SESSION->$session_key = [];

        $response['success'] = true;
        $response['message'] = get_string('chat_cleared', 'mod_gemini');

    } elseif ($action === 'get_versions') {
        // Get all versions for this gemini instance
        $versions = $DB->get_records('gemini_content',
            array('geminiid' => $gemini->id),
            'version DESC'
        );

        $version_data = array();
        foreach ($versions as $v) {
            $version_data[] = array(
                'id' => $v->id,
                'version' => $v->version,
                'type' => $v->type,
                'prompt' => $v->prompt,
                'is_current' => (bool)$v->is_current,
                'date_created' => userdate($v->timecreated, get_string('strftimedatetime', 'langconfig'))
            );
        }

        $response['success'] = true;
        $response['data'] = array('versions' => $version_data);

    } elseif ($action === 'restore_version') {
        $version_id = required_param('version_id', PARAM_INT);

        // Get the version to restore
        $version_to_restore = $DB->get_record('gemini_content',
            array('id' => $version_id, 'geminiid' => $gemini->id),
            '*',
            MUST_EXIST
        );

        // Wrap restore operations in a transaction for data integrity
        $transaction = $DB->start_delegated_transaction();
        try {
            // Mark all versions as not current
            $DB->execute('UPDATE {gemini_content} SET is_current = 0 WHERE geminiid = ?', array($gemini->id));

            // Mark this version as current
            $version_to_restore->is_current = 1;
            $version_to_restore->timemodified = time();
            $DB->update_record('gemini_content', $version_to_restore);

            // Commit transaction
            $transaction->allow_commit();

            $response['success'] = true;
            $response['message'] = 'Version restored successfully';
        } catch (Exception $transaction_exception) {
            $transaction->rollback($transaction_exception);
            throw $transaction_exception;
        }

    } elseif ($action === 'grade_completion') {
        // Allow students - permission already checked above
    }
} catch (Exception $e) {
    // Log the actual error for debugging purposes
    debugging('Ajax action error: ' . $e->getMessage(), DEBUG_DEVELOPER);

    // Return user-friendly error message
    $response['message'] = get_string('contentgenerationfailed', 'mod_gemini');
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
         // Log the actual error for debugging purposes
         debugging('Grade completion error: ' . $e->getMessage(), DEBUG_DEVELOPER);

         $response['success'] = false;
         $response['message'] = get_string('contentgenerationfailed', 'mod_gemini');
    }
}

header('Content-Type: application/json');
echo json_encode($response);
die();
