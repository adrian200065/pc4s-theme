<?php
/**
 * PC4S SMTP Settings Page
 *
 * Provides a settings panel for configuring outgoing SMTP email. Covers:
 *   - Mailer Settings: host, encryption, port, auth, username, password
 *   - General Settings: from address, from name
 *   - Send a Test: send a test email to verify configuration
 *
 * Settings are stored under the `pc4s_smtp_settings` option key.
 * Sensitive credentials (username, password) are stored in the DB unless
 * overridden via wp-config.php constants (PC4S_SMTP_USER, PC4S_SMTP_PASS).
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

class SmtpPage {

	// ─── Constants ───────────────────────────────────────────────────────────

	/** WordPress option key. */
	const OPTION_KEY   = 'pc4s_smtp_settings';

	/** Settings API group. */
	const OPTION_GROUP = 'pc4s_smtp_settings_group';

	/** Nonce action for test-email form. */
	const TEST_NONCE   = 'pc4s_smtp_test_email';

	/** Capability required to view / save. */
const CAPABILITY   = 'pc4s_manage';

	// ─── Singleton ───────────────────────────────────────────────────────────

	/** @var SmtpPage|null */
	private static ?SmtpPage $instance = null;

	public static function get_instance(): SmtpPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'maybe_send_test_email' ] );
		add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );
	}

	// ─── PHPMailer configuration ─────────────────────────────────────────────

	/**
	 * Configure PHPMailer with the stored SMTP settings before every wp_mail()
	 * call. This is what actually makes outgoing mail use SMTP.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance passed by reference.
	 */
	public function configure_phpmailer( $phpmailer ): void {
		$opts = (array) get_option( self::OPTION_KEY, [] );

		$host     = sanitize_text_field( $opts['smtp_host']     ?? '' );
		$username = sanitize_text_field( $opts['smtp_username'] ?? '' );
		$password = sanitize_text_field( $opts['smtp_password'] ?? '' );
		$port     = absint( $opts['smtp_port']                  ?? 465 );
		$enc      = sanitize_key( $opts['encryption']           ?? 'tls' );
		$auth     = ( '1' === ( $opts['smtp_auth']              ?? '1' ) );
		$from     = sanitize_email( $opts['from_email']         ?? '' );
		$name     = sanitize_text_field( $opts['from_name']     ?? get_bloginfo( 'name' ) );

		// Do nothing if no host is configured yet.
		if ( empty( $host ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = $port;
		$phpmailer->SMTPAuth   = $auth;

		if ( $auth ) {
			$phpmailer->Username = $username;
			$phpmailer->Password = $password;
		}

		switch ( $enc ) {
			case 'ssl':
				$phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
				break;
			case 'tls':
				$phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
				break;
			default:
				$phpmailer->SMTPSecure = '';
				$phpmailer->SMTPAutoTLS = false;
				break;
		}

		if ( $from ) {
			$phpmailer->From     = $from;
			$phpmailer->FromName = $name;
		}
	}

	// ─── Settings API ────────────────────────────────────────────────────────

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
	 * Sanitize all SMTP settings before they are stored.
	 *
	 * @param mixed $input Raw $_POST values from the settings form.
	 * @return array<string, mixed> Sanitized option array.
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		$encryption = sanitize_key( $input['encryption'] ?? 'tls' );
		if ( ! in_array( $encryption, [ 'none', 'ssl', 'tls' ], true ) ) {
			$encryption = 'tls';
		}

		return [
			// ── Mailer ───────────────────────────────────────────────────────
			'smtp_host'       => sanitize_text_field( $input['smtp_host']   ?? '' ),
			'encryption'      => $encryption,
			'smtp_port'       => absint( $input['smtp_port']                ?? 465 ),
			'auto_tls'        => ! empty( $input['auto_tls'] )        ? '1' : '0',
			'smtp_auth'       => ! empty( $input['smtp_auth'] )       ? '1' : '0',
			'smtp_username'   => sanitize_text_field( $input['smtp_username'] ?? '' ),
			'smtp_password'   => sanitize_text_field( $input['smtp_password'] ?? '' ),

			// ── General ──────────────────────────────────────────────────────
			'from_email'      => sanitize_email( $input['from_email']       ?? '' ),
			'from_name'       => sanitize_text_field( $input['from_name']   ?? '' ),
		];
	}

	// ─── Test email handler ──────────────────────────────────────────────────

	/**
	 * Handle the "Send Test Email" POST before any output is sent.
	 */
	public function maybe_send_test_email(): void {
		if ( empty( $_POST['pc4s_smtp_send_test'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'pc4s' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::TEST_NONCE );

		$send_to = sanitize_email( wp_unslash( $_POST['test_send_to'] ?? '' ) );
		$use_html = ! empty( $_POST['test_html'] );

		$subject = __( 'PC4S SMTP Test Email', 'pc4s' );
		$body    = $use_html
			? '<p>' . esc_html__( 'This is a test email sent from your PC4S SMTP settings.', 'pc4s' ) . '</p>'
			: __( 'This is a test email sent from your PC4S SMTP settings.', 'pc4s' );

		$headers = $use_html ? [ 'Content-Type: text/html; charset=UTF-8' ] : [];

		$opts       = (array) get_option( self::OPTION_KEY, [] );
		$from_email = sanitize_email( $opts['from_email'] ?? '' );
		$from_name  = sanitize_text_field( $opts['from_name'] ?? get_bloginfo( 'name' ) );

		if ( $from_email ) {
			$headers[] = sprintf( 'From: %s <%s>', $from_name, $from_email );
		}

		$sent   = wp_mail( $send_to, $subject, $body, $headers );
		$status = $sent ? 'test-sent' : 'test-failed';

		wp_safe_redirect(
			add_query_arg( $status, '1', admin_url( 'admin.php?page=pc4s-smtp' ) )
		);
		exit;
	}

	// ─── Static getter ───────────────────────────────────────────────────────

	/**
	 * Retrieve a single SMTP setting.
	 *
	 * @param string $key     Option subkey.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( string $key, $default = '' ) {
		static $opts = null;
		if ( null === $opts ) {
			$opts = (array) get_option( self::OPTION_KEY, [] );
		}
		return $opts[ $key ] ?? $default;
	}

	// ─── Render ──────────────────────────────────────────────────────────────

	/**
	 * Output the SMTP admin page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pc4s' ) );
		}

		$opts = (array) get_option( self::OPTION_KEY, [] );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$just_saved  = isset( $_GET['settings-updated'] ) && '1' === (string) $_GET['settings-updated'];
		$test_sent   = isset( $_GET['test-sent'] )   && '1' === (string) $_GET['test-sent'];
		$test_failed = isset( $_GET['test-failed'] ) && '1' === (string) $_GET['test-failed'];
		// phpcs:enable

		$enc        = $opts['encryption']    ?? 'tls';
		$auto_tls   = ( '1' === ( $opts['auto_tls']  ?? '1' ) );
		$smtp_auth  = ( '1' === ( $opts['smtp_auth']  ?? '1' ) );

		$field   = static fn( string $key ): string => esc_attr( self::OPTION_KEY . '[' . $key . ']' );
		$val     = static fn( string $key, string $default = '' ): string => esc_attr( (string) ( $opts[ $key ] ?? $default ) );
		?>
		<div class="wrap pc4s-admin-page pc4s-smtp-page">

			<header class="pc4s-admin-header">
				<h1 class="pc4s-admin-header__title"><?php esc_html_e( 'SMTP Settings', 'pc4s' ); ?></h1>
				<p class="pc4s-admin-header__description">
					<?php esc_html_e( 'Configure outgoing email delivery via SMTP. Settings are applied globally across all PC4S email notifications.', 'pc4s' ); ?>
				</p>
			</header>

			<?php if ( $just_saved ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--success" role="status" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
				</svg>
				<span><?php esc_html_e( 'SMTP settings saved successfully.', 'pc4s' ); ?></span>
			</div>
			<?php endif; ?>

			<?php if ( $test_sent ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--success" role="status" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
				</svg>
				<span><?php esc_html_e( 'Test email sent successfully.', 'pc4s' ); ?></span>
			</div>
			<?php endif; ?>

			<?php if ( $test_failed ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--error" role="alert" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.707 7.293a1 1 0 0 0-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 1 0 1.414 1.414L10 11.414l1.293 1.293a1 1 0 0 0 1.414-1.414L11.414 10l1.293-1.293a1 1 0 0 0-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
				</svg>
				<span><?php esc_html_e( 'Test email failed to send. Please check your SMTP settings.', 'pc4s' ); ?></span>
			</div>
			<?php endif; ?>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<!-- ── Card: Mailer Settings ────────────────────────────── -->
				<section class="pc4s-form-card" aria-labelledby="smtp-mailer-heading">
					<header class="pc4s-form-card__header">
						<h2 class="pc4s-form-card__title" id="smtp-mailer-heading">
							<?php esc_html_e( 'Mailer Settings', 'pc4s' ); ?>
						</h2>
					</header>

					<div class="pc4s-form-card__body">

						<!-- SMTP Host -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_host">
								<?php esc_html_e( 'SMTP Host', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="text"
									id="smtp_host"
									name="<?php echo $field( 'smtp_host' ); ?>"
									value="<?php echo $val( 'smtp_host', 'smtp.gmail.com' ); ?>"
									class="pc4s-field-input"
									placeholder="smtp.gmail.com"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( "Your mail server's address.", 'pc4s' ); ?><br>
									<?php esc_html_e( 'Gmail SMTP Server: smtp.gmail.com', 'pc4s' ); ?><br>
									<?php esc_html_e( 'Port 465: SSL — everywhere. Port 587: TLS — everywhere.', 'pc4s' ); ?><br>
									<?php esc_html_e( 'Password: Find it in your Google Account settings.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- Type of Encryption -->
						<div class="pc4s-smtp-row">
							<fieldset class="pc4s-smtp-row__fieldset">
								<legend class="pc4s-smtp-row__label">
									<?php esc_html_e( 'Type of Encryption', 'pc4s' ); ?>
								</legend>
								<div class="pc4s-smtp-row__control">
									<div class="pc4s-radio-group">
										<label class="pc4s-radio-label">
											<input
												type="radio"
												name="<?php echo $field( 'encryption' ); ?>"
												value="none"
												<?php checked( 'none', $enc ); ?>
												class="pc4s-radio"
											/>
											<?php esc_html_e( 'None', 'pc4s' ); ?>
										</label>
										<label class="pc4s-radio-label">
											<input
												type="radio"
												name="<?php echo $field( 'encryption' ); ?>"
												value="ssl"
												<?php checked( 'ssl', $enc ); ?>
												class="pc4s-radio"
											/>
											<?php esc_html_e( 'SSL', 'pc4s' ); ?>
										</label>
										<label class="pc4s-radio-label">
											<input
												type="radio"
												name="<?php echo $field( 'encryption' ); ?>"
												value="tls"
												<?php checked( 'tls', $enc ); ?>
												class="pc4s-radio"
											/>
											<?php esc_html_e( 'TLS', 'pc4s' ); ?>
										</label>
									</div>
									<p class="pc4s-field-hint">
										<?php esc_html_e( 'For most servers SSL is recommended; TLS is set to port 587 while SSL is used for port 465.', 'pc4s' ); ?>
									</p>
								</div>
							</fieldset>
						</div>

						<!-- SMTP Port -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_port">
								<?php esc_html_e( 'SMTP Port', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="number"
									id="smtp_port"
									name="<?php echo $field( 'smtp_port' ); ?>"
									value="<?php echo $val( 'smtp_port', '465' ); ?>"
									class="pc4s-field-input pc4s-field-input--narrow"
									min="1"
									max="65535"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'The port set by your mail server.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- Auto TLS -->
						<div class="pc4s-smtp-row">
							<span class="pc4s-smtp-row__label">
								<?php esc_html_e( 'Auto TLS', 'pc4s' ); ?>
							</span>
							<div class="pc4s-smtp-row__control">
								<label class="pc4s-toggle-label" for="smtp_auto_tls">
									<input
										type="checkbox"
										id="smtp_auto_tls"
										name="<?php echo $field( 'auto_tls' ); ?>"
										value="1"
										class="pc4s-toggle-input"
										<?php checked( $auto_tls ); ?>
									/>
									<span class="pc4s-toggle-track" aria-hidden="true"></span>
									<span class="pc4s-toggle-text"><?php esc_html_e( 'On', 'pc4s' ); ?></span>
								</label>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'By default, if the server supports TLS encryption it will be used automatically. If the server does not support TLS, the message will be sent without encryption. Uncheck to disable this.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- SMTP Authentication -->
						<div class="pc4s-smtp-row">
							<span class="pc4s-smtp-row__label">
								<?php esc_html_e( 'SMTP Authentication', 'pc4s' ); ?>
							</span>
							<div class="pc4s-smtp-row__control">
								<label class="pc4s-toggle-label" for="smtp_auth_toggle">
									<input
										type="checkbox"
										id="smtp_auth_toggle"
										name="<?php echo $field( 'smtp_auth' ); ?>"
										value="1"
										class="pc4s-toggle-input"
										<?php checked( $smtp_auth ); ?>
									/>
									<span class="pc4s-toggle-track" aria-hidden="true"></span>
									<span class="pc4s-toggle-text"><?php esc_html_e( 'On', 'pc4s' ); ?></span>
								</label>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'Authentication with username and password is required; the requires should be filled in if not in constant.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- SMTP Username -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_username">
								<?php esc_html_e( 'SMTP Username', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="text"
									id="smtp_username"
									name="<?php echo $field( 'smtp_username' ); ?>"
									value="<?php echo $val( 'smtp_username' ); ?>"
									class="pc4s-field-input"
									autocomplete="username"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'The user to log in to your mail server.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- SMTP Password -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_password">
								<?php esc_html_e( 'SMTP Password', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="password"
									id="smtp_password"
									name="<?php echo $field( 'smtp_password' ); ?>"
									value="<?php echo $val( 'smtp_password' ); ?>"
									class="pc4s-field-input"
									autocomplete="current-password"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'Your SMTP password. For Gmail, use an App Password instead of your regular password.', 'pc4s' ); ?>
								</p>
								<div class="pc4s-info-box" role="note">
									<p class="pc4s-info-box__intro"><?php esc_html_e( 'Gmail App Password Required', 'pc4s' ); ?></p>
									<ol class="pc4s-info-box__steps">
										<li><?php esc_html_e( 'Gmail requires an App Password instead of your regular password.', 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Enable 2-Factor Authentication on your Gmail Account.', 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Go to Google Account settings.', 'pc4s' ); ?></li>
										<li><?php esc_html_e( "Generate a new App Password for 'Mail'.", 'pc4s' ); ?></li>
										<li><?php esc_html_e( 'Use this 16-character password above (not your Gmail password).', 'pc4s' ); ?></li>
									</ol>
								</div>
							</div>
						</div>

					</div><!-- /.pc4s-form-card__body -->
				</section>
				<!-- ── End Card: Mailer Settings ──── -->

				<!-- ── Card: General Settings ───────────────────────────── -->
				<section class="pc4s-form-card" aria-labelledby="smtp-general-heading">
					<header class="pc4s-form-card__header">
						<h2 class="pc4s-form-card__title" id="smtp-general-heading">
							<?php esc_html_e( 'General Settings', 'pc4s' ); ?>
						</h2>
					</header>

					<div class="pc4s-form-card__body">

						<!-- From Email Address -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_from_email">
								<?php esc_html_e( 'From Email Address', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="email"
									id="smtp_from_email"
									name="<?php echo $field( 'from_email' ); ?>"
									value="<?php echo $val( 'from_email' ); ?>"
									class="pc4s-field-input"
									placeholder="noreply@example.com"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'The email address that emails are sent from.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- From Name -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_from_name">
								<?php esc_html_e( 'From Name', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="text"
									id="smtp_from_name"
									name="<?php echo $field( 'from_name' ); ?>"
									value="<?php echo $val( 'from_name', get_bloginfo( 'name' ) ); ?>"
									class="pc4s-field-input"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'The name that all emails are sent from.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

					</div><!-- /.pc4s-form-card__body -->

					<footer class="pc4s-form-card__footer">
						<button type="submit" class="pc4s-btn pc4s-btn--primary">
							<?php esc_html_e( 'Save Settings', 'pc4s' ); ?>
						</button>
					</footer>
				</section>
				<!-- ── End Card: General Settings ──── -->

			</form>

			<!-- ── Card: Send a Test ─────────────────────────────────────── -->
			<section class="pc4s-form-card" aria-labelledby="smtp-test-heading">
				<header class="pc4s-form-card__header">
					<h2 class="pc4s-form-card__title" id="smtp-test-heading">
						<?php esc_html_e( 'Send a Test', 'pc4s' ); ?>
					</h2>
				</header>

				<form
					method="post"
					action="<?php echo esc_url( admin_url( 'admin.php?page=pc4s-smtp' ) ); ?>"
					novalidate
				>
					<?php wp_nonce_field( self::TEST_NONCE ); ?>
					<input type="hidden" name="pc4s_smtp_send_test" value="1" />

					<div class="pc4s-form-card__body">

						<!-- Send To -->
						<div class="pc4s-smtp-row">
							<label class="pc4s-smtp-row__label" for="smtp_test_to">
								<?php esc_html_e( 'Send To', 'pc4s' ); ?>
							</label>
							<div class="pc4s-smtp-row__control">
								<input
									type="email"
									id="smtp_test_to"
									name="test_send_to"
									class="pc4s-field-input"
									placeholder="you@example.com"
								/>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'Enter the email address you want to send the test email to.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

						<!-- HTML -->
						<div class="pc4s-smtp-row">
							<span class="pc4s-smtp-row__label">
								<?php esc_html_e( 'HTML', 'pc4s' ); ?>
							</span>
							<div class="pc4s-smtp-row__control">
								<label class="pc4s-toggle-label" for="smtp_test_html">
									<input
										type="checkbox"
										id="smtp_test_html"
										name="test_html"
										value="1"
										class="pc4s-toggle-input"
										checked
									/>
									<span class="pc4s-toggle-track" aria-hidden="true"></span>
									<span class="pc4s-toggle-text"><?php esc_html_e( 'On', 'pc4s' ); ?></span>
								</label>
								<p class="pc4s-field-hint">
									<?php esc_html_e( 'Send email in HTML format; otherwise sends in plain text format.', 'pc4s' ); ?>
								</p>
							</div>
						</div>

					</div><!-- /.pc4s-form-card__body -->

					<footer class="pc4s-form-card__footer">
						<button type="submit" class="pc4s-btn pc4s-btn--primary">
							<?php esc_html_e( 'Send Test Email', 'pc4s' ); ?>
						</button>
					</footer>

				</form>
			</section>
			<!-- ── End Card: Send a Test ──── -->

		</div><!-- /.wrap.pc4s-smtp-page -->
		<?php
	}
}
