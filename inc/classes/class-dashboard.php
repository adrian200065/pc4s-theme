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
            __( 'PC4S Admin Center', PC4S_TEXTDOMAIN ),
            [ $this, 'render_dashboard_widget' ]
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $data = $this->get_widget_data();
        include get_template_directory() . '/templates/dashboard-widget.php';
    }

    /**
     * Collect all data the dashboard template needs.
     *
     * @return array{
     *   form_status: string,
     *   redirect_url: string,
     *   support_email: string,
     *   urls: array<string, string>
     * }
     */
    private function get_widget_data(): array {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $qs_status  = isset( $_GET['pc4s_form'] ) ? sanitize_key( $_GET['pc4s_form'] ) : '';
        $qs_form_id = isset( $_GET['form_id'] )   ? sanitize_key( $_GET['form_id'] )   : '';
        // phpcs:enable

        $form_status = ( 'dashboard_support' === $qs_form_id ) ? $qs_status : '';

        return [
            'form_status'   => $form_status,
            'redirect_url'  => admin_url( 'index.php' ),
            'support_email' => get_option( 'admin_email', '' ),
            'urls'          => [
                'new_page'       => admin_url( 'post-new.php?post_type=page' ),
                'edit_pages'     => admin_url( 'edit.php?post_type=page' ),
                'new_post'       => admin_url( 'post-new.php' ),
                'media'          => admin_url( 'upload.php' ),
                'new_event'      => admin_url( 'post-new.php?post_type=pc4s_event' ),
                'events'         => admin_url( 'edit.php?post_type=pc4s_event' ),
                'new_staff'      => admin_url( 'post-new.php?post_type=pc4s_staff' ),
                'staff'          => admin_url( 'edit.php?post_type=pc4s_staff' ),
                'form_entries'   => admin_url( 'admin.php?page=pc4s-form-entries' ),
                'forms'          => admin_url( 'admin.php?page=pc4s-forms' ),
                'settings'       => admin_url( 'admin.php?page=pc4s-settings' ),
                'menus'          => admin_url( 'nav-menus.php' ),
                'view_site'      => home_url( '/' ),
                'customizer'     => admin_url( 'customize.php' ),
                'site_health'    => admin_url( 'site-health.php' ),
            ],
        ];
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
