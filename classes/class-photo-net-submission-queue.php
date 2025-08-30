<?php
declare(strict_types=1);
/**
 * PhotoNetSubmissionQueue Class
 *
 * Handles photo submission queue management for the plugin, including:
 * - AJAX handlers for queue updates and new submissions
 * - Database logic for queue storage and retrieval
 * - Admin and frontend asset loading
 * - Custom post type hooks and meta box management
 * - Utility functions for queue comparison and manipulation
 */
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('PhotoNetSubmissionQueue')) {

    class PhotoNetSubmissionQueue
    {

              /**
         * Declaring constructor
         */
        public function __construct()
        {

        add_action('wp_ajax_net_photo_deletion_info_ajax', [$this, 'net_photo_deletion_info_ajax' ] );
        add_action('wp_ajax_nopriv_net_photo_deletion_info_ajax', [$this, 'net_photo_deletion_info_ajax' ]);
        add_action('wp_ajax_form_post_new_net_photo_submission_ajax', [$this, 'form_post_new_net_photo_submission_ajax' ] );
        add_action('wp_ajax_nopriv_form_post_new_net_photo_submission_ajax', [$this, 'form_post_new_net_photo_submission_ajax' ]);

        add_action('trashed_post', [$this, 'net_submission_skip_trash' ] );
        add_action( 'pre_post_update', [$this, 'intercept_publishToDraft' ] , 10, 2);
        add_action( 'publish_net_submission', [$this, 'do_updated_to_publish' ] , 10, 3 );

        add_action('admin_menu', [$this, 'edpqPhotoSubmission_register_submenu_page' ] );
        add_action( 'admin_enqueue_scripts', [$this, 'load_admin_net_style' ]  );
        add_action( 'wp_enqueue_scripts', [$this, 'net_style_scripts' ]  );

        add_action( 'admin_menu', [$this, 'edpq_net_submission_remove_meta_boxes' ]  );
        add_action( 'add_meta_boxes', [$this, 'edpq_net_submission_register_meta_boxes' ]  );

        add_filter( 'post_row_actions', [$this, 'my_cpt_row_actions' ] , 10, 2 );

        }


    /**
     * Compare two multidimensional arrays and return differences
     * @param array $array1
     * @param array $array2
     * @param bool $strict
     * @return array
     */
    public function edpqcompareMultiDimensional($array1, $array2, $strict = true){
            if (!is_array($array1)) {
                throw new \InvalidArgumentException('$array1 must be an array!');
            }

            if (!is_array($array2)) {
                return $array1;
            }

            $result = array();

            foreach ($array1 as $key => $value) {
                if (!array_key_exists($key, $array2)) {
                    $result[$key] = $value;
                    continue;
                }

                if (is_array($value) && count($value) > 0) {
                    $recursiveArrayDiff = $this->edpqcompareMultiDimensional($value, $array2[$key], $strict);

                    if (count($recursiveArrayDiff) > 0) {
                        $result[$key] = $recursiveArrayDiff;
                    }

                    continue;
                }

                $value1 = $value;
                $value2 = $array2[$key];

                if ($strict ? is_float($value1) && is_float($value2) : is_float($value1) || is_float($value2)) {
                    $value1 = (string) $value1;
                    $value2 = (string) $value2;
                }

                if ($strict ? $value1 !== $value2 : $value1 != $value2) {
                    $result[$key] = $value;
                }
            }

            return $result;
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

        // Helper: Connect to DB
        $db_connect = function() {
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$conn) {
                die('Connection to database failed with error#: ' . mysqli_connect_error());
            }
            return $conn;
        };

        // Helper: Get queue list from DB
        $get_queue_list_db = function($conn) {
            $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            return isset($row['list']) && !empty($row['list']) ? unserialize(base64_decode($row['list'])) : null;
        };

        // Helper: Update queue list in DB
        $update_queue_list_db = function($conn, $queue) {
            $serialize_queueListArray = base64_encode(serialize($queue));
            $sql = "UPDATE edpq_net_photos_queue_order SET list='" . $serialize_queueListArray . "' WHERE id=1";
            return $conn->query($sql);
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
            $reNumberBeforeSubmit = $renumber_queue($reNumberBeforeSubmit, $queueNumberToRemove);

            $conn = $db_connect();
            $db_queue = $get_queue_list_db($conn);
            if ($db_queue !== null) {
                $isWindowOutdated = $this->edpqcompareMultiDimensional($db_queue, $old_stored_queue_list_arr);
                if (empty($isWindowOutdated)) {
                    $success = $update_queue_list_db($conn, $reNumberBeforeSubmit);
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
            $conn->close();
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
        $updatedQueuelist = array_replace($stored_queue_list_arr, $FixedTempArrayFromPageForm);

        $conn = $db_connect();
        $db_queue = $get_queue_list_db($conn);
        if ($db_queue !== null) {
            $isWindowOutdated = $this->edpqcompareMultiDimensional($db_queue, $stored_queue_list_arr);
            if (empty($isWindowOutdated)) {
                $success = $update_queue_list_db($conn, $updatedQueuelist);
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
        $conn->close();
        wp_die();
    } 

    /**
     * Hook: Force delete net_submission posts instead of sending to trash
     */
    public function net_submission_skip_trash($post_id) {

        if (get_post_type($post_id) == 'net_submission') {
            // Force delete
            wp_delete_post( $post_id, true );
        }

    } 

    /**
     * Hook: Prevent publishing/drafting net_submission posts in unsupported states
     */
    public function intercept_publishToDraft ($post_ID, $data ) {

        if (get_post_type($post_ID) !== 'net_submission') {
            return;
        }
        $post = get_post($post_ID);

        if ( ( $data['post_status'] !== 'publish' ) && ( $data['post_status'] !== 'trash' ) && ( $post->post_type == 'net_submission' ) ) {

            wp_die( 'not allowed');
        }

    } 


    /**
     * Hook: Add newly published net_submission post to the queue list in the database
     */
    public function do_updated_to_publish( $post_id, $post , $old_status  ) {

        if ($old_status == 'publish') {
            return;
        }
        $queue_list = $this->get_queue_list_from_db();
        if (is_array($queue_list) && !empty($queue_list)) {
            $getHighestNumberInQueueDatabase = array_key_last($queue_list);
            $highestNumber = $queue_list[$getHighestNumberInQueueDatabase]['queueNumber'];
            $addthis = intval($highestNumber) + 1;
            $queue_list[] = array("postid" => $post_id, "queueNumber" => $addthis);
        } else {
            $queue_list = [array("postid" => $post_id, "queueNumber" => 1)];
        }
        $result = $this->update_queue_list_in_db($queue_list);
        if ($result !== true) {
            echo "SQL Error: " . $result;
        } else {
            echo "New record created successfully";
        }

    } 


    /**
     * Admin: Register submenu pages for queue list and admin queue list
     */
        public function edpqPhotoSubmission_register_submenu_page() {

                //Add Custom Social Sharing Sub Menu
                add_submenu_page(
                'edit.php?post_type=net_submission', 
                'Photo Submission Queue List',
                'Queue List',
                "upload_files" ,
                'edit_net_submissions' ,
                [$this, 'edpqqueue_list_page'] 
                );
                //Add Custom Social Sharing Sub Menu
                add_submenu_page(
                'edit.php?post_type=net_submission',
                'Admin Photo Queue List',
                'Admin Photo Queue List',
                "manage_options",
                'admin-queue-list',
                [$this, 'edpqadmin_queue_list_page']);

            } 

    /**
     * Retrieve the current photo submission queue list from the database
     * @return array
     */
        public function get_queue_list() {
                $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                if (!$conn) {
                    return ['error' => mysqli_connect_error()];
                }
                $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                mysqli_close($conn);

                if (isset($row['list']) && !empty($row['list'])) {
                    $queue = unserialize(base64_decode($row['list']));
                    return is_array($queue) ? $queue : [];
                }
                return [];
            }

    /**
     * Render the auto submission queue list page (with reorder/delete UI)
     */
        public function edpqqueue_list_page(){

                global $pagenow;
                $plugin_url = plugin_dir_url(dirname(__FILE__));
                $rand = rand(1, 99999999999);
                $queue_list = $this->get_queue_list();
                require_once __DIR__  . '/../templates/options-page-auto-submission.php';

        }

    /**
     * Render the admin queue list page (read-only, no editing)
     */
        public function edpqadmin_queue_list_page(){

                $queue_list = $this->get_queue_list();
                require_once __DIR__ . '/../templates/options-page-admin-queue-list.php';

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
      
        }


    /**
     * Remove default meta boxes from net_submission post edit screens
     */
        public function edpq_net_submission_remove_meta_boxes() {
                remove_meta_box( 'submitdiv', 'net_submission', 'normal' );
        }

    /**
     * Register custom meta boxes for net_submission post type
     */
        public function edpq_net_submission_register_meta_boxes() {
                add_meta_box( 
                    "_submitdiv", 
                    __( "Publish" ), 
                    [$this, "edpq_net_submission_meta_boxes_callback"], 
                    'net_submission', 
                    'side', 
                    'high',
                    [ 'show_draft_button' => false ] 
                );
        }

    /**
     * Render custom meta box UI for net_submission post editing
     */
        public function edpq_net_submission_meta_boxes_callback( $post){
                global $action;

                $post_id          = (int) $post->ID;
                $post_type        = $post->post_type;
                $post_type_object = get_post_type_object( $post_type );
                $can_publish      = current_user_can( $post_type_object->cap->publish_posts );
                ?>
            <div class="submitbox" id="submitpost">

            <div id="minor-publishing">

                <?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key. ?>
                <div style="display:none;">
                    <?php submit_button( __( 'Save' ), '', 'save' ); ?>
                </div>
                <div class="clear"></div>
            </div>

            <div id="major-publishing-actions">
                <?php
                /**
                 * Fires at the beginning of the publishing actions section of the Publish meta box.
                 *
                 * @since 2.7.0
                 * @since 4.9.0 Added the `$post` parameter.
                 *
                 * @param WP_Post|null $post WP_Post object for the current post on Edit Post screen,
                 *                           null on Edit Link screen.
                 */
                do_action( 'post_submitbox_start', $post );
                ?>
                <div id="delete-action">
                    <?php
                    if ( current_user_can( 'delete_post', $post_id ) ) {
                        if ( ! EMPTY_TRASH_DAYS ) {
                            $delete_text = __( 'Delete permanently' );
                        } else {
                            $delete_text = __( 'Move to Trash' );
                        }

                        if ( 'publish' !== $post->post_status ) {
                        ?>
                        <a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post_id ); ?>"><?php echo $delete_text; ?></a>
                      <?php
                        }
                        ?>

                        <?php
                    }
                    ?>
                </div>

                <div id="publishing-action">
                    <span class="spinner"></span>
                    <?php
                    if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ), true ) || 0 === $post_id ) {
                        if ( $can_publish ) :
                            if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) :
                                ?>
                                <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr_x( 'Schedule', 'post action/button label' ); ?>" />
                                <?php submit_button( _x( 'Schedule', 'post action/button label' ), 'primary large', 'publish', false ); ?>
                                <?php
                            else :
                                ?>
                                <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ); ?>" />
                                <?php submit_button( __( 'Publish' ), 'primary large', 'publish', false ); ?>
                                <?php
                            endif;
                        else :
                            ?>
                            <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ); ?>" />
                            <?php submit_button( __( 'Submit for Review' ), 'primary large', 'publish', false ); ?>
                            <?php
                        endif;
                    } else {
                        ?>
                        <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>" />
                        <?php submit_button( __( 'Update' ), 'primary large', 'save', false, array( 'id' => 'publish' ) ); ?>
                        <?php
                    }
                    ?>
                </div>
                <div class="clear"></div>
            </div>

            </div>
                <?php
        }

    /**
     * Customize row actions for net_submission posts (removes quick edit/trash)
     */
        public function my_cpt_row_actions( $actions, $post ) {
                if ( 'net_submission' === $post->post_type ) {
                    // Removes the "Quick Edit" action.
                    // Removes the "Trash" action.
                    unset( $actions['inline hide-if-no-js'] );
                    unset( $actions['trash'] );
                }
                return $actions;
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
            echo '<p class="newpost-success">Thank you for your submission!</p>';
            wp_die();
        }

    /**
     * Conditionally enqueue styles/scripts for frontend shortcodes
     */
        public function net_style_scripts(){
                global $post;
                $rand = rand(1, 99999999999);
                $plugin_url = plugin_dir_url(dirname(__FILE__));
                if( has_shortcode( $post->post_content, 'EmDailyPostsQueueForm' ) ){
                wp_enqueue_script( 'edpq-submit-photo-submission-script', $plugin_url . 'assets/js/edpq-submit-photo-submission.js', array('jquery'), $rand, true);
                    wp_localize_script('edpq-submit-photo-submission-script', 'ajax_form_post_new_net_photo_submission', array(
                    'ajaxurl_form_post_new_net_photo_submission' => admin_url('admin-ajax.php') ,
                    'noposts' => __('No older posts found', 'edpq-white') ,
                    )); 

                wp_enqueue_style( 'edpq-form-styles', $plugin_url . '/assets/css/form.css' , array(),  $rand );  	  
                }
                if( has_shortcode( $post->post_content, 'EmDailyPostsQueueDisplayPost' ) || is_singular('net_submission')){
                wp_enqueue_style( 'edpq-display-styles', $plugin_url . '/assets/css/display.css' , array(),  $rand ); 
                }

            }

        /**
         * Get the queue list from the database
         * @return array
         */
        private function get_queue_list_from_db() {
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$conn) {
                return ['error' => mysqli_connect_error()];
            }
            $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            mysqli_close($conn);
            if (isset($row['list']) && !empty($row['list'])) {
                $queue = unserialize(base64_decode($row['list']));
                return is_array($queue) ? $queue : [];
            }
            return [];
        }

        /**
         * Update the queue list in the database
         * @param array $queue_list
         * @return bool|string True on success, error message on failure
         */
        private function update_queue_list_in_db($queue_list) {
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$conn) {
                return mysqli_connect_error();
            }
            $serialized_array = base64_encode(serialize($queue_list));
            $sql = "UPDATE edpq_net_photos_queue_order SET list='" . $serialized_array . "' WHERE id=1";
            $result = $conn->query($sql);
            $conn->close();
            return $result === TRUE ? true : $conn->error;
        }


    } 
    

}

new PhotoNetSubmissionQueue();