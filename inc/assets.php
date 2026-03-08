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
    // Add time to version to prevent caching
    $version = PC4S_THEME_VERSION . '.' . time();

    // Define asset paths
    $css_path = PC4S_THEME_URI . '/assets/css/main.min.css';
    $js_path = PC4S_THEME_URI . '/assets/js/main.min.js';

    // Check if files exist before enqueuing
    $css_file = PC4S_THEME_DIR . '/assets/css/main.min.css';
    $js_file = PC4S_THEME_DIR . '/assets/js/main.min.js';

    // Enqueue main theme stylesheet (style.css)
    wp_enqueue_style(
        'pc4s-style',
        get_stylesheet_uri(),
        [],
        $version
    );

    // Enqueue compiled main CSS if it exists
    if (file_exists($css_file)) {
        wp_enqueue_style(
            'pc4s-main',
            $css_path,
            ['pc4s-style'],
            $version
        );
    }

    // Enqueue main JS if it exists
    if (file_exists($js_file)) {
        wp_enqueue_script(
            'pc4s-main',
            $js_path,
            [],
            $version,
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
    // Limit to PC4S admin pages only.
    if ( strpos( $hook, 'pc4s' ) === false ) {
        return;
    }

    // Add time to version to prevent caching
    $version = PC4S_THEME_VERSION . '.' . time();

    // Define asset paths
    $admin_css_path = PC4S_THEME_URI . '/assets/admin/css/admin.min.css';
    $admin_js_path = PC4S_THEME_URI . '/assets/admin/js/admin.min.js';

    // Check if files exist before enqueuing
    $admin_css_file = PC4S_THEME_DIR . '/assets/admin/css/admin.min.css';
    $admin_js_file = PC4S_THEME_DIR . '/assets/admin/js/admin.min.js';

    // Enqueue admin styles if file exists
    if (file_exists($admin_css_file)) {
        wp_enqueue_style(
            'pc4s-admin-style',
            $admin_css_path,
            [],
            $version
        );
    }

    // Check if we're on a settings page
    $is_dashboard_page = (
        strpos($hook, 'pc4s-dashboard') !== false ||
        'toplevel_page_pc4s-dashboard' === $hook ||
        'pc4s-dashboard-dashboard_page_pc4s-dashboard' === $hook
    );

    // Always enqueue WordPress media scripts on dashboard page
    if ($is_dashboard_page) {
        wp_enqueue_media();
    }

    // Enqueue admin scripts if file exists
    if (file_exists($admin_js_file)) {
        wp_enqueue_script(
            'pc4s-admin-script',
            $admin_js_path,
            ['jquery'],
            $version,
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
 * Add async/defer attributes to scripts
 */
function pc4s_script_attributes($tag, $handle, $src) {
    $async_scripts = ['pc4s-script'];

    if (in_array($handle, $async_scripts)) {
        return str_replace('<script ', '<script async ', $tag);
    }

    return $tag;
}
add_filter('script_loader_tag', 'pc4s_script_attributes', 10, 3);
