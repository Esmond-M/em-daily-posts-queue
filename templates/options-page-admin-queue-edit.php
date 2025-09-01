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
<div class="wrap">
    <h1>Edit Photo Submission Queue</h1>
    <form id="admin-queue-edit-form" method="post">
        <div id="queue-list">
            <?php foreach ($queue_list as $item): ?>
                <div class="queue-row" data-postid="<?php echo esc_attr($item['postid']); ?>" data-queuenumber="<?php echo esc_attr($item['queueNumber']); ?>">
                    <span class="queue-title">Photo Submission #<?php echo esc_html($item['queueNumber']); ?> (Post ID: <?php echo esc_html($item['postid']); ?>)</span>
                    <button type="button" class="queue-up">&#8593; Up</button>
                    <button type="button" class="queue-down">&#8595; Down</button>
                    <button type="button" class="queue-delete">Delete</button>
                    <input type="hidden" name="queue-postID-<?php echo esc_attr($item['queueNumber']); ?>" value="<?php echo esc_attr($item['postid']); ?>">
                    <input type="hidden" name="queue-value-<?php echo esc_attr($item['queueNumber']); ?>" value="<?php echo esc_attr($item['queueNumber']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="button button-primary">Save Queue Order</button>
    </form>
</div>
