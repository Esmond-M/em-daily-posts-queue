<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('cpt_meta_net_submission')) {

    class cpt_meta_net_submission
    {

      /**
      Declaring constructor
      */
        public function __construct()
        {

            add_action(
                'add_meta_boxes',
                [$this, 'icon_class_metabox']
            );
            add_action(
                'save_post',
                [$this, 'icon_class_save_metabox']
            );
    
        }


        /**
         Adding meta box for post

        @return void
         */
        public function icon_class_metabox()
        {
            $screens = ['net_submission']; // post type to display one
            foreach ($screens as $screen) {
                add_meta_box(
                    'em_icon_class_metaboxbox_id',    // Unique ID
                    'Icon class text',  // Box title
                    array($this, 'icon_class_html'),
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
        public function icon_class_html($post)
        {
            $get_services_post_icon_class = get_post_meta($post->ID, 'services_post_icon_class_value', true);
			$get_portfolio_post_url_link = get_post_meta($post->ID, 'portfolio_post_url_link_value', true);
            $get_portfolio_post_popup_target_id = get_post_meta($post->ID, 'portfolio_post_popup_target_id_value', true);
            wp_nonce_field(
                'services_post_metabox',
                'services_post_metabox_nonce'
            ); // adding nonce to meta box.
            ?>
            <div class="post_meta_extras">
                <p>
				<label>Icon Class <input
                               type="text"
                               name="services_post_icon_class_value"
                               value="<?php 
							   if (is_string($get_services_post_icon_class) ) {
                             	echo $get_services_post_icon_class;
                               }?>"
                            />

                    </label>
                </p>
				<p>
				<label>URL Link <input
                               type="text"
                               name="portfolio_post_url_link_value"
                               value="<?php 
							   if (is_string($get_portfolio_post_url_link) ) {
                             	echo $get_portfolio_post_url_link;
                               }?>"
                            />

                    </label>
                </p>
                <p>
				<label>Popup target ID <input
                               type="text"
                               name="portfolio_post_popup_target_id_value"
                               value="<?php 
							   if (is_string($get_portfolio_post_popup_target_id) ) {
                             	echo $get_portfolio_post_popup_target_id;
                               }?>"
                            />

                    </label>
                </p>
            </div>
            <?php
        }
    
        /**
        Save meta box value to database

        @param $post_id of wordpress post

        @return string
         */
        public function icon_class_save_metabox($post_id)
        {
            /*
            * We need to verify this came from the
            *  our screen and with proper authorization,
            * because save_post can be triggered at
            *  other times. Add as many nonces, as you
            * have metaboxes.
               */
            if (!isset($_POST['services_post_metabox_nonce'])
                || !wp_verify_nonce(
                    sanitize_key(
                        $_POST['services_post_metabox_nonce']
                    ),
                    'services_post_metabox'
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

            $services_post_icon_class_value = $_POST['services_post_icon_class_value']; // Input var okay.
            update_post_meta(
                $post_id,
                'services_post_icon_class_value',
                esc_attr($services_post_icon_class_value)
            );
            $portfolio_post_url_link_value = $_POST['portfolio_post_url_link_value']; // Input var okay.
            $portfolio_post_popup_target_id_value = $_POST['portfolio_post_popup_target_id_value']; // Input var okay.
            update_post_meta(
                $post_id,
                'portfolio_post_url_link_value',
                esc_attr($portfolio_post_url_link_value)
            );
            update_post_meta(
                $post_id,
                'portfolio_post_popup_target_id_value',
                esc_attr($portfolio_post_popup_target_id_value)
            );

        }


    }// Closing bracket for classes
}


new cpt_meta_net_submission;