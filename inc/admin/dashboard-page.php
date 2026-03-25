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
					<?php esc_html_e( 'Manage events, configure forms, and update shared contact and footer content from here.', 'pc4s' ); ?>
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
					<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Contact Info', 'pc4s' ); ?></strong>
					<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Shared contact details, office hours, and footer content', 'pc4s' ); ?></span>
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

			<!-- ────────────────── Google Analytics Dashboard ──────────── -->
			<?php $this->render_analytics_section(); ?>

		</div><!-- .pc4s-dashboard-page -->
		<?php
	}

	// ─── Analytics section ────────────────────────────────────────────────────

	/**
	 * Render the Google Analytics dashboard section on the Overview page.
	 * If GA is not connected, shows a prompt linking to the settings page.
	 * If connected, renders the dashboard shell whose charts are populated
	 * by the pc4s_ga_fetch AJAX call in admin.js.
	 */
	private function render_analytics_section(): void {
		$ga         = \PC4S\Classes\Admin\GoogleAnalytics::get_instance();
		$status     = $ga->get_connection_status();
		$has_creds  = $ga->has_credentials();
		$property   = $ga->get_property_id();
		$settings_url = admin_url( 'admin.php?page=pc4s-settings' );
		?>

		<h2 class="pc4s-section-heading"><?php esc_html_e( 'Google Analytics', 'pc4s' ); ?></h2>

		<?php if ( ! $has_creds || empty( $property ) ) : ?>

		<!-- Not connected prompt -->
		<div class="pc4s-analytics-prompt">
			<div class="pc4s-analytics-prompt__icon" aria-hidden="true">
				<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="48" height="48" rx="12" fill="currentColor" opacity=".08"/>
					<path d="M14 34V22a2 2 0 0 1 2-2h4v14H14zm9 0V14a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v20h-8zm9 0v-8a2 2 0 0 1 2-2h4v10h-6z" fill="currentColor"/>
				</svg>
			</div>
			<div class="pc4s-analytics-prompt__content">
				<h3 class="pc4s-analytics-prompt__title"><?php esc_html_e( 'Connect Google Analytics', 'pc4s' ); ?></h3>
				<p class="pc4s-analytics-prompt__desc">
					<?php esc_html_e( 'View page views, user activity, traffic sources, and more — right here without leaving the dashboard.', 'pc4s' ); ?>
				</p>
				<a href="<?php echo esc_url( $settings_url . '#ga' ); ?>" class="pc4s-btn pc4s-btn--primary pc4s-btn--sm">
					<?php esc_html_e( 'Set Up Google Analytics', 'pc4s' ); ?>
				</a>
			</div>
		</div>

		<?php elseif ( 'error' === $status ) : ?>

		<!-- Connection error -->
		<div class="pc4s-analytics-error">
			<svg class="pc4s-analytics-error__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
				<path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/>
			</svg>
			<div>
				<strong><?php esc_html_e( 'Google Analytics connection error.', 'pc4s' ); ?></strong>
				<p><?php esc_html_e( 'Could not fetch analytics data. Check your credentials and property ID in Settings.', 'pc4s' ); ?></p>
				<div class="pc4s-analytics-error__actions">
					<a href="<?php echo esc_url( $settings_url ); ?>" class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm"><?php esc_html_e( 'Go to Settings', 'pc4s' ); ?></a>
					<button type="button" class="pc4s-btn pc4s-btn--primary pc4s-btn--sm js-ga-refresh-btn"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'pc4s-admin-nonce' ) ); ?>">
						<?php esc_html_e( 'Retry', 'pc4s' ); ?>
					</button>
				</div>
			</div>
		</div>

		<?php else : ?>

		<!-- Analytics dashboard shell — populated via AJAX -->
		<div
			class="pc4s-analytics-dashboard js-analytics-dashboard"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'pc4s-admin-nonce' ) ); ?>"
			data-settings-url="<?php echo esc_url( $settings_url ); ?>"
		>
			<!-- Loading state -->
			<div class="pc4s-analytics-loading js-analytics-loading" aria-busy="true" aria-label="<?php esc_attr_e( 'Loading analytics data', 'pc4s' ); ?>">
				<span class="pc4s-analytics-loading__spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Loading analytics data\u2026', 'pc4s' ); ?>
			</div>

			<!-- Error state (shown if AJAX call fails) -->
			<div class="pc4s-analytics-fetch-error js-analytics-error" hidden>
				<svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>
				<span class="js-analytics-error-msg"></span>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm"><?php esc_html_e( 'Settings', 'pc4s' ); ?></a>
				<button type="button" class="pc4s-btn pc4s-btn--primary pc4s-btn--sm js-ga-refresh-btn"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'pc4s-admin-nonce' ) ); ?>">
					<?php esc_html_e( 'Retry', 'pc4s' ); ?>
				</button>
			</div>

			<!-- Dashboard content (hidden until data loads) -->
			<div class="pc4s-analytics-content js-analytics-content" hidden>

				<!-- Toolbar -->
				<div class="pc4s-analytics-toolbar">
					<p class="pc4s-analytics-toolbar__label">
						<?php esc_html_e( 'Last 30 days', 'pc4s' ); ?>
						&bull;
						<span class="js-analytics-cached-label"></span>
					</p>
					<div class="pc4s-analytics-toolbar__actions">
						<button type="button" class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm js-ga-refresh-btn"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'pc4s-admin-nonce' ) ); ?>">
							<svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" style="width:0.875em;height:0.875em"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Refresh', 'pc4s' ); ?>
						</button>
					</div>
				</div>

				<!-- Metric cards row -->
				<div class="pc4s-analytics-cards" role="list">

					<div class="pc4s-analytics-card" role="listitem">
						<div class="pc4s-analytics-card__trend pc4s-analytics-card__trend--blue" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="currentColor"><path d="M12.232 4.232a2.5 2.5 0 0 1 3.536 3.536l-1.225 1.224a.75.75 0 0 0 1.061 1.06l1.224-1.224a4 4 0 0 0-5.656-5.656l-3 3a4 4 0 0 0 .225 5.865.75.75 0 0 0 .977-1.138 2.5 2.5 0 0 1-.142-3.667l3-3z"/><path d="M11.603 7.963a.75.75 0 0 0-.977 1.138 2.5 2.5 0 0 1 .142 3.667l-3 3a2.5 2.5 0 0 1-3.536-3.536l1.225-1.224a.75.75 0 0 0-1.061-1.06l-1.224 1.224a4 4 0 1 0 5.656 5.656l3-3a4 4 0 0 0-.225-5.865z"/></svg>
						</div>
						<span class="pc4s-analytics-card__label"><?php esc_html_e( 'Page Views', 'pc4s' ); ?></span>
						<strong class="pc4s-analytics-card__value js-metric-pageviews">&mdash;</strong>
						<span class="pc4s-analytics-card__sublabel"><?php esc_html_e( 'Last 30 days', 'pc4s' ); ?></span>
					</div>

					<div class="pc4s-analytics-card" role="listitem">
						<div class="pc4s-analytics-card__trend pc4s-analytics-card__trend--green" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003z"/></svg>
						</div>
						<span class="pc4s-analytics-card__label"><?php esc_html_e( 'Active Users', 'pc4s' ); ?></span>
						<strong class="pc4s-analytics-card__value js-metric-users">&mdash;</strong>
						<span class="pc4s-analytics-card__sublabel"><?php esc_html_e( 'Last 30 days', 'pc4s' ); ?></span>
					</div>

					<div class="pc4s-analytics-card" role="listitem">
						<div class="pc4s-analytics-card__trend pc4s-analytics-card__trend--amber" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 0 1 1.75 2h16.5a.75.75 0 0 1 0 1.5H18v8.75A2.75 2.75 0 0 1 15.25 15h-1.072l.798 3.06a.75.75 0 0 1-1.452.38L13.731 18H6.27l-.793 3.44a.75.75 0 0 1-1.452-.38L4.823 15H3.75A2.75 2.75 0 0 1 1 12.25V3.5h-.25A.75.75 0 0 1 1 2.75zM2.5 3.5v8.75c0 .69.56 1.25 1.25 1.25h12.5c.69 0 1.25-.56 1.25-1.25V3.5H2.5z" clip-rule="evenodd"/></svg>
						</div>
						<span class="pc4s-analytics-card__label"><?php esc_html_e( 'Sessions', 'pc4s' ); ?></span>
						<strong class="pc4s-analytics-card__value js-metric-sessions">&mdash;</strong>
						<span class="pc4s-analytics-card__sublabel"><?php esc_html_e( 'Last 30 days', 'pc4s' ); ?></span>
					</div>

					<div class="pc4s-analytics-card" role="listitem">
						<div class="pc4s-analytics-card__trend pc4s-analytics-card__trend--red" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 0 1-1.162-.682 22.045 22.045 0 0 1-2.582-2.09c-1.843-1.883-3.235-4.151-3.235-7.13C2.65 4.383 5.082 2 8 2c1.433 0 2.628.453 3.5 1.254A4.488 4.488 0 0 1 12 2c2.918 0 5.35 2.383 5.35 5 0 2.979-1.392 5.247-3.235 7.13a22.053 22.053 0 0 1-2.582 2.09 22.014 22.014 0 0 1-1.162.682l-.02.01-.005.003-.002.001a.752.752 0 0 1-.69 0l-.002-.001z"/></svg>
						</div>
						<span class="pc4s-analytics-card__label"><?php esc_html_e( 'Bounce Rate', 'pc4s' ); ?></span>
						<strong class="pc4s-analytics-card__value js-metric-bounce">&mdash;</strong>
						<span class="pc4s-analytics-card__sublabel"><?php esc_html_e( 'Last 30 days', 'pc4s' ); ?></span>
					</div>

				</div><!-- .pc4s-analytics-cards -->

				<!-- Charts row -->
				<div class="pc4s-analytics-charts">

					<!-- Page views trend chart -->
					<div class="pc4s-analytics-chart-card">
						<h3 class="pc4s-analytics-chart-card__title"><?php esc_html_e( 'Page Views Trend', 'pc4s' ); ?></h3>
						<div class="pc4s-chart-container">
							<canvas id="pc4s-chart-trend" aria-label="<?php esc_attr_e( 'Page views over the last 30 days', 'pc4s' ); ?>" role="img"></canvas>
						</div>
					</div>

					<!-- Traffic sources chart -->
					<div class="pc4s-analytics-chart-card">
						<h3 class="pc4s-analytics-chart-card__title"><?php esc_html_e( 'Traffic Sources', 'pc4s' ); ?></h3>
						<div class="pc4s-chart-container pc4s-chart-container--doughnut">
							<canvas id="pc4s-chart-sources" aria-label="<?php esc_attr_e( 'Traffic sources breakdown', 'pc4s' ); ?>" role="img"></canvas>
						</div>
					</div>

				</div><!-- .pc4s-analytics-charts -->

				<!-- Devices + Top pages row -->
				<div class="pc4s-analytics-tables">

					<!-- Device breakdown -->
					<div class="pc4s-analytics-table-card">
						<h3 class="pc4s-analytics-table-card__title"><?php esc_html_e( 'Devices', 'pc4s' ); ?></h3>
						<table class="pc4s-analytics-table js-devices-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Device', 'pc4s' ); ?></th>
									<th class="pc4s-analytics-table__num"><?php esc_html_e( 'Sessions', 'pc4s' ); ?></th>
									<th class="pc4s-analytics-table__num"><?php esc_html_e( 'Share', 'pc4s' ); ?></th>
								</tr>
							</thead>
							<tbody class="js-devices-tbody">
								<tr><td colspan="3" class="pc4s-analytics-table__loading"><?php esc_html_e( 'Loading\u2026', 'pc4s' ); ?></td></tr>
							</tbody>
						</table>
					</div>

					<!-- Top pages -->
					<div class="pc4s-analytics-table-card">
						<h3 class="pc4s-analytics-table-card__title"><?php esc_html_e( 'Top Pages', 'pc4s' ); ?></h3>
						<div class="pc4s-analytics-table-wrap">
							<table class="pc4s-analytics-table js-pages-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Page', 'pc4s' ); ?></th>
										<th class="pc4s-analytics-table__num"><?php esc_html_e( 'Views', 'pc4s' ); ?></th>
										<th class="pc4s-analytics-table__num"><?php esc_html_e( 'Users', 'pc4s' ); ?></th>
									</tr>
								</thead>
								<tbody class="js-pages-tbody">
									<tr><td colspan="3" class="pc4s-analytics-table__loading"><?php esc_html_e( 'Loading\u2026', 'pc4s' ); ?></td></tr>
								</tbody>
							</table>
						</div>
					</div>

				</div><!-- .pc4s-analytics-tables -->

			</div><!-- .pc4s-analytics-content -->

		</div><!-- .pc4s-analytics-dashboard -->

		<?php endif;
	}
}
