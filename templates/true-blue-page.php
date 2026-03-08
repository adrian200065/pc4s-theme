<?php
/**
 * Template Name: True Blue Peers 4 Success
 * Template Post Type: page
 *
 * Page template for the True Blue Peers 4 Success program.
 *
 * Sections rendered (in order):
 *   1. Page Banner  — parts/content/page-banner.php
 *   2. Hero         — program intro with accent card (ACF)
 *   3. Mission      — goal & mission card grid (ACF)
 *   4. Events       — upcoming True Blue meetings (EventQuery::expand_occurrences → inline tbp-events__card markup)
 *   5. Funding      — funding acknowledgment bar (ACF)
 *   6. Page CTA     — bottom call-to-action (ACF)
 *
 * ACF field group: group_true_blue_page (acf-json/group_true_blue_page.json)
 * Event taxonomy slug: true-blue
 *
 * @package PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Classes\EventQuery;

// ---------------------------------------------------------------------------
// Resolve all ACF fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Hero ---
$hero_badge_text     = (string) get_field( 'tbp_hero_badge_text' );
$hero_title          = (string) get_field( 'tbp_hero_title' );
$hero_title_span     = (string) get_field( 'tbp_hero_title_span' );
$hero_lead           = (string) get_field( 'tbp_hero_lead' );
$hero_cta_primary    = get_field( 'tbp_hero_cta_primary' );   // array: url, title, target
$hero_cta_outline    = get_field( 'tbp_hero_cta_outline' );   // array: url, title, target
$accent_eyebrow      = (string) get_field( 'tbp_hero_accent_eyebrow' );
$accent_heading      = (string) get_field( 'tbp_hero_accent_heading' );
$accent_desc         = (string) get_field( 'tbp_hero_accent_desc' );
$accent_details      = (array)  get_field( 'tbp_hero_accent_details' );

// --- Mission ---
$mission_badge    = (string) get_field( 'tbp_mission_badge_text' );
$mission_title    = (string) get_field( 'tbp_mission_title' );
$mission_subtitle = (string) get_field( 'tbp_mission_subtitle' );
$mission_cards    = (array)  get_field( 'tbp_mission_cards' );

// --- Events ---
$events_badge         = (string) get_field( 'tbp_events_badge_text' );
$events_title         = (string) get_field( 'tbp_events_title' );
$events_subtitle      = (string) get_field( 'tbp_events_subtitle' );
$events_count         = absint( get_field( 'tbp_events_count' ) ) ?: 10;
$events_view_all_link = get_field( 'tbp_events_view_all_url' ); // array: url, title, target

// --- Funding ---
$funding_logo = get_field( 'tbp_funding_logo' );   // array: url, alt, ...
$funding_text = (string) get_field( 'tbp_funding_text' );

// --- CTA ---
$cta_title        = (string) get_field( 'tbp_cta_title' );
$cta_text         = (string) get_field( 'tbp_cta_text' );
$cta_primary_link = get_field( 'tbp_cta_primary' );  // array: url, title, target
$cta_outline_link = get_field( 'tbp_cta_outline' );  // array: url, title, target

// ---------------------------------------------------------------------------
// Events query — filtered to the 'true-blue' event_type taxonomy term.
//
// Strategy: fetch all series posts for this taxonomy (cap at 50 — there are
// never 50 distinct recurring-event series), then expand every recurring post
// into its individual future occurrences via EventQuery::expand_occurrences().
// This correctly handles a single biweekly series producing 5+ dated cards.
// Results are capped at $events_count, then grouped by calendar month.
// ---------------------------------------------------------------------------
$events_by_month  = [];
$evt_fields_cache = [];

if ( $events_title ) {
	$series_query = EventQuery::get_upcoming( 50, [ 'true-blue' ] );

	if ( $series_query->have_posts() ) {
		$series_posts = $series_query->posts;
		wp_reset_postdata();

		// Expand each series post into individual dated occurrences (6-month window).
		$all_occurrences = EventQuery::expand_occurrences( $series_posts, 6 );

		// Honour the configured display limit.
		$all_occurrences = array_slice( $all_occurrences, 0, $events_count );

		// Pre-load per-post ACF fields once per unique post to avoid redundant
		// DB reads when the same series appears on multiple occurrence dates.
		foreach ( $all_occurrences as $occ ) {
			$pid = $occ['post']->ID;
			if ( isset( $evt_fields_cache[ $pid ] ) ) {
				continue;
			}
			$details_raw             = (string) get_field( 'event_details', $pid );
			$evt_fields_cache[ $pid ] = [
				'title'      => get_the_title( $pid ),
				'permalink'  => (string) get_permalink( $pid ),
				'start_time' => (string) get_field( 'event_start_time', $pid ), // H:i
				'location'   => (string) get_field( 'event_location',   $pid ),
				'excerpt'    => $details_raw
					? wp_trim_words( wp_strip_all_tags( $details_raw ), 20, '&hellip;' )
					: (string) get_the_excerpt( $pid ),
				'cta_url'    => (string) ( get_field( 'event_cta_url',  $pid ) ?: get_permalink( $pid ) ),
				'cta_text'   => (string) ( get_field( 'event_cta_text', $pid ) ?: __( 'Learn More', 'pc4s' ) ),
			];
		}

		// Group occurrences by calendar month using the built-in helper.
		$events_by_month = EventQuery::group_by_month( $all_occurrences );
	}
}

// ---------------------------------------------------------------------------
// Icon helper — returns a safe, hardcoded inline SVG by icon key.
// SVGs are static strings (no user data) — safe to echo without escaping.
// ---------------------------------------------------------------------------
$tbp_icon = static function ( string $type ): string {
	$icons = [
		'calendar' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
		'time'     => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
		'location' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
		'people'   => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
	];
	return $icons[ $type ] ?? '';
};

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main-content">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION: Hero / Program Intro
	   ================================================================ */ ?>
	<?php if ( $hero_title ) : ?>
	<section class="section tbp-hero" aria-labelledby="tbp-hero-heading">
		<div class="wrapper tbp-hero__inner">

			<!-- Copy -->
			<div class="tbp-hero__copy">

				<?php if ( $hero_badge_text ) : ?>
					<span class="tbp-hero__badge"><?php echo esc_html( $hero_badge_text ); ?></span>
				<?php endif; ?>

				<h2 id="tbp-hero-heading" class="tbp-hero__title">
					<?php echo esc_html( $hero_title ); ?>
					<?php if ( $hero_title_span ) : ?>
						<span><?php echo esc_html( $hero_title_span ); ?></span>
					<?php endif; ?>
				</h2>

				<?php if ( $hero_lead ) : ?>
					<p class="tbp-hero__lead"><?php echo esc_html( $hero_lead ); ?></p>
				<?php endif; ?>

				<?php if ( $hero_cta_primary || $hero_cta_outline ) : ?>
				<div class="tbp-hero__cta">

					<?php if ( ! empty( $hero_cta_primary['url'] ) ) : ?>
						<a
							href="<?php echo esc_url( $hero_cta_primary['url'] ); ?>"
							class="btn btn--primary"
							<?php if ( ! empty( $hero_cta_primary['target'] ) ) : ?>
								target="<?php echo esc_attr( $hero_cta_primary['target'] ); ?>"
								rel="noopener noreferrer"
							<?php endif; ?>
						>
							<?php echo esc_html( $hero_cta_primary['title'] ); ?>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $hero_cta_outline['url'] ) ) : ?>
						<a
							href="<?php echo esc_url( $hero_cta_outline['url'] ); ?>"
							class="btn btn--outline"
							<?php if ( ! empty( $hero_cta_outline['target'] ) ) : ?>
								target="<?php echo esc_attr( $hero_cta_outline['target'] ); ?>"
								rel="noopener noreferrer"
							<?php endif; ?>
						>
							<?php echo esc_html( $hero_cta_outline['title'] ); ?>
						</a>
					<?php endif; ?>

				</div><!-- .tbp-hero__cta -->
				<?php endif; ?>

			</div><!-- .tbp-hero__copy -->

			<!-- Accent Card -->
			<?php if ( $accent_heading ) : ?>
			<div class="tbp-hero__accent" aria-label="<?php esc_attr_e( 'Recurring meeting highlights', 'pc4s' ); ?>">

				<?php if ( $accent_eyebrow ) : ?>
					<p class="tbp-hero__accent-eyebrow"><?php echo esc_html( $accent_eyebrow ); ?></p>
				<?php endif; ?>

				<p class="tbp-hero__accent-heading">
					<?php
					// wp_kses_post allows <br> for intentional line breaks entered in the admin.
					echo wp_kses_post( $accent_heading );
					?>
				</p>

				<?php if ( $accent_desc ) : ?>
					<p class="tbp-hero__accent-desc"><?php echo esc_html( $accent_desc ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $accent_details ) ) : ?>
				<ul class="tbp-hero__accent-details" role="list" aria-label="<?php esc_attr_e( 'Meeting details', 'pc4s' ); ?>">
					<?php foreach ( $accent_details as $detail ) :
						$icon_key   = sanitize_key( $detail['icon'] ?? '' );
						$item_text  = sanitize_text_field( $detail['text'] ?? '' );
						if ( ! $item_text ) {
							continue;
						}
					?>
					<li class="tbp-hero__accent-item">
						<?php echo $tbp_icon( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG, no user data ?>
						<span><?php echo esc_html( $item_text ); ?></span>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>

			</div><!-- .tbp-hero__accent -->
			<?php endif; ?>

		</div><!-- .wrapper.tbp-hero__inner -->
	</section><!-- .tbp-hero -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: Goal & Mission
	   ================================================================ */ ?>
	<?php if ( $mission_title ) : ?>
	<section class="section tbp-mission" aria-labelledby="tbp-mission-heading">
		<div class="wrapper">

			<div class="section__header">

				<?php if ( $mission_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $mission_badge ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="tbp-mission-heading" class="section__title">
					<?php echo esc_html( $mission_title ); ?>
				</h2>

				<?php if ( $mission_subtitle ) : ?>
					<p class="section__subtitle"><?php echo esc_html( $mission_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->

			<?php if ( ! empty( $mission_cards ) ) : ?>
			<div class="tbp-mission__grid">
				<?php foreach ( $mission_cards as $card ) :
					$card_tag      = sanitize_text_field( $card['tag']        ?? '' );
					$card_title    = sanitize_text_field( $card['card_title'] ?? '' );
					$card_text     = wp_kses_post( $card['card_text']         ?? '' );
					$card_modifier = sanitize_html_class( $card['modifier']   ?? '' );

					if ( ! $card_title ) {
						continue;
					}

					$card_class = 'tbp-mission__card';
					if ( $card_modifier ) {
						$card_class .= ' tbp-mission__card--' . $card_modifier;
					}
				?>
				<div class="<?php echo esc_attr( $card_class ); ?>">

					<?php if ( $card_tag ) : ?>
						<span class="tbp-mission__card-tag"><?php echo esc_html( $card_tag ); ?></span>
					<?php endif; ?>

					<h3 class="tbp-mission__card-title"><?php echo esc_html( $card_title ); ?></h3>

					<?php if ( $card_text ) : ?>
						<div class="tbp-mission__card-text">
							<?php echo $card_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — already passed through wp_kses_post ?>
						</div>
					<?php endif; ?>

				</div>
				<?php endforeach; ?>
			</div><!-- .tbp-mission__grid -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .tbp-mission -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: Upcoming Events (dynamic — True Blue taxonomy)
	   ================================================================ */ ?>
	<?php if ( $events_title && ! empty( $events_by_month ) ) : ?>
	<section class="section tbp-events" id="tbp-events" aria-labelledby="tbp-events-heading">
		<div class="wrapper">

			<div class="section__header">

				<?php if ( $events_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $events_badge ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="tbp-events-heading" class="section__title">
					<?php echo esc_html( $events_title ); ?>
				</h2>

				<?php if ( $events_subtitle ) : ?>
					<p class="section__subtitle"><?php echo esc_html( $events_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->

<?php foreach ( $events_by_month as $month_group ) :
			$month_label   = $month_group['label'];
			$month_dt_attr = $month_group['datetime'];
		?>
		<div class="tbp-events__month-group">

			<?php if ( $month_label ) : ?>
			<h3 class="tbp-events__month-label">
				<time datetime="<?php echo esc_attr( $month_dt_attr ); ?>">
					<?php echo esc_html( $month_label ); ?>
				</time>
			</h3>
			<?php endif; ?>

			<ol
				class="tbp-events__list"
				role="list"
				<?php if ( $month_label ) : ?>
					aria-label="<?php echo esc_attr( $month_label . ' ' . __( 'meetings', 'pc4s' ) ); ?>"
				<?php endif; ?>
			>
				<?php foreach ( $month_group['occurrences'] as $occ ) :
					$evt_post   = $occ['post'];
					$evt_date   = $occ['date'];               // Y-m-d
					$evt_ts     = $evt_date ? strtotime( $evt_date ) : 0;
					$evt_pid    = $evt_post->ID;
					$evt_f      = $evt_fields_cache[ $evt_pid ] ?? [];

					$evt_title      = $evt_f['title']      ?? get_the_title( $evt_pid );
					$evt_permalink  = $evt_f['permalink']  ?? (string) get_permalink( $evt_pid );
					$evt_start_time = $evt_f['start_time'] ?? '';
					$evt_location   = $evt_f['location']   ?? '';
					$evt_excerpt    = $evt_f['excerpt']    ?? '';
					$evt_cta_url    = $evt_f['cta_url']    ?? $evt_permalink;
					$evt_cta_text   = $evt_f['cta_text']   ?? __( 'Learn More', 'pc4s' );

					// Derived display values.
					$evt_dow     = $evt_ts ? strtoupper( gmdate( 'D', $evt_ts ) ) : '';
					$evt_day     = $evt_ts ? gmdate( 'j', $evt_ts ) : '';
					$evt_dt_attr = '';
					$evt_display_date = '';
					if ( $evt_ts ) {
						$evt_dt_attr      = $evt_start_time
							? gmdate( 'Y-m-d', $evt_ts ) . 'T' . $evt_start_time
							: gmdate( 'Y-m-d', $evt_ts );
						$evt_display_date = $evt_start_time
							? gmdate( 'F j', $evt_ts ) . ' @ ' . gmdate( 'g:i a', strtotime( $evt_start_time ) )
							: gmdate( 'F j, Y', $evt_ts );
					}
					$evt_uid = esc_attr( $evt_pid . '-' . $evt_date );
				?>
				<li>
					<article class="tbp-events__card" aria-labelledby="tbp-evt-<?php echo $evt_uid; // phpcs:ignore ?>">

						<div class="tbp-events__card-header">

							<?php if ( $evt_ts ) : ?>
							<div class="tbp-events__card-date" aria-hidden="true">
								<span class="tbp-events__card-dow"><?php echo esc_html( $evt_dow ); ?></span>
								<span class="tbp-events__card-day"><?php echo esc_html( $evt_day ); ?></span>
							</div>
							<?php endif; ?>

							<div class="tbp-events__card-meta">
								<h4 id="tbp-evt-<?php echo $evt_uid; // phpcs:ignore ?>" class="tbp-events__card-title">
									<?php echo esc_html( $evt_title ); ?>
								</h4>
								<?php if ( $evt_display_date ) : ?>
								<p class="tbp-events__card-time">
									<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
									<time <?php echo $evt_dt_attr ? 'datetime="' . esc_attr( $evt_dt_attr ) . '"' : ''; // phpcs:ignore ?>><?php echo esc_html( $evt_display_date ); ?></time>
								</p>
								<?php endif; ?>
							</div><!-- .tbp-events__card-meta -->

						</div><!-- .tbp-events__card-header -->

						<div class="tbp-events__card-body">

							<?php if ( $evt_location ) : ?>
							<p class="tbp-events__card-location">
								<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
								<span><?php echo esc_html( $evt_location ); ?></span>
							</p>
							<?php endif; ?>

							<?php if ( $evt_excerpt ) : ?>
							<p class="tbp-events__card-desc"><?php echo esc_html( $evt_excerpt ); ?></p>
							<?php endif; ?>

							<a href="<?php echo esc_url( $evt_cta_url ); ?>" class="tbp-events__card-link">
								<?php echo esc_html( $evt_cta_text ); ?>
								<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
							</a>

						</div><!-- .tbp-events__card-body -->

					</article>
				</li>
				<?php endforeach; ?>
				</ol>

			</div><!-- .tbp-events__month-group -->
			<?php endforeach; ?>

			<?php if ( ! empty( $events_view_all_link['url'] ) ) : ?>
			<div class="tbp-events__cta">
				<a
					href="<?php echo esc_url( $events_view_all_link['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $events_view_all_link['target'] ) ) : ?>
						target="<?php echo esc_attr( $events_view_all_link['target'] ); ?>"
						rel="noopener noreferrer"
					<?php endif; ?>
				>
					<?php echo esc_html( $events_view_all_link['title'] ); ?>
				</a>
			</div>
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .tbp-events -->
	<?php endif; ?>


	<?php /* ================================================================
	   BLOCK: Funding Acknowledgment
	   ================================================================ */ ?>
	<?php if ( $funding_logo || $funding_text ) : ?>
	<div class="tbp-funding">
		<div class="wrapper tbp-funding__inner">

			<?php if ( ! empty( $funding_logo['url'] ) ) : ?>
			<div class="tbp-funding__logo-wrap">
				<img
					src="<?php echo esc_url( $funding_logo['url'] ); ?>"
					alt="<?php echo esc_attr( $funding_logo['alt'] ?? '' ); ?>"
					class="tbp-funding__logo"
					loading="lazy"
					decoding="async"
				/>
			</div>
			<div class="tbp-funding__divider" aria-hidden="true"></div>
			<?php endif; ?>

			<?php if ( $funding_text ) : ?>
				<p class="tbp-funding__text"><?php echo esc_html( $funding_text ); ?></p>
			<?php endif; ?>

		</div><!-- .wrapper.tbp-funding__inner -->
	</div><!-- .tbp-funding -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: Page CTA
	   ================================================================ */ ?>
	<?php if ( $cta_title ) : ?>
	<section class="section tbp-cta page-cta" aria-labelledby="tbp-cta-heading">
		<div class="wrapper">
			<div class="text-center">

				<div class="page-cta__copy">
					<h2 id="tbp-cta-heading" class="page-cta__title">
						<?php echo esc_html( $cta_title ); ?>
					</h2>
					<?php if ( $cta_text ) : ?>
						<p class="page-cta__text"><?php echo esc_html( $cta_text ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $cta_primary_link || $cta_outline_link ) : ?>
				<div class="page-cta__actions">

					<?php if ( ! empty( $cta_primary_link['url'] ) ) : ?>
						<a
							href="<?php echo esc_url( $cta_primary_link['url'] ); ?>"
							class="btn btn--primary"
							<?php if ( ! empty( $cta_primary_link['target'] ) ) : ?>
								target="<?php echo esc_attr( $cta_primary_link['target'] ); ?>"
								rel="noopener noreferrer"
							<?php endif; ?>
						>
							<?php echo esc_html( $cta_primary_link['title'] ); ?>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $cta_outline_link['url'] ) ) : ?>
						<a
							href="<?php echo esc_url( $cta_outline_link['url'] ); ?>"
							class="btn btn--outline"
							<?php if ( ! empty( $cta_outline_link['target'] ) ) : ?>
								target="<?php echo esc_attr( $cta_outline_link['target'] ); ?>"
								rel="noopener noreferrer"
							<?php endif; ?>
						>
							<?php echo esc_html( $cta_outline_link['title'] ); ?>
						</a>
					<?php endif; ?>

				</div><!-- .page-cta__actions -->
				<?php endif; ?>

			</div>
		</div><!-- .wrapper -->
	</section><!-- .page-cta -->
	<?php endif; ?>

</main><!-- #main-content -->

<?php
get_footer();
