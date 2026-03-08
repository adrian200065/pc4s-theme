<?php
/**
 * Template Name: How Can You Get Involved
 * Template Post Type: page
 *
 * Page template for the "How Can You Get Involved?" page.
 *
 * Sections rendered (in order):
 *   1. Page Banner       — parts/content/page-banner.php
 *   2. Hero              — badge, heading, lead paragraph, CTA buttons, decorative accent pills
 *   3. Coalition Meetings — section header (badge, heading, subtitle) + icon-card grid repeater
 *   4. Getting Started   — section header (badge, heading, subtitle) + numbered steps repeater
 *   5. Callout           — highlighted box with heading, text, and two action buttons
 *
 * ACF field group: group_involved_page (acf-json/group_involved_page.json)
 *   — Field prefix: hcgi_
 *   — Location: Page Template == involved-page.php
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
$hcgi_hero_badge     = (string) get_field( 'hcgi_hero_badge' );
$hcgi_hero_heading   = (string) get_field( 'hcgi_hero_heading' );
$hcgi_hero_lead      = (string) get_field( 'hcgi_hero_lead' );
$hcgi_hero_primary   = get_field( 'hcgi_hero_primary' );   // link array|null
$hcgi_hero_secondary = get_field( 'hcgi_hero_secondary' ); // link array|null
$hcgi_hero_pill_1    = (string) get_field( 'hcgi_hero_pill_1' );
$hcgi_hero_pill_2    = (string) get_field( 'hcgi_hero_pill_2' );

// --- Coalition Meetings Section ---
$hcgi_meetings_badge    = (string) get_field( 'hcgi_meetings_badge' );
$hcgi_meetings_heading  = (string) get_field( 'hcgi_meetings_heading' );
$hcgi_meetings_subtitle = (string) get_field( 'hcgi_meetings_subtitle' );
$hcgi_meetings_cards    = (array)  get_field( 'hcgi_meetings_cards' ) ?: [];

// --- Getting Started Steps Section ---
$hcgi_steps_badge    = (string) get_field( 'hcgi_steps_badge' );
$hcgi_steps_heading  = (string) get_field( 'hcgi_steps_heading' );
$hcgi_steps_subtitle = (string) get_field( 'hcgi_steps_subtitle' );
$hcgi_steps_items    = (array)  get_field( 'hcgi_steps_items' ) ?: [];

// --- Callout Section ---
$hcgi_callout_heading   = (string) get_field( 'hcgi_callout_heading' );
$hcgi_callout_text      = (string) get_field( 'hcgi_callout_text' );
$hcgi_callout_primary   = get_field( 'hcgi_callout_primary' );   // link array|null
$hcgi_callout_secondary = get_field( 'hcgi_callout_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Icon SVG map — keyed by the select field value.
// Decorative only; aria-hidden is applied at render time.
// ---------------------------------------------------------------------------
$hcgi_icon_svgs = [
	'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
	'users'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
	'star'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
	'heart'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
	'check'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>',
	'shield'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
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
<?php if ( $hcgi_hero_heading ) : ?>
<section class="what-we-do-hero" aria-labelledby="hcgi-hero-heading">
	<div class="wrapper what-we-do-hero__inner">

		<!-- Copy -->
		<div class="what-we-do-hero__copy">

			<?php if ( $hcgi_hero_badge ) : ?>
			<span class="what-we-do-hero__badge"><?php echo esc_html( $hcgi_hero_badge ); ?></span>
			<?php endif; ?>

			<h2 id="hcgi-hero-heading" class="what-we-do-hero__title">
				<?php echo nl2br( esc_html( $hcgi_hero_heading ) ); ?>
			</h2>

			<?php if ( $hcgi_hero_lead ) : ?>
			<p class="what-we-do-hero__lead"><?php echo esc_html( $hcgi_hero_lead ); ?></p>
			<?php endif; ?>

			<?php if ( $hcgi_hero_primary || $hcgi_hero_secondary ) : ?>
			<div class="what-we-do-hero__actions">
				<?php if ( $hcgi_hero_primary && ! empty( $hcgi_hero_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $hcgi_hero_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $hcgi_hero_primary['target'] ) ) : ?>target="<?php echo esc_attr( $hcgi_hero_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $hcgi_hero_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $hcgi_hero_secondary && ! empty( $hcgi_hero_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $hcgi_hero_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $hcgi_hero_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $hcgi_hero_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $hcgi_hero_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__copy -->

		<!-- Decorative accent pills -->
		<?php if ( $hcgi_hero_pill_1 || $hcgi_hero_pill_2 ) : ?>
		<div class="what-we-do-hero__accent" aria-hidden="true">

			<?php if ( $hcgi_hero_pill_1 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
					<circle cx="9" cy="7" r="4"></circle>
					<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
					<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
				</svg>
				<?php echo esc_html( $hcgi_hero_pill_1 ); ?>
			</span>
			<?php endif; ?>

			<?php if ( $hcgi_hero_pill_2 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--secondary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
					<line x1="16" y1="2" x2="16" y2="6"></line>
					<line x1="8" y1="2" x2="8" y2="6"></line>
					<line x1="3" y1="10" x2="21" y2="10"></line>
				</svg>
				<?php echo esc_html( $hcgi_hero_pill_2 ); ?>
			</span>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__accent -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .what-we-do-hero -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Coalition Meetings — icon-card grid repeater
   ==================================================================== */ ?>
<?php if ( $hcgi_meetings_heading ) : ?>
<section class="section" aria-labelledby="hcgi-meetings-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $hcgi_meetings_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $hcgi_meetings_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="hcgi-meetings-heading" class="section__title">
				<?php echo esc_html( $hcgi_meetings_heading ); ?>
			</h2>

			<?php if ( $hcgi_meetings_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $hcgi_meetings_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $hcgi_meetings_cards ) : ?>
		<div class="what-we-do__grid">
			<?php foreach ( $hcgi_meetings_cards as $card ) :
				$card_icon     = (string) ( $card['card_icon']     ?? '' );
				$card_modifier = (string) ( $card['card_modifier'] ?? '' );
				$card_title    = (string) ( $card['card_title']    ?? '' );
				$card_body     = (string) ( $card['card_body']     ?? '' );
				if ( ! $card_title ) {
					continue;
				}
				$modifier_class = $card_modifier
					? ' what-we-do__card--' . sanitize_html_class( $card_modifier )
					: '';
				$icon_svg = isset( $hcgi_icon_svgs[ $card_icon ] ) ? $hcgi_icon_svgs[ $card_icon ] : '';
			?>
			<div class="what-we-do__card<?php echo esc_attr( $modifier_class ); ?>">
				<?php if ( $icon_svg ) : ?>
				<div class="what-we-do__card-icon">
					<?php echo $icon_svg; // pre-escaped static SVG map — no user input ?>
				</div>
				<?php endif; ?>
				<h3 class="what-we-do__card-title"><?php echo esc_html( $card_title ); ?></h3>
				<?php if ( $card_body ) : ?>
				<p class="what-we-do__card-body"><?php echo esc_html( $card_body ); ?></p>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div><!-- .what-we-do__grid -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Getting Started Steps — numbered repeater list
   ==================================================================== */ ?>
<?php if ( $hcgi_steps_heading ) : ?>
<section class="section background-light" aria-labelledby="hcgi-steps-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $hcgi_steps_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $hcgi_steps_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="hcgi-steps-heading" class="section__title">
				<?php echo esc_html( $hcgi_steps_heading ); ?>
			</h2>

			<?php if ( $hcgi_steps_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $hcgi_steps_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $hcgi_steps_items ) : ?>
		<ol
			class="what-we-do__action-list"
			aria-label="<?php esc_attr_e( 'Ways to get involved with PC4S', 'pc4s' ); ?>"
		>
			<?php
			$step_num = 0;
			foreach ( $hcgi_steps_items as $step ) :
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
<?php if ( $hcgi_callout_heading ) : ?>
<section class="section" aria-labelledby="hcgi-callout-heading">
	<div class="wrapper">
		<div class="what-we-do__callout">

			<h2 id="hcgi-callout-heading" class="what-we-do__callout-title">
				<?php echo esc_html( $hcgi_callout_heading ); ?>
			</h2>

			<?php if ( $hcgi_callout_text ) : ?>
			<p class="what-we-do__callout-text"><?php echo esc_html( $hcgi_callout_text ); ?></p>
			<?php endif; ?>

			<?php if ( $hcgi_callout_primary || $hcgi_callout_secondary ) : ?>
			<div class="what-we-do__callout-actions">
				<?php if ( $hcgi_callout_primary && ! empty( $hcgi_callout_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $hcgi_callout_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $hcgi_callout_primary['target'] ) ) : ?>target="<?php echo esc_attr( $hcgi_callout_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $hcgi_callout_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $hcgi_callout_secondary && ! empty( $hcgi_callout_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $hcgi_callout_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $hcgi_callout_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $hcgi_callout_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $hcgi_callout_secondary['title'] ); ?>
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
