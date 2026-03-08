<?php
/**
 * Template Name: What Can We Do
 * Template Post Type: page
 *
 * Page template for the "What Can We Do?" page.
 *
 * Sections rendered (in order):
 *   1. Page Banner   — parts/content/page-banner.php
 *   2. Hero          — badge, heading, lead paragraph, CTA buttons, decorative accent pills
 *   3. Action Steps  — section header (badge, heading, subtitle) + numbered repeater list
 *   4. Callout       — highlighted box with heading, text, and two action buttons
 *
 * ACF field group: group_what_can_page (acf-json/group_what_can_page.json)
 *   — Field prefix: wcwd_
 *   — Location: Page Template == what-can-page.php
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
$wcwd_hero_badge     = (string) get_field( 'wcwd_hero_badge' );
$wcwd_hero_heading   = (string) get_field( 'wcwd_hero_heading' );
$wcwd_hero_lead      = (string) get_field( 'wcwd_hero_lead' );
$wcwd_hero_primary   = get_field( 'wcwd_hero_primary' );   // link array|null
$wcwd_hero_secondary = get_field( 'wcwd_hero_secondary' ); // link array|null
$wcwd_hero_pill_1    = (string) get_field( 'wcwd_hero_pill_1' );
$wcwd_hero_pill_2    = (string) get_field( 'wcwd_hero_pill_2' );

// --- Action Steps Section ---
$wcwd_steps_badge     = (string) get_field( 'wcwd_steps_badge' );
$wcwd_steps_heading   = (string) get_field( 'wcwd_steps_heading' );
$wcwd_steps_subtitle  = (string) get_field( 'wcwd_steps_subtitle' );
$wcwd_steps_items     = (array)  get_field( 'wcwd_steps_items' ) ?: [];

// --- Callout Section ---
$wcwd_callout_heading   = (string) get_field( 'wcwd_callout_heading' );
$wcwd_callout_text      = (string) get_field( 'wcwd_callout_text' );
$wcwd_callout_primary   = get_field( 'wcwd_callout_primary' );   // link array|null
$wcwd_callout_secondary = get_field( 'wcwd_callout_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<?php /* ====================================================================
   SECTION: Hero — badge, heading, lead, CTA buttons, accent pills
   ==================================================================== */ ?>
<?php if ( $wcwd_hero_heading ) : ?>
<section class="what-we-do-hero" aria-labelledby="wcwd-hero-heading">
	<div class="wrapper what-we-do-hero__inner">

		<!-- Copy -->
		<div class="what-we-do-hero__copy">

			<?php if ( $wcwd_hero_badge ) : ?>
			<span class="what-we-do-hero__badge"><?php echo esc_html( $wcwd_hero_badge ); ?></span>
			<?php endif; ?>

			<h2 id="wcwd-hero-heading" class="what-we-do-hero__title">
				<?php echo esc_html( $wcwd_hero_heading ); ?>
			</h2>

			<?php if ( $wcwd_hero_lead ) : ?>
			<p class="what-we-do-hero__lead"><?php echo esc_html( $wcwd_hero_lead ); ?></p>
			<?php endif; ?>

			<?php if ( $wcwd_hero_primary || $wcwd_hero_secondary ) : ?>
			<div class="what-we-do-hero__actions">
				<?php if ( $wcwd_hero_primary && ! empty( $wcwd_hero_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wcwd_hero_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $wcwd_hero_primary['target'] ) ) : ?>target="<?php echo esc_attr( $wcwd_hero_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wcwd_hero_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $wcwd_hero_secondary && ! empty( $wcwd_hero_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wcwd_hero_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $wcwd_hero_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $wcwd_hero_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wcwd_hero_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__copy -->

		<!-- Decorative accent pills -->
		<?php if ( $wcwd_hero_pill_1 || $wcwd_hero_pill_2 ) : ?>
		<div class="what-we-do-hero__accent" aria-hidden="true">

			<?php if ( $wcwd_hero_pill_1 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
					<circle cx="9" cy="7" r="4"></circle>
					<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
					<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
				</svg>
				<?php echo esc_html( $wcwd_hero_pill_1 ); ?>
			</span>
			<?php endif; ?>

			<?php if ( $wcwd_hero_pill_2 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--secondary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
				</svg>
				<?php echo esc_html( $wcwd_hero_pill_2 ); ?>
			</span>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__accent -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .what-we-do-hero -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Action Steps — numbered repeater list
   ==================================================================== */ ?>
<?php if ( $wcwd_steps_heading ) : ?>
<section class="section" aria-labelledby="wcwd-actions-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $wcwd_steps_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $wcwd_steps_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="wcwd-actions-heading" class="section__title">
				<?php echo esc_html( $wcwd_steps_heading ); ?>
			</h2>

			<?php if ( $wcwd_steps_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $wcwd_steps_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $wcwd_steps_items ) : ?>
		<ol
			class="what-we-do__action-list"
			aria-label="<?php esc_attr_e( 'Action steps', 'pc4s' ); ?>"
		>
			<?php
			$step_num = 0;
			foreach ( $wcwd_steps_items as $step ) :
				$step_title = (string) ( $step['step_title'] ?? '' );
				$step_desc  = (string) ( $step['step_desc']  ?? '' );
				if ( ! $step_title ) {
					continue;
				}
				++$step_num;
			?>
			<li class="what-we-do__action-item">
				<span class="what-we-do__action-num" aria-hidden="true"><?php echo esc_html( (string) $step_num ); ?></span>
				<div class="what-we-do__action-content">
					<h3 class="what-we-do__action-title"><?php echo esc_html( $step_title ); ?></h3>
					<?php if ( $step_desc ) : ?>
					<p class="what-we-do__action-desc"><?php echo esc_html( $step_desc ); ?></p>
					<?php endif; ?>
				</div>
			</li>
			<?php endforeach; ?>
		</ol>
		<?php endif; ?>

	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Callout — highlighted box with heading, text, buttons
   ==================================================================== */ ?>
<?php if ( $wcwd_callout_heading ) : ?>
<section class="section page-cta" aria-labelledby="wcwd-callout-heading">
	<div class="wrapper">
		<div class="what-we-do__callout">

			<h2 id="wcwd-callout-heading" class="what-we-do__callout-title">
				<?php echo esc_html( $wcwd_callout_heading ); ?>
			</h2>

			<?php if ( $wcwd_callout_text ) : ?>
			<p class="what-we-do__callout-text"><?php echo esc_html( $wcwd_callout_text ); ?></p>
			<?php endif; ?>

			<?php if ( $wcwd_callout_primary || $wcwd_callout_secondary ) : ?>
			<div class="what-we-do__callout-actions">
				<?php if ( $wcwd_callout_primary && ! empty( $wcwd_callout_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wcwd_callout_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $wcwd_callout_primary['target'] ) ) : ?>target="<?php echo esc_attr( $wcwd_callout_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wcwd_callout_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $wcwd_callout_secondary && ! empty( $wcwd_callout_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $wcwd_callout_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $wcwd_callout_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $wcwd_callout_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $wcwd_callout_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .what-we-do__callout -->
	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php
get_footer();
