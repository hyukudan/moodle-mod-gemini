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
 * Spanish strings for mod_gemini.
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Contenido IA Gemini';
$string['modulename'] = 'Contenido Gemini';
$string['modulename_help'] = 'El módulo de Contenido Gemini permite a los profesores generar recursos educativos como presentaciones, resúmenes, tarjetas de estudio (flashcards) y audio utilizando modelos de IA (Gemini o LLMs locales).';
$string['modulenameplural'] = 'Contenidos Gemini';
$string['gemini:addinstance'] = 'Añadir un nuevo recurso de Contenido Gemini';
$string['gemini:view'] = 'Ver Contenido Gemini';
$string['gemini:generate'] = 'Generar contenido IA';
$string['gemini:viewanalytics'] = 'Ver estadísticas de uso';

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
$string['prompt_inspiration'] = 'Prueba estos:';
$string['prompt_explain_child'] = 'Explícalo como a un niño de 10';
$string['prompt_critical_analysis'] = 'Análisis Crítico';
$string['prompt_real_world'] = 'Ejemplos del Mundo Real';
$string['prompt_timeline'] = 'Cronología de Eventos';

// Tasks
$string['cleanup_task'] = 'Limpiar registros antiguos de generación Gemini';

// Privacy
$string['privacy:metadata:gemini_queue'] = 'Almacena prompts temporales y estado de las tareas de generación de contenido.';
$string['privacy:metadata:gemini_queue:userid'] = 'El usuario que solicitó la generación de contenido.';
$string['privacy:metadata:gemini_queue:prompt'] = 'El texto prompt enviado a la IA.';
$string['privacy:metadata:gemini_queue:timecreated'] = 'Cuándo se realizó la solicitud.';

// Chat Feature
$string['chat_with_ai'] = 'Preguntar al Tutor IA';
$string['chat_panel_title'] = 'Chat con Tutor IA';
$string['chat_placeholder'] = 'Haz una pregunta sobre este contenido...';
$string['chat_send'] = 'Enviar';
$string['chat_thinking'] = 'Pensando...';
$string['chat_error'] = 'Lo siento, no pude procesar tu pregunta. Por favor intenta de nuevo.';
$string['chat_welcome'] = 'Hola! Soy tu tutor IA. Hazme cualquier pregunta sobre el contenido de arriba.';
$string['chat_no_content'] = 'No hay contenido disponible para discutir.';
$string['chat_clear'] = 'Limpiar Chat';
$string['chat_cleared'] = 'Historial de chat limpiado.';
$string['chat_ratelimit'] = 'Estás haciendo preguntas muy rápido. Por favor espera un momento.';

// Versioning
$string['version_history'] = 'Historial de Versiones';
$string['loading_versions'] = 'Cargando versiones...';
$string['version_history_help'] = 'Ver y restaurar versiones anteriores del contenido generado. Se mantienen hasta 10 versiones.';
$string['no_versions'] = 'No se encontraron versiones anteriores.';
$string['version'] = 'Versión';
$string['current'] = 'Actual';
$string['restore'] = 'Restaurar';
$string['restore_confirm'] = '¿Está seguro de que desea restaurar esta versión? Se convertirá en la versión activa actual.';
$string['restore_success'] = '¡Versión restaurada exitosamente!';
$string['prompt'] = 'Prompt';
$string['content_type'] = 'Tipo de Contenido';

// Errors
$string['error_title'] = 'Error';
$string['ratelimit_exceeded'] = 'Límite de peticiones excedido. Has hecho demasiadas solicitudes. Por favor espera antes de intentarlo de nuevo.';
$string['invalidurl'] = 'Configuración de URL inválida';
$string['ssrfblocked'] = 'Petición bloqueada por razones de seguridad';
$string['apierror'] = 'Error de API: {$a}';
$string['apiinvalidresponse'] = 'Respuesta inválida de la API';
$string['invalidjson'] = 'Respuesta JSON inválida de la API';
$string['apitimeout'] = 'La petición a la API ha excedido el tiempo de espera';
$string['maxretries'] = 'Se alcanzó el número máximo de reintentos';
$string['networkerror'] = 'Ocurrió un error de red';
$string['contentgenerationfailed'] = 'La generación de contenido ha fallado';
$string['api_request_failed'] = 'La solicitud a la API falló. Por favor intenta más tarde.';
$string['generation_in_progress'] = 'La generación de contenido está en progreso...';
$string['no_versions_available'] = 'No hay versiones anteriores disponibles.';

// Content Type Selection
$string['select_type'] = 'Seleccionar tipo de contenido';

// Flashcard Navigation
$string['next_card'] = 'Siguiente tarjeta';
$string['prev_card'] = 'Tarjeta anterior';
$string['finish'] = 'Finalizar';
$string['finished'] = '¡Terminado!';
$string['deck_finished'] = '¡Has completado todas las tarjetas!';
$string['card_of'] = 'Tarjeta {$a->current} de {$a->total}';
$string['flip_card'] = 'Voltear tarjeta';
$string['restart_deck'] = 'Reiniciar';

