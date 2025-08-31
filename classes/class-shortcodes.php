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
        <div class="edpq-form-wrapper <?php echo esc_attr($a['class']); ?>">
            <form id="new_post" name="new_post" method="post" action="" enctype="multipart/form-data" class="edpq-form">
                <h2 class="edpq-form-title">Submit a Photo</h2>
                <div class="edpq-form-group">
                    <label for="topic_headline_value" class="edpq-label">Topic Headline</label>
                    <input type="text" class="edpq-input" id="topic_headline_value" name="topic_headline_value" placeholder="Enter headline" required />
                </div>
                <div class="edpq-form-group">
                    <label for="topic_caption_value" class="edpq-label">Photo Caption</label>
                    <textarea class="edpq-textarea" id="topic_caption_value" name="topic_caption_value" rows="5" placeholder="Write a short description and include full names for credit." required></textarea>
                </div>
                <div class="edpq-form-group">
                    <label for="net_image" class="edpq-label">Photo Upload</label>
                    <input type="file" class="edpq-file" id="net_image" name="net_image" accept=".png, .jpg, .jpeg" required />
                    <small class="edpq-help">Max file size: 8MB. Accepted formats: JPG, JPEG, PNG.</small>
                </div>
                <input type="hidden" name="action" value="form_post_new_net_photo_submission_ajax" />
                <?php wp_nonce_field('new-post'); ?>
                <button type="submit" class="edpq-submit-btn" id="submit" name="submit">Submit Photo</button>
            </form>
            <script>
                jQuery(document).ready(function($) {
                    var uploadField = document.getElementById("net_image");
                    uploadField.onchange = function() {
                        if (this.files[0].size > 8388608) {
                            alert("File is too big! Maximum allowed size is 8MB.");
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

    $placeholder_img = esc_url(plugin_dir_url(__DIR__) . 'assets/imgs/placeholder.png');

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
