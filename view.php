<?php
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

// Check if content has already been generated.
$content = $DB->get_record('gemini_content', array('geminiid' => $gemini->id));

echo $OUTPUT->header();

if ($content) {
    // --- TEACHER CONTROL PANEL ---
    if (has_capability('moodle/course:manageactivities', $context)) {
        echo '<div class="card mb-4 border-warning">';
        echo '<div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">';
        echo '<strong>' . get_string('teacher_controls', 'mod_gemini') . '</strong>';
        echo '<div>';
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

        // Control Panel JS
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
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
                             toolOutput.innerHTML = '<div class=\"alert alert-danger\">Error: '+d.message+'</div>';
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

                echo '<div class="slide-content fs-5">' . $slide->content . '</div>';
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
            if (!$data || !isset($data->cards)) {
                echo '<div class="alert alert-danger">Error decoding flashcards data.</div>';
                break;
            }
            echo '<div class="text-center mb-4"><p class="text-muted">Click the card to flip it!</p></div>';
            echo '<div id="flashcard-deck" class="d-flex flex-column align-items-center">';
            
            foreach ($data->cards as $index => $card) {
                $display = ($index === 0) ? '' : 'display:none;';
                echo '<div class="gemini-flashcard-container" id="card-'.$index.'" style="'.$display.'">';
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
                containers.forEach(c => {
                    c.addEventListener('click', () => c.classList.toggle('flipped'));
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
                const typeNameDisplay = document.getElementById('selected-type-name');

                cards.forEach(card => {
                    card.addEventListener('click', function() {
                        const type = this.getAttribute('data-type');
                        const name = this.querySelector('h5').innerText;
                        
                        // Highlight selection
                        cards.forEach(c => c.classList.remove('border-primary', 'bg-light'));
                        this.classList.add('border-primary', 'bg-light');

                        // Show form
                        typeInput.value = type;
                        typeNameDisplay.innerText = name;
                        formContainer.style.display = 'block';
                        
                        // Scroll to form
                        formContainer.scrollIntoView({behavior: 'smooth'});
                    });
                });
                
                document.getElementById('btn-generate').addEventListener('click', function() {
                    const prompt = document.querySelector('textarea[name=\"prompt\"]').value;
                    const type = document.getElementById('input-type').value;
                    const btn = this;
                    const statusDiv = document.getElementById('generation-status');
                    
                    if (!prompt.trim()) {
                        alert('" . $str_enter_topic . "');
                        return;
                    }

                    // UI Loading State
                    btn.disabled = true;
                    btn.innerHTML = '<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span> ' + '" . $str_generating . "';
                    statusDiv.innerHTML = '<div class=\"alert alert-info\">' + '" . $str_contacting . "' + '</div>';

                    // AJAX Request
                    const sesskey = M.cfg.sesskey;
                    const geminiId = <?php echo $gemini->id; ?>; // Instance ID

                    const formData = new FormData();
                    formData.append('id', geminiId);
                    formData.append('action', 'generate');
                    formData.append('prompt', prompt);
                    formData.append('type', type);
                    formData.append('sesskey', sesskey);

                    fetch('ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = '<div class=\"alert alert-success\">' + '" . $str_success . "' + '</div>';
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            throw new Error(data.message || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        statusDiv.innerHTML = '<div class=\"alert alert-danger\">' + '" . $str_error . "' + error.message + '</div>';
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
