<?php
/**
 * Asset management functions
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define theme enqueue function
 */
function pc4s_enqueue_scripts() {
    // Define asset paths
    $css_path = PC4S_THEME_URI . '/assets/css/main.min.css';
    $js_path  = PC4S_THEME_URI . '/assets/js/main.min.js';
    $css_file = PC4S_THEME_DIR . '/assets/css/main.min.css';
    $js_file  = PC4S_THEME_DIR . '/assets/js/main.min.js';

    // Enqueue main theme stylesheet (style.css) — versioned by theme version.
    wp_enqueue_style(
        'pc4s-style',
        get_stylesheet_uri(),
        [],
        PC4S_THEME_VERSION
    );

    // Enqueue compiled main CSS — versioned by file modification time so the
    // browser cache is busted only when the file actually changes.
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'pc4s-main',
            $css_path,
            [ 'pc4s-style' ],
            (string) filemtime( $css_file )
        );
    }

    // Enqueue main JS — same filemtime strategy.
    if ( file_exists( $js_file ) ) {
        wp_enqueue_script(
            'pc4s-main',
            $js_path,
            [],
            (string) filemtime( $js_file ),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'pc4s_enqueue_scripts');

/**
 * Enqueue admin styles and scripts
 *
 * Only loads assets on PC4S admin pages (hooks containing 'pc4s').
 *
 * @param string $hook The current admin page hook suffix.
 */
function pc4s_enqueue_admin_scripts($hook) {
    // Load on PC4S admin pages and on the main WordPress dashboard (index.php)
    // which hosts the PC4S Admin Center widget.
    $is_pc4s_page      = strpos( $hook, 'pc4s' ) !== false;
    $is_wp_dashboard   = 'index.php' === $hook;

    if ( ! $is_pc4s_page && ! $is_wp_dashboard ) {
        return;
    }

    // Define asset paths
    $admin_css_path = PC4S_THEME_URI . '/assets/admin/css/admin.min.css';
    $admin_js_path  = PC4S_THEME_URI . '/assets/admin/js/admin.min.js';
    $admin_css_file = PC4S_THEME_DIR . '/assets/admin/css/admin.min.css';
    $admin_js_file  = PC4S_THEME_DIR . '/assets/admin/js/admin.min.js';

    // Enqueue admin styles if file exists
    if ( file_exists( $admin_css_file ) ) {
        wp_enqueue_style(
            'pc4s-admin-style',
            $admin_css_path,
            [],
            (string) filemtime( $admin_css_file )
        );
    }

    // Check if we're on a settings page
    $is_dashboard_page = (
        'index.php' === $hook ||
        strpos($hook, 'pc4s-dashboard') !== false ||
        'toplevel_page_pc4s-dashboard' === $hook ||
        'pc4s-dashboard-dashboard_page_pc4s-dashboard' === $hook
    );

    // Always enqueue WordPress media scripts on dashboard page
    if ($is_dashboard_page) {
        wp_enqueue_media();
    }

    // Enqueue admin scripts if file exists
    if ( file_exists( $admin_js_file ) ) {
        wp_enqueue_script(
            'pc4s-admin-script',
            $admin_js_path,
            [ 'jquery' ],
            (string) filemtime( $admin_js_file ),
            true
        );

        // Add admin data for all admin pages
        wp_localize_script('pc4s-admin-script', 'pc4sAdmin', [
            'ajax_url'              => admin_url('admin-ajax.php'),
            'nonce'                 => wp_create_nonce('pc4s-admin-nonce'),
            'is_dashboard_page'     => $is_dashboard_page
        ]);
    }
}
add_action('admin_enqueue_scripts', 'pc4s_enqueue_admin_scripts');

/**
 * Output a <link rel="preload"> for the hero background image on pages that
 * use the flexible-content hero layout, improving LCP on image-heavy pages.
 *
 * Runs at priority 1 (before other wp_head output) so the hint reaches the
 * browser as early as possible.
 */
function pc4s_preload_hero_image(): void {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	// Map page template → ACF flexible-content field name.
	// Add entries here when new templates with hero sections are created.
	$field_map = [
		'front-page'                    => 'front_page_sections',
		'templates/what-can-page.php'   => 'wcwd_sections',
		'templates/involved-page.php'   => 'hcgi_sections',
		'templates/drug-use-page.php'   => 'dktf_sections',
		'templates/prevention-page.php' => 'wpw_sections',
	];

	$template   = is_front_page() ? 'front-page' : get_page_template_slug( $post_id );
	$field_name = $field_map[ $template ] ?? null;

	if ( ! $field_name ) {
		return;
	}

	$rows = get_field( $field_name, $post_id );
	if ( ! is_array( $rows ) ) {
		return;
	}

	$image_id = null;
	foreach ( $rows as $row ) {
		if ( isset( $row['acf_fc_layout'] ) && 'hero' === $row['acf_fc_layout'] && ! empty( $row['bg_image'] ) ) {
			$image_id = (int) $row['bg_image'];
			break;
		}
	}

	if ( ! $image_id ) {
		return;
	}

	$src = wp_get_attachment_image_url( $image_id, 'full' );
	if ( ! $src ) {
		return;
	}

	$srcset = wp_get_attachment_image_srcset( $image_id, 'full' );
	$sizes  = wp_get_attachment_image_sizes( $image_id, 'full' );

	printf(
		'<link rel="preload" as="image" href="%s"%s%s fetchpriority="high">' . "\n",
		esc_url( $src ),
		$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '',
		$sizes  ? ' imagesizes="'  . esc_attr( $sizes  ) . '"' : ''
	);
}
add_action( 'wp_head', 'pc4s_preload_hero_image', 1 );
