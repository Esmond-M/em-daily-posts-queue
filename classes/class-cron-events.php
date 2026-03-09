<?php
declare(strict_types=1);
/**
 * Handles the weekly update for net_submission queue via cron event.
 *
 * - Removes the first item in the queue
 * - Renumbers the queue
 * - Updates the database
 * - Deletes the corresponding post
 * - Sends notification emails
 *
 * @return void
 */
namespace EmDailyPostsQueue\init_plugin\Classes;
require_once __DIR__ . '/class-photo-submission-utils.php';

class CronEvents
{

    private $utils;

    /**
     * Constructor: Sets up cron event action for weekly net_submission update.
     */
        public function __construct()
        {

        $this->utils = new PhotoNetSubmissionUtils();
        add_action( 'eg_1_weekdays_log',[$this, 'eg_action_net_submission_weekly_update' ]  ); // this action "eg_1_weekdays_log" is also called in another file edpq-class-cron-events.php

        }
    

        public function eg_action_net_submission_weekly_update() {
            $queue = $this->utils->get_queue_list();
            if (empty($queue)) {
                echo 'Queue is empty or database row does not exist.';
                return;
            }
            $old_queue    = $queue;
            $idToRemove   = $queue[0]['postid'] ?? null;
            array_shift($queue);
            foreach ($queue as $i => &$item) {
                $item['queueNumber'] = $i + 1;
            }
            unset($item);

            // Optimistic concurrency: re-read and compare
            $current = $this->utils->get_queue_list();
            $diff    = $this->utils->edpqcompareMultiDimensional($current, $old_queue);

            if (empty($diff)) {
                $ok = $this->utils->update_queue_list_in_db($queue);
                if ($ok) {
                    if ($idToRemove) {
                        wp_delete_post((int) $idToRemove, true);
                    }
                    echo 'Queue List updated. Item has been removed. Next weekly post active.';
                    $subject = 'Next submission Live: Check Status - ' . date('m-d-y');
                    $message = '<a href="' . esc_url(site_url()) . '/wp-admin/tools.php?page=action-scheduler&status=pending">View it</a>';
                    $this->utils->send_admin_email($subject, $message);
                } else {
                    echo 'SQL Error: Could not update queue list.';
                }
            } else {
                echo 'This window is out of date. Weekly update failed.';
                $subject = 'Weekly update failed: Someone was editing ' . date('m-d-y');
                $message = 'Window was out of date when cron event ran.';
                $this->utils->send_admin_email($subject, $message);
            }
        }
    
} 


new CronEvents;