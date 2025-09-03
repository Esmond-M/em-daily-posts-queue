<?php
/**
 * CPT_NetSubmissionMeta Class
 *
 * Handles custom meta box functionality for the 'net_submission' post type.
 * - Registers and renders custom fields for headline and caption
 * - Saves custom field data securely to post meta
 * - Integrates with WordPress hooks for meta box display and saving
 */
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

class CPT_NetSubmissionMeta {

    /**
     * Constructor: Sets up meta box actions for net_submission post type.
     */
        public function __construct()
        {

            add_action(
                'add_meta_boxes',
                [$this, 'netSubmissionMetabox']
            );
            add_action(
                'save_post',
                [$this, 'netSubmissionMetaboxSave']
            );

        }


    /**
     * Adds the custom meta box to the net_submission post type.
     *
     * @return void
     */
        public function netSubmissionMetabox()
        {
            $screens = ['net_submission']; // post type to display one
            foreach ($screens as $screen) {
                add_meta_box(
                    'netSubmission_metaboxbox_id',    // Unique ID
                    'Topic Custom Fields',  // Box title
                    array($this, 'netSubmissionCustomFields'),
                    $screen,                  // Post type
                    'normal',
                    'high'
                );

            }
        }
    /**
     * Renders the custom fields inside the meta box for net_submission.
     *
     * @param WP_Post $post The current post object.
     * @return void
     */
        public function netSubmissionCustomFields($post)
        {
            $get_topic_headline = get_post_meta($post->ID, 'topic_headline_value', true);
            $get_topic_caption = get_post_meta($post->ID, 'topic_caption_value', true);
            wp_nonce_field(
                'net_submission_post_metabox',
                'net_submission_post_metabox_nonce'
            ); // adding nonce to meta box.
            ?>
            <div class="post_meta_extras">
                <p>
                    <label>Topic Headline
                        <input
                            type="text"
                            name="topic_headline_value"
                            value="<?php echo esc_attr(is_string($get_topic_headline) ? $get_topic_headline : ''); ?>"
                        />
                    </label>
                </p>
                <p>
                    <label>Topic Caption
                        <textarea cols="40" rows="10" name="topic_caption_value" required><?php echo esc_textarea(is_string($get_topic_caption) ? $get_topic_caption : ''); ?></textarea>
                    </label>
                </p>
                <style>
                    .post_meta_extras textarea {
                        display: block;
                        text-align: left;
                    }
                </style>
            </div>
            <?php
        }

    /**
     * Saves the custom meta box values to the database when the post is saved.
     *
     * @param int $post_id The ID of the WordPress post.
     * @return int|string Returns post ID or string on failure.
     */
        public function netSubmissionMetaboxSave($post_id)
        {
        /**
         * Handles saving of custom meta box fields for net_submission post type.
         *
         * @param int $post_id The ID of the WordPress post.
         * @return int Returns post ID.
         */
        // Verify nonce for security
        if (
            !isset($_POST['net_submission_post_metabox_nonce']) ||
            !wp_verify_nonce(
                sanitize_key($_POST['net_submission_post_metabox_nonce']),
                'net_submission_post_metabox'
            )
        ) {
            return $post_id;
        }

        // Check user permissions
        $post_type = $_POST['post_type'] ?? '';
        if (
            ($post_type === 'page' && !current_user_can('edit_page', $post_id)) ||
            ($post_type !== 'page' && !current_user_can('edit_post', $post_id))
        ) {
            return $post_id;
        }

        // Prevent autosave from saving meta
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Save custom fields if set
        if (isset($_POST['topic_headline_value'])) {
            update_post_meta(
                $post_id,
                'topic_headline_value',
                sanitize_text_field($_POST['topic_headline_value'])
            );
        }
        if (isset($_POST['topic_caption_value'])) {
            update_post_meta(
                $post_id,
                'topic_caption_value',
                sanitize_textarea_field($_POST['topic_caption_value'])
            );
        }

        return $post_id;
    }


}



new CPT_NetSubmissionMeta;