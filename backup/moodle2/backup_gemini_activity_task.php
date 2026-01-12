<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/gemini/backup/moodle2/backup_gemini_stepslib.php');

class backup_gemini_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_gemini_activity_structure_step('gemini_structure', 'gemini.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        return $content; // No logic needed for now
    }
}
