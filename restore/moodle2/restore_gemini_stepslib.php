<?php
defined('MOODLE_INTERNAL') || die();

class restore_gemini_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();

        $paths[] = new restore_path_element('gemini', '/activity/gemini');
        $paths[] = new restore_path_element('gemini_content', '/activity/gemini/contents/content');

        return $paths;
    }

    protected function process_gemini($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the gemini record
        $newitemid = $DB->insert_record('gemini', $data);
        
        // Immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_gemini_content($data) {
        global $DB;

        $data = (object)$data;
        $data->geminiid = $this->get_new_parentid('gemini');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the content record
        $newitemid = $DB->insert_record('gemini_content', $data);

        // Restore files for this content (audio/quiz)
        $this->add_related_files('mod_gemini', 'audio', 'id');
        $this->add_related_files('mod_gemini', 'quiz', 'id');
    }

    protected function after_execute() {
        // Add any post-processing after all steps here
        $this->add_related_files('mod_gemini', 'intro', null);
    }
}
