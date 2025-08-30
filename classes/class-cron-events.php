<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('CronEvents')) {

    class CronEvents
    {
        /**
         * Helper to get the queue list from DB.
         */
        private function get_queue_list_from_db($conn) {
            $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            return $row['list'] ?? '';
        }

        /**
         * Helper to update the queue list in DB.
         */
        private function update_queue_list_in_db($conn, $queueList) {
            $serialized = base64_encode(serialize($queueList));
            $sql = "UPDATE edpq_net_photos_queue_order SET list='" . $serialized . "' WHERE id=1";
            return $conn->query($sql);
        }

        /**
         * Helper to send notification email.
         */
        private function send_admin_email($subject, $message) {
            $emailto = get_option('admin_email');
            wp_mail($emailto, $subject, $message);
        }

    /**
     * Constructor: Sets up cron event action for weekly net_submission update.
     */
        public function __construct()
        {

        add_action( 'eg_1_weekdays_log',[$this, 'eg_action_net_submission_weekly_update' ]  ); // this action "eg_1_weekdays_log" is also called in another file edpq-class-cron-events.php

        }
    /**
     * Compares two multidimensional arrays and returns differences.
     *
     * @param array $array1 First array to compare.
     * @param array $array2 Second array to compare.
     * @param bool $strict Whether to use strict comparison.
     * @return array Differences found in $array1 compared to $array2.
     * @throws \InvalidArgumentException If $array1 is not an array.
     */
        public function edpqcompareMultiDimensional($array1, $array2, $strict = true){
            if (!is_array($array1)) {
                throw new \InvalidArgumentException('$array1 must be an array!');
            }

            if (!is_array($array2)) {
                return $array1;
            }

            $result = array();

            foreach ($array1 as $key => $value) {
                if (!array_key_exists($key, $array2)) {
                    $result[$key] = $value;
                    continue;
                }

                if (is_array($value) && count($value) > 0) {
                    $recursiveArrayDiff = $this->edpqcompareMultiDimensional($value, $array2[$key], $strict);

                    if (count($recursiveArrayDiff) > 0) {
                        $result[$key] = $recursiveArrayDiff;
                    }

                    continue;
                }

                $value1 = $value;
                $value2 = $array2[$key];

                if ($strict ? is_float($value1) && is_float($value2) : is_float($value1) || is_float($value2)) {
                    $value1 = (string) $value1;
                    $value2 = (string) $value2;
                }

                if ($strict ? $value1 !== $value2 : $value1 != $value2) {
                    $result[$key] = $value;
                }
            }

            return $result;
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
            // Connect to DB
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$conn) {
                error_log("Connection to database failed: " . mysqli_connect_error());
                return;
            }

            $list = $this->get_queue_list_from_db($conn);
            $conn->close();
            if (empty($list)) {
                echo 'Database row does not exist.';
                return;
            }

            $stored_queue_list_arr = unserialize(base64_decode($list));
            $old_stored_queue_list_arr = $stored_queue_list_arr;
            $idToRemove = $stored_queue_list_arr[0]['postid'] ?? null;
            unset($stored_queue_list_arr[0]);
            $reNumberBeforeSubmit = array_values($stored_queue_list_arr);
            foreach ($reNumberBeforeSubmit as &$item) {
                $item['queueNumber'] = $item['queueNumber'] - 1;
            }
            unset($item);

            // Reconnect for update/check
            $conn2 = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$conn2) {
                error_log("Connection to database failed: " . mysqli_connect_error());
                return;
            }
            $list2 = $this->get_queue_list_from_db($conn2);
            if (empty($list2)) {
                echo 'Database row does not exist.';
                $conn2->close();
                return;
            }
            $Second_stored_queue_list_arr = unserialize(base64_decode($list2));
            $isWindowOutdated = $this->edpqcompareMultiDimensional($Second_stored_queue_list_arr, $old_stored_queue_list_arr);

            if (empty($isWindowOutdated)) {
                $updateSuccess = $this->update_queue_list_in_db($conn2, $reNumberBeforeSubmit);
                if ($updateSuccess) {
                    if ($idToRemove) {
                        wp_delete_post($idToRemove, true);
                    }
                    echo 'Queue List updated. Item has been removed. Next weekly post active.';
                    $subject = 'Next submission Live: Check Status - ' . date('m-d-y');
                    $message = '<a href="' . site_url() . '/wp-admin/tools.php?page=action-scheduler&status=pending">View it</a>';
                    $this->send_admin_email($subject, $message);
                } else {
                    echo "SQL Error: Could not update queue list.";
                }
            } else {
                echo 'This window is out of date. Weekly update failed.';
                $subject = 'Weekly update failed: Someone was editing ' . date('m-d-y');
                $message = 'Window was out of date when cron event ran.';
                $this->send_admin_email($subject, $message);
            }
            $conn2->close();
        }
    

    } 

}

new CronEvents;