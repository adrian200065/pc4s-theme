<?php
/**
 * Template Name: Donate
 * Template Post Type: page
 *
 * Page template for the Donate page.
 *
 * Sections rendered (in order):
 *   1. Page Banner      — parts/content/page-banner.php
 *   2. Why Give         — badge, heading, body, CTA
 *   3. Donation Form    — preset amounts, personal info, trust sidebar
 *   4. More Ways (CTA)  — badge, heading, subtitle, action links
 *
 * ACF field group: group_donate_page (acf-json/group_donate_page.json)
 *   – Manages all editable content. No hardcoded strings in markup.
 *
 * Donation flow:
 *   1. User selects preset or enters custom amount (JS populates hidden `amount` input).
 *   2. Form submits → admin-post.php → Custom_Forms::handle_submission().
 *   3. Handler stores entry, emails admin, then redirects to PayPal with amount.
 *   Configure PayPal Hosted Button ID at PC4S → Settings.
 *
 * Form ID: donate  (registered in PC4S\Classes\Custom_Forms)
 *   Nonce action: pc4s_form_donate
 *   Success: ?pc4s_form=success&form_id=donate
 *   Error:   ?pc4s_form=error&form_id=donate
 *
 * Events: True Blue Peers 4 Success — queried via EventQuery::get_upcoming().
 *
 * @package PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Classes\Custom_Forms;

// ---------------------------------------------------------------------------
// Resolve all ACF fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Why Give Section ---
$why_badge    = (string) get_field( 'dp_why_badge' );
$why_title    = (string) get_field( 'dp_why_title' );
$why_body     = (string) get_field( 'dp_why_body' );   // wysiwyg
$why_cta      = (string) get_field( 'dp_why_cta_label' );

// --- Donation Form Section ---
$form_badge        = (string) get_field( 'dp_form_badge' );
$form_heading      = (string) get_field( 'dp_form_heading' );
$form_subtitle     = (string) get_field( 'dp_form_subtitle' );
$amount_presets    = (array)  get_field( 'dp_amount_presets' ) ?: [];
$default_preset    = (int)    get_field( 'dp_default_preset_amount' );
$personal_heading  = (string) get_field( 'dp_personal_heading' );
$company_question  = (string) get_field( 'dp_company_question' );
$total_label       = (string) get_field( 'dp_total_label' );
$submit_label      = (string) get_field( 'dp_submit_label' );
$privacy_text      = (string) get_field( 'dp_privacy_text' );

// --- Trust Sidebar ---
$trust_heading = (string) get_field( 'dp_trust_heading' );
$trust_items   = (array)  get_field( 'dp_trust_items' ) ?: [];

// --- CTA Section ---
$cta_badge     = (string) get_field( 'dp_cta_badge' );
$cta_heading   = (string) get_field( 'dp_cta_heading' );
$cta_subtitle  = (string) get_field( 'dp_cta_subtitle' );
$cta_primary   = get_field( 'dp_cta_primary' );   // link field (array|null)
$cta_secondary = get_field( 'dp_cta_secondary' ); // link field (array|null)

// ---------------------------------------------------------------------------
// Form feedback — set by Custom_Forms::handle_submission() via query string.
// ---------------------------------------------------------------------------
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$qs_status  = isset( $_GET['pc4s_form'] ) ? sanitize_key( $_GET['pc4s_form'] ) : '';
$qs_form_id = isset( $_GET['form_id'] )   ? sanitize_key( $_GET['form_id'] )   : '';
// phpcs:enable

$dn_success = ( 'success' === $qs_status && 'donate' === $qs_form_id );
$dn_error   = ( 'error'   === $qs_status && 'donate' === $qs_form_id );

$form_redirect = esc_url_raw( home_url( add_query_arg( [] ) ) );
$form_redirect = remove_query_arg( [ 'pc4s_form', 'form_id' ], $form_redirect );

// Load form-field definitions (merged with any admin-saved overrides).
$_dn_fields = Custom_Forms::get_form( 'donate' )['fields'] ?? [];

// ---------------------------------------------------------------------------
// Safe inline SVG helper — check-mark icon used in the trust sidebar.
// ---------------------------------------------------------------------------
$check_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>';

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main-content">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION 2: Why Give — badge, heading, wysiwyg body, CTA
	   ================================================================ */ ?>
	<?php if ( $why_title || $why_body ) : ?>
	<section class="section donate-why" aria-labelledby="donate-why-heading">
		<div class="wrapper">
			<div class="donate-why__inner">

				<div class="donate-why__content">

					<?php if ( $why_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $why_badge ); ?></span>
					</div>
					<?php endif; ?>

					<?php if ( $why_title ) : ?>
					<h2 id="donate-why-heading" class="section__title">
						<?php echo esc_html( $why_title ); ?>
					</h2>
					<?php endif; ?>

					<?php if ( $why_body ) : ?>
					<div class="donate-why__body">
						<?php echo wp_kses_post( $why_body ); ?>
					</div>
					<?php endif; ?>

					<?php if ( $why_cta ) : ?>
					<a href="#donation-form" class="btn btn--primary">
						<?php echo esc_html( $why_cta ); ?>
					</a>
					<?php endif; ?>

				</div><!-- .donate-why__content -->

			</div><!-- .donate-why__inner -->
		</div><!-- .wrapper -->
	</section><!-- .donate-why -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 3: Donation Form — presets, personal info, trust sidebar
	   ================================================================ */ ?>
	<section
		class="section donate-form-section"
		id="donation-form"
		aria-labelledby="donate-form-heading"
	>
		<div class="wrapper">

			<?php if ( $form_heading || $form_badge || $form_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $form_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $form_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $form_heading ) : ?>
				<h2 id="donate-form-heading" class="section__title">
					<?php echo esc_html( $form_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $form_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $form_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<div class="donate-form-section__inner">

				<!-- Donation Form Card -->
				<div class="donate-form-card">

					<?php if ( $dn_success ) : ?>

						<div class="form-message form-message--success" role="status">
							<p><?php esc_html_e( 'Thank you for your generosity! Redirecting you to PayPal to complete your donation&hellip;', 'pc4s' ); ?></p>
						</div>

					<?php else : ?>

						<?php if ( $dn_error ) : ?>
						<div class="form-message form-message--error" role="alert">
							<p><?php esc_html_e( 'Please fill in all required fields and select or enter a donation amount.', 'pc4s' ); ?></p>
						</div>
						<?php endif; ?>

						<?php /* --- Amount Presets (JS-driven; JS sets the hidden `amount` input) --- */ ?>
						<?php if ( ! empty( $amount_presets ) ) : ?>
						<div
							role="group"
							aria-labelledby="donate-amount-preset-label"
						>
							<p id="donate-amount-preset-label" class="donate-section-heading">
								<?php esc_html_e( 'Select an Amount', 'pc4s' ); ?>
							</p>
							<div class="donate-amounts">
								<?php
								foreach ( $amount_presets as $preset ) :
									$preset_val = (int) ( $preset['dp_preset_amount'] ?? 0 );
									if ( $preset_val < 1 ) {
										continue;
									}
									$is_default = ( $preset_val === $default_preset );
								?>
								<button
									type="button"
									class="donate-amounts__btn<?php echo $is_default ? ' is-selected' : ''; ?>"
									data-amount="<?php echo esc_attr( (string) $preset_val ); ?>"
									aria-pressed="<?php echo $is_default ? 'true' : 'false'; ?>"
								>
									$<?php echo esc_html( number_format( $preset_val ) ); ?>
								</button>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>

						<!-- Custom amount -->
						<div class="donate-custom">
							<label for="donate-custom-amount" class="donate-custom__label">
								<?php esc_html_e( 'Custom Amount', 'pc4s' ); ?>
							</label>
							<div class="donate-custom__input-wrap">
								<span class="donate-custom__symbol" aria-hidden="true">$</span>
								<input
									type="number"
									id="donate-custom-amount"
									name="custom_amount_display"
									class="donate-custom__input js-donate-custom-amount"
									min="1"
									step="1"
									placeholder="10.00"
									aria-label="<?php esc_attr_e( 'Enter a custom donation amount in dollars', 'pc4s' ); ?>"
									inputmode="decimal"
								/>
							</div>
						</div><!-- .donate-custom -->

						<hr class="donate-divider" />

						<!-- Personal Info Form -->
						<form
							class="donate-form"
							id="donate-personal-form"
							method="post"
							action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							novalidate
							aria-label="<?php esc_attr_e( 'Donation personal information form', 'pc4s' ); ?>"
						>
							<?php wp_nonce_field( 'pc4s_form_donate', 'pc4s_form_nonce' ); ?>
							<input type="hidden" name="action"      value="pc4s_form_submit" />
							<input type="hidden" name="form_id"     value="donate" />
							<input type="hidden" name="source_page" value="<?php echo esc_attr( home_url( add_query_arg( [] ) ) ); ?>" />
							<input type="hidden" name="_redirect"   value="<?php echo esc_attr( $form_redirect ); ?>" />
							<!-- Amount is populated by JS when user selects/types. -->
							<input
								type="hidden"
								id="donate-amount-hidden"
								name="amount"
								value="<?php echo esc_attr( $default_preset > 0 ? (string) $default_preset : '' ); ?>"
								aria-label="<?php esc_attr_e( 'Donation amount', 'pc4s' ); ?>"
							/>

							<?php if ( $personal_heading ) : ?>
							<h3 class="donate-section-heading"><?php echo esc_html( $personal_heading ); ?></h3>
							<?php endif; ?>

							<!-- Name row -->
							<div class="donate-form__row">
								<div class="donate-form__group">
									<label for="donor-first-name" class="donate-form__label">
											<?php echo esc_html( $_dn_fields['first_name']['label'] ); ?>
											<?php if ( ! empty( $_dn_fields['first_name']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
										</label>
										<input
											type="text"
											id="donor-first-name"
											name="first_name"
											class="donate-form__input"
											autocomplete="given-name"
											<?php echo ! empty( $_dn_fields['first_name']['required'] ) ? 'required aria-required="true"' : ''; ?>
											placeholder="<?php echo esc_attr( $_dn_fields['first_name']['placeholder'] ?? '' ); ?>"
									/>
								</div>
								<div class="donate-form__group">
									<label for="donor-last-name" class="donate-form__label">
											<?php echo esc_html( $_dn_fields['last_name']['label'] ); ?>
											<?php if ( ! empty( $_dn_fields['last_name']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
										</label>
										<input
											type="text"
											id="donor-last-name"
											name="last_name"
											class="donate-form__input"
											autocomplete="family-name"
											<?php echo ! empty( $_dn_fields['last_name']['required'] ) ? 'required aria-required="true"' : ''; ?>
											placeholder="<?php echo esc_attr( $_dn_fields['last_name']['placeholder'] ?? '' ); ?>"
									/>
								</div>
							</div><!-- .donate-form__row -->

							<!-- Company? -->
							<fieldset class="donate-form__radio-group">
								<legend class="donate-form__radio-legend">
									<?php echo esc_html( $company_question ?: __( 'Is this donation on behalf of a company?', 'pc4s' ) ); ?>
								</legend>
								<div class="donate-form__radio-options">
									<label class="donate-form__radio-label">
										<input
											type="radio"
											name="on_behalf_of_company"
											value="no"
											class="donate-form__radio"
											checked
											aria-controls="donate-company-group"
										/>
										<?php esc_html_e( 'No', 'pc4s' ); ?>
									</label>
									<label class="donate-form__radio-label">
										<input
											type="radio"
											name="on_behalf_of_company"
											value="yes"
											class="donate-form__radio"
											aria-controls="donate-company-group"
										/>
										<?php esc_html_e( 'Yes', 'pc4s' ); ?>
									</label>
								</div>
							</fieldset>

							<!-- Company name (shown when Yes) -->
							<div
								class="donate-form__group donate-form__group--company js-company-group"
								id="donate-company-group"
								aria-hidden="true"
							>
								<label for="donor-company-name" class="donate-form__label">
									<?php echo esc_html( $_dn_fields['company_name']['label'] ); ?>
									<abbr title="<?php esc_attr_e( 'required when visible', 'pc4s' ); ?>" aria-hidden="true">*</abbr>
								</label>
								<input
									type="text"
									id="donor-company-name"
									name="company_name"
									class="donate-form__input"
									autocomplete="organization"
									placeholder="<?php echo esc_attr( $_dn_fields['company_name']['placeholder'] ?? '' ); ?>"
								/>
							</div>

							<!-- Email -->
							<div class="donate-form__group">
								<label for="donor-email" class="donate-form__label">
									<?php echo esc_html( $_dn_fields['email']['label'] ); ?>
									<?php if ( ! empty( $_dn_fields['email']['required'] ) ) : ?><abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr><?php endif; ?>
								</label>
								<input
									type="email"
									id="donor-email"
									name="email"
									class="donate-form__input"
									autocomplete="email"
									<?php echo ! empty( $_dn_fields['email']['required'] ) ? 'required aria-required="true"' : ''; ?>
									placeholder="<?php echo esc_attr( $_dn_fields['email']['placeholder'] ?? '' ); ?>"
									inputmode="email"
								/>
							</div>

							<!-- Donation total display (updated by JS) -->
							<div class="donate-total" aria-live="polite" aria-atomic="true">
								<span class="donate-total__label">
									<?php echo esc_html( $total_label ?: __( 'Donation Total:', 'pc4s' ) ); ?>
								</span>
								<span class="donate-total__amount js-donate-total-display" id="donate-total-display">
									<?php echo esc_html( $default_preset > 0 ? '$' . number_format( $default_preset ) . '.00' : '$0.00' ); ?>
								</span>
							</div>

							<!-- Submit -->
							<button type="submit" class="btn btn--primary donate-form__submit">
								<?php echo esc_html( $submit_label ?: __( 'Donate Now', 'pc4s' ) ); ?>
							</button>

							<!-- Privacy note -->
							<p class="donate-form__privacy">
								<?php
								if ( $privacy_text ) {
									echo wp_kses_post( $privacy_text );
								} else {
									printf(
										/* translators: %s: privacy policy link */
										wp_kses_post( __( 'By donating, you agree to our %s. PC4S is a 501(c)(3) non-profit organization. Your donation may be tax-deductible.', 'pc4s' ) ),
										'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'pc4s' ) . '</a>'
									);
								}
								?>
							</p>

						</form><!-- #donate-personal-form -->

					<?php endif; // success/error ?>

				</div><!-- .donate-form-card -->

				<!-- Trust Sidebar -->
				<?php if ( $trust_heading || ! empty( $trust_items ) ) : ?>
				<aside
					class="donate-trust"
					aria-label="<?php esc_attr_e( 'Why donate to PC4S', 'pc4s' ); ?>"
				>
					<div class="donate-trust__card donate-trust__card--dark">

						<?php if ( $trust_heading ) : ?>
						<h3 class="donate-trust__heading"><?php echo esc_html( $trust_heading ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $trust_items ) ) : ?>
						<ul class="donate-trust__list" role="list">
							<?php foreach ( $trust_items as $item ) :
								$trust_text = (string) ( $item['dp_trust_text'] ?? '' );
								if ( ! $trust_text ) {
									continue;
								}
							?>
							<li class="donate-trust__item">
								<span class="donate-trust__icon" aria-hidden="true">
									<?php echo $check_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — hardcoded SVG ?>
								</span>
								<p><?php echo esc_html( $trust_text ); ?></p>
							</li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>

					</div><!-- .donate-trust__card -->
				</aside><!-- .donate-trust -->
				<?php endif; ?>

			</div><!-- .donate-form-section__inner -->

		</div><!-- .wrapper -->
	</section><!-- .donate-form-section -->


	<?php /* ================================================================
	   SECTION 4: More Ways to Support (CTA)
	   ================================================================ */ ?>
	<?php if ( $cta_heading ) : ?>
	<section class="section page-cta donate-cta" aria-labelledby="donate-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="donate-cta-heading" class="section__title">
					<?php echo esc_html( $cta_heading ); ?>
				</h2>

				<?php if ( $cta_subtitle ) : ?>
				<p class="section__subtitle"><?php echo wp_kses_post( $cta_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $cta_primary || $cta_secondary ) : ?>
				<div class="page-cta__actions">
					<?php if ( ! empty( $cta_primary['url'] ) ) : ?>
					<a
						href="<?php echo esc_url( $cta_primary['url'] ); ?>"
						class="btn btn--primary"
						<?php if ( ! empty( $cta_primary['target'] ) ) : ?>
							target="<?php echo esc_attr( $cta_primary['target'] ); ?>"
							rel="noopener noreferrer"
						<?php endif; ?>
					>
						<?php echo esc_html( $cta_primary['title'] ?? __( 'Get Involved', 'pc4s' ) ); ?>
					</a>
					<?php endif; ?>
					<?php if ( ! empty( $cta_secondary['url'] ) ) : ?>
					<a
						href="<?php echo esc_url( $cta_secondary['url'] ); ?>"
						class="btn btn--outline"
						<?php if ( ! empty( $cta_secondary['target'] ) ) : ?>
							target="<?php echo esc_attr( $cta_secondary['target'] ); ?>"
							rel="noopener noreferrer"
						<?php endif; ?>
					>
						<?php echo esc_html( $cta_secondary['title'] ?? __( 'Contact Us', 'pc4s' ) ); ?>
					</a>
					<?php endif; ?>
				</div><!-- .page-cta__actions -->
				<?php endif; ?>

			</div><!-- .section__header -->
		</div><!-- .wrapper -->
	</section><!-- .donate-cta -->
	<?php endif; ?>

</main><!-- #main-content -->

<?php
get_footer();
