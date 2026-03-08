<?php
/**
 * The template for displaying search results
 *
 * Sections rendered (in order):
 *   1. Page Banner  — "Search Results" heading + breadcrumbs
 *   2. Search Form  — persistent form above the results
 *   3. Results Loop — content-search.php for each post
 *   4. Pagination   — numeric posts pagination
 *   5. Empty State  — shown when no results are found
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'parts/content/page-banner' );
?>

<main id="main-content" class="site-main" role="main">
	<section class="section search-page" aria-label="<?php esc_attr_e( 'Search results', 'pc4s' ); ?>">
		<div class="wrapper">

			<?php /* Persistent search form — lets the user refine their query */ ?>
			<div class="search-page__form-wrap">
				<?php get_search_form(); ?>
			</div><!-- .search-page__form-wrap -->

			<?php if ( have_posts() ) : ?>

				<?php /* Results count + keyword summary */ ?>
				<p class="search-page__summary">
					<?php
					printf(
						/* translators: 1: result count, 2: search query */
						esc_html( _n(
							'%1$s result found for &ldquo;%2$s&rdquo;',
							'%1$s results found for &ldquo;%2$s&rdquo;',
							(int) $wp_query->found_posts,
							'pc4s'
						) ),
						'<strong>' . number_format_i18n( (int) $wp_query->found_posts ) . '</strong>',
						'<em>' . esc_html( get_search_query() ) . '</em>'
					);
					?>
				</p><!-- .search-page__summary -->

				<ol class="search-page__results" role="list">
					<?php while ( have_posts() ) : the_post(); ?>
						<li class="search-page__results-item">
							<?php get_template_part( 'parts/content/content', 'search' ); ?>
						</li>
					<?php endwhile; ?>
				</ol><!-- .search-page__results -->

				<?php
				the_posts_pagination( [
					'mid_size'           => 2,
					'prev_text'          => sprintf(
						'<span aria-hidden="true">&larr;</span> <span class="nav-prev-text">%s</span>',
						esc_html__( 'Previous', 'pc4s' )
					),
					'next_text'          => sprintf(
						'<span class="nav-next-text">%s</span> <span aria-hidden="true">&rarr;</span>',
						esc_html__( 'Next', 'pc4s' )
					),
					'screen_reader_text' => esc_html__( 'Search results navigation', 'pc4s' ),
					'class'              => 'search-page__pagination',
				] );
				?>

			<?php else : ?>

				<?php /* Empty state — no results found */ ?>
				<div class="search-page__empty" role="status">
					<div class="search-page__empty-icon" aria-hidden="true">
						<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
							<circle cx="22" cy="22" r="14" />
							<line x1="32" y1="32" x2="44" y2="44" />
							<line x1="17" y1="22" x2="27" y2="22" />
							<line x1="22" y1="17" x2="22" y2="27" />
						</svg>
					</div>
					<h2 class="search-page__empty-title">
						<?php esc_html_e( 'No results found', 'pc4s' ); ?>
					</h2>
					<p class="search-page__empty-text">
						<?php
						printf(
							/* translators: %s: search query */
							esc_html__( 'Sorry, nothing matched &ldquo;%s&rdquo;. Try a different search term or browse the site below.', 'pc4s' ),
							esc_html( get_search_query() )
						);
						?>
					</p>
					<div class="search-page__empty-actions">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
							<?php esc_html_e( 'Back to Home', 'pc4s' ); ?>
						</a>
					</div>
				</div><!-- .search-page__empty -->

			<?php endif; ?>

		</div><!-- .wrapper -->
	</section>
</main><!-- #main-content -->

<?php
get_footer();
