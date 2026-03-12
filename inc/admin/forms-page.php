<?php
/**
 * Forms Admin Page
 *
 * Editable per-form configuration: notification emails (comma-separated),
 * email subject, and confirmation message. Settings are persisted via the
 * Custom_Forms::save_settings() helper (option key: pc4s_form_settings).
 *
 * Save flow:
 *   1. Admin submits POST to admin.php?page=pc4s-forms.
 *   2. maybe_save_settings() fires on admin_init, verifies nonce, sanitizes,
 *      saves, then redirects back with ?saved=1.
 *   3. render_page() detects ?saved=1 and displays the success notice.
 *
 * @package PC4S\Admin
 */

namespace PC4S\Admin;

use PC4S\Classes\Custom_Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormsPage {

	const CAPABILITY = 'manage_options';
	const NONCE_KEY  = 'pc4s_form_settings_nonce';
	const ACTION     = 'pc4s_save_form_settings';

	/** @var FormsPage|null */
	private static ?FormsPage $instance = null;

	public static function get_instance(): FormsPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_save_settings' ] );
	}

	// ─── Save handler ─────────────────────────────────────────────────────────

	/**
	 * Process the settings form POST on admin_init (before any output).
	 * Redirects back to the Forms page to prevent double-submit.
	 */
	public function maybe_save_settings(): void {
		if ( empty( $_POST[ self::ACTION ] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'pc4s' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE_KEY );

		$form_id = sanitize_key( $_POST['form_id'] ?? '' );
		if ( ! $form_id ) {
			return;
		}

		// ── Sanitize notification emails ──────────────────────────────────────
		$raw_emails  = sanitize_text_field( wp_unslash( $_POST['notification_emails'] ?? '' ) );
		$email_parts = array_map( 'trim', explode( ',', $raw_emails ) );
		$valid_emails = array_filter( $email_parts, 'is_email' );
		$notification_emails = implode( ', ', $valid_emails );

		// ── Sanitize remaining fields ─────────────────────────────────────────
		$subject              = sanitize_text_field( wp_unslash( $_POST['subject']              ?? '' ) );
		$confirmation_message = sanitize_textarea_field( wp_unslash( $_POST['confirmation_message'] ?? '' ) );

		// ── Sanitize fields array ─────────────────────────────────────────────
		$fields_data = [];
		if ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) {
			foreach ( wp_unslash( $_POST['fields'] ) as $fkey => $fdata ) {
				$fkey = sanitize_key( $fkey );
				if ( isset( $fdata['label'] ) ) {
					$fields_data[ $fkey ]['label'] = sanitize_text_field( $fdata['label'] );
				}
				if ( isset( $fdata['placeholder'] ) ) {
					$fields_data[ $fkey ]['placeholder'] = sanitize_text_field( $fdata['placeholder'] );
				}
				$fields_data[ $fkey ]['required'] = ! empty( $fdata['required'] );
			}
		}

		Custom_Forms::save_settings( $form_id, [
			'notification_emails'  => $notification_emails,
			'subject'              => $subject,
			'confirmation_message' => $confirmation_message,
			'fields'               => $fields_data,
		] );

		wp_safe_redirect(
			add_query_arg( 'saved', '1', admin_url( 'admin.php?page=pc4s-forms' ) )
		);
		exit;
	}

	// ─── Render ───────────────────────────────────────────────────────────────

	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pc4s' ) );
		}

		$forms = Custom_Forms::get_forms();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$just_saved = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
		// phpcs:enable
		?>
		<div class="wrap pc4s-admin-page pc4s-forms-page">

			<header class="pc4s-admin-header">
				<h1 class="pc4s-admin-header__title"><?php esc_html_e( 'Forms', 'pc4s' ); ?></h1>
				<p class="pc4s-admin-header__description">
					<?php esc_html_e( 'Configure notification settings for each form. Changes take effect immediately.', 'pc4s' ); ?>
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

			<?php if ( empty( $forms ) ) : ?>
			<p><?php esc_html_e( 'No forms are currently registered.', 'pc4s' ); ?></p>
			<?php else : ?>

				<?php foreach ( $forms as $form_id => $form ) :
					$saved       = Custom_Forms::get_saved_settings( $form_id );
					$entries_url = add_query_arg(
						[ 'page' => 'pc4s-form-entries', 'form_id' => $form_id ],
						admin_url( 'admin.php' )
					);

					// Resolved display values (saved overrides default).
					$val_emails  = ! empty( $saved['notification_emails'] )
						? $saved['notification_emails']
						: $form['notification_emails'];
					$val_subject = ! empty( $saved['subject'] )
						? $saved['subject']
						: $form['subject'];
					$val_message = ! empty( $saved['confirmation_message'] )
						? $saved['confirmation_message']
						: $form['confirmation_message'];

					$field_subject_id  = 'pc4s-subject-'  . esc_attr( $form_id );
					$field_emails_id   = 'pc4s-emails-'   . esc_attr( $form_id );
					$field_message_id  = 'pc4s-message-'  . esc_attr( $form_id );
				?>

				<article class="pc4s-form-card" aria-label="<?php echo esc_attr( $form['label'] ); ?>">

					<!-- Card header -->
					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php echo esc_html( $form['label'] ); ?></h2>
							<code class="pc4s-form-badge"><?php echo esc_html( $form_id ); ?></code>
						</div>
						<a
							class="pc4s-form-card__entries-link"
							href="<?php echo esc_url( $entries_url ); ?>"
						>
							<svg aria-hidden="true" focusable="false" viewBox="0 0 20 20" fill="currentColor">
								<path d="M9 2a1 1 0 0 0 0 2h2.586L5 10.586 3.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l8-8V7a1 1 0 1 0 2 0V3a1 1 0 0 0-1-1H9z"/>
							</svg>
							<?php esc_html_e( 'View entries', 'pc4s' ); ?>
						</a>
					</header>

					<!-- Settings form -->
					<form
						method="post"
						action="<?php echo esc_url( admin_url( 'admin.php?page=pc4s-forms' ) ); ?>"
						class="pc4s-form-card__body"
						novalidate
					>
						<?php wp_nonce_field( self::NONCE_KEY ); ?>
						<input type="hidden" name="<?php echo esc_attr( self::ACTION ); ?>" value="1" />
						<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>" />

						<!-- Settings section -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Settings', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="<?php echo esc_attr( $field_emails_id ); ?>">
									<?php esc_html_e( 'Notification Emails', 'pc4s' ); ?>
									<span class="pc4s-field-required" aria-hidden="true">*</span>
								</label>
								<input
									type="text"
									id="<?php echo esc_attr( $field_emails_id ); ?>"
									name="notification_emails"
									class="pc4s-field-input"
									value="<?php echo esc_attr( $val_emails ); ?>"
									autocomplete="off"
									required
									aria-required="true"
									aria-describedby="<?php echo esc_attr( $field_emails_id ); ?>-hint"
								/>
								<p id="<?php echo esc_attr( $field_emails_id ); ?>-hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Separate multiple addresses with commas. Only valid emails are saved.', 'pc4s' ); ?>
								</p>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="<?php echo esc_attr( $field_subject_id ); ?>">
									<?php esc_html_e( 'Email Subject', 'pc4s' ); ?>
									<span class="pc4s-field-required" aria-hidden="true">*</span>
								</label>
								<input
									type="text"
									id="<?php echo esc_attr( $field_subject_id ); ?>"
									name="subject"
									class="pc4s-field-input"
									value="<?php echo esc_attr( $val_subject ); ?>"
									required
									aria-required="true"
								/>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="<?php echo esc_attr( $field_message_id ); ?>">
									<?php esc_html_e( 'Confirmation Message', 'pc4s' ); ?>
								</label>
								<textarea
									id="<?php echo esc_attr( $field_message_id ); ?>"
									name="confirmation_message"
									class="pc4s-field-textarea"
									rows="3"
									aria-describedby="<?php echo esc_attr( $field_message_id ); ?>-hint"
								><?php echo esc_textarea( $val_message ); ?></textarea>
								<p id="<?php echo esc_attr( $field_message_id ); ?>-hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Shown to the visitor immediately after a successful submission.', 'pc4s' ); ?>
								</p>
							</div>
						</section>

						<!-- Form fields overview (editable) -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Form Fields', 'pc4s' ); ?></h3>
							<div class="pc4s-table-wrap">
								<table class="pc4s-fields-table">
									<thead>
										<tr>
											<th scope="col"><?php esc_html_e( 'Key', 'pc4s' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Type', 'pc4s' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Label', 'pc4s' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Placeholder', 'pc4s' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Required', 'pc4s' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $form['fields'] as $fkey => $field ) : 
											// Fetch currently saved values if they exist, otherwise fallback to defaults
											$saved_f_label       = $saved['fields'][$fkey]['label'] ?? $field['label'];
											$saved_f_placeholder = $saved['fields'][$fkey]['placeholder'] ?? ( $field['placeholder'] ?? '' );
											$saved_f_required    = isset( $saved['fields'][$fkey]['required'] ) ? $saved['fields'][$fkey]['required'] : ! empty( $field['required'] );
										?>
										<tr>
											<td><code><?php echo esc_html( $fkey ); ?></code></td>
											<td><?php echo esc_html( $field['type'] ); ?></td>
											<td>
												<input type="text" class="pc4s-field-input" name="fields[<?php echo esc_attr( $fkey ); ?>][label]" value="<?php echo esc_attr( $saved_f_label ); ?>" aria-label="<?php esc_attr_e( 'Label for ', 'pc4s' ); echo esc_attr( $fkey ); ?>" />
											</td>
											<td>
												<?php if ( ! in_array( $field['type'], [ 'checkbox', 'radio', 'select' ], true ) ) : ?>
													<input type="text" class="pc4s-field-input" name="fields[<?php echo esc_attr( $fkey ); ?>][placeholder]" value="<?php echo esc_attr( $saved_f_placeholder ); ?>" aria-label="<?php esc_attr_e( 'Placeholder for ', 'pc4s' ); echo esc_attr( $fkey ); ?>" />
												<?php else : ?>
													<span class="pc4s-text-muted">—</span>
												<?php endif; ?>
											</td>
											<td>
												<label class="pc4s-checkbox-label" style="display: flex; align-items: center;">
													<input type="checkbox" name="fields[<?php echo esc_attr( $fkey ); ?>][required]" value="1" <?php checked( $saved_f_required ); ?> />
												</label>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</section>

						<!-- Card footer / submit -->
						<footer class="pc4s-form-card__footer">
							<button type="submit" class="pc4s-btn pc4s-btn--primary">
								<?php esc_html_e( 'Save Settings', 'pc4s' ); ?>
							</button>
						</footer>

					</form>
				</article>

				<?php endforeach; ?>

			<?php endif; ?>

		</div><!-- .pc4s-forms-page -->
		<?php
	}
}

