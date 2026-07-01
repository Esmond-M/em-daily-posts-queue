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
$queue_count = count($queue_list);
?>

<div class="edpq-admin-queue-wrap">

    <!-- Page Header -->
    <div class="edpq-page-header">
        <div class="edpq-page-header-icon">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <div class="edpq-page-header-text">
            <h1>Photo Submission Queue</h1>
            <p>Reorder, manage, and schedule your photo submissions</p>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="edpq-info-cards">
        <div class="edpq-info-card">
            <div class="edpq-info-card-icon purple">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div class="edpq-info-card-body">
                <div class="edpq-info-card-label">Next Cron Event</div>
                <div class="edpq-info-card-value"><?php echo esc_html($next_cron_time); ?></div>
            </div>
        </div>
        <div class="edpq-info-card">
            <div class="edpq-info-card-icon orange">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div class="edpq-info-card-body">
                <div class="edpq-info-card-label">Next to be Published</div>
                <div class="edpq-info-card-value">
                    <?php if ($next_post): ?>
                        <?php echo esc_html($next_post_title); ?> <span class="edpq-id-tag">#<?php echo esc_html($next_post_id); ?></span>
                    <?php else: ?>
                        None
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="edpq-info-card">
            <div class="edpq-info-card-icon teal">
                <i class="fa-solid fa-list-ol"></i>
            </div>
            <div class="edpq-info-card-body">
                <div class="edpq-info-card-label">Queue Size</div>
                <div class="edpq-info-card-value"><?php echo esc_html($queue_count); ?> item<?php echo $queue_count !== 1 ? 's' : ''; ?></div>
            </div>
        </div>
    </div>

    <?php if (current_user_can('manage_options')): ?>
    <!-- Toolbar -->
    <div class="edpq-toolbar">
        <a href="<?php echo esc_url(add_query_arg('import_demo', '1')); ?>" class="edpq-btn edpq-btn-ghost">
            <i class="fa-solid fa-file-import"></i>
            Import Demo Submissions
        </a>
    </div>
    <?php endif; ?>

    <!-- Queue Panel -->
    <form id="admin-queue-edit-form" method="post">
        <div class="edpq-queue-panel">
            <div class="edpq-queue-panel-header">
                <div class="edpq-queue-panel-title">
                    <i class="fa-solid fa-camera-retro"></i>
                    Submission Queue
                    <span class="edpq-badge"><?php echo esc_html($queue_count); ?> items</span>
                </div>
                <div class="edpq-queue-panel-hint">
                    <i class="fa-solid fa-arrows-up-down" style="margin-right:4px;"></i>Use arrows to reorder
                </div>
            </div>

            <div id="queue-list">
                <?php if (empty($queue_list)): ?>
                    <div class="edpq-empty-state">
                        <i class="fa-regular fa-folder-open edpq-empty-state-icon"></i>
                        <p>No submissions in the queue yet.<br>Import demo submissions or publish a net_submission post to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($queue_list as $index => $item): ?>
                    <!-- Conflict warning will be injected by JS as .edpq-conflict-warning -->
                        <?php $post_title = get_the_title($item['postid']); ?>
                        <div class="queue-row" data-postid="<?php echo esc_attr($item['postid']); ?>" data-queuenumber="<?php echo esc_attr($item['queueNumber']); ?>" data-posttitle="<?php echo esc_attr($post_title); ?>">
                            <!-- Drag handle -->
                            <div class="edpq-drag-handle" title="Drag to reorder">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </div>
                            <!-- Queue number badge -->
                            <div class="edpq-queue-number"><?php echo esc_html($item['queueNumber']); ?></div>
                            <!-- Title -->
                            <span class="queue-title"><?php echo esc_html($post_title); ?></span>
                            <!-- Next badge on first item -->
                            <?php if ($index === 0): ?>
                                <span class="edpq-next-badge"><i class="fa-solid fa-bolt"></i> Next Up</span>
                            <?php endif; ?>
                            <!-- Action buttons -->
                            <div class="edpq-row-actions">
                                <button type="button" class="queue-up" title="Move up">
                                    <i class="fa-solid fa-chevron-up"></i>
                                </button>
                                <button type="button" class="queue-down" title="Move down">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <button type="button" class="queue-delete" title="Remove from queue">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                            <input type="hidden" name="queue-postID-<?php echo esc_attr($item['queueNumber']); ?>" value="<?php echo esc_attr($item['postid']); ?>">
                            <input type="hidden" name="queue-value-<?php echo esc_attr($item['queueNumber']); ?>" value="<?php echo esc_attr($item['queueNumber']); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="edpq-queue-panel-footer">
                <div class="edpq-footer-actions">
                    <button type="submit" name="save_queue_order" class="edpq-btn edpq-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Queue Order
                    </button>
                    <?php if (current_user_can('manage_options')): ?>
                    <button type="button" id="full-wipe-btn" class="edpq-btn edpq-btn-danger">
                        <i class="fa-solid fa-trash"></i>
                        Full Wipe
                    </button>
                    <?php endif; ?>
                </div>
                <div class="edpq-footer-hint">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Changes are saved via AJAX
                </div>
            </div>
        </div>
    </form>

    <!-- Cron Time Update Section -->
    <div class="edpq-cron-section">
        <div class="edpq-cron-section-header">
            <div class="edpq-cron-section-icon">
                <i class="fa-solid fa-gear"></i>
            </div>
            <h2>Update Cron Schedule</h2>
        </div>
        <div class="edpq-cron-section-body">
            <form id="cron-time-form" method="post">
                <div class="edpq-cron-form-row">
                    <label for="cron-time-input">
                        <i class="fa-regular fa-calendar-clock" style="margin-right:5px;"></i>Schedule Expression:
                    </label>
                    <input type="text" name="cron_time_input" id="cron-time-input" class="edpq-cron-input" placeholder="+1 weekday 8pm">
                    <button type="submit" name="update_cron_time" class="edpq-btn edpq-btn-ghost">
                        <i class="fa-solid fa-rotate"></i>
                        Update Schedule
                    </button>
                </div>
                <p class="edpq-cron-hint">Example: <code>+1 weekday 8pm</code></p>
            </form>
        </div>
    </div>

</div>
