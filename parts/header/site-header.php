<?php
/**
 * Template part for displaying the site header
 *
 * Mirrors the static-site/index.html header structure exactly:
 *  - Logo (via Helper::get_logo)
 *  - Mobile hamburger toggle
 *  - Main navigation (wp_nav_menu + Nav_Walker)
 *
 * @package PC4S
 * @subpackage Template_Parts/Header
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<header class="site-header" id="site-header">
	<div class="wrapper header-inner">

		<!-- Logo -->
		<?php echo PC4S\Classes\Helper::get_logo( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<!-- Mobile Menu Toggle -->
		<button
			class="menu-toggle"
			id="menu-toggle"
			aria-expanded="false"
			aria-controls="main-navigation"
			aria-label="<?php esc_attr_e( 'Toggle navigation menu', 'pc4s' ); ?>"
		>
			<span class="hamburger" aria-hidden="true">
				<span class="hamburger-line"></span>
				<span class="hamburger-line"></span>
				<span class="hamburger-line"></span>
			</span>
		</button>

		<!-- Main Navigation -->
		<nav class="main-navigation" id="main-navigation" aria-label="<?php esc_attr_e( 'Main navigation', 'pc4s' ); ?>">
			<?php
			wp_nav_menu(
				[
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '<ul class="nav-list" role="list">%3$s</ul>',
					'walker'         => new PC4S\Classes\Nav_Walker(),
					'fallback_cb'    => false,
				]
			);
			?>
		</nav><!-- #main-navigation -->

	</div><!-- .wrapper.header-inner -->
</header><!-- #site-header -->
