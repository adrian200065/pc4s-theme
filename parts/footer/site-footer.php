<?php
/**
 * Template part — Site Footer
 *
 * Renders the full `<footer>` element. All editable content (tagline, contact
 * info, newsletter copy, funding logo) is read from the pc4s_footer_settings
 * option via FooterSettings::get() which caches the option once per request.
 *
 * The footer brand logo is managed separately in
 * Appearance → Customize → Footer Branding (theme mod `footer_logo_id`).
 *
 * Navigation columns use wp_nav_menu() with Footer_Nav_Walker:
 *   - "Footer: Helpful Links"   → theme location `footer_helpful`
 *   - "Footer: What We Do"      → theme location `footer_what_we_do`
 *   - "Footer: Legal & Policies"→ theme location `footer_legal`
 *
 * @package PC4S
 * @subpackage Template_Parts/Footer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Admin\FooterSettings;
use PC4S\Classes\Footer_Nav_Walker;

// ── Resolve footer brand logo ────────────────────────────────────────────────
// Dedicated footer logo first (Customizer), falls back to header logo.
$footer_logo_id  = (int) get_theme_mod( 'footer_logo_id', 0 );
if ( ! $footer_logo_id ) {
	$footer_logo_id = (int) get_theme_mod( 'custom_logo', 0 );
}

// ── Read editable footer settings (single DB call, cached) ──────────────────
$tagline      = FooterSettings::get( 'tagline' );
$addr_line1   = FooterSettings::get( 'address_line1' );
$addr_line2   = FooterSettings::get( 'address_line2' );
$phone        = FooterSettings::get( 'phone' );
$email        = FooterSettings::get( 'email' );
$nl_heading   = FooterSettings::get( 'newsletter_heading', __( 'Newsletter', 'pc4s' ) );
$nl_text      = FooterSettings::get( 'newsletter_text' );
$nl_disclaimer = FooterSettings::get( 'newsletter_disclaimer' );
$funding_html = FooterSettings::get_funding_logo_html();

// ── Shared nav args ──────────────────────────────────────────────────────────
$nav_defaults = [
	'container'  => false,
	'items_wrap' => '<ul class="footer-links" role="list">%3$s</ul>',
	'fallback_cb' => false,
	'walker'     => new Footer_Nav_Walker(),
	'depth'      => 1,
];

?>
<footer class="site-footer" id="site-footer">
	<div class="wrapper">

		<!-- ── Main grid ──────────────────────────────────────────────────── -->
		<div class="footer-grid">

			<!-- Column 1 — Brand -->
			<div class="footer-column footer-column--brand">

				<!-- Logo -->
				<?php if ( $footer_logo_id ) : ?>
				<a
					href="<?php echo esc_url( home_url( '/' ) ); ?>"
					class="footer-logo-link"
					rel="home"
					aria-label="<?php echo esc_attr( sprintf( __( '%s — Home', 'pc4s' ), get_bloginfo( 'name' ) ) ); ?>"
				>
					<?php
					echo wp_get_attachment_image(
						$footer_logo_id,
						'full',
						false,
						[
							'class'    => 'footer-logo',
							'loading'  => 'lazy',
							'decoding' => 'async',
						]
					);
					?>
				</a>
				<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo-link" rel="home">
					<span class="footer-logo-text"><?php bloginfo( 'name' ); ?></span>
				</a>
				<?php endif; ?>

				<!-- Tagline -->
				<?php if ( $tagline ) : ?>
				<p class="footer-text"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

				<!-- Contact information -->
				<?php if ( $addr_line1 || $phone || $email ) : ?>
				<address class="footer-contact">

					<?php if ( $addr_line1 ) : ?>
					<div class="contact-item">
						<!-- Location pin icon -->
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
							<circle cx="12" cy="10" r="3"></circle>
						</svg>
						<span>
							<?php echo esc_html( $addr_line1 ); ?>
							<?php if ( $addr_line2 ) : ?>
							<br /><?php echo esc_html( $addr_line2 ); ?>
							<?php endif; ?>
						</span>
					</div>
					<?php endif; ?>

					<?php if ( $phone ) : ?>
					<div class="contact-item">
						<!-- Phone icon -->
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
							<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 5.19 12.9 19.79 19.79 0 0 1 2.12 4.27 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
						</svg>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
							<?php echo esc_html( $phone ); ?>
						</a>
					</div>
					<?php endif; ?>

					<?php if ( $email ) : ?>
					<div class="contact-item">
						<!-- Email icon -->
						<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
							<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
							<polyline points="22,6 12,13 2,6"></polyline>
						</svg>
						<a href="mailto:<?php echo esc_attr( $email ); ?>">
							<?php echo esc_html( $email ); ?>
						</a>
					</div>
					<?php endif; ?>

				</address>
				<?php endif; ?>

			</div><!-- .footer-column--brand -->

			<!-- Column 2 — Helpful Links (nav menu) -->
			<div class="footer-column">
				<h2 class="footer-heading"><?php esc_html_e( 'Helpful Links', 'pc4s' ); ?></h2>
				<?php
				wp_nav_menu( array_merge( $nav_defaults, [
					'theme_location' => 'footer_helpful',
				] ) );
				?>
			</div>

			<!-- Column 3 — What We Do (nav menu) -->
			<div class="footer-column">
				<h2 class="footer-heading"><?php esc_html_e( 'What We Do', 'pc4s' ); ?></h2>
				<?php
				wp_nav_menu( array_merge( $nav_defaults, [
					'theme_location' => 'footer_what_we_do',
				] ) );
				?>
			</div>

			<!-- Column 4 — Newsletter -->
			<div class="footer-column footer-column--newsletter">
				<h2 class="footer-heading"><?php echo esc_html( $nl_heading ); ?></h2>

				<?php if ( $nl_text ) : ?>
				<p class="footer-text"><?php echo esc_html( $nl_text ); ?></p>
				<?php endif; ?>

				<?php
				/**
				 * Fires inside the footer newsletter column, after the description text.
				 *
				 * Hook here to inject a subscription form, e.g.:
				 *
				 *   add_action( 'pc4s_footer_newsletter_form', function() {
				 *       echo do_shortcode( '[contact-form-7 id="123"]' );
				 *   } );
				 */
				do_action( 'pc4s_footer_newsletter_form' );
				?>

				<?php if ( $nl_disclaimer ) : ?>
				<p class="footer-disclaimer">
					<?php echo wp_kses_post( $nl_disclaimer ); ?>
				</p>
				<?php endif; ?>

			</div><!-- .footer-column--newsletter -->

		</div><!-- .footer-grid -->

		<!-- ── Funding / TN Department logo ─────────────────────────────── -->
		<?php if ( $funding_html ) : ?>
		<div class="footer-funding">
			<?php echo $funding_html; // Escaped inside get_funding_logo_html(). ?>
		</div>
		<?php endif; ?>

		<!-- ── Footer bottom bar ─────────────────────────────────────────── -->
		<div class="footer-bottom">

			<p class="copyright">
				<?php
				printf(
					/* translators: 1: copyright symbol + year, 2: site name */
					'&copy; %1$s %2$s. %3$s',
					esc_html( date_i18n( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) ),
					esc_html__( 'All Rights Reserved.', 'pc4s' )
				);
				?>
			</p>

			<nav
				class="footer-legal"
				aria-label="<?php esc_attr_e( 'Legal links', 'pc4s' ); ?>"
			>
				<?php
				wp_nav_menu( [
					'theme_location' => 'footer_legal',
					'container'      => false,
					'items_wrap'     => '<ul role="list">%3$s</ul>',
					'fallback_cb'    => false,
					'walker'         => new Footer_Nav_Walker(),
					'depth'          => 1,
				] );
				?>
			</nav><!-- .footer-legal -->

		</div><!-- .footer-bottom -->

	</div><!-- .wrapper -->
</footer><!-- .site-footer -->
