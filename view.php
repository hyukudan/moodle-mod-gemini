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
 * View page for mod_gemini.
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/gemini/lib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.

// Get the course module, instance, and course data.
$cm = get_coursemodule_from_id('gemini', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$gemini = $DB->get_record('gemini', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Trigger module viewed event.
$event = \mod_gemini\event\course_module_viewed::create(array(
    'objectid' => $gemini->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('gemini', $gemini);
$event->trigger();

// Output setup.
$PAGE->set_url('/mod/gemini/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($gemini->name));
$PAGE->set_heading(format_string($course->fullname));

// Check if content has already been generated (get current version).
$content = $DB->get_record('gemini_content', array('geminiid' => $gemini->id, 'is_current' => 1));

echo $OUTPUT->header();

if ($content) {
    // --- TEACHER CONTROL PANEL ---
    if (has_capability('moodle/course:manageactivities', $context)) {
        echo '<div class="card mb-4 border-warning">';
        echo '<div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">';
        echo '<strong>' . get_string('teacher_controls', 'mod_gemini') . '</strong>';
        echo '<div>';
        echo '<button class="btn btn-sm btn-info me-2" id="btn-version-history">' . get_string('version_history', 'mod_gemini') . '</button>';
        echo '<button class="btn btn-sm btn-dark me-2" id="btn-tools-rubric">' . get_string('generate_rubric', 'mod_gemini') . '</button>';
        echo '<button class="btn btn-sm btn-light me-2" id="btn-edit-content">' . get_string('edit_content', 'mod_gemini') . '</button>';
        echo '<button class="btn btn-sm btn-danger" id="btn-regenerate">' . get_string('regenerate', 'mod_gemini') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Tools Modal (Rubric)
        echo '
        <div class="modal fade" id="toolsModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-info text-white">
                <h5 class="modal-title">' . get_string('ai_teaching_assistant', 'mod_gemini') . '</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="text-center mb-3">
                    <button class="btn btn-outline-primary" id="btn-do-rubric">' . get_string('create_rubric', 'mod_gemini') . '</button>
                    <!-- Future tools: <button class="btn btn-outline-success">Suggested Activities</button> -->
                </div>
                <div id="tool-output" class="border p-3 rounded bg-light" style="min-height:200px; display:none;"></div>
                <div id="tool-loading" class="text-center p-3" style="display:none;">
                    <div class="spinner-border text-primary" role="status"></div><br>' . get_string('thinking', 'mod_gemini') . '
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . get_string('close', 'mod_gemini') . '</button>
              </div>
            </div>
          </div>
        </div>';

        // Edit Modal
        echo '
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">' . get_string('edit_modal_title', 'mod_gemini') . '</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p class="small text-muted">' . get_string('edit_modal_help', 'mod_gemini') . '</p>
                <textarea class="form-control font-monospace" id="edit-content-area" rows="15"></textarea>
                <div id="edit-error" class="text-danger mt-2"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . get_string('close', 'mod_gemini') . '</button>
                <button type="button" class="btn btn-primary" id="btn-save-edit">' . get_string('save_changes', 'mod_gemini') . '</button>
              </div>
            </div>
          </div>
        </div>';

        // Version History Modal
        echo '
        <div class="modal fade" id="versionModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header bg-info text-white">
                <h5 class="modal-title">' . get_string('version_history', 'mod_gemini') . '</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div id="version-loading" class="text-center p-3">
                    <div class="spinner-border text-primary" role="status"></div><br>' . get_string('loading_versions', 'mod_gemini') . '
                </div>
                <div id="version-list" style="display:none;">
                    <p class="text-muted small">' . get_string('version_history_help', 'mod_gemini') . '</p>
                    <div id="version-items"></div>
                </div>
                <div id="version-error" class="alert alert-danger" style="display:none;"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . get_string('close', 'mod_gemini') . '</button>
              </div>
            </div>
          </div>
        </div>';

        // Control Panel JS
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // VERSION HISTORY
            const btnVersionHistory = document.getElementById('btn-version-history');
            const versionModalEl = document.getElementById('versionModal');
            const versionModal = new bootstrap.Modal(versionModalEl);
            const versionLoading = document.getElementById('version-loading');
            const versionList = document.getElementById('version-list');
            const versionItems = document.getElementById('version-items');
            const versionError = document.getElementById('version-error');

            if(btnVersionHistory) {
                btnVersionHistory.addEventListener('click', function() {
                    versionModal.show();
                    loadVersionHistory();
                });
            }

            function loadVersionHistory() {
                versionLoading.style.display = 'block';
                versionList.style.display = 'none';
                versionError.style.display = 'none';
                versionItems.innerHTML = '';

                const formData = new FormData();
                formData.append('id', " . $gemini->id . ");
                formData.append('action', 'get_versions');
                formData.append('sesskey', M.cfg.sesskey);

                fetch('ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    versionLoading.style.display = 'none';
                    if(d.success && d.data && d.data.versions) {
                        versionList.style.display = 'block';
                        displayVersions(d.data.versions);
                    } else {
                        versionError.textContent = d.message || 'Failed to load versions';
                        versionError.style.display = 'block';
                    }
                })
                .catch(e => {
                    versionLoading.style.display = 'none';
                    versionError.textContent = 'Network error: ' + e.message;
                    versionError.style.display = 'block';
                });
            }

            function displayVersions(versions) {
                versionItems.innerHTML = '';

                if(versions.length === 0) {
                    versionItems.innerHTML = '<p class=\"text-muted\">" . get_string('no_versions', 'mod_gemini') . "</p>';
                    return;
                }

                versions.forEach(function(v) {
                    const card = document.createElement('div');
                    card.className = 'card mb-3 ' + (v.is_current ? 'border-success' : '');

                    const cardHeader = document.createElement('div');
                    cardHeader.className = 'card-header d-flex justify-content-between align-items-center ' + (v.is_current ? 'bg-success text-white' : 'bg-light');

                    const headerLeft = document.createElement('div');
                    const versionTitle = document.createElement('strong');
                    versionTitle.textContent = '" . get_string('version', 'mod_gemini') . " ' + v.version;
                    if(v.is_current) {
                        const currentBadge = document.createElement('span');
                        currentBadge.className = 'badge bg-light text-success ms-2';
                        currentBadge.textContent = '" . get_string('current', 'mod_gemini') . "';
                        versionTitle.appendChild(currentBadge);
                    }
                    headerLeft.appendChild(versionTitle);

                    const dateSpan = document.createElement('small');
                    dateSpan.className = 'ms-3 ' + (v.is_current ? 'text-white' : 'text-muted');
                    dateSpan.textContent = v.date_created;
                    headerLeft.appendChild(dateSpan);

                    cardHeader.appendChild(headerLeft);

                    if(!v.is_current) {
                        const restoreBtn = document.createElement('button');
                        restoreBtn.className = 'btn btn-sm btn-primary';
                        restoreBtn.textContent = '" . get_string('restore', 'mod_gemini') . "';
                        restoreBtn.onclick = function() {
                            restoreVersion(v.id);
                        };
                        cardHeader.appendChild(restoreBtn);
                    }

                    card.appendChild(cardHeader);

                    const cardBody = document.createElement('div');
                    cardBody.className = 'card-body';

                    if(v.prompt) {
                        const promptLabel = document.createElement('strong');
                        promptLabel.textContent = '" . get_string('prompt', 'mod_gemini') . ": ';
                        cardBody.appendChild(promptLabel);

                        const promptText = document.createElement('p');
                        promptText.className = 'text-muted';
                        promptText.textContent = v.prompt;
                        cardBody.appendChild(promptText);
                    }

                    const typeLabel = document.createElement('small');
                    typeLabel.className = 'text-muted';
                    typeLabel.textContent = '" . get_string('content_type', 'mod_gemini') . ": ' + v.type;
                    cardBody.appendChild(typeLabel);

                    card.appendChild(cardBody);
                    versionItems.appendChild(card);
                });
            }

            function restoreVersion(versionId) {
                if(!confirm('" . get_string('restore_confirm', 'mod_gemini') . "')) {
                    return;
                }

                const formData = new FormData();
                formData.append('id', " . $gemini->id . ");
                formData.append('action', 'restore_version');
                formData.append('version_id', versionId);
                formData.append('sesskey', M.cfg.sesskey);

                fetch('ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if(d.success) {
                        alert('" . get_string('restore_success', 'mod_gemini') . "');
                        window.location.reload();
                    } else {
                        alert('" . get_string('error_prefix', 'mod_gemini') . "' + (d.message || 'Unknown error'));
                    }
                })
                .catch(e => {
                    alert('" . get_string('error_prefix', 'mod_gemini') . "' + e.message);
                });
            }

            // TOOLS
            const btnTools = document.getElementById('btn-tools-rubric');
            const toolsModalEl = document.getElementById('toolsModal');
            const toolsModal = new bootstrap.Modal(toolsModalEl);
            const btnDoRubric = document.getElementById('btn-do-rubric');
            const toolOutput = document.getElementById('tool-output');
            const toolLoading = document.getElementById('tool-loading');

            if(btnTools) {
                btnTools.addEventListener('click', () => toolsModal.show());
            }

            if(btnDoRubric) {
                btnDoRubric.addEventListener('click', function() {
                     toolOutput.style.display = 'none';
                     toolLoading.style.display = 'block';
                     
                     // Use the main topic as context (we can grab it from existing content or prompt user)
                     // Simple MVP: Just ask for rubric based on what we have.
                     const prompt = 'this topic'; 

                     const formData = new FormData();
                     formData.append('id', " . $gemini->id . ");
                     formData.append('action', 'tools_rubric');
                     formData.append('prompt', prompt);
                     formData.append('sesskey', M.cfg.sesskey);

                     fetch('ajax.php', { method: 'POST', body: formData })
                     .then(r => r.json())
                     .then(d => {
                         toolLoading.style.display = 'none';
                         if(d.success) {
                             toolOutput.innerHTML = d.data.html;
                             toolOutput.style.display = 'block';
                         } else {
                             const errorDiv = document.createElement('div');
                             errorDiv.className = 'alert alert-danger';
                             errorDiv.textContent = 'Error: ' + d.message;
                             toolOutput.innerHTML = '';
                             toolOutput.appendChild(errorDiv);
                             toolOutput.style.display = 'block';
                         }
                     });
                });
            }

            // REGENERATE
            const btnRegen = document.getElementById('btn-regenerate');
            if(btnRegen) {
                btnRegen.addEventListener('click', function() {
                    if(confirm('Are you sure? This will DELETE the current content permanently.')) {
                        const formData = new FormData();
                        formData.append('id', " . $gemini->id . ");
                        formData.append('action', 'reset');
                        formData.append('sesskey', M.cfg.sesskey);
                        
                        fetch('ajax.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(d => { if(d.success) window.location.reload(); });
                    }
                });
            }

            // EDIT
            const btnEdit = document.getElementById('btn-edit-content');
            const editModalEl = document.getElementById('editModal');
            const editModal = new bootstrap.Modal(editModalEl);
            const textarea = document.getElementById('edit-content-area');
            const btnSave = document.getElementById('btn-save-edit');
            
            // Raw content from PHP (safely encoded)
            const rawContent = " . json_encode($content->content) . ";

            if(btnEdit) {
                btnEdit.addEventListener('click', function() {
                    textarea.value = rawContent;
                    editModal.show();
                });
            }

            if(btnSave) {
                btnSave.addEventListener('click', function() {
                    const newContent = textarea.value;
                    const errorDiv = document.getElementById('edit-error');
                    errorDiv.innerText = '';
                    
                    const formData = new FormData();
                    formData.append('id', " . $gemini->id . ");
                    formData.append('action', 'update');
                    formData.append('content', newContent);
                    formData.append('sesskey', M.cfg.sesskey);

                    btnSave.disabled = true;
                    btnSave.innerText = 'Saving...';

                    fetch('ajax.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(d => { 
                        if(d.success) {
                            window.location.reload();
                        } else {
                            errorDiv.innerText = d.message || 'Error saving';
                            btnSave.disabled = false;
                            btnSave.innerText = 'Save Changes';
                        }
                    })
                    .catch(e => {
                         errorDiv.innerText = 'Network Error';
                         btnSave.disabled = false;
                    });
                });
            }
        });
        </script>";
    }

    // --- STUDENT VIEW (Content exists) ---
    echo $OUTPUT->heading(format_string($gemini->name));
    
    // Show description if set.
    if ($gemini->intro) {
        echo $OUTPUT->box(format_module_intro('gemini', $gemini, $cm->id), 'generalbox intro');
    }

    echo '<div class="gemini-content-container p-3">';
    
    switch ($content->type) {
        case 'presentation':
            $data = json_decode($content->content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = new stdClass();
            }
            if (!$data || !isset($data->slides)) {
                echo '<div class="alert alert-danger">Error decoding presentation data.</div>';
                break;
            }
            
            echo '<div id="geminiCarousel" class="carousel slide border shadow-sm rounded bg-white" data-bs-ride="false">';
            echo '<div class="carousel-inner">';
            foreach ($data->slides as $index => $slide) {
                $active = ($index === 0) ? 'active' : '';
                echo '<div class="carousel-item ' . $active . '">';
                echo '<div class="gemini-presentation-slide">';
                echo '<h2 class="text-primary border-bottom pb-2 mb-4">' . s($slide->title) . '</h2>';
                
                // Image rendering
                if (!empty($slide->image_prompt)) {
                    $img_url = 'https://image.pollinations.ai/prompt/' . rawurlencode($slide->image_prompt . ' minimal flat educational vector art') . '?width=800&height=400&nologo=true';
                    echo '<div class="text-center mb-4"><img src="'.$img_url.'" class="img-fluid rounded shadow-sm" alt="Slide Image" style="max-height: 300px; object-fit: cover;"></div>';
                }

                echo '<div class="slide-content fs-5">' . format_text($slide->content, FORMAT_HTML, ['context' => $context]) . '</div>';
                if (!empty($slide->notes)) {
                    echo '<div class="mt-5 p-3 bg-light border-start border-4 border-info small text-muted"><strong>Notes:</strong> ' . s($slide->notes) . '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            // Controls
            echo '<button class="carousel-control-prev" type="button" data-bs-target="#geminiCarousel" data-bs-slide="prev" style="filter: invert(1);">';
            echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
            echo '<span class="visually-hidden">Previous</span>';
            echo '</button>';
            echo '<button class="carousel-control-next" type="button" data-bs-target="#geminiCarousel" data-bs-slide="next" style="filter: invert(1);">';
            echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
            echo '<span class="visually-hidden">Next</span>';
            echo '</button>';
            // Indicators
            echo '<div class="carousel-indicators" style="position:relative; margin-top: 20px; filter: invert(1);">';
            foreach ($data->slides as $index => $slide) {
                $active = ($index === 0) ? 'active' : '';
                echo '<button type="button" data-bs-target="#geminiCarousel" data-bs-slide-to="' . $index . '" class="' . $active . '" aria-current="true"></button>';
            }
            echo '</div>';
            echo '</div>';
            break;
            
        case 'flashcards':
            $data = json_decode($content->content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = new stdClass();
            }
            if (!$data || !isset($data->cards)) {
                echo '<div class="alert alert-danger">Error decoding flashcards data.</div>';
                break;
            }
            echo '<div class="text-center mb-4"><p class="text-muted">Click the card to flip it!</p></div>';
            echo '<div id="flashcard-deck" class="d-flex flex-column align-items-center">';
            
            foreach ($data->cards as $index => $card) {
                $display = ($index === 0) ? '' : 'display:none;';
                // A11y: Add tabindex and role
                echo '<div class="gemini-flashcard-container" id="card-'.$index.'" style="'.$display.'" tabindex="0" role="button" aria-label="Flashcard ' . ($index + 1) . '">';
                echo '<div class="gemini-flashcard-inner">';
                echo '<div class="gemini-flashcard-front">';
                echo '<h4>' . s($card->front) . '</h4>';
                echo '</div>';
                echo '<div class="gemini-flashcard-back">';
                echo '<div>' . s($card->back) . '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }

            echo '<div class="mt-4">';
            echo '<button class="btn btn-outline-secondary me-2" id="prev-card" disabled>Previous</button>';
            echo '<span id="card-counter" class="mx-3">1 / '.count($data->cards).'</span>';
            echo '<button class="btn btn-primary" id="next-card">Next Card</button>';
            echo '</div>';
            echo '</div>';

            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                let currentCard = 0;
                const totalCards = ".count($data->cards).";
                const containers = document.querySelectorAll('.gemini-flashcard-container');
                const prevBtn = document.getElementById('prev-card');
                const nextBtn = document.getElementById('next-card');
                const counter = document.getElementById('card-counter');

                // Flip logic
                function flipCard(card) {
                    card.classList.toggle('flipped');
                }

                containers.forEach(c => {
                    // Click
                    c.addEventListener('click', () => flipCard(c));
                    
                    // Keyboard (Enter/Space)
                    c.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            flipCard(c);
                        }
                    });
                });

                // Navigation
                function updateNav() {
                    containers.forEach((c, i) => c.style.display = (i === currentCard) ? 'block' : 'none');
                    counter.innerText = (currentCard + 1) + ' / ' + totalCards;
                    prevBtn.disabled = (currentCard === 0);
                    nextBtn.innerText = (currentCard === totalCards - 1) ? 'Finish' : 'Next Card';
                }

                nextBtn.addEventListener('click', () => {
                    if (currentCard < totalCards - 1) {
                        currentCard++;
                        updateNav();
                    } else {
                        // Finish Deck - Send Grade
                        nextBtn.disabled = true;
                        nextBtn.innerText = 'Saving...';
                        
                        const formData = new FormData();
                        formData.append('id', <?php echo $gemini->id; ?>); // Instance ID for ajax.php logic (Note: ajax.php uses instance ID 'id', not cmid)
                        formData.append('action', 'grade_completion');
                        formData.append('sesskey', M.cfg.sesskey);
                        
                        fetch('ajax.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(d => {
                             alert('Deck finished! Grade recorded: 100%');
                             nextBtn.innerText = 'Finished';
                        })
                        .catch(e => {
                            console.error(e);
                            alert('Deck finished!');
                        });
                    }
                });

                prevBtn.addEventListener('click', () => {
                    if (currentCard > 0) {
                        currentCard--;
                        updateNav();
                    }
                });
            });
            </script>";
            break;
            
        case 'summary':
            echo '<div class="bg-white p-5 border rounded shadow-sm">';
            echo '<h3>📝 Content Summary</h3><hr>';
            echo '<div class="gemini-summary-text lead">' . format_text($content->content, FORMAT_HTML) . '</div>';
            echo '</div>';
            break;

        case 'audio':
            echo '<div class="bg-white p-5 border rounded shadow-sm text-center">';
            echo '<div class="display-1 mb-4">🎙️</div>';
            echo '<h3>Audio Lesson</h3>';
            
            // Check for audio file
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_gemini', 'audio', $content->id, 'sortorder', false);
            $audio_file = reset($files);
            
            if ($audio_file) {
                $url = moodle_url::make_pluginfile_url(
                    $context->id, 
                    'mod_gemini', 
                    'audio', 
                    $content->id, 
                    '/', 
                    $audio_file->get_filename()
                );
                echo '<div class="my-4">';
                echo '<audio controls class="w-100">';
                echo '<source src="' . $url . '" type="audio/mpeg">';
                echo 'Your browser does not support the audio element.';
                echo '</audio>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-warning">Audio file not found (TTS generation might have failed or is not configured). Showing script only.</div>';
            }

            echo '<div class="text-start mt-4 p-3 bg-light border rounded italic">' . nl2br(s($content->content)) . '</div>';
            echo '</div>';
            break;

        case 'quiz':
            echo '<div class="bg-white p-5 border rounded shadow-sm text-center">';
            echo '<div class="display-1 mb-4">❓</div>';
            echo '<h3>Quiz Questions Generated</h3>';
            echo '<p class="lead">Your Moodle XML file is ready for import.</p>';
            
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_gemini', 'quiz', $content->id, 'sortorder', false);
            $xml_file = reset($files);

            if ($xml_file) {
                 $url = moodle_url::make_pluginfile_url(
                    $context->id, 
                    'mod_gemini', 
                    'quiz', 
                    $content->id, 
                    '/', 
                    $xml_file->get_filename()
                );
                echo '<a href="'.$url.'?forcedownload=1" class="btn btn-success btn-lg mt-3">⬇️ Download XML Questions</a>';
                echo '<p class="text-muted mt-3 small">Go to Course Administration > Question Bank > Import > Moodle XML to use these questions.</p>';
            } else {
                echo '<div class="alert alert-danger">Error: XML file could not be generated.</div>';
            }

            echo '<div class="mt-4 text-start"><pre class="bg-light p-3 border rounded" style="max-height:300px;">' . s($content->content) . '</pre></div>';
            echo '</div>';
            break;

        default:
            echo '<div class="alert alert-warning">Unknown content type.</div>';
    }
    echo '</div>';

    // --- INTERACTIVE CHAT PANEL ---
    // Available for all users who can view the content
    $str_chat_title = get_string('chat_panel_title', 'mod_gemini');
    $str_chat_placeholder = get_string('chat_placeholder', 'mod_gemini');
    $str_chat_send = get_string('chat_send', 'mod_gemini');
    $str_chat_thinking = get_string('chat_thinking', 'mod_gemini');
    $str_chat_welcome = get_string('chat_welcome', 'mod_gemini');
    $str_chat_clear = get_string('chat_clear', 'mod_gemini');
    $str_chat_error = get_string('chat_error', 'mod_gemini');

    echo '<div class="gemini-chat-container mt-4">';
    echo '<div class="card border-info">';
    echo '<div class="card-header bg-info text-white d-flex justify-content-between align-items-center"
          style="cursor: pointer;"
          data-bs-toggle="collapse"
          data-bs-target="#chatPanel"
          aria-expanded="false"
          aria-controls="chatPanel">';
    echo '<span><strong>' . $str_chat_title . '</strong></span>';
    echo '<span class="chat-toggle-icon">&#9660;</span>';
    echo '</div>';

    echo '<div class="collapse" id="chatPanel">';
    echo '<div class="card-body">';

    // Chat messages area
    echo '<div id="chat-messages" class="border rounded p-3 mb-3 bg-light" style="height: 300px; overflow-y: auto;">';
    echo '<div class="chat-message assistant-message">';
    echo '<div class="d-flex align-items-start">';
    echo '<span class="badge bg-info me-2">AI</span>';
    echo '<div class="message-content">' . s($str_chat_welcome) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Chat input area
    echo '<div class="input-group">';
    echo '<input type="text" class="form-control" id="chat-input" placeholder="' . s($str_chat_placeholder) . '" maxlength="1000">';
    echo '<button class="btn btn-info" type="button" id="chat-send-btn">' . $str_chat_send . '</button>';
    echo '</div>';

    // Clear chat button
    echo '<div class="mt-2 text-end">';
    echo '<button class="btn btn-sm btn-outline-secondary" id="chat-clear-btn">' . $str_chat_clear . '</button>';
    echo '</div>';

    echo '</div>'; // card-body
    echo '</div>'; // collapse
    echo '</div>'; // card
    echo '</div>'; // gemini-chat-container

    // Chat CSS
    echo '<style>
    .gemini-chat-container .chat-message {
        margin-bottom: 12px;
        animation: fadeIn 0.3s ease-in;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .gemini-chat-container .user-message .message-content {
        background-color: #e3f2fd;
        padding: 8px 12px;
        border-radius: 12px;
        display: inline-block;
        max-width: 80%;
    }
    .gemini-chat-container .assistant-message .message-content {
        background-color: #f5f5f5;
        padding: 8px 12px;
        border-radius: 12px;
        display: inline-block;
        max-width: 80%;
    }
    .gemini-chat-container .chat-toggle-icon {
        transition: transform 0.3s ease;
    }
    .gemini-chat-container [aria-expanded="true"] .chat-toggle-icon {
        transform: rotate(180deg);
    }
    .gemini-chat-container .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 8px 12px;
    }
    .gemini-chat-container .typing-indicator span {
        width: 8px;
        height: 8px;
        background-color: #17a2b8;
        border-radius: 50%;
        animation: bounce 1.4s infinite ease-in-out both;
    }
    .gemini-chat-container .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
    .gemini-chat-container .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    </style>';

    // Chat JavaScript
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('chat-send-btn');
        const clearBtn = document.getElementById('chat-clear-btn');
        const geminiId = " . $gemini->id . ";
        const sesskey = M.cfg.sesskey;
        const thinkingMsg = '" . addslashes($str_chat_thinking) . "';
        const errorMsg = '" . addslashes($str_chat_error) . "';
        const welcomeMsg = '" . addslashes($str_chat_welcome) . "';

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add message to chat
        function addMessage(content, isUser) {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-message ' + (isUser ? 'user-message text-end' : 'assistant-message');

            const inner = document.createElement('div');
            inner.className = 'd-flex align-items-start' + (isUser ? ' justify-content-end' : '');

            if (!isUser) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-info me-2';
                badge.textContent = 'AI';
                inner.appendChild(badge);
            }

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.textContent = content;
            inner.appendChild(contentDiv);

            if (isUser) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary ms-2';
                badge.textContent = 'You';
                inner.appendChild(badge);
            }

            msgDiv.appendChild(inner);
            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            return msgDiv;
        }

        // Add typing indicator
        function addTypingIndicator() {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-message assistant-message';
            msgDiv.id = 'typing-indicator';

            const inner = document.createElement('div');
            inner.className = 'd-flex align-items-start';

            const badge = document.createElement('span');
            badge.className = 'badge bg-info me-2';
            badge.textContent = 'AI';
            inner.appendChild(badge);

            const indicator = document.createElement('div');
            indicator.className = 'typing-indicator';
            indicator.innerHTML = '<span></span><span></span><span></span>';
            inner.appendChild(indicator);

            msgDiv.appendChild(inner);
            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Remove typing indicator
        function removeTypingIndicator() {
            const indicator = document.getElementById('typing-indicator');
            if (indicator) indicator.remove();
        }

        // Send message
        function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            // Disable input while processing
            chatInput.disabled = true;
            sendBtn.disabled = true;

            // Add user message
            addMessage(message, true);
            chatInput.value = '';

            // Show typing indicator
            addTypingIndicator();

            // Send to server
            const formData = new FormData();
            formData.append('id', geminiId);
            formData.append('action', 'chat');
            formData.append('message', message);
            formData.append('sesskey', sesskey);

            fetch('ajax.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                removeTypingIndicator();

                if (data.success && data.data && data.data.response) {
                    addMessage(data.data.response, false);
                } else {
                    addMessage(data.message || errorMsg, false);
                }
            })
            .catch(err => {
                removeTypingIndicator();
                console.error('Chat error:', err);
                addMessage(errorMsg, false);
            })
            .finally(() => {
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.focus();
            });
        }

        // Event listeners
        sendBtn.addEventListener('click', sendMessage);

        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Clear chat
        clearBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('id', geminiId);
            formData.append('action', 'chat_clear');
            formData.append('sesskey', sesskey);

            fetch('ajax.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                // Reset chat UI
                chatMessages.innerHTML = '';
                addMessage(welcomeMsg, false);
            });
        });
    });
    </script>";

} else {
    // --- TEACHER VIEW (Wizard / Empty State) ---
    // Only teachers can generate content.
    if (has_capability('moodle/course:manageactivities', $context)) {
        echo $OUTPUT->heading(get_string('content_creator', 'mod_gemini'));
        echo '<div class="alert alert-info">' . get_string('no_content_yet', 'mod_gemini') . '</div>';
        
        // We will load the Wizard UI (Vue/React or Vanilla JS) here.
        // For now, a placeholder UI.
        echo '<div id="gemini-wizard-app" class="row">';
        
        $types = [
            ['type' => 'presentation', 'icon' => '📽️', 'name' => 'Presentation', 'desc' => 'Generate a slide deck with images.'],
            ['type' => 'flashcards',   'icon' => '🗂️', 'name' => 'Flashcards',   'desc' => 'Create study cards.'],
            ['type' => 'summary',      'icon' => '📝', 'name' => 'Summary',      'desc' => 'Summarize a topic.'],
            ['type' => 'audio',        'icon' => '🎧', 'name' => 'Audio',        'desc' => 'Generate an audio explanation (MP3).'],
            ['type' => 'quiz',         'icon' => '❓', 'name' => 'Quiz Questions', 'desc' => 'Generate Moodle XML for Question Bank.'],
        ];

        foreach ($types as $t) {
            echo '<div class="col-md-3 mb-3">';
            echo '<div class="card h-100 text-center p-3 cursor-pointer gemini-type-card" style="cursor:pointer;" data-type="'.$t['type'].'">';
            echo '<div class="display-4 mb-2">'.$t['icon'].'</div>';
            echo '<h5>'.$t['name'].'</h5>';
            echo '<p class="text-muted small">'.$t['desc'].'</p>';
            echo '<button class="btn btn-outline-primary btn-sm mt-auto">Select</button>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>'; // row

        // Container for the generation form (hidden initially via JS)
        $str_topic = get_string('topic_prompt', 'mod_gemini');
        $str_placeholder = get_string('topic_placeholder', 'mod_gemini');
        $str_generate = get_string('generate_btn', 'mod_gemini');

        echo '<div id="gemini-generation-form" class="mt-4 p-4 bg-light border rounded" style="display:none;">';
        echo '<h4>' . get_string('configure_type', 'mod_gemini', '<span id="selected-type-name"></span>') . '</h4>';
        echo '<form id="gemini-generate-form">';
        echo '<input type="hidden" name="type" id="input-type">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">' . $str_topic . '</label>';
        echo '<textarea class="form-control" name="prompt" rows="3" placeholder="' . $str_placeholder . '"></textarea>';
        // Prompt Chips
        echo '<div class="mt-2 small text-muted">';
        echo '<span class="me-2">' . get_string('prompt_inspiration', 'mod_gemini') . '</span>';
        $chips = [
            get_string('prompt_explain_child', 'mod_gemini') => 'Explain [TOPIC] simply as if to a 10 year old.',
            get_string('prompt_critical_analysis', 'mod_gemini') => 'Provide a critical analysis of [TOPIC], discussing pros and cons.',
            get_string('prompt_real_world', 'mod_gemini') => 'Give 3 real-world case studies related to [TOPIC].',
            get_string('prompt_timeline', 'mod_gemini') => 'Create a chronological timeline of key events for [TOPIC].'
        ];
        foreach ($chips as $label => $template) {
            echo '<button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1 prompt-chip" data-template="'.s($template).'">' . $label . '</button>';
        }
        echo '</div>';
        echo '</div>';
        echo '<button type="button" class="btn btn-primary" id="btn-generate">' . $str_generate . '</button>';
        echo '</form>';
        echo '<div id="generation-status" class="mt-3"></div>';
        echo '</div>';

        // Add some basic JS for the prototype wizard
        $str_generating = get_string('generating_msg', 'mod_gemini');
        $str_contacting = get_string('contacting_msg', 'mod_gemini');
        $str_success = get_string('success_reload', 'mod_gemini');
        $str_error = get_string('error_prefix', 'mod_gemini');
        $str_enter_topic = get_string('enter_topic', 'mod_gemini');

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const cards = document.querySelectorAll('.gemini-type-card');
                const formContainer = document.getElementById('gemini-generation-form');
                const typeInput = document.getElementById('input-type');
                const promptInput = document.querySelector('textarea[name=\"prompt\"]');
                const typeNameDisplay = document.getElementById('selected-type-name');
                const statusDiv = document.getElementById('generation-status');

                // Chip Logic
                document.querySelectorAll('.prompt-chip').forEach(chip => {
                    chip.addEventListener('click', function() {
                        let current = promptInput.value.trim();
                        // If empty or generic placeholder, replace. Else append.
                        let template = this.getAttribute('data-template');
                        if (current.length < 5) {
                            promptInput.value = template;
                        } else {
                            // If user already typed 'Photosynthesis', try to replace [TOPIC] or just append
                            if (template.includes('[TOPIC]')) {
                                promptInput.value = template.replace('[TOPIC]', current);
                            } else {
                                promptInput.value = current + '\\n\\n' + template;
                            }
                        }
                        promptInput.focus();
                    });
                });

                // Polling for Task Status
                const geminiId = " . $gemini->id . ";
                const sesskey = M.cfg.sesskey;
                
                function checkStatus() {
                    const fd = new FormData();
                    fd.append('id', geminiId);
                    fd.append('action', 'check_status');
                    fd.append('sesskey', sesskey);

                    fetch('ajax.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Success logic
                            if (data.data.has_newly_completed) {
                                window.location.reload();
                            }
                            
                            // Error Logic
                            if (data.data.errors && data.data.errors.length > 0) {
                                const err = data.data.errors[0];
                                const errorContainer = document.createElement('div');
                                errorContainer.className = 'alert alert-danger';
                                const strong = document.createElement('strong');
                                strong.textContent = 'Generation Failed:';
                                errorContainer.appendChild(strong);
                                errorContainer.appendChild(document.createTextNode(' ' + (err.errormessage || 'Unknown error')));
                                statusDiv.innerHTML = '';
                                statusDiv.appendChild(errorContainer);
                                const btn = document.getElementById('btn-generate');
                                if(btn) {
                                     btn.disabled = false;
                                     btn.innerHTML = '✨ Generate with Gemini';
                                }
                                return; // Stop processing other statuses
                            }
                            
                            if (data.data.pending_count > 0) {
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-warning';
                                const spinner = document.createElement('div');
                                spinner.className = 'spinner-border spinner-border-sm me-2';
                                alertDiv.appendChild(spinner);
                                alertDiv.appendChild(document.createTextNode(' Processing tasks in background:'));
                                const ul = document.createElement('ul');
                                data.data.tasks.forEach(t => {
                                    const li = document.createElement('li');
                                    li.textContent = t.type + ': ' + (t.status == 1 ? 'Processing...' : 'Queued');
                                    ul.appendChild(li);
                                });
                                alertDiv.appendChild(ul);
                                const small = document.createElement('small');
                                small.textContent = 'You can leave this page. We will notify you when done.';
                                alertDiv.appendChild(small);
                                statusDiv.innerHTML = '';
                                statusDiv.appendChild(alertDiv);
                                statusDiv.style.display = 'block';
                            } else if (statusDiv.innerHTML.includes('Processing')) {
                                statusDiv.innerHTML = ''; // Clear if done but not newly completed (e.g. error)
                            }
                        }
                    });
                }

                // Poll every 5 seconds
                setInterval(checkStatus, 5000);
                checkStatus(); // Initial check

                cards.forEach(card => {
                    card.addEventListener('click', function() {
                        const type = this.getAttribute('data-type');
                        const name = this.querySelector('h5').innerText;
                        
                        cards.forEach(c => c.classList.remove('border-primary', 'bg-light'));
                        this.classList.add('border-primary', 'bg-light');

                        typeInput.value = type;
                        typeNameDisplay.innerText = name;
                        formContainer.style.display = 'block';
                        formContainer.scrollIntoView({behavior: 'smooth'});
                    });
                });
                
                document.getElementById('btn-generate').addEventListener('click', function() {
                    const prompt = document.querySelector('textarea[name=\"prompt\"]').value;
                    const type = document.getElementById('input-type').value;
                    const btn = this;
                    
                    if (!prompt.trim()) {
                        alert('" . $str_enter_topic . "');
                        return;
                    }

                    btn.disabled = true;
                    btn.innerHTML = '" . $str_generating . "';
                    const enqueuingDiv = document.createElement('div');
                    enqueuingDiv.className = 'alert alert-info';
                    enqueuingDiv.textContent = 'Enqueuing task...';
                    statusDiv.innerHTML = '';
                    statusDiv.appendChild(enqueuingDiv);

                    const formData = new FormData();
                    formData.append('id', geminiId);
                    formData.append('action', 'generate');
                    formData.append('prompt', prompt);
                    formData.append('type', type);
                    formData.append('sesskey', sesskey);

                    fetch('ajax.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const successDiv = document.createElement('div');
                            successDiv.className = 'alert alert-success';
                            successDiv.textContent = 'Task Queued! The system is generating your content in the background.';
                            statusDiv.innerHTML = '';
                            statusDiv.appendChild(successDiv);
                            btn.disabled = false;
                            btn.innerHTML = '" . $str_generate . "';
                            checkStatus(); // Update UI immediately
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger';
                        errorDiv.textContent = '" . $str_error . "' + error.message;
                        statusDiv.innerHTML = '';
                        statusDiv.appendChild(errorDiv);
                        btn.disabled = false;
                        btn.innerHTML = '" . $str_generate . "';
                    });
                });
            });
        </script>";

    } else {
        echo '<div class="alert alert-warning">This activity is not ready yet. Please ask your teacher to generate the content.</div>';
    }
}

echo $OUTPUT->footer();
