<?php

declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;


if (!class_exists('CronEventTimer')) {

    class CronEventTimer
    {

        /**
        Declaring constructor
         */
        public function __construct()
        {

        add_action( 'init', [$this, 'eg_schedule_1_weekdays_log']  );

        }

        /**
         * Schedule an action with the hook 'eg_[insert time here]_log' to run every 15 mins 
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
                        $emailto = 'esmondmccain@gmail.com';

                        // Email subject, "New {post_type_label}"
                        $subject = 'Auto timer not working';

                        // Email body
                        $message = 'Had to set default 1 days<br>' . 'timer:' . $oneWeekDayInterval ;

                        wp_mail( $emailto, $subject, $message );
            }
            
            else {
                       $emailto = 'esmondmccain@gmail.com';

                        // Email subject, "New {post_type_label}"
                        $subject = 'Run timer value';

                        // Email body
                        $message = 'timer:' . $oneWeekDayInterval ;

                        wp_mail( $emailto, $subject, $message );	
            }
           as_schedule_recurring_action( strtotime( '+1 weekdays 10pm America/Chicago' ), $oneWeekDayInterval, 'eg_1_weekdays_log' );
           }
       } 

    }

}

new CronEventTimer();