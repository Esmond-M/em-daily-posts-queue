<?php
/**
 * Shortcode reference card — rendered both on the Shortcodes submenu page
 * and inside the dashboard widget. $context is either 'page' or 'widget'.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$context = $context ?? 'page';
$is_page = $context === 'page';
?>
<style>
.edpq-sc-wrap {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 720px;
}
.edpq-sc-wrap h2 {
    margin: <?php echo $is_page ? '1.5rem 0 .5rem' : '.25rem 0 .75rem'; ?>;
    font-size: <?php echo $is_page ? '1.3rem' : '1rem'; ?>;
    color: #1d2327;
}
.edpq-sc-wrap p.edpq-sc-intro {
    color: #50575e;
    margin: 0 0 1.25rem;
    font-size: .95rem;
}
.edpq-sc-card {
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    padding: 1rem 1.1rem;
    margin-bottom: 1.1rem;
}
.edpq-sc-card h3 {
    margin: 0 0 .3rem;
    font-size: 1rem;
    color: #1d2327;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.edpq-sc-card h3 span.edpq-tag {
    background: #2271b1;
    color: #fff;
    border-radius: 3px;
    font-size: .7rem;
    padding: 1px 6px;
    font-weight: 600;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.edpq-sc-card p.edpq-sc-desc {
    margin: 0 0 .65rem;
    color: #50575e;
    font-size: .9rem;
    line-height: 1.5;
}
.edpq-sc-row {
    display: flex;
    align-items: center;
    gap: .5rem;
}
.edpq-sc-code {
    flex: 1;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: .45rem .7rem;
    font-family: "Courier New", Courier, monospace;
    font-size: .9rem;
    color: #2271b1;
    user-select: all;
    overflow-x: auto;
    white-space: nowrap;
}
.edpq-copy-btn {
    cursor: pointer;
    border: 1px solid #2271b1;
    background: #fff;
    color: #2271b1;
    border-radius: 4px;
    padding: .4rem .8rem;
    font-size: .85rem;
    font-weight: 600;
    white-space: nowrap;
    transition: background .15s, color .15s;
    flex-shrink: 0;
}
.edpq-copy-btn:hover { background: #2271b1; color: #fff; }
.edpq-copy-btn.copied { background: #00a32a; border-color: #00a32a; color: #fff; }
.edpq-sc-hint {
    margin: .4rem 0 0;
    font-size: .82rem;
    color: #646970;
}
.edpq-sc-hint a { color: #2271b1; }
</style>

<div class="edpq-sc-wrap">
    <?php if ( $is_page ) : ?>
        <h2>📋 Available Shortcodes</h2>
        <p class="edpq-sc-intro">
            Copy a shortcode and paste it into any <strong>Page, Post, or widget area</strong> to display
            that feature on the front end of your site.
        </p>
    <?php endif; ?>

    <!-- Submission Form -->
    <div class="edpq-sc-card">
        <h3>
            Photo Submission Form
            <span class="edpq-tag">Frontend</span>
        </h3>
        <p class="edpq-sc-desc">
            Displays a form that lets visitors upload a photo, headline, and caption.
            Paste this into any page where you want users to submit content.
        </p>
        <div class="edpq-sc-row">
            <code class="edpq-sc-code" id="edpq-sc-1">[EmDailyPostsQueueForm]</code>
            <button class="edpq-copy-btn" data-target="edpq-sc-1">Copy</button>
        </div>
        <p class="edpq-sc-hint">
            Optional: <code>[EmDailyPostsQueueForm class="my-custom-class"]</code> — adds a CSS class to the wrapper.
        </p>
    </div>

    <!-- Daily Post Display -->
    <div class="edpq-sc-card">
        <h3>
            Daily Post Display
            <span class="edpq-tag">Frontend</span>
        </h3>
        <p class="edpq-sc-desc">
            Shows the first item currently in the queue — featured image, headline, and caption.
            Place this anywhere you want the "post of the day" to appear.
        </p>
        <div class="edpq-sc-row">
            <code class="edpq-sc-code" id="edpq-sc-2">[EmDailyPostsQueueDisplayPost]</code>
            <button class="edpq-copy-btn" data-target="edpq-sc-2">Copy</button>
        </div>
        <p class="edpq-sc-hint">
            Optional: <code>[EmDailyPostsQueueDisplayPost class="my-custom-class"]</code> — adds a CSS class to the wrapper.
        </p>
    </div>

    <?php if ( $is_page ) : ?>
    <p class="edpq-sc-hint" style="margin-top:.25rem;">
        💡 <strong>How to use:</strong> Go to
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>">Pages</a>,
        open or create a page, add a <em>Shortcode block</em>, and paste the shortcode above.
    </p>
    <?php endif; ?>
</div>

<script>
(function () {
    document.querySelectorAll('.edpq-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.target);
            if (!target) return;
            navigator.clipboard.writeText(target.textContent.trim()).then(function () {
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(function () {
                    btn.textContent = 'Copy';
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(function () {
                target.select ? target.select() : window.getSelection().selectAllChildren(target);
            });
        });
    });
}());
</script>
