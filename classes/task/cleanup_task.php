<?php
namespace mod_gemini\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('cleanup_task', 'mod_gemini');
    }

    public function execute() {
        global $DB;
        
        // Delete queue records older than 30 days
        $cutoff = time() - (30 * 24 * 60 * 60);
        $DB->delete_records_select('gemini_queue', 'timecreated < ?', [$cutoff]);
        
        mtrace("Cleaned up old Gemini queue records.");
    }
}

