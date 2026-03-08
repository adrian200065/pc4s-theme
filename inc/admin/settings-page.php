<?php
/**
 * PC4S Settings Page
 *
 * Central settings panel for site-wide integrations. Currently includes:
 *   - PayPal (email, Client ID, Hosted Button ID, sandbox mode, currency)
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
	const CAPABILITY  = 'manage_options';

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
	 * Sanitize all settings fields before they are stored.
	 *
	 * @param mixed $input Raw $_POST data submitted by the settings form.
	 * @return array<string, mixed> Sanitized option array.
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		return [
			// ── PayPal ─────────────────────────────────────────────────────
			'paypal_email'            => sanitize_email( $input['paypal_email']            ?? '' ),
			'paypal_client_id'        => sanitize_text_field( $input['paypal_client_id']   ?? '' ),
			'paypal_hosted_button_id' => sanitize_text_field( $input['paypal_hosted_button_id'] ?? '' ),
			'paypal_sandbox'          => ! empty( $input['paypal_sandbox'] ) ? '1' : '0',
			'paypal_currency'         => strtoupper( sanitize_text_field( $input['paypal_currency'] ?? 'USD' ) ),

			// ── Facebook ────────────────────────────────────────────────────
			'facebook_page_url'       => sanitize_url( $input['facebook_page_url']        ?? '' ),
			'facebook_page_id'        => sanitize_text_field( $input['facebook_page_id']  ?? '' ),
			'facebook_access_token'   => sanitize_text_field( $input['facebook_access_token'] ?? '' ),
			'facebook_feed_limit'     => min( 50, max( 1, absint( $input['facebook_feed_limit'] ?? 10 ) ) ),

			// ── Google Analytics ────────────────────────────────────────────
			'ga_enabled'              => ! empty( $input['ga_enabled'] ) ? '1' : '0',
			'ga_measurement_id'       => sanitize_text_field( $input['ga_measurement_id']  ?? '' ),
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
									type="text"
									id="pc4s_paypal_client_id"
									name="<?php echo $field( 'paypal_client_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_client_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									placeholder="AaBbCcDd..."
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_paypal_client_id_hint"
								/>
								<?php endif; ?>
								<div class="pc4s-info-box" id="pc4s_paypal_client_id_hint">
									<p class="pc4s-info-box__intro"><?php esc_html_e( 'To get your Client ID:', 'pc4s' ); ?></p>
									<ol class="pc4s-info-box__steps">
										<li><?php echo wp_kses( __( 'Log in to <a href="https://developer.paypal.com" target="_blank" rel="noopener noreferrer">PayPal Developer Dashboard</a>', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></li>
										<li><?php esc_html_e( 'Go to My Apps &amp; Credentials', 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Under REST API apps, find your app or create one', 'pc4s' ); ?></li>
										<li><?php esc_html_e( "Copy the Client ID (starts with 'A' or 'BAA')", 'pc4s' ); ?></li>
									</ol>
									<p class="pc4s-info-box__note"><?php esc_html_e( 'Use Sandbox Client ID for testing, Live Client ID for production.', 'pc4s' ); ?></p>
								</div>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_paypal_hosted_button_id">
									<?php esc_html_e( 'Hosted Button ID', 'pc4s' ); ?>
								</label>
								<?php if ( $locked( 'paypal_hosted_button_id' ) ) : ?>
								<p class="pc4s-field-hint" style="font-style:italic">
									<?php printf( esc_html__( 'Set via %s constant in wp-config.php — edit that file to change it.', 'pc4s' ), '<code>PC4S_PAYPAL_HOSTED_BUTTON_ID</code>' ); ?>
								</p>
								<?php else : ?>
								<input
									type="text"
									id="pc4s_paypal_hosted_button_id"
									name="<?php echo $field( 'paypal_hosted_button_id' ); // phpcs:ignore ?>"
									value="<?php echo $val( 'paypal_hosted_button_id' ); // phpcs:ignore ?>"
									class="pc4s-field-input pc4s-field-input--code"
									autocomplete="off"
									spellcheck="false"
									aria-describedby="pc4s_paypal_hbid_hint"
								/>
								<?php endif; ?>
								<div class="pc4s-info-box" id="pc4s_paypal_hbid_hint">
									<p class="pc4s-info-box__intro"><?php esc_html_e( 'ID of a hosted button created in your PayPal account. Leave blank if using the JS SDK only.', 'pc4s' ); ?></p>
									<p class="pc4s-info-box__note"><?php echo wp_kses( __( 'Example: if your PayPal code contains <code>hostedButtonId: "3TMTGXDWYCRK6"</code>, enter <code>3TMTGXDWYCRK6</code>.', 'pc4s' ), [ 'code' => [] ] ); ?></p>
								</div>
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
								<div class="pc4s-info-box" id="pc4s_fb_id_hint">
									<p class="pc4s-info-box__intro"><?php esc_html_e( 'To find your Facebook Page ID:', 'pc4s' ); ?></p>
									<ol class="pc4s-info-box__steps">
										<li><?php esc_html_e( 'Go to your Facebook Page and click "About"', 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Scroll to the bottom — Page ID is listed there', 'pc4s' ); ?></li>
										<li><?php echo wp_kses( __( 'Alternatively, log in to <a href="https://business.facebook.com/" target="_blank" rel="noopener noreferrer">Meta Business Suite</a>, open your page, and the Page ID appears in the URL: facebook.com/profile.php?id=<strong>XXXXXXX</strong>', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ], 'strong' => [] ] ); ?></li>
									</ol>
									<p class="pc4s-info-box__note"><?php esc_html_e( 'Used internally — not displayed to visitors.', 'pc4s' ); ?></p>
								</div>
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
								<div class="pc4s-info-box" id="pc4s_fb_token_hint">
									<p class="pc4s-info-box__intro"><?php esc_html_e( 'To get your Facebook Access Token:', 'pc4s' ); ?></p>
									<ol class="pc4s-info-box__steps">
										<li><?php echo wp_kses( __( 'Go to <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener noreferrer">Meta Graph API Explorer</a>', 'pc4s' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></li>
										<li><?php esc_html_e( 'Select your app and click "Generate Access Token"', 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Grant "pages_show_list" and "pages_read_engagement" permissions', 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Click "Generate Token" and copy the result', 'pc4s' ); ?></li>
									</ol>
									<p class="pc4s-info-box__note"><?php esc_html_e( 'For long-term use, generate a long-lived Page Access Token. Short-lived tokens expire in ~1 hour.', 'pc4s' ); ?></p>
								</div>
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
					</header>

					<div class="pc4s-form-card__body">
						<section class="pc4s-settings-section">

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

						</section>
					</div><!-- .pc4s-form-card__body -->

					<footer class="pc4s-form-card__footer">
						<button type="submit" class="pc4s-btn pc4s-btn--primary">
							<?php esc_html_e( 'Save Settings', 'pc4s' ); ?>
						</button>
					</footer>

				</article><!-- Google Analytics card -->

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
		'paypal_hosted_button_id' => 'PC4S_PAYPAL_HOSTED_BUTTON_ID',
	];

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
}
