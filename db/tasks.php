<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'mod_gemini\task\cleanup_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3', // Run at 3 AM
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];

