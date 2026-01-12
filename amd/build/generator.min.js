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
 * Content Generation UI Module for mod_gemini.
 * Handles the wizard interface, type selection, prompt chips,
 * task enqueueing, and status polling.
 *
 * @module     mod_gemini/generator
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    var Generator = {

        /**
         * Configuration object
         */
        config: null,

        /**
         * Polling interval ID
         */
        pollInterval: null,

        /**
         * Initialize the generator module
         *
         * @param {Object} params Configuration parameters
         * @param {int} params.geminiId The Gemini activity instance ID
         * @param {string} params.ajaxUrl URL to ajax.php
         * @param {Object} params.strings Language strings
         */
        init: function(params) {
            this.config = params;
            this.setupTypeSelection();
            this.setupPromptChips();
            this.setupGenerateButton();
            this.startStatusPolling();
        },

        /**
         * Setup type card selection
         */
        setupTypeSelection: function() {
            var self = this;
            $('.gemini-type-card').on('click', function() {
                var $card = $(this);
                var type = $card.data('type');
                var name = $card.find('h5').text();

                // Update UI
                $('.gemini-type-card').removeClass('border-primary bg-light');
                $card.addClass('border-primary bg-light');

                // Set form values
                $('#input-type').val(type);
                $('#selected-type-name').text(name);
                $('#gemini-generation-form').slideDown();

                // Smooth scroll
                $('html, body').animate({
                    scrollTop: $('#gemini-generation-form').offset().top - 20
                }, 500);
            });
        },

        /**
         * Setup prompt inspiration chips
         */
        setupPromptChips: function() {
            $('.prompt-chip').on('click', function(e) {
                e.preventDefault();
                var $textarea = $('textarea[name="prompt"]');
                var currentValue = $textarea.val().trim();
                var template = $(this).data('template');

                if (currentValue.length < 5) {
                    // Empty or very short - replace
                    $textarea.val(template);
                } else {
                    // Has content - try to replace [TOPIC] placeholder or append
                    if (template.includes('[TOPIC]')) {
                        $textarea.val(template.replace('[TOPIC]', currentValue));
                    } else {
                        $textarea.val(currentValue + '\n\n' + template);
                    }
                }
                $textarea.focus();
            });
        },

        /**
         * Setup generate button click handler
         */
        setupGenerateButton: function() {
            var self = this;
            $('#btn-generate').on('click', function() {
                self.enqueueTask();
            });
        },

        /**
         * Enqueue a generation task
         */
        enqueueTask: function() {
            var self = this;
            var $btn = $('#btn-generate');
            var $statusDiv = $('#generation-status');
            var prompt = $('textarea[name="prompt"]').val().trim();
            var type = $('#input-type').val();

            // Validate
            if (!prompt) {
                Notification.alert(
                    self.config.strings.error_title || 'Error',
                    self.config.strings.enter_topic || 'Please enter a topic or description.',
                    self.config.strings.ok || 'OK'
                );
                return;
            }

            if (!type) {
                Notification.alert(
                    self.config.strings.error_title || 'Error',
                    self.config.strings.select_type || 'Please select a content type.',
                    self.config.strings.ok || 'OK'
                );
                return;
            }

            // Disable button and show loading
            $btn.prop('disabled', true);
            $btn.html(self.config.strings.generating_msg || 'Generating...');

            $statusDiv.html(
                '<div class="alert alert-info">' +
                (self.config.strings.contacting_msg || 'Enqueuing task...') +
                '</div>'
            );

            // Use Moodle AJAX API
            var promises = Ajax.call([{
                methodname: 'mod_gemini_enqueue_task',
                args: {
                    geminiid: self.config.geminiId,
                    type: type,
                    prompt: prompt
                },
                fail: function(error) {
                    self.handleEnqueueError(error);
                }
            }]);

            // Fallback to direct fetch if AJAX service not available
            // (For backward compatibility during migration)
            promises[0].then(function(response) {
                self.handleEnqueueSuccess(response);
            }).catch(function() {
                // Fallback to old ajax.php method
                self.enqueueTaskLegacy(prompt, type);
            });
        },

        /**
         * Legacy enqueue method using ajax.php (fallback)
         *
         * @param {string} prompt The prompt text
         * @param {string} type The content type
         */
        enqueueTaskLegacy: function(prompt, type) {
            var self = this;
            var formData = new FormData();
            formData.append('id', self.config.geminiId);
            formData.append('action', 'generate');
            formData.append('prompt', prompt);
            formData.append('type', type);
            formData.append('sesskey', M.cfg.sesskey);

            fetch(self.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    self.handleEnqueueSuccess(data);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            })
            .catch(function(error) {
                self.handleEnqueueError(error);
            });
        },

        /**
         * Handle successful task enqueue
         *
         * @param {Object} response Response data
         */
        handleEnqueueSuccess: function(response) {
            var self = this;
            var $btn = $('#btn-generate');
            var $statusDiv = $('#generation-status');

            $statusDiv.html(
                '<div class="alert alert-success">' +
                (self.config.strings.task_queued || 'Task queued! Generating content in background...') +
                '</div>'
            );

            // Re-enable button
            $btn.prop('disabled', false);
            $btn.html(self.config.strings.generate_btn || 'Generate with Gemini');

            // Trigger immediate status check
            this.checkStatus();
        },

        /**
         * Handle enqueue error
         *
         * @param {Object} error Error object
         */
        handleEnqueueError: function(error) {
            var self = this;
            var $btn = $('#btn-generate');
            var $statusDiv = $('#generation-status');

            var errorMsg = error.message || error.error || 'Unknown error occurred';

            $statusDiv.html(
                '<div class="alert alert-danger">' +
                '<strong>' + (self.config.strings.error_prefix || 'Error:') + '</strong> ' +
                errorMsg +
                '</div>'
            );

            // Re-enable button
            $btn.prop('disabled', false);
            $btn.html(self.config.strings.generate_btn || 'Generate with Gemini');
        },

        /**
         * Start polling for task status
         */
        startStatusPolling: function() {
            var self = this;

            // Initial check
            this.checkStatus();

            // Poll every 5 seconds
            this.pollInterval = setInterval(function() {
                self.checkStatus();
            }, 5000);
        },

        /**
         * Check task status
         */
        checkStatus: function() {
            var self = this;
            var $statusDiv = $('#generation-status');

            var formData = new FormData();
            formData.append('id', self.config.geminiId);
            formData.append('action', 'check_status');
            formData.append('sesskey', M.cfg.sesskey);

            fetch(self.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Check if newly completed
                    if (data.data.has_newly_completed) {
                        window.location.reload();
                        return;
                    }

                    // Check for errors
                    if (data.data.errors && data.data.errors.length > 0) {
                        var error = data.data.errors[0];
                        var $errorDiv = $('<div class="alert alert-danger">')
                            .html('<strong>Generation Failed:</strong> ' +
                                  (error.errormessage || 'Unknown error'));

                        $statusDiv.html('').append($errorDiv);

                        // Re-enable generate button
                        var $btn = $('#btn-generate');
                        if ($btn.length) {
                            $btn.prop('disabled', false);
                            $btn.html(self.config.strings.generate_btn || 'Generate with Gemini');
                        }
                        return;
                    }

                    // Check for pending tasks
                    if (data.data.pending_count > 0) {
                        var $alertDiv = $('<div class="alert alert-warning">');

                        // Add spinner
                        $alertDiv.append(
                            '<div class="spinner-border spinner-border-sm me-2" role="status"></div>'
                        );
                        $alertDiv.append(' Processing tasks in background:');

                        // Add task list
                        var $ul = $('<ul>');
                        data.data.tasks.forEach(function(task) {
                            var status = task.status == 1 ? 'Processing...' : 'Queued';
                            $ul.append($('<li>').text(task.type + ': ' + status));
                        });
                        $alertDiv.append($ul);

                        $alertDiv.append(
                            '<small>You can leave this page. We will notify you when done.</small>'
                        );

                        $statusDiv.html('').append($alertDiv);
                    } else if ($statusDiv.html().indexOf('Processing') !== -1) {
                        // Clear status if no longer processing
                        $statusDiv.html('');
                    }
                }
            })
            .catch(function(error) {
                // Silent fail for polling errors
                console.error('Status check error:', error);
            });
        },

        /**
         * Stop polling
         */
        stopPolling: function() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        }
    };

    return Generator;
});
