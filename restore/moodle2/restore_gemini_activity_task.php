<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/gemini/restore/moodle2/restore_gemini_stepslib.php');

class restore_gemini_activity_task extends restore_activity_task {

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
        $this->add_step(new restore_gemini_activity_structure_step('gemini_structure', 'gemini.xml'));
    }

    /**
     * Define the contents for this activity
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('gemini', array('intro'), 'gemini');

        return $contents;
    }

    /**
     * Define the decoding rules for links
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('GEMINIVIEWBYID', '/mod/gemini/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('GEMINIINDEX', '/mod/gemini/index.php?id=$1', 'course');

        return $rules;
    }

    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('gemini', 'add', 'view.php?id={course_module}', '{name}');
        $rules[] = new restore_log_rule('gemini', 'update', 'view.php?id={course_module}', '{name}');
        $rules[] = new restore_log_rule('gemini', 'view', 'view.php?id={course_module}', '{name}');

        return $rules;
    }
}
