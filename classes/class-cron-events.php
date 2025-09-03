<?php
declare(strict_types=1);
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
        public function eg_action_net_submission_weekly_update() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
            $row = $wpdb->get_row("SELECT list FROM $table_name WHERE id='1';", ARRAY_A);
            if (empty($row) || empty($row['list'])) {
                echo 'Database row does not exist.';
                return;
            }
            $stored_queue_list_arr = unserialize(base64_decode($row['list']));
            $old_stored_queue_list_arr = $stored_queue_list_arr;
            $idToRemove = $stored_queue_list_arr[0]['postid'] ?? null;
            unset($stored_queue_list_arr[0]);
            $reNumberBeforeSubmit = array_values($stored_queue_list_arr);
            foreach ($reNumberBeforeSubmit as $i => &$item) {
                $item['queueNumber'] = $i + 1;
            }
            unset($item);

            // Check for update/conflict
            $row2 = $wpdb->get_row("SELECT list FROM $table_name WHERE id='1';", ARRAY_A);
            if (empty($row2) || empty($row2['list'])) {
                echo 'Database row does not exist.';
                return;
            }
            $Second_stored_queue_list_arr = unserialize(base64_decode($row2['list']));
            $isWindowOutdated = $this->utils->edpqcompareMultiDimensional($Second_stored_queue_list_arr, $old_stored_queue_list_arr);

            if (empty($isWindowOutdated)) {
                $serialized = base64_encode(serialize($reNumberBeforeSubmit));
                $result = $wpdb->query($wpdb->prepare("UPDATE $table_name SET list=%s WHERE id=1", $serialized));
                if ($result !== false && $result > 0) {
                    if ($idToRemove) {
                        wp_delete_post($idToRemove, true);
                    }
                    echo 'Queue List updated. Item has been removed. Next weekly post active.';
                    $subject = 'Next submission Live: Check Status - ' . date('m-d-y');
                    $message = '<a href="' . site_url() . '/wp-admin/tools.php?page=action-scheduler&status=pending">View it</a>';
                    $this->utils->send_admin_email($subject, $message);
                } else {
                    echo "SQL Error: Could not update queue list.";
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