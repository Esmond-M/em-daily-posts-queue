<?php
/**
 * Plugin Name:       Esmond Daily Posts Queue
 * Plugin URI:        https://github.com/Esmond-M/em-daily-posts-queue
 * Author:            Esmond Mccain
 * Author URI:        https://esmondmccain.com/
 * Description:       Display daily posts on the front end and allow assigned users to manage the post queue via a custom admin interface. Includes photo submission, queue reordering, and integration with custom post types.
 * Requires at least: 6.1
 * Requires PHP:      7.4.33
 * Requires Plugins:  action-scheduler
 * Version:           0.1.0
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       em-daily-posts-queue
 * Domain Path:       /languages
 *
 * This plugin provides a daily post queue system for WordPress, allowing users to submit photos, edit post metadata, and manage the display order of posts. Admins can review, reorder, and delete submissions. Frontend shortcodes are available for both submission and display. Database tables are created on activation and all queries use the WordPress table prefix for compatibility.
 */
namespace EmDailyPostsQueue\init_plugin;

use EmDailyPostsQueue\init_plugin\Classes\CPT_NetSubmission;
use EmDailyPostsQueue\init_plugin\Classes\CPT_NetSubmissionMeta;
use EmDailyPostsQueue\init_plugin\Classes\CronEvents;
use EmDailyPostsQueue\init_plugin\Classes\CronEventTimer;
use EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionQueue;
use EmDailyPostsQueue\init_plugin\Classes\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

    global $EmDailyPostsQueueDbVersion;
    $EmDailyPostsQueueDbVersion = '1.0';
    final class EmDailyPostsQueueInit {

        const VERSION = '0.1.0';
        const PHP_MINIMUM_VERSION = '7.0';

        private static $_instance = null;

        public function __construct() {
            add_action( 'init', [ $this, 'i18n' ] );
            add_action( 'plugins_loaded', [ $this, 'init_class' ] );
            register_activation_hook( __FILE__,   [ $this, 'EmDailyPostsQueue_install' ] );
        }

        public function i18n() {
            load_plugin_textdomain( 'em-daily-posts-queue');
        }

        public function EmDailyPostsQueue_install() {
            global $wpdb;
            global $EmDailyPostsQueueDbVersion;
            $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id INT AUTO_INCREMENT PRIMARY KEY,
                list LONGTEXT NOT NULL
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);

            add_option('EmDailyPostsQueueDbVersion', $EmDailyPostsQueueDbVersion);

            // Use JSON for initial value
            $initial_list = json_encode(['message' => 'Congratulations, you just completed the installation!']);

            // Only insert if table is empty
            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name"));
            if ($existing == 0) {
                $inserted = $wpdb->insert(
                    $table_name,
                    array(
                        'list' => $initial_list
                    )
                );
                if ($inserted === false) {
                    error_log('Failed to insert initial row into ' . $table_name . ': ' . $wpdb->last_error);
                }
            }
        }

    public function init_class() {

        require_once __DIR__ . '/classes/class-photo-submission-queue-manager.php';
        require_once __DIR__ . '/classes/class-cron-events.php';
        require_once __DIR__ . '/classes/class-cron-event-timer.php';
        require_once __DIR__ . '/classes/class-cpt-net-submission.php';
        require_once __DIR__ . '/classes/class-cpt-net-submission-meta.php';
        require_once __DIR__ . '/classes/class-shortcodes.php';
    }

    public static function get_instance() {

        if ( null == self::$_instance ) {
            self::$_instance = new Self();
        }

        return self::$_instance;

    }


    }

EmDailyPostsQueueInit::get_instance();