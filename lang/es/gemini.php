<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Contenido IA Gemini';
$string['modulename'] = 'Contenido Gemini';
$string['modulename_help'] = 'El módulo de Contenido Gemini permite a los profesores generar recursos educativos como presentaciones, resúmenes, tarjetas de estudio (flashcards) y audio utilizando modelos de IA (Gemini o LLMs locales).';
$string['modulenameplural'] = 'Contenidos Gemini';
$string['gemini:addinstance'] = 'Añadir un nuevo recurso de Contenido Gemini';
$string['gemini:view'] = 'Ver Contenido Gemini';

// Settings
$string['settings_apikey'] = 'Clave API';
$string['settings_apikey_desc'] = 'Introduce tu Clave API (Google Gemini o compatible con LM Studio/OpenAI).';
$string['settings_baseurl'] = 'URL Base';
$string['settings_baseurl_desc'] = 'La URL del endpoint de la API. Para Gemini (compatibilidad OpenAI): "https://generativelanguage.googleapis.com/v1beta/openai/". Para LM Studio: "http://localhost:1234/v1".';
$string['settings_model'] = 'Nombre del Modelo';
$string['settings_model_desc'] = 'El ID del modelo a utilizar. Ej: "gemini-1.5-flash", "gpt-4", "llama-3-8b-instruct".';
$string['settings_temperature'] = 'Temperatura';
$string['settings_temperature_desc'] = 'Nivel de creatividad (0.0 a 1.0).';
