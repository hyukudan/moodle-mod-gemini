# JavaScript Extraction Summary

This document maps the inline JavaScript from view.php to the new AMD modules.

## Files Created

### 1. /amd/src/generator.js
**Purpose:** Content generation wizard UI

**Extracted from view.php lines 545-711**

**Key functions:**
- `init(params)` - Initialize the generator module
- `setupTypeSelection()` - Handle content type card clicks
- `setupPromptChips()` - Handle prompt inspiration chips
- `enqueueTask()` - Submit generation request
- `enqueueTaskLegacy()` - Fallback to ajax.php
- `checkStatus()` - Poll for task completion
- `startStatusPolling()` - Start 5-second polling interval

**Dependencies:**
- jquery
- core/ajax (with fallback to fetch)
- core/notification

**Configuration parameters:**
```javascript
{
    geminiId: int,           // Gemini instance ID
    ajaxUrl: string,         // Path to ajax.php
    strings: {               // Localized strings
        generating_msg,
        contacting_msg,
        task_queued,
        generate_btn,
        error_title,
        error_prefix,
        enter_topic,
        select_type,
        ok
    }
}
```

### 2. /amd/src/tools.js
**Purpose:** Teaching assistant tools

**Extracted from view.php lines 121-236**

**Key functions:**
- `init(params)` - Initialize the tools module
- `setupRubricGenerator()` - Handle rubric generation modal
- `generateRubric()` - Call AI to generate rubric
- `setupContentEditor()` - Handle content editing modal
- `saveEditedContent()` - Save edited content
- `setupRegenerate()` - Handle regenerate/reset
- `regenerateContent()` - Delete content and reload

**Dependencies:**
- jquery
- core/ajax
- core/notification
- core/modal_factory (for future modal improvements)
- core/modal_events

**Configuration parameters:**
```javascript
{
    geminiId: int,           // Gemini instance ID
    ajaxUrl: string,         // Path to ajax.php
    rawContent: string,      // JSON content for editing
    strings: {               // Localized strings
        content_empty,
        saving,
        save_changes,
        confirm_regenerate
    }
}
```

### 3. /amd/src/viewer.js
**Purpose:** Interactive content viewers

**Extracted from view.php lines 329-398**

**Key functions:**
- `init(params)` - Initialize the viewer module
- `setupFlashcards()` - Setup flashcard interactions
- `flipCard($card)` - Toggle card flip state
- `nextCard()` - Navigate to next card
- `previousCard()` - Navigate to previous card
- `updateNavigation()` - Update UI (counter, buttons)
- `finishDeck()` - Record completion grade

**Dependencies:**
- jquery
- core/notification

**Configuration parameters:**
```javascript
{
    geminiId: int,           // Gemini instance ID
    totalCards: int,         // Number of flashcards
    ajaxUrl: string,         // Path to ajax.php
    strings: {               // Localized strings
        next_card,
        finish,
        finished,
        deck_finished,
        saving
    }
}
```

## Build Configuration

### /Gruntfile.js
Standard Moodle Grunt configuration for:
- Compiling AMD modules from src/ to build/
- ESLint for code quality
- Watch mode for development

### /package.json
NPM package configuration with:
- Grunt dependencies
- Build scripts (build, watch, lint)
- Project metadata

## Code Improvements Over Inline JavaScript

### 1. Better Error Handling
- Uses Moodle's `core/notification` for consistent alerts
- Proper error propagation with try/catch
- Graceful degradation (fallback to legacy AJAX)

### 2. Proper Event Delegation
- Uses jQuery event binding instead of inline addEventListener
- Proper event namespacing for cleanup
- Keyboard accessibility maintained

### 3. Code Organization
- Separated concerns (generation, tools, viewing)
- Reusable functions
- Clear module interfaces

### 4. Moodle Integration
- Uses `core/ajax` for AJAX calls (with fallback)
- Proper sesskey handling via M.cfg.sesskey
- Follows Moodle coding standards

### 5. Maintainability
- JSDoc comments for all functions
- Clear parameter documentation
- Modular design for testing

## Not Yet Extracted (Keeping Inline)

The following remain as inline JavaScript for now:

1. **Presentation carousel** (lines 257-295)
   - Uses Bootstrap carousel, minimal JS needed
   - Mostly server-rendered HTML

2. **Audio player** (lines 408-438)
   - Native HTML5 audio, no custom JS

3. **Quiz display** (lines 441-468)
   - Static display, no interactivity

These can be extracted later if needed, but they don't have complex logic requiring AMD modules.

## Testing Notes

### Manual Testing Required

1. **Generator Module:**
   - Click type cards - form should appear
   - Click prompt chips - textarea should update
   - Generate content - should show status updates
   - Wait for completion - should reload page

2. **Tools Module:**
   - Click "Teaching Tools" - modal should open
   - Generate rubric - should show AI output
   - Click "Edit Content" - should show JSON editor
   - Save changes - should update and reload
   - Click "Regenerate" - should confirm and reset

3. **Viewer Module:**
   - Click flashcard - should flip
   - Press Enter/Space - should flip
   - Click Next - should show next card
   - Click Previous - should go back
   - Finish deck - should record grade

### Browser Console Checks

Look for:
- Module load errors
- Undefined function errors
- AJAX errors
- jQuery selector failures

### Common Issues

1. **Module not found:** Rebuild with `npm run build`
2. **Functions don't work:** Check browser console for errors
3. **AJAX fails:** Verify ajax.php path is correct
4. **Strings missing:** Add to lang/en/gemini.php

## Future Improvements

### 1. Migrate to Moodle External Functions
Replace ajax.php calls with proper external functions:
- `mod_gemini_enqueue_task`
- `mod_gemini_check_status`
- `mod_gemini_generate_rubric`
- `mod_gemini_update_content`
- `mod_gemini_reset_content`
- `mod_gemini_record_completion`

### 2. Add Unit Tests
Create Behat tests for:
- Content generation workflow
- Flashcard navigation
- Teacher tools

### 3. Extract Remaining Viewers
If presentation/audio need interactivity:
- Create presentation.js for slide navigation enhancements
- Create audio.js for advanced playback controls

### 4. Use Templates
Replace inline HTML with Mustache templates:
- Status messages
- Error alerts
- Loading spinners

### 5. Add Progress Indicators
Replace simple spinners with:
- Progress bars
- Task queues visualization
- Real-time updates via WebSockets/SSE

## Performance Benefits

1. **Minification:** ~30-40% size reduction in production
2. **Caching:** Browser caches .min.js files
3. **Lazy Loading:** Only loads modules when needed
4. **Code Splitting:** Separate concerns = smaller initial load

## Browser Compatibility

All modules support:
- Modern browsers (ES6+)
- IE11 (with Moodle's polyfills)
- Mobile browsers
- Accessibility standards (WCAG 2.1)
