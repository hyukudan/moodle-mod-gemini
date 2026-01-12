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
 * Restore task for mod_gemini
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
