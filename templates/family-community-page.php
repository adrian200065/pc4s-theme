<?php
/**
 * Template Name: Family & Community
 * Template Post Type: page
 *
 * Page template for the Family & Community resources page.
 *
 * Sections rendered (in order):
 *   1. Page Banner       — parts/content/page-banner.php
 *   2. Intro Split       — badge, heading, body copy (2 paras), CTA, highlights list
 *   3. Resource Downloads — three PDF download cards (ACF repeater)
 *   4. External Links    — quick-link cards to trusted external resources (ACF repeater)
 *   5. Find Us / Map     — info panel (FooterSettings) + Google Maps iframe
 *   6. Page CTA          — badge, heading, subtitle, two action links
 *
 * ACF field group : group_family_community_page
 *   → acf-json/group_family_community_page.json
 *   Fields are prefixed fcp_ (family community page).
 *
 * Contact info (address, phone, email) is sourced from PC4S → Settings
 * via FooterSettings::get(). Office hours are an ACF field so they can
 * differ from the footer without touching server settings.
 * The Google Maps embed `src` URL is stored in fcp_map_embed_url so the
 * admin can paste a fresh embed code without touching code.
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Admin\FooterSettings;

// ---------------------------------------------------------------------------
// ACF fields — resolved once; zero duplicate DB calls in markup.
// ---------------------------------------------------------------------------

// --- Intro Section ---
$fcp_intro_badge      = (string) get_field( 'fcp_intro_badge' );
$fcp_intro_heading    = (string) get_field( 'fcp_intro_heading' );
$fcp_intro_body_1     = (string) get_field( 'fcp_intro_body_1' );
$fcp_intro_body_2     = (string) get_field( 'fcp_intro_body_2' );
$fcp_intro_cta_label  = (string) get_field( 'fcp_intro_cta_label' );
$fcp_intro_highlights = (array)  get_field( 'fcp_intro_highlights' ) ?: [];

// --- Resource Downloads Section ---
$fcp_res_badge    = (string) get_field( 'fcp_res_badge' );
$fcp_res_heading  = (string) get_field( 'fcp_res_heading' );
$fcp_res_subtitle = (string) get_field( 'fcp_res_subtitle' );
$fcp_resource_cards = (array) get_field( 'fcp_resource_cards' ) ?: [];

// --- External Links Section ---
$fcp_ext_badge       = (string) get_field( 'fcp_ext_badge' );
$fcp_ext_heading     = (string) get_field( 'fcp_ext_heading' );
$fcp_ext_subtitle    = (string) get_field( 'fcp_ext_subtitle' );
$fcp_external_links  = (array)  get_field( 'fcp_external_links' ) ?: [];

// --- Location / Map Section ---
$fcp_loc_badge      = (string) get_field( 'fcp_loc_badge' );
$fcp_loc_heading    = (string) get_field( 'fcp_loc_heading' );
$fcp_loc_subtitle   = (string) get_field( 'fcp_loc_subtitle' );
$fcp_office_hours   = (string) get_field( 'fcp_office_hours' );
$fcp_map_embed_url  = (string) get_field( 'fcp_map_embed_url' );

// --- CTA Section ---
$fcp_cta_badge     = (string) get_field( 'fcp_cta_badge' );
$fcp_cta_heading   = (string) get_field( 'fcp_cta_heading' );
$fcp_cta_subtitle  = (string) get_field( 'fcp_cta_subtitle' );
$fcp_cta_primary   = get_field( 'fcp_cta_primary' );   // link array|null
$fcp_cta_secondary = get_field( 'fcp_cta_secondary' ); // link array|null

// --- Contact info from Footer Settings ---
$loc_addr1 = FooterSettings::get( 'address_line1' );
$loc_addr2 = FooterSettings::get( 'address_line2' );
$loc_phone = FooterSettings::get( 'phone' );
$loc_email = FooterSettings::get( 'email' );

// Build the "Get Directions" href from the Footer Settings address.
$maps_href = 'https://maps.google.com/?q=' . rawurlencode(
	trim( $loc_addr1 . ' ' . $loc_addr2 )
);

// ---------------------------------------------------------------------------
// Template
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main" class="site-main" role="main">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<!-- ====================================================================
	     SECTION 1 — INTRO SPLIT
	     Two-column: mission copy left, resource highlights right
	     ==================================================================== -->
	<?php if ( $fcp_intro_heading ) : ?>
	<section class="section fc-intro" aria-labelledby="fc-intro-heading">
		<div class="wrapper">
			<div class="fc-intro__inner">

				<!-- Left — copy + CTA -->
				<div class="fc-intro__copy">
					<?php if ( $fcp_intro_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $fcp_intro_badge ); ?></span>
					</div>
					<?php endif; ?>

					<h2 id="fc-intro-heading" class="section__title">
						<?php echo esc_html( $fcp_intro_heading ); ?>
					</h2>

					<?php if ( $fcp_intro_body_1 ) : ?>
					<p class="section__subtitle">
						<?php echo wp_kses_post( $fcp_intro_body_1 ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $fcp_intro_body_2 ) : ?>
					<p class="fc-intro__body">
						<?php echo wp_kses_post( $fcp_intro_body_2 ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $fcp_intro_cta_label ) : ?>
					<a href="#resource-list" class="btn btn--primary">
						<?php echo esc_html( $fcp_intro_cta_label ); ?>
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" width="24" height="24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="12" y1="5" x2="12" y2="19"></line>
							<polyline points="19 12 12 19 5 12"></polyline>
						</svg>
					</a>
					<?php endif; ?>
				</div><!-- /.fc-intro__copy -->

				<!-- Right — highlights list -->
				<?php if ( ! empty( $fcp_intro_highlights ) ) : ?>
				<aside class="fc-intro__highlights" aria-label="Resource highlights">
					<ul class="fc-intro__highlights-list" role="list">
						<?php foreach ( $fcp_intro_highlights as $highlight ) :
							$hl_title    = (string) ( $highlight['fcp_highlight_title']    ?? '' );
							$hl_subtitle = (string) ( $highlight['fcp_highlight_subtitle'] ?? '' );
							if ( ! $hl_title ) {
								continue;
							}
						?>
						<li class="fc-intro__highlight-item">
							<div class="fc-intro__highlight-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<circle cx="12" cy="12" r="10"></circle>
									<polyline points="12 6 12 12 16 14"></polyline>
								</svg>
							</div>
							<div class="fc-intro__highlight-text">
								<strong><?php echo esc_html( $hl_title ); ?></strong>
								<?php if ( $hl_subtitle ) : ?>
								<span><?php echo esc_html( $hl_subtitle ); ?></span>
								<?php endif; ?>
							</div>
						</li>
						<?php endforeach; ?>
					</ul>
				</aside>
				<?php endif; ?>

			</div><!-- /.fc-intro__inner -->
		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 2 — RUTHERFORD COUNTY RESOURCE LIST
	     Three PDF download cards
	     ==================================================================== -->
	<?php if ( ! empty( $fcp_resource_cards ) ) : ?>
	<section
		class="section fc-resources"
		id="resource-list"
		aria-labelledby="resource-list-heading"
	>
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $fcp_res_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $fcp_res_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $fcp_res_heading ) : ?>
				<h2 id="resource-list-heading" class="section__title">
					<?php echo esc_html( $fcp_res_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $fcp_res_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $fcp_res_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<ol class="fc-resource-list" role="list" aria-label="Downloadable resource guides">
				<?php foreach ( $fcp_resource_cards as $index => $card ) :
					$card_category  = (string) ( $card['fcp_card_category']   ?? '' );
					$card_title     = (string) ( $card['fcp_card_title']      ?? '' );
					$card_desc      = (string) ( $card['fcp_card_desc']       ?? '' );
					$card_date      = (string) ( $card['fcp_card_date_label'] ?? '' );
					$card_file      = $card['fcp_card_file'] ?? null; // ACF file array
					$card_file_url  = is_array( $card_file ) ? ( $card_file['url'] ?? '' ) : (string) $card_file;
					$card_file_name = is_array( $card_file ) ? ( basename( $card_file['filename'] ?? $card_title ) ) : '';

					if ( ! $card_title || ! $card_file_url ) {
						continue;
					}

					$card_id = 'res-card-' . ( $index + 1 );
				?>
				<li class="fc-resource-card">
					<article aria-labelledby="<?php echo esc_attr( $card_id ); ?>">
						<div class="fc-resource-card__icon-wrap" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
								<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
								<circle cx="9" cy="7" r="4"></circle>
								<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
								<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
							</svg>
						</div>

						<div class="fc-resource-card__body">
							<?php if ( $card_category ) : ?>
							<div class="fc-resource-card__category">
								<?php echo esc_html( $card_category ); ?>
							</div>
							<?php endif; ?>

							<h3 id="<?php echo esc_attr( $card_id ); ?>" class="fc-resource-card__title">
								<?php echo esc_html( $card_title ); ?>
							</h3>

							<?php if ( $card_desc ) : ?>
							<p class="fc-resource-card__desc">
								<?php echo wp_kses_post( $card_desc ); ?>
							</p>
							<?php endif; ?>

							<div class="fc-resource-card__meta">
								<?php if ( $card_date ) : ?>
								<span class="fc-resource-card__badge">
									<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
										<line x1="16" y1="2" x2="16" y2="6"></line>
										<line x1="8" y1="2" x2="8" y2="6"></line>
										<line x1="3" y1="10" x2="21" y2="10"></line>
									</svg>
									<?php echo esc_html( $card_date ); ?>
								</span>
								<?php endif; ?>

								<span class="fc-resource-card__badge">
									<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<polyline points="14 2 14 8 20 8"></polyline>
										<path d="M20 20H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10l6 6v14a2 2 0 0 1-2 2z"></path>
									</svg>
									PDF Document
								</span>
							</div><!-- /.fc-resource-card__meta -->

						</div><!-- /.fc-resource-card__body -->

						<div class="fc-resource-card__actions">
							<a
								href="<?php echo esc_url( $card_file_url ); ?>"
								class="btn btn--primary fc-resource-card__btn"
								download
								aria-label="<?php echo esc_attr( 'Download ' . $card_title . ' (PDF)' ); ?>"
							>
								<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
									<polyline points="7 10 12 15 17 10"></polyline>
									<line x1="12" y1="15" x2="12" y2="3"></line>
								</svg>
								Download
							</a>

							<a
								href="<?php echo esc_url( $card_file_url ); ?>"
								class="btn btn--outline fc-resource-card__btn"
								target="_blank"
								rel="noopener noreferrer"
								aria-label="<?php echo esc_attr( 'View ' . $card_title . ' in browser (opens in new tab)' ); ?>"
							>
								<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
									<circle cx="12" cy="12" r="3"></circle>
								</svg>
								View
							</a>
						</div><!-- /.fc-resource-card__actions -->
					</article>
				</li>
				<?php endforeach; ?>
			</ol>

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 3 — EXTERNAL RESOURCE LINKS
	     Quick-link cards to trusted external community resources
	     ==================================================================== -->
	<?php if ( ! empty( $fcp_external_links ) ) : ?>
	<section class="section fc-external" aria-labelledby="fc-external-heading">
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $fcp_ext_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $fcp_ext_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $fcp_ext_heading ) : ?>
				<h2 id="fc-external-heading" class="section__title">
					<?php echo esc_html( $fcp_ext_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $fcp_ext_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $fcp_ext_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<ul class="fc-external-grid" role="list" aria-label="External community resource links">
				<?php foreach ( $fcp_external_links as $ext ) :
					$ext_url     = (string) ( $ext['fcp_link_url']    ?? '' );
					$ext_name    = (string) ( $ext['fcp_link_name']   ?? '' );
					$ext_desc    = (string) ( $ext['fcp_link_desc']   ?? '' );
					$ext_new_tab = ! empty( $ext['fcp_link_new_tab'] );

					if ( ! $ext_name || ! $ext_url ) {
						continue;
					}

					$ext_target = $ext_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
					$ext_aria   = esc_attr( $ext_name . ( $ext_new_tab ? ' — opens in new tab' : '' ) );
				?>
				<li class="fc-external-card">
					<a
						href="<?php echo esc_url( $ext_url ); ?>"
						<?php echo $ext_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						class="fc-external-card__link"
						aria-label="<?php echo $ext_aria; ?>"
					>
						<div class="fc-external-card__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10"></circle>
								<line x1="2" y1="12" x2="22" y2="12"></line>
								<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
							</svg>
						</div>
						<span class="fc-external-card__name"><?php echo esc_html( $ext_name ); ?></span>
						<?php if ( $ext_desc ) : ?>
						<span class="fc-external-card__desc"><?php echo esc_html( $ext_desc ); ?></span>
						<?php endif; ?>
						<svg
							class="fc-external-card__arrow"
							aria-hidden="true"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<line x1="7" y1="17" x2="17" y2="7"></line>
							<polyline points="7 7 17 7 17 17"></polyline>
						</svg>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>

		</div><!-- /.wrapper -->
	</section>
	<?php endif; ?>

	<!-- ====================================================================
	     SECTION 4 — FIND US / MAP
	     Info panel (FooterSettings) + Google Maps iframe
	     ==================================================================== -->
	<section class="section fc-location" aria-labelledby="fc-location-heading">
		<div class="wrapper">

			<div class="section__header">
				<?php if ( $fcp_loc_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $fcp_loc_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $fcp_loc_heading ) : ?>
				<h2 id="fc-location-heading" class="section__title">
					<?php echo esc_html( $fcp_loc_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $fcp_loc_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $fcp_loc_subtitle ); ?>
				</p>
				<?php endif; ?>
			</div><!-- /.section__header -->

			<div class="fc-location__inner">

				<!-- Info panel — contact data from FooterSettings -->
				<aside class="fc-location__info" aria-label="PC4S office contact information">

					<?php if ( $loc_addr1 || $loc_addr2 ) : ?>
					<div class="fc-location__info-block">
						<div class="fc-location__info-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
								<circle cx="12" cy="10" r="3"></circle>
							</svg>
						</div>
						<div class="fc-location__info-text">
							<h3 class="fc-location__info-label">Address</h3>
							<address class="fc-location__address">
								<?php if ( $loc_addr1 ) : ?>
									<?php echo esc_html( $loc_addr1 ); ?>
								<?php endif; ?>
								<?php if ( $loc_addr2 ) : ?>
									<br /><?php echo esc_html( $loc_addr2 ); ?>
								<?php endif; ?>
							</address>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $loc_phone ) : ?>
					<div class="fc-location__info-block">
						<div class="fc-location__info-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path>
							</svg>
						</div>
						<div class="fc-location__info-text">
							<h3 class="fc-location__info-label">Phone</h3>
							<a
								href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $loc_phone ) ); ?>"
								class="fc-location__contact-link"
							><?php echo esc_html( $loc_phone ); ?></a>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $loc_email ) : ?>
					<div class="fc-location__info-block">
						<div class="fc-location__info-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
								<polyline points="22,6 12,13 2,6"></polyline>
							</svg>
						</div>
						<div class="fc-location__info-text">
							<h3 class="fc-location__info-label">Email</h3>
							<a
								href="mailto:<?php echo esc_attr( $loc_email ); ?>"
								class="fc-location__contact-link"
							><?php echo esc_html( $loc_email ); ?></a>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $fcp_office_hours ) : ?>
					<div class="fc-location__info-block">
						<div class="fc-location__info-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10"></circle>
								<polyline points="12 6 12 12 16 14"></polyline>
							</svg>
						</div>
						<div class="fc-location__info-text">
							<h3 class="fc-location__info-label">Office Hours</h3>
							<p class="fc-location__hours">
								<?php echo wp_kses_post( nl2br( esc_html( $fcp_office_hours ) ) ); ?>
							</p>
						</div>
					</div>
					<?php endif; ?>

					<a
						href="<?php echo esc_url( $maps_href ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						class="btn btn--outline fc-location__directions-btn"
						aria-label="Get directions to PC4S office (opens Google Maps in new tab)"
					>
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
						</svg>
						Get Directions
					</a>

				</aside><!-- /.fc-location__info -->

				<!-- Map embed — src URL from ACF field fcp_map_embed_url -->
				<?php if ( $fcp_map_embed_url ) : ?>
				<div class="fc-location__map-wrap">
					<iframe
						class="fc-location__map"
						title="PC4S office location on Google Maps"
						src="<?php echo esc_url( $fcp_map_embed_url ); ?>"
						width="600"
						height="450"
						style="border:0"
						allowfullscreen=""
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"
						aria-label="<?php
							echo esc_attr(
								'Google Map showing PC4S location' .
								( $loc_addr1 ? ' at ' . $loc_addr1 . ( $loc_addr2 ? ', ' . $loc_addr2 : '' ) : '' )
							);
						?>"
					></iframe>
				</div>
				<?php endif; ?>

			</div><!-- /.fc-location__inner -->

		</div><!-- /.wrapper -->
	</section>

	<!-- ====================================================================
	     SECTION 5 — PAGE CTA
	     ==================================================================== -->
	<?php if ( $fcp_cta_heading ) : ?>
	<section class="section page-cta" aria-labelledby="fc-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $fcp_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $fcp_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="fc-cta-heading" class="section__title">
					<?php echo esc_html( $fcp_cta_heading ); ?>
				</h2>

				<?php if ( $fcp_cta_subtitle ) : ?>
				<p class="section__subtitle">
					<?php echo wp_kses_post( $fcp_cta_subtitle ); ?>
				</p>
				<?php endif; ?>

				<?php if ( $fcp_cta_primary || $fcp_cta_secondary ) : ?>
				<div class="page-cta__actions">
					<?php if ( ! empty( $fcp_cta_primary ) ) :
						$cta_p_url    = esc_url( $fcp_cta_primary['url'] ?? '#' );
						$cta_p_title  = esc_html( $fcp_cta_primary['title'] ?? 'Learn More' );
						$cta_p_target = ! empty( $fcp_cta_primary['target'] ) ? ' target="' . esc_attr( $fcp_cta_primary['target'] ) . '"' : '';
					?>
					<a href="<?php echo $cta_p_url; ?>"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn btn--primary">
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( ! empty( $fcp_cta_secondary ) ) :
						$cta_s_url    = esc_url( $fcp_cta_secondary['url'] ?? '#' );
						$cta_s_title  = esc_html( $fcp_cta_secondary['title'] ?? 'Learn More' );
						$cta_s_target = ! empty( $fcp_cta_secondary['target'] ) ? ' target="' . esc_attr( $fcp_cta_secondary['target'] ) . '"' : '';
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
