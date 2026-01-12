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
 * Backup steps for mod_gemini
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
            'type', 'content', 'config', 'timecreated', 'timemodified',
            'version', 'parent_id', 'prompt', 'is_current'
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
