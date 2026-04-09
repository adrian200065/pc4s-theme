<?php
/**
 * Theme functions and definitions
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define theme constants
define('PC4S_THEME_DIR', get_template_directory());
define('PC4S_THEME_URI', get_template_directory_uri());
define('PC4S_TEXTDOMAIN', 'pc4s');
define('PC4S_THEME_VERSION', wp_get_theme()->version);


function pc4s_init() {
    // Load files that don't need translations first
    require_once PC4S_THEME_DIR . '/inc/assets.php';
    require_once PC4S_THEME_DIR . '/inc/customizer.php';
    require_once PC4S_THEME_DIR . '/inc/setup.php';
}
add_action('after_setup_theme', 'pc4s_init', 0);

function pc4s_load_i18n_files() {
    require_once PC4S_THEME_DIR . '/inc/template-tags.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-helper.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-breadcrumb.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-nav-walker.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-footer-nav-walker.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-custom-login.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-core-customizer.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-dashboard.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-email-template.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-forms.php';
    require_once PC4S_THEME_DIR . '/inc/classes/post-types/class-event.php';
    require_once PC4S_THEME_DIR . '/inc/classes/post-types/class-staff.php';
    require_once PC4S_THEME_DIR . '/inc/admin/footer-settings.php';
    require_once PC4S_THEME_DIR . '/inc/admin/dashboard-page.php';
    require_once PC4S_THEME_DIR . '/inc/admin/forms-page.php';
    require_once PC4S_THEME_DIR . '/inc/admin/form-entries-page.php';
    require_once PC4S_THEME_DIR . '/inc/admin/settings-page.php';
    require_once PC4S_THEME_DIR . '/inc/admin/smtp-page.php';
    require_once PC4S_THEME_DIR . '/inc/classes/admin/class-admin-menu.php';
    require_once PC4S_THEME_DIR . '/inc/classes/admin/class-google-analytics.php';
    require_once PC4S_THEME_DIR . '/inc/classes/class-event-query.php';

    // Initialize custom login, core customizer, and dashboard
    PC4S\Classes\CustomLogin::get_instance();
    PC4S\Classes\CoreCustomizer::get_instance();
    PC4S\Classes\Dashboard::get_instance();

    // Bootstrap form handling (registers admin_post handlers + DB table check).
    PC4S\Classes\Custom_Forms::init();

    // Initialize event system
    PC4S\Classes\PostTypes\Event::get_instance();
    PC4S\Classes\PostTypes\Staff::get_instance();
    PC4S\Classes\Admin\Pc4sAdminMenu::get_instance();
    PC4S\Classes\Admin\GoogleAnalytics::get_instance();
    PC4S\Classes\EventQuery::register_hooks();

    // Initialize admin settings pages (must come before admin_menu fires).
    PC4S\Admin\FooterSettings::get_instance();
    PC4S\Admin\DashboardPage::get_instance();
    PC4S\Admin\FormsPage::get_instance();
    PC4S\Admin\FormEntriesPage::get_instance();
    PC4S\Admin\SettingsPage::get_instance();
    // SmtpPage must be instantiated on every request so the phpmailer_init
    // hook is registered for both admin and frontend wp_mail() calls.
    PC4S\Admin\SmtpPage::get_instance();
}
add_action('init', 'pc4s_load_i18n_files', 0);
