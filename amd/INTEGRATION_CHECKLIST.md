# AMD Module Integration Checklist

## Pre-Integration

- [x] AMD modules created in /amd/src/
- [x] Build configuration created (Gruntfile.js, package.json)
- [x] Modules compiled to /amd/build/
- [x] Documentation written

## Step 1: Build Modules

```bash
cd /home/sergioc/desarrollo/moodle_mod-gemini
npm install
npm run build
```

- [ ] Dependencies installed successfully
- [ ] Build completed without errors
- [ ] Files exist in amd/build/

## Step 2: Add Language Strings

Edit `/lang/en/gemini.php` and add:

```php
// Generator strings
$string['generating_msg'] = '⏳ Generating...';
$string['generate_btn'] = '✨ Generate with Gemini';
$string['enter_topic'] = 'Please enter a topic or description';
$string['task_queued'] = 'Task queued! Generating content in background...';
$string['select_type'] = 'Please select a content type first';
$string['contacting_msg'] = 'Contacting Gemini AI...';

// Tools strings
$string['saving'] = 'Saving...';
$string['save_changes'] = 'Save Changes';
$string['confirm_regenerate'] = 'Are you sure? This will DELETE the current content permanently.';
$string['content_empty'] = 'Content cannot be empty';

// Viewer strings
$string['next_card'] = 'Next Card';
$string['finish'] = 'Finish';
$string['finished'] = 'Finished';
$string['deck_finished'] = 'Deck finished! Grade recorded: 100%';
```

- [ ] Language strings added

## Step 3: Modify view.php

### 3A. Remove Inline JavaScript

**Delete lines 121-236** (Teacher control panel scripts)
- [ ] Removed script for tools modal
- [ ] Removed script for edit modal
- [ ] Removed script for regenerate button

**Delete lines 329-398** (Flashcard viewer scripts)
- [ ] Removed flashcard flip logic
- [ ] Removed navigation logic
- [ ] Removed grade completion logic

**Delete lines 545-711** (Generator wizard scripts)
- [ ] Removed type selection logic
- [ ] Removed prompt chip logic
- [ ] Removed task enqueue logic
- [ ] Removed status polling logic

### 3B. Add AMD Module Calls

**After line 119** (Teacher control panel), add:

```php
// Initialize teaching tools module
$PAGE->requires->js_call_amd('mod_gemini/tools', 'init', [[
    'geminiId' => $gemini->id,
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php')->out(false),
    'rawContent' => $content->content,
    'strings' => [
        'content_empty' => get_string('content_empty', 'mod_gemini'),
        'saving' => get_string('saving', 'mod_gemini'),
        'save_changes' => get_string('save_changes', 'mod_gemini'),
        'confirm_regenerate' => get_string('confirm_regenerate', 'mod_gemini'),
    ]
]]);
```

- [ ] Tools module call added

**Inside flashcards case** (after line 327), add:

```php
// Initialize flashcard viewer
$PAGE->requires->js_call_amd('mod_gemini/viewer', 'init', [[
    'geminiId' => $gemini->id,
    'totalCards' => count($data->cards),
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php')->out(false),
    'strings' => [
        'next_card' => get_string('next_card', 'mod_gemini'),
        'finish' => get_string('finish', 'mod_gemini'),
        'finished' => get_string('finished', 'mod_gemini'),
        'deck_finished' => get_string('deck_finished', 'mod_gemini'),
        'saving' => get_string('saving', 'mod_gemini'),
    ]
]]);
```

- [ ] Viewer module call added

**After generation-status div** (around line 536), add:

```php
// Initialize content generator
$PAGE->requires->js_call_amd('mod_gemini/generator', 'init', [[
    'geminiId' => $gemini->id,
    'ajaxUrl' => new moodle_url('/mod/gemini/ajax.php')->out(false),
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

- [ ] Generator module call added

## Step 4: Clear Caches

```bash
# Via CLI
php admin/cli/purge_caches.php

# Or via browser
# Site admin > Development > Purge all caches
```

- [ ] Moodle caches purged

## Step 5: Test Functionality

### Generator Module Tests

- [ ] Open activity without content (teacher view)
- [ ] Type selection cards display correctly
- [ ] Click a type card → form appears
- [ ] Click prompt chip → textarea updates
- [ ] Enter topic and click Generate
- [ ] Status polling shows "Processing tasks"
- [ ] Page reloads when content is ready
- [ ] Check browser console: no errors

### Tools Module Tests

- [ ] Open activity with content (teacher view)
- [ ] Click "Teaching Tools" button → modal opens
- [ ] Click "Create Rubric" → loading spinner → rubric displays
- [ ] Click "Edit Content" → modal opens with JSON
- [ ] Edit JSON and click Save → page reloads with changes
- [ ] Click "Regenerate" → confirmation dialog
- [ ] Confirm → content deleted, page reloads
- [ ] Check browser console: no errors

### Viewer Module Tests

- [ ] Open flashcards activity (student view)
- [ ] Click flashcard → flips to back
- [ ] Press Enter/Space → flips card
- [ ] Click "Previous" → goes to previous card
- [ ] Click "Next Card" → goes to next card
- [ ] Navigate to last card → button says "Finish"
- [ ] Click "Finish" → grade recorded alert
- [ ] Check browser console: no errors

## Step 6: Browser Testing

Test in multiple browsers:

- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari (if available)
- [ ] Edge
- [ ] Mobile browser (iOS/Android)

## Step 7: Accessibility Testing

- [ ] Keyboard navigation works (Tab, Enter, Space)
- [ ] Screen reader compatible
- [ ] No color-only indicators
- [ ] Focus states visible
- [ ] ARIA labels present

## Step 8: Performance Testing

- [ ] Modules load quickly (check Network tab)
- [ ] No memory leaks (check Performance tab)
- [ ] Status polling doesn't overwhelm server
- [ ] Minified files are served in production

## Common Issues & Solutions

### Issue: Modules not loading

**Symptoms:** Console error: "Module not found"

**Solutions:**
1. Run `npm run build`
2. Clear Moodle caches
3. Check file permissions on amd/build/
4. Verify files exist: `ls -la amd/build/`

### Issue: Functions not working

**Symptoms:** Buttons don't respond, no errors

**Solutions:**
1. Check browser console for errors
2. Verify init() is being called
3. Check jQuery is loaded: `typeof jQuery` in console
4. Verify AJAX URL is correct

### Issue: AJAX calls failing

**Symptoms:** Network errors, 403/404 responses

**Solutions:**
1. Check ajax.php exists and is accessible
2. Verify sesskey is being passed
3. Check user has required capabilities
4. Look at Network tab in DevTools

### Issue: Changes not appearing

**Symptoms:** Code changes don't show up

**Solutions:**
1. Rebuild: `npm run build`
2. Clear Moodle caches: `php admin/cli/purge_caches.php`
3. Hard refresh browser: Ctrl+Shift+R
4. Check you edited src/ not build/
5. Clear browser cache completely

### Issue: Language strings missing

**Symptoms:** Shows string identifiers instead of text

**Solutions:**
1. Add strings to lang/en/gemini.php
2. Clear Moodle caches
3. Check string keys match exactly

## Step 9: Version Control

```bash
git add amd/
git add Gruntfile.js
git add package.json
git add .gitignore
git add lang/en/gemini.php
git add view.php
git commit -m "Refactor: Extract inline JavaScript to AMD modules

- Created generator.js for content generation wizard
- Created tools.js for teaching assistant tools
- Created viewer.js for flashcard viewer
- Added Grunt build configuration
- Updated view.php to use AMD modules
- Added required language strings"
```

- [ ] Changes committed to git

## Step 10: Documentation

- [ ] Update README.md with AMD module info
- [ ] Add JSDoc comments if missing
- [ ] Update CHANGELOG.md
- [ ] Update version number in version.php

## Post-Integration

### Optional Improvements

- [ ] Create Moodle external functions to replace ajax.php
- [ ] Add Behat tests for user workflows
- [ ] Extract presentation viewer to AMD
- [ ] Add unit tests for JavaScript functions
- [ ] Implement WebSocket for real-time updates
- [ ] Add loading animations/skeletons

### Performance Monitoring

- [ ] Check page load time (before/after)
- [ ] Monitor server load during polling
- [ ] Profile JavaScript execution
- [ ] Check bundle size vs inline scripts

## Success Criteria

✅ All inline scripts removed from view.php
✅ Three AMD modules created and working
✅ All functionality preserved
✅ No JavaScript errors in console
✅ Passes accessibility testing
✅ Works in all major browsers
✅ Code follows Moodle standards
✅ Documentation complete

## Rollback Plan

If issues occur:

1. Revert view.php changes
2. Keep AMD modules for future use
3. File bug report with details
4. Fix issues in AMD modules
5. Test thoroughly before re-deploying

## Next Steps

After successful integration:

1. Monitor for user-reported issues
2. Gather feedback from teachers
3. Plan additional AMD modules
4. Consider migrating to external functions
5. Add automated testing
6. Document lessons learned

## Notes

- AMD modules are cached by browser (good for performance)
- Changes require rebuild + cache clear during development
- Use `npm run watch` for faster development
- Production builds are minified automatically
- Keep documentation updated as code evolves
