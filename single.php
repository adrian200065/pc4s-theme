<?php
/**
 * The template for displaying all single posts.
 *
 * Sections rendered (in order):
 *   1. Page Banner      — title (h1) + breadcrumbs via parts/content/page-banner
 *   2. Post article     — featured image, date + category meta, content, pagination
 *   3. Post navigation  — previous / next post links
 *   4. Comments         — loaded only when comments are open or exist
 *
 * The page banner reads from the ACF group_page_banner field group,
 * which is already assigned to all post types. The post title is the
 * automatic fallback when no ACF title override is set.
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// The banner template part reads from the current post context (already
// set by the main WP loop — no manual setup_postdata() call needed here).
get_template_part( 'parts/content/page-banner' );
?>
<main id="main-content" class="site-main" role="main">
	<section class="site-main section single-post">
		<div class="wrapper">

			<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>

				<?php if ( has_post_thumbnail() ) : ?>
				<div class="entry__featured-image">
					<?php
					the_post_thumbnail(
						'large',
						[
							'class'   	=> 'entry__img',
							'loading' 	=> 'eager',
							'decoding' 	=> 'async',
						]
					);
					?>
				</div><!-- .entry__featured-image -->
				<?php endif; ?>

				<header class="entry__header">
					<div class="entry__meta">
						<time class="entry__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date() ); ?>
						</time>
						<?php
						$post_cats = get_the_category();
						if ( $post_cats ) :
							$first_cat = $post_cats[0];
						?>
						<span class="entry__meta-sep" aria-hidden="true">&middot;</span>
						<a
							href="<?php echo esc_url( get_category_link( $first_cat->term_id ) ); ?>"
							class="entry__category news-tag news-tag--<?php echo esc_attr( sanitize_html_class( $first_cat->slug ) ); ?>"
							rel="category tag"
						>
							<?php echo esc_html( $first_cat->name ); ?>
						</a>
						<?php endif; ?>
					</div><!-- .entry__meta -->
				</header><!-- .entry__header -->

				<div class="entry__content">
					<?php
					the_content(
						sprintf(
							wp_kses(
								/* translators: %s: post title, screen-reader only */
								__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'pc4s' ),
								[ 'span' => [ 'class' => [] ] ]
							),
							wp_kses_post( get_the_title() )
						)
					);

					wp_link_pages(
						[
							'before' => '<nav class="entry__page-links" aria-label="' . esc_attr__( 'Post pages', 'pc4s' ) . '">'
								. '<span class="entry__page-links-label">' . esc_html__( 'Pages:', 'pc4s' ) . '</span>',
							'after'  => '</nav>',
						]
					);
					?>
				</div><!-- .entry__content -->

				<footer class="entry__footer">
					<?php if ( function_exists( 'pc4s_entry_footer' ) ) : ?>
						<?php pc4s_entry_footer(); ?>
					<?php endif; ?>
				</footer><!-- .entry__footer -->

			</article><!-- #post-<?php the_ID(); ?> -->

			<nav
				class="post-navigation"
				aria-label="<?php esc_attr_e( 'Post navigation', 'pc4s' ); ?>"
			>
				<?php
				the_post_navigation(
					[
						'prev_text' => '<span class="post-navigation__label">'
							. esc_html__( 'Previous', 'pc4s' )
							. '</span> <span class="post-navigation__title">%title</span>',
						'next_text' => '<span class="post-navigation__label">'
							. esc_html__( 'Next', 'pc4s' )
							. '</span> <span class="post-navigation__title">%title</span>',
					]
				);
				?>
			</nav><!-- .post-navigation -->

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>

			<?php endwhile; // End of the loop. ?>

		</div><!-- .wrapper -->
	</section><!-- .site-main -->
</main>
<?php
get_footer();
