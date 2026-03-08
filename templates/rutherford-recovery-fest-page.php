<?php
/**
 * Template Name: Rutherford Recovery Fest
 * Template Post Type: page
 *
 * Page template for the Rutherford Recovery Fest event resource page.
 *
 * Sections rendered (in order):
 *   1. Page Banner      — parts/content/page-banner.php
 *   2. Event Intro      — badge, heading, subtitle, lead, CTAs | event details card
 *   3. Event Highlights — feature grid (icon, label, description) — ACF repeater
 *   4. Documents        — doc cards (Google Drive / Eventbrite links) — ACF repeater
 *   5. Sponsor Tiers    — Bronze / Silver / Gold / Platinum pricing cards — ACF repeater
 *   6. Page CTA         — badge, heading, subtitle, two action links
 *
 * ACF field group : group_rutherford_recovery_fest_page
 *   → acf-json/group_rutherford_recovery_fest_page.json
 *   Fields are prefixed rrf_ (rutherford recovery fest).
 *
 * The Sponsorship Information card links to a Google Drive URL (url ACF field).
 * The Resource Table Sign-Up card links to an Eventbrite URL (url ACF field).
 *
 * Inline SVG icons are rendered from a local map keyed by a select ACF field
 * so the admin never touches SVG code.
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Inline SVG helpers — all icons are hardcoded safe strings; no user input.
// Keyed by select field value so templates stay SVG-free.
// ---------------------------------------------------------------------------

/**
 * Highlight-section icons (rrf_highlight_icon select values).
 */
$rrf_highlight_icons = [
	'music'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
	'resources' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
	'family'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'story'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
	'speaker'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
	'food'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>',
	'default'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
];

/**
 * Event-card detail icons (rrf_detail_icon select values).
 */
$rrf_detail_icons = [
	'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
	'location'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
	'clock'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
	'ticket'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/></svg>',
	'default'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>',
];

// ---------------------------------------------------------------------------
// Doc card SVG icons (keyed by rrf_doc_icon select value).
// ---------------------------------------------------------------------------
$rrf_doc_icons = [
	'document'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
	'calendar'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg>',
	'link'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
	'default'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>',
];

// Arrow SVG reused across doc cards.
$rrf_arrow_svg = '<svg class="cb-doc-card__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>';

// Checkmark SVG reused in sponsor tier perks.
$rrf_check_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';

// ---------------------------------------------------------------------------
// ACF fields — resolved once at the top; zero duplicate DB calls in markup.
// ---------------------------------------------------------------------------

// --- Intro Section ---
$rrf_intro_badge          = (string) get_field( 'rrf_intro_badge' );
$rrf_intro_heading        = (string) get_field( 'rrf_intro_heading' );
$rrf_intro_subtitle       = (string) get_field( 'rrf_intro_subtitle' );
$rrf_intro_lead           = (string) get_field( 'rrf_intro_lead' );
$rrf_intro_cta_primary    = get_field( 'rrf_intro_cta_primary' );   // link array|null
$rrf_intro_cta_secondary  = get_field( 'rrf_intro_cta_secondary' ); // link array|null
$rrf_intro_card_heading   = (string) get_field( 'rrf_intro_card_heading' );
$rrf_intro_card_details   = (array)  get_field( 'rrf_intro_card_details' ) ?: [];
$rrf_intro_card_note      = (string) get_field( 'rrf_intro_card_note' );

// --- Highlights Section ---
$rrf_highlights_badge    = (string) get_field( 'rrf_highlights_badge' );
$rrf_highlights_heading  = (string) get_field( 'rrf_highlights_heading' );
$rrf_highlights_subtitle = (string) get_field( 'rrf_highlights_subtitle' );
$rrf_highlights          = (array)  get_field( 'rrf_highlights' ) ?: [];

// --- Documents Section ---
$rrf_docs_badge    = (string) get_field( 'rrf_docs_badge' );
$rrf_docs_heading  = (string) get_field( 'rrf_docs_heading' );
$rrf_docs_subtitle = (string) get_field( 'rrf_docs_subtitle' );
$rrf_docs_cards    = (array)  get_field( 'rrf_docs_cards' ) ?: [];

// --- Sponsor Section ---
$rrf_sponsor_badge     = (string) get_field( 'rrf_sponsor_badge' );
$rrf_sponsor_heading   = (string) get_field( 'rrf_sponsor_heading' );
$rrf_sponsor_subtitle  = (string) get_field( 'rrf_sponsor_subtitle' );
$rrf_sponsor_tiers     = (array)  get_field( 'rrf_sponsor_tiers' ) ?: [];

// --- CTA Section ---
$rrf_cta_badge     = (string) get_field( 'rrf_cta_badge' );
$rrf_cta_heading   = (string) get_field( 'rrf_cta_heading' );
$rrf_cta_subtitle  = (string) get_field( 'rrf_cta_subtitle' );
$rrf_cta_primary   = get_field( 'rrf_cta_primary' );   // link array|null
$rrf_cta_secondary = get_field( 'rrf_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Template
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main" class="site-main" role="main">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<!-- ====================================================================
	     SECTION 1 — EVENT INTRO
	     Left: mission copy + two CTAs  |  Right: event details card
	     ==================================================================== -->
	<?php if ( $rrf_intro_heading ) : ?>
	<section class="section rrf-intro" aria-labelledby="rrf-intro-heading">
		<div class="wrapper">
			<div class="rrf-intro__inner">

				<!-- Left — copy + CTAs -->
				<div class="rrf-intro__copy">

					<?php if ( $rrf_intro_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $rrf_intro_badge ); ?></span>
					</div>
					<?php endif; ?>

					<h2 id="rrf-intro-heading" class="section__title">
						<?php echo esc_html( $rrf_intro_heading ); ?>
					</h2>

					<?php if ( $rrf_intro_subtitle ) : ?>
					<p class="section__subtitle">
						<?php echo wp_kses_post( $rrf_intro_subtitle ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $rrf_intro_lead ) : ?>
					<p class="rrf-intro__lead">
						<?php echo wp_kses_post( $rrf_intro_lead ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $rrf_intro_cta_primary || $rrf_intro_cta_secondary ) : ?>
					<div class="rrf-intro__actions">
						<?php if ( ! empty( $rrf_intro_cta_primary ) ) :
							$pri_url    = esc_url( $rrf_intro_cta_primary['url'] ?? '#' );
							$pri_title  = esc_html( $rrf_intro_cta_primary['title'] ?? __( 'Learn More', 'pc4s' ) );
							$pri_target = ! empty( $rrf_intro_cta_primary['target'] )
								? ' target="' . esc_attr( $rrf_intro_cta_primary['target'] ) . '"'
								: '';
						?>
						<a href="<?php echo $pri_url; ?>"<?php echo $pri_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--primary">
							<?php echo $pri_title; ?>
						</a>
						<?php endif; ?>

						<?php if ( ! empty( $rrf_intro_cta_secondary ) ) :
							$sec_url    = esc_url( $rrf_intro_cta_secondary['url'] ?? '#' );
							$sec_title  = esc_html( $rrf_intro_cta_secondary['title'] ?? __( 'Learn More', 'pc4s' ) );
							$sec_target = ! empty( $rrf_intro_cta_secondary['target'] )
								? ' target="' . esc_attr( $rrf_intro_cta_secondary['target'] ) . '"'
								: '';
						?>
						<a href="<?php echo $sec_url; ?>"<?php echo $sec_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--outline">
							<?php echo $sec_title; ?>
						</a>
						<?php endif; ?>
					</div><!-- /.rrf-intro__actions -->
					<?php endif; ?>

				</div><!-- /.rrf-intro__copy -->

				<!-- Right — event details card -->
				<aside class="rrf-intro__card" aria-label="<?php esc_attr_e( 'Event details', 'pc4s' ); ?>">

					<?php if ( $rrf_intro_card_heading ) : ?>
					<h3 class="rrf-intro__card-heading">
						<?php echo esc_html( $rrf_intro_card_heading ); ?>
					</h3>
					<?php endif; ?>

					<?php foreach ( $rrf_intro_card_details as $detail ) :
						$detail_icon = (string) ( $detail['rrf_detail_icon'] ?? 'default' );
						$detail_text = (string) ( $detail['rrf_detail_text'] ?? '' );
						if ( ! $detail_text ) {
							continue;
						}
						$detail_svg = $rrf_detail_icons[ $detail_icon ] ?? $rrf_detail_icons['default'];
					?>
					<div class="rrf-intro__card-detail">
						<?php echo $detail_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
						<span><?php echo wp_kses_post( $detail_text ); ?></span>
					</div>
					<?php endforeach; ?>

					<?php if ( $rrf_intro_card_note ) : ?>
					<hr class="rrf-intro__card-divider" />
					<p class="rrf-intro__card-note">
						<?php echo wp_kses_post( $rrf_intro_card_note ); ?>
					</p>
					<?php endif; ?>

				</aside><!-- /.rrf-intro__card -->

			</div><!-- /.rrf-intro__inner -->
		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 2 — EVENT HIGHLIGHTS
	     Feature grid: icon + label + description — ACF repeater
	     ==================================================================== -->
	<?php if ( ! empty( $rrf_highlights ) ) : ?>
	<section class="section rrf-highlights" aria-labelledby="rrf-highlights-heading">
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $rrf_highlights_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $rrf_highlights_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $rrf_highlights_heading ) : ?>
				<h2 id="rrf-highlights-heading" class="section__title">
					<?php echo esc_html( $rrf_highlights_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $rrf_highlights_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $rrf_highlights_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<div class="rrf-highlights__grid" role="list">
				<?php foreach ( $rrf_highlights as $highlight ) :
					$hl_icon  = (string) ( $highlight['rrf_highlight_icon']  ?? 'default' );
					$hl_label = (string) ( $highlight['rrf_highlight_label'] ?? '' );
					$hl_desc  = (string) ( $highlight['rrf_highlight_desc']  ?? '' );

					if ( ! $hl_label ) {
						continue;
					}

					$hl_svg = $rrf_highlight_icons[ $hl_icon ] ?? $rrf_highlight_icons['default'];
				?>
				<article class="rrf-highlights__item" role="listitem">
					<div class="rrf-highlights__icon" aria-hidden="true">
						<?php echo $hl_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
					</div>
					<h3 class="rrf-highlights__label"><?php echo esc_html( $hl_label ); ?></h3>
					<?php if ( $hl_desc ) : ?>
					<p class="rrf-highlights__desc"><?php echo wp_kses_post( $hl_desc ); ?></p>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div><!-- /.rrf-highlights__grid -->

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 3 — DOCUMENTS & SIGN-UPS
	     Each card: icon, tag, title, description, CTA label, URL
	     Sponsorship Information → Google Drive URL
	     Resource Table Sign-Up  → Eventbrite URL
	     ==================================================================== -->
	<?php if ( ! empty( $rrf_docs_cards ) ) : ?>
	<section
		class="section cb-docs"
		id="rrf-documents"
		aria-labelledby="rrf-docs-heading"
	>
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $rrf_docs_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $rrf_docs_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $rrf_docs_heading ) : ?>
				<h2 id="rrf-docs-heading" class="section__title">
					<?php echo esc_html( $rrf_docs_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $rrf_docs_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $rrf_docs_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<div class="cb-docs__grid">
				<?php foreach ( $rrf_docs_cards as $card ) :
					$card_tag       = (string) ( $card['rrf_doc_tag']       ?? '' );
					$card_title     = (string) ( $card['rrf_doc_title']     ?? '' );
					$card_desc      = (string) ( $card['rrf_doc_desc']      ?? '' );
					$card_cta_label = (string) ( $card['rrf_doc_cta_label'] ?? __( 'Open', 'pc4s' ) );
					$card_url       = (string) ( $card['rrf_doc_url']       ?? '' );
					$card_icon      = (string) ( $card['rrf_doc_icon']      ?? 'document' );

					if ( ! $card_title || ! $card_url ) {
						continue;
					}

					$card_icon_svg = $rrf_doc_icons[ $card_icon ] ?? $rrf_doc_icons['document'];
					$card_is_ext   = ( 0 !== strpos( $card_url, home_url() ) );
					$card_target   = $card_is_ext ? ' target="_blank" rel="noopener noreferrer"' : '';
					$card_aria     = esc_attr( $card_title . ( $card_tag ? ' (' . $card_tag . ')' : '' ) . ( $card_is_ext ? ' — ' . __( 'opens in new tab', 'pc4s' ) : '' ) );
				?>
				<a
					href="<?php echo esc_url( $card_url ); ?>"
					class="cb-doc-card"
					aria-label="<?php echo $card_aria; ?>"
					<?php echo $card_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — safe literal ?>
				>
					<div class="cb-doc-card__header">
						<div class="cb-doc-card__icon" aria-hidden="true">
							<?php echo $card_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
						</div>
						<?php echo $rrf_arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
					</div>

					<?php if ( $card_tag ) : ?>
					<span class="cb-doc-card__tag"><?php echo esc_html( $card_tag ); ?></span>
					<?php endif; ?>

					<h3 class="cb-doc-card__title"><?php echo esc_html( $card_title ); ?></h3>

					<?php if ( $card_desc ) : ?>
					<p class="cb-doc-card__desc"><?php echo wp_kses_post( $card_desc ); ?></p>
					<?php endif; ?>

					<span class="cb-doc-card__cta" aria-hidden="true">
						<?php echo esc_html( $card_cta_label ); ?>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							style="inline-size: 1em; block-size: 1em"
							aria-hidden="true"
						>
							<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
							<polyline points="7 10 12 15 17 10" />
							<line x1="12" y1="15" x2="12" y2="3" />
						</svg>
					</span>
				</a>
				<?php endforeach; ?>
			</div><!-- /.cb-docs__grid -->

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 4 — SPONSOR TIERS
	     Bronze / Silver / Gold / Platinum — ACF repeater
	     Perks are stored one-per-line in a textarea and split at render time.
	     ==================================================================== -->
	<?php if ( ! empty( $rrf_sponsor_tiers ) ) : ?>
	<section class="section rrf-sponsor" aria-labelledby="rrf-sponsor-heading">
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $rrf_sponsor_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $rrf_sponsor_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $rrf_sponsor_heading ) : ?>
				<h2 id="rrf-sponsor-heading" class="section__title">
					<?php echo esc_html( $rrf_sponsor_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $rrf_sponsor_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $rrf_sponsor_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<div class="rrf-sponsor__tiers">
				<?php foreach ( $rrf_sponsor_tiers as $tier ) :
					$tier_name  = (string) ( $tier['rrf_tier_name']  ?? '' );
					$tier_price = (string) ( $tier['rrf_tier_price'] ?? '' );
					$tier_perks = (string) ( $tier['rrf_tier_perks'] ?? '' );
					$tier_cta   = $tier['rrf_tier_cta'] ?? null; // link array|null

					if ( ! $tier_name ) {
						continue;
					}

					// Split perks on newlines; filter blank lines.
					$perks = $tier_perks
						? array_filter( array_map( 'trim', explode( "\n", $tier_perks ) ) )
						: [];
				?>
				<article class="rrf-sponsor__tier">
					<p class="rrf-sponsor__tier-name"><?php echo esc_html( $tier_name ); ?></p>

					<?php if ( $tier_price ) : ?>
					<p class="rrf-sponsor__tier-price">
						<?php echo esc_html( $tier_price ); ?>
						<span><?php esc_html_e( '/ level', 'pc4s' ); ?></span>
					</p>
					<?php endif; ?>

					<?php if ( ! empty( $perks ) ) : ?>
					<ul class="rrf-sponsor__tier-perks" role="list">
						<?php foreach ( $perks as $perk ) : ?>
						<li class="rrf-sponsor__tier-perk">
							<?php echo $rrf_check_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
							<?php echo esc_html( $perk ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>

					<?php if ( ! empty( $tier_cta ) ) :
						$t_url    = esc_url( $tier_cta['url'] ?? '#' );
						$t_title  = esc_html( $tier_cta['title'] ?? __( 'Sponsor Now', 'pc4s' ) );
						$t_target = ! empty( $tier_cta['target'] )
							? ' target="' . esc_attr( $tier_cta['target'] ) . '"'
							: '';
					?>
					<a href="<?php echo $t_url; ?>"<?php echo $t_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--primary">
						<?php echo $t_title; ?>
					</a>
					<?php endif; ?>

				</article>
				<?php endforeach; ?>
			</div><!-- /.rrf-sponsor__tiers -->

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 5 — PAGE CTA
	     ==================================================================== -->
	<?php if ( $rrf_cta_heading ) : ?>
	<section class="section page-cta" aria-labelledby="rrf-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $rrf_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $rrf_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="rrf-cta-heading" class="section__title">
					<?php echo esc_html( $rrf_cta_heading ); ?>
				</h2>

				<?php if ( $rrf_cta_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $rrf_cta_subtitle ); ?>
				</p>
				<?php endif; ?>

				<?php if ( $rrf_cta_primary || $rrf_cta_secondary ) : ?>
				<div class="page-cta__actions">
					<?php if ( ! empty( $rrf_cta_primary ) ) :
						$cta_p_url    = esc_url( $rrf_cta_primary['url'] ?? '#' );
						$cta_p_title  = esc_html( $rrf_cta_primary['title'] ?? __( 'Learn More', 'pc4s' ) );
						$cta_p_target = ! empty( $rrf_cta_primary['target'] )
							? ' target="' . esc_attr( $rrf_cta_primary['target'] ) . '"'
							: '';
					?>
					<a href="<?php echo $cta_p_url; ?>"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--primary">
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( ! empty( $rrf_cta_secondary ) ) :
						$cta_s_url    = esc_url( $rrf_cta_secondary['url'] ?? '#' );
						$cta_s_title  = esc_html( $rrf_cta_secondary['title'] ?? __( 'Learn More', 'pc4s' ) );
						$cta_s_target = ! empty( $rrf_cta_secondary['target'] )
							? ' target="' . esc_attr( $rrf_cta_secondary['target'] ) . '"'
							: '';
					?>
					<a href="<?php echo $cta_s_url; ?>"<?php echo $cta_s_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--outline">
						<?php echo $cta_s_title; ?>
					</a>
					<?php endif; ?>
				</div><!-- /.page-cta__actions -->
				<?php endif; ?>

			</div><!-- /.section__header -->
		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

</main><!-- /#main -->

<?php
get_footer();
