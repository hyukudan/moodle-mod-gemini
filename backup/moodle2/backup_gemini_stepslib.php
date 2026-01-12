<?php
defined('MOODLE_INTERNAL') || die();

class backup_gemini_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separate
        $gemini = new backup_nested_element('gemini', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified'
        ));

        $contents = new backup_nested_element('contents');
        
        $content = new backup_nested_element('content', array('id'), array(
            'type', 'content', 'config', 'timecreated', 'timemodified'
        ));

        // Build the tree
        $gemini->add_child($contents);
        $contents->add_child($content);

        // Define sources
        $gemini->set_source_table('gemini', array('id' => backup::VAR_ACTIVITYID));

        // Define source for contents (related to gemini instance)
        $content->set_source_table('gemini_content', array('geminiid' => backup::VAR_PARENTID));

        // Define id annotations
        // (None needed for custom tables unless they link to users or other modules)

        // Define file annotations
        $gemini->annotate_files('mod_gemini', 'intro', null); // Intro files
        
        // Annotate files for our content (audio, quiz)
        // Note: 'content' element is the parent of these files
        $content->annotate_files('mod_gemini', 'audio', 'id');
        $content->annotate_files('mod_gemini', 'quiz', 'id');

        return $this->prepare_activity_structure($gemini);
    }
}
