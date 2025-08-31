<?php
namespace EmDailyPostsQueue\init_plugin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Handles registration and rendering of frontend shortcodes for the Daily Posts Queue plugin.
 *
 * Shortcodes:
 * - EmDailyPostsQueueForm: Displays the photo submission form.
 * - EmDailyPostsQueueDisplayPost: Displays the current daily post/banner.
 *
 * Usage: Instantiate this class to register shortcodes.
 */
class Shortcodes {

    public function __construct() {
        add_shortcode( 'EmDailyPostsQueueForm', [ $this, 'FormShortcodeContent' ] );
        add_shortcode( 'EmDailyPostsQueueDisplayPost', [ $this, 'DisplayPostShortcodeContent' ] );
    }

    public static function FormShortcodeContent($atts) {
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

    public static function DisplayPostShortcodeContent($atts) {
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
}

new Shortcodes();
