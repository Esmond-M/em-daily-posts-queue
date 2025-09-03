<?php
declare(strict_types=1);
/**
 * EmDailyPostsQueueUIManager Class
 *
 * Main controller for the plugin. Responsible for:
 * - Registering WordPress hooks, actions, and filters for admin UI, asset loading, and custom post type management
 * - Delegating AJAX requests to the PhotoNetSubmissionAjax class
 * - Interfacing with the utility class for queue management and array operations
 * - Managing admin and frontend UI (submenus, meta boxes, styles/scripts)
 * - Handling post lifecycle events for the custom post type
 * - Rendering admin pages for queue list and queue editing
 *
 * Note: Most AJAX logic and queue manipulation details have been moved to dedicated classes for modularity.
 */
namespace EmDailyPostsQueue\init_plugin\Classes;
require_once __DIR__ . '/class-photo-submission-utils.php';
require_once __DIR__ . '/class-photo-submission-ajax.php';

class EmDailyPostsQueueUIManager

{
    /**
     * @var \EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils Utility class for queue and array operations
     */
    private $utils;
    /**
     * @var PhotoNetSubmissionAjax Handles AJAX endpoints for queue management
     */
    private $ajax;

    /**
     * Declaring constructor
     */
    public function __construct()

    {

    // --- Initialization ---
    // Instantiate utility class for queue and array operations
    $this->utils = new \EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils();

    // Instantiate AJAX handler for queue management endpoints (delegates all AJAX logic)
    $this->ajax = new PhotoNetSubmissionAjax($this->utils);

    // --- Register hooks, actions, and filters ---
    // Admin UI and CPT management
    add_filter('bulk_actions-edit-net_submission', [$this, 'remove_bulk_actions']);
    add_action('admin_head', [$this, 'hide_bulk_actions_dropdown']);
    add_action('admin_menu', [$this, 'edpqPhotoSubmission_register_submenu_page']);
    add_action('admin_menu', [$this, 'edpq_net_submission_remove_meta_boxes']);
    add_action('add_meta_boxes', [$this, 'edpq_net_submission_register_meta_boxes']);
    add_filter('post_row_actions', [$this, 'my_cpt_row_actions'], 10, 2);

    // Post lifecycle management for custom post type
    add_action('trashed_post', [$this, 'net_submission_skip_trash']);
    add_action('pre_post_update', [$this, 'intercept_publishToDraft'], 10, 2);
    add_action('publish_net_submission', [$this, 'do_updated_to_publish'], 10, 3 );

    // AJAX handlers (delegated to PhotoNetSubmissionAjax)
    add_action('wp_ajax_net_photo_deletion_info_ajax', [$this->ajax, 'net_photo_deletion_info_ajax']);
    add_action('wp_ajax_nopriv_net_photo_deletion_info_ajax', [$this->ajax, 'net_photo_deletion_info_ajax']);
    add_action('wp_ajax_form_post_new_net_photo_submission_ajax', [$this->ajax, 'form_post_new_net_photo_submission_ajax']);
    add_action('wp_ajax_nopriv_form_post_new_net_photo_submission_ajax', [$this->ajax, 'form_post_new_net_photo_submission_ajax']);
    add_action('wp_ajax_admin_queue_edit', [$this->ajax, 'handle_admin_queue_edit_ajax']);
    add_action('wp_ajax_admin_queue_full_wipe', [$this->ajax, 'handle_admin_queue_full_wipe_ajax']);

    // Asset loading for admin and frontend
    add_action('admin_enqueue_scripts', [$this->ajax, 'load_admin_net_style']);
    add_action('wp_enqueue_scripts', [$this->ajax, 'net_style_scripts']);

    }

       // 1. Admin UI

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
     * Remove bulk actions for net_submission post type
     */
    public function remove_bulk_actions($actions) {
        unset($actions['edit']); // Remove Bulk Edit
        unset($actions['trash']); // Optionally remove Trash
        return $actions;
    }

    /**
     * Hide bulk actions dropdown for net_submission post type using plugin style
     */
    public function hide_bulk_actions_dropdown() {
        $screen = get_current_screen();
        if ($screen->post_type === 'net_submission') {
            echo '<style>.bulkactions { display: none !important; }</style>';
        }
    }

    /**
     * Admin: Register submenu pages for queue list and admin queue list
     */
    public function edpqPhotoSubmission_register_submenu_page() {

        // Register custom admin submenu pages for photo submission queue management

        // Submenu: Admin Photo Queue List (read-only view)
        // - Appears under the "net_submission" post type menu
        // - Allows admins to view the current photo submission queue
        add_submenu_page(
            'edit.php?post_type=net_submission', // Parent menu (custom post type)
            'Admin Photo Queue List',            // Page title (shown in browser tab)
            'Admin Photo Queue List',            // Menu title (shown in WP admin menu)
            'manage_options',                    // Capability required to access
            'admin-queue-list',                  // Menu slug
            [$this, 'edpqadmin_queue_list_page'] // Callback to render the page
        );

        // Submenu: Edit Photo Queue (reorder/delete UI)
        // - Appears under the "net_submission" post type menu
        // - Allows admins to reorder or delete items in the queue
        add_submenu_page(
            'edit.php?post_type=net_submission', // Parent menu (custom post type)
            'Edit Photo Queue',                  // Page title (shown in browser tab)
            'Edit Photo Queue',                  // Menu title (shown in WP admin menu)
            'manage_options',                    // Capability required to access
            'admin-queue-edit',                  // Menu slug
            [$this, 'edpqadmin_queue_edit_page'] // Callback to render the page
        );



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
     * Customize row actions for net_submission posts (removes quick edit/trash)
     */
    public function my_cpt_row_actions( $actions, $post ) {
        if ( 'net_submission' === $post->post_type ) {
            // Removes the "Quick Edit" action.
            // Removes the "Trash" action.
            // Removes the "Bulk Edit" action.
            unset( $actions['inline hide-if-no-js'] );
            unset( $actions['trash'] );
            unset( $actions['bulk_edit'] );
        }
        return $actions;
    }

    // 2. Post Management
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
        $queue_list = $this->utils->get_queue_list_from_db();
        if (is_array($queue_list) && !empty($queue_list)) {
            $queue_list[] = array("postid" => $post_id, "queueNumber" => 0); // temp value
        } else {
            $queue_list = [array("postid" => $post_id, "queueNumber" => 0)];
        }
        // Reindex queueNumber sequentially (no gaps)
        foreach ($queue_list as $i => &$item) {
            $item['queueNumber'] = $i + 1;
        }
        unset($item);
        $result = $this->utils->update_queue_list_in_db($queue_list);

    }

    /**
     * Render the admin queue edit page (with reorder/delete UI)
     */
    public function edpqadmin_queue_edit_page(){
        // Handle demo import trigger
        if (isset($_GET['import_demo']) && $_GET['import_demo'] === '1' && current_user_can('manage_options')) {
            $this->utils->import_demo_net_submissions();
            echo '<div class="notice notice-success"><p>Demo net_submission posts imported!</p></div>';
        }

        // Handle cron time form submission
        if (
            isset($_POST['update_cron_time']) &&
            !empty($_POST['cron_time_input']) &&
            current_user_can('manage_options')
        ) {
            $cron_time = sanitize_text_field($_POST['cron_time_input']);
            $cron_timer = new \EmDailyPostsQueue\init_plugin\Classes\CronEventTimer();
            $cron_timer->update_cron_schedule_from_input($cron_time);
            echo '<div class="notice notice-success"><p>Cron event time updated!</p></div>';
        }

        $queue_list = $this->utils->get_queue_list();
        require_once __DIR__ . '/../templates/options-page-admin-queue-edit.php';
    }

    /**
     * Render the admin queue list page (read-only, no editing)
     */
    public function edpqadmin_queue_list_page(){

        $queue_list = $this->utils->get_queue_list();
        require_once __DIR__ . '/../templates/options-page-admin-queue-list.php';

    }

}
new EmDailyPostsQueueUIManager();