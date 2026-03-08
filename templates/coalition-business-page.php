<?php
/**
 * Template Name: Coalition Business & Title VI
 * Template Post Type: page
 *
 * Page template for the Coalition Business & Title VI resources page.
 *
 * Sections rendered (in order):
 *   1. Page Banner         — parts/content/page-banner.php
 *   2. Intro Split         — badge, heading, subtitle, body, CTA | partnership benefits panel
 *   3. Coalition Documents — repeater of downloadable PDF cards
 *   4. Title VI            — civil rights compliance 3-card grid
 *   5. Page CTA            — badge, heading, subtitle, two action links
 *
 * ACF field group : group_coalition_business_page
 *   → acf-json/group_coalition_business_page.json
 *   Fields are prefixed cbp_ (coalition business page).
 *
 * Contact info (address, phone, email) is sourced from PC4S → Settings
 * via FooterSettings::get() so updates in the admin propagate here
 * automatically. The Title VI grievance contact email is pulled from the
 * same source rather than being hardcoded.
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Admin\FooterSettings;

// ---------------------------------------------------------------------------
// ACF fields — resolved once at the top; zero duplicate DB calls in markup.
// ---------------------------------------------------------------------------

// --- Intro Section ---
$cbp_intro_badge        = (string) get_field( 'cbp_intro_badge' );
$cbp_intro_heading      = (string) get_field( 'cbp_intro_heading' );
$cbp_intro_subtitle     = (string) get_field( 'cbp_intro_subtitle' );
$cbp_intro_body         = (string) get_field( 'cbp_intro_body' );
$cbp_intro_cta_label    = (string) get_field( 'cbp_intro_cta_label' );
$cbp_intro_panel_heading = (string) get_field( 'cbp_intro_panel_heading' );
$cbp_intro_panel_items  = (array)  get_field( 'cbp_intro_panel_items' ) ?: [];

// --- Coalition Documents Section ---
$cbp_docs_badge    = (string) get_field( 'cbp_docs_badge' );
$cbp_docs_heading  = (string) get_field( 'cbp_docs_heading' );
$cbp_docs_subtitle = (string) get_field( 'cbp_docs_subtitle' );
$cbp_documents     = (array)  get_field( 'cbp_documents' ) ?: [];

// --- Title VI Section ---
$cbp_t6_badge    = (string) get_field( 'cbp_t6_badge' );
$cbp_t6_heading  = (string) get_field( 'cbp_t6_heading' );
$cbp_t6_subtitle = (string) get_field( 'cbp_t6_subtitle' );
$cbp_t6_cards    = (array)  get_field( 'cbp_t6_cards' ) ?: [];

// --- CTA Section ---
$cbp_cta_badge     = (string) get_field( 'cbp_cta_badge' );
$cbp_cta_heading   = (string) get_field( 'cbp_cta_heading' );
$cbp_cta_subtitle  = (string) get_field( 'cbp_cta_subtitle' );
$cbp_cta_primary   = get_field( 'cbp_cta_primary' );   // link array|null
$cbp_cta_secondary = get_field( 'cbp_cta_secondary' ); // link array|null

// --- Contact info from Footer Settings (shared with footer) ---
$contact_email = FooterSettings::get( 'email' );

// ---------------------------------------------------------------------------
// Template
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main" class="site-main" role="main">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<!-- ====================================================================
	     SECTION 1 — INTRO SPLIT
	     Left: mission copy + CTA  |  Right: partnership benefits panel
	     ==================================================================== -->
	<?php if ( $cbp_intro_heading ) : ?>
	<section class="section cb-intro" aria-labelledby="cb-intro-heading">
		<div class="wrapper">
			<div class="cb-intro__inner">

				<!-- Left — copy + CTA -->
				<div class="cb-intro__copy">
					<?php if ( $cbp_intro_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $cbp_intro_badge ); ?></span>
					</div>
					<?php endif; ?>

					<h2 id="cb-intro-heading" class="section__title">
						<?php echo esc_html( $cbp_intro_heading ); ?>
					</h2>

					<?php if ( $cbp_intro_subtitle ) : ?>
					<p class="section__subtitle">
						<?php echo wp_kses_post( $cbp_intro_subtitle ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $cbp_intro_body ) : ?>
					<p class="cb-intro__lead">
						<?php echo wp_kses_post( $cbp_intro_body ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $cbp_intro_cta_label ) : ?>
					<a href="#cb-documents" class="btn btn--primary">
						<?php echo esc_html( $cbp_intro_cta_label ); ?>
						<svg
							aria-hidden="true"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							style="inline-size: 1em; block-size: 1em"
						>
							<path d="M12 5v14M5 12l7 7 7-7" />
						</svg>
					</a>
					<?php endif; ?>
				</div><!-- /.cb-intro__copy -->

				<!-- Right — partnership benefits panel -->
				<?php if ( ! empty( $cbp_intro_panel_items ) ) : ?>
				<aside class="cb-intro__panel" aria-label="Coalition business highlights">
					<?php if ( $cbp_intro_panel_heading ) : ?>
					<h3 class="cb-intro__panel-heading">
						<?php echo esc_html( $cbp_intro_panel_heading ); ?>
					</h3>
					<?php endif; ?>

					<ul class="cb-intro__panel-list" role="list">
						<?php foreach ( $cbp_intro_panel_items as $item ) :
							$item_text = (string) ( $item['cbp_panel_item_text'] ?? '' );
							if ( ! $item_text ) {
								continue;
							}
						?>
						<li class="cb-intro__panel-item">
							<?php echo wp_kses_post( $item_text ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</aside>
				<?php endif; ?>

			</div><!-- /.cb-intro__inner -->
		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 2 — COALITION DOCUMENTS
	     Downloadable PDF cards — ACF repeater
	     ==================================================================== -->
	<?php if ( ! empty( $cbp_documents ) ) : ?>
	<section
		class="section rrf-docs"
		id="cb-documents"
		aria-labelledby="cb-docs-heading"
	>
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $cbp_docs_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $cbp_docs_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $cbp_docs_heading ) : ?>
				<h2 id="cb-docs-heading" class="section__title">
					<?php echo esc_html( $cbp_docs_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $cbp_docs_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $cbp_docs_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<ul class="rrf-docs__list" role="list">
				<?php foreach ( $cbp_documents as $doc ) :
					$doc_title   = (string) ( $doc['cbp_doc_title'] ?? '' );
					$doc_desc    = (string) ( $doc['cbp_doc_desc']  ?? '' );
					$doc_tag     = (string) ( $doc['cbp_doc_tag']   ?? 'PDF' );
					$doc_url     = (string) ( $doc['cbp_doc_file'] ?? '' ); // Google Drive URL

					if ( ! $doc_title || ! $doc_url ) {
						continue;
					}
				?>
				<li class="rrf-doc-item">
					<a
						href="<?php echo esc_url( $doc_url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						aria-label="<?php echo esc_attr( 'Open ' . $doc_title . ( $doc_tag ? ' (' . $doc_tag . ')' : '' ) ); ?>"
					>
						<div class="rrf-doc-item__icon" aria-hidden="true">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round"
							>
								<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
								<polyline points="14 2 14 8 20 8" />
								<line x1="16" y1="13" x2="8" y2="13" />
								<line x1="16" y1="17" x2="8" y2="17" />
								<polyline points="10 9 9 9 8 9" />
							</svg>
						</div>

						<div class="rrf-doc-item__body">
							<?php if ( $doc_tag ) : ?>
							<span class="rrf-doc-item__tag"><?php echo esc_html( $doc_tag ); ?></span>
							<?php endif; ?>

							<span class="rrf-doc-item__title"><?php echo esc_html( $doc_title ); ?></span>

							<?php if ( $doc_desc ) : ?>
							<span class="rrf-doc-item__desc"><?php echo wp_kses_post( $doc_desc ); ?></span>
							<?php endif; ?>
						</div>

						<svg
							class="rrf-doc-item__arrow"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							aria-hidden="true"
						>
							<line x1="7" y1="17" x2="17" y2="7" />
							<polyline points="7 7 17 7 17 17" />
						</svg>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 3 — TITLE VI INFORMATION
	     Civil rights compliance cards — ACF repeater
	     Contact email sourced from FooterSettings (shared with footer)
	     ==================================================================== -->
	<?php if ( ! empty( $cbp_t6_cards ) ) : ?>
	<section class="section cb-title6" aria-labelledby="cb-title6-heading">
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $cbp_t6_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $cbp_t6_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $cbp_t6_heading ) : ?>
				<h2 id="cb-title6-heading" class="section__title">
					<?php echo esc_html( $cbp_t6_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $cbp_t6_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $cbp_t6_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<div class="cb-title6__grid">
				<?php foreach ( $cbp_t6_cards as $card ) :
					$t6_title = (string) ( $card['cbp_t6_card_title'] ?? '' );
					$t6_text  = (string) ( $card['cbp_t6_card_text']  ?? '' );

					if ( ! $t6_title ) {
						continue;
					}

					// Replace the {{contact_email}} placeholder with the
					// live value from FooterSettings so the admin doesn't need
					// to re-enter it here when the contact email changes.
					if ( $contact_email ) {
						$email_link = '<a href="mailto:' . esc_attr( $contact_email ) . '" style="color:var(--clr-secondary-200)">'
							. esc_html( $contact_email )
							. '</a>';
						$t6_text = str_replace( '{{contact_email}}', $email_link, $t6_text );
					}
				?>
				<article class="cb-title6__item">
					<div class="cb-title6__item-icon" aria-hidden="true">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
						</svg>
					</div>
					<h3 class="cb-title6__item-title"><?php echo esc_html( $t6_title ); ?></h3>
					<?php if ( $t6_text ) : ?>
					<p class="cb-title6__item-text">
						<?php echo wp_kses_post( $t6_text ); ?>
					</p>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div><!-- /.cb-title6__grid -->

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 4 — PAGE CTA
	     ==================================================================== -->
	<?php if ( $cbp_cta_heading ) : ?>
	<section class="section page-cta" aria-labelledby="cb-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $cbp_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $cbp_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="cb-cta-heading" class="section__title">
					<?php echo esc_html( $cbp_cta_heading ); ?>
				</h2>

				<?php if ( $cbp_cta_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $cbp_cta_subtitle ); ?>
				</p>
				<?php endif; ?>

				<?php if ( $cbp_cta_primary || $cbp_cta_secondary ) : ?>
				<div class="page-cta__actions">
					<?php if ( ! empty( $cbp_cta_primary ) ) :
						$cta_p_url    = esc_url( $cbp_cta_primary['url'] ?? '#' );
						$cta_p_title  = esc_html( $cbp_cta_primary['title'] ?? 'Learn More' );
						$cta_p_target = ! empty( $cbp_cta_primary['target'] )
							? ' target="' . esc_attr( $cbp_cta_primary['target'] ) . '"'
							: '';
					?>
					<a href="<?php echo $cta_p_url; ?>"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--primary">
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( ! empty( $cbp_cta_secondary ) ) :
						$cta_s_url    = esc_url( $cbp_cta_secondary['url'] ?? '#' );
						$cta_s_title  = esc_html( $cbp_cta_secondary['title'] ?? 'Learn More' );
						$cta_s_target = ! empty( $cbp_cta_secondary['target'] )
							? ' target="' . esc_attr( $cbp_cta_secondary['target'] ) . '"'
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
