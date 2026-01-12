# AMD Module Migration Guide

This guide explains how to replace the inline JavaScript in view.php with the new AMD modules.

## Overview

Three AMD modules have been created to replace inline JavaScript:

1. **mod_gemini/generator** - Content generation wizard
2. **mod_gemini/tools** - Teaching tools (rubric, edit, regenerate)
3. **mod_gemini/viewer** - Content viewers (flashcards, presentations, etc.)

## Integration Steps

### Step 1: Remove Inline JavaScript from view.php

Remove the following inline `<script>` blocks:

**Lines 121-236:** Teacher control panel scripts (Tools)
**Lines 329-398:** Flashcard viewer scripts
**Lines 545-711:** Content generator wizard scripts

### Step 2: Add AMD Module Calls

#### For Teacher Control Panel (when content exists)

After line 119 (before the closing PHP tag), add:

```php
// Initialize teaching tools module
$PAGE->requires->js_call_amd('mod_gemini/tools', 'init', [[
    'geminiId' => $gemini->id,
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php'),
    'rawContent' => $content->content,
    'strings' => [
        'content_empty' => get_string('content_empty', 'mod_gemini'),
        'saving' => get_string('saving', 'mod_gemini'),
        'save_changes' => get_string('save_changes', 'mod_gemini'),
        'confirm_regenerate' => get_string('confirm_regenerate', 'mod_gemini'),
    ]
]]);
```

#### For Flashcard Viewer (in case 'flashcards')

After line 327 (before the closing PHP for flashcards), add:

```php
// Initialize flashcard viewer
$PAGE->requires->js_call_amd('mod_gemini/viewer', 'init', [[
    'geminiId' => $gemini->id,
    'totalCards' => count($data->cards),
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php'),
    'strings' => [
        'next_card' => get_string('next_card', 'mod_gemini'),
        'finish' => get_string('finish', 'mod_gemini'),
        'finished' => get_string('finished', 'mod_gemini'),
        'deck_finished' => get_string('deck_finished', 'mod_gemini'),
        'saving' => get_string('saving', 'mod_gemini'),
    ]
]]);
```

#### For Content Generator Wizard (when no content exists)

After line 536 (after the generation-status div), add:

```php
// Initialize content generator
$PAGE->requires->js_call_amd('mod_gemini/generator', 'init', [[
    'geminiId' => $gemini->id,
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php'),
    'strings' => [
        'generating_msg' => get_string('generating_msg', 'mod_gemini'),
        'contacting_msg' => get_string('contacting_msg', 'mod_gemini'),
        'task_queued' => get_string('task_queued', 'mod_gemini'),
        'generate_btn' => get_string('generate_btn', 'mod_gemini'),
        'error_title' => get_string('error', 'core'),
        'error_prefix' => get_string('error_prefix', 'mod_gemini'),
        'enter_topic' => get_string('enter_topic', 'mod_gemini'),
        'select_type' => get_string('select_type', 'mod_gemini'),
        'ok' => get_string('ok', 'core'),
    ]
]]);
```

### Step 3: Add Required Language Strings

Add these to `lang/en/gemini.php` if not already present:

```php
$string['content_empty'] = 'Content cannot be empty';
$string['saving'] = 'Saving...';
$string['save_changes'] = 'Save Changes';
$string['confirm_regenerate'] = 'Are you sure? This will DELETE the current content permanently.';
$string['next_card'] = 'Next Card';
$string['finish'] = 'Finish';
$string['finished'] = 'Finished';
$string['deck_finished'] = 'Deck finished! Grade recorded: 100%';
$string['task_queued'] = 'Task queued! Generating content in background...';
$string['select_type'] = 'Please select a content type';
```

### Step 4: Build AMD Modules

```bash
cd /path/to/mod/gemini
npm install
npm run build
```

### Step 5: Clear Moodle Caches

```bash
# From Moodle root
php admin/cli/purge_caches.php

# Or via browser
# Site administration > Development > Purge all caches
```

## Testing Checklist

- [ ] Content generator wizard loads and displays type cards
- [ ] Type selection shows generation form
- [ ] Prompt chips work and fill textarea
- [ ] Generate button enqueues tasks
- [ ] Status polling updates UI
- [ ] Teacher tools button opens modal
- [ ] Rubric generator works
- [ ] Edit content opens and saves
- [ ] Regenerate deletes and reloads
- [ ] Flashcards flip on click
- [ ] Flashcard navigation works
- [ ] Finish button records grade
- [ ] No JavaScript errors in console

## Benefits of AMD Modules

1. **Code Organization**: Separated concerns into logical modules
2. **Reusability**: Can use these modules on other pages if needed
3. **Caching**: Minified modules are cached by browser
4. **Dependencies**: Proper dependency injection (jQuery, Ajax, Notification)
5. **Standards**: Follows Moodle JavaScript coding standards
6. **Maintainability**: Easier to test and debug

## Troubleshooting

### Module not loading
- Check browser console for errors
- Verify AMD files exist in `amd/build/`
- Clear Moodle caches
- Check file permissions

### Functions not working
- Verify AJAX URL is correct
- Check sesskey is being passed
- Look for JavaScript errors in console
- Verify all required parameters are passed to init()

### Changes not appearing
1. Clear browser cache (Ctrl+Shift+R)
2. Purge Moodle caches
3. Rebuild AMD modules: `npm run build`
4. Check that you edited `src/` not `build/`

## Advanced: Using Moodle's AJAX Services

For better integration, consider creating Moodle external functions:

1. Create `classes/external/enqueue_task.php`
2. Define in `db/services.php`
3. Update generator.js to use `core/ajax` with methodname

Example:
```javascript
var promises = Ajax.call([{
    methodname: 'mod_gemini_enqueue_task',
    args: {
        geminiid: self.config.geminiId,
        type: type,
        prompt: prompt
    }
}]);

promises[0].done(function(response) {
    // Handle success
}).fail(function(error) {
    // Handle error
});
```

## Next Steps

After successful migration:

1. Remove commented-out inline scripts from view.php
2. Consider moving presentation/audio viewers to AMD if needed
3. Add unit tests for AMD modules
4. Update plugin documentation
5. Commit changes to version control
