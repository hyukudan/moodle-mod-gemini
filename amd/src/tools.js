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
 * Teaching Tools Module for mod_gemini.
 * Handles rubric generation, content editing, and regeneration.
 *
 * @module     mod_gemini/tools
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/modal_events'],
function($, Ajax, Notification, ModalFactory, ModalEvents) {

    var Tools = {

        /**
         * Configuration object
         */
        config: null,

        /**
         * Bootstrap modals
         */
        toolsModal: null,
        editModal: null,

        /**
         * Initialize the tools module
         *
         * @param {Object} params Configuration parameters
         * @param {int} params.geminiId The Gemini activity instance ID
         * @param {string} params.ajaxUrl URL to ajax.php
         * @param {string} params.rawContent The raw JSON content for editing
         * @param {Object} params.strings Language strings
         */
        init: function(params) {
            this.config = params;
            this.setupRubricGenerator();
            this.setupContentEditor();
            this.setupRegenerate();
        },

        /**
         * Setup rubric generator
         */
        setupRubricGenerator: function() {
            var self = this;

            // Open tools modal
            $('#btn-tools-rubric').on('click', function() {
                var toolsModal = new bootstrap.Modal(document.getElementById('toolsModal'));
                toolsModal.show();
            });

            // Generate rubric
            $('#btn-do-rubric').on('click', function() {
                self.generateRubric();
            });
        },

        /**
         * Generate rubric
         */
        generateRubric: function() {
            var self = this;
            var $output = $('#tool-output');
            var $loading = $('#tool-loading');

            // Show loading
            $output.hide();
            $loading.show();

            // Prepare request
            var formData = new FormData();
            formData.append('id', self.config.geminiId);
            formData.append('action', 'tools_rubric');
            formData.append('prompt', 'this topic');
            formData.append('sesskey', M.cfg.sesskey);

            fetch(self.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                $loading.hide();

                if (data.success) {
                    $output.html(data.data.html);
                    $output.show();
                } else {
                    var $errorDiv = $('<div class="alert alert-danger">')
                        .text('Error: ' + (data.message || 'Unknown error'));
                    $output.html('').append($errorDiv);
                    $output.show();
                }
            })
            .catch(function(error) {
                $loading.hide();
                var $errorDiv = $('<div class="alert alert-danger">')
                    .text('Error: ' + error.message);
                $output.html('').append($errorDiv);
                $output.show();
            });
        },

        /**
         * Setup content editor
         */
        setupContentEditor: function() {
            var self = this;

            // Open edit modal
            $('#btn-edit-content').on('click', function() {
                var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                var $textarea = $('#edit-content-area');
                $textarea.val(self.config.rawContent);
                editModal.show();
            });

            // Save edited content
            $('#btn-save-edit').on('click', function() {
                self.saveEditedContent();
            });
        },

        /**
         * Save edited content
         */
        saveEditedContent: function() {
            var self = this;
            var $btn = $('#btn-save-edit');
            var $textarea = $('#edit-content-area');
            var $errorDiv = $('#edit-error');
            var newContent = $textarea.val();

            // Clear previous errors
            $errorDiv.text('');

            // Validate
            if (!newContent.trim()) {
                $errorDiv.text(self.config.strings.content_empty || 'Content cannot be empty');
                return;
            }

            // Disable button
            $btn.prop('disabled', true);
            $btn.text(self.config.strings.saving || 'Saving...');

            // Prepare request
            var formData = new FormData();
            formData.append('id', self.config.geminiId);
            formData.append('action', 'update');
            formData.append('content', newContent);
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
                    // Reload page to show updated content
                    window.location.reload();
                } else {
                    $errorDiv.text(data.message || 'Error saving');
                    $btn.prop('disabled', false);
                    $btn.text(self.config.strings.save_changes || 'Save Changes');
                }
            })
            .catch(function(error) {
                $errorDiv.text('Network Error: ' + error.message);
                $btn.prop('disabled', false);
                $btn.text(self.config.strings.save_changes || 'Save Changes');
            });
        },

        /**
         * Setup regenerate button
         */
        setupRegenerate: function() {
            var self = this;

            $('#btn-regenerate').on('click', function() {
                var confirmMsg = self.config.strings.confirm_regenerate ||
                    'Are you sure? This will DELETE the current content permanently.';

                if (confirm(confirmMsg)) {
                    self.regenerateContent();
                }
            });
        },

        /**
         * Regenerate content (delete and reset)
         */
        regenerateContent: function() {
            var self = this;

            var formData = new FormData();
            formData.append('id', self.config.geminiId);
            formData.append('action', 'reset');
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
                    window.location.reload();
                }
            })
            .catch(function(error) {
                Notification.exception({
                    message: 'Error regenerating content: ' + error.message
                });
            });
        }
    };

    return Tools;
});
