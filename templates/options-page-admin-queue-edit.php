<?php
/**
 * Admin Queue Edit Page Markup
 * This template renders the queue with reorder and delete controls for each item.
 * Usage: include this file from your class function for the admin queue edit page.
 */
if (!isset($queue_list) || !is_array($queue_list)) {
    $queue_list = [];
}
?>
<?php
// Stub for Action Scheduler's as_next_scheduled_action.
// Prevents Intelephense "undefined function" warnings in development environments.
if (!function_exists('as_next_scheduled_action')) {
    function as_next_scheduled_action($hook) { return false; }
}
// Get next scheduled cron event timestamp
$next_cron_timestamp = false;
if (function_exists('as_next_scheduled_action')) {
    $next_cron_timestamp = as_next_scheduled_action('eg_1_weekdays_log');
}
$next_cron_time = $next_cron_timestamp ? date('Y-m-d H:i:s', $next_cron_timestamp) : 'Not scheduled';

// Get the next post to be removed (first in queue)
$next_post = !empty($queue_list) ? $queue_list[0] : null;
$next_post_title = $next_post ? get_the_title($next_post['postid']) : 'None';
$next_post_id = $next_post ? $next_post['postid'] : 'None';
?>

<div class="edpq-admin-queue-wrap">
    <h1>Edit Photo Submission Queue</h1>
    <div class="edpq-cron-info" style="margin-bottom:20px;">
        <strong>Next Cron Event:</strong> <?php echo esc_html($next_cron_time); ?><br>
        <strong>Next Post to be Removed:</strong>
        <?php if ($next_post): ?>
            <?php echo esc_html($next_post_title); ?> (ID: <?php echo esc_html($next_post_id); ?>)
        <?php else: ?>
            None
        <?php endif; ?>
    </div>
    <?php if (current_user_can('manage_options')): ?>
    <a href="<?php echo esc_url(add_query_arg('import_demo', '1')); ?>" class="button" style="margin-bottom:15px;">Import Demo Submissions</a>
    <?php endif; ?>

    <!-- Queue actions form (AJAX) -->
    <form id="admin-queue-edit-form" method="post">
        <div id="queue-list">
            <?php foreach ($queue_list as $item): ?>
            <!-- Conflict warning will be injected by JS as .edpq-conflict-warning -->    
                <?php $post_title = get_the_title($item['postid']); ?>
                <div class="queue-row" data-postid="<?php echo esc_attr($item['postid']); ?>" data-queuenumber="<?php echo esc_attr($item['queueNumber']); ?>" data-posttitle="<?php echo esc_attr($post_title); ?>">
                    <span class="queue-title"><?php echo esc_html($post_title); ?> (Queue Order: <?php echo esc_html($item['queueNumber']); ?>)</span>
                    <button type="button" class="queue-up">&#8593; Up</button>
                    <button type="button" class="queue-down">&#8595; Down</button>
                    <button type="button" class="queue-delete">Delete</button>
                    <input type="hidden" name="queue-postID-<?php echo esc_attr($item['queueNumber']); ?>" value="<?php echo esc_attr($item['postid']); ?>">
                    <input type="hidden" name="queue-value-<?php echo esc_attr($item['queueNumber']); ?>" value="<?php echo esc_attr($item['queueNumber']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="save_queue_order" class="button button-primary">Save Queue Order</button>
        <?php if (current_user_can('manage_options')): ?>
        <button type="button" id="full-wipe-btn" class="button button-danger" style="margin-left:10px;">Full Wipe</button>
        <?php endif; ?>
    </form>

    <!-- Cron time update form (regular POST) -->
    <form id="cron-time-form" method="post" style="margin-top:30px;">
        <h2>Update Cron Event Time</h2>
        <label for="cron-time-input">Set Cron Time (e.g. "+1 weekday 8pm"):</label>
        <input type="text" name="cron_time_input" id="cron-time-input" placeholder="+1 weekday 8pm">
        <button type="submit" name="update_cron_time" class="button">Update Cron Time</button>
    </form>
</div>
