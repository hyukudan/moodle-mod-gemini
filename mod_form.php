<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_gemini_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $mform = $this->_form;

        // --- General ---
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Intro (Description)
        $this->standard_intro_elements();

        // Standard coursemodule elements
        $this->standard_coursemodule_elements();

        // Standard buttons
        $this->add_action_buttons();
    }
}
