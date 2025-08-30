<?php

declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;


if (!class_exists('CronEventTimer')) {

    /**
     * CronEventTimer Class
     *
     * Handles scheduling of recurring cron events for the plugin.
     * - Schedules a custom action hook to run at a specific weekday/time interval
     * - Notifies the WordPress admin if the timer interval is set to a default value
     * - Used to trigger queue and post-processing logic in other plugin classes
     */
    class CronEventTimer
    {

        /**
         * Declaring constructor
         */
        public function __construct()
        {

        add_action( 'init', [$this, 'eg_schedule_1_weekdays_log']  );

        }

        /**
         * Schedule an action with the hook 'eg_[insert time here]_log' to run every x amount of time
         * so that our callbacks in the class cron events is run then.
         */

        public function eg_schedule_1_weekdays_log() {
            if ( false === as_has_scheduled_action( 'eg_1_weekdays_log' ) ) {
                $str1Weekdays = strtotime( '+1 weekday 10pm America/Chicago' );
                $strToday = strtotime( 'Now America/Chicago' );
                $startDate = new \DateTime( gmdate("Y-m-d H:i:s", $strToday) );//start time
                $nextDate = new \DateTime( gmdate("Y-m-d H:i:s", $str1Weekdays));//end time
                $oneWeekDayInterval = $nextDate->getTimestamp() - $startDate->getTimestamp();

                if($oneWeekDayInterval < 86400){ // this number is seconds to hours
                    $oneWeekDayInterval = 86400;
                    $this->send_admin_email('Auto timer not working', 'Had to set default 1 days<br>timer:' . $oneWeekDayInterval);
                } else {
                    $this->send_admin_email('Run timer value', 'timer:' . $oneWeekDayInterval);
                }
                as_schedule_recurring_action( strtotime( '+1 weekdays 10pm America/Chicago' ), $oneWeekDayInterval, 'eg_1_weekdays_log' );
            }
        }

        /**
         * Helper to send notification email to admin
         */
        private function send_admin_email($subject, $message) {
            $emailto = get_option('admin_email');
            wp_mail($emailto, $subject, $message);
        }

    }

}

new CronEventTimer();