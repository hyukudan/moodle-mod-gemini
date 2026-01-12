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
 * Restore steps for mod_gemini
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $oldid = $data->id;
        $data->geminiid = $this->get_new_parentid('gemini');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Handle parent_id mapping if it exists (for version history)
        if (!empty($data->parent_id)) {
            $data->parent_id = $this->get_mappingid('gemini_content', $data->parent_id);
        }

        // Insert the content record
        $newitemid = $DB->insert_record('gemini_content', $data);

        // Set mapping for this content record (needed for file restore)
        $this->set_mapping('gemini_content', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add gemini intro files
        $this->add_related_files('mod_gemini', 'intro', null);

        // Add content-related files (audio, quiz) using the content mapping
        $this->add_related_files('mod_gemini', 'audio', 'gemini_content');
        $this->add_related_files('mod_gemini', 'quiz', 'gemini_content');
    }
}
