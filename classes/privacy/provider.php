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
 * Privacy provider for mod_gemini
 *
 * @package    mod_gemini
 * @copyright  2026 Sergio C
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gemini\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {
        // The main content table does not store user ID (teacher creates content for the course).
        // BUT the queue table DOES store who requested the generation.
        $collection->add_database_table('gemini_queue', [
            'userid' => 'privacy:metadata:gemini_queue:userid',
            'prompt' => 'privacy:metadata:gemini_queue:prompt',
            'timecreated' => 'privacy:metadata:gemini_queue:timecreated',
        ], 'privacy:metadata:gemini_queue');

        return $collection;
    }

    /**
     * Get the list of contexts where a user has stored data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        
        // Find contexts where user has entries in the queue
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {gemini} g ON g.id = cm.instance
                  JOIN {gemini_queue} q ON q.geminiid = g.id
                 WHERE q.userid = :userid";
                 
        $params = [
            'modname' => 'gemini',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified approved contextlist.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            // Check if this context is a gemini module
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            // Export Queue Data (Prompts)
            // Get the gemini instance id from context
            $cm = get_coursemodule_from_id('gemini', $context->instanceid);
            if (!$cm) { continue; }

            $queues = $DB->get_records('gemini_queue', ['geminiid' => $cm->instance, 'userid' => $user->id]);
            
            if ($queues) {
                $data = [];
                foreach ($queues as $q) {
                    $data[] = [
                        'prompt' => $q->prompt,
                        'type' => $q->type,
                        'timecreated' => transform::datetime($q->timecreated),
                        'status' => $q->status
                    ];
                }
                writer::with_context($context)->export_data(['Generation History'], (object)$data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        
        $cm = get_coursemodule_from_id('gemini', $context->instanceid);
        if (!$cm) { return; }

        // Delete all queue records for this activity
        $DB->delete_records('gemini_queue', ['geminiid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('gemini', $context->instanceid);
            if (!$cm) { continue; }

            $DB->delete_records('gemini_queue', ['geminiid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
