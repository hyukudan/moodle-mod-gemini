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
 * Admin settings for mod_gemini
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // --- Text/Chat Configuration ---
    $settings->add(new admin_setting_heading('mod_gemini/header_chat', 'Text Generation (LLM)', ''));

    $settings->add(new admin_setting_configpassword(
        'mod_gemini/apikey',
        get_string('settings_apikey', 'mod_gemini'),
        get_string('settings_apikey_desc', 'mod_gemini'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'mod_gemini/baseurl',
        get_string('settings_baseurl', 'mod_gemini'),
        get_string('settings_baseurl_desc', 'mod_gemini'),
        'https://generativelanguage.googleapis.com/v1beta/openai/',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'mod_gemini/model',
        get_string('settings_model', 'mod_gemini'),
        get_string('settings_model_desc', 'mod_gemini'),
        'gemini-3.0-flash',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_gemini/temperature',
        get_string('settings_temperature', 'mod_gemini'),
        get_string('settings_temperature_desc', 'mod_gemini'),
        '0.7',
        PARAM_FLOAT
    ));

    // --- Audio/TTS Configuration ---
    $settings->add(new admin_setting_heading('mod_gemini/header_audio', 'Audio Generation (TTS)', 'Settings for Text-to-Speech conversion.'));

    $settings->add(new admin_setting_configtext(
        'mod_gemini/ttsurl',
        'TTS API URL',
        'Endpoint for speech generation. Standard OpenAI format is ".../v1/audio/speech". Leave empty to use the Base URL above.',
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'mod_gemini/ttsmodel',
        'TTS Model',
        'Model for audio generation (e.g., "tts-1", "tts-1-hd").',
        'tts-1',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_gemini/ttsvoice',
        'TTS Voice',
        'Voice ID (e.g., "alloy", "echo", "fable", "onyx", "nova", "shimmer").',
        'alloy',
        PARAM_TEXT
    ));
}