<?php
/**
 * PhotoNetSubmissionAjax
 *
 * Handles AJAX requests for the Esmond Daily Posts Queue plugin, including:
 * - Queue editing and reordering
 * - Full queue wipe
 * - Queue item deletion
 * - New photo submission form processing
 * - Conditional loading of admin and frontend styles/scripts
 *
 * Relies on PhotoNetSubmissionUtils for queue and helper functions.
 */
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;
require_once __DIR__ . '/class-photo-submission-utils.php';

class PhotoNetSubmissionAjax {
    /**
     * @var PhotoNetSubmissionUtils
     */
    private $utils;

    public function __construct($utils) {
        $this->utils = $utils;
    }

    public function handle_admin_queue_edit_ajax() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        if (!isset($_POST['form_data'])) {
            wp_send_json_error(['message' => 'No data received.']);
        }
        parse_str($_POST['form_data'], $form);
        $queue = [];
        $new_postids = [];
        foreach ($form as $key => $value) {
            if (strpos($key, 'queue-postID-') === 0) {
                $num = str_replace('queue-postID-', '', $key);
                $queue[$num]['postid'] = intval($value);
                $new_postids[] = intval($value);
            }
            if (strpos($key, 'queue-value-') === 0) {
                $num = str_replace('queue-value-', '', $key);
                $queue[$num]['queueNumber'] = intval($value);
            }
        }
        // Reindex queueNumber sequentially (no gaps)
        $queue = array_values($queue);
        foreach ($queue as $i => &$item) {
            $item['queueNumber'] = $i + 1;
        }
        unset($item);

        // Get current queue from DB to find removed post IDs
        $old_queue = $this->utils->get_queue_list();
        $old_postids = array_map(function($item) { return intval($item['postid']); }, $old_queue);
        $removed_postids = array_diff($old_postids, $new_postids);
        foreach ($removed_postids as $removed_id) {
            wp_delete_post($removed_id, true);
        }

        // Check for queue conflict (optimistic concurrency)
        $db_queue = $this->utils->get_queue_list();
        if ($db_queue !== $old_queue) {
            wp_send_json_error(['conflict' => true, 'message' => 'Queue has been updated by another user.']);
            return;
        }
        $result = $this->utils->update_queue_list_in_db($queue);
        if ($result === true || $result === 1) {
            wp_send_json_success(['message' => 'Queue updated.']);
        } else {
            wp_send_json_error(['message' => 'Error updating queue.']);
        }
    }

    /**
     * AJAX handler: Full wipe of queue and all net_submission posts
     */
    public function handle_admin_queue_full_wipe_ajax() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        // Delete all net_submission posts
        $posts = get_posts([
            'post_type' => 'net_submission',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        foreach ($posts as $pid) {
            wp_delete_post($pid, true);
        }
        // Empty the queue table
        global $wpdb;
        $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
        $wpdb->query("UPDATE $table_name SET list='' WHERE id=1");
        wp_send_json_success(['message' => 'Full wipe completed.']);
    }

/**
     * AJAX handler: Processes queue item deletion and updates the queue in the database
     */
    public function net_photo_deletion_info_ajax() {
        $stored_queue_list_arr = json_decode(stripslashes($_POST['checkWindowAge']), true);

        // Helper: Render AJAX response and die
        $render_ajax_response = function($msg) {
            echo '<div class="edpq-response-msg"><p>' . $msg . '<br>Page will reload soon.</p><div class="edpq-ajax-loader"></div></div>';
            header('refresh:5; url=' . site_url() . '/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions');
            wp_die();
        };


        // Helper: Get queue list from DB using $wpdb
        $get_queue_list_db = function() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
            $row = $wpdb->get_row("SELECT list FROM $table_name WHERE id='1';", ARRAY_A);
            return isset($row['list']) && !empty($row['list']) ? unserialize(base64_decode($row['list'])) : null;
        };

        // Helper: Update queue list in DB using $wpdb
        $update_queue_list_db = function($queue) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
            $serialize_queueListArray = base64_encode(serialize($queue));
            return $wpdb->query($wpdb->prepare("UPDATE $table_name SET list=%s WHERE id=1", $serialize_queueListArray));
        };

        // Helper: Renumber queue
        $renumber_queue = function($queue, $removedQueueNumber) {
            foreach ($queue as &$item) {
                if (intval($item['queueNumber']) > $removedQueueNumber) {
                    $item['queueNumber'] = $item['queueNumber'] - 1;
                }
            }
            return $queue;
        };

        // --- Main Logic ---
        if (isset($_POST['remove_postid'])) {
            $idToRemove = $_POST['remove_postid'];
            $queueNumberToRemove = $_POST['remove_queue'];
            $old_stored_queue_list_arr = $stored_queue_list_arr;

            // Remove post from queue
            foreach ($stored_queue_list_arr as $i => $item) {
                if ($item['postid'] == $idToRemove) {
                    unset($stored_queue_list_arr[$i]);
                }
            }
            $reNumberBeforeSubmit = array_values($stored_queue_list_arr);
            // Reindex queueNumber sequentially (no gaps)
            foreach ($reNumberBeforeSubmit as $i => &$item) {
                $item['queueNumber'] = $i + 1;
            }
            unset($item);

            $db_queue = $get_queue_list_db();
            if ($db_queue !== null) {
                $isWindowOutdated = $this->utils->edpqcompareMultiDimensional($db_queue, $old_stored_queue_list_arr);
                if (empty($isWindowOutdated)) {
                    $success = $update_queue_list_db($reNumberBeforeSubmit);
                    if ($success) {
                        wp_delete_post($idToRemove, true);
                        $render_ajax_response('Queue List updated. Item has been removed.');
                    } else {
                        $render_ajax_response('SQL Error: Could not update queue list.');
                    }
                } else {
                    $render_ajax_response('This window is out of date. Please refresh and make sure you only have one tab of this page open or that no one else is editing the page at the same time as you.');
                }
            } else {
                $render_ajax_response('Database row does not exist.');
            }

            return;
        }

        // Handle reorder (no removal)
        $tempArrayFromPageForm = [];
        $postID_count = -1;
        $queueLoop_count = 0;
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'queue-postID-') === 0) {
                $tempArrayFromPageForm[$postID_count] = array('postid' => intval($v));
            }
            $postID_count++;
        }
        foreach ($_POST as $inputname => $inputvalue) {
            if (strpos($inputname, 'queue-value-') === 0) {
                $tempArrayFromPageForm[$queueLoop_count]['queueNumber'] = intval($inputvalue);
            }
            $queueLoop_count++;
        }
        $FixedTempArrayFromPageForm = array_values($tempArrayFromPageForm);
        // Reindex queueNumber sequentially (no gaps)
        foreach ($FixedTempArrayFromPageForm as $i => &$item) {
            $item['queueNumber'] = $i + 1;
        }
        unset($item);
        $updatedQueuelist = array_replace($stored_queue_list_arr, $FixedTempArrayFromPageForm);
        $db_queue = $get_queue_list_db();
        if ($db_queue !== null) {
            $isWindowOutdated = $this->utils->edpqcompareMultiDimensional($db_queue, $stored_queue_list_arr);
            if (empty($isWindowOutdated)) {
                $success = $update_queue_list_db($updatedQueuelist);
                if ($success) {
                    $render_ajax_response('Queue List updated.');
                } else {
                    $render_ajax_response('SQL Error: Could not update queue list.');
                }
            } else {
                $render_ajax_response('This window is out of date. Please refresh and make sure you only have one tab of this page open or that no one else is editing the page at the same time as you.');
            }
        } else {
            $render_ajax_response('Database row does not exist.');
        }

        wp_die();
    }

    /**
     * AJAX handler: Processes new photo submission form and creates new net_submission post
     */
    public function form_post_new_net_photo_submission_ajax()
    {

            // Do some minor form validation to make sure there is content
            if (  isset($_POST['topic_headline_value']) && isset($_POST['topic_caption_value'])  ) {

            }
            else{
                    wp_die('<p class="newpost-fail">Server error please resubmit.</p>');
            }

            // Add the content of the form to $post as an array
            $new_post = array(
                'post_title'    => $_POST['topic_headline_value'] . ' ' . date("m-d-y") ,
                'post_status'   => 'draft',           // Choose: publish, preview, future, draft, etc.
                'meta_input'   => array(
                'topic_headline_value' => '' . $_POST['topic_headline_value'] . '',
                'topic_caption_value' => '' . $_POST['topic_caption_value'] . '',
                ),
                'post_type' => 'net_submission'  //'post',page' or use a custom post type if you want to
                );
                //save the new post
            $pid = wp_insert_post($new_post);

            // The nonce was valid and the user has the capabilities, it is safe to continue.

            // These files need to be included as dependencies when on the front end.
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'net_image', $pid );
            //Set Image as thumbnail
            set_post_thumbnail($pid, $attachment_id);

                // Get admin email dynamically
                $admin_email = get_option('admin_email');
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: "Admin" <' . $admin_email . '>',
                    'Reply-To: "Admin" <' . $admin_email . '>'
                );
                // Recipient, in this case the administrator email
                $emailto = $admin_email;

            // Email subject, "New {post_type_label}"
            $subject = 'New Photo Submission for: ' . $_POST['topic_headline_value'] . ' ' . date("m-d-y");

            // Dynamically get a user who can edit posts (administrator)
            $admin_user = get_users([
                'role'    => 'administrator',
                'number'  => 1,
                'fields'  => 'ID'
            ]);
            if (!empty($admin_user)) {
                wp_set_current_user($admin_user[0]);
            }
            // Email body
                $message = 'View it: ' . get_permalink( $pid ) . "<br><br>Edit it: " . get_edit_post_link( $pid, 'display' );
                wp_set_current_user(0);  // turn off get user after get link function

            wp_mail( $emailto, $subject, $message, $headers );
                        echo '<div class="edpq-success-message">
                                        <svg class="edpq-success-icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#28a745"/><path d="M10 17l4 4 8-8" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <h3>Thank you for your submission!</h3>
                                        <p>Your photo and caption have been received.<br>We appreciate your contribution. An admin will review your submission soon.</p>
                                        <a href="' . esc_url(home_url('/')) . '" class="edpq-success-home-btn">Return to Homepage</a>
                                    </div>';
            wp_die();
    }

    /**
     * Conditionally enqueue styles/scripts for admin pages based on context
     */
    public function load_admin_net_style(){
                global $pagenow;
                $rand = rand(1, 99999999999);
                $plugin_url = plugin_dir_url(dirname(__FILE__));

                if ( 'edit.php' === $pagenow  && 'net_submission' ===  $_GET['post_type'] ) {
                wp_enqueue_style( 'edit_screen_css',  $plugin_url  . '/admin/assets/css/net-submission-edit.css' , array(),  $rand );
                }
                // Only enqueue admin_option_css for the specific admin queue list page
                if (
                    'edit.php' === $pagenow &&
                    isset($_GET['post_type']) && $_GET['post_type'] === 'net_submission' &&
                    isset($_GET['page']) && $_GET['page'] === 'admin-queue-list'
                ) {
                    wp_enqueue_style( 'admin_option_css',  $plugin_url  . '/admin/assets/css/admin-queue.css' , array(),  $rand );
                }
                // Only enqueue styles and scripts for the edit_net_submissions page
                if (
                    'edit.php' === $pagenow &&
                    isset($_GET['post_type']) && $_GET['post_type'] === 'net_submission' &&
                    isset($_GET['page']) && $_GET['page'] === 'edit_net_submissions'
                ) {
                    wp_enqueue_style( 'edpq-photo-submission-styles', $plugin_url  . '/admin/assets/css/edpq-photo-submission.css' , array(),  $rand );
                    wp_enqueue_script( 'edpq-photo-submission-scripts', $plugin_url  . '/admin/assets/js/edpq-photo-submission.js', array('jquery'), $rand, true);
                    wp_localize_script('edpq-photo-submission-scripts', 'ajax_net_photo_deletion_info', array(
                        'ajaxurl_net_photo_deletion_info' => admin_url('admin-ajax.php'),
                        'noposts' => __('No older posts found', 'edpq-white'),
                    ));
                }
                // Enqueue admin-queue-edits.js for the Edit Photo Queue page
                if (
                    'edit.php' === $pagenow &&
                    isset($_GET['post_type']) && $_GET['post_type'] === 'net_submission' &&
                    isset($_GET['page']) && $_GET['page'] === 'admin-queue-edit'
                ) {
                    wp_enqueue_script('admin-queue-edits-js', $plugin_url . '/admin/assets/js/admin-queue-edits.js', array('jquery'), $rand, true);
                }

    }

    /**
     * Conditionally enqueue styles/scripts for frontend shortcodes
     */
    public function net_style_scripts(){
                global $post;
                $rand = rand(1, 99999999999);
                $plugin_url = plugin_dir_url(dirname(__FILE__));

                if( has_shortcode( $post->post_content, 'EmDailyPostsQueueDisplayPost' ) || is_singular('net_submission') || has_shortcode( $post->post_content, 'EmDailyPostsQueueForm' )){
                wp_enqueue_style( 'edpq-display-styles', $plugin_url . '/assets/css/main.css' , array(),  $rand );

                wp_enqueue_script( 'edpq-submit-photo-submission-script', $plugin_url . 'assets/js/edpq-submit-photo-submission.js', array('jquery'), $rand, true);
                    wp_localize_script('edpq-submit-photo-submission-script', 'ajax_form_post_new_net_photo_submission', array(
                    'ajaxurl_form_post_new_net_photo_submission' => admin_url('admin-ajax.php') ,
                    'noposts' => __('No older posts found', 'edpq-white') ,
                    ));
                }

    }

}
