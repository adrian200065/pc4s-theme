<?php
/**
 * Template part for displaying posts
 *
 * @package PC4S
 * @subpackage Template_Parts/Content
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="entry-title">', '</h1>');
        else :
            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;

        if ('post' === get_post_type()) :
        ?>
            <div class="entry-meta">
                <?php
                pc4s_posted_on();
                pc4s_posted_by();
                ?>
            </div><!-- /.entry-meta -->
        <?php endif; ?>
    </header>

    <?php if (has_post_thumbnail() && !is_singular()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('large'); ?>
            </a>
        </div><!-- /.post-thumbnail -->
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if (is_singular()) :
            the_content();
        else :
            the_excerpt();
        endif;
        ?>
    </div><!-- /.entry-content -->

    <footer class="entry-footer">
        <?php pc4s_entry_footer(); ?>
    </footer>
</article>
