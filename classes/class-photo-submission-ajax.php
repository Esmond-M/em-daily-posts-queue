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
        check_ajax_referer('edpq_admin_queue', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        if (!isset($_POST['form_data'])) {
            wp_send_json_error(['message' => 'No data received.']);
        }

        // Client's snapshot of the queue at page load — used for optimistic concurrency
        $client_snapshot = json_decode(wp_unslash($_POST['client_snapshot'] ?? '[]'), true);
        if (!is_array($client_snapshot)) {
            $client_snapshot = [];
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

        // Check for conflict: compare client's page-load snapshot against current DB value
        $db_queue = $this->utils->get_queue_list();
        $diff = $this->utils->edpqcompareMultiDimensional($db_queue, $client_snapshot);
        if (!empty($diff)) {
            wp_send_json_error(['conflict' => true, 'message' => 'Queue has been updated by another user.']);
            return;
        }

        // Conflict check passed — safe to delete posts removed by the user
        $old_postids = array_map(function($item) { return intval($item['postid']); }, $client_snapshot);
        $removed_postids = array_diff($old_postids, $new_postids);
        foreach ($removed_postids as $removed_id) {
            wp_delete_post($removed_id, true);
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
        check_ajax_referer('edpq_admin_queue', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $confirmation = sanitize_text_field(wp_unslash($_POST['confirmation'] ?? ''));
        if ('FULL WIPE' !== $confirmation) {
            wp_send_json_error(['message' => 'Type FULL WIPE to confirm permanent deletion.']);
        }

        $posts = get_posts([
            'post_type' => 'net_submission',
            'post_status' => array_values(get_post_stati([], 'names')),
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        foreach ($posts as $pid) {
            wp_delete_post($pid, true);
        }
        // Empty the queue table
        global $wpdb;
        $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
        $wpdb->update($table_name, ['list' => '[]'], ['id' => 1], ['%s'], ['%d']);
        wp_send_json_success(['message' => 'Full wipe completed.']);
    }

/**
     * AJAX handler: Processes queue item deletion and updates the queue in the database
     */
    public function net_photo_deletion_info_ajax() {
        check_ajax_referer('edpq_admin_queue', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            return;
        }
        $stored_queue_list_arr = json_decode(wp_unslash($_POST['checkWindowAge'] ?? ''), true);

        // Helper: Render AJAX response and die
        $render_ajax_response = function($msg) {
            $redirect_url = site_url() . '/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions';
            echo '<div class="edpq-response-msg"><p>' . $msg . '<br>Page will reload soon.</p><div class="edpq-ajax-loader"></div></div>';
            echo '<script>setTimeout(function(){ window.location.href=' . wp_json_encode(esc_url_raw($redirect_url)) . '; }, 5000);</script>';
            wp_die();
        };


        // Helper: Get queue list from DB
        $get_queue_list_db = function() {
            return $this->utils->get_queue_list();
        };

        // Helper: Update queue list in DB
        $update_queue_list_db = function($queue) {
            return $this->utils->update_queue_list_in_db($queue);
        };

        // --- Main Logic ---
        if (isset($_POST['remove_postid'])) {
            $idToRemove        = (int) $_POST['remove_postid'];
            $queueNumberToRemove = (int) $_POST['remove_queue'];
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
            check_ajax_referer('new-post', '_wpnonce');

            if ( ! isset($_POST['topic_headline_value']) || ! isset($_POST['topic_caption_value']) ) {
                wp_die('<p class="newpost-fail">Server error please resubmit.</p>');
            }
            $headline = sanitize_text_field(wp_unslash($_POST['topic_headline_value']));
            $caption  = sanitize_textarea_field(wp_unslash($_POST['topic_caption_value']));

            // Add the content of the form to $post as an array
            $new_post = array(
                'post_title'  => $headline . ' ' . date('m-d-y'),
                'post_status' => 'draft',
                'meta_input'  => array(
                    'topic_headline_value' => $headline,
                    'topic_caption_value'  => $caption,
                ),
                'post_type'   => 'net_submission',
            );
            $pid = wp_insert_post($new_post);

            // The nonce was valid and the user has the capabilities, it is safe to continue.

            // These files need to be included as dependencies when on the front end.
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'net_image', $pid );
            if ( is_wp_error( $attachment_id ) ) {
                wp_delete_post( $pid, true );
                wp_die( '<p class="newpost-fail">Image upload failed. Please try again.</p>' );
            }
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
            $subject = 'New Photo Submission for: ' . $headline . ' ' . date('m-d-y');

            // Email body
                $message = 'View it: ' . get_permalink( $pid ) . "<br><br>Edit it: " . admin_url( 'post.php?post=' . $pid . '&action=edit' );

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

                // Only enqueue admin_option_css for the specific admin queue list page
                if (
                    'edit.php' === $pagenow &&
                    isset($_GET['post_type']) && $_GET['post_type'] === 'net_submission' &&
                    isset($_GET['page']) && $_GET['page'] === 'admin-queue-list'
                ) {
                    wp_enqueue_style( 'admin_option_css', $plugin_url . 'admin/assets/css/admin-queue.css', array(), (string) filemtime(dirname(__DIR__) . '/admin/assets/css/admin-queue.css') );
                    if (current_user_can('manage_options')) {
                        wp_enqueue_script('admin-queue-edits-js', $plugin_url . '/admin/assets/js/admin-queue-edits.js', array('jquery'), (string) filemtime(dirname(__DIR__) . '/admin/assets/js/admin-queue-edits.js'), true);
                        wp_localize_script('admin-queue-edits-js', 'edpq_admin_queue', [
                            'nonce'      => wp_create_nonce('edpq_admin_queue'),
                            'showingNow' => __('Showing now', 'em-daily-posts-queue'),
                            'upNext'     => __('Up next', 'em-daily-posts-queue'),
                            'queued'     => __('Queued', 'em-daily-posts-queue'),
                            'clean'      => __('No unsaved changes.', 'em-daily-posts-queue'),
                            'dirty'      => __('You have unsaved queue changes.', 'em-daily-posts-queue'),
                            'saving'     => __('Saving queue changes...', 'em-daily-posts-queue'),
                            'saved'      => __('Queue changes saved.', 'em-daily-posts-queue'),
                            'conflict'   => __('The queue changed in another window. Your changes were not saved; review them before refreshing.', 'em-daily-posts-queue'),
                            'error'      => __('Queue changes could not be saved.', 'em-daily-posts-queue'),
                            'confirmDelete' => __('Remove this item from the queue and permanently delete its submission?', 'em-daily-posts-queue'),
                            'fullWipePrompt' => __('Type FULL WIPE to permanently delete every Net Submission post in every status and clear the queue.', 'em-daily-posts-queue'),
                            'fullWipeCancelled' => __('Full Wipe cancelled.', 'em-daily-posts-queue'),
                        ]);
                    }
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
                        'nonce'   => wp_create_nonce('edpq_admin_queue'),
                        'noposts' => __('No older posts found', 'edpq-white'),
                    ));
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
