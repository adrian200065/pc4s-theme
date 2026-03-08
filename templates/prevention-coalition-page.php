<?php
/**
 * Template Name: What Prevention Coalition Does
 * Template Post Type: page
 *
 * Page template for the "What Prevention Coalition Does?" page.
 *
 * Sections rendered (in order):
 *   1. Page Banner  — parts/content/page-banner.php
 *   2. Hero         — badge, heading, lead paragraph, CTA buttons, decorative accent pills
 *   3. Pillars      — section header + repeater of pillar articles (icon, colour, title, description)
 *   4. Stats        — section header + repeater of stat items (number, aria-label, label)
 *   5. CTA          — centered section header with badge, heading, subtitle, and two action buttons
 *
 * ACF field group: group_prevention_coalition_page (acf-json/group_prevention_coalition_page.json)
 *   — Field prefix: wpcd_
 *   — Location: Page Template == prevention-coalition-page.php
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
$wpcd_hero_badge     = (string) get_field( 'wpcd_hero_badge' );
$wpcd_hero_heading   = (string) get_field( 'wpcd_hero_heading' );
$wpcd_hero_lead      = (string) get_field( 'wpcd_hero_lead' );
$wpcd_hero_primary   = get_field( 'wpcd_hero_primary' );   // link array|null
$wpcd_hero_secondary = get_field( 'wpcd_hero_secondary' ); // link array|null
$wpcd_hero_pill_1    = (string) get_field( 'wpcd_hero_pill_1' );
$wpcd_hero_pill_2    = (string) get_field( 'wpcd_hero_pill_2' );

// --- Pillars Section ---
$wpcd_pillars_badge    = (string) get_field( 'wpcd_pillars_badge' );
$wpcd_pillars_heading  = (string) get_field( 'wpcd_pillars_heading' );
$wpcd_pillars_subtitle = (string) get_field( 'wpcd_pillars_subtitle' );
$wpcd_pillars_items    = (array)  get_field( 'wpcd_pillars_items' ) ?: [];

// --- Stats Section ---
$wpcd_stats_badge    = (string) get_field( 'wpcd_stats_badge' );
$wpcd_stats_heading  = (string) get_field( 'wpcd_stats_heading' );
$wpcd_stats_subtitle = (string) get_field( 'wpcd_stats_subtitle' );
$wpcd_stats_items    = (array)  get_field( 'wpcd_stats_items' ) ?: [];

// --- CTA Section ---
$wpcd_cta_badge      = (string) get_field( 'wpcd_cta_badge' );
$wpcd_cta_heading    = (string) get_field( 'wpcd_cta_heading' );
$wpcd_cta_subtitle   = (string) get_field( 'wpcd_cta_subtitle' );
$wpcd_cta_primary    = get_field( 'wpcd_cta_primary' );   // link array|null
$wpcd_cta_secondary  = get_field( 'wpcd_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Pillar icon SVG map — keyed by select field value.
// Decorative only; aria-hidden is applied on the wrapper at render time.
// ---------------------------------------------------------------------------
$wpcd_pillar_icon_svgs = [
	'shield'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
	'book'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
	'trending' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line></svg>',
	'file'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
	'heart'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
	'users'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
	'star'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
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
<?php if ( $wpcd_hero_heading ) : ?>
<section class="what-we-do-hero" aria-labelledby="wpcd-hero-heading">
	<div class="wrapper what-we-do-hero__inner">

		<!-- Copy -->
		<div class="what-we-do-hero__copy">

			<?php if ( $wpcd_hero_badge ) : ?>
			<span class="what-we-do-hero__badge"><?php echo esc_html( $wpcd_hero_badge ); ?></span>
			<?php endif; ?>

			<h2 id="wpcd-hero-heading" class="what-we-do-hero__title">
				<?php echo nl2br( esc_html( $wpcd_hero_heading ) ); ?>
			</h2>

			<?php if ( $wpcd_hero_lead ) : ?>
			<p class="what-we-do-hero__lead"><?php echo esc_html( $wpcd_hero_lead ); ?></p>
			<?php endif; ?>

			<?php if ( $wpcd_hero_primary || $wpcd_hero_secondary ) : ?>
			<div class="what-we-do-hero__actions">
				<?php if ( $wpcd_hero_primary && ! empty( $wpcd_hero_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpcd_hero_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $wpcd_hero_primary['target'] ) ) : ?>target="<?php echo esc_attr( $wpcd_hero_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpcd_hero_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $wpcd_hero_secondary && ! empty( $wpcd_hero_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpcd_hero_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $wpcd_hero_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $wpcd_hero_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpcd_hero_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__copy -->

		<!-- Decorative accent pills -->
		<?php if ( $wpcd_hero_pill_1 || $wpcd_hero_pill_2 ) : ?>
		<div class="what-we-do-hero__accent" aria-hidden="true">

			<?php if ( $wpcd_hero_pill_1 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
				</svg>
				<?php echo esc_html( $wpcd_hero_pill_1 ); ?>
			</span>
			<?php endif; ?>

			<?php if ( $wpcd_hero_pill_2 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--secondary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
					<circle cx="9" cy="7" r="4"></circle>
					<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
					<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
				</svg>
				<?php echo esc_html( $wpcd_hero_pill_2 ); ?>
			</span>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__accent -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .what-we-do-hero -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Pillars — five strategic pillars of PC4S work.
   Each pillar has a coloured icon wrap, title, and description paragraph.
   ==================================================================== */ ?>
<?php if ( $wpcd_pillars_heading ) : ?>
<section class="section" aria-labelledby="wpcd-pillars-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $wpcd_pillars_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wpcd_pillars_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wpcd-pillars-heading" class="section__title">
				<?php echo esc_html( $wpcd_pillars_heading ); ?>
			</h2>

			<?php if ( $wpcd_pillars_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wpcd_pillars_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $wpcd_pillars_items ) : ?>
		<div class="what-we-do__pillars" role="list">
			<?php
			foreach ( $wpcd_pillars_items as $pillar ) :
				$pillar_icon  = (string) ( $pillar['pillar_icon']  ?? '' );
				$pillar_color = (string) ( $pillar['pillar_color'] ?? 'red' );
				$pillar_title = (string) ( $pillar['pillar_title'] ?? '' );
				$pillar_desc  = (string) ( $pillar['pillar_desc']  ?? '' );

				if ( ! $pillar_title && ! $pillar_desc ) {
					continue;
				}

				$icon_wrap_class = 'what-we-do__pillar-icon-wrap';
				if ( $pillar_color ) {
					$icon_wrap_class .= ' what-we-do__pillar-icon-wrap--' . sanitize_html_class( $pillar_color );
				}
			?>
			<article class="what-we-do__pillar" role="listitem">

				<?php if ( isset( $wpcd_pillar_icon_svgs[ $pillar_icon ] ) ) : ?>
				<div class="<?php echo esc_attr( $icon_wrap_class ); ?>" aria-hidden="true">
					<?php echo $wpcd_pillar_icon_svgs[ $pillar_icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped static SVG markup. ?>
				</div>
				<?php endif; ?>

				<div class="what-we-do__pillar-content">
					<?php if ( $pillar_title ) : ?>
					<h3 class="what-we-do__pillar-title"><?php echo esc_html( $pillar_title ); ?></h3>
					<?php endif; ?>
					<?php if ( $pillar_desc ) : ?>
					<p class="what-we-do__pillar-desc"><?php echo esc_html( $pillar_desc ); ?></p>
					<?php endif; ?>
				</div><!-- .what-we-do__pillar-content -->

			</article>
			<?php endforeach; ?>
		</div><!-- .what-we-do__pillars -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .section (pillars) -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Stats — key impact numbers displayed in a horizontal grid.
   ==================================================================== */ ?>
<?php if ( $wpcd_stats_heading ) : ?>
<section class="section" aria-labelledby="wpcd-stats-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $wpcd_stats_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wpcd_stats_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wpcd-stats-heading" class="section__title">
				<?php echo esc_html( $wpcd_stats_heading ); ?>
			</h2>

			<?php if ( $wpcd_stats_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wpcd_stats_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $wpcd_stats_items ) : ?>
		<div class="what-we-do__stats" role="list">
			<?php
			foreach ( $wpcd_stats_items as $stat ) :
				$stat_number     = (string) ( $stat['stat_number']     ?? '' );
				$stat_aria_label = (string) ( $stat['stat_aria_label'] ?? '' );
				$stat_label      = (string) ( $stat['stat_label']      ?? '' );

				if ( ! $stat_number && ! $stat_label ) {
					continue;
				}
			?>
			<div class="what-we-do__stat" role="listitem">
				<?php if ( $stat_number ) : ?>
				<span
					class="what-we-do__stat-number"
					<?php if ( $stat_aria_label ) : ?>aria-label="<?php echo esc_attr( $stat_aria_label ); ?>"<?php endif; ?>
				><?php echo esc_html( $stat_number ); ?></span>
				<?php endif; ?>
				<?php if ( $stat_label ) : ?>
				<p class="what-we-do__stat-label"><?php echo esc_html( $stat_label ); ?></p>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div><!-- .what-we-do__stats -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .section (stats) -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: CTA — full-width call to action.
   ==================================================================== */ ?>
<?php if ( $wpcd_cta_heading ) : ?>
<section class="section page-cta" aria-labelledby="wpcd-cta-heading">
	<div class="wrapper">
		<div class="section__header">

			<?php if ( $wpcd_cta_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wpcd_cta_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wpcd-cta-heading" class="section__title">
				<?php echo esc_html( $wpcd_cta_heading ); ?>
			</h2>

			<?php if ( $wpcd_cta_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wpcd_cta_subtitle ); ?></p>
			<?php endif; ?>

			<?php if ( $wpcd_cta_primary || $wpcd_cta_secondary ) : ?>
			<div class="page-cta__actions">
				<?php if ( $wpcd_cta_primary && ! empty( $wpcd_cta_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpcd_cta_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $wpcd_cta_primary['target'] ) ) : ?>target="<?php echo esc_attr( $wpcd_cta_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpcd_cta_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $wpcd_cta_secondary && ! empty( $wpcd_cta_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wpcd_cta_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $wpcd_cta_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $wpcd_cta_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wpcd_cta_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .section__header -->
	</div><!-- .wrapper -->
</section><!-- .section.page-cta -->
<?php endif; ?>

<?php get_footer(); ?>
