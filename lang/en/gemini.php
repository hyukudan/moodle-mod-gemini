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
$string['settings_model_desc'] = 'The model ID to use. E.g., "gemini-1.5-flash", "gpt-4", "llama-3-8b-instruct".';
$string['settings_temperature'] = 'Temperature';
$string['settings_temperature_desc'] = 'Creativity level (0.0 to 1.0).';
