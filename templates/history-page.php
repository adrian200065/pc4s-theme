<?php
/**
 * Template Name: Our History
 * Template Post Type: page
 *
 * Page template for the Our History page.
 *
 * Sections rendered (in order):
 *   1. Page Banner       — parts/content/page-banner.php
 *   2. History Content   — badge, heading, subtitle, timeline (repeater)
 *   3. Page CTA          — badge, heading, subtitle, two action links
 *
 * ACF field group: group_history_page (acf-json/group_history_page.json)
 *   – Manages all editable content. No hardcoded strings in markup.
 *   – Field prefix: hp_ (history page).
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

// --- History Content Section ---
$hp_history_badge    = (string) get_field( 'hp_history_badge' );
$hp_history_heading  = (string) get_field( 'hp_history_heading' );
$hp_history_subtitle = (string) get_field( 'hp_history_subtitle' );
$hp_timeline         = (array)  get_field( 'hp_timeline' ) ?: [];

// --- CTA Section ---
$hp_cta_badge     = (string) get_field( 'hp_cta_badge' );
$hp_cta_heading   = (string) get_field( 'hp_cta_heading' );
$hp_cta_subtitle  = (string) get_field( 'hp_cta_subtitle' );
$hp_cta_primary   = get_field( 'hp_cta_primary' );   // link array|null
$hp_cta_secondary = get_field( 'hp_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main-content" class="site-main" role="main">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION 2: History Content — badge, heading, subtitle, timeline
	   ================================================================ */ ?>
	<?php if ( $hp_history_heading || $hp_timeline ) : ?>
	<section class="section history-content" aria-labelledby="history-heading">
		<div class="wrapper">

			<?php if ( $hp_history_badge || $hp_history_heading || $hp_history_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $hp_history_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $hp_history_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $hp_history_heading ) : ?>
				<h2 id="history-heading" class="section__title">
					<?php echo esc_html( $hp_history_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $hp_history_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $hp_history_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $hp_timeline ) : ?>
			<ol class="history-timeline" aria-label="<?php esc_attr_e( 'PC4S historical milestones', 'pc4s' ); ?>">

				<?php foreach ( $hp_timeline as $item ) :
					$year  = (string) ( $item['hp_timeline_year']  ?? '' );
					$title = (string) ( $item['hp_timeline_title'] ?? '' );
					$body  = (string) ( $item['hp_timeline_body']  ?? '' );

					if ( ! $year && ! $title && ! $body ) {
						continue;
					}
				?>
				<li class="history-timeline__item">

					<?php if ( $year ) : ?>
					<div class="history-timeline__marker" aria-hidden="true">
						<span class="history-timeline__year"><?php echo esc_html( $year ); ?></span>
					</div>
					<?php endif; ?>

					<article class="history-timeline__card">

						<?php if ( $title ) : ?>
						<h3 class="history-timeline__card-title"><?php echo esc_html( $title ); ?></h3>
						<?php endif; ?>

						<?php if ( $body ) : ?>
						<div class="history-timeline__card-body">
							<?php echo wp_kses_post( $body ); ?>
						</div>
						<?php endif; ?>

					</article>

				</li>
				<?php endforeach; ?>

			</ol><!-- .history-timeline -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .history-content -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 3: Page CTA — badge, heading, subtitle, two action links
	   ================================================================ */ ?>
	<?php if ( $hp_cta_heading || $hp_cta_primary || $hp_cta_secondary ) : ?>
	<section class="section page-cta" aria-labelledby="history-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $hp_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $hp_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $hp_cta_heading ) : ?>
				<h2 id="history-cta-heading" class="section__title">
					<?php echo esc_html( $hp_cta_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $hp_cta_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $hp_cta_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $hp_cta_primary || $hp_cta_secondary ) : ?>
				<div class="page-cta__actions">

					<?php if ( $hp_cta_primary ) :
						$cta_p_url    = esc_url( $hp_cta_primary['url'] ?? '' );
						$cta_p_title  = esc_html( $hp_cta_primary['title'] ?? '' );
						$cta_p_target = ! empty( $hp_cta_primary['target'] ) ? ' target="' . esc_attr( $hp_cta_primary['target'] ) . '" rel="noopener noreferrer"' : '';
					?>
					<a href="<?php echo $cta_p_url; ?>" class="btn btn--primary"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( $hp_cta_secondary ) :
						$cta_s_url    = esc_url( $hp_cta_secondary['url'] ?? '' );
						$cta_s_title  = esc_html( $hp_cta_secondary['title'] ?? '' );
						$cta_s_target = ! empty( $hp_cta_secondary['target'] ) ? ' target="' . esc_attr( $hp_cta_secondary['target'] ) . '" rel="noopener noreferrer"' : '';
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

</main><!-- #main-content -->

<?php get_footer(); ?>
