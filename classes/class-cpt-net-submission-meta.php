<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('CPT_NetSubmissionMeta')) {

    class CPT_NetSubmissionMeta
    {

      /**
      Declaring constructor
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
         Adding meta box for post

        @return void
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
        Styles for custom metabox on backend

        @param $post callback

        @return callable
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
				<label>Topic Headline <input
                               type="text"
                               name="topic_headline_value"
                               value="<?php 
							   if (is_string($get_topic_headline) ) {
                             	echo $get_topic_headline;
                               }?>"
                            />

                    </label>
                </p>
				<p>
				<label>Topic Caption
                    <textarea cols="40" rows="10" name="topic_caption_value"  required>
                     <?php 
							   if (is_string($get_topic_caption) ) {
                             	echo $get_topic_caption;
                               }?>   
                   </textarea>
                </label>
                </p>
            <style>
            .post_meta_extras textarea {
                display:block;
                text-align:left;
            }    
                        </style>    
            </div>
            <?php
        }
    
        /**
        Save meta box value to database

        @param $post_id of wordpress post

        @return string
         */
        public function netSubmissionMetaboxSave($post_id)
        {
            /*
            * We need to verify this came from the
            *  our screen and with proper authorization,
            * because save_post can be triggered at
            *  other times. Add as many nonces, as you
            * have metaboxes.
               */
            if (!isset($_POST['net_submission_post_metabox_nonce'])
                || !wp_verify_nonce(
                    sanitize_key(
                        $_POST['net_submission_post_metabox_nonce']
                    ),
                    'net_submission_post_metabox'
                )
            ) { // Input var okay.
                return $post_id;
            }


            // Check the user's permissions.
            if (isset($_POST['post_type'])
                && 'page' === $_POST['post_type']
            ) { // Input var okay.
                if (!current_user_can(
                    'edit_page', $post_id
                )
                ) {
                    return $post_id;
                }
            } else {
                if (!current_user_can('edit_post', $post_id)) {
                    return $post_id;
                }
            }

            /*
               * If this is an autosave, our form has not been submitted,
               * so we don't want to do anything.
               */
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }

            /* Ok to save */

            $topic_headline_value = $_POST['topic_headline_value']; // Input var okay.
            update_post_meta(
                $post_id,
                'topic_headline_value',
                esc_attr($topic_headline_value)
            );
            $topic_caption_value = $_POST['topic_caption_value']; // Input var okay.

            update_post_meta(
                $post_id,
                'topic_caption_value',
                esc_attr($topic_caption_value)
            );

        }


    }// Closing bracket for classes
}


new CPT_NetSubmissionMeta;