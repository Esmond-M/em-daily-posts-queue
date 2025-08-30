
<?php
/**
 * Template for single Net Submission post
 *
 * @package em-daily-posts-queue
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<main id="main" class="site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>
        </header>
        <div class="entry-content">
            <?php
            $postID = get_the_ID();
            $featuredImage = get_the_post_thumbnail_url($postID,'large');
            $netTopicHeadline = get_post_meta($postID, 'topic_headline_value', true);
            $netTopicCaption = get_post_meta($postID, 'topic_caption_value', true);
            ?>
            <div class="edpq-around-edpq">
                <div class="edpq-content-left">
                    <img src="<?php echo esc_url($featuredImage); ?>" />
                </div>
                <div class="edpq-content-right">
                    <p class="heading">Daily Post </p>
                    <?php if( $netTopicHeadline ) { ?>
                        <p class="edpq-title"><?php echo esc_html($netTopicHeadline); ?></p>
                    <?php } ?>
                    <?php if( $netTopicCaption ) { ?>
                        <p class="edpq-net-caption"><?php echo esc_html($netTopicCaption); ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </article>
</main>
<?php get_footer(); ?>
