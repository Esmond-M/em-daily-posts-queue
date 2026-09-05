<?php
/**
 * CronEventTimer Class
 *
 * Handles scheduling of recurring cron events for the plugin.
 * - Schedules a custom action hook to run at a specific weekday/time interval
 * - Notifies the WordPress admin if the timer interval is set to a default value
 * - Used to trigger queue and post-processing logic in other plugin classes
 */
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;
require_once __DIR__ . '/class-photo-submission-utils.php';

class CronEventTimer {
    const HOOK = 'eg_1_weekdays_log';
    const OPTION = 'edpq_schedule_settings';

    private $utils;
    /**
     * Declaring constructor
     */
    public function __construct()
    {

        $this->utils = new PhotoNetSubmissionUtils();
        add_action( 'init', [$this, 'eg_schedule_1_weekdays_log']  );

    }

    /**
     * Schedule an action with the hook 'eg_[insert time here]_log' to run every x amount of time
     * so that our callbacks in the class cron events is run then.
     */

    public function eg_schedule_1_weekdays_log() {
        if (
            /** @intelephense-ignore */
            function_exists( '\\as_has_scheduled_action' ) && false === \as_has_scheduled_action( self::HOOK )
        ) {
            $this->schedule_next_from_settings();
        }
    }

    public function get_schedule_settings() {
        return $this->normalize_schedule_settings(get_option(self::OPTION, []));
    }

    public function get_schedule_choices() {
        return [
            'daily' => __('Daily', 'em-daily-posts-queue'),
            'weekdays' => __('Weekdays', 'em-daily-posts-queue'),
            'selected' => __('Selected days', 'em-daily-posts-queue'),
        ];
    }

    public function get_weekday_choices() {
        return [
            0 => __('Sunday', 'em-daily-posts-queue'),
            1 => __('Monday', 'em-daily-posts-queue'),
            2 => __('Tuesday', 'em-daily-posts-queue'),
            3 => __('Wednesday', 'em-daily-posts-queue'),
            4 => __('Thursday', 'em-daily-posts-queue'),
            5 => __('Friday', 'em-daily-posts-queue'),
            6 => __('Saturday', 'em-daily-posts-queue'),
        ];
    }

    public function update_schedule_settings(array $settings) {
        if (!$this->validate_schedule_settings($settings)) {
            return false;
        }

        $settings = $this->normalize_schedule_settings($settings);
        $timestamp = $settings['paused'] ? false : $this->calculate_next_run_timestamp($settings);
        if (!$settings['paused'] && !$timestamp) {
            return false;
        }

        if (!function_exists('\\as_unschedule_all_actions')) {
            return false;
        }

        if (!$settings['paused'] && !function_exists('\\as_schedule_single_action')) {
            return false;
        }

        \as_unschedule_all_actions(self::HOOK);

        if (!$settings['paused']) {
            $scheduled = \as_schedule_single_action($timestamp, self::HOOK);
            if (!$scheduled) {
                return false;
            }
        }

        update_option(self::OPTION, $settings);
        return true;
    }

    public function validate_schedule_settings($settings) {
        if (!is_array($settings)) {
            return false;
        }

        if (!isset($settings['mode']) || !in_array($settings['mode'], array_keys($this->get_schedule_choices()), true)) {
            return false;
        }

        if (!isset($settings['time']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', (string) $settings['time'])) {
            return false;
        }

        if ('selected' === $settings['mode']) {
            $days = array_values(array_intersect(array_map('absint', (array) ($settings['days'] ?? [])), [0, 1, 2, 3, 4, 5, 6]));
            if (!$days) {
                return false;
            }
        }

        return true;
    }

    public function schedule_next_from_settings($now = null) {
        $settings = $this->get_schedule_settings();
        if ($settings['paused']) {
            return true;
        }

        $timestamp = $this->calculate_next_run_timestamp($settings, $now);
        if (!$timestamp || !function_exists('\\as_schedule_single_action')) {
            return false;
        }

        return (bool) \as_schedule_single_action($timestamp, self::HOOK);
    }

    public function calculate_next_run_timestamp(array $settings, $now = null) {
        $settings = $this->normalize_schedule_settings($settings);
        if ($settings['paused']) {
            return false;
        }

        $timezone = $this->get_wp_timezone();
        $current = null === $now ? new \DateTimeImmutable('now', $timezone) : (new \DateTimeImmutable('@' . (int) $now))->setTimezone($timezone);

        for ($day_offset = 0; $day_offset <= 14; $day_offset++) {
            $candidate_date = $current->modify('+' . $day_offset . ' days')->format('Y-m-d');
            $candidate = new \DateTimeImmutable($candidate_date . ' ' . $settings['time'], $timezone);
            if ((int) $candidate->format('U') <= (int) $current->format('U')) {
                continue;
            }

            if (in_array((int) $candidate->format('w'), $settings['days'], true)) {
                return (int) $candidate->format('U');
            }
        }

        return false;
    }

    public function normalize_schedule_settings($settings) {
        $defaults = [
            'mode' => 'weekdays',
            'days' => [1, 2, 3, 4, 5],
            'time' => '22:00',
            'paused' => false,
        ];

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings = array_merge($defaults, $settings);
        if (!in_array($settings['mode'], array_keys($this->get_schedule_choices()), true)) {
            $settings['mode'] = $defaults['mode'];
        }

        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', (string) $settings['time'])) {
            $settings['time'] = $defaults['time'];
        }
        [$hour, $minute] = array_map('intval', explode(':', $settings['time']));
        $settings['time'] = sprintf('%02d:%02d', $hour, $minute);

        if ('daily' === $settings['mode']) {
            $settings['days'] = [0, 1, 2, 3, 4, 5, 6];
        } elseif ('weekdays' === $settings['mode']) {
            $settings['days'] = [1, 2, 3, 4, 5];
        } else {
            $settings['days'] = array_values(array_unique(array_map('absint', (array) $settings['days'])));
            $settings['days'] = array_values(array_intersect($settings['days'], [0, 1, 2, 3, 4, 5, 6]));
            sort($settings['days']);
            if (!$settings['days']) {
                $settings['days'] = $defaults['days'];
            }
        }

        $settings['paused'] = !empty($settings['paused']);
        return $settings;
    }

    private function get_wp_timezone() {
        if (function_exists('wp_timezone')) {
            return wp_timezone();
        }

        $wp_timezone_string = get_option('timezone_string');
        return new \DateTimeZone($wp_timezone_string ? $wp_timezone_string : 'UTC');
    }


    public function update_cron_schedule_from_input($time_string, $interval = 86400) {
        // Get timezone from WordPress settings
        $wp_timezone_string = get_option('timezone_string');
        $wp_timezone = $wp_timezone_string ? $wp_timezone_string : 'UTC';

        $timestamp = strtotime($time_string . ' ' . $wp_timezone);
        if ($timestamp === false) {
            $this->utils->send_admin_email('Invalid cron time', 'Could not parse time string: ' . esc_html($time_string));
            return false;
        }

        if (!function_exists('\\as_unschedule_all_actions') || !function_exists('\\as_schedule_recurring_action')) {
            return false;
        }

        \as_unschedule_all_actions(self::HOOK);
        $scheduled = \as_schedule_recurring_action($timestamp, $interval, self::HOOK);
        if ($scheduled) {
            $this->utils->send_admin_email('Cron time updated', 'New cron time: ' . esc_html($time_string));
            return true;
        }

        return false;
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