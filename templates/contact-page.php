<?php
/**
 * Template Name: Contact Us
 * Template Post Type: page
 *
 * Page template for the Contact Us page.
 *
 * Sections rendered (in order):
 *   1. Page Banner  — parts/content/page-banner.php
 *   2. Intro        — badge, heading, lead paragraph + quick-contact cards
 *   3. Form + Info  — two-column: info sidebar (address/phone/email) + contact form
 *
 * Contact details (phone, email, address) are read from the Footer admin page via
 * FooterSettings::get(). Update them at PC4S → Footer Settings.
 *
 * ACF field group: group_contact_page (acf-json/group_contact_page.json)
 *   — Manages intro badge/title/lead and form labels only.
 *
 * Form handling: PC4S\Classes\Custom_Forms ('contact_us' form ID).
 *   Submission → admin-post.php → sanitize & save entry → notify admin → redirect.
 *   Success: ?pc4s_form=success&form_id=contact_us
 *   Error:   ?pc4s_form=error&form_id=contact_us
 *
 * @package PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Admin\FooterSettings;
use PC4S\Classes\Custom_Forms;

// ---------------------------------------------------------------------------
// Resolve all fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Intro Section (ACF) ---
$intro_badge = (string) get_field( 'cp_intro_badge' );
$intro_title = (string) get_field( 'cp_intro_title' );
$intro_lead  = (string) get_field( 'cp_intro_lead' );

// --- Contact Details — sourced from PC4S → Footer Settings (shared with the footer).
// Update phone, email, and address at PC4S → Footer in the WP admin.
$contact_phone   = FooterSettings::get( 'phone' );
$contact_email   = FooterSettings::get( 'email' );
$contact_addr1   = FooterSettings::get( 'address_line1' );
$contact_addr2   = FooterSettings::get( 'address_line2' );
$hours_weekday   = FooterSettings::get( 'office_hours_weekday',  __( '8:00 am – 4:00 pm', 'pc4s' ) );
$hours_saturday  = FooterSettings::get( 'office_hours_saturday', __( 'Closed', 'pc4s' ) );
$hours_sunday    = FooterSettings::get( 'office_hours_sunday',   __( 'Closed', 'pc4s' ) );

// --- Form Labels (ACF) ---
$form_title        = (string) get_field( 'cp_form_title' );
$form_subtitle     = (string) get_field( 'cp_form_subtitle' );
$form_req_note     = (string) get_field( 'cp_form_required_note' );
$form_privacy_text = (string) get_field( 'cp_form_privacy_text' );
$form_submit_label = (string) get_field( 'cp_form_submit_label' );

// ---------------------------------------------------------------------------
// Form feedback — set by Custom_Forms::handle_submission() via query string.
// ---------------------------------------------------------------------------
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$qs_status  = isset( $_GET['pc4s_form'] ) ? sanitize_key( $_GET['pc4s_form'] ) : '';
$qs_form_id = isset( $_GET['form_id'] )   ? sanitize_key( $_GET['form_id'] )   : '';
// phpcs:enable

$cu_success = ( 'success' === $qs_status && 'contact_us' === $qs_form_id );
$cu_error   = ( 'error'   === $qs_status && 'contact_us' === $qs_form_id );

$form_redirect = esc_url_raw( home_url( add_query_arg( [] ) ) );
$form_redirect = remove_query_arg( [ 'pc4s_form', 'form_id' ], $form_redirect );

// Load form-field definitions (merged with any admin-saved overrides).
$_cu_fields = Custom_Forms::get_form( 'contact_us' )['fields'] ?? [];

// ---------------------------------------------------------------------------
// Inline SVG helper — returns a hardcoded safe SVG by icon key.
// ---------------------------------------------------------------------------
$cp_icon = static function ( string $type ): string {
	$icons = [
		'phone'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
		'email'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
		'location' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
	];
	return $icons[ $type ] ?? '';
};

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION: Contact Intro — badge, heading, lead + quick-contact cards
	   ================================================================ */ ?>
	<?php if ( $intro_title || $contact_phone || $contact_email ) : ?>
	<section class="section contact-intro" aria-labelledby="contact-intro-heading">
		<div class="wrapper contact-intro__inner">

			<!-- Left: Copy -->
			<?php if ( $intro_title ) : ?>
			<div class="contact-intro__copy">

				<?php if ( $intro_badge ) : ?>
					<span class="contact-intro__badge"><?php echo esc_html( $intro_badge ); ?></span>
				<?php endif; ?>

				<h2 id="contact-intro-heading" class="contact-intro__title">
					<?php echo esc_html( $intro_title ); ?>
				</h2>

				<?php if ( $intro_lead ) : ?>
					<p class="contact-intro__lead"><?php echo esc_html( $intro_lead ); ?></p>
				<?php endif; ?>

			</div><!-- .contact-intro__copy -->
			<?php endif; ?>

			<!-- Right: Quick-contact cards -->
			<ul class="contact-intro__quick-list" role="list" aria-label="<?php esc_attr_e( 'Quick contact options', 'pc4s' ); ?>">

				<?php if ( $contact_phone ) : ?>
				<li>
					<a
						href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>"
						class="contact-intro__quick-item"
						aria-label="<?php echo esc_attr( sprintf( __( 'Call PC4S at %s', 'pc4s' ), $contact_phone ) ); ?>"
					>
						<span class="contact-intro__quick-icon">
							<?php echo $cp_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
						</span>
						<span class="contact-intro__quick-info">
							<span class="contact-intro__quick-label"><?php esc_html_e( 'Phone', 'pc4s' ); ?></span>
							<span class="contact-intro__quick-value"><?php echo esc_html( $contact_phone ); ?></span>
						</span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( $contact_email ) : ?>
				<li>
					<a
						href="mailto:<?php echo esc_attr( sanitize_email( $contact_email ) ); ?>"
						class="contact-intro__quick-item"
						aria-label="<?php echo esc_attr( sprintf( __( 'Email PC4S at %s', 'pc4s' ), $contact_email ) ); ?>"
					>
						<span class="contact-intro__quick-icon">
							<?php echo $cp_icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
						</span>
						<span class="contact-intro__quick-info">
							<span class="contact-intro__quick-label"><?php esc_html_e( 'Email', 'pc4s' ); ?></span>
							<span class="contact-intro__quick-value"><?php echo esc_html( $contact_email ); ?></span>
						</span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( $contact_addr1 ) : ?>
				<li>
					<?php
					$maps_href = 'https://maps.google.com/?q=' . rawurlencode( $contact_addr1 . ( $contact_addr2 ? ', ' . $contact_addr2 : '' ) );
					?>
					<a
						href="<?php echo esc_url( $maps_href ); ?>"
						class="contact-intro__quick-item"
						target="_blank"
						rel="noopener noreferrer"
						aria-label="<?php esc_attr_e( 'Get directions to PC4S office (opens in new tab)', 'pc4s' ); ?>"
					>
						<span class="contact-intro__quick-icon">
							<?php echo $cp_icon( 'location' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
						</span>
						<span class="contact-intro__quick-info">
							<span class="contact-intro__quick-label"><?php esc_html_e( 'Office', 'pc4s' ); ?></span>
							<span class="contact-intro__quick-value"><?php echo esc_html( $contact_addr1 ); ?></span>
						</span>
					</a>
				</li>
				<?php endif; ?>

			</ul><!-- .contact-intro__quick-list -->

		</div><!-- .wrapper.contact-intro__inner -->
	</section><!-- .contact-intro -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: Contact Form + Info Sidebar (two-column layout)
	   ================================================================ */ ?>
	<section class="section contact-form-section" aria-labelledby="contact-form-heading">
		<div class="wrapper contact-form-section__inner">

			<!-- Info Sidebar -->
			<aside class="contact-form-section__sidebar" aria-label="<?php esc_attr_e( 'PC4S contact information', 'pc4s' ); ?>">

				<?php if ( $contact_addr1 || $contact_phone || $contact_email ) : ?>
				<div class="contact-form-section__info-card">
					<h2 class="contact-form-section__info-card-title"><?php esc_html_e( 'Our Information', 'pc4s' ); ?></h2>
					<ul class="contact-form-section__info-list" role="list">

						<?php if ( $contact_addr1 ) : ?>
						<li class="contact-form-section__info-item">
							<span class="contact-form-section__info-icon">
								<?php echo $cp_icon( 'location' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
							</span>
							<span class="contact-form-section__info-content">
								<span class="contact-form-section__info-label"><?php esc_html_e( 'Mailing Address', 'pc4s' ); ?></span>
								<address class="contact-form-section__info-value" style="font-style:normal">
									<?php echo esc_html( $contact_addr1 ); ?>
									<?php if ( $contact_addr2 ) : ?>
										<br /><?php echo esc_html( $contact_addr2 ); ?>
									<?php endif; ?>
								</address>
							</span>
						</li>
						<?php endif; ?>

						<?php if ( $contact_phone ) : ?>
						<li class="contact-form-section__info-item">
							<span class="contact-form-section__info-icon">
								<?php echo $cp_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
							</span>
							<span class="contact-form-section__info-content">
								<span class="contact-form-section__info-label"><?php esc_html_e( 'Phone', 'pc4s' ); ?></span>
								<span class="contact-form-section__info-value">
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>">
										<?php echo esc_html( $contact_phone ); ?>
									</a>
								</span>
							</span>
						</li>
						<?php endif; ?>

						<?php if ( $contact_email ) : ?>
						<li class="contact-form-section__info-item">
							<span class="contact-form-section__info-icon">
								<?php echo $cp_icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
							</span>
							<span class="contact-form-section__info-content">
								<span class="contact-form-section__info-label"><?php esc_html_e( 'Email', 'pc4s' ); ?></span>
								<span class="contact-form-section__info-value">
									<a href="mailto:<?php echo esc_attr( sanitize_email( $contact_email ) ); ?>">
										<?php echo esc_html( $contact_email ); ?>
									</a>
								</span>
							</span>
						</li>
						<?php endif; ?>

					</ul>
				</div><!-- .contact-form-section__info-card -->
				<?php endif; ?>

				<?php if ( $hours_weekday || $hours_saturday || $hours_sunday ) : ?>
				<div class="contact-form-section__info-card">
					<h2 class="contact-form-section__info-card-title"><?php esc_html_e( 'Office Hours', 'pc4s' ); ?></h2>
					<table class="contact-form-section__hours" aria-label="<?php esc_attr_e( 'PC4S office hours', 'pc4s' ); ?>">
						<thead class="screen-reader-text">
							<tr>
								<th scope="col"><?php esc_html_e( 'Day', 'pc4s' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Hours', 'pc4s' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( $hours_weekday ) : ?>
							<tr>
								<td><?php esc_html_e( 'Monday &ndash; Friday', 'pc4s' ); ?></td>
								<td><?php echo esc_html( $hours_weekday ); ?></td>
							</tr>
							<?php endif; ?>
							<?php if ( $hours_saturday ) : ?>
							<tr>
								<td><?php esc_html_e( 'Saturday', 'pc4s' ); ?></td>
								<td><?php echo esc_html( $hours_saturday ); ?></td>
							</tr>
							<?php endif; ?>
							<?php if ( $hours_sunday ) : ?>
							<tr>
								<td><?php esc_html_e( 'Sunday', 'pc4s' ); ?></td>
								<td><?php echo esc_html( $hours_sunday ); ?></td>
							</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div><!-- .contact-form-section__info-card (hours) -->
				<?php endif; ?>

			</aside><!-- .contact-form-section__sidebar -->

			<!-- Form Column -->
			<div class="contact-form-section__form-col">

				<?php if ( $cu_success ) : ?>
					<div class="form-message form-message--success" role="status">
						<h2 class="contact-form__title"><?php echo esc_html( $form_title ?: __( 'Message Sent!', 'pc4s' ) ); ?></h2>
						<p><?php esc_html_e( "Thank you for reaching out. We'll get back to you within one business day.", 'pc4s' ); ?></p>
					</div>
				<?php else : ?>

					<?php if ( $cu_error ) : ?>
					<div class="form-message form-message--error" role="alert" id="contact-form-error" tabindex="-1">
						<p><?php esc_html_e( 'Please fill in all required fields before submitting.', 'pc4s' ); ?></p>
					</div>
					<?php endif; ?>

					<form
						class="contact-form"
						id="contact-form"
						method="post"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						novalidate
						aria-labelledby="contact-form-heading"
						<?php if ( $cu_error ) : ?>aria-describedby="contact-form-error"<?php endif; ?>
					>
						<?php wp_nonce_field( 'pc4s_form_contact_us', 'pc4s_form_nonce' ); ?>
						<input type="hidden" name="action"      value="pc4s_form_submit" />
						<input type="hidden" name="form_id"     value="contact_us" />
						<input type="hidden" name="source_page" value="<?php echo esc_attr( home_url( add_query_arg( [] ) ) ); ?>" />
						<input type="hidden" name="_redirect"   value="<?php echo esc_attr( $form_redirect ); ?>" />
						<?php /* Honeypot — bots fill every field; real users never see this one. */ ?>
							<div style="display:none;" aria-hidden="true">
							<label for="contact-hp"><?php esc_html_e( 'Website', 'pc4s' ); ?></label>
							<input type="text" id="contact-hp" name="pc4s_hp_website" tabindex="-1" autocomplete="off" value="" />
						</div>

						<h2 id="contact-form-heading" class="contact-form__title">
							<?php echo esc_html( $form_title ?: __( 'Leave a Message', 'pc4s' ) ); ?>
						</h2>

						<?php if ( $form_subtitle ) : ?>
							<p class="contact-form__subtitle"><?php echo esc_html( $form_subtitle ); ?></p>
						<?php endif; ?>

						<p class="contact-form__required-note">
							<?php echo esc_html( $form_req_note ?: __( 'All fields are required', 'pc4s' ) ); ?>
						</p>

						<!-- First / Last Name -->
						<div class="contact-form__row">
							<div class="contact-form__group">
								<label for="contact-first-name" class="contact-form__label">
								<?php echo esc_html( $_cu_fields['first_name']['label'] ); ?>
								<?php if ( ! empty( $_cu_fields['first_name']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
							</label>
							<input
								type="text"
								id="contact-first-name"
								name="first_name"
								class="contact-form__input"
								placeholder="<?php echo esc_attr( $_cu_fields['first_name']['placeholder'] ?? '' ); ?>"
								autocomplete="given-name"
								<?php echo ! empty( $_cu_fields['first_name']['required'] ) ? 'required aria-required="true"' : ''; ?>
								/>
							</div>
							<div class="contact-form__group">
								<label for="contact-last-name" class="contact-form__label">
								<?php echo esc_html( $_cu_fields['last_name']['label'] ); ?>
								<?php if ( ! empty( $_cu_fields['last_name']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
							</label>
							<input
								type="text"
								id="contact-last-name"
								name="last_name"
								class="contact-form__input"
								placeholder="<?php echo esc_attr( $_cu_fields['last_name']['placeholder'] ?? '' ); ?>"
								autocomplete="family-name"
								<?php echo ! empty( $_cu_fields['last_name']['required'] ) ? 'required aria-required="true"' : ''; ?>
								/>
							</div>
						</div><!-- .contact-form__row -->

						<!-- Email -->
						<div class="contact-form__group">
							<label for="contact-email" class="contact-form__label">
							<?php echo esc_html( $_cu_fields['email']['label'] ); ?>
							<?php if ( ! empty( $_cu_fields['email']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
						</label>
						<input
							type="email"
							id="contact-email"
							name="email"
							class="contact-form__input"
							placeholder="<?php echo esc_attr( $_cu_fields['email']['placeholder'] ?? '' ); ?>"
							autocomplete="email"
							<?php echo ! empty( $_cu_fields['email']['required'] ) ? 'required aria-required="true"' : ''; ?>
								inputmode="email"
							/>
						</div><!-- .contact-form__group -->

						<!-- Subject -->
						<div class="contact-form__group">
							<label for="contact-subject" class="contact-form__label">
							<?php echo esc_html( $_cu_fields['subject_line']['label'] ); ?>
							<?php if ( ! empty( $_cu_fields['subject_line']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
						</label>
						<input
							type="text"
							id="contact-subject"
							name="subject_line"
							class="contact-form__input"
							placeholder="<?php echo esc_attr( $_cu_fields['subject_line']['placeholder'] ?? '' ); ?>"
							<?php echo ! empty( $_cu_fields['subject_line']['required'] ) ? 'required aria-required="true"' : ''; ?>
							/>
						</div><!-- .contact-form__group -->

						<!-- Message -->
						<div class="contact-form__group">
							<label for="contact-message" class="contact-form__label">
							<?php echo esc_html( $_cu_fields['message']['label'] ); ?>
							<?php if ( ! empty( $_cu_fields['message']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
						</label>
						<textarea
							id="contact-message"
							name="message"
							class="contact-form__textarea"
							placeholder="<?php echo esc_attr( $_cu_fields['message']['placeholder'] ?? '' ); ?>"
							rows="7"
							<?php echo ! empty( $_cu_fields['message']['required'] ) ? 'required aria-required="true"' : ''; ?>
							></textarea>
						</div><!-- .contact-form__group -->

						<!-- Footer / Submit -->
						<div class="contact-form__footer">
							<p class="contact-form__privacy">
								<?php
								if ( $form_privacy_text ) {
									echo esc_html( $form_privacy_text );
								} else {
									printf(
										/* translators: %s: privacy policy link */
										esc_html__( 'By submitting this form you agree to our %s.', 'pc4s' ),
										'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'pc4s' ) . '</a>'
									);
								}
								?>
							</p>
							<button type="submit" class="btn btn--primary">
								<?php echo esc_html( $form_submit_label ?: __( 'Submit', 'pc4s' ) ); ?>
							</button>
						</div><!-- .contact-form__footer -->

					</form><!-- #contact-form -->

				<?php endif; ?>

			</div><!-- .contact-form-section__form-col -->

		</div><!-- .wrapper.contact-form-section__inner -->
	</section><!-- .contact-form-section -->


<?php
get_footer();
