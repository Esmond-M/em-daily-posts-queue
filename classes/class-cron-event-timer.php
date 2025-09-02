<?php

declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

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
            
            if (
                /** @intelephense-ignore */
                false === as_has_scheduled_action( 'eg_1_weekdays_log' )
            ) {
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
                /** @intelephense-ignore */
                as_schedule_recurring_action( strtotime( '+1 weekdays 10pm America/Chicago' ), $oneWeekDayInterval, 'eg_1_weekdays_log' );
            }
        }


        public function update_cron_schedule_from_input($time_string, $interval = 86400) {
        // Remove all previously scheduled actions for this hook
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('eg_1_weekdays_log');
        }

        // Schedule new recurring action using user input
        $timestamp = strtotime($time_string . ' America/Chicago');
        if ($timestamp === false) {
            $this->send_admin_email('Invalid cron time', 'Could not parse time string: ' . esc_html($time_string));
            return false;
        }

        if (function_exists('as_schedule_recurring_action')) {
            as_schedule_recurring_action($timestamp, $interval, 'eg_1_weekdays_log');
            $this->send_admin_email('Cron time updated', 'New cron time: ' . esc_html($time_string));
            return true;
        }
        return false;
        }

        /**
         * Helper to send notification email to admin
         */
        private function send_admin_email($subject, $message) {
            $emailto = get_option('admin_email');
            wp_mail($emailto, $subject, $message);
        }

    }

    // Stub for Action Scheduler's as_has_scheduled_action.
    // Prevents Intelephense "undefined function" warnings in development.
    if (!function_exists('as_has_scheduled_action')) {
        function as_has_scheduled_action($hook) {}
    }

    // Stub for Action Scheduler's as_schedule_recurring_action.
    // Prevents Intelephense "undefined function" warnings in development.
    if (!function_exists('as_schedule_recurring_action')) {
        function as_schedule_recurring_action($timestamp, $interval, $hook) {}
    }
    // Stub for Action Scheduler's as_unschedule_all_actions.
    // Prevents Intelephense "undefined function" warnings in development.
    if (!function_exists('as_unschedule_all_actions')) {
        function as_unschedule_all_actions($hook) {}
    }
new CronEventTimer();