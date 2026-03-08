<?php
/**
 * Template part: Page Banner
 *
 * Renders the interior-page banner containing the page title, an optional
 * subtitle, and optional breadcrumb navigation.
 *
 * ACF fields (group_page_banner — assigned to pages, posts, and CPTs):
 *   page_banner_title            (text)       – overrides the WP page title.
 *   page_banner_subtitle         (text)       – optional supporting line.
 *   page_banner_show_breadcrumbs (true_false) – toggles breadcrumb nav.
 *
 * Usage:
 *   get_template_part( 'parts/content/page-banner' );
 *
 * @package PC4S
 * @subpackage Template_Parts/Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Classes\Breadcrumb;

// ---------------------------------------------------------------------------
// Resolve field values — cache once to avoid duplicate DB calls.
// ---------------------------------------------------------------------------
$banner_title    = function_exists( 'get_field' ) ? get_field( 'page_banner_title' ) : '';
$banner_subtitle = function_exists( 'get_field' ) ? get_field( 'page_banner_subtitle' ) : '';

// get_field returns null when the toggle is unset; treat null as the default (true).
$show_breadcrumbs_raw = function_exists( 'get_field' ) ? get_field( 'page_banner_show_breadcrumbs' ) : null;
$show_breadcrumbs     = ( null === $show_breadcrumbs_raw ) ? true : (bool) $show_breadcrumbs_raw;

// Fall back to the WordPress page title when no ACF override is provided.
if ( ! $banner_title ) {
	if ( is_post_type_archive() ) {
		$banner_title = post_type_archive_title( '', false );
	} elseif ( is_search() ) {
		/* translators: %s: search query */
		$banner_title = sprintf( __( 'Search: %s', 'pc4s' ), get_search_query() );
	} elseif ( is_404() ) {
		$banner_title = __( 'Page Not Found', 'pc4s' );
	} else {
		$banner_title = get_the_title();
	}
}

// Nothing to render if we still have no title.
if ( ! $banner_title ) {
	return;
}
?>

<div class="page-banner" role="banner" aria-label="<?php esc_attr_e( 'Page header', 'pc4s' ); ?>">
	<div class="wrapper page-banner__inner">

		<h1 class="page-banner__title"><?php echo esc_html( $banner_title ); ?></h1>

		<?php if ( $banner_subtitle ) : ?>
			<p class="page-banner__subtitle"><?php echo esc_html( $banner_subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( $show_breadcrumbs ) : ?>
			<?php echo Breadcrumb::render(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>

	</div><!-- .wrapper.page-banner__inner -->
</div><!-- .page-banner -->
