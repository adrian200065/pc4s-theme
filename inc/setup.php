<?php
/**
 * Theme setup functions
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Show an admin notice when Advanced Custom Fields is not active.
 * ACF is required for all page templates and the event system.
 */
function pc4s_acf_missing_notice(): void {
	if ( function_exists( 'acf' ) || class_exists( 'ACF' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'PC4S Theme:', 'pc4s' ),
		esc_html__( 'Advanced Custom Fields (ACF) is not active. Page templates, events, and staff profiles will not display correctly until ACF is installed and activated.', 'pc4s' )
	);
}
add_action( 'admin_notices', 'pc4s_acf_missing_notice' );

/**
 * Stub ACF helper functions so templates don't throw fatal errors when ACF
 * is not active. All stubs return null / empty values identical to how ACF
 * behaves when a field has no value.
 */
if ( ! function_exists( 'get_field' ) ) {
	function get_field( $selector, $post_id = false, $format_value = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return null;
	}
}
if ( ! function_exists( 'the_field' ) ) {
	function the_field( $selector, $post_id = false, $format_value = true ): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	}
}
if ( ! function_exists( 'have_rows' ) ) {
	function have_rows( $selector, $post_id = false ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return false;
	}
}
if ( ! function_exists( 'get_sub_field' ) ) {
	function get_sub_field( $selector, $format_value = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return null;
	}
}

/**
 * Load theme text domain
 */
function pc4s_load_theme_textdomain() {
    load_theme_textdomain(PC4S_TEXTDOMAIN, get_template_directory() . '/languages');
}
add_action( 'after_setup_theme', 'pc4s_load_theme_textdomain', 1 );

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function pc4s_theme_setup() {
    // Add theme support features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'width'         => 205,
        'height'        => 75,
        'flex-width'    => true,
        'flex-height'   => true,
        'header-text'   => ['site-title', 'site-description'],
        'unlink-homepage-logo' => false,
    ]);
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    // Register navigation menus
    register_nav_menus( [
        'primary'           => __( 'Primary Menu', PC4S_TEXTDOMAIN ),
        'footer_helpful'    => __( 'Footer: Helpful Links', PC4S_TEXTDOMAIN ),
        'footer_what_we_do' => __( 'Footer: What We Do', PC4S_TEXTDOMAIN ),
        'footer_legal'      => __( 'Footer: Legal & Policies', PC4S_TEXTDOMAIN ),
    ] );

    // Editor styles
    add_theme_support('editor-styles');
}
add_action('after_setup_theme', 'pc4s_theme_setup');

/**
 * Register widget area.
 */
function pc4s_widgets_init() {
    register_sidebar([
        'name'          => __('Primary Sidebar', PC4S_TEXTDOMAIN),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here to appear in your sidebar.', PC4S_TEXTDOMAIN),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);

    register_sidebar([
        'name'          => __('Footer Widget Area', PC4S_TEXTDOMAIN),
        'id'            => 'footer-1',
        'description'   => __('Add widgets here to appear in your footer.', PC4S_TEXTDOMAIN),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
}
add_action('widgets_init', 'pc4s_widgets_init');

/**
 * Set content width
 */
function pc4s_content_width() {
    $GLOBALS['content_width'] = apply_filters('pc4s_content_width', 1200);
}
add_action('after_setup_theme', 'pc4s_content_width', 0);

/**
 * ACF JSON — save point
 *
 * Directs ACF to write field group JSON files into the theme's acf-json/
 * directory whenever a field group is saved in the admin.
 *
 * Registered at after_setup_theme (before init) so the path is in place
 * before ACF initializes and scans for JSON.
 *
 * @param string $path Default save path.
 * @return string
 */
function pc4s_acf_json_save_point( string $path ): string {
    return get_template_directory() . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'pc4s_acf_json_save_point' );

/**
 * ACF JSON — load paths
 *
 * Tells ACF to scan the theme's acf-json/ directory when looking for
 * field group definitions. The default path is replaced so there is
 * a single, predictable location.
 *
 * @param array $paths Existing load paths.
 * @return array
 */
function pc4s_acf_json_load_paths( array $paths ): array {
    unset( $paths[0] );
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
}
add_filter( 'acf/settings/load_json', 'pc4s_acf_json_load_paths' );

/**
 * Flush rewrite rules on theme activation.
 *
 * Sets a short-lived transient on theme switch. The actual flush happens on
 * the next `admin_init` request — by which time `init` has already run and
 * every CPT / taxonomy is registered, so the generated rules are complete.
 */
function pc4s_schedule_rewrite_flush(): void {
	set_transient( 'pc4s_flush_rewrite_rules', true, MINUTE_IN_SECONDS * 2 );
}
add_action( 'after_switch_theme', 'pc4s_schedule_rewrite_flush' );

/**
 * Execute the deferred rewrite-rules flush on the next admin request.
 */
function pc4s_maybe_flush_rewrite_rules(): void {
	if ( get_transient( 'pc4s_flush_rewrite_rules' ) ) {
		delete_transient( 'pc4s_flush_rewrite_rules' );
		flush_rewrite_rules();
	}
}
add_action( 'admin_init', 'pc4s_maybe_flush_rewrite_rules' );

/**
 * Grant the custom pc4s_manage capability to Editor and Administrator roles.
 *
 * Stored in the DB via WP_Role — only runs when the capability is missing,
 * so the overhead on normal requests is negligible.
 */
function pc4s_grant_manage_capability(): void {
	foreach ( [ 'editor', 'administrator' ] as $role_name ) {
		$role = get_role( $role_name );
		if ( $role && ! $role->has_cap( 'pc4s_manage' ) ) {
			$role->add_cap( 'pc4s_manage' );
		}
	}
}
add_action( 'admin_init', 'pc4s_grant_manage_capability' );
