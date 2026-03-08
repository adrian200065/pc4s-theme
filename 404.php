<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * Renders the full-page 404 error layout, including the standard page banner
 * (which outputs "Page Not Found" automatically via parts/content/page-banner.php
 * when is_404() is true), a large decorative 404 code, a content block with
 * CTA buttons, and a quick-links panel for common site destinations.
 *
 * Styling: src/scss/layout/_404-page.scss
 *
 * @package PC4S
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="main-content">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION: 404 Error
	   ================================================================ */ ?>
	<section class="section error-404" aria-labelledby="error-404-heading">
		<div class="wrapper">
			<div class="error-404__inner">

				<?php /* Decorative radial background glow — purely visual */ ?>
				<div class="error-404__decoration" aria-hidden="true"></div>

				<?php /* Large decorative "404" numeral */ ?>
				<div class="error-404__visual" aria-hidden="true">
					<div class="error-404__glow"></div>
					<span class="error-404__code">404</span>
				</div>

				<?php /* Primary content block */ ?>
				<div class="error-404__content">

					<div class="error-404__badge" aria-hidden="true">
						<span class="error-404__badge-dot"></span>
						<span class="error-404__badge-text"><?php esc_html_e( 'Error', 'pc4s' ); ?></span>
					</div>

					<h2 class="error-404__title" id="error-404-heading">
						<?php esc_html_e( 'Oops! This page has gone missing.', 'pc4s' ); ?>
					</h2>

					<p class="error-404__description">
						<?php esc_html_e( "The page you\u{2019}re looking for doesn\u{2019}t exist, was moved, or the link may be broken. Let\u{2019}s get you back on track.", 'pc4s' ); ?>
					</p>

					<div class="error-404__actions">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
							<?php esc_html_e( 'Back to Home', 'pc4s' ); ?>
						</a>
						<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" class="btn btn--outline">
							<?php esc_html_e( 'Contact Us', 'pc4s' ); ?>
						</a>
					</div>

				</div><!-- .error-404__content -->

				<?php /* Helpful quick-links panel */ ?>
				<nav class="error-404__links" aria-labelledby="error-404-links-heading">

					<p class="error-404__links-heading" id="error-404-links-heading">
						<?php esc_html_e( 'Or explore a section below', 'pc4s' ); ?>
					</p>

					<?php
					// Quick-links definition: label → site-root-relative path.
					$quick_links = [
						__( 'Our History',        'pc4s' ) => '/our-history/',
						__( 'Events',             'pc4s' ) => '/events/',
						__( 'News',               'pc4s' ) => '/news/',
						__( 'Family & Community', 'pc4s' ) => '/family-community/',
						__( 'Contact Us',         'pc4s' ) => '/contact-us/',
						__( 'Our Work',           'pc4s' ) => '/our-work/',
					];
					?>

					<ul class="error-404__links-list" role="list">
						<?php foreach ( $quick_links as $label => $path ) : ?>
						<li class="error-404__links-item">
							<a href="<?php echo esc_url( home_url( $path ) ); ?>">
								<svg
									viewBox="0 0 16 16"
									fill="none"
									stroke="currentColor"
									stroke-width="2"
									stroke-linecap="round"
									stroke-linejoin="round"
									aria-hidden="true"
									focusable="false"
								>
									<path d="M3 8l5-5 5 5M8 3v10" />
								</svg>
								<?php echo esc_html( $label ); ?>
							</a>
						</li>
						<?php endforeach; ?>
					</ul>

				</nav><!-- .error-404__links -->

			</div><!-- .error-404__inner -->
		</div><!-- .wrapper -->
	</section>

</main><!-- #main-content -->

<?php
get_footer();
