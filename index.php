<?php
/**
 * The main template file
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header(); ?>

<div class="site-main">
    <div class="wrapper">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <h1 class="entry-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h1>
                    </header>

                    <div class="entry-content">
                        <?php the_excerpt(); ?>
                    </div><!-- /.entry-content -->

                    <footer class="entry-footer">
                        <p>Posted on <?php the_date(); ?> by <?php the_author(); ?></p>
                    </footer>
                </article>
            <?php endwhile; ?>

            <?php the_posts_navigation(); ?>
        <?php else : ?>
            <p><?php _e('Sorry, no posts matched your criteria.', 'pc4s'); ?></p>
        <?php endif; ?>
    </div><!-- /.wrapper -->
</div><!-- /.site-main -->

<?php get_footer(); ?>
