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
 * English strings for mod_gemini.
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Gemini AI Content';
$string['modulename'] = 'Gemini Content';
$string['modulename_help'] = ' The Gemini Content module allows teachers to generate educational resources such as presentations, summaries, flashcards, and audio using AI models (Gemini or Local LLMs).';
$string['modulenameplural'] = 'Gemini Contents';
$string['gemini:addinstance'] = 'Add a new Gemini Content resource';
$string['gemini:view'] = 'View Gemini Content';
$string['gemini:generate'] = 'Generate AI content';
$string['gemini:viewanalytics'] = 'View analytics and usage statistics';

// Settings
$string['settings_apikey'] = 'API Key';
$string['settings_apikey_desc'] = 'Enter your API Key (Google Gemini or LM Studio/OpenAI compatible).';
$string['settings_baseurl'] = 'Base URL';
$string['settings_baseurl_desc'] = 'The API endpoint URL. For Gemini (OpenAI compat): "https://generativelanguage.googleapis.com/v1beta/openai/". For LM Studio: "http://localhost:1234/v1".';
$string['settings_model'] = 'Model Name';
$string['settings_model_desc'] = 'The model ID to use. E.g., "gemini-3.0-flash", "gemini-1.5-pro", "llama-3-8b-instruct".';
$string['settings_temperature'] = 'Temperature';
$string['settings_temperature_desc'] = 'Creativity level (0.0 to 1.0).';

// UI Strings
$string['teacher_controls'] = '🔧 Teacher Controls';
$string['generate_rubric'] = '💡 Generate Rubric';
$string['edit_content'] = '✏️ Edit Content';
$string['regenerate'] = '🔄 Regenerate (Reset)';
$string['regenerate_confirm'] = 'Are you sure? This will DELETE the current content permanently.';
$string['ai_teaching_assistant'] = '🤖 AI Teaching Assistant';
$string['create_rubric'] = '📊 Create Assessment Rubric';
$string['thinking'] = 'Thinking...';
$string['close'] = 'Close';
$string['save_changes'] = 'Save Changes';
$string['saving'] = 'Saving...';
$string['edit_modal_title'] = 'Edit Generated Content';
$string['edit_modal_help'] = 'Edit the raw content below. For Presentations/Flashcards, ensure JSON validity.';
$string['no_content_yet'] = 'No content generated yet. Use the wizard below to create it.';
$string['content_creator'] = 'Gemini Content Creator';
$string['generate_btn'] = '✨ Generate with Gemini';
$string['generating_msg'] = 'Generating... (this may take a minute)';
$string['contacting_msg'] = 'Contacting Gemini... Do not close this page.';
$string['success_reload'] = 'Content generated successfully! Reloading...';
$string['error_prefix'] = 'Error: ';
$string['enter_topic'] = 'Please enter a topic.';
$string['configure_type'] = 'Configure your {$a}'; // {$a} will be replaced by type name
$string['topic_prompt'] = 'Topic / Prompt';
$string['topic_placeholder'] = 'E.g., The history of the Roman Empire...';
$string['prompt_inspiration'] = 'Try these:';
$string['prompt_explain_child'] = 'Explain like I\'m 10';
$string['prompt_critical_analysis'] = 'Critical Analysis';
$string['prompt_real_world'] = 'Real-world Examples';
$string['prompt_timeline'] = 'Timeline of Events';

// Tasks
$string['cleanup_task'] = 'Cleanup old Gemini generation logs';

// Privacy
$string['privacy:metadata:gemini_queue'] = 'Stores temporary prompts and status of content generation tasks.';
$string['privacy:metadata:gemini_queue:userid'] = 'The user who requested the content generation.';
$string['privacy:metadata:gemini_queue:prompt'] = 'The text prompt sent to the AI.';
$string['privacy:metadata:gemini_queue:timecreated'] = 'When the request was made.';

// Chat Feature
$string['chat_with_ai'] = 'Ask AI Tutor';
$string['chat_panel_title'] = 'AI Tutor Chat';
$string['chat_placeholder'] = 'Ask a question about this content...';
$string['chat_send'] = 'Send';
$string['chat_thinking'] = 'Thinking...';
$string['chat_error'] = 'Sorry, I could not process your question. Please try again.';
$string['chat_welcome'] = 'Hello! I am your AI tutor. Ask me any questions about the content above.';
$string['chat_no_content'] = 'No content available to discuss.';
$string['chat_clear'] = 'Clear Chat';
$string['chat_cleared'] = 'Chat history cleared.';
$string['chat_ratelimit'] = 'You are asking questions too fast. Please wait a moment.';

// Versioning
$string['version_history'] = 'Version History';
$string['loading_versions'] = 'Loading versions...';
$string['version_history_help'] = 'View and restore previous versions of generated content. Up to 10 versions are kept.';
$string['no_versions'] = 'No previous versions found.';
$string['version'] = 'Version';
$string['current'] = 'Current';
$string['restore'] = 'Restore';
$string['restore_confirm'] = 'Are you sure you want to restore this version? It will become the current active version.';
$string['restore_success'] = 'Version restored successfully!';
$string['prompt'] = 'Prompt';
$string['content_type'] = 'Content Type';

// Errors
$string['error_title'] = 'Error';
$string['ratelimit_exceeded'] = 'Rate limit exceeded. You have made too many requests. Please wait before trying again.';
$string['invalidurl'] = 'Invalid URL configuration';
$string['ssrfblocked'] = 'Request blocked for security reasons';
$string['apierror'] = 'API Error: {$a}';
$string['apiinvalidresponse'] = 'Invalid response from API';
$string['invalidjson'] = 'Invalid JSON response from API';
$string['apitimeout'] = 'API request timed out';
$string['maxretries'] = 'Maximum retry attempts reached';
$string['networkerror'] = 'Network error occurred';
$string['contentgenerationfailed'] = 'Content generation failed';
$string['api_request_failed'] = 'API request failed. Please try again later.';
$string['generation_in_progress'] = 'Content generation is in progress...';
$string['no_versions_available'] = 'No previous versions available.';

// Content Type Selection
$string['select_type'] = 'Select content type';

// Flashcard Navigation
$string['next_card'] = 'Next card';
$string['prev_card'] = 'Previous card';
$string['finish'] = 'Finish';
$string['finished'] = 'Finished!';
$string['deck_finished'] = 'You have completed all flashcards!';
$string['card_of'] = 'Card {$a->current} of {$a->total}';
$string['flip_card'] = 'Flip card';
$string['restart_deck'] = 'Restart';



