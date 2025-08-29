<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('automatePhotoNetSubmissions')) {

    class automatePhotoNetSubmissions
    {

        /**
        Declaring constructor
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
         * Check if multidimensional array is the same
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
        } // end function

        /**
         * Queue List photo deletion ajax submission
         */
        public function net_photo_deletion_info_ajax() {
            
            $stored_queue_list_arr =  json_decode(stripslashes($_POST['checkWindowAge']),true);
            if(isset($_POST['remove_postid'])){
            $idToRemove =  $_POST['remove_postid'];
            $queueNumberToRemove =  $_POST['remove_queue'];
            $old_stored_queue_list_arr = $stored_queue_list_arr; // using this to verify before updating database. I am unsetting the -idToRemove- in array in the code below.
            for($i = 0; $i < count($stored_queue_list_arr); $i++) {
                if($stored_queue_list_arr[$i]['postid'] == $idToRemove){
                    unset($stored_queue_list_arr[$i]);
                } 		
            }

            $reNumberBeforeSubmit = array_values($stored_queue_list_arr); // re-number an array

            for($i = 0; $i < count($reNumberBeforeSubmit); $i++) {
            if( intval($reNumberBeforeSubmit[$i]['queueNumber']) > $queueNumberToRemove  ){
                $reNumberBeforeSubmit[$i]['queueNumber'] = $reNumberBeforeSubmit[$i]['queueNumber'] - 1;
            }

            }

             $SubmissionConn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD,DB_NAME);

             if (!$SubmissionConn)
             { 
             die("Connection to database failed with error#: " . mysqli_connect_error()); 
             }   

             $SubmissionConnsql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
              //----- check queue list again to see if multiple tabs open or someoneelse made a request.

             $Second_result = mysqli_query($SubmissionConn, $SubmissionConnsql);
             $Second_row = mysqli_fetch_assoc($Second_result);

             if( isset($Second_row['list']) && !empty($Second_row['list']) ){
                $Second_stored_queue_list_arr = unserialize(base64_decode($Second_row['list']));
                $isWindowOutdated = $this->edpqcompareMultiDimensional($Second_stored_queue_list_arr, $old_stored_queue_list_arr);
               /* 
              ----- This will check if array is the same. if yes then it will be empty.----
              I am doing this incase another user is updating the same page on another device or if the current user is updating the page in multiple tabs.
                */

                if( empty($isWindowOutdated) ){
                    $serialize_queueListArray = base64_encode(serialize($reNumberBeforeSubmit)); 
                    $SubmissionConnsql = "UPDATE edpq_net_photos_queue_order SET list='" . $serialize_queueListArray . "' WHERE id=1";
                    if ($SubmissionConn->query($SubmissionConnsql) === TRUE) {
                        wp_delete_post( $idToRemove, true); // Set to False if you want to send them to Trash.
                    ?>
                    <div class="edpq-response-msg"><p>Queue List updated. Item has been removed.<br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );                    
                    } 
                    if ($SubmissionConn->query($SubmissionConnsql) !== TRUE) {
                    ?>
                    <div class="edpq-response-msg"><p><?php echo "SQL Error: " . $SubmissionConnsql . "<br>" . $SubmissionConn->error;?><br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );					
                    }
                }

               else{ // If this form window is outdated do not let them submit to datbase new list.
                    ?>
                    <div class="edpq-response-msg"><p>This window is out of date. Please refresh and make sure you only have one tab of this page open or that no one else is editing the page at the same time as you.<br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php 
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );
                 }	


             }


             else{ //  if row is not there do not upate
                    ?>
                    <div class="edpq-response-msg"><p>Database row does not exist.<br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php		
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );
             }


                $SubmissionConn->close();

             }


         else{
                $tempArrayFromPageForm = [];
            $postID_count = -1;
            $queueLoop_count = 0;

            foreach($_POST as $k => $v) {
                if(strpos($k, 'queue-postID-') === 0) {
                $tempArrayFromPageForm[$postID_count] = array("postid" => intval($v));    
                }
            $postID_count++;
            }

            foreach($_POST as $inputname => $inputvalue) {
                if(strpos($inputname, 'queue-value-') === 0) {
                 $tempArrayFromPageForm[$queueLoop_count]['queueNumber'] = intval($inputvalue) ;  
                }
            $queueLoop_count++;
            }
            $FixedTempArrayFromPageForm = array_values($tempArrayFromPageForm); // re-number an array because for some reason it skips keys

             $updatedQueuelist = array_replace($stored_queue_list_arr, $FixedTempArrayFromPageForm);

             $SubmissionConn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD,DB_NAME);

             if (!$SubmissionConn)
             { 
             die("Connection to database failed with error#: " . mysqli_connect_error()); 
             }   

             $SubmissionConnsql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
              //----- check queue list again to see if multiple tabs open or someoneelse made a request.

             $Second_result = mysqli_query($SubmissionConn, $SubmissionConnsql);
             $Second_row = mysqli_fetch_assoc($Second_result);

             if( isset($Second_row['list']) && !empty($Second_row['list']) ){
                $Second_stored_queue_list_arr = unserialize(base64_decode($Second_row['list']));
               /* 
              ----- This will check if array is the same. if yes then it will be empty.----
              I am doing this incase another user is updating the same page on another device or if the current user is updating the page in multiple tabs.
                */

                $isWindowOutdated = $this->edpqcompareMultiDimensional($Second_stored_queue_list_arr, $stored_queue_list_arr);

                if (empty($isWindowOutdated)){
                    // prepare to store queuelist in database
                    $serialize_queueListArray = base64_encode(serialize($updatedQueuelist)); 
                    $SubmissionConnsql = "UPDATE edpq_net_photos_queue_order SET list='" . $serialize_queueListArray . "' WHERE id=1";
                    if ($SubmissionConn->query($SubmissionConnsql) === TRUE) {
                    ?>
                    <div class="edpq-response-msg"><p>Queue List updated.<br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );
                    } 
                    if ($SubmissionConn->query($SubmissionConnsql) !== TRUE) {
                    ?>
                    <div class="edpq-response-msg"><p><?php echo "SQL Error: " . $SubmissionConnsql . "<br>" . $SubmissionConn->error;?><br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );
                    }

                }

                else{ // If this form window is outdated do not let them submit to datbase new list.
                    ?>
                    <div class="edpq-response-msg"><p>This window is out of date. Please refresh and make sure you only have one tab of this page open or that no one else is editing the page at the same time as you.<br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php 
                    header( "refresh:5; url=". site_url() ."/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions" );
                 }	

             }


             else{ //  if row is not there do not upate
                    ?>
                    <div class="edpq-response-msg"><p>Database row does not exist.<br>Page will reload soon.</p>
                    <div class="edpq-ajax-loader"></div></div>
                    <?php		
             }
                         $SubmissionConn->close();
         }
         wp_die();
        } // end function

        public function net_submission_skip_trash($post_id) {
            if (get_post_type($post_id) == 'net_submission') {
                // Force delete
                wp_delete_post( $post_id, true );
            }
        } // end function

        public function intercept_publishToDraft ($post_ID, $data ) {
                if (get_post_type($post_ID) !== 'net_submission') {
                    return;
                }
                $post = get_post($post_ID);

                if ( ( $data['post_status'] !== 'publish' ) && ( $data['post_status'] !== 'trash' ) && ( $post->post_type == 'net_submission' ) ) {

                    wp_die( 'not allowed');
                }

        } // end function


        public function do_updated_to_publish( $post_id, $post , $old_status  ) {

                if($old_status == 'publish'){
                    return;
                }
                else{

                //$post_id = $post->ID; // Get the current posts ID
                 $conn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD,DB_NAME);

                 if (!$conn)
                 { 
                 die("Connection to database failed with error#: " . mysqli_connect_error()); 
                 }   

                 $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';"; //----- get current queue list

                 $result = mysqli_query($conn, $sql);
                 $row = mysqli_fetch_assoc($result);
                 if( isset($row['list']) && !empty($row['list']) ){ // if current queue list exists add to array with new post that was published
                         $stored_queue_list_arr = unserialize(base64_decode($row['list']));	
                        if( is_array($stored_queue_list_arr) && !empty($stored_queue_list_arr) ){
                              $getHighestNumberInQueueDatabase = array_key_last($stored_queue_list_arr);
                              $highestNumber =  $stored_queue_list_arr[$getHighestNumberInQueueDatabase]['queueNumber'];
                              $addthis = intval($highestNumber) + 1;
                              $stored_queue_list_arr[] = array("postid" => $post_id, "queueNumber" => $addthis); 
                               $serialized_array = base64_encode(serialize($stored_queue_list_arr)); // store jobs in database
                                 $sql_addToListDB = "UPDATE edpq_net_photos_queue_order SET list='" . $serialized_array . "' WHERE id=1";
                                if ($conn->query($sql_addToListDB) === TRUE) {
                                 echo "New record created successfully";
                                 } 
                                 if ($conn->query($sql_addToListDB) !== TRUE) {
                                 echo "SQL Error: " . $sql_addToListDB . "<br>" . $conn->error;
                                 }


                            //  echo"if loaded";
                        }
                        else{ // array is empty so no queue list are there do same as if no row is in database.
                           $create_queue_list_arr = [];
                           $create_queue_list_arr[] = array("postid" => $post_id, "queueNumber" => 1); 
                           $serialized_array = base64_encode(serialize($create_queue_list_arr)); // store jobs in database
                             $sql_createToListDB = "UPDATE edpq_net_photos_queue_order SET list='" . $serialized_array . "' WHERE id=1";
                            if ($conn->query($sql_createToListDB) === TRUE) {
                             echo "New record created successfully";
                             } 
                             if ($conn->query($sql_createToListDB) !== TRUE) {
                             echo "SQL Error: " . $sql_createToListDB . "<br>" . $conn->error;
                             }					
                        }

                 }
                 else{ // if queue list is empty create it
                   $create_queue_list_arr = [];
                   $create_queue_list_arr[] = array("postid" => $post_id, "queueNumber" => 1); 
                   $serialized_array = base64_encode(serialize($create_queue_list_arr)); // store jobs in database
                     $sql_createToListDB = "UPDATE edpq_net_photos_queue_order SET list='" . $serialized_array . "' WHERE id=1";
                    if ($conn->query($sql_createToListDB) === TRUE) {
                     echo "New record created successfully";
                     } 
                     if ($conn->query($sql_createToListDB) !== TRUE) {
                     echo "SQL Error: " . $sql_createToListDB . "<br>" . $conn->error;
                     }
                 }

                     $conn->close();					
                }

            } // end of function

            //admin menu callback function

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

            } // end of function

            public function edpqqueue_list_page(){
                global $pagenow;
                $plugin_url = plugin_dir_url(dirname(__FILE__));
                $rand = rand(1, 99999999999);
                require_once __DIR__  . '/../templates/options-page-auto-submission.php';


                wp_enqueue_style( 'edpq-photo-submission-styles', $plugin_url  . '/admin/assets/css/edpq-photo-submission.css' , array(),  $rand );

                wp_enqueue_script( 'edpq-photo-submission-scripts', $plugin_url  . '/admin/assets/js/edpq-photo-submission.js', array('jquery'), $rand, true);
                    wp_localize_script('edpq-photo-submission-scripts', 'ajax_net_photo_deletion_info', array(
                    'ajaxurl_net_photo_deletion_info' => admin_url('admin-ajax.php') ,
                    'noposts' => __('No older posts found', 'edpq-white') ,
                  )); 
                return;
            }

            public function edpqadmin_queue_list_page(){
                require_once __DIR__ . '/../templates/options-page-admin-queue-list.php';
                return;
            }

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
               
            }

            /**
             * Remove meta boxes from the post edit screens
             */
            public function edpq_net_submission_remove_meta_boxes() {
                    remove_meta_box( 'submitdiv', 'net_submission', 'normal' );
            }

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

            public function my_cpt_row_actions( $actions, $post ) {
                if ( 'net_submission' === $post->post_type ) {
                    // Removes the "Quick Edit" action.
                    // Removes the "Trash" action.
                    unset( $actions['inline hide-if-no-js'] );
                    unset( $actions['trash'] );
                }
                return $actions;
            }

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

                $headers = array('Content-Type: text/html; charset=UTF-8','From: Esmond Mccain <esmondmccain@gmail.com>', 'Reply-To: Esmond Mccain <esmondmccain@gmail.com>');// this does not work but also does not hinder
                // send email of new post
                // Recipient, in this case the administrator email
                $emailto = 'esmondmccain@gmail.com';

                // Email subject, "New {post_type_label}"
                $subject = 'New Photo Submission for: ' . $_POST['topic_headline_value'] . ' ' . date("m-d-y");

                wp_set_current_user(604); // get user that can edit posts so edit link function will work
                // Email body
                $message = 'View it: ' . get_permalink( $pid ) . "<br><br>Edit it: " . get_edit_post_link( $pid, "&" );
                 wp_set_current_user(0);  // turn off get user after get link function

                wp_mail( $emailto, $subject, $message, $headers );
                echo '<p class="newpost-success">Thank you for your submission!</p>';
                wp_die();
            }

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
                     if( has_shortcode( $post->post_content, 'EmDailyPostsQueueDisplayPost' ) ){
                        wp_enqueue_style( 'edpq-display-styles', $plugin_url . '/assets/css/display.css' , array(),  $rand ); 
                     }

            }

    } // Closing bracket for classes

}

new automatePhotoNetSubmissions;