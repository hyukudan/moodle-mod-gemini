<?php
namespace mod_gemini\service;

defined('MOODLE_INTERNAL') || die();

use core\http_client;

/**
 * Service to handle communication with Gemini/OpenAI-compatible APIs.
 */
class gemini_client {

    private $apikey;
    private $baseurl;
    private $model;
    private $temperature;

    public function __construct() {
        $this->apikey = get_config('mod_gemini', 'apikey');
        $this->baseurl = get_config('mod_gemini', 'baseurl');
        $this->model = get_config('mod_gemini', 'model');
        $this->temperature = (float)get_config('mod_gemini', 'temperature');

        // Ensure base URL ends with slash.
        if (substr($this->baseurl, -1) !== '/') {
            $this->baseurl .= '/';
        }

        // SSRF Protection: Validate URL is safe.
        $this->validate_url($this->baseurl);
    }

    /**
     * Validates a URL to prevent SSRF attacks.
     * Blocks both IPv4 and IPv6 private/internal IP ranges.
     *
     * @param string $url The URL to validate.
     * @throws \moodle_exception If the URL is unsafe.
     */
    private function validate_url($url) {
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            debugging('Invalid URL format: ' . $url, DEBUG_DEVELOPER);
            throw new \moodle_exception('invalidurl', 'mod_gemini');
        }

        $host = $parsed['host'];

        // Block private/internal IP ranges (SSRF protection for both IPv4 and IPv6).
        // First, try to resolve the hostname to an IP address.
        // We need to check both IPv4 and IPv6 addresses.

        // Get all IP addresses for the host (including IPv6).
        $ips = [];

        // Try IPv4 resolution.
        $ipv4 = gethostbyname($host);
        if ($ipv4 !== $host) {
            $ips[] = $ipv4;
        }

        // Try IPv6 resolution using dns_get_record.
        $dns_records = @dns_get_record($host, DNS_AAAA);
        if ($dns_records !== false) {
            foreach ($dns_records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // If host is already an IP address (IPv4 or IPv6), validate it directly.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        }

        // Validate each resolved IP address.
        foreach ($ips as $ip) {
            // Use filter_var with flags to block private and reserved IP ranges.
            // This automatically handles:
            // IPv4: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 127.0.0.0/8, etc.
            // IPv6: ::1 (localhost), fe80::/10 (link-local), fc00::/7 (unique local), fd00::/8 (private), etc.
            $is_valid_public_ip = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($is_valid_public_ip === false) {
                debugging('SSRF blocked: URL resolves to private/internal IP address (' . $ip . ')', DEBUG_DEVELOPER);
                throw new \moodle_exception('ssrfblocked', 'mod_gemini');
            }
        }

        // Optional: Require HTTPS for production.
        if (isset($parsed['scheme']) && $parsed['scheme'] !== 'https' && $parsed['scheme'] !== 'http') {
            debugging('Invalid URL scheme: ' . $parsed['scheme'], DEBUG_DEVELOPER);
            throw new \moodle_exception('invalidurl', 'mod_gemini');
        }
    }

    /**
     * Sends a chat completion request to the API.
     *
     * @param array $messages Array of message objects [['role' => 'user', 'content' => '...']]
     * @param string $response_format 'text' or 'json_object' (if supported by backend)
     * @return string The generated text content.
     * @throws \moodle_exception
     */
    public function generate_content($messages, $response_format = 'text') {
        global $CFG;

        // Construct endpoint URL.
        // Assuming OpenAI compatible endpoint: v1/chat/completions
        // If Google Gemini native URL is used without /openai/ suffix, this might need adjustment,
        // but our README instructions suggest using the OpenAI compat layer of Gemini or LM Studio.
        $url = $this->baseurl . 'chat/completions';

        // Headers
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey
        ];

        // Body
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            // 'max_tokens' => 2048, // Optional: let model decide or config later
        ];

        if ($response_format === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        // Using curl class from Moodle
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        
        // Add headers
        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => 90 // Wait up to 90s for generation
        ];

        $response_json = $curl->post($url, json_encode($payload), $options);
        $info = $curl->get_info();

        if ($info['http_code'] !== 200) {
            // Log error for debugging
            debugging('Gemini API Error (HTTP ' . $info['http_code'] . '): ' . $response_json, DEBUG_DEVELOPER);
            throw new \moodle_exception('apierror', 'mod_gemini', '', $info['http_code']);
        }

        $response = json_decode($response_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjson', 'mod_gemini');
        }

        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        } else {
            throw new \moodle_exception('apiinvalidresponse', 'mod_gemini');
        }
    }

    /**
     * Helper to generate specific content types.
     */
    public function generate_presentation($topic) {
        $system_prompt = "You are an educational content creator. Create a presentation about the given topic. 
        Return ONLY a valid JSON object with the following structure:
        {
            \"title\": \"Presentation Title\",
            \"slides\": [
                {
                    \"title\": \"Slide Title\",
                    \"content\": \"<ul><li>Point 1</li><li>Point 2</li></ul>\",
                    \"image_prompt\": \"A short description of an image suitable for this slide (e.g., 'ancient roman colosseum sunny day')\",
                    \"notes\": \"Speaker notes for this slide\"
                }
            ]
        }
        Ensure the content is HTML safe.";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Create a presentation about: " . $topic]
        ];

        return $this->generate_content($messages, 'json_object');
    }

    public function generate_flashcards($topic) {
        $system_prompt = "You are an educational tool. Create a set of 10 flashcards for the given topic.
        Return ONLY a valid JSON object with the following structure:
        {
            \"topic\": \"Topic Name\",
            \"cards\": [
                {\"front\": \"Question or Term\", \"back\": \"Answer or Definition\"}
            ]
        }";

        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Create flashcards about: " . $topic]
        ];

        return $this->generate_content($messages, 'json_object');
    }

    public function generate_quiz_questions($topic) {
        $system_prompt = "You are an expert teacher. Create a multiple-choice quiz (10 questions) about the topic.
        Return ONLY a valid JSON object with the following structure:
        {
            \"questions\": [
                {
                    \"name\": \"Short question name\",
                    \"questiontext\": \"The full question text\",
                    \"correct_answer\": \"The correct option\",
                    \"incorrect_answers\": [\"Wrong 1\", \"Wrong 2\", \"Wrong 3\"]
                }
            ]
        }";

        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Create a quiz about: " . $topic]
        ];

        return $this->generate_content($messages, 'json_object');
    }

    /**
     * Interactive Chat: Student asks a question about the generated content.
     */
    public function chat_with_content($context_content, $student_question, $chat_history = []) {
        $system_prompt = "You are a helpful educational tutor. You have provided the following content to the student:\n\n" . 
                         $context_content . "\n\n" .
                         "Your task is to answer the student's questions ONLY about this specific content. 
                         If the question is unrelated, politely redirect them. Keep answers concise and educational.";
        
        $messages = [['role' => 'system', 'content' => $system_prompt]];
        
        // Add history if any (for multi-turn chat)
        foreach ($chat_history as $msg) {
            $messages[] = $msg;
        }

        $messages[] = ['role' => 'user', 'content' => $student_question];

        return $this->generate_content($messages, 'text');
    }

    public function generate_rubric($topic) {
        $system_prompt = "You are an expert pedagogue. Create a detailed assessment Rubric for a student assignment related to the topic.
        Output ONLY an HTML Table representing the rubric.
        Columns should be: Criteria (30%), Needs Improvement (0-5), Satisfactory (6-8), Excellent (9-10).
        Add a brief description of a suggested assignment above the table.";

        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Create a rubric for: " . $topic]
        ];

        return $this->generate_content($messages, 'text');
    }

    public function generate_summary($topic) {
        $system_prompt = "You are an expert summarizer. Provide a comprehensive summary of the topic. 
        Use HTML formatting (<h3>, <p>, <ul>, <strong>) to structure the text clearly. 
        
        CRITICAL: Identify 3-5 key complex terms or concepts in the text and wrap them in <abbr title=\"Short, clear definition of the term\">Term</abbr> tags. This will act as an integrated glossary for students.
        
        Do not use Markdown, return HTML only.";

        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Summarize this topic: " . $topic]
        ];

        return $this->generate_content($messages, 'text');
    }

    /**
     * Generates audio from text using OpenAI-compatible TTS endpoint.
     * 
     * @param string $input_text The text to synthesize.
     * @return string Binary MP3 data.
     * @throws \moodle_exception
     */
    public function generate_speech_mp3($input_text) {
        global $CFG;

        $tts_url = get_config('mod_gemini', 'ttsurl');
        if (empty($tts_url)) {
            // Fallback: try to construct it from baseurl.
            // Remove 'chat/completions' if present, or just append 'audio/speech'.
            $base = str_replace('chat/completions', '', $this->baseurl);
            // Ensure trailing slash.
            if (substr($base, -1) !== '/') {
                $base .= '/';
            }
            $tts_url = $base . 'audio/speech';
        }

        // SSRF Protection: Validate TTS URL.
        $this->validate_url($tts_url);

        $model = get_config('mod_gemini', 'ttsmodel') ?: 'tts-1';
        $voice = get_config('mod_gemini', 'ttsvoice') ?: 'alloy';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey
        ];

        $payload = [
            'model' => $model,
            'input' => $input_text,
            'voice' => $voice,
            'response_format' => 'mp3'
        ];

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        
        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => 120 // Audio generation might take longer
        ];

        // We expect binary output
        $response = $curl->post($tts_url, json_encode($payload), $options);
        $info = $curl->get_info();

        if ($info['http_code'] !== 200) {
            // If it's text error, try to read it
            debugging('TTS API Error (HTTP ' . $info['http_code'] . '): ' . substr($response, 0, 200), DEBUG_DEVELOPER);
            throw new \moodle_exception('apierror', 'mod_gemini', '', $info['http_code']);
        }

        return $response; // Binary data
    }
}

