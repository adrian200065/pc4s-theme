<?php
/**
 * PC4S Settings Page
 *
 * Central settings panel for site-wide integrations. Currently includes:
 *   - PayPal (email, Client ID, Donate button ID, License Plate button ID,
 *     sandbox mode, currency)
 *   - Google Analytics (GA4 Measurement ID, enable/disable)
 *
 * Settings are stored in a single option key (`pc4s_settings`) to keep DB
 * reads minimal. Templates and other classes should retrieve values through
 * the static `SettingsPage::get()` helper, which caches the option in a
 * static variable so the DB is queried at most once per request.
 *
 * Save flow (WordPress Settings API):
 *   1. Form POSTs to options.php.
 *   2. WordPress verifies nonce, calls sanitize(), stores the option.
 *   3. WordPress redirects back with ?settings-updated=true.
 *   4. render_page() detects the query arg and shows the success notice.
 *
 * @package PC4S\Admin
 */

namespace PC4S\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsPage {

	// ─── Constants ──────────────────────────────────────────────────────────

	/** WordPress option key for all PC4S settings. */
	const OPTION_KEY  = 'pc4s_settings';

	/** Settings API group name. */
	const OPTION_GROUP = 'pc4s_settings_group';

	/** Capability required to view / save this page. */
const CAPABILITY  = 'pc4s_manage';

	/**
	 * PayPal button setting keys keyed by form ID.
	 *
	 * @var array<string,string>
	 */
	private const PAYPAL_BUTTON_KEY_MAP = [
		'donate'        => 'paypal_donate_button_id',
		'license_plate' => 'paypal_license_plate_button_id',
	];

	// ─── Singleton ──────────────────────────────────────────────────────────

	/** @var SettingsPage|null */
	private static ?SettingsPage $instance = null;

	public static function get_instance(): SettingsPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'option_page_capability_' . self::OPTION_GROUP, [ $this, 'option_page_capability' ] );
	}

	// ─── Settings API ───────────────────────────────────────────────────────

	/**
	 * Register the option and attach the sanitize callback.
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_KEY,
			[ 'sanitize_callback' => [ $this, 'sanitize' ] ]
		);
	}

	/**
	 * Override the Settings API capability used by options.php.
	 *
	 * @return string
	 */
	public function option_page_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Sanitize all settings fields before they are stored.
	 *
	 * @param mixed $input Raw $_POST data submitted by the settings form.
	 * @return array<string, mixed> Sanitized option array.
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		$existing = (array) get_option( self::OPTION_KEY, [] );

		$sanitize_text = static function ( string $key, string $default = '' ) use ( $input, $existing ): string {
			if ( array_key_exists( $key, $input ) ) {
				return sanitize_text_field( $input[ $key ] ?? '' );
			}

			return (string) ( $existing[ $key ] ?? $default );
		};

		$sanitize_email = static function ( string $key, string $default = '' ) use ( $input, $existing ): string {
			if ( array_key_exists( $key, $input ) ) {
				return sanitize_email( $input[ $key ] ?? '' );
			}

			return (string) ( $existing[ $key ] ?? $default );
		};

		$sanitize_url = static function ( string $key, string $default = '' ) use ( $input, $existing ): string {
			if ( array_key_exists( $key, $input ) ) {
				return sanitize_url( $input[ $key ] ?? '' );
			}

			return (string) ( $existing[ $key ] ?? $default );
		};

		$sanitize_button_id = static function ( string $key ) use ( $sanitize_text ): string {
			return strtoupper( $sanitize_text( $key ) );
		};

		return [
			// ── PayPal ─────────────────────────────────────────────────────
			'paypal_email'            => $sanitize_email( 'paypal_email' ),
			'paypal_client_id'        => $sanitize_text( 'paypal_client_id' ),
			'paypal_donate_button_id' => $sanitize_button_id( 'paypal_donate_button_id' ),
			'paypal_license_plate_button_id' => $sanitize_button_id( 'paypal_license_plate_button_id' ),
			'paypal_sandbox'          => ! empty( $input['paypal_sandbox'] ) ? '1' : '0',
			'paypal_currency'         => strtoupper( $sanitize_text( 'paypal_currency', 'USD' ) ),

			// ── Facebook ────────────────────────────────────────────────────
			'facebook_page_url'       => $sanitize_url( 'facebook_page_url' ),
			'facebook_page_id'        => $sanitize_text( 'facebook_page_id' ),
			'facebook_access_token'   => $sanitize_text( 'facebook_access_token' ),
			'facebook_feed_limit'     => min( 50, max( 1, absint( $input['facebook_feed_limit'] ?? ( $existing['facebook_feed_limit'] ?? 10 ) ) ) ),

			// ── Google Analytics ────────────────────────────────────────────
			'ga_enabled'              => ! empty( $input['ga_enabled'] ) ? '1' : '0',
			'ga_measurement_id'       => $sanitize_text( 'ga_measurement_id' ),
			'ga_property_id'          => $sanitize_text( 'ga_property_id' ),
		];
	}

	// ─── Render ─────────────────────────────────────────────────────────────

	/**
	 * Output the admin settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pc4s' ) );
		}

		$opts = (array) get_option( self::OPTION_KEY, [] );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$just_saved = isset( $_GET['settings-updated'] ) && '1' === (string) $_GET['settings-updated'];
		// phpcs:enable

		$field   = static fn( string $key ): string => esc_attr( self::OPTION_KEY . '[' . $key . ']' );
		$val     = static fn( string $key, string $default = '' ): string => esc_attr( (string) ( $opts[ $key ] ?? $default ) );
		$checked = static fn( string $key ): string => checked( '1', $opts[ $key ] ?? '0', false );
		// $locked: renders a read-only "Set via constant" notice for credential fields
		// that are configured in wp-config.php — the live value is never echoed.
		$locked  = static fn( string $key ): bool => self::is_constant_override( $key );
		$paypal_notices = $this->get_paypal_notices();
		?>
		<div class="wrap pc4s-admin-page pc4s-settings-page">

			<header class="pc4s-admin-header">
				<h1 class="pc4s-admin-header__title"><?php esc_html_e( 'Settings', 'pc4s' ); ?></h1>
				<p class="pc4s-admin-header__description">
					<?php esc_html_e( 'Global integration settings for PayPal, Facebook, and Google Analytics. More services can be added here over time.', 'pc4s' ); ?>
				</p>
			</header>

			<?php if ( $just_saved ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--success" role="status" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
				</svg>
				<span><?php esc_html_e( 'Settings saved successfully.', 'pc4s' ); ?></span>
			</div>
			<?php endif; ?>

			<?php foreach ( $paypal_notices as $paypal_notice ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--<?php echo esc_attr( $paypal_notice['type'] ); ?>" role="status" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<?php if ( 'success' === $paypal_notice['type'] ) : ?>
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
					<?php else : ?>
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
					<?php endif; ?>
				</svg>
				<span><?php echo esc_html( $paypal_notice['text'] ); ?></span>
			</div>
			<?php endforeach; ?>

			<form method="post" action="options.php" novalidate>

				<?php settings_fields( self::OPTION_GROUP ); ?>

				<!-- ── Card: PayPal Settings ─────────────────────────────── -->
				<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'PayPal Settings', 'pc4s' ); ?>">

					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php esc_html_e( 'PayPal', 'pc4s' ); ?></h2>
							<code class="pc4s-form-badge">paypal</code>
						</div>
					</header>

					<div class="pc4s-form-card__body">

						<!-- ── Step-by-step setup guide (collapsible) ──────── -->
						<details class="pc4s-ga-setup-guide">
							<summary class="pc4s-ga-setup-guide__summary">
								<svg class="pc4s-ga-setup-guide__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/></svg>
								<?php esc_html_e( 'How to connect PayPal (setup guide)', 'pc4s' ); ?>
							</summary>
							<div class="pc4s-ga-setup-guide__body">
								<ol class="pc4s-ga-setup-steps">
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Create or access a PayPal Business account', 'pc4s' ); ?></strong>
										<p><?php echo wp_kses( __( 'Go to <a href="https://www.paypal.com/business" target="_blank" rel="noopener noreferrer">paypal.com/business</a> and sign in. If you don\'t have a business account, create one — a business account is required to accept payments.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></p>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Get your Client ID from the Developer Dashboard', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php echo wp_kses( __( 'Log in to the <a href="https://developer.paypal.com" target="_blank" rel="noopener noreferrer">PayPal Developer Dashboard</a>.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></li>
											<li><?php esc_html_e( 'Go to My Apps &amp; Credentials.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Under REST API apps, open your app or create a new one.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( "Copy the Client ID (starts with 'A' or 'BAA'). Use the Sandbox ID for testing, the Live ID for production.", 'pc4s' ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Create two hosted buttons in PayPal', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php esc_html_e( 'In your PayPal account go to Sell → More ways to get paid and create or open a hosted button for donations.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Copy the hostedButtonId for that button and save it as the Donate Hosted Button ID below.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Create or open a separate hosted button for the License Plate pre-order flow.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Copy that hostedButtonId and save it as the License Plate Hosted Button ID below.', 'pc4s' ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Enter your credentials and save', 'pc4s' ); ?></strong>
										<p><?php esc_html_e( 'Paste your PayPal email, Client ID, Donate Hosted Button ID, and License Plate Hosted Button ID into the fields below. Set the currency code and toggle Sandbox mode off when going live.', 'pc4s' ); ?></p>
									</li>
								</ol>
							</div><!-- .pc4s-ga-setup-guide__body -->
						</details><!-- .pc4s-ga-setup-guide -->

						<!-- Sandbox toggle -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Mode', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-toggle-label" for="pc4s_paypal_sandbox">
									<input
										type="checkbox"
										id="pc4s_paypal_sandbox"
										name="<?php echo $field( 'paypal_sandbox' ); // phpcs:ignore ?>"
										value="1"
										class="pc4s-toggle-input"
										<?php echo $checked( 'paypal_sandbox' ); // phpcs:ignore ?>
									/>
									<span class="pc4s-toggle-track" aria-hidden="true"></span>
									<span class="pc4s-toggle-text"><?php esc_html_e( 'Enable Sandbox (test) mode', 'pc4s' ); ?></span>
								</label>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'Use PayPal\'s sandbox environment for testing. Disable this on a live site.', 'pc4s' ); ?>
								</p>
							</div>

						</section>

						<!-- Credentials section -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Credentials', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_paypal_email">
									<?php esc_html_e( 'PayPal Email', 'pc4s' ); ?>
								</label>
								<input
									type="email"
									id="pc4s_paypal_email"
									name="<?php echo $field( 'paypal_email' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_email' ); // phpcs:ignore ?>"
									class="pc4s-field-input"
									placeholder="payments@example.org"
									autocomplete="off"
									aria-describedby="pc4s_paypal_email_hint"
								/>
								<p id="pc4s_paypal_email_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'The email address associated with your PayPal business account (used for standard payment buttons).', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_paypal_client_id">
									<?php esc_html_e( 'Client ID', 'pc4s' ); ?>
								</label>
								<?php if ( $locked( 'paypal_client_id' ) ) : ?>
								<p class="pc4s-field-hint" style="font-style:italic">
									<?php printf( esc_html__( 'Set via %s constant in wp-config.php — edit that file to change it.', 'pc4s' ), '<code>PC4S_PAYPAL_CLIENT_ID</code>' ); ?>
								</p>
								<?php else : ?>
								<input
									type="password"
									id="pc4s_paypal_client_id"
									name="<?php echo $field( 'paypal_client_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_client_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									placeholder="AaBbCcDd..."
									autocomplete="new-password"
									spellcheck="false"
									aria-describedby="pc4s_paypal_client_id_hint"
								/>
								<?php endif; ?>
							<p id="pc4s_paypal_client_id_hint" class="pc4s-field-hint">
								<?php esc_html_e( 'Your PayPal REST API Client ID. Optional for the current hosted-button redirect flow, but required if you later switch to PayPal SDK checkout buttons.', 'pc4s' ); ?>
							</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_paypal_donate_button_id">
									<?php esc_html_e( 'Donate Hosted Button ID', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_paypal_donate_button_id"
									name="<?php echo $field( 'paypal_donate_button_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_donate_button_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_paypal_donate_hbid_hint"
								/>
								<p id="pc4s_paypal_donate_hbid_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Required for the Donate form. Use the hosted button ID created for donations in your PayPal account.', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_paypal_license_plate_button_id">
									<?php esc_html_e( 'License Plate Hosted Button ID', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_paypal_license_plate_button_id"
									name="<?php echo $field( 'paypal_license_plate_button_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_license_plate_button_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_paypal_license_plate_hbid_hint"
								/>
								<p id="pc4s_paypal_license_plate_hbid_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Required for the License Plate form. Use the hosted button ID created for license plate payments in your PayPal account.', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_paypal_currency">
									<?php esc_html_e( 'Currency Code', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_paypal_currency"
									name="<?php echo $field( 'paypal_currency' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_currency', 'USD' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--narrow pc4s-field-input--code"
									maxlength="3"
									placeholder="USD"
									spellcheck="false"
									aria-describedby="pc4s_paypal_currency_hint"
								/>
								<p id="pc4s_paypal_currency_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'ISO 4217 three-letter currency code (e.g. USD, EUR, CAD). Saved as uppercase.', 'pc4s' ); ?>
								</p>
							</div>

						</section>

						<!-- SDK notice -->
						<div class="pc4s-info-box pc4s-info-box--notice" role="note">
							<svg class="pc4s-info-box__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/>
							</svg>
							<p class="pc4s-info-box__note"><?php esc_html_e( 'Note: The PayPal SDK script should already be added to your website\'s header.', 'pc4s' ); ?></p>
						</div>

					</div><!-- .pc4s-form-card__body -->

				</article><!-- PayPal card -->

				<!-- ── Card: Facebook ───────────────────────────────────── -->
				<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'Facebook Settings', 'pc4s' ); ?>">

					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php esc_html_e( 'Facebook', 'pc4s' ); ?></h2>
							<code class="pc4s-form-badge">Communication</code>
						</div>
					</header>

					<div class="pc4s-form-card__body">

						<!-- ── Step-by-step setup guide (collapsible) ──────── -->
						<details class="pc4s-ga-setup-guide">
							<summary class="pc4s-ga-setup-guide__summary">
								<svg class="pc4s-ga-setup-guide__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/></svg>
								<?php esc_html_e( 'How to connect Facebook (setup guide)', 'pc4s' ); ?>
							</summary>
							<div class="pc4s-ga-setup-guide__body">
								<ol class="pc4s-ga-setup-steps">
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Find your Facebook Page ID', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php esc_html_e( 'Go to your Facebook Page and click "About".', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Scroll to the bottom — the Page ID is listed there.', 'pc4s' ); ?></li>
											<li><?php echo wp_kses( __( 'Alternatively, open <a href="https://business.facebook.com/" target="_blank" rel="noopener noreferrer">Meta Business Suite</a>; the Page ID appears in the URL as <strong>profile.php?id=XXXXXXX</strong>.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ], 'strong' => [] ] ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Create a Facebook App for API access', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php echo wp_kses( __( 'Go to <a href="https://developers.facebook.com/" target="_blank" rel="noopener noreferrer">developers.facebook.com</a> and create a developer account if needed.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></li>
											<li><?php esc_html_e( 'Click "My Apps" → "Create App" and choose the "Business" type.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Complete the setup wizard and note your App ID.', 'pc4s' ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Generate a Page Access Token', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php echo wp_kses( __( 'Open <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener noreferrer">Meta Graph API Explorer</a>.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></li>
											<li><?php esc_html_e( 'Select your app and click "Generate Access Token".', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Grant the "pages_show_list" and "pages_read_engagement" permissions, then copy the token.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'For long-term use, exchange it for a long-lived Page Access Token — short-lived tokens expire in ~1 hour.', 'pc4s' ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Enter your details and save', 'pc4s' ); ?></strong>
										<p><?php esc_html_e( 'Paste your Facebook Page URL, Page ID, and Access Token into the fields below, then save settings.', 'pc4s' ); ?></p>
									</li>
								</ol>
							</div><!-- .pc4s-ga-setup-guide__body -->
						</details><!-- .pc4s-ga-setup-guide -->

						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Page Details', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_facebook_page_url">
									<?php esc_html_e( 'Facebook Page URL', 'pc4s' ); ?>
								</label>
								<input
									type="url"
									id="pc4s_facebook_page_url"
									name="<?php echo $field( 'facebook_page_url' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'facebook_page_url' ); // phpcs:ignore ?>"
									class="pc4s-field-input"
									placeholder="https://www.facebook.com/your-page"
									autocomplete="off"
									aria-describedby="pc4s_fb_url_hint"
								/>
								<p id="pc4s_fb_url_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Full URL of your Facebook page — used for the embedded feed and social links on the Communication page.', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_facebook_page_id">
									<?php esc_html_e( 'Facebook Page ID', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_facebook_page_id"
									name="<?php echo $field( 'facebook_page_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'facebook_page_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									placeholder="123456789012345"
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_fb_id_hint"
								/>
								<p id="pc4s_fb_id_hint" class="pc4s-field-hint">
								<?php esc_html_e( 'Numeric ID of your Facebook Page. Used internally — not displayed to visitors. See the setup guide above for how to find it.', 'pc4s' ); ?>
							</p>
							</div>

						</section>

						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'API Access Token', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_facebook_access_token">
									<?php esc_html_e( 'Access Token', 'pc4s' ); ?>
								</label>
								<?php if ( $locked( 'facebook_access_token' ) ) : ?>
								<p class="pc4s-field-hint" style="font-style:italic">
									<?php printf( esc_html__( 'Set via %s constant in wp-config.php — edit that file to change it.', 'pc4s' ), '<code>PC4S_FACEBOOK_ACCESS_TOKEN</code>' ); ?>
								</p>
								<?php else : ?>
								<input
									type="text"
									id="pc4s_facebook_access_token"
									name="<?php echo $field( 'facebook_access_token' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'facebook_access_token' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									placeholder="EAAB..."
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_fb_token_hint"
								/>
								<?php endif; ?>
								<p id="pc4s_fb_token_hint" class="pc4s-field-hint">
								<?php esc_html_e( 'A long-lived Page Access Token from Meta Graph API Explorer. Short-lived tokens expire in ~1 hour. See the setup guide above for step-by-step instructions.', 'pc4s' ); ?>
							</p>
							</div>

						</section>

						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Feed Options', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_facebook_feed_limit">
									<?php esc_html_e( 'Feed Limit', 'pc4s' ); ?>
								</label>
								<input
									type="number"
									id="pc4s_facebook_feed_limit"
									name="<?php echo $field( 'facebook_feed_limit' ); // phpcs:ignore ?>"
									value="<?php echo esc_attr( (string) ( $opts['facebook_feed_limit'] ?? 10 ) ); ?>"
									class="pc4s-field-input pc4s-field-input--narrow"
									min="1"
									max="50"
									step="1"
									aria-describedby="pc4s_fb_limit_hint"
								/>
								<p id="pc4s_fb_limit_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Number of posts to show in the embedded feed (1–50). Default: 10.', 'pc4s' ); ?>
								</p>
							</div>

						</section>

						<!-- SDK note -->
						<div class="pc4s-info-box pc4s-info-box--notice" role="note">
							<svg class="pc4s-info-box__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/>
							</svg>
							<p class="pc4s-info-box__note"><?php esc_html_e( 'The Facebook Page Plugin (embedded timeline) is loaded automatically on the Communication page via the Facebook JavaScript SDK. No additional plugin installation is required.', 'pc4s' ); ?></p>
						</div>

					</div><!-- .pc4s-form-card__body -->

				</article><!-- Facebook card -->

				<!-- ── Card: Google Analytics ────────────────────────────── -->
				<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'Google Analytics', 'pc4s' ); ?>">

					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php esc_html_e( 'Google Analytics', 'pc4s' ); ?></h2>
							<code class="pc4s-form-badge">GA4</code>
						</div>
						<?php
						$ga    = \PC4S\Classes\Admin\GoogleAnalytics::get_instance();
						$ga_status = $ga->get_connection_status();
						?>
						<div class="pc4s-ga-status-badge pc4s-ga-status-badge--<?php echo esc_attr( $ga_status ?: 'disconnected' ); ?>" role="status">
							<span class="pc4s-ga-status-badge__dot" aria-hidden="true"></span>
							<?php
							if ( 'connected' === $ga_status ) {
								esc_html_e( 'Connected', 'pc4s' );
							} elseif ( 'error' === $ga_status ) {
								esc_html_e( 'Connection Error', 'pc4s' );
							} else {
								esc_html_e( 'Not Connected', 'pc4s' );
							}
							?>
						</div>
					</header>

					<div class="pc4s-form-card__body">

						<!-- ── Step-by-step setup guide (collapsible) ──────── -->
						<details class="pc4s-ga-setup-guide">
							<summary class="pc4s-ga-setup-guide__summary">
								<svg class="pc4s-ga-setup-guide__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/></svg>
								<?php esc_html_e( 'How to connect Google Analytics (setup guide)', 'pc4s' ); ?>
							</summary>
							<div class="pc4s-ga-setup-guide__body">

								<ol class="pc4s-ga-setup-steps">
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Create or access your Google Analytics account', 'pc4s' ); ?></strong>
										<p><?php echo wp_kses( __( 'Go to <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">Google Analytics</a>. Sign in and create an account for your website if you don\'t have one.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></p>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Create a GA4 property (if needed)', 'pc4s' ); ?></strong>
										<p><?php esc_html_e( 'In Google Analytics → Admin → Property → Create Property. Select "Web" as the platform. Complete the setup wizard.', 'pc4s' ); ?></p>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Find your Measurement ID &amp; Property ID', 'pc4s' ); ?></strong>
										<p><?php esc_html_e( 'Measurement ID (e.g. G-XXXXXXX): Admin → Data Streams → your stream → Measurement ID.', 'pc4s' ); ?></p>
										<p><?php esc_html_e( 'Property ID (numeric): Admin → Property Settings → Property ID (top right).', 'pc4s' ); ?></p>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Create a Google Cloud service account', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php echo wp_kses( __( 'Open the <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>.', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></li>
											<li><?php esc_html_e( 'Create or select a project.', 'pc4s' ); ?></li>
											<li><?php echo wp_kses( __( 'Go to <strong>APIs &amp; Services → Enabled APIs</strong> and enable the <strong>Google Analytics Data API</strong>.', 'pc4s' ), [ 'strong' => [] ] ); ?></li>
											<li><?php echo wp_kses( __( 'Go to <strong>IAM &amp; Admin → Service Accounts → Create Service Account</strong>.', 'pc4s' ), [ 'strong' => [] ] ); ?></li>
											<li><?php esc_html_e( 'Give it any name, then click "Create and Continue". Skip optional role/user steps.', 'pc4s' ); ?></li>
											<li><?php echo wp_kses( __( 'Open the service account → <strong>Keys</strong> tab → <strong>Add Key → Create new key → JSON</strong>. Download the JSON file.', 'pc4s' ), [ 'strong' => [] ] ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Grant the service account access to your GA4 property', 'pc4s' ); ?></strong>
										<ol class="pc4s-ga-setup-step__sub">
											<li><?php esc_html_e( 'In Google Analytics → Admin → Property Access Management.', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Click "+" and add the service account email (from the JSON, the "client_email" field).', 'pc4s' ); ?></li>
											<li><?php esc_html_e( 'Assign the "Viewer" role, then save.', 'pc4s' ); ?></li>
										</ol>
									</li>
									<li class="pc4s-ga-setup-step">
										<strong class="pc4s-ga-setup-step__title"><?php esc_html_e( 'Paste the JSON key and connect below', 'pc4s' ); ?></strong>
										<p><?php esc_html_e( 'Open the downloaded JSON file in a text editor, select all the contents, and paste it into the "Service Account JSON" field below.', 'pc4s' ); ?></p>
									</li>
								</ol>

							</div><!-- .pc4s-ga-setup-guide__body -->
						</details><!-- .pc4s-ga-setup-guide -->

						<!-- ── Tracking (in the main Settings-API form) ───── -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Tracking', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-toggle-label" for="pc4s_ga_enabled">
									<input
										type="checkbox"
										id="pc4s_ga_enabled"
										name="<?php echo $field( 'ga_enabled' ); // phpcs:ignore ?>"
										value="1"
										class="pc4s-toggle-input"
										<?php echo $checked( 'ga_enabled' ); // phpcs:ignore ?>
									/>
									<span class="pc4s-toggle-track" aria-hidden="true"></span>
									<span class="pc4s-toggle-text"><?php esc_html_e( 'Enable Google Analytics tracking', 'pc4s' ); ?></span>
								</label>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'Loads the gtag.js snippet on every front-end page. Disable during development.', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_ga_measurement_id">
									<?php esc_html_e( 'Measurement ID', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_ga_measurement_id"
									name="<?php echo $field( 'ga_measurement_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'ga_measurement_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									placeholder="G-XXXXXXXXXX"
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_ga_mid_hint"
								/>
								<p id="pc4s_ga_mid_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Your GA4 Measurement ID — found in Google Analytics → Admin → Data Streams → Web Stream details.', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_ga_property_id">
									<?php esc_html_e( 'Property ID', 'pc4s' ); ?>
									<span class="pc4s-field-required" aria-label="<?php esc_attr_e( 'required for dashboard', 'pc4s' ); ?>">*</span>
								</label>
								<input
									type="text"
									id="pc4s_ga_property_id"
									name="<?php echo $field( 'ga_property_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'ga_property_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code pc4s-field-input--narrow"
									placeholder="123456789"
									autocomplete="off"
									spellcheck="false"
									pattern="[0-9]+"
									aria-describedby="pc4s_ga_pid_hint"
								/>
								<p id="pc4s_ga_pid_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Numeric GA4 Property ID — found in Google Analytics → Admin → Property Settings. Required for the analytics dashboard.', 'pc4s' ); ?>
								</p>
							</div>

						</section>

					</div><!-- .pc4s-form-card__body -->

					<footer class="pc4s-form-card__footer">
						<button type="submit" class="pc4s-btn pc4s-btn--primary">
							<?php esc_html_e( 'Save Settings', 'pc4s' ); ?>
						</button>
					</footer>

				</article><!-- Google Analytics tracking card -->

			</form>

			<!-- ── Card: Google Analytics Credentials (separate form) ─── -->
			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$ga_msg_key = sanitize_key( $_GET['ga_status'] ?? '' );
			// phpcs:enable
			$ga_messages = [
				'saved'          => [ 'type' => 'success', 'text' => __( 'Service-account credentials saved successfully.', 'pc4s' ) ],
				'disconnected'   => [ 'type' => 'success', 'text' => __( 'Google Analytics has been disconnected.', 'pc4s' ) ],
				'empty'          => [ 'type' => 'error',   'text' => __( 'No JSON was provided. Please paste your service-account key.', 'pc4s' ) ],
				'invalid_json'   => [ 'type' => 'error',   'text' => __( 'Invalid service-account JSON. Ensure you pasted the entire file contents and that the "type" is "service_account".', 'pc4s' ) ],
				'encrypt_failed' => [ 'type' => 'error',   'text' => __( 'Could not encrypt credentials (PHP OpenSSL may be unavailable). Contact your host.', 'pc4s' ) ],
			];
			?>

			<?php if ( isset( $ga_messages[ $ga_msg_key ] ) ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--<?php echo esc_attr( $ga_messages[ $ga_msg_key ]['type'] ); ?>" role="status" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<?php if ( 'success' === $ga_messages[ $ga_msg_key ]['type'] ) : ?>
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
					<?php else : ?>
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
					<?php endif; ?>
				</svg>
				<span><?php echo esc_html( $ga_messages[ $ga_msg_key ]['text'] ); ?></span>
			</div>
			<?php endif; ?>

			<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'Google Analytics API Credentials', 'pc4s' ); ?>">

				<header class="pc4s-form-card__header">
					<div class="pc4s-form-card__title-row">
						<h2 class="pc4s-form-card__title"><?php esc_html_e( 'GA4 API Credentials', 'pc4s' ); ?></h2>
						<code class="pc4s-form-badge">Service Account</code>
					</div>
				</header>

				<div class="pc4s-form-card__body">

					<div class="pc4s-info-box pc4s-info-box--notice" role="note">
						<svg class="pc4s-info-box__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/>
						</svg>
						<p class="pc4s-info-box__note"><?php esc_html_e( 'Credentials are encrypted with AES-256 before being stored. The private key is never exposed in page source or API responses.', 'pc4s' ); ?></p>
					</div>

					<?php if ( $ga->has_credentials() ) : ?>
					<div class="pc4s-ga-creds-stored">
						<svg class="pc4s-ga-creds-stored__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1zm3 8V5.5a3 3 0 1 0-6 0V9h6z" clip-rule="evenodd"/></svg>
						<span><?php esc_html_e( 'Service-account credentials are stored securely.', 'pc4s' ); ?></span>
					</div>
					<?php endif; ?>

					<!-- Paste new credentials -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'pc4s_save_ga_creds' ); ?>
						<input type="hidden" name="action" value="pc4s_save_ga_creds" />

						<div class="pc4s-field-group">
							<label class="pc4s-field-label" for="pc4s_ga_sa_json">
								<?php echo $ga->has_credentials() ? esc_html__( 'Paste new Service Account JSON (to update)', 'pc4s' ) : esc_html__( 'Service Account JSON', 'pc4s' ); ?>
							</label>
							<textarea
								id="pc4s_ga_sa_json"
								name="pc4s_ga_service_account_json"
								class="pc4s-field-textarea pc4s-field-input--code"
								rows="8"
								placeholder='<?php echo $ga->has_credentials() ? esc_attr__( 'Credentials stored — paste a new JSON to replace them.', 'pc4s' ) : esc_attr__( 'Paste the full contents of your service-account JSON key file here…', 'pc4s' ); ?>'
								autocomplete="off"
								spellcheck="false"
								aria-describedby="pc4s_ga_sa_hint"
							></textarea>
							<p id="pc4s_ga_sa_hint" class="pc4s-field-hint">
								<?php esc_html_e( 'The JSON file downloaded from Google Cloud → Service Accounts → Keys. Must include "type": "service_account".', 'pc4s' ); ?>
							</p>
						</div>

						<div class="pc4s-ga-creds-actions">
							<button type="submit" class="pc4s-btn pc4s-btn--primary">
								<svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" style="width:1em;height:1em"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143z" clip-rule="evenodd"/></svg>
								<?php echo $ga->has_credentials() ? esc_html__( 'Update Credentials', 'pc4s' ) : esc_html__( 'Save Credentials', 'pc4s' ); ?>
							</button>

							<button
								type="button"
								class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm js-ga-test-btn"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'pc4s-admin-nonce' ) ); ?>"
								<?php echo $ga->has_credentials() ? '' : 'disabled'; ?>
							>
								<svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" style="width:1em;height:1em"><path fill-rule="evenodd" d="M2 10a8 8 0 1 1 16 0A8 8 0 0 1 2 10zm6.39-2.908a.75.75 0 0 1 .766.027l3.5 2.25a.75.75 0 0 1 0 1.262l-3.5 2.25A.75.75 0 0 1 8 12.25v-4.5a.75.75 0 0 1 .39-.658z" clip-rule="evenodd"/></svg>
								<?php esc_html_e( 'Test Connection', 'pc4s' ); ?>
							</button>
							<span class="pc4s-ga-test-result" role="status" aria-live="polite"></span>
						</div>

					</form>

					<?php if ( $ga->has_credentials() ) : ?>
					<!-- Disconnect form -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pc4s-ga-disconnect-form">
						<?php wp_nonce_field( 'pc4s_clear_ga_creds' ); ?>
						<input type="hidden" name="action" value="pc4s_clear_ga_creds" />
						<button type="submit" class="pc4s-btn pc4s-btn--danger pc4s-btn--sm" onclick="return confirm('<?php esc_attr_e( 'Remove Google Analytics credentials? This cannot be undone.', 'pc4s' ); ?>')">
							<svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" style="width:1em;height:1em"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 3.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Disconnect Google Analytics', 'pc4s' ); ?>
						</button>
					</form>
					<?php endif; ?>

				</div><!-- .pc4s-form-card__body -->

			</article><!-- GA credentials card -->

			</form>

		</div><!-- .pc4s-settings-page -->
		<?php
	}

	// ─── Constant map ────────────────────────────────────────────────────────

	/**
	 * Map from settings key → wp-config.php constant name.
	 *
	 * When a constant is defined in wp-config.php (or any file loaded before
	 * the theme) it is used as the authoritative value and the DB field is
	 * ignored. This keeps sensitive credentials out of the database entirely.
	 *
	 * To use:  define( 'PC4S_FACEBOOK_ACCESS_TOKEN', 'EAABxxx...' );
	 */
	private const CONSTANT_MAP = [
		'facebook_access_token'   => 'PC4S_FACEBOOK_ACCESS_TOKEN',
		'paypal_client_id'        => 'PC4S_PAYPAL_CLIENT_ID',
		'paypal_donate_button_id' => 'PC4S_PAYPAL_DONATE_BUTTON_ID',
		'paypal_license_plate_button_id' => 'PC4S_PAYPAL_LICENSE_PLATE_BUTTON_ID',
	];

	/**
	 * Resolve the hosted button ID for a specific form.
	 *
	 * @param string $form_id Form identifier.
	 * @return string
	 */
	public static function get_paypal_button_id( string $form_id ): string {
		if ( isset( self::PAYPAL_BUTTON_KEY_MAP[ $form_id ] ) ) {
			return self::get( self::PAYPAL_BUTTON_KEY_MAP[ $form_id ] );
		}

		return '';
	}

	// ─── Template helper ─────────────────────────────────────────────────────

	/**
	 * Retrieve a single setting value.
	 *
	 * Resolution order:
	 *   1. wp-config.php constant (see CONSTANT_MAP) — never touches the DB.
	 *   2. Value stored in the wp_options DB row.
	 *   3. $default.
	 *
	 * Cached in a static variable; the DB is read at most once per request.
	 *
	 * @param string $key     Setting key (e.g. 'ga_measurement_id').
	 * @param string $default Fallback when the key is empty or unset.
	 * @return string
	 */
	public static function get( string $key, string $default = '' ): string {
		// 1. Constant override — highest priority, never stored in DB.
		if ( isset( self::CONSTANT_MAP[ $key ] ) ) {
			$const = self::CONSTANT_MAP[ $key ];
			if ( defined( $const ) ) {
				return (string) constant( $const );
			}
		}

		// 2. DB value.
		static $opts = null;
		if ( null === $opts ) {
			$opts = (array) get_option( self::OPTION_KEY, [] );
		}
		$value = (string) ( $opts[ $key ] ?? '' );
		return '' !== $value ? $value : $default;
	}

	/**
	 * Check whether a boolean setting is enabled.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_enabled( string $key ): bool {
		return '1' === self::get( $key );
	}

	/**
	 * Return whether a given key's value is locked via a wp-config.php constant.
	 * Used by the admin UI to show a "Set via constant" notice instead of an
	 * editable input, so the live token is never echoed into the page source.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_constant_override( string $key ): bool {
		if ( ! isset( self::CONSTANT_MAP[ $key ] ) ) {
			return false;
		}
		return defined( self::CONSTANT_MAP[ $key ] );
	}

	/**
	 * Build PayPal validation notices shown on the settings page.
	 *
	 * @return array<int,array{type:string,text:string}>
	 */
	private function get_paypal_notices(): array {
		$notices = [];

		if ( '' === self::get( 'paypal_client_id' ) ) {
			$notices[] = [
				'type' => 'warning',
				'text' => __( 'PayPal Client ID is currently blank. That does not block the current hosted-button redirect flow, but it will be required if you later switch to PayPal SDK checkout buttons.', 'pc4s' ),
			];
		}

		$paypal_sandbox = self::is_enabled( 'paypal_sandbox' );
		$environment    = $paypal_sandbox ? __( 'sandbox', 'pc4s' ) : __( 'live', 'pc4s' );
		$contexts       = [
			'donate'        => __( 'Donate', 'pc4s' ),
			'license_plate' => __( 'License Plate', 'pc4s' ),
		];

		foreach ( $contexts as $form_id => $label ) {
			$button_id = self::get_paypal_button_id( $form_id );

			if ( '' === $button_id ) {
				$notices[] = [
					'type' => 'warning',
					'text' => sprintf( __( '%s does not have a PayPal hosted button ID configured yet.', 'pc4s' ), $label ),
				];
				continue;
			}

			$validation = $this->validate_paypal_button_id( $button_id, $paypal_sandbox );

			if ( 'invalid' === $validation['status'] ) {
				$notices[] = [
					'type' => 'error',
					'text' => sprintf( __( '%1$s PayPal button validation failed in %2$s mode: %3$s', 'pc4s' ), $label, $environment, $validation['message'] ),
				];
				continue;
			}

			if ( 'unreachable' === $validation['status'] ) {
				$notices[] = [
					'type' => 'warning',
					'text' => sprintf( __( '%1$s PayPal button could not be validated in %2$s mode right now: %3$s', 'pc4s' ), $label, $environment, $validation['message'] ),
				];
				continue;
			}
		}

		return $notices;
	}

	/**
	 * Validate a PayPal hosted button ID against the active environment.
	 *
	 * @param string $button_id Hosted button ID.
	 * @param bool   $sandbox   Whether sandbox mode is enabled.
	 * @return array{status:string,message:string}
	 */
	private function validate_paypal_button_id( string $button_id, bool $sandbox ): array {
		$button_id = strtoupper( trim( $button_id ) );

		if ( ! preg_match( '/^[A-Z0-9]{8,32}$/', $button_id ) ) {
			return [
				'status'  => 'invalid',
				'message' => __( 'The hosted button ID format looks invalid.', 'pc4s' ),
			];
		}

		$transient_key = 'pc4s_paypal_btn_' . md5( ( $sandbox ? 'sandbox:' : 'live:' ) . $button_id );
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) && isset( $cached['status'], $cached['message'] ) ) {
			return $cached;
		}

		$paypal_base = $sandbox
			? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
			: 'https://www.paypal.com/cgi-bin/webscr';

		$response = wp_remote_get(
			add_query_arg(
				[
					'cmd'              => '_s-xclick',
					'hosted_button_id' => $button_id,
				],
				$paypal_base
			),
			[
				'timeout'     => 10,
				'redirection' => 5,
				'user-agent'  => 'PC4S PayPal Validator; ' . home_url( '/' ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$result = [
				'status'  => 'unreachable',
				'message' => $response->get_error_message(),
			];
			set_transient( $transient_key, $result, 10 * MINUTE_IN_SECONDS );
			return $result;
		}

		$body = strtolower( wp_strip_all_tags( (string) wp_remote_retrieve_body( $response ) ) );
		$error_markers = [
			"things don't appear to be working at the moment",
			"something doesn't look right",
			"can't be completed using paypal",
			'choose another way to pay',
		];

		foreach ( $error_markers as $marker ) {
			if ( false !== strpos( $body, strtolower( $marker ) ) ) {
				$result = [
					'status'  => 'invalid',
					'message' => __( 'PayPal returned an error page for this button ID.', 'pc4s' ),
				];
				set_transient( $transient_key, $result, 10 * MINUTE_IN_SECONDS );
				return $result;
			}
		}

		$result = [
			'status'  => 'valid',
			'message' => __( 'The hosted button responded successfully.', 'pc4s' ),
		];
		set_transient( $transient_key, $result, 10 * MINUTE_IN_SECONDS );

		return $result;
	}
}
