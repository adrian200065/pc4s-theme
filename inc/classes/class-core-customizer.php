<?php
/**
 * Core Customizer Class
 *
 * Handles WordPress core customizations including Gutenberg editor,
 * admin interface, comments, and ACF configurations.
 *
 * @package Pc4s
 */

namespace PC4S\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CoreCustomizer {
    /**
     * Instance of this class
     *
     * @var CoreCustomizer
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Gutenberg editor management
        add_filter('use_block_editor_for_post_type', [$this, 'manage_gutenberg_editor'], 10, 2);

        // Admin interface customization
        add_action('admin_head', [$this, 'manage_admin_notices']);
        add_filter('admin_footer_text', [$this, 'customize_admin_footer_text']);
        add_filter('update_footer', [$this, 'customize_version_text'], 20);
        add_action('admin_bar_menu', [$this, 'remove_wp_logo'], 999);

        // Admin menu customization
        add_action('admin_menu', [$this, 'remove_comments_menu']);

        // Comment form customization
        add_filter('comment_form_default_fields', [$this, 'modify_comment_form']);

        // ACF configurations — JSON paths are registered early in inc/setup.php.
        add_filter('acf/settings/show_admin', [$this, 'control_acf_admin_access']);

        // Allow SVG uploads
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'check_filetype' ], 10, 4 );
        add_filter( 'wp_handle_upload_prefilter', [ $this, 'sanitize_svg' ] );

        add_filter('upload_mimes', function($mimes) {
            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        });
    }

    /**
     * Get instance of this class
     *
     * @return CoreCustomizer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Manage Gutenberg editor availability
     *
     * @param bool $use_block_editor Whether to use block editor
     * @param string $post_type Current post type
     * @return bool Modified status
     */
    public function manage_gutenberg_editor($use_block_editor, $post_type) {
        // Get settings (you'll need to create these in your theme settings)
        $disable_globally   = get_option('disable_gutenberg_globally', false);
        $disable_for_pages  = get_option('disable_gutenberg_pages', true);
        $disable_for_posts  = get_option('disable_gutenberg_posts', true);

        // Disable globally if set
        if ($disable_globally) {
            return false;
        }

        // Disable for pages if set
        if ($disable_for_pages && $post_type === 'page') {
            return false;
        }

        // Disable for posts if set
        if ($disable_for_posts && $post_type === 'post') {
            return false;
        }

        return $use_block_editor;
    }

    /**
     * Manage admin notices visibility
     */
    public function manage_admin_notices() {
        if (!current_user_can('manage_options')) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }

    /**
     * Customize admin footer text
     *
     * @param string $text Default footer text
     * @return string Modified footer text
     */
    public function customize_admin_footer_text($text) {
        return sprintf(
            __('Created by %s | Powered by %s', PC4S_TEXTDOMAIN),
            '<a href="https://lucidsitesstudio.com" target="_blank">Lucid Site Studio</a>',
            '<a href="https://wordpress.org" target="_blank">WordPress</a>'
        );
    }

    /**
     * Customize version text in admin footer
     *
     * @param string $text Default version text
     * @return string Modified version text
     */
    public function customize_version_text($text) {
        $theme = wp_get_theme();
        return sprintf(
            __('%s Version %s', PC4S_TEXTDOMAIN),
            $theme->get('Name'),
            $theme->get('Version')
        );
    }

    /**
     * Remove WordPress logo from admin bar for non-admin users
     *
     * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance
     */
    public function remove_wp_logo($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            $wp_admin_bar->remove_node('wp-logo');
        }
    }

    /**
     * Remove comments menu from admin
     */
    public function remove_comments_menu() {
        remove_menu_page('edit-comments.php');
    }

    /**
     * Modify comment form fields
     *
     * @param array $fields Default comment form fields
     * @return array Modified fields
     */
    public function modify_comment_form($fields) {
        if (isset($fields['url'])) {
            unset($fields['url']);
        }
        return $fields;
    }

    /**
     * Control ACF admin access
     *
     * @param bool $show Whether to show ACF in admin
     * @return bool Modified status
     */
    public function control_acf_admin_access($show) {
        return current_user_can('manage_options');
    }

    /**
     * Checks the MIME type of a file upload.
     *
     * @param array $data Array of upload data.
     * @param string $file The file to check.
     * @param string $filename The name of the file.
     * @param array $mimes Array of allowed MIME types.
     * @return array Array containing the file extension, MIME type, and proper filename.
     */
    public function check_filetype($data, $file, $filename, $mimes) {
        $filetype = wp_check_filetype($filename, $mimes);

        return [
            'ext'               => $filetype['ext'],
            'type'              => $filetype['type'],
            'proper_filename'   => $data['proper_filename']
        ];
    }

     /**
     * Sanitizes an SVG file by removing any `<script>` tags and any 'on' attributes.
     *
     * This function is used as a filter for the `wp_handle_upload` filter.
     *
     * @param array $file The data for the uploaded file.
     * @return array The sanitized data for the uploaded file.
     */
    public function sanitize_svg( $file ) {
        if ( $file['type'] === 'image/svg+xml' ) {
            // Read the SVG file
            $content = file_get_contents( $file['tmp_name'] );

            // Basic sanitization: Remove scripts and dangerous attributes
            $content = preg_replace( '/<script[\s\S]*?\/script>/i', '', $content );
            $content = preg_replace( '/on\w+="[^"]*"/i', '', $content );

            // Write sanitized content back
            file_put_contents( $file['tmp_name'], $content );
        }
        return $file;
    }
}
