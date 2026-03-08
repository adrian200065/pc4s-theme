<?php
/**
 * Template for displaying the pc4s_event post-type archive (Events page).
 *
 * URL: /events/  (CPT archive — requires no WordPress page with slug "events")
 *
 * Sections rendered:
 *   1. Page banner  — title + breadcrumbs via parts/content/page-banner
 *   2. Featured Event — spotlight on the single next upcoming event
 *   3. Events Listing — all upcoming events grouped by calendar month
 *   4. Page CTA       — "Want to Host an Event?" with contact / our-work links
 *
 * Query is pre-filtered via Event::filter_archive_query() (pre_get_posts hook).
 * All heavy logic lives in PC4S\Classes\EventQuery — this template only renders.
 *
 * NOTE: If a WordPress Page with the slug "events" exists it will conflict with
 * this CPT archive. Delete that page or reassign its slug, then visit
 * Settings → Permalinks → Save to regenerate rewrite rules.
 *
 * @package PC4S
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Classes\EventQuery;

get_header();

// Collect all posts from the pre-filtered main query, then expand recurring
// events into individual dated occurrences. No extra DB queries are needed —
// expand_occurrences() reads ACF field values (already in the object cache).
global $wp_query;
$raw_posts       = is_array( $wp_query->posts ) ? $wp_query->posts : [];
$all_occurrences = EventQuery::expand_occurrences( $raw_posts );
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<?php if ( empty( $all_occurrences ) ) : ?>

	<!-- ── No Events ────────────────────────────────────────────────────── -->
	<section class="section events-none">
		<div class="wrapper">
			<p class="events-none__message">
				<?php esc_html_e( 'No upcoming events at this time. Please check back soon.', 'pc4s' ); ?>
			</p>
		</div>
	</section>

<?php else : ?>

	<?php
	// Featured event = the earliest upcoming occurrence.
	$featured_occ    = $all_occurrences[0];
	$featured        = $featured_occ['post'];
	$feat_date       = $featured_occ['date']; // specific occurrence date (Y-m-d)
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['post'] = $featured;
	setup_postdata( $featured );

	$feat_ts        = $feat_date ? strtotime( $feat_date ) : 0;
	$feat_start     = (string) get_field( 'event_start_time' );
	$feat_end       = (string) get_field( 'event_end_time' );
	$feat_location  = (string) get_field( 'event_location' );
	$feat_cta_url   = (string) ( get_field( 'event_cta_url' ) ?: get_permalink() );
	$feat_cta_text  = (string) ( get_field( 'event_cta_text' ) ?: __( 'Find Out More', 'pc4s' ) );
	$feat_details   = (string) get_field( 'event_details' );
	$feat_excerpt   = $feat_details
		? wp_trim_words( wp_strip_all_tags( $feat_details ), 30, '&hellip;' )
		: '';
	$feat_title     = get_the_title();
	$feat_permalink = get_permalink();

	// Pre-build derived datetime attributes.
	$feat_dt_start = ( $feat_date && $feat_start ) ? $feat_date . 'T' . $feat_start : '';
	$feat_dt_end   = ( $feat_date && $feat_end )   ? $feat_date . 'T' . $feat_end   : '';
	?>

	<!-- ── Featured Event ───────────────────────────────────────────────── -->
	<section class="section featured-event" aria-labelledby="featured-heading">
		<div class="wrapper">
			<div class="featured-event__inner">

				<?php if ( $feat_ts ) : ?>
				<div class="featured-event__date-block" aria-hidden="true">
					<span class="featured-event__month"><?php echo esc_html( strtoupper( gmdate( 'M', $feat_ts ) ) ); ?></span>
					<span class="featured-event__day"><?php echo esc_html( gmdate( 'j', $feat_ts ) ); ?></span>
					<span class="featured-event__year"><?php echo esc_html( gmdate( 'Y', $feat_ts ) ); ?></span>
				</div>
				<?php endif; ?>

				<div class="featured-event__content">

					<p class="featured-event__label"><?php esc_html_e( 'Featured Event', 'pc4s' ); ?></p>

					<h2 id="featured-heading" class="featured-event__title">
						<a href="<?php echo esc_url( $feat_permalink ); ?>"><?php echo esc_html( $feat_title ); ?></a>
					</h2>

					<ul
						class="featured-event__meta"
						aria-label="<?php esc_attr_e( 'Event details', 'pc4s' ); ?>"
					>

						<?php if ( $feat_start ) : ?>
						<li class="featured-event__meta-item">
							<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10"></circle>
								<polyline points="12 6 12 12 16 14"></polyline>
							</svg>
							<span>
								<time <?php echo $feat_dt_start ? 'datetime="' . esc_attr( $feat_dt_start ) . '"' : ''; ?>>
									<?php echo esc_html( gmdate( 'g:i A', strtotime( $feat_start ) ) ); ?>
								</time>
								<?php if ( $feat_end ) : ?>
								<span aria-hidden="true">&ndash;</span>
								<time <?php echo $feat_dt_end ? 'datetime="' . esc_attr( $feat_dt_end ) . '"' : ''; ?>>
									<?php echo esc_html( gmdate( 'g:i A', strtotime( $feat_end ) ) ); ?>
								</time>
								<?php endif; ?>
							</span>
						</li>
						<?php endif; ?>

						<?php if ( $feat_location ) : ?>
						<li class="featured-event__meta-item">
							<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
								<circle cx="12" cy="10" r="3"></circle>
							</svg>
							<span><?php echo esc_html( $feat_location ); ?></span>
						</li>
						<?php endif; ?>

					</ul>

					<?php if ( $feat_excerpt ) : ?>
					<p class="featured-event__desc"><?php echo esc_html( $feat_excerpt ); ?></p>
					<?php endif; ?>

					<a
						href="<?php echo esc_url( $feat_cta_url ); ?>"
						class="btn btn--primary"
						aria-label="<?php echo esc_attr( sprintf( __( 'Find out more about %s', 'pc4s' ), $feat_title ) ); ?>"
					>
						<?php echo esc_html( $feat_cta_text ); ?>
					</a>

				</div><!-- .featured-event__content -->

			</div><!-- .featured-event__inner -->
		</div><!-- .wrapper -->
	</section><!-- .featured-event -->

	<?php wp_reset_postdata(); ?>

	<!-- ── Events Listing ───────────────────────────────────────────────── -->
	<?php $grouped = EventQuery::group_by_month( $all_occurrences ); ?>

	<?php if ( ! empty( $grouped ) ) : ?>
	<section class="section events-listing" aria-labelledby="all-events-heading">
		<div class="wrapper">

			<div class="section__header">
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php esc_html_e( 'Community Calendar', 'pc4s' ); ?></span>
				</div>
				<h2 id="all-events-heading" class="section__title">
					<?php esc_html_e( 'Upcoming Events', 'pc4s' ); ?>
				</h2>
				<p class="section__subtitle">
					<?php esc_html_e( 'Stay connected with what\'s happening across Rutherford County. All are welcome.', 'pc4s' ); ?>
				</p>
			</div>

			<div class="month-tabs" data-month-tabs>

				<!-- ── Tab navigation ──────────────────────────────────────────── -->
				<div class="month-tabs__nav-wrapper">
					<ul
						class="month-tabs__list"
						role="tablist"
						aria-label="<?php esc_attr_e( 'Events by month', 'pc4s' ); ?>"
					>
						<?php $tab_idx = 0; foreach ( $grouped as $ym => $group ) : ?>
						<li class="month-tabs__item" role="presentation">
							<button
								id="tab-<?php echo esc_attr( $ym ); ?>"
								class="month-tabs__tab"
								role="tab"
								aria-selected="<?php echo 0 === $tab_idx ? 'true' : 'false'; ?>"
								aria-controls="panel-<?php echo esc_attr( $ym ); ?>"
								tabindex="<?php echo 0 === $tab_idx ? '0' : '-1'; ?>"
							>
								<time datetime="<?php echo esc_attr( $group['datetime'] ); ?>">
									<?php echo esc_html( $group['label'] ); ?>
								</time>
							</button>
						</li>
						<?php $tab_idx++; endforeach; ?>
					</ul>
				</div><!-- .month-tabs__nav-wrapper -->

				<!-- ── Tab panels ──────────────────────────────────────────────── -->
				<?php $panel_idx = 0; foreach ( $grouped as $ym => $group ) : ?>
				<div
					id="panel-<?php echo esc_attr( $ym ); ?>"
					class="month-tabs__panel"
					role="tabpanel"
					aria-labelledby="tab-<?php echo esc_attr( $ym ); ?>"
					tabindex="0"
					<?php if ( $panel_idx > 0 ) echo 'hidden'; ?>
				>
					<ol
						class="events-month-list"
						aria-label="<?php echo esc_attr( $group['label'] . ' ' . __( 'events', 'pc4s' ) ); ?>"
					>
						<?php foreach ( $group['occurrences'] as $occ ) : ?>
							<?php
							// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							$GLOBALS['post'] = $occ['post'];
							setup_postdata( $occ['post'] );
							?>
							<?php get_template_part( 'parts/content/event-row', null, [ 'occurrence_date' => $occ['date'] ] ); ?>
						<?php endforeach; ?>
					</ol>

				</div><!-- .month-tabs__panel -->
				<?php $panel_idx++; endforeach; ?>

			</div><!-- .month-tabs -->

		</div><!-- .wrapper -->
	</section><!-- .events-listing -->

	<?php wp_reset_postdata(); ?>
	<?php endif; // grouped ?>

<?php endif; // all_events ?>

	<!-- ── Page CTA ─────────────────────────────────────────────────────── -->
	<section class="section page-cta" aria-labelledby="events-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php esc_html_e( 'Get Involved', 'pc4s' ); ?></span>
				</div>

				<h2 id="events-cta-heading" class="section__title">
					<?php esc_html_e( 'Want to Host an Event?', 'pc4s' ); ?>
				</h2>

				<p class="section__subtitle">
					<?php esc_html_e( 'Partner with PC4S to bring prevention education and community support to your neighborhood, school, or organization.', 'pc4s' ); ?>
				</p>

				<div class="page-cta__actions">
					<a
						href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"
						class="btn btn--primary"
					>
						<?php esc_html_e( 'Contact Us', 'pc4s' ); ?>
					</a>
					<a
						href="<?php echo esc_url( home_url( '/our-work/' ) ); ?>"
						class="btn btn--outline"
					>
						<?php esc_html_e( 'Our Work', 'pc4s' ); ?>
					</a>
				</div>

			</div>
		</div>
	</section><!-- .page-cta -->


<?php get_footer(); ?>
