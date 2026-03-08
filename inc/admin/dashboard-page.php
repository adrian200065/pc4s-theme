<?php
/**
 * PC4S Dashboard (Overview) Admin Page
 *
 * Top-level "PC4S → Overview" page. Shows at-a-glance stats and quick-action
 * links to every PC4S admin area. Replaces the inline render_dashboard() that
 * previously lived inside Pc4sAdminMenu.
 *
 * @package PC4S\Admin
 */

namespace PC4S\Admin;

use PC4S\Classes\PostTypes\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DashboardPage {

	const CAPABILITY = 'edit_posts';

	/** @var DashboardPage|null */
	private static ?DashboardPage $instance = null;

	public static function get_instance(): DashboardPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// No hooks needed — the page is registered via Pc4sAdminMenu.
	}

	// ─── Stat helpers ─────────────────────────────────────────────────────────

	/**
	 * Count published events.
	 */
	private function count_events(): int {
		$counts = wp_count_posts( Event::POST_TYPE );
		return (int) ( $counts->publish ?? 0 );
	}

	/**
	 * Count all form entries in the DB.
	 */
	private function count_entries_total(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i',
				$wpdb->prefix . 'pc4s_form_entries'
			)
		);
	}

	/**
	 * Count form entries submitted in the last 30 days.
	 */
	private function count_entries_recent(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE submitted_at >= %s',
				$wpdb->prefix . 'pc4s_form_entries',
				gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
			)
		);
	}

	// ─── Render ───────────────────────────────────────────────────────────────

	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pc4s' ) );
		}

		$event_count   = $this->count_events();
		$entries_total = $this->count_entries_total();
		$entries_recent = $this->count_entries_recent();

		$urls = [
			'events'        => admin_url( 'edit.php?post_type=' . Event::POST_TYPE ),
			'new_event'     => admin_url( 'post-new.php?post_type=' . Event::POST_TYPE ),
			'event_types'   => admin_url( 'edit-tags.php?taxonomy=' . Event::TAXONOMY . '&post_type=' . Event::POST_TYPE ),
			'footer'        => admin_url( 'admin.php?page=pc4s-footer-settings' ),
			'forms'         => admin_url( 'admin.php?page=pc4s-forms' ),
			'form_entries'  => admin_url( 'admin.php?page=pc4s-form-entries' ),
			'customizer'    => admin_url( 'customize.php?autofocus[section]=pc4s_footer_branding' ),
		];
		?>
		<div class="wrap pc4s-admin-page pc4s-dashboard-page">

			<header class="pc4s-admin-header">
				<h1 class="pc4s-admin-header__title"><?php esc_html_e( 'PC4S Overview', 'pc4s' ); ?></h1>
				<p class="pc4s-admin-header__description">
					<?php esc_html_e( 'Manage events, configure forms, and update footer content from here.', 'pc4s' ); ?>
				</p>
			</header>

			<!-- ────────────────── Stats strip ────────────────────────────── -->
			<div class="pc4s-stats-row" role="list">

				<div class="pc4s-stat-card" role="listitem">
					<strong class="pc4s-stat-card__value"><?php echo esc_html( number_format_i18n( $event_count ) ); ?></strong>
					<span class="pc4s-stat-card__label"><?php esc_html_e( 'Published Events', 'pc4s' ); ?></span>
					<a class="pc4s-stat-card__link" href="<?php echo esc_url( $urls['events'] ); ?>">
						<?php esc_html_e( 'View all', 'pc4s' ); ?> &rarr;
					</a>
				</div>

				<div class="pc4s-stat-card" role="listitem">
					<strong class="pc4s-stat-card__value"><?php echo esc_html( number_format_i18n( $entries_total ) ); ?></strong>
					<span class="pc4s-stat-card__label"><?php esc_html_e( 'Total Form Entries', 'pc4s' ); ?></span>
					<a class="pc4s-stat-card__link" href="<?php echo esc_url( $urls['form_entries'] ); ?>">
						<?php esc_html_e( 'View entries', 'pc4s' ); ?> &rarr;
					</a>
				</div>

				<div class="pc4s-stat-card pc4s-stat-card--accent" role="listitem">
					<strong class="pc4s-stat-card__value"><?php echo esc_html( number_format_i18n( $entries_recent ) ); ?></strong>
					<span class="pc4s-stat-card__label"><?php esc_html_e( 'New Signups (30 days)', 'pc4s' ); ?></span>
					<a class="pc4s-stat-card__link" href="<?php echo esc_url( $urls['form_entries'] ); ?>">
						<?php esc_html_e( 'View entries', 'pc4s' ); ?> &rarr;
					</a>
				</div>

			</div><!-- .pc4s-stats-row -->

			<!-- ────────────────── Quick links ────────────────────────────── -->
			<h2 class="pc4s-section-heading"><?php esc_html_e( 'Quick Actions', 'pc4s' ); ?></h2>

			<div class="pc4s-quick-links">

				<a href="<?php echo esc_url( $urls['events'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-calendar-alt" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Events', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'View and manage all events', 'pc4s' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $urls['new_event'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-plus-alt" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Add Event', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Create a new event', 'pc4s' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $urls['event_types'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-tag" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Event Types', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Manage event categories', 'pc4s' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $urls['footer'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-editor-insertmore" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Footer Settings', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Tagline, contact info, newsletter text', 'pc4s' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $urls['forms'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-feedback" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Forms', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Configure notification emails and messages', 'pc4s' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $urls['form_entries'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-list-view" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Form Entries', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Browse and filter submissions', 'pc4s' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $urls['customizer'] ); ?>" class="pc4s-quick-link-card">
					<span class="pc4s-quick-link-card__icon dashicons dashicons-admin-customizer" aria-hidden="true"></span>
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Footer Logo', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Change the footer brand logo in Customizer', 'pc4s' ); ?></span>
				</a>

			</div><!-- .pc4s-quick-links -->

		</div><!-- .pc4s-dashboard-page -->
		<?php
	}
}
