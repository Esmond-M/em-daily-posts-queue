<?php
/** Read-only overview of the saved photo queue. */
if (!defined('ABSPATH')) {
    exit;
}

$queue_error = !isset($queue_list) || !is_array($queue_list) || isset($queue_list['error']);
$queue_items = $queue_error ? [] : array_values($queue_list);
?>
<div class="wrap edpq-queue-overview">
    <h1><?php esc_html_e('Photo Queue', 'em-daily-posts-queue'); ?></h1>
    <p class="description edpq-queue-intro">
        <?php esc_html_e('View the saved display order of your photo submissions. The first item is featured by the Daily Post display; the remaining items follow in order.', 'em-daily-posts-queue'); ?>
    </p>
    <div class="edpq-queue-toolbar">
        <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=net_submission')); ?>">
            <?php esc_html_e('All submissions', 'em-daily-posts-queue'); ?>
        </a>
        <?php if (current_user_can('manage_options')): ?>
            <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=net_submission&page=admin-queue-edit')); ?>">
                <?php esc_html_e('Manage queue', 'em-daily-posts-queue'); ?>
            </a>
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
        <div class="edpq-queue-summary">
            <h2 id="edpq-display-order"><?php esc_html_e('Display order', 'em-daily-posts-queue'); ?></h2>
            <p>
                <?php
                /* translators: %s: Number of submissions in the saved queue. */
                printf(esc_html(_n('%s submission', '%s submissions', count($queue_items), 'em-daily-posts-queue')), esc_html(number_format_i18n(count($queue_items))));
                ?>
            </p>
        </div>
        <div class="edpq-queue-table-scroll" role="region" aria-labelledby="edpq-display-order" tabindex="0">
            <table class="widefat striped edpq-queue-table">
                <caption class="screen-reader-text"><?php esc_html_e('Photo submissions in saved display order', 'em-daily-posts-queue'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col" class="edpq-position"><?php esc_html_e('Position', 'em-daily-posts-queue'); ?></th>
                        <th scope="col" class="edpq-photo"><?php esc_html_e('Photo', 'em-daily-posts-queue'); ?></th>
                        <th scope="col"><?php esc_html_e('Submission', 'em-daily-posts-queue'); ?></th>
                        <th scope="col" class="edpq-display"><?php esc_html_e('Display', 'em-daily-posts-queue'); ?></th>
                    </tr>
                </thead>
                <tbody>
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
                        <tr>
                            <td class="edpq-position"><?php echo esc_html(number_format_i18n($index + 1)); ?></td>
                            <td class="edpq-photo">
                                <?php if ($thumbnail): ?>
                                    <?php echo wp_kses_post($thumbnail); ?>
                                <?php else: ?>
                                    <span class="edpq-no-photo"><?php esc_html_e('No photo', 'em-daily-posts-queue'); ?></span>
                                <?php endif; ?>
                            </td>
                            <th scope="row" class="edpq-submission">
                                <?php if ($can_view): ?>
                                    <a class="edpq-submission-title" href="<?php echo esc_url(get_permalink($submission)); ?>"><?php echo esc_html($title); ?></a>
                                <?php else: ?>
                                    <span class="edpq-submission-title"><?php echo esc_html($title); ?></span>
                                <?php endif; ?>
                                <span class="edpq-submission-id">
                                    <?php
                                    /* translators: %d: WordPress submission post ID. */
                                    printf(esc_html__('Post ID: %d', 'em-daily-posts-queue'), $submission_id);
                                    ?>
                                </span>
                                <?php if (is_string($caption) && '' !== trim($caption)): ?>
                                    <p class="edpq-submission-caption"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($caption), 24)); ?></p>
                                <?php endif; ?>
                                <?php if (!$available): ?>
                                    <p class="edpq-submission-caption"><?php esc_html_e('This queue entry no longer points to a published submission. Ask a queue administrator to review it.', 'em-daily-posts-queue'); ?></p>
                                <?php endif; ?>
                            </th>
                            <td class="edpq-display">
                                <?php if (!$available): ?>
                                    <span class="edpq-display-badge edpq-display-unavailable"><?php esc_html_e('Unavailable', 'em-daily-posts-queue'); ?></span>
                                <?php elseif (0 === $index): ?>
                                    <span class="edpq-display-badge edpq-display-current"><?php esc_html_e('Showing now', 'em-daily-posts-queue'); ?></span>
                                <?php elseif (1 === $index): ?>
                                    <span class="edpq-display-badge"><?php esc_html_e('Up next', 'em-daily-posts-queue'); ?></span>
                                <?php else: ?>
                                    <span class="edpq-display-badge"><?php esc_html_e('Queued', 'em-daily-posts-queue'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>