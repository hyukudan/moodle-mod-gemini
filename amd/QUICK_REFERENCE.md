# AMD Modules Quick Reference

## Building

```bash
# First time setup
npm install

# Build all modules
npm run build

# Auto-rebuild on changes
npm run watch

# Lint code
npm run lint
```

## Using in PHP

### Content Generator (Wizard Page)

```php
$PAGE->requires->js_call_amd('mod_gemini/generator', 'init', [[
    'geminiId' => $gemini->id,
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php'),
    'strings' => [
        'generating_msg' => get_string('generating_msg', 'mod_gemini'),
        'generate_btn' => get_string('generate_btn', 'mod_gemini'),
        'enter_topic' => get_string('enter_topic', 'mod_gemini'),
        'task_queued' => get_string('task_queued', 'mod_gemini'),
        'error_prefix' => get_string('error_prefix', 'mod_gemini'),
    ]
]]);
```

### Teaching Tools (Teacher Control Panel)

```php
$PAGE->requires->js_call_amd('mod_gemini/tools', 'init', [[
    'geminiId' => $gemini->id,
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php'),
    'rawContent' => $content->content,
    'strings' => [
        'saving' => get_string('saving', 'mod_gemini'),
        'save_changes' => get_string('save_changes', 'mod_gemini'),
        'confirm_regenerate' => get_string('confirm_regenerate', 'mod_gemini'),
    ]
]]);
```

### Flashcard Viewer

```php
$PAGE->requires->js_call_amd('mod_gemini/viewer', 'init', [[
    'geminiId' => $gemini->id,
    'totalCards' => count($data->cards),
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php'),
    'strings' => [
        'next_card' => get_string('next_card', 'mod_gemini'),
        'finish' => get_string('finish', 'mod_gemini'),
        'deck_finished' => get_string('deck_finished', 'mod_gemini'),
    ]
]]);
```

## Module Functions

### generator.js

| Function | Description |
|----------|-------------|
| `init(params)` | Initialize generator module |
| `enqueueTask()` | Submit generation request |
| `checkStatus()` | Poll for task status |
| `setupTypeSelection()` | Handle type card clicks |
| `setupPromptChips()` | Handle prompt chips |

### tools.js

| Function | Description |
|----------|-------------|
| `init(params)` | Initialize tools module |
| `generateRubric()` | Generate assessment rubric |
| `saveEditedContent()` | Save manual edits |
| `regenerateContent()` | Delete and reset content |

### viewer.js

| Function | Description |
|----------|-------------|
| `init(params)` | Initialize viewer module |
| `flipCard($card)` | Toggle flashcard flip |
| `nextCard()` | Navigate to next card |
| `finishDeck()` | Record completion |

## Required Language Strings

Add to `lang/en/gemini.php`:

```php
// Generator
$string['generating_msg'] = 'Generating...';
$string['generate_btn'] = 'Generate with Gemini';
$string['enter_topic'] = 'Please enter a topic';
$string['task_queued'] = 'Task queued!';
$string['select_type'] = 'Please select a type';

// Tools
$string['saving'] = 'Saving...';
$string['save_changes'] = 'Save Changes';
$string['confirm_regenerate'] = 'Are you sure?';
$string['content_empty'] = 'Content cannot be empty';

// Viewer
$string['next_card'] = 'Next Card';
$string['finish'] = 'Finish';
$string['finished'] = 'Finished';
$string['deck_finished'] = 'Deck finished! Grade: 100%';
```

## Debugging

```javascript
// In browser console
require(['mod_gemini/generator'], function(Generator) {
    console.log(Generator);
});

// Check if module loaded
window.require.s.contexts._.defined['mod_gemini/generator']

// Force reload
window.location.reload(true);
```

## File Locations

```
mod/gemini/
├── amd/
│   ├── src/
│   │   ├── generator.js    ← Edit these
│   │   ├── tools.js
│   │   └── viewer.js
│   └── build/
│       ├── generator.min.js ← Auto-generated
│       ├── tools.min.js
│       └── viewer.min.js
├── Gruntfile.js
└── package.json
```

## Common Errors

| Error | Solution |
|-------|----------|
| Module not found | Run `npm run build` |
| Functions undefined | Check browser console |
| AJAX 403 | Check sesskey is passed |
| Changes not appearing | Clear caches + hard refresh |
| Grunt not found | Run `npm install` |

## Cache Clearing

```bash
# CLI
php admin/cli/purge_caches.php

# Browser
Ctrl + Shift + R (hard refresh)

# Moodle UI
Site admin > Development > Purge all caches
```

## Development Workflow

1. Edit `amd/src/*.js`
2. Run `npm run build` (or `npm run watch`)
3. Clear Moodle caches
4. Hard refresh browser (Ctrl+Shift+R)
5. Check console for errors
6. Test functionality

## Production Checklist

- [ ] All src files compiled to build
- [ ] No console errors
- [ ] Language strings added
- [ ] Grunt/npm in .gitignore
- [ ] Build files committed (for Moodle plugins)
- [ ] Documentation updated
- [ ] Version incremented

## Further Reading

- [Moodle JavaScript Modules](https://docs.moodle.org/dev/Javascript_Modules)
- [AMD Module Tutorial](https://docs.moodle.org/dev/Javascript/AMD)
- [Moodle Coding Style](https://docs.moodle.org/dev/Javascript_Coding_Style)
