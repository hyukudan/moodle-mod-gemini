# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**mod_gemini** is a Moodle activity module that enables teachers to generate AI-powered educational content using LLMs (Gemini, OpenAI-compatible APIs, or local models like Ollama/LM Studio).

- **Plugin type**: Activity module (`mod_gemini`)
- **Requires**: Moodle 4.4+, PHP 8.1+
- **License**: MIT

## Architecture

### Request Flow

```
Teacher clicks "Generate" → ajax.php queues adhoc task →
Moodle cron executes generate_content task →
gemini_client.php calls LLM API →
Content saved to gemini_content table →
view.php polls status via AJAX and displays result
```

### Key Components

| File | Purpose |
|------|---------|
| `view.php` | Main UI - dual interface (teacher wizard / student content display) |
| `ajax.php` | AJAX endpoints: generate, check_status, reset, update, tools_rubric, grade_completion |
| `classes/service/gemini_client.php` | LLM API client (OpenAI-compatible format) |
| `classes/task/generate_content.php` | Adhoc task for background content generation |
| `lib.php` | Moodle callbacks: add/update/delete instance, supports, pluginfile, grades |

### Database Schema (db/install.xml)

- **gemini**: Activity instances (name, intro, course)
- **gemini_content**: Generated content storage (type, JSON/HTML content, config, version, parent_id)
- **gemini_queue**: Background task queue (status: 0=pending, 1=processing, 2=done, -1=error; retries, progress, progressmsg)

### Content Types

The plugin generates 5 content types via `gemini_client.php`:

1. **presentation** - JSON slides with titles, HTML content, speaker notes, image prompts (rendered via Pollinations.ai)
2. **flashcards** - JSON Q&A pairs with 3D flip animation, keyboard navigation, gradebook integration
3. **summary** - HTML with auto-glossary (`<abbr>` tags for terms)
4. **audio** - Script text + TTS conversion to MP3 via OpenAI-compatible endpoint
5. **quiz** - Moodle XML format (importable to Question Bank)

## Development

### Installation (in Moodle)

```bash
# Clone into Moodle's mod directory
cd /path/to/moodle/mod
git clone <repo-url> gemini

# Run upgrade via Moodle admin or CLI
php admin/cli/upgrade.php
```

### Configuration

Site Administration → Plugins → Activity modules → Gemini AI Content:
- API Key, Base URL (default: Gemini API), Model (default: gemini-3.0-flash)
- TTS URL, Model, Voice for audio generation

### Testing Changes

No automated test suite. Test manually in Moodle:

1. Create a course and add "Gemini AI Content" activity
2. As teacher: test content generation for each type
3. As student: verify content display and interactions
4. Check `gemini_queue` table for task status
5. Monitor Moodle's adhoc task logs

### Purging the Cron Queue

```bash
# Run adhoc tasks manually
php admin/cli/adhoc_task.php --execute
```

### Database Schema Changes

Edit `db/install.xml` using Moodle's XMLDB editor, then bump version in `version.php`.

## Code Patterns

### AJAX Handler Pattern (ajax.php)

```php
require_sesskey();  // CSRF protection
$action = required_param('action', PARAM_ALPHA);
$cmid = required_param('cmid', PARAM_INT);
require_capability('mod/gemini:generate', $context);
// ... handle action
echo json_encode($response);
```

### Background Task Pattern

Queue in ajax.php → Execute in `classes/task/generate_content.php`:
- Reads queue record, sets status to processing
- Calls appropriate `gemini_client` method
- Saves content to `gemini_content` table
- Updates queue status to done/error

### LLM Client Pattern (gemini_client.php)

All methods use OpenAI-compatible chat completions format:
```php
$this->generate_content($systemprompt, $userprompt, $json_mode);
```

## Moodle APIs Used

- **Privacy API** (`classes/privacy/provider.php`): GDPR data export/deletion
- **Backup API** (`backup/`, `restore/`): Course backup/restore support
- **Gradebook**: Flashcard completion awards 100% grade
- **Completion**: Tracks view-based completion
- **Events** (`classes/event/`): Logs course_module_viewed
- **Scheduled tasks** (`db/tasks.php`): Daily cleanup at 3 AM (purges 30-day-old queue records)

## Internationalization

Language strings in `lang/en/gemini.php` and `lang/es/gemini.php`. Use `get_string('key', 'mod_gemini')` for all user-facing text.

## Security Considerations

- All AJAX calls require `sesskey` verification
- Teacher actions require `mod/gemini:generate` capability
- File serving (`gemini_pluginfile`) validates context permissions
- User input sanitized via `required_param()` / `optional_param()` with type constants
- **XSS Protection**: LLM-generated HTML sanitized via `format_text()` with context
- **SSRF Protection**: API URLs validated against private IP ranges (gemini_client.php)
- **Rate Limiting**: Max 10 API requests per user per hour (ajax.php)

## Moodle Infrastructure Files

| File | Purpose |
|------|---------|
| `db/access.php` | Capability definitions (addinstance, view, generate, viewanalytics) |
| `db/upgrade.php` | Database migration script for version upgrades |
| `db/messages.php` | Message provider definitions (generation_complete, generation_failed) |
| `db/caches.php` | MUC cache definitions (content, ratelimit) |
| `db/tasks.php` | Scheduled task definitions (cleanup_task) |
