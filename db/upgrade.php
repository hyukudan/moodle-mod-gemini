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
 * Upgrade script for mod_gemini.
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_gemini upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_gemini_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade to version 2026011204.
    if ($oldversion < 2026011204) {

        // Define fields to be added to gemini_queue.
        $table = new xmldb_table('gemini_queue');

        // Add retries field.
        $field = new xmldb_field('retries', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'errormessage');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add progress field.
        $field = new xmldb_field('progress', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'retries');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add progressmsg field.
        $field = new xmldb_field('progressmsg', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'progress');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index on userid.
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Gemini savepoint reached.
        upgrade_mod_savepoint(true, 2026011204, 'gemini');
    }

    // Upgrade to version 2026011205.
    if ($oldversion < 2026011205) {

        // Define fields to be added to gemini_content.
        $table = new xmldb_table('gemini_content');

        // Add version field.
        $field = new xmldb_field('version', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'timemodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add parent_id field.
        $field = new xmldb_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'version');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gemini savepoint reached.
        upgrade_mod_savepoint(true, 2026011205, 'gemini');
    }

    // Upgrade to version 2026011206.
    if ($oldversion < 2026011206) {

        // Define fields to be added to gemini_content for versioning.
        $table = new xmldb_table('gemini_content');

        // Add prompt field.
        $field = new xmldb_field('prompt', XMLDB_TYPE_TEXT, null, null, null, null, null, 'parent_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add is_current field.
        $field = new xmldb_field('is_current', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'prompt');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index on geminiid and is_current.
        $index = new xmldb_index('geminiid_current', XMLDB_INDEX_NOTUNIQUE, ['geminiid', 'is_current']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Gemini savepoint reached.
        upgrade_mod_savepoint(true, 2026011206, 'gemini');
    }

    // Upgrade to version 2026011207.
    if ($oldversion < 2026011207) {

        // Define composite index to be added to gemini_queue for performance optimization.
        $table = new xmldb_table('gemini_queue');

        // Add composite index on geminiid, status, and timemodified.
        // This optimizes queries in ajax.php that filter by geminiid and status, ordered by timemodified.
        $index = new xmldb_index('geminiqueue_gemsta_tim_ix', XMLDB_INDEX_NOTUNIQUE, ['geminiid', 'status', 'timemodified']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Gemini savepoint reached.
        upgrade_mod_savepoint(true, 2026011207, 'gemini');
    }

    return true;
}
