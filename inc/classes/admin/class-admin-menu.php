<?php
/**
 * PC4S Admin Menu
 *
 * Creates a top-level "PC4S" admin menu and moves the Events CPT
 * under it, providing a single branded entry point in the sidebar.
 *
 * @package PC4S
 */

namespace PC4S\Classes\Admin;

use PC4S\Classes\PostTypes\Event;
use PC4S\Classes\PostTypes\Staff;
use PC4S\Admin\DashboardPage;
use PC4S\Admin\FooterSettings;
use PC4S\Admin\FormsPage;
use PC4S\Admin\FormEntriesPage;
use PC4S\Admin\SettingsPage;
use PC4S\Admin\SmtpPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pc4sAdminMenu {

	/**
	 * Parent slug used for all PC4S submenu items.
	 */
	const PARENT_SLUG = 'pc4s-dashboard';

	/**
	 * @var Pc4sAdminMenu|null
	 */
	private static ?Pc4sAdminMenu $instance = null;

	public static function get_instance(): Pc4sAdminMenu {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
	}

	/**
	 * Register the top-level menu and all submenus.
	 */
	public function register_menus(): void {
		// ── Top-level PC4S menu ──────────────────────────────────────────────
		add_menu_page(
			__( 'PC4S', 'pc4s' ),                              // Page title
			__( 'PC4S', 'pc4s' ),                              // Menu title
			'edit_posts',                                      // Capability
			self::PARENT_SLUG,                                 // Menu slug
			[ DashboardPage::get_instance(), 'render_page' ],  // Callback
			'dashicons-groups',                                // Icon
			3,                                                 // Position
		);

		// ── Overview submenu (replaces the auto-created duplicate) ───────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'PC4S Overview', 'pc4s' ),
			__( 'Overview', 'pc4s' ),
			'edit_posts',
			self::PARENT_SLUG,
			[ DashboardPage::get_instance(), 'render_page' ]
		);

		// ── Events submenu ───────────────────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Events', 'pc4s' ),
			__( 'Events', 'pc4s' ),
			'edit_posts',
			'edit.php?post_type=' . Event::POST_TYPE
		);

		// ── Staff submenu ───────────────────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Staff', 'pc4s' ),
			__( 'Staff', 'pc4s' ),
			'edit_posts',
			'edit.php?post_type=' . Staff::POST_TYPE
		);

		// ── Event Types taxonomy submenu ─────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Event Types', 'pc4s' ),
			__( 'Event Types', 'pc4s' ),
			'manage_categories',
			'edit-tags.php?taxonomy=' . Event::TAXONOMY . '&post_type=' . Event::POST_TYPE
		);

		// ── Forms submenu ────────────────────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Forms', 'pc4s' ),
			__( 'Forms', 'pc4s' ),
			'manage_options',
			'pc4s-forms',
			[ FormsPage::get_instance(), 'render_page' ]
		);

		// ── Form Entries submenu ─────────────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Form Entries', 'pc4s' ),
			__( 'Form Entries', 'pc4s' ),
			'manage_options',
			'pc4s-form-entries',
			[ FormEntriesPage::get_instance(), 'render_page' ]
		);

		// ── Footer settings submenu ──────────────────────────────────────────
		$footer_hook = add_submenu_page(
			self::PARENT_SLUG,
			__( 'Footer Settings', 'pc4s' ),
			__( 'Footer', 'pc4s' ),
			'manage_options',
			'pc4s-footer-settings',
			[ FooterSettings::get_instance(), 'render_page' ]
		);
		if ( $footer_hook ) {
			FooterSettings::get_instance()->set_page_hook( $footer_hook );
		}
		
		// ── Settings submenu ───────────────────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'PC4S Settings', 'pc4s' ),
			__( 'Settings', 'pc4s' ),
			'manage_options',
			'pc4s-settings',
			[ SettingsPage::get_instance(), 'render_page' ]
		);

		// ── SMTP submenu ───────────────────────────────────────────────────────
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'SMTP Settings', 'pc4s' ),
			__( 'SMTP', 'pc4s' ),
			'manage_options',
			'pc4s-smtp',
			[ SmtpPage::get_instance(), 'render_page' ]
		);	}

}

