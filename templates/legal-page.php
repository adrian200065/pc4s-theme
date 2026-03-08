<?php
/**
 * Template Name: Legal Page
 * Template Post Type: page
 *
 * Shared page template for legal documents (Privacy Policy, Terms of Service, etc.).
 *
 * Structure rendered (in order):
 *   1. Page Banner      — parts/content/page-banner.php (title + breadcrumbs)
 *   2. Legal Article    — article.legal > .wrapper > .legal__content
 *        a. Meta line   — optional "Last updated on …" text (p.legal__meta)
 *        b. Sections    — repeater of titled sections (h2 + wysiwyg body)
 *
 * ACF field group: group_legal_page (acf-json/group_legal_page.json)
 *   — Field prefix: lp_
 *   — Location: Page Template == legal-page.php
 *
 * Both Privacy Policy and Terms of Service use this single template.
 * The Page Banner title and breadcrumbs are driven by the WordPress page title,
 * so no duplicate ACF fields are needed for the document title itself.
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

$lp_meta     = (string) get_field( 'lp_meta' );      // e.g. "Last updated on April 10, 2023."
$lp_sections = (array)  get_field( 'lp_sections' ) ?: [];

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<article class="legal" aria-labelledby="legal-page-heading">
	<div class="wrapper">
		<div class="legal__content">

			<?php if ( $lp_meta ) : ?>
			<p class="legal__meta"><?php echo esc_html( $lp_meta ); ?></p>
			<?php endif; ?>

			<?php
			foreach ( $lp_sections as $section ) :
				$section_title   = (string) ( $section['section_title']   ?? '' );
				$section_content = (string) ( $section['section_content'] ?? '' );

				if ( ! $section_title && ! $section_content ) {
					continue;
				}

				/*
				 * Generate a stable, URL-safe anchor id from the section title.
				 * sanitize_title() converts "Mobile Device Privacy" → "mobile-device-privacy".
				 */
				$section_id = $section_title ? 'legal-' . sanitize_title( $section_title ) : '';
			?>
			<section
				class="legal__section"
				<?php if ( $section_id ) : ?>aria-labelledby="<?php echo esc_attr( $section_id ); ?>"<?php endif; ?>
			>
				<?php if ( $section_title ) : ?>
				<h2
					class="legal__title"
					<?php if ( $section_id ) : ?>id="<?php echo esc_attr( $section_id ); ?>"<?php endif; ?>
				><?php echo esc_html( $section_title ); ?></h2>
				<?php endif; ?>

				<?php if ( $section_content ) : ?>
				<div class="legal__body">
					<?php echo wp_kses_post( $section_content ); ?>
				</div>
				<?php endif; ?>

			</section>
			<?php endforeach; ?>

		</div><!-- .legal__content -->
	</div><!-- .wrapper -->
</article><!-- .legal -->

<?php get_footer(); ?>
