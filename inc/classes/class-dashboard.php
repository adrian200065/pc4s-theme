<?php
/**
 * Dashboard Customization Class
 *
 * Handles customization of WordPress dashboard including widgets,
 * meta boxes, and custom dashboard content.
 *
 * @package CUSTOM_THEME
 */

namespace PC4S\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Dashboard {
    /**
     * Instance of this class
     *
     * @var Dashboard
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Remove default dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']);

        // Remove dashboard metaboxes
        add_action('admin_init', [$this, 'remove_dashboard_metaboxes']);

        // Add custom dashboard widget
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);

        // Force single column layout
        add_filter('get_user_option_screen_layout_dashboard', [$this, 'force_single_column']);
    }

    /**
     * Get instance of this class
     *
     * @return Dashboard
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Remove default dashboard widgets
     */
    public function remove_dashboard_widgets() {
        global $wp_meta_boxes;

        // Remove Welcome panel
        remove_action('welcome_panel', 'wp_welcome_panel');

        // Core widgets
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health']);
    }

    /**
     * Remove dashboard metaboxes
     */
    public function remove_dashboard_metaboxes() {
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    }

    /**
     * Add custom dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'custom_theme_dashboard_widget',
            __('PC4S Dashboard Tools', PC4S_TEXTDOMAIN),
            [$this, 'render_dashboard_widget']
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        include get_template_directory() . '/templates/dashboard-widget.php';
    }

    /**
     * Force single column dashboard layout
     *
     * @return int Number of columns (1)
     */
    public function force_single_column() {
        return 1;
    }
}
