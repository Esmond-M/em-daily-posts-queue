<?php
/** Full WordPress integration, explicitly separated from composer test. */

$edpq_root = dirname(__DIR__, 2);
require_once $edpq_root . '/vendor/autoload.php';
define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php');
define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $edpq_root . '/vendor/yoast/phpunit-polyfills');
require_once $edpq_root . '/vendor/wp-phpunit/wp-phpunit/includes/functions.php';

$GLOBALS['edpq_test_scheduled_actions'] = [];
if (!function_exists('as_has_scheduled_action')) {
    function as_has_scheduled_action($hook) {
        return !empty($GLOBALS['edpq_test_scheduled_actions'][$hook]);
    }
}
if (!function_exists('as_schedule_recurring_action')) {
    function as_schedule_recurring_action($timestamp, $interval, $hook) {
        $GLOBALS['edpq_test_scheduled_actions'][$hook][] = [
            'timestamp' => $timestamp,
            'interval' => $interval,
        ];
        return count($GLOBALS['edpq_test_scheduled_actions'][$hook]);
    }
}
if (!function_exists('as_schedule_single_action')) {
    function as_schedule_single_action($timestamp, $hook) {
        $GLOBALS['edpq_test_scheduled_actions'][$hook][] = [
            'timestamp' => $timestamp,
            'interval' => null,
        ];
        return count($GLOBALS['edpq_test_scheduled_actions'][$hook]);
    }
}
if (!function_exists('as_unschedule_all_actions')) {
    function as_unschedule_all_actions($hook) {
        $GLOBALS['edpq_test_scheduled_actions'][$hook] = [];
    }
}
if (!function_exists('as_next_scheduled_action')) {
    function as_next_scheduled_action($hook) {
        return $GLOBALS['edpq_test_scheduled_actions'][$hook][0]['timestamp'] ?? false;
    }
}

tests_add_filter('muplugins_loaded', static function () use ($edpq_root) {
    // Load the actual permission, post type, menu, and AJAX code under test.
    // Scheduler and mail execution are outside this suite's scope.
    require_once $edpq_root . '/classes/class-cpt-net-submission.php';
    require_once $edpq_root . '/classes/class-cron-event-timer.php';
    require_once $edpq_root . '/classes/class-photo-submission-queue-manager.php';
    $GLOBALS['edpq_test_cpt'] = new EmDailyPostsQueue\init_plugin\Classes\CPT_NetSubmission();
    $GLOBALS['edpq_test_manager'] = new EmDailyPostsQueue\init_plugin\Classes\EmDailyPostsQueueUIManager();
    add_filter('pre_wp_mail', '__return_true');
    add_filter('pre_http_request', static function () {
        return new WP_Error('edpq_test_no_network', 'External requests are disabled in this suite.');
    });
});

require $edpq_root . '/vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php';

// The queue table belongs exclusively to this run's disposable database.
global $wpdb;
$edpq_table = $wpdb->prefix . 'edpq_net_photos_queue_order';
$wpdb->query("CREATE TABLE {$edpq_table} (id INT AUTO_INCREMENT PRIMARY KEY, list LONGTEXT NOT NULL) " . $wpdb->get_charset_collate());
$wpdb->insert($edpq_table, ['id' => 1, 'list' => '[]']);
