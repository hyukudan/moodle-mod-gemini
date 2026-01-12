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
$string['settings_model_desc'] = 'El ID del modelo a utilizar. Ej: "gemini-3.0-flash", "gemini-1.5-pro", "llama-3-8b-instruct".';
$string['settings_temperature'] = 'Temperatura';
$string['settings_temperature_desc'] = 'Nivel de creatividad (0.0 a 1.0).';

// UI Strings
$string['teacher_controls'] = '🔧 Controles del Profesor';
$string['generate_rubric'] = '💡 Generar Rúbrica';
$string['edit_content'] = '✏️ Editar Contenido';
$string['regenerate'] = '🔄 Regenerar (Reset)';
$string['regenerate_confirm'] = '¿Estás seguro? Esto BORRARÁ el contenido actual permanentemente.';
$string['ai_teaching_assistant'] = '🤖 Asistente Docente IA';
$string['create_rubric'] = '📊 Crear Rúbrica de Evaluación';
$string['thinking'] = 'Pensando...';
$string['close'] = 'Cerrar';
$string['save_changes'] = 'Guardar Cambios';
$string['saving'] = 'Guardando...';
$string['edit_modal_title'] = 'Editar Contenido Generado';
$string['edit_modal_help'] = 'Edita el contenido crudo abajo. Para Presentaciones/Flashcards, asegura que el JSON sea válido.';
$string['no_content_yet'] = 'Aún no hay contenido. Usa el asistente para crearlo.';
$string['content_creator'] = 'Creador de Contenido Gemini';
$string['generate_btn'] = '✨ Generar con Gemini';
$string['generating_msg'] = 'Generando... (esto puede tardar un minuto)';
$string['contacting_msg'] = 'Contactando con Gemini... No cierres esta página.';
$string['success_reload'] = '¡Contenido generado! Recargando...';
$string['error_prefix'] = 'Error: ';
$string['enter_topic'] = 'Por favor introduce un tema.';
$string['configure_type'] = 'Configura tu {$a}';
$string['topic_prompt'] = 'Tema / Prompt';
$string['topic_placeholder'] = 'Ej: La historia del Imperio Romano...';

