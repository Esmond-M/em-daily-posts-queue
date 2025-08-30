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

use EmDailyPostsQueue\init_plugin\Classes\CPT_NetSubmission;
use EmDailyPostsQueue\init_plugin\Classes\CPT_NetSubmissionMeta;
use EmDailyPostsQueue\init_plugin\Classes\CronEvents;
use EmDailyPostsQueue\init_plugin\Classes\CronEventTimer;
use EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionQueue;

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
            add_shortcode( 'EmDailyPostsQueueForm', [ $this, 'FormShortcodeContent' ] );
            add_shortcode( 'EmDailyPostsQueueDisplayPost', [ $this, 'DisplayPostShortcodeContent' ] );

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
            list longtext NOT NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        add_option('EmDailyPostsQueueDbVersion', $EmDailyPostsQueueDbVersion);
        $welcome_text = 'Congratulations, you just completed the installation!';

        // Check if row exists before inserting
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE id = 1");
        if (!$existing) {
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'id' => 1,
                    'list' => $welcome_text
                )
            );
            if ($inserted === false) {
                error_log('Failed to insert initial row into ' . $table_name . ': ' . $wpdb->last_error);
            }
        }
    }

        public function FormShortcodeContent($atts)
        {
        $a = shortcode_atts([
            'class' => ' '
        ], $atts);
        ob_start();
        ?>
        <div class="<?php echo esc_attr($a['class']); ?>">
            <!-- New Post Form -->
            <form id="new_post" name="new_post" method="post" action="" enctype="multipart/form-data">
                <!-- Topic Headline -->
                <label for="topic_headline_value">Topic Headline</label><br />
                <input type="text" value="" tabindex="1" size="20" name="topic_headline_value" required />
                <br />
                <!-- Topic Caption -->
                <br /><label for="topic_caption_value">Please write a short description of the photo and include the full names of those pictured so they may be credited.</label><br />
                <textarea cols="40" rows="10" name="topic_caption_value" required></textarea>
                <!-- Photo -->
                <label for="net_image">Photo</label><br />
                <input type="file"
                    id="net_image" name="net_image"
                    accept=".png, .jpg, .jpeg" required />
                <!-- Hidden inputs -->
                <input type="hidden" name="action" value="form_post_new_net_photo_submission_ajax" />
                <input type="submit" value="Submit" tabindex="6" id="submit" name="submit" />
                <?php wp_nonce_field('new-post'); ?>
            </form>
            <script>
                jQuery(document).ready(function($) {
                    var uploadField = document.getElementById("net_image");
                    uploadField.onchange = function() {
                        if (this.files[0].size > 8388608) {
                            alert("File is too big!");
                            this.value = "";
                        }
                    };
                });
            </script>
        </div>
        <?php
        $shortcode_html = ob_get_clean();
        return $shortcode_html;
    }

        public function DisplayPostShortcodeContent($atts)
        {
        $a = shortcode_atts([
            'class' => ' '
        ], $atts);
        ob_start();

    global $wpdb;
    $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
    $row = $wpdb->get_row("SELECT list FROM {$table_name} WHERE id = 1", ARRAY_A);

        $placeholder_img = esc_url(plugins_url('assets/imgs/placeholder.png', __FILE__));

        if (isset($row['list']) && !empty($row['list'])) {
            $stored_queue_list_arr = @unserialize(base64_decode($row['list']));
            if (is_array($stored_queue_list_arr) && !empty($stored_queue_list_arr)) {
                // Only show the first post in the queue
                $postID = isset($stored_queue_list_arr[0]['postid']) ? intval($stored_queue_list_arr[0]['postid']) : 0;
                $featuredImage = $postID ? get_the_post_thumbnail_url($postID, 'large') : '';
                $netTopicHeadline = $postID ? get_post_meta($postID, 'topic_headline_value', true) : '';
                $netTopicCaption = $postID ? get_post_meta($postID, 'topic_caption_value', true) : '';

                ?>
                <div class="edpq-around-edpq">
                    <div class="edpq-content-left">
                        <img src="<?php echo esc_url($featuredImage ?: $placeholder_img); ?>" alt="Featured Image" />
                    </div>
                    <div class="edpq-content-right">
                        <p class="heading">Daily Post</p>
                        <?php if ($netTopicHeadline) : ?>
                            <p class="edpq-title"><?php echo esc_html($netTopicHeadline); ?></p>
                        <?php endif; ?>
                        <?php if ($netTopicCaption) : ?>
                            <p class="edpq-net-caption"><?php echo esc_html($netTopicCaption); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            } else {
                // Array of posts is empty
                ?>
                <div class="edpq-around-edpq">
                    <div class="edpq-content-left">
                        <img src="<?php echo $placeholder_img; ?>" alt="Placeholder" />
                    </div>
                    <div class="edpq-content-right">
                        <p class="heading">Around edpq</p>
                        <p class="edpq-net-caption">If you would like to submit an image to be used as the .NET Intranet website banner, please click the button below.</p>
                        <button class="edpq-submit-btn">Submit your photo!</button>
                    </div>
                </div>
                <?php
            }
        } else {
            // No row in database
            ?>
            <div class="edpq-around-edpq">
                <div class="edpq-content-left">
                    <img src="<?php echo $placeholder_img; ?>" alt="Placeholder" />
                </div>
                <div class="edpq-content-right">
                    <p class="heading">Around edpq</p>
                    <p class="edpq-net-caption">If you would like to submit an image to be used as the .NET Intranet website banner, please click the button below.</p>
                    <button class="edpq-submit-btn">Submit your photo!</button>
                </div>
            </div>
            <?php
        }

        $shortcode_html = ob_get_clean();
        return $shortcode_html;
    }


    public function init_class() {

        require_once __DIR__ . '/classes/class-photo-net-submission-queue.php';
        require_once __DIR__ . '/classes/class-cron-events.php';
        require_once __DIR__ . '/classes/class-cron-event-timer.php';
        require_once __DIR__ . '/classes/class-cpt-net-submission.php';
        require_once __DIR__ . '/classes/class-cpt-net-submission-meta.php';

    }

    public static function get_instance() {

        if ( null == self::$_instance ) {
            self::$_instance = new Self();
        }

        return self::$_instance;

    }


    }

EmDailyPostsQueueInit::get_instance();