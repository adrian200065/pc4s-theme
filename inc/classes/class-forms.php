<?php
/**
 * Custom Forms System
 *
 * Centralized system for registering forms, handling submissions,
 * storing entries in a custom DB table, and sending admin notifications.
 * Designed to support multiple forms beyond just the newsletter.
 *
 * @package PC4S
 */

namespace PC4S\Classes;

use PC4S\Admin\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Custom_Forms {

	// ─── DB version ──────────────────────────────────────────────────────────

	const DB_VERSION     = '1.0';
	const DB_VERSION_KEY = 'pc4s_form_entries_db_version';

	// ─── Settings option ─────────────────────────────────────────────────────

	/**
	 * WordPress option key that stores editable per-form configuration.
	 * Structure: [ form_id => [ notification_emails, subject, confirmation_message ] ]
	 */
	const SETTINGS_OPTION = 'pc4s_form_settings';

	// ─── Form registry ───────────────────────────────────────────────────────

	/** @var array<string, array> */
	private static array $forms = [];

	// ─── Bootstrap ───────────────────────────────────────────────────────────

	public static function init(): void {
		self::register_forms();

		// Create DB table on theme activation or when version is stale.
		add_action( 'after_switch_theme', [ __CLASS__, 'create_tables' ] );
		add_action( 'init', [ __CLASS__, 'maybe_create_tables' ], 5 );

		// Handle form submissions (logged-in users and guests).
		add_action( 'admin_post_pc4s_form_submit',        [ __CLASS__, 'handle_submission' ] );
		add_action( 'admin_post_nopriv_pc4s_form_submit', [ __CLASS__, 'handle_submission' ] );

		// Hook the newsletter form into the template action points.
		add_action( 'pc4s_footer_newsletter_form',   [ __CLASS__, 'render_footer_newsletter' ] );
		add_action( 'pc4s_events_subscribe_section', [ __CLASS__, 'render_events_newsletter' ] );
		add_action( 'pc4s_news_newsletter',          [ __CLASS__, 'render_news_newsletter' ] );

		// Preserve existing comment-form customizations.
		add_filter( 'comment_form_default_fields', [ __CLASS__, 'custom_comment_form_fields' ] );
		add_filter( 'comment_form_defaults',       [ __CLASS__, 'custom_comment_form' ] );
	}

	// ─── Form registry ───────────────────────────────────────────────────────

	/**
	 * Register all site forms.
	 * Saved admin settings (from FormsPage) are merged over these defaults.
	 * To add a new form, append an entry here — nothing else changes.
	 */
	private static function register_forms(): void {
		// ── Newsletter defaults ───────────────────────────────────────────────
		$nl_default_subject  = __( 'New Newsletter Subscription — PC4S', 'pc4s' );
		$nl_default_message  = __( "Thank you for subscribing! You'll hear from us soon.", 'pc4s' );
		$nl_default_email    = get_option( 'admin_email' );

		// ── Merge with saved admin settings (single option read, cached) ──────
		$nl_saved = self::get_saved_settings( 'newsletter' );

		// ── License Plate pre-order defaults ────────────────────────────────────
		$lp_default_subject = __( 'New License Plate Pre-Order — PC4S', 'pc4s' );
		$lp_default_message = __( 'Thank you for your pre-order! We will keep you updated on the campaign.', 'pc4s' );
		$lp_saved           = self::get_saved_settings( 'license_plate' );

		// ── Donate saved settings ─────────────────────────────────────────────
		$dn_saved = self::get_saved_settings( 'donate' );

		self::$forms = [
			'newsletter' => [
				'id'                   => 'newsletter',
				'label'                => __( 'Newsletter', 'pc4s' ),
				'fields'               => [
					'email' => [
						'type'        => 'email',
						'label'       => __( 'Email Address', 'pc4s' ),
						'placeholder' => __( 'Your email address', 'pc4s' ),
						'required'    => true,
					],
				],
				// Saved admin values take precedence over defaults.
				'notification_emails'  => ! empty( $nl_saved['notification_emails'] )
											? $nl_saved['notification_emails']
											: $nl_default_email,
				'subject'              => ! empty( $nl_saved['subject'] )
											? $nl_saved['subject']
											: $nl_default_subject,
				'confirmation_message' => ! empty( $nl_saved['confirmation_message'] )
											? $nl_saved['confirmation_message']
											: $nl_default_message,
				'error_message'        => __( 'Please enter a valid email address.', 'pc4s' ),
			],

			// ── License Plate Pre-Order ───────────────────────────────────────────
			'license_plate' => [
				'id'             => 'license_plate',
				'label'          => __( 'License Plate Pre-Order', 'pc4s' ),
				'paypal_redirect' => true,   // On success: redirect to PayPal instead of back to page.
				'fields'         => [
					'first_name'     => [ 'type' => 'text',  'label' => __( 'First Name',     'pc4s' ), 'required' => true ],
					'last_name'      => [ 'type' => 'text',  'label' => __( 'Last Name',      'pc4s' ), 'required' => true ],
					'street_address' => [ 'type' => 'text',  'label' => __( 'Street Address', 'pc4s' ), 'required' => true ],
					'city'           => [ 'type' => 'text',  'label' => __( 'City',           'pc4s' ), 'required' => true ],
					'state'          => [ 'type' => 'text',  'label' => __( 'State',          'pc4s' ), 'required' => true ],
					'zip_code'       => [ 'type' => 'text',  'label' => __( 'Zip Code',       'pc4s' ), 'required' => true ],
					'county'         => [ 'type' => 'text',  'label' => __( 'County',         'pc4s' ), 'required' => true ],
					'email'          => [ 'type' => 'email', 'label' => __( 'Email Address',  'pc4s' ), 'required' => true ],
				],
				'notification_emails'  => ! empty( $lp_saved['notification_emails'] )
											? $lp_saved['notification_emails']
											: $nl_default_email,
				'subject'              => ! empty( $lp_saved['subject'] )
											? $lp_saved['subject']
											: $lp_default_subject,
				'confirmation_message' => ! empty( $lp_saved['confirmation_message'] )
											? $lp_saved['confirmation_message']
											: $lp_default_message,
				'error_message'        => __( 'Please fill in all required fields before submitting.', 'pc4s' ),
			],

			// ── Donate ───────────────────────────────────────────────────────────
			// PayPal Hosted Button ID is configured at PC4S → Settings.
			// On success the handler appends `amount` to the PayPal redirect URL.
			'donate' => [
			'id'              => 'donate',
			'label'           => __( 'Donate', 'pc4s' ),
			'paypal_redirect' => true,
			'fields'          => [
				'first_name'   => [ 'type' => 'text',   'label' => __( 'First Name',      'pc4s' ), 'required' => true  ],
				'last_name'    => [ 'type' => 'text',   'label' => __( 'Last Name',       'pc4s' ), 'required' => false ],
				'company_name' => [ 'type' => 'text',   'label' => __( 'Company Name',    'pc4s' ), 'required' => false ],
				'email'        => [ 'type' => 'email',  'label' => __( 'Email Address',   'pc4s' ), 'required' => true  ],
				'amount'       => [ 'type' => 'number', 'label' => __( 'Donation Amount', 'pc4s' ), 'required' => true  ],
			],
			'notification_emails'  => ! empty( $dn_saved['notification_emails'] )
										? $dn_saved['notification_emails']
										: get_option( 'admin_email' ),
			'subject'              => ! empty( $dn_saved['subject'] )
										? $dn_saved['subject']
										: __( 'New Donation — PC4S', 'pc4s' ),
			'confirmation_message' => ! empty( $dn_saved['confirmation_message'] )
										? $dn_saved['confirmation_message']
										: __( "Thank you for your generous donation! You'll be redirected to PayPal to complete your gift.", 'pc4s' ),
			'error_message'        => __( 'Please fill in all required fields and select or enter a donation amount.', 'pc4s' ),
		],
			// ── Contact Us ───────────────────────────────────────────────────────────
			'contact_us' => [
				'id'    => 'contact_us',
				'label' => __( 'Contact Us', 'pc4s' ),
				'fields' => [
					'first_name'  => [ 'type' => 'text',     'label' => __( 'First Name',    'pc4s' ), 'required' => true ],
					'last_name'   => [ 'type' => 'text',     'label' => __( 'Last Name',     'pc4s' ), 'required' => true ],
					'email'       => [ 'type' => 'email',    'label' => __( 'Email Address', 'pc4s' ), 'required' => true ],
					'subject_line' => [ 'type' => 'text',    'label' => __( 'Subject',       'pc4s' ), 'required' => true ],
					'message'     => [ 'type' => 'textarea', 'label' => __( 'Message',       'pc4s' ), 'required' => true ],
				],
				'notification_emails'  => get_option( 'admin_email' ),
				'subject'              => __( 'New Contact Us Message — PC4S', 'pc4s' ),
				'confirmation_message' => __( "Thank you for reaching out! We'll get back to you within one business day.", 'pc4s' ),
				'error_message'        => __( 'Please fill in all required fields before submitting.', 'pc4s' ),
			],

			// ── Dashboard Support ─────────────────────────────────────────────────
			'dashboard_support' => [
				'id'    => 'dashboard_support',
				'label' => __( 'Dashboard Support', 'pc4s' ),
				'fields' => [
					'name'    => [ 'type' => 'text',     'label' => __( 'Name',    'pc4s' ), 'required' => true ],
					'email'   => [ 'type' => 'email',    'label' => __( 'Email',   'pc4s' ), 'required' => true ],
					'subject' => [ 'type' => 'text',     'label' => __( 'Subject', 'pc4s' ), 'required' => true ],
					'message' => [ 'type' => 'textarea', 'label' => __( 'Message', 'pc4s' ), 'required' => true ],
				],
				'notification_emails'  => get_option( 'admin_email' ),
				'subject'              => __( 'New Dashboard Support Request — PC4S', 'pc4s' ),
				'confirmation_message' => __( "Thank you! We'll get back to you shortly.", 'pc4s' ),
				'error_message'        => __( 'Please fill in all required fields before submitting.', 'pc4s' ),
			],
		];

		// Merge saved dynamically edited field settings (label, placeholder, required)
		foreach ( self::$forms as $id => &$form ) {
			$saved = self::get_saved_settings( $id );
			if ( ! empty( $saved['fields'] ) && is_array( $saved['fields'] ) ) {
				foreach ( $saved['fields'] as $fkey => $fdata ) {
					if ( isset( $form['fields'][ $fkey ] ) ) {
						if ( isset( $fdata['label'] ) && '' !== $fdata['label'] ) {
							$form['fields'][ $fkey ]['label'] = $fdata['label'];
						}
						if ( isset( $fdata['placeholder'] ) ) {
							$form['fields'][ $fkey ]['placeholder'] = $fdata['placeholder'];
						}
						if ( isset( $fdata['required'] ) ) {
							$form['fields'][ $fkey ]['required'] = (bool) $fdata['required'];
						}
					}
				}
			}
		}
		unset( $form );
	}

	/**
	 * Return all registered forms (used by the Forms admin page).
	 *
	 * @return array<string, array>
	 */
	public static function get_forms(): array {
		return self::$forms;
	}

	/**
	 * Return a single form definition, or null if not found.
	 *
	 * @param string $form_id
	 * @return array|null
	 */
	public static function get_form( string $form_id ): ?array {
		return self::$forms[ $form_id ] ?? null;
	}

	/**
	 * Return the saved admin settings for a single form.
	 * Result is cached in a static variable — one DB read per request.
	 *
	 * @param string $form_id
	 * @return array  Saved keys: notification_emails, subject, confirmation_message.
	 */
	public static function get_saved_settings( string $form_id ): array {
		static $all = null;
		if ( null === $all ) {
			$all = (array) get_option( self::SETTINGS_OPTION, [] );
		}
		return (array) ( $all[ $form_id ] ?? [] );
	}

	/**
	 * Persist editable settings for a single form.
	 * Called from FormsPage after nonce verification and sanitization.
	 *
	 * @param string $form_id
	 * @param array  $data  Keys: notification_emails, subject, confirmation_message.
	 */
	public static function save_settings( string $form_id, array $data ): void {
		$all = (array) get_option( self::SETTINGS_OPTION, [] );
		$all[ $form_id ] = $data;
		update_option( self::SETTINGS_OPTION, $all );
	}

	// ─── Rendering ───────────────────────────────────────────────────────────

	/**
	 * Render a form by ID.
	 *
	 * @param string $form_id Registered form identifier.
	 * @param array  $args {
	 *   @type string $context        'footer' (default) or 'inline'. Controls CSS class names.
	 *   @type string $source_page    Full URL of the originating page for entry tracking.
	 *   @type string $email_input_id HTML id for the email <input>. Defaults to 'newsletter-email'.
	 * }
	 */
	public static function render_form( string $form_id, array $args = [] ): void {
		$form = self::get_form( $form_id );
		if ( ! $form ) {
			return;
		}

		$context        = isset( $args['context'] )        ? sanitize_key( $args['context'] )                    : 'footer';
		$source_page    = isset( $args['source_page'] )    ? esc_url_raw( $args['source_page'] )                 : '';
		$email_input_id = isset( $args['email_input_id'] ) ? sanitize_html_class( $args['email_input_id'] ) : 'newsletter-email';

		// CSS class names match the static-site reference HTML exactly.
		$form_class  = ( 'footer' === $context ) ? 'newsletter-form' : 'comm-feed__newsletter-form';
		$btn_class   = ( 'footer' === $context ) ? 'btn--secondary'  : 'btn--primary';
		$placeholder = ( 'footer' === $context )
			? __( 'Email Address', 'pc4s' )
			: __( 'Your email address', 'pc4s' );

		// Redirect target: use the page that rendered the form.
		$redirect = $source_page ?: ( wp_get_referer() ?: home_url( '/' ) );
		$redirect = remove_query_arg( [ 'pc4s_form', 'form_id' ], $redirect );

		// ── Inline success / error feedback ──────────────────────────────────
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$qs_status  = isset( $_GET['pc4s_form'] ) ? sanitize_key( $_GET['pc4s_form'] ) : '';
		$qs_form_id = isset( $_GET['form_id'] )   ? sanitize_key( $_GET['form_id'] )   : '';
		// phpcs:enable

		if ( $qs_status && $qs_form_id === $form_id ) {
			if ( 'success' === $qs_status ) {
				echo '<p class="form-message form-message--success" role="status">'
					. esc_html( $form['confirmation_message'] )
					. '</p>';
				return; // Hide the form after a successful submission.
			}
			if ( 'error' === $qs_status ) {
				echo '<p class="form-message form-message--error" role="alert">'
					. esc_html( $form['error_message'] )
					. '</p>';
			}
		}
		?>
		<form
			class="<?php echo esc_attr( $form_class ); ?>"
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			aria-label="<?php esc_attr_e( 'Newsletter subscription', 'pc4s' ); ?>"
			novalidate
		>
			<?php wp_nonce_field( 'pc4s_form_' . $form_id, 'pc4s_form_nonce' ); ?>
			<input type="hidden" name="action"      value="pc4s_form_submit" />
			<input type="hidden" name="form_id"     value="<?php echo esc_attr( $form_id ); ?>" />
			<input type="hidden" name="source_page" value="<?php echo esc_attr( $source_page ); ?>" />
			<input type="hidden" name="_redirect"   value="<?php echo esc_attr( $redirect ); ?>" />
			<?php /* Honeypot — bots fill every field; real users never see this one. */ ?>
			<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
				<label for="<?php echo esc_attr( $form_id ); ?>-hp"><?php esc_html_e( 'Website', 'pc4s' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $form_id ); ?>-hp" name="pc4s_hp_website" tabindex="-1" autocomplete="off" value="" />
			</div>

			<div class="form-group">
				<label for="<?php echo esc_attr( $email_input_id ); ?>" class="visually-hidden">
					<?php esc_html_e( 'Email Address', 'pc4s' ); ?>
				</label>
				<input
					type="email"
					id="<?php echo esc_attr( $email_input_id ); ?>"
					name="email"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					autocomplete="email"
					required
					aria-required="true"
				/>
			</div>

			<button type="submit" class="btn <?php echo esc_attr( $btn_class ); ?>">
				<?php esc_html_e( 'Subscribe', 'pc4s' ); ?>
			</button>
		</form>
		<?php
	}

	/**
	 * Return the confirmation message for a given form ID.
	 * Templates can call this to display success feedback independently.
	 */
	public static function get_confirmation_message( string $form_id ): string {
		$form = self::get_form( $form_id );
		return $form ? (string) $form['confirmation_message'] : '';
	}

	// ─── Action callbacks ─────────────────────────────────────────────────────

	/** Render the newsletter form inside the footer newsletter column. */
	public static function render_footer_newsletter(): void {
		get_template_part( 'parts/content/newsletter-form', null, [
			'context'        => 'footer',
			'email_input_id' => 'newsletter-email',
			'source_page'    => esc_url_raw( home_url( add_query_arg( [] ) ) ),
		] );
	}

	/** Render the newsletter form inside the events archive subscribe section. */
	public static function render_events_newsletter(): void {
		get_template_part( 'parts/content/newsletter-form', null, [
			'context'        => 'inline',
			'email_input_id' => 'comm-newsletter-email',
			'source_page'    => esc_url_raw( home_url( add_query_arg( [] ) ) ),
		] );
	}

	/**
	 * Render the newsletter form on the news / blog archive.
	 * Guards against other archive types that also use archive.php.
	 */
	public static function render_news_newsletter(): void {
		if ( ! is_home() && ! is_category() && ! is_tag() ) {
			return;
		}
		get_template_part( 'parts/content/newsletter-form', null, [
			'context'        => 'inline',
			'email_input_id' => 'comm-newsletter-email',
			'source_page'    => esc_url_raw( home_url( add_query_arg( [] ) ) ),
		] );
	}

	// ─── Submission handler ───────────────────────────────────────────────────

	public static function handle_submission(): void {
		// ── Verify nonce and resolve the form definition ──────────────────────
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';

		if (
			! $form_id
			|| ! isset( $_POST['pc4s_form_nonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['pc4s_form_nonce'] ) ),
				'pc4s_form_' . $form_id
			)
		) {
			wp_die( esc_html__( 'Security check failed.', 'pc4s' ), '', [ 'response' => 403 ] );
		}

		$form = self::get_form( $form_id );
		if ( ! $form ) {
			wp_die( esc_html__( 'Unknown form.', 'pc4s' ), '', [ 'response' => 404 ] );
		}

		// ── Honeypot check — bots fill all fields; humans leave this blank ────
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $_POST['pc4s_hp_website'] ) ) {
			// Silently pretend success to avoid telling bots they were caught.
			wp_safe_redirect(
				add_query_arg( [ 'pc4s_form' => 'success', 'form_id' => $form_id ], home_url( '/' ) )
			);
			exit;
		}

		// ── Build safe redirect base ──────────────────────────────────────────
		$redirect_base = isset( $_POST['_redirect'] )
			? esc_url_raw( wp_unslash( $_POST['_redirect'] ) )
			: home_url( '/' );
		$redirect_base = remove_query_arg( [ 'pc4s_form', 'form_id' ], $redirect_base );

		$source_page = isset( $_POST['source_page'] )
			? esc_url_raw( wp_unslash( $_POST['source_page'] ) )
			: '';

		// ── Collect and validate all registered fields ────────────────────────
		$form_fields = $form['fields'] ?? [];
		$field_data  = [];
		$has_error   = false;

		foreach ( $form_fields as $field_key => $field_def ) {
			$field_type     = $field_def['type']     ?? 'text';
			$field_required = ! empty( $field_def['required'] );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_value      = isset( $_POST[ $field_key ] ) ? wp_unslash( $_POST[ $field_key ] ) : '';

			if ( 'email' === $field_type ) {
				$value = sanitize_email( $raw_value );
				if ( $field_required && ! is_email( $value ) ) {
					$has_error = true;
					break;
				}
			} elseif ( 'textarea' === $field_type ) {
				$value = sanitize_textarea_field( $raw_value );
				if ( $field_required && '' === $value ) {
					$has_error = true;
					break;
				}
			} elseif ( 'number' === $field_type ) {
				// Validate as a monetary amount: must be a number >= 1.00.
				$float_val = (float) sanitize_text_field( $raw_value );
				if ( $field_required && $float_val < 1.00 ) {
					$has_error = true;
					break;
				}
				// Store as a clean formatted string (2 decimal places).
				$value = $float_val >= 1.00 ? number_format( $float_val, 2, '.', '' ) : '0.00';
			} else {
				$value = sanitize_text_field( $raw_value );
				if ( $field_required && '' === $value ) {
					$has_error = true;
					break;
				}
			}

			$field_data[ $field_key ] = $value;
		}

		// Fallback for legacy forms that define no fields — validate email only.
		if ( empty( $form_fields ) ) {
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( ! is_email( $email ) ) {
				$has_error = true;
			} else {
				$field_data['email'] = $email;
			}
		}

		if ( $has_error ) {
			wp_safe_redirect(
				add_query_arg( [ 'pc4s_form' => 'error', 'form_id' => $form_id ], $redirect_base )
			);
			exit;
		}

		// ── Persist entry ─────────────────────────────────────────────────────
		self::save_entry( $form_id, wp_json_encode( $field_data ), $source_page );

		// ── Notify admin ──────────────────────────────────────────────────────
		self::send_notification_email( $form, $field_data, $source_page );

		// ── Redirect — PayPal or standard success page ─────────────────────────
		if ( ! empty( $form['paypal_redirect'] ) ) {
			$paypal_sandbox = SettingsPage::is_enabled( 'paypal_sandbox' );
			$paypal_base    = $paypal_sandbox
				? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
				: 'https://www.paypal.com/cgi-bin/webscr';

			// When the form collects a variable donation amount, use cmd=_donations
			// (standard PayPal donation command) which honours the `amount` URL
			// parameter. cmd=_s-xclick (hosted button) stores its amount server-side
			// on PayPal and silently ignores any `amount` query parameter — that is
			// why the checkout page was always showing $0.
			$has_variable_amount = ! empty( $field_data['amount'] ) && (float) $field_data['amount'] > 0;
			$paypal_email        = SettingsPage::get( 'paypal_email' );

			if ( $has_variable_amount && $paypal_email ) {
				$paypal_params = [
					'cmd'           => '_donations',
					'business'      => $paypal_email,
					'item_name'     => __( 'Donation to PC4S', 'pc4s' ),
					'amount'        => number_format( (float) $field_data['amount'], 2, '.', '' ),
					'currency_code' => SettingsPage::get( 'paypal_currency', 'USD' ),
					'no_note'       => '0',
					'return'        => add_query_arg( [ 'pc4s_form' => 'success', 'form_id' => $form_id ], $redirect_base ),
					'cancel_return' => add_query_arg( [ 'pc4s_form' => 'cancel',  'form_id' => $form_id ], $redirect_base ),
				];
				$paypal_url = add_query_arg( $paypal_params, $paypal_base );
				wp_redirect( esc_url_raw( $paypal_url ) ); // External URL — wp_safe_redirect would block it.
				exit;
			}

			// Fixed-price forms (e.g. license plate pre-order) use a hosted button.
			$button_id = SettingsPage::get_paypal_button_id( $form_id );
			if ( $button_id ) {
				$paypal_params = [
					'cmd'              => '_s-xclick',
					'hosted_button_id' => $button_id,
				];
				$paypal_url = add_query_arg( $paypal_params, $paypal_base );
				wp_redirect( esc_url_raw( $paypal_url ) );
				exit;
			}
			// PayPal not configured — fall through to standard success redirect.
		}

		wp_safe_redirect(
			add_query_arg( [ 'pc4s_form' => 'success', 'form_id' => $form_id ], $redirect_base )
		);
		exit;
	}

	// ─── Storage ──────────────────────────────────────────────────────────────

	private static function save_entry( string $form_id, string $field_data, string $source_page = '' ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pc4s_form_entries',
			[
				'form_id'      => $form_id,
				'field_data'   => $field_data,
				'source_page'  => $source_page,
				'submitted_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);
	}

	// ─── Notification ─────────────────────────────────────────────────────────

	private static function send_notification_email( array $form, array $field_data, string $source_page ): void {
		// ── Resolve recipients ────────────────────────────────────────────────
		$raw        = ! empty( $form['notification_emails'] ) ? $form['notification_emails'] : get_option( 'admin_email' );
		$parsed     = array_map( 'trim', explode( ',', $raw ) );
		$recipients = array_values( array_filter( $parsed, 'is_email' ) );

		if ( empty( $recipients ) ) {
			$recipients = [ get_option( 'admin_email' ) ];
		}

		// Use the subject typed in the form if present, otherwise fall back to the form default.
		$subject = ! empty( $field_data['subject_line'] )
			? sanitize_text_field( $field_data['subject_line'] ) . ' — ' . get_bloginfo( 'name' )
			: $form['subject'];

		// ── Build branded HTML body ───────────────────────────────────────────
		$html_body = Email_Template::build( $form, $field_data, $source_page );
		$headers   = Email_Template::headers();

		wp_mail( $recipients, $subject, $html_body, $headers );
	}

	// ─── Database ────────────────────────────────────────────────────────────

	/**
	 * Create the form-entries table only when the stored DB version is stale.
	 * Called on `init` so the check is cheap on every request.
	 */
	public static function maybe_create_tables(): void {
		if ( get_option( self::DB_VERSION_KEY ) !== self::DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Create (or upgrade) the pc4s_form_entries table via dbDelta().
	 * Called directly on theme activation via `after_switch_theme`.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'pc4s_form_entries';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id      VARCHAR(100)        NOT NULL DEFAULT '',
			field_data   LONGTEXT            NOT NULL,
			source_page  VARCHAR(500)        NOT NULL DEFAULT '',
			submitted_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id  (form_id),
			KEY submitted_at (submitted_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_KEY, self::DB_VERSION, false );
	}

	// ─── Comment form (preserved from original) ───────────────────────────────

	/**
	 * Customize comment form fields.
	 */
	public static function custom_comment_form_fields( $fields ) {
		$commenter = wp_get_current_commenter();
		$req       = get_option( 'require_name_email' );

		$fields['author'] = '<p class="comment-form-author">'
			. '<label for="author">' . __( 'Name', 'pc4s' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> '
			. '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . ( $req ? ' required' : '' ) . ' /></p>';

		$fields['email'] = '<p class="comment-form-email">'
			. '<label for="email">' . __( 'Email', 'pc4s' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> '
			. '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30"' . ( $req ? ' required' : '' ) . ' /></p>';

		$fields['url'] = '<p class="comment-form-url">'
			. '<label for="url">' . __( 'Website', 'pc4s' ) . '</label> '
			. '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>';

		return $fields;
	}

	/**
	 * Customize the comment textarea.
	 */
	public static function custom_comment_form( $defaults ) {
		$defaults['comment_field'] = '<p class="comment-form-comment">'
			. '<label for="comment">' . __( 'Comment', 'pc4s' ) . ' <span class="required">*</span></label> '
			. '<textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

		return $defaults;
	}
}

Custom_Forms::init();
