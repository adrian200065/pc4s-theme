<?php
/**
 * Template Name: Why Prevention Works
 * Template Post Type: page
 *
 * Page template for the "Why Prevention Works?" page.
 *
 * Sections rendered (in order):
 *   1. Page Banner     — parts/content/page-banner.php
 *   2. Hero            — badge, heading, lead paragraph, CTA buttons, decorative accent pills
 *   3. Key Stats       — "Prevention in Action" section with evidence-card grid (statistics)
 *   4. Why It Works    — "The Case for Prevention" section with evidence-card grid
 *   5. CTA             — centered section header with badge, heading, subtitle, and two action buttons
 *
 * ACF field group: group_prevention_page (acf-json/group_prevention_page.json)
 *   — Field prefix: wpw_
 *   — Location: Page Template == prevention-page.php
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Resolve all ACF fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Hero Section ---
$wpw_hero_badge     = (string) get_field( 'wpw_hero_badge' );
$wpw_hero_heading   = (string) get_field( 'wpw_hero_heading' );
$wpw_hero_lead      = (string) get_field( 'wpw_hero_lead' );
$wpw_hero_primary   = get_field( 'wpw_hero_primary' );   // link array|null
$wpw_hero_secondary = get_field( 'wpw_hero_secondary' ); // link array|null
$wpw_hero_pill_1    = (string) get_field( 'wpw_hero_pill_1' );
$wpw_hero_pill_2    = (string) get_field( 'wpw_hero_pill_2' );

// --- Key Stats Section ---
$wpw_stats_badge    = (string) get_field( 'wpw_stats_badge' );
$wpw_stats_heading  = (string) get_field( 'wpw_stats_heading' );
$wpw_stats_subtitle = (string) get_field( 'wpw_stats_subtitle' );
$wpw_stats_cards    = (array)  get_field( 'wpw_stats_cards' ) ?: [];

// --- Why It Works Section ---
$wpw_evidence_badge    = (string) get_field( 'wpw_evidence_badge' );
$wpw_evidence_heading  = (string) get_field( 'wpw_evidence_heading' );
$wpw_evidence_subtitle = (string) get_field( 'wpw_evidence_subtitle' );
$wpw_evidence_cards    = (array)  get_field( 'wpw_evidence_cards' ) ?: [];

// --- CTA Section ---
$wpw_cta_badge      = (string) get_field( 'wpw_cta_badge' );
$wpw_cta_heading    = (string) get_field( 'wpw_cta_heading' );
$wpw_cta_subtitle   = (string) get_field( 'wpw_cta_subtitle' );
$wpw_cta_primary    = get_field( 'wpw_cta_primary' );   // link array|null
$wpw_cta_secondary  = get_field( 'wpw_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Icon SVG maps — keyed by the select field value.
// Decorative only; aria-hidden is applied at render time.
// ---------------------------------------------------------------------------

/** Stats section icons — substance categories. */
$wpw_stats_icon_svgs = [
	'cigarette'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 16h15"></path><path d="M22 11h-6.5a2 2 0 0 0-2 2v2.5"></path><path d="M20 4v4"></path><path d="M16 6h4"></path></svg>',
	'alert'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
	'glass'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 22h8"></path><path d="M7 10h10"></path><path d="M12 22V10"></path><path d="M5 3l1 7h12l1-7z"></path></svg>',
	'globe'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a10 10 0 0 1 0 20"></path><path d="M12 2a10 10 0 0 0 0 20"></path><path d="M12 2v20"></path><path d="M2 12h20"></path></svg>',
	'prescription' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2v-4M9 21H5a2 2 0 0 1-2-2v-4m0 0h18"></path></svg>',
];

/** Why It Works section icons — prevention principles. */
$wpw_evidence_icon_svgs = [
	'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
	'clock'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
	'bar-chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
	'shield'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
	'heart'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
	'arrow-up'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="16 12 12 8 8 12"></polyline><line x1="12" y1="16" x2="12" y2="8"></line></svg>',
];

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<?php /* ====================================================================
   SECTION: Hero — badge, heading, lead, CTA buttons, accent pills
   ==================================================================== */ ?>
<?php if ( $wpw_hero_heading ) : ?>
<section class="what-we-do-hero" aria-labelledby="wpw-hero-heading">
	<div class="wrapper what-we-do-hero__inner">

		<!-- Copy -->
		<div class="what-we-do-hero__copy">

			<?php if ( $wpw_hero_badge ) : ?>
			<span class="what-we-do-hero__badge"><?php echo esc_html( $wpw_hero_badge ); ?></span>
			<?php endif; ?>

			<h2 id="wpw-hero-heading" class="what-we-do-hero__title">
				<?php echo nl2br( esc_html( $wpw_hero_heading ) ); ?>
			</h2>

			<?php if ( $wpw_hero_lead ) : ?>
			<p class="what-we-do-hero__lead"><?php echo esc_html( $wpw_hero_lead ); ?></p>
			<?php endif; ?>

			<?php if ( $wpw_hero_primary || $wpw_hero_secondary ) : ?>
			<div class="what-we-do-hero__actions">
				<?php if ( $wpw_hero_primary && ! empty( $wpw_hero_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpw_hero_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $wpw_hero_primary['target'] ) ) : ?>target="<?php echo esc_attr( $wpw_hero_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpw_hero_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $wpw_hero_secondary && ! empty( $wpw_hero_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpw_hero_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $wpw_hero_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $wpw_hero_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpw_hero_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__copy -->

		<!-- Decorative accent pills -->
		<?php if ( $wpw_hero_pill_1 || $wpw_hero_pill_2 ) : ?>
		<div class="what-we-do-hero__accent" aria-hidden="true">

			<?php if ( $wpw_hero_pill_1 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
				</svg>
				<?php echo esc_html( $wpw_hero_pill_1 ); ?>
			</span>
			<?php endif; ?>

			<?php if ( $wpw_hero_pill_2 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--secondary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
				</svg>
				<?php echo esc_html( $wpw_hero_pill_2 ); ?>
			</span>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__accent -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .what-we-do-hero -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Key Stats — evidence-card grid with substance-use statistics.
   Card body supports basic HTML (<strong>) for numeric highlights.
   ==================================================================== */ ?>
<?php if ( $wpw_stats_heading ) : ?>
<section class="section" aria-labelledby="wpw-stats-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $wpw_stats_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wpw_stats_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wpw-stats-heading" class="section__title">
				<?php echo esc_html( $wpw_stats_heading ); ?>
			</h2>

			<?php if ( $wpw_stats_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wpw_stats_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $wpw_stats_cards ) : ?>
		<div class="what-we-do__evidence">
			<?php
			foreach ( $wpw_stats_cards as $stat_card ) :
				$card_title    = (string) ( $stat_card['card_title']    ?? '' );
				$card_body     = (string) ( $stat_card['card_body']     ?? '' );
				$card_icon     = (string) ( $stat_card['card_icon']     ?? '' );
				$card_modifier = (string) ( $stat_card['card_icon_modifier'] ?? '' );

				if ( ! $card_title && ! $card_body ) {
					continue;
				}

				$icon_class = 'what-we-do__evidence-icon';
				if ( $card_modifier && 'default' !== $card_modifier ) {
					$icon_class .= ' what-we-do__evidence-icon--' . sanitize_html_class( $card_modifier );
				}
			?>
			<article class="what-we-do__evidence-card">

				<?php if ( isset( $wpw_stats_icon_svgs[ $card_icon ] ) ) : ?>
				<div class="<?php echo esc_attr( $icon_class ); ?>">
					<?php echo $wpw_stats_icon_svgs[ $card_icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped static SVG markup. ?>
				</div>
				<?php endif; ?>

				<?php if ( $card_title ) : ?>
				<h3 class="what-we-do__evidence-title"><?php echo esc_html( $card_title ); ?></h3>
				<?php endif; ?>

				<?php if ( $card_body ) : ?>
				<p class="what-we-do__evidence-body"><?php echo wp_kses_post( $card_body ); ?></p>
				<?php endif; ?>

			</article>
			<?php endforeach; ?>
		</div><!-- .what-we-do__evidence -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .section (stats) -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Why It Works — evidence-card grid with prevention principles.
   ==================================================================== */ ?>
<?php if ( $wpw_evidence_heading ) : ?>
<section class="section" aria-labelledby="wpw-evidence-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $wpw_evidence_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wpw_evidence_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wpw-evidence-heading" class="section__title">
				<?php echo esc_html( $wpw_evidence_heading ); ?>
			</h2>

			<?php if ( $wpw_evidence_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wpw_evidence_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $wpw_evidence_cards ) : ?>
		<div class="what-we-do__evidence">
			<?php
			foreach ( $wpw_evidence_cards as $evid_card ) :
				$card_title    = (string) ( $evid_card['card_title']         ?? '' );
				$card_body     = (string) ( $evid_card['card_body']          ?? '' );
				$card_icon     = (string) ( $evid_card['card_icon']          ?? '' );
				$card_modifier = (string) ( $evid_card['card_icon_modifier'] ?? '' );

				if ( ! $card_title && ! $card_body ) {
					continue;
				}

				$icon_class = 'what-we-do__evidence-icon';
				if ( $card_modifier && 'default' !== $card_modifier ) {
					$icon_class .= ' what-we-do__evidence-icon--' . sanitize_html_class( $card_modifier );
				}
			?>
			<article class="what-we-do__evidence-card">

				<?php if ( isset( $wpw_evidence_icon_svgs[ $card_icon ] ) ) : ?>
				<div class="<?php echo esc_attr( $icon_class ); ?>">
					<?php echo $wpw_evidence_icon_svgs[ $card_icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped static SVG markup. ?>
				</div>
				<?php endif; ?>

				<?php if ( $card_title ) : ?>
				<h3 class="what-we-do__evidence-title"><?php echo esc_html( $card_title ); ?></h3>
				<?php endif; ?>

				<?php if ( $card_body ) : ?>
				<p class="what-we-do__evidence-body"><?php echo esc_html( $card_body ); ?></p>
				<?php endif; ?>

			</article>
			<?php endforeach; ?>
		</div><!-- .what-we-do__evidence -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .section (evidence) -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: CTA — full-width call to action.
   ==================================================================== */ ?>
<?php if ( $wpw_cta_heading ) : ?>
<section class="section page-cta" aria-labelledby="wpw-cta-heading">
	<div class="wrapper">
		<div class="section__header">

			<?php if ( $wpw_cta_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wpw_cta_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wpw-cta-heading" class="section__title">
				<?php echo esc_html( $wpw_cta_heading ); ?>
			</h2>

			<?php if ( $wpw_cta_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wpw_cta_subtitle ); ?></p>
			<?php endif; ?>

			<?php if ( $wpw_cta_primary || $wpw_cta_secondary ) : ?>
			<div class="page-cta__actions">
				<?php if ( $wpw_cta_primary && ! empty( $wpw_cta_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpw_cta_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $wpw_cta_primary['target'] ) ) : ?>target="<?php echo esc_attr( $wpw_cta_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpw_cta_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $wpw_cta_secondary && ! empty( $wpw_cta_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpw_cta_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $wpw_cta_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $wpw_cta_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpw_cta_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .section__header -->
	</div><!-- .wrapper -->
</section><!-- .section.page-cta -->
<?php endif; ?>

<?php get_footer(); ?>
