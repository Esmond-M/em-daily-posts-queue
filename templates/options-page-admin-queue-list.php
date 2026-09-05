<?php
/** Unified Photo Queue screen. */
if (!defined('ABSPATH')) {
    exit;
}

$queue_error = !isset($queue_list) || !is_array($queue_list) || isset($queue_list['error']);
$queue_items = $queue_error ? [] : array_values($queue_list);
$can_manage_queue = current_user_can('manage_options');
$queue_count = count($queue_items);

$next_cron_timestamp = false;
if (function_exists('as_next_scheduled_action')) {
    $next_cron_timestamp = as_next_scheduled_action('eg_1_weekdays_log');
}
$next_cron_time = !empty($schedule_settings['paused']) ? __('Paused', 'em-daily-posts-queue') : ($next_cron_timestamp ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $next_cron_timestamp) : __('Not scheduled', 'em-daily-posts-queue'));

$current_item = $queue_items[0] ?? null;
$current_id = is_array($current_item) && isset($current_item['postid']) ? absint($current_item['postid']) : 0;
$current_post = $current_id ? get_post($current_id) : null;
$current_title = $current_post && 'net_submission' === $current_post->post_type ? get_the_title($current_post) : __('None', 'em-daily-posts-queue');
$current_title = '' !== trim($current_title) ? $current_title : __('(Untitled submission)', 'em-daily-posts-queue');
?>
<div class="wrap edpq-queue-overview">
    <div class="edpq-queue-header">
        <div class="edpq-queue-header-icon" aria-hidden="true">
            <span class="dashicons dashicons-screenoptions"></span>
        </div>
        <div>
            <h1><?php esc_html_e('Photo Submission Queue', 'em-daily-posts-queue'); ?></h1>
            <p class="description edpq-queue-intro">
                <?php esc_html_e('Review photo submissions, choose their display order, and rotate the featured photo on a schedule.', 'em-daily-posts-queue'); ?>
            </p>
        </div>
    </div>

    <div class="edpq-summary-cards" aria-label="<?php esc_attr_e('Queue summary', 'em-daily-posts-queue'); ?>">
        <div class="edpq-summary-card">
            <div class="edpq-summary-icon edpq-summary-icon-purple" aria-hidden="true">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div>
                <div class="edpq-summary-label"><?php esc_html_e('Next rotation', 'em-daily-posts-queue'); ?></div>
                <div class="edpq-summary-value"><?php echo esc_html($next_cron_time); ?></div>
            </div>
        </div>
        <div class="edpq-summary-card">
            <div class="edpq-summary-icon edpq-summary-icon-orange" aria-hidden="true">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div>
                <div class="edpq-summary-label"><?php esc_html_e('Showing now', 'em-daily-posts-queue'); ?></div>
                <div class="edpq-summary-value">
                    <?php echo esc_html($current_title); ?>
                    <?php if ($current_id): ?>
                        <span class="edpq-id-tag">#<?php echo esc_html((string) $current_id); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="edpq-summary-card">
            <div class="edpq-summary-icon edpq-summary-icon-teal" aria-hidden="true">
                <span class="dashicons dashicons-list-view"></span>
            </div>
            <div>
                <div class="edpq-summary-label"><?php esc_html_e('Queue size', 'em-daily-posts-queue'); ?></div>
                <div class="edpq-summary-value">
                    <?php
                    /* translators: %s: Number of submissions in the saved queue. */
                    printf(esc_html(_n('%s item', '%s items', $queue_count, 'em-daily-posts-queue')), esc_html(number_format_i18n($queue_count)));
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="edpq-queue-toolbar">
        <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=net_submission')); ?>">
            <span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
            <?php esc_html_e('All submissions', 'em-daily-posts-queue'); ?>
        </a>
        <?php if ($can_manage_queue): ?>
            <a href="<?php echo esc_url(add_query_arg('import_demo', '1')); ?>" class="button">
                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                <?php esc_html_e('Import demo submissions', 'em-daily-posts-queue'); ?>
            </a>
            <button type="button" id="full-wipe-btn" class="button-link-delete edpq-toolbar-danger">
                <?php esc_html_e('Full Wipe', 'em-daily-posts-queue'); ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($queue_error): ?>
        <div class="notice notice-error inline">
            <p><?php esc_html_e('The queue could not be loaded. Refresh this page to try again.', 'em-daily-posts-queue'); ?></p>
        </div>
    <?php elseif (!$queue_items): ?>
        <div class="edpq-queue-empty">
            <h2><?php esc_html_e('No photos in the queue', 'em-daily-posts-queue'); ?></h2>
            <p><?php esc_html_e('Published submissions appear here in display order. Visit All submissions to review the available photos.', 'em-daily-posts-queue'); ?></p>
        </div>
    <?php else: ?>
        <?php if ($can_manage_queue): ?>
            <form id="admin-queue-edit-form" method="post">
        <?php endif; ?>
        <div class="edpq-queue-panel">
            <div class="edpq-queue-panel-header">
                <h2 id="edpq-display-order" class="edpq-queue-panel-title">
                    <span class="dashicons dashicons-camera-alt" aria-hidden="true"></span>
                    <?php esc_html_e('Submission Queue', 'em-daily-posts-queue'); ?>
                    <span class="edpq-badge">
                        <?php
                        /* translators: %s: Number of submissions in the saved queue. */
                        printf(esc_html(_n('%s item', '%s items', $queue_count, 'em-daily-posts-queue')), esc_html(number_format_i18n($queue_count)));
                        ?>
                    </span>
                </h2>
                <?php if ($can_manage_queue): ?>
                    <div class="edpq-queue-panel-hint">
                        <span class="dashicons dashicons-move" aria-hidden="true"></span>
                        <?php esc_html_e('Use arrows to reorder', 'em-daily-posts-queue'); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div id="queue-list" class="edpq-queue-list" role="list" aria-labelledby="edpq-display-order">
                <?php foreach ($queue_items as $index => $item): ?>
                    <?php
                    $submission_id = isset($item['postid']) ? absint($item['postid']) : 0;
                    $submission = $submission_id ? get_post($submission_id) : null;
                    $available = $submission && 'net_submission' === $submission->post_type && 'publish' === $submission->post_status;
                    $can_view = $available && current_user_can('read_post', $submission_id);
                    $title = $available ? get_the_title($submission) : __('Submission unavailable', 'em-daily-posts-queue');
                    $title = '' !== trim($title) ? $title : __('(Untitled submission)', 'em-daily-posts-queue');
                    $thumbnail = $available ? get_the_post_thumbnail($submission_id, 'thumbnail', ['alt' => '', 'loading' => 'lazy']) : '';
                    $caption = $available ? get_post_meta($submission_id, 'topic_caption_value', true) : '';
                    ?>
                    <div class="edpq-queue-row <?php echo $can_manage_queue ? 'queue-row' : ''; ?>" role="listitem" <?php echo $can_manage_queue ? 'tabindex="0"' : ''; ?> data-postid="<?php echo esc_attr($submission_id); ?>" data-queuenumber="<?php echo esc_attr($index + 1); ?>" data-posttitle="<?php echo esc_attr($title); ?>">
                        <span class="edpq-drag-marker" aria-hidden="true"><span class="dashicons dashicons-menu-alt"></span></span>
                        <span class="edpq-position"><?php echo esc_html(number_format_i18n($index + 1)); ?></span>
                        <span class="edpq-photo">
                            <?php if ($thumbnail): ?>
                                <?php echo wp_kses_post($thumbnail); ?>
                            <?php else: ?>
                                <span class="edpq-no-photo"><span class="dashicons dashicons-format-image" aria-hidden="true"></span></span>
                            <?php endif; ?>
                        </span>
                        <span class="edpq-submission">
                            <?php if ($can_view): ?>
                                <a class="edpq-submission-title queue-title" href="<?php echo esc_url(get_permalink($submission)); ?>"><?php echo esc_html($title); ?></a>
                            <?php else: ?>
                                <span class="edpq-submission-title queue-title"><?php echo esc_html($title); ?></span>
                            <?php endif; ?>
                            <span class="edpq-submission-id">
                                <?php
                                /* translators: %d: WordPress submission post ID. */
                                printf(esc_html__('Post ID: %d', 'em-daily-posts-queue'), $submission_id);
                                ?>
                            </span>
                            <?php if (is_string($caption) && '' !== trim($caption)): ?>
                                <span class="edpq-submission-caption"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($caption), 18)); ?></span>
                            <?php endif; ?>
                            <?php if (!$available): ?>
                                <span class="edpq-submission-caption"><?php esc_html_e('This queue entry no longer points to a published submission. Ask a queue administrator to review it.', 'em-daily-posts-queue'); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="edpq-display">
                            <?php if (!$available): ?>
                                <span class="edpq-display-badge edpq-display-unavailable"><?php esc_html_e('Unavailable', 'em-daily-posts-queue'); ?></span>
                            <?php elseif (0 === $index): ?>
                                <span class="edpq-display-badge edpq-display-current"><?php esc_html_e('Showing now', 'em-daily-posts-queue'); ?></span>
                            <?php elseif (1 === $index): ?>
                                <span class="edpq-display-badge"><?php esc_html_e('Up next', 'em-daily-posts-queue'); ?></span>
                            <?php else: ?>
                                <span class="edpq-display-badge"><?php esc_html_e('Queued', 'em-daily-posts-queue'); ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if ($can_manage_queue): ?>
                            <span class="edpq-actions">
                                <span class="edpq-action-group" aria-label="<?php esc_attr_e('Queue item actions', 'em-daily-posts-queue'); ?>">
                                    <button type="button" class="button button-small edpq-icon-button queue-up" aria-label="<?php esc_attr_e('Move up', 'em-daily-posts-queue'); ?>">
                                        <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                                    </button>
                                    <button type="button" class="button button-small edpq-icon-button queue-down" aria-label="<?php esc_attr_e('Move down', 'em-daily-posts-queue'); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                    </button>
                                    <?php if ($available && current_user_can('edit_post', $submission_id)): ?>
                                        <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($submission_id, '')); ?>"><?php esc_html_e('Edit', 'em-daily-posts-queue'); ?></a>
                                    <?php endif; ?>
                                    <button type="button" class="button-link-delete edpq-row-delete queue-delete" aria-label="<?php esc_attr_e('Remove and delete', 'em-daily-posts-queue'); ?>">
                                        <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                    </button>
                                </span>
                                <input type="hidden" name="queue-postID-<?php echo esc_attr($index + 1); ?>" value="<?php echo esc_attr($submission_id); ?>">
                                <input type="hidden" name="queue-value-<?php echo esc_attr($index + 1); ?>" value="<?php echo esc_attr($index + 1); ?>">
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($can_manage_queue): ?>
                <div class="edpq-queue-panel-footer">
                    <div id="edpq-queue-status" class="edpq-queue-status" role="status" aria-live="polite"></div>
                    <p class="submit">
                        <button type="submit" name="save_queue_order" class="button button-primary" disabled>
                            <?php esc_html_e('Save queue order', 'em-daily-posts-queue'); ?>
                        </button>
                        <button type="button" id="edpq-discard-queue-changes" class="button" disabled>
                            <?php esc_html_e('Discard changes', 'em-daily-posts-queue'); ?>
                        </button>
                    </p>
                    <div class="edpq-footer-hint">
                        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                        <?php esc_html_e('Use Alt+Up or Alt+Down on a row to reorder.', 'em-daily-posts-queue'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($can_manage_queue): ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($can_manage_queue): ?>
        <div class="edpq-schedule-panel">
            <h2><?php esc_html_e('Schedule', 'em-daily-posts-queue'); ?></h2>
            <form id="cron-time-form" method="post">
                <?php wp_nonce_field('edpq_update_schedule', 'edpq_schedule_nonce'); ?>
                <fieldset class="edpq-schedule-fieldset">
                    <legend><?php esc_html_e('Rotation days', 'em-daily-posts-queue'); ?></legend>
                    <?php foreach ($schedule_choices as $mode => $label): ?>
                        <label class="edpq-schedule-option">
                            <input type="radio" name="edpq_schedule_mode" value="<?php echo esc_attr($mode); ?>" <?php checked($schedule_settings['mode'], $mode); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <fieldset class="edpq-schedule-fieldset edpq-schedule-days">
                    <legend><?php esc_html_e('Selected days', 'em-daily-posts-queue'); ?></legend>
                    <?php foreach ($weekday_choices as $day => $label): ?>
                        <label class="edpq-schedule-day">
                            <input type="checkbox" name="edpq_schedule_days[]" value="<?php echo esc_attr((string) $day); ?>" <?php checked(in_array((int) $day, $schedule_settings['days'], true)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="edpq-schedule-row">
                    <label for="edpq-schedule-time"><?php esc_html_e('Rotation time', 'em-daily-posts-queue'); ?></label>
                    <input type="time" name="edpq_schedule_time" id="edpq-schedule-time" value="<?php echo esc_attr($schedule_settings['time']); ?>" required>
                    <span class="description">
                        <?php
                        /* translators: %s: WordPress timezone name. */
                        printf(esc_html__('Site timezone: %s', 'em-daily-posts-queue'), esc_html(wp_timezone_string() ?: 'UTC'));
                        ?>
                    </span>
                </div>
                <label class="edpq-schedule-paused">
                    <input type="checkbox" name="edpq_schedule_paused" value="1" <?php checked($schedule_settings['paused']); ?>>
                    <?php esc_html_e('Pause scheduled rotation', 'em-daily-posts-queue'); ?>
                </label>
                <p class="submit">
                    <button type="submit" name="update_cron_time" class="button">
                        <?php esc_html_e('Update schedule', 'em-daily-posts-queue'); ?>
                    </button>
                </p>
            </form>
        </div>
    <?php endif; ?>
</div>