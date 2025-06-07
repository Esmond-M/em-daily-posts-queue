<?php
/**
 * Plugin Name: Esmond Daily Posts Queue
 * Plugin URI: https://github.com/Esmond-M
 * Author: Esmond Mccain
 * Author URI: https://esmondmccain.com/
 * Description: Show daily posts on front and allow assigned user to edit queue.
 * Requires at least: 6.1
 * Requires PHP:      7.4.33
 * Requires Plugins: action-scheduler
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
        add_shortcode( 'EmDailyPostsQueueShortcode', [ $this, 'shortcodeContent' ] );
        

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

    public function shortcodeContent($atts)
    {
        $a = shortcode_atts(
            [
                'class' => ' '
            ],
            $atts
        );
        ob_start();
        ?>
        <div class="<?php echo  $a['class']; ?>">
            <!-- New Post Form -->
            <form id="new_post" name="new_post" method="post" action="" enctype="multipart/form-data">
            <?php 
            $current_user = wp_get_current_user();
            date_default_timezone_set("America/Chicago");
            ?>

            <!-- Employee Name -->
            <label for="net_employee_name">Employee Name</label><br />
            <input type="text" value="" tabindex="1" size="20" name="net_employee_name" required/>
            <br />
            <!-- --------- -->

            <!-- Title -->
            <label for="net_title">Title</label><br />
            <input type="text" value="" tabindex="1" size="20" name="net_title" required/>
            <br />
            <!-- --------- -->

            <!-- Beat Team -->
            <label for="net_beat_team">Beat Team</label><br />
            <select name="net_beat_team">
            <option value="A3IM">A3IM</option>
            <option value="ACE">ACE</option>
            <option value="AWC">AWC</option>
            <option value="Beat Emerson">Beat Emerson</option>
            <option value="Beat Rockwell">Beat Rockwell</option>
            <option value="NSCC">NSCC</option>
            </select>
            <!-- --------- -->

            <!-- >Region -->
            <br /><label for="net_region">Region</label><br />
            <input type="text" value="" tabindex="1" size="20" name="net_region" required/>
            <br />
            <!-- --------- -->

            <!-- Photo -->
            <label for="net_image">Photo</label><br />
            <input type="file"
                id="net_image" name="net_image"
                accept=".png, .jpg, .jpeg" required>
            <!-- --------- -->

            <!-- >Caption-->
            <br /><label for="net_caption">Please write a short description of the photo and include the full names of those pictured so they may be credited.</label><br />
            <textarea cols="40" rows="10" name="net_caption" required>
            </textarea>
            <!-- --------- -->

            <!-- Hidden inputs -->
            <input type="hidden" name="action" value="form_post_new_net_photo_submission_ajax" />
            <!-- --------- -->
            <input type="submit" value="Submit" tabindex="6" id="submit" name="submit" />
            <?php wp_nonce_field( 'new-post' ); ?>
            </form>
            <script>

            jQuery(document).ready(function($) {

            var uploadField = document.getElementById("net_image");

            uploadField.onchange = function() {
                if(this.files[0].size > 8388608){
                alert("File is too big!");
                this.value = "";
                };
            };

            });

            </script>
        </div>
        <?php
        $shortcode_html = ob_get_clean();
        return $shortcode_html;
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