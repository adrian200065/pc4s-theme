<?php
/**
 * The template for displaying archive pages
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();


?>

<div id="primary" class="archive-page">
    <div class="wrapper">
        <div class="archive-layout">
            <div class="archive-content">
                <header class="page-header">
                    <?php
                    the_archive_title('<h1 class="page-title">', '</h1>');
                    the_archive_description('<div class="archive-description">', '</div>');
                    ?>
                </header><!-- .page-header -->

                <?php if (have_posts()) : ?>
                    <div class="posts-grid">
                        <?php while (have_posts()) : the_post(); ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="post-card__thumbnail">
                                        <a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                                            <?php the_post_thumbnail('medium', ['class' => 'post-card__image']); ?>
                                        </a>
                                    </div><!-- .post-card__thumbnail -->
                                <?php endif; ?>

                                <div class="post-card__content">
                                    <header class="post-card__header">
                                        <?php the_title(sprintf('<h2 class="post-card__title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>'); ?>

                                        <div class="post-card__meta">
                                            <?php
                                            pc4s_posted_on();
                                            pc4s_posted_by();
                                            ?>
                                        </div><!-- .post-card__meta -->
                                    </header><!-- .post-card__header -->

                                    <div class="post-card__excerpt">
                                        <?php the_excerpt(); ?>
                                    </div><!-- .post-card__excerpt -->

                                    <footer class="post-card__footer">
                                        <?php pc4s_entry_footer(); ?>
                                    </footer><!-- .post-card__footer -->
                                </div><!-- .post-card__content -->
                            </article><!-- #post-<?php the_ID(); ?> -->
                        <?php endwhile; ?>
                    </div><!-- .posts-grid -->

                    <?php
                    the_posts_navigation([
                        'prev_text' => __('&larr; Older posts', PC4S_TEXTDOMAIN),
                        'next_text' => __('Newer posts &rarr;', PC4S_TEXTDOMAIN),
                    ]);
                    ?>
                <?php else : ?>
                    <div class="no-posts-found">
                        <h2><?php esc_html_e('Nothing here', PC4S_TEXTDOMAIN); ?></h2>
                        <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try a search?', PC4S_TEXTDOMAIN); ?></p>
                        <?php get_search_form(); ?>
                    </div><!-- .no-posts-found -->
                <?php endif; ?>

                <?php
                /**
                 * Fires on news / blog archive pages to render the newsletter subscription form.
                 * Custom_Forms::render_news_newsletter() is hooked here and guards for is_home().
                 */
                do_action( 'pc4s_news_newsletter' );
                ?>

            </div><!-- .archive-content -->

            <?php get_sidebar(); ?>
        </div><!-- .archive-layout -->
    </div><!-- /.wrapper -->
</div><!-- #primary -->

<?php
get_footer();
