<?php
/**
 * Custom Login Class
 *
 * Handles customization of WordPress login page and admin bar
 *
 * @package Pc4s
 */

namespace PC4S\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CustomLogin {
    /**
     * Instance of this class
     *
     * @var CustomLogin
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Login page customization
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_styles'], 20);
        add_filter('login_headerurl', [$this, 'custom_login_header_url']);
        add_filter('login_headertext', [$this, 'custom_login_header_text']);

        // Remove login shake animation
        add_action('login_head', [$this, 'remove_login_shake']);

        // Customize login messages
        add_filter('login_errors', [$this, 'custom_login_error_messages']);

        // Logout redirect
        add_action('wp_logout', [$this, 'redirect_after_logout']);

        // Admin bar customization - use correct hook with higher priority
        add_action('admin_bar_menu', [$this, 'customize_admin_bar'], 25);
        add_action('wp_before_admin_bar_render', [$this, 'modify_admin_bar_howdy']);
        add_filter('gettext', [$this, 'replace_howdy_text'], 10, 3);
    }

    /**
     * Get instance of this class
     *
     * @return CustomLogin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Enqueue login styles
     *
     * Loads the main compiled stylesheet (which includes all login page styles)
     * and injects the dynamic logo URL as a CSS custom property.
     * Dark background is used for the login page, so the white logo variant is applied.
     */
    public function enqueue_login_styles() {
        // Add time to version to prevent caching.
        $version = PC4S_THEME_VERSION . '.' . time();

        wp_enqueue_style(
            'pc4s-login',
            get_template_directory_uri() . '/assets/css/main.min.css',
            [],
            $version
        );

        // Inject the logo URL as a CSS custom property.
        // All other login styles are handled in src/scss/layout/_login-page.scss.
        // The login page uses a dark background so the white logo variant is used.
        $logo_url = esc_url( PC4S_THEME_URI . '/assets/images/pc4s-logo-white.webp' );
        $custom_css = ":root { --login-logo-url: url('{$logo_url}'); }";
        wp_add_inline_style( 'pc4s-login', $custom_css );
    }

    /**
     * Remove login shake animation
     */
    public function remove_login_shake() {
        remove_action('login_head', 'wp_shake_js', 12);

        // Add CSS to completely disable shake animation
        echo '<style type="text/css">
            .shake { animation: none !important; }
            #login form { animation: none !important; }
        </style>';

        // Remove shake script completely
        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                // Disable any shake functionality
                if (typeof shake !== "undefined") {
                    shake = function() { return false; };
                }
            });
        </script>';
    }

    /**
     * Custom login header URL
     */
    public function custom_login_header_url() {
        return home_url('/');
    }

    /**
     * Custom login header text
     */
    public function custom_login_header_text() {
        return get_bloginfo('name');
    }

    /**
     * Custom login error messages
     */
    public function custom_login_error_messages($error) {
        global $errors;

        if (isset($errors) && $errors->get_error_code()) {
            $code = $errors->get_error_code();

            switch ($code) {
                case 'incorrect_password':
                case 'invalid_username':
                    // Generic error message for login failures
                    return __('Whoops, Login failed. Please check your credentials and try again.', PC4S_TEXTDOMAIN);
                case 'empty_password':
                case 'empty_username':
                    // Generic message for empty fields
                    return __('Please fill in all required fields.', PC4S_TEXTDOMAIN);
                default:
                    // For other errors, return a generic message
                    return __('Error logging in. Please try again.', PC4S_TEXTDOMAIN);
            }
        }

        return $error;
    }

    /**
     * Redirect after logout
     */
    public function redirect_after_logout() {
        wp_safe_redirect(home_url('/'));
        exit();
    }

    /**
     * Get time-based greeting
     */
    private function get_time_based_greeting() {
        // Get the current hour in user's timezone
        $current_hour = current_time('G');

        // Determine the greeting based on time
        if ($current_hour >= 5 && $current_hour < 12) {
            return __('Good Morning', PC4S_TEXTDOMAIN);
        } elseif ($current_hour >= 12 && $current_hour < 17) {
            return __('Good Afternoon', PC4S_TEXTDOMAIN);
        } elseif ($current_hour >= 17 && $current_hour < 22) {
            return __('Good Evening', PC4S_TEXTDOMAIN);
        } else {
            return __('Good Night', PC4S_TEXTDOMAIN);
        }
    }

    /**
     * Replace "Howdy" text in translations
     */
    public function replace_howdy_text($translated_text, $text, $domain) {
        if ($text === 'Howdy, %s' && $domain === 'default') {
            $greeting = $this->get_time_based_greeting();
            return $greeting . ', %s';
        }
        return $translated_text;
    }

    /**
     * Modify admin bar before render
     */
    public function modify_admin_bar_howdy() {
        global $wp_admin_bar;

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        // Get my-account node
        $my_account = $wp_admin_bar->get_node('my-account');
        if ($my_account) {
            $user_info = get_userdata($user_id);
            $greeting = $this->get_time_based_greeting();

            // Replace the title completely
            $my_account->title = $greeting . ', ' . esc_html($user_info->display_name);
            $wp_admin_bar->add_node($my_account);
        }
    }

    /**
     * Customize admin bar greeting
     */
    public function customize_admin_bar($wp_admin_bar) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $user_info = get_userdata($user_id);
        $greeting = $this->get_time_based_greeting();

        // Get existing my-account node to preserve structure
        $my_account = $wp_admin_bar->get_node('my-account');
        if ($my_account) {
            // Update just the title, preserving everything else
            $my_account->title = $greeting . ', ' . esc_html($user_info->display_name);
            $wp_admin_bar->add_node($my_account);
        }
    }
}
