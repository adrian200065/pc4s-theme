<?php
/**
 * News / Posts Archive Template (home.php)
 *
 * Loaded by WordPress when the site has a static front page and a dedicated
 * Posts Page is assigned in Settings → Reading (is_home() === true).
 *
 * Sections rendered (in order):
 *   1. Page Banner        — parts/content/page-banner.php
 *   2. Featured Story     — hero card built from the most-recent post
 *   3. Recent Updates     — editorial grid (lead card + standard cards)
 *   4. More Stories       — compact archive list for remaining posts
 *   5. Pagination         — standard WP posts navigation
 *   6. Page CTA           — badge, heading, subtitle + two link buttons (ACF)
 *
 * The main WordPress query is never modified — this template only renders it.
 * Posts are ordered date DESC by WP core; no custom WP_Query is needed.
 *
 * ACF field group: group_news_page (acf-json/group_news_page.json)
 *   – Location: Page Type == Posts Page
 *   – Field prefix: np_
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Set the global post context to the Posts Page so that:
//   • page-banner.php can read the ACF title override + breadcrumb toggle
//   • get_the_title() returns the page name rather than a loop post title
// ---------------------------------------------------------------------------
global $wp_query, $post;

$posts_page_id = (int) get_option( 'page_for_posts' );
if ( $posts_page_id ) {
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$post = get_post( $posts_page_id );
	setup_postdata( $post );
}

// ---------------------------------------------------------------------------
// Resolve all ACF section fields once — zero repeated DB calls in markup.
// ---------------------------------------------------------------------------

// --- Featured Story section ---
$np_featured_badge   = (string) ( function_exists( 'get_field' ) ? get_field( 'np_featured_badge'   ) : '' );
$np_featured_heading = (string) ( function_exists( 'get_field' ) ? get_field( 'np_featured_heading' ) : '' );

// --- Recent Updates section ---
$np_recent_badge    = (string) ( function_exists( 'get_field' ) ? get_field( 'np_recent_badge'    ) : '' );
$np_recent_heading  = (string) ( function_exists( 'get_field' ) ? get_field( 'np_recent_heading'  ) : '' );
$np_recent_subtitle = (string) ( function_exists( 'get_field' ) ? get_field( 'np_recent_subtitle' ) : '' );

// --- More Stories section ---
$np_archive_heading = (string) ( function_exists( 'get_field' ) ? get_field( 'np_archive_heading' ) : '' );

// --- Page CTA section ---
$np_cta_badge     = (string) ( function_exists( 'get_field' ) ? get_field( 'np_cta_badge'     ) : '' );
$np_cta_heading   = (string) ( function_exists( 'get_field' ) ? get_field( 'np_cta_heading'   ) : '' );
$np_cta_subtitle  = (string) ( function_exists( 'get_field' ) ? get_field( 'np_cta_subtitle'  ) : '' );
$np_cta_primary   = function_exists( 'get_field' ) ? get_field( 'np_cta_primary'   ) : null;
$np_cta_secondary = function_exists( 'get_field' ) ? get_field( 'np_cta_secondary' ) : null;

// ---------------------------------------------------------------------------
// Collect all posts from the main query — no extra DB hit.
// The query is already sorted by post_date DESC by WordPress core.
// ---------------------------------------------------------------------------
$all_posts = is_array( $wp_query->posts ) ? $wp_query->posts : [];

// Partition posts into display buckets:
//   [0]    → Featured Story hero card
//   [1..3] → Recent Updates editorial grid (max 3)
//   [4+]   → More Stories compact archive list
$featured_post = ! empty( $all_posts ) ? $all_posts[0]      : null;
$recent_posts  = array_slice( $all_posts, 1, 3 );
$archive_posts = count( $all_posts ) > 4 ? array_slice( $all_posts, 4 ) : [];

// ---------------------------------------------------------------------------
// Inline SVG helpers — defined once, reused in loops.
// phpcs:disable WordPress.Security.EscapeOutput
// ---------------------------------------------------------------------------
$arrow_svg = '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$clock_svg = '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
// phpcs:enable WordPress.Security.EscapeOutput

/**
 * Helper: return the first post category's slug as a CSS modifier,
 * e.g. "news-tag--opportunity". Falls back to "news-tag--general".
 *
 * @param  int $post_id
 * @return string
 */
$get_tag_class = static function( int $post_id ): string {
	$cats = get_the_category( $post_id );
	$slug = ! empty( $cats ) ? sanitize_html_class( $cats[0]->slug ) : 'general';
	return 'news-tag news-tag--' . $slug;
};

/**
 * Helper: return the first post category name (or empty string).
 *
 * @param  int $post_id
 * @return string
 */
$get_tag_label = static function( int $post_id ): string {
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0]->name : '';
};

/**
 * Helper: calculate reading time in minutes from post content.
 *
 * @param  int $post_id
 * @return int
 */
$get_read_time = static function( int $post_id ): int {
	$content    = get_post_field( 'post_content', $post_id );
	$word_count = (int) str_word_count( wp_strip_all_tags( $content ) );
	return max( 1, (int) round( $word_count / 200 ) );
};

// Restore global query state before the header (widgets, menus, etc. need it).
wp_reset_postdata();

get_header();
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<div class="site-main news-archive-page">

	<?php if ( empty( $all_posts ) ) : ?>

		<!-- ── Empty state ───────────────────────────────────────────── -->
		<section class="section news-empty">
			<div class="wrapper">
				<p class="news-empty__message">
					<?php esc_html_e( 'No posts found. Check back soon.', 'pc4s' ); ?>
				</p>
			</div>
		</section>

	<?php else : ?>

		<?php /* ============================================================
		   SECTION 1: Featured Story — hero card for the most recent post
		   ============================================================ */ ?>
		<?php if ( $featured_post ) :
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $featured_post;
			setup_postdata( $post );

			$feat_id         = $featured_post->ID;
			$feat_title      = get_the_title( $feat_id );
			$feat_permalink  = get_permalink( $feat_id );
			$feat_date_iso   = get_the_date( 'Y-m-d', $feat_id );
			$feat_date_label = get_the_date( 'F j, Y', $feat_id );
			$feat_excerpt    = has_excerpt( $feat_id )
				? wp_trim_words( get_the_excerpt( $feat_id ), 35, '&hellip;' )
				: wp_trim_words( get_the_content( null, false, $feat_id ), 35, '&hellip;' );
			$feat_tag_class  = $get_tag_class( $feat_id );
			$feat_tag_label  = $get_tag_label( $feat_id );
			$feat_read_time  = $get_read_time( $feat_id );
			$feat_has_thumb  = has_post_thumbnail( $feat_id );

			wp_reset_postdata();
		?>
		<section class="section news-featured" aria-labelledby="news-featured-heading">
			<div class="wrapper">

				<div class="section__header">
					<?php if ( $np_featured_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $np_featured_badge ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $np_featured_heading ) : ?>
					<h2 id="news-featured-heading" class="section__title">
						<?php echo esc_html( $np_featured_heading ); ?>
					</h2>
					<?php endif; ?>
				</div><!-- .section__header -->

				<article
					class="news-hero-card"
					aria-labelledby="hero-article-heading"
				>
					<div class="news-hero-card__media" aria-hidden="true">
						<?php if ( $feat_has_thumb ) : ?>
						<a href="<?php echo esc_url( $feat_permalink ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo get_the_post_thumbnail( $feat_id, 'large', [ 'class' => 'news-hero-card__img', 'loading' => 'eager', 'decoding' => 'async' ] ); ?>
						</a>
						<?php else : ?>
						<div class="news-hero-card__media-inner"></div>
						<?php endif; ?>
						<div class="news-hero-card__accent-strip" aria-hidden="true"></div>
					</div><!-- .news-hero-card__media -->

					<div class="news-hero-card__body">

						<div class="news-hero-card__meta">
							<?php if ( $feat_tag_label ) : ?>
							<span class="<?php echo esc_attr( $feat_tag_class ); ?>">
								<?php echo esc_html( $feat_tag_label ); ?>
							</span>
							<?php endif; ?>
							<time class="news-hero-card__date" datetime="<?php echo esc_attr( $feat_date_iso ); ?>">
								<?php echo esc_html( $feat_date_label ); ?>
							</time>
						</div><!-- .news-hero-card__meta -->

						<h3 id="hero-article-heading" class="news-hero-card__title">
							<?php echo esc_html( $feat_title ); ?>
						</h3>

						<?php if ( $feat_excerpt ) : ?>
						<p class="news-hero-card__excerpt">
							<?php echo esc_html( $feat_excerpt ); ?>
						</p>
						<?php endif; ?>

						<div class="news-hero-card__footer">
							<a
								href="<?php echo esc_url( $feat_permalink ); ?>"
								class="btn btn--primary"
								aria-label="<?php echo esc_attr( sprintf( __( 'Read more about %s', 'pc4s' ), $feat_title ) ); ?>"
							>
								<?php esc_html_e( 'Read More', 'pc4s' ); ?>
								<?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
							<span
								class="news-hero-card__read-time"
								aria-label="<?php echo esc_attr( sprintf( __( 'Estimated reading time: %d minute', 'pc4s' ), $feat_read_time ) ); ?>"
							>
								<?php echo $clock_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: reading time in minutes */
										_n( '%d min read', '%d min read', $feat_read_time, 'pc4s' ),
										$feat_read_time
									)
								);
								?>
							</span>
						</div><!-- .news-hero-card__footer -->

					</div><!-- .news-hero-card__body -->
				</article><!-- .news-hero-card -->

			</div><!-- .wrapper -->
		</section><!-- .news-featured -->
		<?php endif; ?>


		<?php /* ============================================================
		   SECTION 2: Recent Updates — editorial grid (lead + standard cards)
		   Shown only when there are posts beyond the featured one.
		   ============================================================ */ ?>
		<?php if ( ! empty( $recent_posts ) ) : ?>
		<section class="section news-recent" aria-labelledby="recent-heading">
			<div class="wrapper">

				<div class="section__header">
					<?php if ( $np_recent_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $np_recent_badge ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $np_recent_heading ) : ?>
					<h2 id="recent-heading" class="section__title">
						<?php echo esc_html( $np_recent_heading ); ?>
					</h2>
					<?php endif; ?>
					<?php if ( $np_recent_subtitle ) : ?>
					<p class="section__subtitle"><?php echo esc_html( $np_recent_subtitle ); ?></p>
					<?php endif; ?>
				</div><!-- .section__header -->

				<div class="news-grid" role="list" aria-label="<?php esc_attr_e( 'Recent news articles', 'pc4s' ); ?>">

					<?php foreach ( $recent_posts as $index => $recent_post ) :
						// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						$post = $recent_post;
						setup_postdata( $post );

						$rec_id         = $recent_post->ID;
						$rec_title      = get_the_title( $rec_id );
						$rec_permalink  = get_permalink( $rec_id );
						$rec_date_iso   = get_the_date( 'M j, Y', $rec_id );
						$rec_date_attr  = get_the_date( 'Y-m-d', $rec_id );
						$rec_excerpt    = has_excerpt( $rec_id )
							? wp_trim_words( get_the_excerpt( $rec_id ), 25, '&hellip;' )
							: wp_trim_words( get_the_content( null, false, $rec_id ), 25, '&hellip;' );
						$rec_tag_class  = $get_tag_class( $rec_id );
						$rec_tag_label  = $get_tag_label( $rec_id );
						$rec_has_thumb  = has_post_thumbnail( $rec_id );
						$rec_card_class = ( 0 === $index ) ? 'news-card news-card--lead' : 'news-card';
						$rec_heading_id = 'card-recent-' . $rec_id;

						wp_reset_postdata();
					?>
					<article
						class="<?php echo esc_attr( $rec_card_class ); ?>"
						role="listitem"
						aria-labelledby="<?php echo esc_attr( $rec_heading_id ); ?>"
					>
						<div class="news-card__media" aria-hidden="true">
							<?php if ( $rec_has_thumb ) : ?>
							<a href="<?php echo esc_url( $rec_permalink ); ?>" tabindex="-1" aria-hidden="true">
								<?php echo get_the_post_thumbnail( $rec_id, 'medium_large', [ 'class' => 'news-card__img', 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
							</a>
							<?php else : ?>
							<div class="news-card__img-placeholder"></div>
							<?php endif; ?>
						</div><!-- .news-card__media -->

						<div class="news-card__body">
							<div class="news-card__meta">
								<?php if ( $rec_tag_label ) : ?>
								<span class="<?php echo esc_attr( $rec_tag_class ); ?>">
									<?php echo esc_html( $rec_tag_label ); ?>
								</span>
								<?php endif; ?>
								<time class="news-card__date" datetime="<?php echo esc_attr( $rec_date_attr ); ?>">
									<?php echo esc_html( $rec_date_iso ); ?>
								</time>
							</div><!-- .news-card__meta -->

							<h3 id="<?php echo esc_attr( $rec_heading_id ); ?>" class="news-card__title">
								<?php echo esc_html( $rec_title ); ?>
							</h3>

							<?php if ( $rec_excerpt ) : ?>
							<p class="news-card__excerpt"><?php echo esc_html( $rec_excerpt ); ?></p>
							<?php endif; ?>

							<a
								href="<?php echo esc_url( $rec_permalink ); ?>"
								class="news-card__link"
								aria-label="<?php echo esc_attr( sprintf( __( 'Read more about %s', 'pc4s' ), $rec_title ) ); ?>"
							>
								<?php esc_html_e( 'Read Article', 'pc4s' ); ?>
								<?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						</div><!-- .news-card__body -->
					</article><!-- .news-card -->
					<?php endforeach; ?>

				</div><!-- .news-grid -->
			</div><!-- .wrapper -->
		</section><!-- .news-recent -->
		<?php endif; ?>


		<?php /* ============================================================
		   SECTION 3: More Stories — compact archive list for older posts
		   Shown only when there are posts beyond the first 4.
		   ============================================================ */ ?>
		<?php if ( ! empty( $archive_posts ) ) : ?>
		<section class="section news-archive" aria-labelledby="archive-heading">
			<div class="wrapper">

				<div class="news-archive__header">
					<h2 id="archive-heading" class="news-archive__heading">
						<?php echo esc_html( $np_archive_heading ?: __( 'More Stories', 'pc4s' ) ); ?>
					</h2>
					<div class="news-archive__rule" aria-hidden="true"></div>
				</div>

				<ol class="news-archive-list" role="list" aria-label="<?php esc_attr_e( 'Older news articles', 'pc4s' ); ?>">

					<?php foreach ( $archive_posts as $archive_post ) :
						// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						$post = $archive_post;
						setup_postdata( $post );

						$arc_id           = $archive_post->ID;
						$arc_title        = get_the_title( $arc_id );
						$arc_permalink    = get_permalink( $arc_id );
						$arc_date_iso     = get_the_date( 'Y-m-d', $arc_id );
						$arc_date_full    = get_the_date( 'F j, Y', $arc_id );
						$arc_date_month   = get_the_date( 'M', $arc_id );
						$arc_date_day     = get_the_date( 'j', $arc_id );
						$arc_excerpt      = has_excerpt( $arc_id )
							? wp_trim_words( get_the_excerpt( $arc_id ), 20, '&hellip;' )
							: wp_trim_words( get_the_content( null, false, $arc_id ), 20, '&hellip;' );
						$arc_tag_class    = $get_tag_class( $arc_id );
						$arc_tag_label    = $get_tag_label( $arc_id );
						$arc_heading_id   = 'archive-' . $arc_id;

						wp_reset_postdata();
					?>
					<li class="news-archive-item">
						<article aria-labelledby="<?php echo esc_attr( $arc_heading_id ); ?>">

							<div class="news-archive-item__date-block" aria-hidden="true">
								<span class="news-archive-item__month"><?php echo esc_html( $arc_date_month ); ?></span>
								<span class="news-archive-item__day"><?php echo esc_html( $arc_date_day ); ?></span>
							</div>

							<div class="news-archive-item__body">
								<div class="news-archive-item__meta">
									<?php if ( $arc_tag_label ) : ?>
									<span class="<?php echo esc_attr( $arc_tag_class ); ?>">
										<?php echo esc_html( $arc_tag_label ); ?>
									</span>
									<?php endif; ?>
									<time datetime="<?php echo esc_attr( $arc_date_iso ); ?>">
										<?php echo esc_html( $arc_date_full ); ?>
									</time>
								</div><!-- .news-archive-item__meta -->

								<h3 id="<?php echo esc_attr( $arc_heading_id ); ?>" class="news-archive-item__title">
									<a href="<?php echo esc_url( $arc_permalink ); ?>">
										<?php echo esc_html( $arc_title ); ?>
									</a>
								</h3>

								<?php if ( $arc_excerpt ) : ?>
								<p class="news-archive-item__excerpt"><?php echo esc_html( $arc_excerpt ); ?></p>
								<?php endif; ?>
							</div><!-- .news-archive-item__body -->

							<a
								href="<?php echo esc_url( $arc_permalink ); ?>"
								class="news-archive-item__arrow"
								aria-label="<?php echo esc_attr( sprintf( __( 'Read: %s', 'pc4s' ), $arc_title ) ); ?>"
								tabindex="-1"
								aria-hidden="true"
							>
								<?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>

						</article>
					</li><!-- .news-archive-item -->
					<?php endforeach; ?>

				</ol><!-- .news-archive-list -->
			</div><!-- .wrapper -->
		</section><!-- .news-archive -->
		<?php endif; ?>


		<?php /* ============================================================
		   Pagination — only shown when there are multiple pages of posts.
		   Uses the main WordPress query; no custom WP_Query involved.
		   ============================================================ */ ?>
		<?php if ( $wp_query->max_num_pages > 1 ) : ?>
		<nav
			class="posts-pagination"
			aria-label="<?php esc_attr_e( 'Posts navigation', 'pc4s' ); ?>"
		>
			<div class="wrapper">
				<?php
				the_posts_pagination(
					[
						'mid_size'           => 2,
						'prev_text'          => sprintf(
							'<span aria-hidden="true">&larr;</span> <span class="screen-reader-text">%s</span>',
							esc_html__( 'Previous page', 'pc4s' )
						),
						'next_text'          => sprintf(
							'<span class="screen-reader-text">%s</span> <span aria-hidden="true">&rarr;</span>',
							esc_html__( 'Next page', 'pc4s' )
						),
						'screen_reader_text' => __( 'Posts navigation', 'pc4s' ),
					]
				);
				?>
			</div><!-- .wrapper -->
		</nav><!-- .posts-pagination -->
		<?php endif; ?>

	<?php endif; // end $all_posts check ?>


	<?php /* ================================================================
	   SECTION 4: Page CTA — badge, heading, subtitle + two action links
	   ================================================================ */ ?>
	<?php if ( $np_cta_heading || $np_cta_primary || $np_cta_secondary ) : ?>
	<section class="section page-cta" aria-labelledby="news-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $np_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $np_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $np_cta_heading ) : ?>
				<h2 id="news-cta-heading" class="section__title">
					<?php echo esc_html( $np_cta_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $np_cta_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $np_cta_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $np_cta_primary || $np_cta_secondary ) : ?>
				<div class="page-cta__actions">

					<?php if ( $np_cta_primary ) :
						$cta_p_url    = esc_url( $np_cta_primary['url'] ?? '' );
						$cta_p_title  = esc_html( $np_cta_primary['title'] ?? '' );
						$cta_p_target = ! empty( $np_cta_primary['target'] )
							? ' target="' . esc_attr( $np_cta_primary['target'] ) . '" rel="noopener noreferrer"'
							: '';
					?>
					<a href="<?php echo $cta_p_url; ?>" class="btn btn--primary"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( $np_cta_secondary ) :
						$cta_s_url    = esc_url( $np_cta_secondary['url'] ?? '' );
						$cta_s_title  = esc_html( $np_cta_secondary['title'] ?? '' );
						$cta_s_target = ! empty( $np_cta_secondary['target'] )
							? ' target="' . esc_attr( $np_cta_secondary['target'] ) . '" rel="noopener noreferrer"'
							: '';
					?>
					<a href="<?php echo $cta_s_url; ?>" class="btn btn--outline"<?php echo $cta_s_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php echo $cta_s_title; ?>
					</a>
					<?php endif; ?>

				</div><!-- .page-cta__actions -->
				<?php endif; ?>

			</div><!-- .section__header -->
		</div><!-- .wrapper -->
	</section><!-- .page-cta -->
	<?php endif; ?>

</div><!-- .site-main -->

<?php get_footer(); ?>
