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
 *   4. Events           — upcoming True Blue Peers 4 Success events
 *   5. More Ways (CTA)  — badge, heading, subtitle, action links
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

use PC4S\Classes\EventQuery;

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

// --- Events Section ---
$events_badge    = (string) get_field( 'dp_events_badge' );
$events_heading  = (string) get_field( 'dp_events_heading' );
$events_subtitle = (string) get_field( 'dp_events_subtitle' );
$events_count    = max( 1, (int) get_field( 'dp_events_count' ) ?: 4 );
$events_view_all = (string) get_field( 'dp_events_view_all_url' );

// --- CTA Section ---
$cta_badge     = (string) get_field( 'dp_cta_badge' );
$cta_heading   = (string) get_field( 'dp_cta_heading' );
$cta_subtitle  = (string) get_field( 'dp_cta_subtitle' );
$cta_primary   = get_field( 'dp_cta_primary' );   // link field (array|null)
$cta_secondary = get_field( 'dp_cta_secondary' ); // link field (array|null)

// ---------------------------------------------------------------------------
// Events Query — True Blue Peers 4 Success, executed once, cached locally.
// ---------------------------------------------------------------------------
$events_query   = null;
$has_events     = false;

if ( $events_heading ) {
	$events_query = EventQuery::get_upcoming( $events_count, [ 'true-blue' ] );
	$has_events   = $events_query->have_posts();
	if ( ! $has_events ) {
		wp_reset_postdata();
	}
}

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
										<?php esc_html_e( 'First Name', 'pc4s' ); ?>
										<abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr>
									</label>
									<input
										type="text"
										id="donor-first-name"
										name="first_name"
										class="donate-form__input"
										autocomplete="given-name"
										required
										aria-required="true"
										placeholder="<?php esc_attr_e( 'First Name', 'pc4s' ); ?>"
									/>
								</div>
								<div class="donate-form__group">
									<label for="donor-last-name" class="donate-form__label">
										<?php esc_html_e( 'Last Name', 'pc4s' ); ?>
									</label>
									<input
										type="text"
										id="donor-last-name"
										name="last_name"
										class="donate-form__input"
										autocomplete="family-name"
										required
										aria-required="true"
										placeholder="<?php esc_attr_e( 'Last Name', 'pc4s' ); ?>"
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
									<?php esc_html_e( 'Company Name', 'pc4s' ); ?>
									<abbr title="<?php esc_attr_e( 'required when visible', 'pc4s' ); ?>" aria-hidden="true">*</abbr>
								</label>
								<input
									type="text"
									id="donor-company-name"
									name="company_name"
									class="donate-form__input"
									autocomplete="organization"
									placeholder="<?php esc_attr_e( 'Your company name', 'pc4s' ); ?>"
								/>
							</div>

							<!-- Email -->
							<div class="donate-form__group">
								<label for="donor-email" class="donate-form__label">
									<?php esc_html_e( 'Email Address', 'pc4s' ); ?>
									<abbr title="<?php esc_attr_e( 'required', 'pc4s' ); ?>" aria-hidden="true">*</abbr>
								</label>
								<input
									type="email"
									id="donor-email"
									name="email"
									class="donate-form__input"
									autocomplete="email"
									required
									aria-required="true"
									placeholder="<?php esc_attr_e( 'Email Address', 'pc4s' ); ?>"
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
	   SECTION 4: True Blue Peers 4 Success Events
	   ================================================================ */ ?>
	<?php if ( $events_heading && $has_events ) : ?>
	<section
		class="section events-preview donate-events"
		id="donate-events"
		aria-labelledby="donate-events-heading"
	>
		<div class="wrapper">
			<div class="events-header">
				<div class="section__header section__header--start">

					<?php if ( $events_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $events_badge ); ?></span>
					</div>
					<?php endif; ?>

					<h2 id="donate-events-heading" class="section__title">
						<?php echo esc_html( $events_heading ); ?>
					</h2>

					<?php if ( $events_subtitle ) : ?>
					<p class="section__subtitle"><?php echo wp_kses_post( $events_subtitle ); ?></p>
					<?php endif; ?>

				</div><!-- .section__header -->

				<?php if ( $events_view_all ) : ?>
				<a
					href="<?php echo esc_url( $events_view_all ); ?>"
					class="view-all-link"
					aria-label="<?php esc_attr_e( 'View all upcoming events', 'pc4s' ); ?>"
				>
					<?php esc_html_e( 'View All Events', 'pc4s' ); ?>
					<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="5" y1="12" x2="19" y2="12"></line>
						<polyline points="12 5 19 12 12 19"></polyline>
					</svg>
				</a>
				<?php endif; ?>

			</div><!-- .events-header -->
		</div><!-- .wrapper -->

		<div class="wrapper">
			<div
				class="events-slider"
				role="region"
				aria-label="<?php esc_attr_e( 'Upcoming True Blue events slider', 'pc4s' ); ?>"
				aria-roledescription="carousel"
			>

				<button
					class="events-nav events-nav--prev"
					type="button"
					aria-label="<?php esc_attr_e( 'Previous event', 'pc4s' ); ?>"
					aria-controls="donate-events-track"
				>
					<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="15 18 9 12 15 6"></polyline>
					</svg>
				</button>

				<ol class="events-track" id="donate-events-track" aria-live="polite">
					<?php
					$i            = 0;
					$total_slides = $events_query->post_count;

					while ( $events_query->have_posts() ) :
						$events_query->the_post();
						$i++;

						$ev_id          = (int) get_the_ID();
						$ev_date        = (string) get_field( 'event_date' );
						$ev_start       = (string) get_field( 'event_start_time' );
						$ev_end         = (string) get_field( 'event_end_time' );
						$ev_location    = (string) get_field( 'event_location' );
						$ev_cta_url     = (string) ( get_field( 'event_cta_url' ) ?: get_permalink() );
						$ev_cta_text    = (string) ( get_field( 'event_cta_text' ) ?: __( 'Find Out More', 'pc4s' ) );
						$ev_ts          = $ev_date ? strtotime( $ev_date ) : 0;
						$ev_day         = $ev_ts ? gmdate( 'j', $ev_ts ) : '';
						$ev_month       = $ev_ts ? strtoupper( gmdate( 'M', $ev_ts ) ) : '';
						$ev_dt_start    = $ev_ts && $ev_start ? esc_attr( $ev_date . 'T' . $ev_start ) : '';
						$ev_dt_end      = $ev_ts && $ev_end   ? esc_attr( $ev_date . 'T' . $ev_end )   : '';

						$slide_label = sprintf(
							/* translators: 1: slide number, 2: total, 3: event title */
							esc_attr__( 'Event %1$d of %2$d: %3$s', 'pc4s' ),
							$i,
							$total_slides,
							get_the_title()
						);
					?>
					<li
						class="event-slide"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( $slide_label ); ?>"
					>

						<div class="event-slide-panel" aria-hidden="true">
							<?php if ( $ev_day && $ev_month ) : ?>
							<div class="event-badge">
								<span class="event-badge-day"><?php echo esc_html( $ev_day ); ?></span>
								<span class="event-badge-month"><?php echo esc_html( $ev_month ); ?></span>
							</div>
							<?php endif; ?>
						</div>

						<div class="event-slide-content">
							<h3 class="event-slide__title">
								<a href="<?php echo esc_url( $ev_cta_url ); ?>">
									<?php the_title(); ?>
								</a>
							</h3>

							<?php if ( $ev_ts ) : ?>
							<p class="event-slide__meta">
								<time
									class="event-slide__date"
									<?php if ( $ev_dt_start ) : ?>datetime="<?php echo esc_attr( $ev_dt_start ); ?>"<?php endif; ?>
								>
									<?php
									$display = $ev_month . ' ' . $ev_day;
									if ( $ev_start ) {
										$display .= ' @ ' . gmdate( 'g:i a', strtotime( $ev_start ) );
									}
									echo esc_html( $display );
									?>
								</time>

								<?php if ( $ev_location ) : ?>
								<span class="event-slide__sep" aria-hidden="true">&mdash;</span>
								<span class="event-slide__location"><?php echo esc_html( $ev_location ); ?></span>
								<?php endif; ?>
							</p>
							<?php endif; ?>

							<a
								href="<?php echo esc_url( $ev_cta_url ); ?>"
								class="event-slide__cta btn btn--outline"
							>
								<?php echo esc_html( $ev_cta_text ); ?>
							</a>
						</div><!-- .event-slide-content -->

					</li><!-- .event-slide -->
					<?php endwhile; wp_reset_postdata(); ?>
				</ol><!-- .events-track -->

				<button
					class="events-nav events-nav--next"
					type="button"
					aria-label="<?php esc_attr_e( 'Next event', 'pc4s' ); ?>"
					aria-controls="donate-events-track"
				>
					<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="9 18 15 12 9 6"></polyline>
					</svg>
				</button>

			</div><!-- .events-slider -->
		</div><!-- .wrapper -->

	</section><!-- .donate-events -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 5: More Ways to Support (CTA)
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
