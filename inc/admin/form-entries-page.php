<?php
/**
 * Form Entries Admin Page
 *
 * Lists stored submissions from {prefix}pc4s_form_entries.
 * Supports ?form_id= filtering and 25-row pagination.
 *
 * @package PC4S\Admin
 */

namespace PC4S\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormEntriesPage {

	const CAPABILITY = 'pc4s_manage';
	const PER_PAGE   = 25;

	/** @var FormEntriesPage|null */
	private static ?FormEntriesPage $instance = null;

	public static function get_instance(): FormEntriesPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_post_pc4s_export_entries', [ $this, 'export_csv' ] );
	}

	/**
	 * Render the Form Entries admin page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pc4s' ) );
		}

		global $wpdb;

		$table = $wpdb->prefix . 'pc4s_form_entries';

		// ── Delete action ─────────────────────────────────────────────────────
		$deleted      = false;
		$delete_error = false;
		if (
			isset( $_GET['action'], $_GET['entry_id'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'delete_entry' === sanitize_key( $_GET['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			$entry_id = (int) $_GET['entry_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( check_admin_referer( 'pc4s_delete_entry_' . $entry_id ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$result = $wpdb->delete( $table, [ 'id' => $entry_id ], [ '%d' ] );
				if ( false !== $result ) {
					$deleted = true;
				} else {
					$delete_error = true;
				}
			}
		}

		// ── Filters & pagination ──────────────────────────────────────────────
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filter_form = isset( $_GET['form_id'] ) ? sanitize_key( $_GET['form_id'] ) : '';
		$paged       = isset( $_GET['paged'] )   ? max( 1, (int) $_GET['paged'] )   : 1;
		// phpcs:enable
		$offset   = ( $paged - 1 ) * self::PER_PAGE;
		$base_url = admin_url( 'admin.php?page=pc4s-form-entries' );

		// ── Query ─────────────────────────────────────────────────────────────
		if ( $filter_form ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE form_id = %s", $filter_form )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE form_id = %s ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
					$filter_form,
					self::PER_PAGE,
					$offset
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
					self::PER_PAGE,
					$offset
				)
			);
		}

		$total_pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );

		// ── Render ────────────────────────────────────────────────────────────
		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		$export_args = [ 'action' => 'pc4s_export_entries' ];
		if ( $filter_form ) {
			$export_args['form_id'] = $filter_form;
		}
		$export_url = wp_nonce_url(
			add_query_arg( $export_args, admin_url( 'admin-post.php' ) ),
			'pc4s_export_entries'
		);
		?>
		<div class="wrap pc4s-admin-page pc4s-entries-page">

			<header class="pc4s-admin-header">
				<h1 class="pc4s-admin-header__title"><?php esc_html_e( 'Form Entries', 'pc4s' ); ?></h1>
			</header>

			<?php if ( $deleted ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--success" role="alert">
				<?php esc_html_e( 'Entry deleted successfully.', 'pc4s' ); ?>
			</div>
			<?php elseif ( $delete_error ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--error" role="alert">
				<?php esc_html_e( 'Could not delete the entry. Please try again.', 'pc4s' ); ?>
			</div>
			<?php endif; ?>

			<!-- ── Toolbar: filters + export ──────────────────────────────── -->
			<div class="pc4s-entries-toolbar">
			<nav class="pc4s-entries-filters" aria-label="<?php esc_attr_e( 'Filter entries by form', 'pc4s' ); ?>">
				<a
					href="<?php echo esc_url( $base_url ); ?>"
					class="pc4s-filter-tab<?php echo ! $filter_form ? ' pc4s-filter-tab--active' : ''; ?>"
					<?php echo ! $filter_form ? 'aria-current="page"' : ''; ?>
				>
					<?php esc_html_e( 'All', 'pc4s' ); ?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'form_id', 'newsletter', $base_url ) ); ?>"
					class="pc4s-filter-tab<?php echo 'newsletter' === $filter_form ? ' pc4s-filter-tab--active' : ''; ?>"
					<?php echo 'newsletter' === $filter_form ? 'aria-current="page"' : ''; ?>
				>
					<?php esc_html_e( 'Newsletter', 'pc4s' ); ?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'form_id', 'license_plate', $base_url ) ); ?>"
					class="pc4s-filter-tab<?php echo 'license_plate' === $filter_form ? ' pc4s-filter-tab--active' : ''; ?>"
					<?php echo 'license_plate' === $filter_form ? 'aria-current="page"' : ''; ?>
				>
					<?php esc_html_e( 'License Plate', 'pc4s' ); ?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'form_id', 'donate', $base_url ) ); ?>"
					class="pc4s-filter-tab<?php echo 'donate' === $filter_form ? ' pc4s-filter-tab--active' : ''; ?>"
					<?php echo 'donate' === $filter_form ? 'aria-current="page"' : ''; ?>
				>
					<?php esc_html_e( 'Donate', 'pc4s' ); ?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'form_id', 'contact_us', $base_url ) ); ?>"
					class="pc4s-filter-tab<?php echo 'contact_us' === $filter_form ? ' pc4s-filter-tab--active' : ''; ?>"
					<?php echo 'contact_us' === $filter_form ? 'aria-current="page"' : ''; ?>
				>
					<?php esc_html_e( 'Contact Us', 'pc4s' ); ?>
				</a>
			</nav>
			<a
				href="<?php echo esc_url( $export_url ); ?>"
				class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="15" height="15"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
				<?php esc_html_e( 'Export CSV', 'pc4s' ); ?>
			</a>
			</div><!-- .pc4s-entries-toolbar -->

			<?php if ( empty( $rows ) ) : ?>
			<div class="pc4s-entries-empty">
				<p><?php esc_html_e( 'No entries found.', 'pc4s' ); ?></p>
			</div>
			<?php else : ?>

			<!-- ── Tablenav top ────────────────────────────────────────────── -->
			<?php if ( $total_pages > 1 ) : ?>
			<?php $this->render_pagination( $total, $total_pages, $paged, $base_url, $filter_form ); ?>
			<?php endif; ?>

			<!-- ── Entries table ───────────────────────────────────────────── -->
			<div class="pc4s-table-wrap">
				<table class="pc4s-entries-table">
					<thead>
						<tr>
							<th scope="col" class="col-id"><?php esc_html_e( 'ID', 'pc4s' ); ?></th>
							<th scope="col" class="col-form"><?php esc_html_e( 'Form', 'pc4s' ); ?></th>
							<th scope="col" class="col-first-name"><?php esc_html_e( 'First Name', 'pc4s' ); ?></th>
							<th scope="col" class="col-last-name"><?php esc_html_e( 'Last Name', 'pc4s' ); ?></th>
							<th scope="col" class="col-email"><?php esc_html_e( 'Email', 'pc4s' ); ?></th>
							<th scope="col" class="col-subject"><?php esc_html_e( 'Subject', 'pc4s' ); ?></th>
							<th scope="col" class="col-message"><?php esc_html_e( 'Message', 'pc4s' ); ?></th>
							<th scope="col" class="col-source"><?php esc_html_e( 'Source Page', 'pc4s' ); ?></th>
							<th scope="col" class="col-date"><?php esc_html_e( 'Date', 'pc4s' ); ?></th>
							<th scope="col" class="col-actions"><?php esc_html_e( 'Actions', 'pc4s' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) :
							$data       = json_decode( $row->field_data, true ) ?: [];
							$email      = ! empty( $data['email'] )       ? $data['email']       : '—';
							$first_name = ! empty( $data['first_name'] )  ? $data['first_name']  : '—';
							$last_name  = ! empty( $data['last_name'] )   ? $data['last_name']   : '—';
							$subject    = ! empty( $data['subject_line'] ) ? $data['subject_line'] : '—';
							$message    = ! empty( $data['message'] )     ? wp_trim_words( $data['message'], 12, '…' ) : '—';
							$date       = get_date_from_gmt( $row->submitted_at, $date_format );
							$delete_url = wp_nonce_url(
								add_query_arg(
									[ 'action' => 'delete_entry', 'entry_id' => $row->id ],
									$filter_form ? add_query_arg( 'form_id', $filter_form, $base_url ) : $base_url
								),
								'pc4s_delete_entry_' . $row->id
							);
						?>
						<tr>
							<td class="col-id"><?php echo (int) $row->id; ?></td>
							<td class="col-form">
								<code class="pc4s-form-badge"><?php echo esc_html( $row->form_id ); ?></code>
							</td>
							<td class="col-first-name"><?php echo esc_html( $first_name ); ?></td>
							<td class="col-last-name"><?php echo esc_html( $last_name ); ?></td>
							<td class="col-email"><?php echo esc_html( $email ); ?></td>
							<td class="col-subject"><?php echo esc_html( $subject ); ?></td>
							<td class="col-message"><?php echo esc_html( $message ); ?></td>
							<td class="col-source">
								<?php if ( $row->source_page ) : ?>
								<a
									href="<?php echo esc_url( $row->source_page ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="pc4s-entries-table__source-link"
								><?php echo esc_html( $row->source_page ); ?></a>
								<?php else : ?>
								<span class="pc4s-text-muted">—</span>
								<?php endif; ?>
							</td>
							<td class="col-date"><?php echo esc_html( $date ); ?></td>
							<td class="col-actions">
								<a
									href="<?php echo esc_url( $delete_url ); ?>"
									class="pc4s-entries-table__delete-link"
									onclick="return confirm('<?php esc_attr_e( 'Delete this entry? This cannot be undone.', 'pc4s' ); ?>')"
								><?php esc_html_e( 'Delete', 'pc4s' ); ?></a>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div><!-- .pc4s-table-wrap -->

			<!-- ── Tablenav bottom ─────────────────────────────────────────── -->
			<?php if ( $total_pages > 1 ) : ?>
			<?php $this->render_pagination( $total, $total_pages, $paged, $base_url, $filter_form ); ?>
			<?php endif; ?>

			<?php endif; ?>

		</div><!-- .pc4s-entries-page -->
		<?php
	}

	// ─── Export ──────────────────────────────────────────────────────────────

	/**
	 * Handle the CSV export admin-post action.
	 * Streams all entries (or a single form's entries) as a downloadable CSV.
	 */
	public function export_csv(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to export entries.', 'pc4s' ) );
		}

		check_admin_referer( 'pc4s_export_entries' );

		global $wpdb;
		$table       = $wpdb->prefix . 'pc4s_form_entries';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_form = isset( $_GET['form_id'] ) ? sanitize_key( $_GET['form_id'] ) : '';

		if ( $filter_form ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE form_id = %s ORDER BY submitted_at DESC",
					$filter_form
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY submitted_at DESC" );
		}

		$filename = 'form-entries'
			. ( $filter_form ? '-' . $filter_form : '' )
			. '-' . gmdate( 'Y-m-d' )
			. '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM so Excel reads the file correctly.
		fwrite( $output, "\xEF\xBB\xBF" );

		// Column headers.
		fputcsv( $output, [
			'ID', 'Form', 'First Name', 'Last Name', 'Company Name',
			'Email', 'Amount', 'Street Address', 'City', 'State',
			'Zip Code', 'County', 'Subject', 'Message', 'Source Page', 'Date (UTC)',
		] );

		foreach ( ( $rows ?: [] ) as $row ) {
			$data = json_decode( $row->field_data, true ) ?: [];
			fputcsv( $output, [
				$row->id,
				$row->form_id,
				$data['first_name']     ?? '',
				$data['last_name']      ?? '',
				$data['company_name']   ?? '',
				$data['email']          ?? '',
				$data['amount']         ?? '',
				$data['street_address'] ?? '',
				$data['city']           ?? '',
				$data['state']          ?? '',
				$data['zip_code']       ?? '',
				$data['county']         ?? '',
				$data['subject_line']   ?? '',
				$data['message']        ?? '',
				$row->source_page ?? '',
				$row->submitted_at,
			] );
		}

		fclose( $output );
		exit;
	}

	// ─── Helpers ─────────────────────────────────────────────────────────────

	private function render_pagination( int $total, int $total_pages, int $paged, string $base_url, string $filter_form ): void {
		$paginate_base = add_query_arg(
			'paged',
			'%#%',
			$filter_form ? add_query_arg( 'form_id', $filter_form, $base_url ) : $base_url
		);

		$page_links = paginate_links( [
			'base'      => $paginate_base,
			'format'    => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total'     => $total_pages,
			'current'   => $paged,
		] );

		$count_text = sprintf(
			/* translators: %d: number of entries */
			_n( '%d entry', '%d entries', $total, 'pc4s' ),
			$total
		);
		?>
		<div class="pc4s-tablenav">
			<span class="pc4s-tablenav__count"><?php echo esc_html( $count_text ); ?></span>
			<?php if ( $page_links ) : ?>
			<div class="pc4s-tablenav__links">
				<?php echo $page_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
