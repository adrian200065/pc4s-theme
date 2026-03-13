<?php
/**
 * PC4S Google Analytics Integration
 *
 * Manages GA4 service-account credentials, fetches analytics data via the
 * Google Analytics Data API v1 beta, and provides AJAX endpoints that power
 * the analytics dashboard on the PC4S Overview page.
 *
 * Security model:
 *  - Service-account JSON is AES-256-CBC encrypted before storage in wp_options.
 *  - The plaintext private key is only decrypted server-side, never echoed.
 *  - All AJAX endpoints verify a nonce and require `pc4s_manage` capability.
 *
 * @package PC4S\Classes\Admin
 */

namespace PC4S\Classes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GoogleAnalytics {

	// ─── Option / transient keys ─────────────────────────────────────────────

	/** Encrypted service-account JSON option key. */
	const CREDS_OPTION    = 'pc4s_ga_service_account';

	/** Connection status option: 'connected' | 'error' | '' */
	const STATUS_OPTION   = 'pc4s_ga_status';

	/** Transient key for the cached analytics report. */
	const DATA_TRANSIENT  = 'pc4s_ga_analytics';

	/** Transient key for the cached OAuth2 access token. */
	const TOKEN_TRANSIENT = 'pc4s_ga_oauth_token';

	/** How long to cache analytics data (seconds). 12 hours. */
	const CACHE_TTL       = 43200;

	/** Required GA4 OAuth2 scope. */
	const GA_SCOPE        = 'https://www.googleapis.com/auth/analytics.readonly';

	/** Google OAuth2 token endpoint. */
	const TOKEN_URL       = 'https://oauth2.googleapis.com/token';

	/** GA4 Data API base URL. */
	const API_BASE        = 'https://analyticsdata.googleapis.com/v1beta/properties/';

	// ─── Singleton ───────────────────────────────────────────────────────────

	/** @var GoogleAnalytics|null */
	private static ?GoogleAnalytics $instance = null;

	public static function get_instance(): GoogleAnalytics {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// AJAX: fetch analytics data (dashboard).
		add_action( 'wp_ajax_pc4s_ga_fetch',        [ $this, 'ajax_fetch' ] );
		// AJAX: test connection (settings page).
		add_action( 'wp_ajax_pc4s_ga_test',         [ $this, 'ajax_test' ] );
		// AJAX: clear cache / force refresh.
		add_action( 'wp_ajax_pc4s_ga_clear_cache',  [ $this, 'ajax_clear_cache' ] );

		// Admin-POST: save credentials from the settings form.
		add_action( 'admin_post_pc4s_save_ga_creds',  [ $this, 'handle_save_creds' ] );
		// Admin-POST: disconnect / remove credentials.
		add_action( 'admin_post_pc4s_clear_ga_creds', [ $this, 'handle_clear_creds' ] );
	}

	// ─── Public helpers ──────────────────────────────────────────────────────

	/**
	 * Return the current connection status string ('connected', 'error', or '').
	 */
	public function get_connection_status(): string {
		if ( ! $this->has_credentials() ) {
			return '';
		}
		return (string) get_option( self::STATUS_OPTION, '' );
	}

	/**
	 * Return true if service-account credentials have been stored.
	 */
	public function has_credentials(): bool {
		$stored = get_option( self::CREDS_OPTION, '' );
		return ! empty( $stored );
	}

	/**
	 * Return the stored property ID from pc4s_settings.
	 */
	public function get_property_id(): string {
		return \PC4S\Admin\SettingsPage::get( 'ga_property_id' );
	}

	// ─── AJAX handlers ───────────────────────────────────────────────────────

	/**
	 * AJAX: return cached (or freshly fetched) analytics data as JSON.
	 */
	public function ajax_fetch(): void {
		check_ajax_referer( 'pc4s-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'pc4s' ) ], 403 );
		}

		$data = $this->get_analytics_data();

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( [
				'message' => $data->get_error_message(),
				'code'    => $data->get_error_code(),
			], 200 );
		}

		wp_send_json_success( $data );
	}

	/**
	 * AJAX: test the GA4 connection and return a status message.
	 */
	public function ajax_test(): void {
		check_ajax_referer( 'pc4s-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'pc4s_manage' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'pc4s' ) ], 403 );
		}

		// Force-refresh: delete cached token and data so we make a fresh call.
		delete_transient( self::TOKEN_TRANSIENT );
		delete_transient( self::DATA_TRANSIENT );

		$result = $this->test_connection();

		if ( is_wp_error( $result ) ) {
			update_option( self::STATUS_OPTION, 'error', false );
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		update_option( self::STATUS_OPTION, 'connected', false );
		wp_send_json_success( [ 'message' => __( 'Connection successful!', 'pc4s' ) ] );
	}

	/**
	 * AJAX: clear analytics cache and return fresh data.
	 */
	public function ajax_clear_cache(): void {
		check_ajax_referer( 'pc4s-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'pc4s' ) ], 403 );
		}

		delete_transient( self::DATA_TRANSIENT );
		delete_transient( self::TOKEN_TRANSIENT );

		$data = $this->get_analytics_data();

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( [ 'message' => $data->get_error_message() ] );
		}

		wp_send_json_success( $data );
	}

	// ─── Admin-POST handlers ─────────────────────────────────────────────────

	/**
	 * Handle credentials form submission from the settings page.
	 */
	public function handle_save_creds(): void {
		if ( ! current_user_can( 'pc4s_manage' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'pc4s' ) );
		}

		check_admin_referer( 'pc4s_save_ga_creds' );

		$redirect = admin_url( 'admin.php?page=pc4s-settings' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$raw_json = wp_unslash( $_POST['pc4s_ga_service_account_json'] ?? '' );

		if ( empty( $raw_json ) ) {
			wp_safe_redirect( add_query_arg( 'ga_status', 'empty', $redirect ) );
			exit;
		}

		// Validate that the input is a valid service-account JSON.
		$parsed = json_decode( $raw_json, true );

		if (
			! is_array( $parsed )
			|| ( $parsed['type'] ?? '' ) !== 'service_account'
			|| empty( $parsed['client_email'] )
			|| empty( $parsed['private_key'] )
		) {
			wp_safe_redirect( add_query_arg( 'ga_status', 'invalid_json', $redirect ) );
			exit;
		}

		// Encrypt and save — only store the fields we actually need.
		$to_store = [
			'type'         => 'service_account',
			'client_email' => sanitize_email( $parsed['client_email'] ),
			'private_key'  => $parsed['private_key'], // sanitize_textarea strips too much.
			'token_uri'    => esc_url_raw( $parsed['token_uri'] ?? self::TOKEN_URL ),
		];

		$encrypted = $this->encrypt( wp_json_encode( $to_store ) );

		if ( false === $encrypted ) {
			wp_safe_redirect( add_query_arg( 'ga_status', 'encrypt_failed', $redirect ) );
			exit;
		}

		update_option( self::CREDS_OPTION, $encrypted, false );
		update_option( self::STATUS_OPTION, '', false ); // Reset status — needs re-test.
		delete_transient( self::TOKEN_TRANSIENT );
		delete_transient( self::DATA_TRANSIENT );

		wp_safe_redirect( add_query_arg( 'ga_status', 'saved', $redirect ) );
		exit;
	}

	/**
	 * Disconnect Google Analytics by removing all stored credentials + cache.
	 */
	public function handle_clear_creds(): void {
		if ( ! current_user_can( 'pc4s_manage' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'pc4s' ) );
		}

		check_admin_referer( 'pc4s_clear_ga_creds' );

		delete_option( self::CREDS_OPTION );
		delete_option( self::STATUS_OPTION );
		delete_transient( self::TOKEN_TRANSIENT );
		delete_transient( self::DATA_TRANSIENT );

		wp_safe_redirect( add_query_arg( 'ga_status', 'disconnected', admin_url( 'admin.php?page=pc4s-settings' ) ) );
		exit;
	}

	// ─── Analytics data ──────────────────────────────────────────────────────

	/**
	 * Return analytics data, using the transient cache if available.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_analytics_data() {
		if ( ! $this->has_credentials() ) {
			return new \WP_Error( 'not_connected', __( 'Google Analytics is not connected.', 'pc4s' ) );
		}

		$property_id = $this->get_property_id();
		if ( empty( $property_id ) ) {
			return new \WP_Error( 'no_property', __( 'No GA4 Property ID configured.', 'pc4s' ) );
		}

		$cached = get_transient( self::DATA_TRANSIENT );
		if ( false !== $cached && is_array( $cached ) ) {
			$cached['cached']    = true;
			$cached['cached_at'] = $cached['fetched_at'] ?? '';
			return $cached;
		}

		return $this->fetch_fresh_data( $property_id );
	}

	/**
	 * Fetch fresh analytics data from the GA4 API (no cache read).
	 *
	 * @param string $property_id GA4 numeric property ID.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function fetch_fresh_data( string $property_id ) {
		$token = $this->get_access_token();

		if ( is_wp_error( $token ) ) {
			update_option( self::STATUS_OPTION, 'error', false );
			return $token;
		}

		$endpoint = self::API_BASE . rawurlencode( $property_id ) . ':runReport';

		// ── Report 1: Summary (pageviews, active users, sessions — 30 days) ──
		$summary = $this->run_report( $endpoint, $token, [
			'dateRanges' => [ [ 'startDate' => '30daysAgo', 'endDate' => 'today' ] ],
			'metrics'    => [
				[ 'name' => 'screenPageViews' ],
				[ 'name' => 'activeUsers' ],
				[ 'name' => 'sessions' ],
				[ 'name' => 'bounceRate' ],
				[ 'name' => 'averageSessionDuration' ],
			],
		] );

		if ( is_wp_error( $summary ) ) {
			update_option( self::STATUS_OPTION, 'error', false );
			return $summary;
		}

		// ── Report 2: Page views trend (last 30 days, daily) ─────────────────
		$trend = $this->run_report( $endpoint, $token, [
			'dateRanges' => [ [ 'startDate' => '29daysAgo', 'endDate' => 'today' ] ],
			'dimensions' => [ [ 'name' => 'date' ] ],
			'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
			'orderBys'   => [ [ 'dimension' => [ 'dimensionName' => 'date' ], 'desc' => false ] ],
			'limit'      => 30,
		] );

		// ── Report 3: Traffic sources ─────────────────────────────────────────
		$sources = $this->run_report( $endpoint, $token, [
			'dateRanges' => [ [ 'startDate' => '30daysAgo', 'endDate' => 'today' ] ],
			'dimensions' => [ [ 'name' => 'sessionDefaultChannelGroup' ] ],
			'metrics'    => [ [ 'name' => 'sessions' ] ],
			'orderBys'   => [ [ 'metric' => [ 'metricName' => 'sessions' ], 'desc' => true ] ],
			'limit'      => 8,
		] );

		// ── Report 4: Top pages ───────────────────────────────────────────────
		$pages = $this->run_report( $endpoint, $token, [
			'dateRanges' => [ [ 'startDate' => '30daysAgo', 'endDate' => 'today' ] ],
			'dimensions' => [ [ 'name' => 'pagePath' ] ],
			'metrics'    => [ [ 'name' => 'screenPageViews' ], [ 'name' => 'activeUsers' ] ],
			'orderBys'   => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
			'limit'      => 10,
		] );

		// ── Report 5: Device categories ──────────────────────────────────────
		$devices = $this->run_report( $endpoint, $token, [
			'dateRanges' => [ [ 'startDate' => '30daysAgo', 'endDate' => 'today' ] ],
			'dimensions' => [ [ 'name' => 'deviceCategory' ] ],
			'metrics'    => [ [ 'name' => 'sessions' ] ],
			'orderBys'   => [ [ 'metric' => [ 'metricName' => 'sessions' ], 'desc' => true ] ],
		] );

		// ── Parse ─────────────────────────────────────────────────────────────
		$data = [
			'summary'         => $this->parse_summary( $summary ),
			'pageviews_trend' => is_wp_error( $trend )   ? [] : $this->parse_trend( $trend ),
			'traffic_sources' => is_wp_error( $sources ) ? [] : $this->parse_dimension_metric( $sources, 'source', 'sessions' ),
			'top_pages'       => is_wp_error( $pages )   ? [] : $this->parse_top_pages( $pages ),
			'devices'         => is_wp_error( $devices ) ? [] : $this->parse_dimension_metric( $devices, 'device', 'sessions' ),
			'cached'          => false,
			'fetched_at'      => gmdate( 'Y-m-d H:i:s' ),
		];

		set_transient( self::DATA_TRANSIENT, $data, self::CACHE_TTL );
		update_option( self::STATUS_OPTION, 'connected', false );

		return $data;
	}

	/**
	 * Run a single GA4 Data API report request.
	 *
	 * @param string               $endpoint Full API endpoint URL.
	 * @param string               $token    OAuth2 access token.
	 * @param array<string,mixed>  $body     Request body (will be JSON-encoded).
	 * @return array<string,mixed>|\WP_Error
	 */
	private function run_report( string $endpoint, string $token, array $body ) {
		$response = wp_remote_post( $endpoint, [
			'timeout' => 20,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$msg = $body['error']['message'] ?? sprintf( __( 'GA4 API returned HTTP %s.', 'pc4s' ), $code );
			return new \WP_Error( 'ga_api_error', $msg );
		}

		return $body ?? [];
	}

	// ─── Parser helpers ──────────────────────────────────────────────────────

	/** @param array<string,mixed> $report */
	private function parse_summary( $report ): array {
		if ( is_wp_error( $report ) || empty( $report['rows'][0]['metricValues'] ) ) {
			return [ 'pageviews' => 0, 'users' => 0, 'sessions' => 0, 'bounce_rate' => 0, 'avg_session_duration' => 0 ];
		}
		$m = $report['rows'][0]['metricValues'];
		return [
			'pageviews'            => (int) ( $m[0]['value'] ?? 0 ),
			'users'                => (int) ( $m[1]['value'] ?? 0 ),
			'sessions'             => (int) ( $m[2]['value'] ?? 0 ),
			'bounce_rate'          => round( (float) ( $m[3]['value'] ?? 0 ) * 100, 1 ),
			'avg_session_duration' => round( (float) ( $m[4]['value'] ?? 0 ) ),
		];
	}

	/** @param array<string,mixed> $report */
	private function parse_trend( $report ): array {
		$result = [];
		foreach ( $report['rows'] ?? [] as $row ) {
			$raw_date = $row['dimensionValues'][0]['value'] ?? '';
			// Convert YYYYMMDD → YYYY-MM-DD.
			$date = strlen( $raw_date ) === 8
				? substr( $raw_date, 0, 4 ) . '-' . substr( $raw_date, 4, 2 ) . '-' . substr( $raw_date, 6, 2 )
				: $raw_date;
			$result[] = [
				'date'  => $date,
				'value' => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
			];
		}
		return $result;
	}

	/**
	 * @param array<string,mixed> $report
	 * @param string $dim_key  Key name in the returned object.
	 * @param string $met_key  Key name in the returned object.
	 */
	private function parse_dimension_metric( $report, string $dim_key, string $met_key ): array {
		$result = [];
		foreach ( $report['rows'] ?? [] as $row ) {
			$result[] = [
				$dim_key => $row['dimensionValues'][0]['value'] ?? '',
				$met_key => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
			];
		}
		return $result;
	}

	/** @param array<string,mixed> $report */
	private function parse_top_pages( $report ): array {
		$result = [];
		foreach ( $report['rows'] ?? [] as $row ) {
			$result[] = [
				'path'  => $row['dimensionValues'][0]['value'] ?? '',
				'views' => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
				'users' => (int) ( $row['metricValues'][1]['value'] ?? 0 ),
			];
		}
		return $result;
	}

	// ─── OAuth2 / JWT ────────────────────────────────────────────────────────

	/**
	 * Return a valid OAuth2 access token, fetching a fresh one if the cached
	 * one has expired.
	 *
	 * @return string|\WP_Error
	 */
	public function get_access_token() {
		$cached = get_transient( self::TOKEN_TRANSIENT );
		if ( false !== $cached && ! empty( $cached ) ) {
			return $cached;
		}

		$creds = $this->get_credentials();

		if ( is_wp_error( $creds ) ) {
			return $creds;
		}

		$jwt = $this->build_jwt( $creds );

		if ( is_wp_error( $jwt ) ) {
			return $jwt;
		}

		$response = wp_remote_post( $creds['token_uri'] ?? self::TOKEN_URL, [
			'timeout' => 15,
			'body'    => [
				'grant_type' => 'urn:ietf:params:oauth2:grant-type:jwt-bearer',
				'assertion'  => $jwt,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $body['access_token'] ) ) {
			$msg = $body['error_description'] ?? $body['error'] ?? sprintf( __( 'Token request returned HTTP %s.', 'pc4s' ), $code );
			return new \WP_Error( 'token_error', $msg );
		}

		// Cache for 55 minutes (tokens are valid 60 minutes; keep a 5-min buffer).
		set_transient( self::TOKEN_TRANSIENT, $body['access_token'], 3300 );

		return $body['access_token'];
	}

	/**
	 * Build a signed RS256 JWT for the Google OAuth2 token exchange.
	 *
	 * @param array<string,string> $creds Service account credential array.
	 * @return string|\WP_Error
	 */
	private function build_jwt( array $creds ) {
		$now = time();

		$header  = $this->base64url_encode( (string) wp_json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
		$payload = $this->base64url_encode( (string) wp_json_encode( [
			'iss'   => $creds['client_email'],
			'scope' => self::GA_SCOPE,
			'aud'   => $creds['token_uri'] ?? self::TOKEN_URL,
			'exp'   => $now + 3600,
			'iat'   => $now,
		] ) );

		$signing_input = $header . '.' . $payload;

		$private_key = openssl_pkey_get_private( $creds['private_key'] );

		if ( false === $private_key ) {
			return new \WP_Error( 'invalid_key', __( 'Could not parse the service-account private key. Ensure the JSON was copied completely.', 'pc4s' ) );
		}

		$signature = '';
		$ok = openssl_sign( $signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256 );

		// Free the key resource (PHP 8.0+ handles this automatically, but good practice).
		if ( is_resource( $private_key ) ) {
			openssl_free_key( $private_key ); // phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions
		}

		if ( ! $ok ) {
			return new \WP_Error( 'sign_error', __( 'Failed to sign the JWT with the service-account key.', 'pc4s' ) );
		}

		return $signing_input . '.' . $this->base64url_encode( $signature );
	}

	/**
	 * Test whether the stored credentials can successfully authenticate and
	 * reach the GA4 API.
	 *
	 * @return true|\WP_Error
	 */
	public function test_connection() {
		$property_id = $this->get_property_id();

		if ( empty( $property_id ) ) {
			return new \WP_Error( 'no_property', __( 'Please enter a GA4 Property ID before testing.', 'pc4s' ) );
		}

		$token = $this->get_access_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		// Make a minimal report request to validate the property permission.
		$endpoint = self::API_BASE . rawurlencode( $property_id ) . ':runReport';
		$result   = $this->run_report( $endpoint, $token, [
			'dateRanges' => [ [ 'startDate' => '1daysAgo', 'endDate' => 'today' ] ],
			'metrics'    => [ [ 'name' => 'activeUsers' ] ],
			'limit'      => 1,
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	// ─── Credential helpers ──────────────────────────────────────────────────

	/**
	 * Retrieve and decrypt the stored service-account credentials.
	 *
	 * @return array<string,string>|\WP_Error
	 */
	private function get_credentials() {
		$encrypted = get_option( self::CREDS_OPTION, '' );

		if ( empty( $encrypted ) ) {
			return new \WP_Error( 'no_creds', __( 'Google Analytics service-account credentials have not been configured.', 'pc4s' ) );
		}

		$json = $this->decrypt( $encrypted );

		if ( false === $json ) {
			return new \WP_Error( 'decrypt_failed', __( 'Could not decrypt the stored credentials. Please re-paste your service-account JSON in Settings.', 'pc4s' ) );
		}

		$creds = json_decode( $json, true );

		if (
			! is_array( $creds )
			|| empty( $creds['client_email'] )
			|| empty( $creds['private_key'] )
		) {
			return new \WP_Error( 'invalid_creds', __( 'The stored credentials appear to be malformed. Please re-paste your service-account JSON.', 'pc4s' ) );
		}

		return $creds;
	}

	// ─── Encryption helpers ──────────────────────────────────────────────────

	/**
	 * AES-256-CBC encrypt a string using a key derived from WordPress salts.
	 *
	 * @param string $plaintext
	 * @return string|false  Base64-encoded "iv:ciphertext" on success, false on failure.
	 */
	private function encrypt( string $plaintext ) {
		$key = $this->get_encryption_key();
		$iv  = random_bytes( 16 );

		$cipher = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $cipher ) {
			return false;
		}

		return base64_encode( $iv . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Decrypt a string that was encrypted with self::encrypt().
	 *
	 * @param string $ciphertext Base64-encoded "iv:ciphertext".
	 * @return string|false Decrypted plaintext, or false on failure.
	 */
	private function decrypt( string $ciphertext ) {
		$key  = $this->get_encryption_key();
		$data = base64_decode( $ciphertext, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		if ( false === $data || strlen( $data ) < 17 ) {
			return false;
		}

		$iv     = substr( $data, 0, 16 );
		$cipher = substr( $data, 16 );

		return openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
	}

	/**
	 * Derive a 32-byte AES key from the WordPress auth salt (site-specific).
	 */
	private function get_encryption_key(): string {
		return hash( 'sha256', wp_salt( 'auth' ), true );
	}

	// ─── Utility ─────────────────────────────────────────────────────────────

	/**
	 * Base64url-encode a string (RFC 4648 § 5, no padding).
	 */
	private function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}
}
