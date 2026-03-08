<?php
/**
 * The header for our theme
 *
 * Outputs the document <head>, opens <body>, and includes the site-header
 * template part. Keep this file minimal — all header markup lives in
 * parts/header/site-header.php.
 *
 * @package PC4S
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to main content', 'pc4s' ); ?></a>

<?php get_template_part( 'parts/header/site-header' ); ?>

<main id="main-content">
