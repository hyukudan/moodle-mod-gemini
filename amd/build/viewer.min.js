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
 * Content Viewer Module for mod_gemini.
 * Handles interactive content display, specifically flashcards.
 *
 * @module     mod_gemini/viewer
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification'], function($, Notification) {

    var Viewer = {

        /**
         * Configuration object
         */
        config: null,

        /**
         * Current card index
         */
        currentCard: 0,

        /**
         * Total number of cards
         */
        totalCards: 0,

        /**
         * Initialize the viewer module (for flashcards)
         *
         * @param {Object} params Configuration parameters
         * @param {int} params.geminiId The Gemini activity instance ID
         * @param {int} params.totalCards Total number of flashcards
         * @param {string} params.ajaxUrl URL to ajax.php
         * @param {Object} params.strings Language strings
         */
        init: function(params) {
            this.config = params;
            this.totalCards = params.totalCards || 0;
            this.currentCard = 0;

            if (this.totalCards > 0) {
                this.setupFlashcards();
            }
        },

        /**
         * Setup flashcard functionality
         */
        setupFlashcards: function() {
            var self = this;
            var $containers = $('.gemini-flashcard-container');
            var $prevBtn = $('#prev-card');
            var $nextBtn = $('#next-card');
            var $counter = $('#card-counter');

            // Flip card on click
            $containers.on('click', function() {
                self.flipCard($(this));
            });

            // Flip card on keyboard (Enter/Space)
            $containers.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.flipCard($(this));
                }
            });

            // Navigation buttons
            $prevBtn.on('click', function() {
                self.previousCard();
            });

            $nextBtn.on('click', function() {
                self.nextCard();
            });

            // Initial update
            this.updateNavigation();
        },

        /**
         * Flip a flashcard
         *
         * @param {jQuery} $card The card element to flip
         */
        flipCard: function($card) {
            $card.toggleClass('flipped');
        },

        /**
         * Go to next card
         */
        nextCard: function() {
            var self = this;

            if (this.currentCard < this.totalCards - 1) {
                this.currentCard++;
                this.updateNavigation();
            } else {
                // Finish deck - record completion
                this.finishDeck();
            }
        },

        /**
         * Go to previous card
         */
        previousCard: function() {
            if (this.currentCard > 0) {
                this.currentCard--;
                this.updateNavigation();
            }
        },

        /**
         * Update navigation UI
         */
        updateNavigation: function() {
            var $containers = $('.gemini-flashcard-container');
            var $prevBtn = $('#prev-card');
            var $nextBtn = $('#next-card');
            var $counter = $('#card-counter');

            // Show/hide cards
            $containers.each(function(index) {
                $(this).css('display', index === this.currentCard ? 'block' : 'none');
            }.bind(this));

            // Update counter
            $counter.text((this.currentCard + 1) + ' / ' + this.totalCards);

            // Update buttons
            $prevBtn.prop('disabled', this.currentCard === 0);
            $nextBtn.text(
                this.currentCard === this.totalCards - 1 ?
                (this.config.strings.finish || 'Finish') :
                (this.config.strings.next_card || 'Next Card')
            );
        },

        /**
         * Finish deck and record grade
         */
        finishDeck: function() {
            var self = this;
            var $nextBtn = $('#next-card');

            // Disable button
            $nextBtn.prop('disabled', true);
            $nextBtn.text(self.config.strings.saving || 'Saving...');

            // Prepare request
            var formData = new FormData();
            formData.append('id', self.config.geminiId);
            formData.append('action', 'grade_completion');
            formData.append('sesskey', M.cfg.sesskey);

            fetch(self.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                alert(self.config.strings.deck_finished || 'Deck finished! Grade recorded: 100%');
                $nextBtn.text(self.config.strings.finished || 'Finished');
            })
            .catch(function(error) {
                console.error('Error recording completion:', error);
                alert(self.config.strings.deck_finished || 'Deck finished!');
                $nextBtn.text(self.config.strings.finished || 'Finished');
            });
        }
    };

    return Viewer;
});
