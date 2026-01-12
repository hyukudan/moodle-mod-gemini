<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Gemini AI Content';
$string['modulename'] = 'Gemini Content';
$string['modulename_help'] = ' The Gemini Content module allows teachers to generate educational resources such as presentations, summaries, flashcards, and audio using AI models (Gemini or Local LLMs).';
$string['modulenameplural'] = 'Gemini Contents';
$string['gemini:addinstance'] = 'Add a new Gemini Content resource';
$string['gemini:view'] = 'View Gemini Content';

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



