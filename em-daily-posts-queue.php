<?php
/**
 * Plugin Name: Esmond Daily Posts Queue
 * Plugin URI: https://github.com/Esmond-M
 * Author: Esmond Mccain
 * Author URI: https://esmondmccain.com/
 * Description: Show daily posts on front and allow assigned user to edit queue. 
 * Version: 0.1.0
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: em-daily-posts-queue
*/
namespace EmDailyPostsQueue\init_plugin;

use EmDailyPostsQueue\init_plugin\Classes\cpt_net_submission_Class;
use EmDailyPostsQueue\init_plugin\Classes\cpt_meta_net_submission;
use EmDailyPostsQueue\init_plugin\Classes\initCronEvents;
use EmDailyPostsQueue\init_plugin\Classes\initEventTimers;
use EmDailyPostsQueue\init_plugin\Classes\automatePhotoNetSubmissions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** Define plugin path constant */
if (!defined('PLUGIN_PATH')) {
    define('PLUGIN_PATH', plugin_dir_url(__FILE__));
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
        //$wpdb->prefix .
        $table_name =  'awc_net_photos_queue_order';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNIQUE AUTO_INCREMENT,
            list longtext  NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        add_option( 'EmDailyPostsQueueDbVersion', $EmDailyPostsQueueDbVersion );
        $welcome_text = 'Congratulations, you just completed the installation!';
        $wpdb->insert( 
            $table_name, 
            array( 
                'id' => 1,
                'list' => $welcome_text 
            ) 
        );
    }


    public function init_class() {

        require_once __DIR__ . '/classes/awc-class-auto-photo-net-submissions.php';
        require_once __DIR__ . '/classes/awc-class-cron-events.php';
        require_once __DIR__ . '/classes/awc-class-cron-event-timers.php';
        require_once __DIR__ . '/classes/cpt-net-submissions.php';
        require_once __DIR__ . '/classes/cpt-meta-net-submissions.php';

    }

    public static function get_instance() {

        if ( null == self::$_instance ) {
            self::$_instance = new Self();
        }

        return self::$_instance;

    }


}

EmDailyPostsQueueInit::get_instance();