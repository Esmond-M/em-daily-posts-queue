<?php
/**
 * Template for Net Submission archive
 *
 * @package em-daily-posts-queue
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<main id="main" class="site-main">
    <header class="page-header">
        <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
    </header>
    <div class="archive-content">
        <?php if ( have_posts() ) : ?>
            <ul class="net-submission-archive-list">
                <?php while ( have_posts() ) : the_post(); ?>
                    <li <?php post_class(); ?>>
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="post-thumbnail">
                                <?php the_post_thumbnail('thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php the_posts_navigation(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No submissions found.', 'em-daily-posts-queue'); ?></p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
