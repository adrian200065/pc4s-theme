<?php
/**
 * Template Name: License Plate
 * Template Post Type: page
 *
 * Page template for the Tennessee "Youth Mental Health Matters" specialty
 * license plate pre-order campaign.
 *
 * Sections rendered (in order):
 *   1. Page Banner   — parts/content/page-banner.php
 *   2. Hero          — intro, price, CTA buttons, plate image (ACF)
 *   3. Progress      — campaign progress bar (ACF: current / goal counts)
 *   4. How It Works  — numbered step list (ACF repeater)
 *   5. Pre-Order     — multi-field form → admin-post.php → PayPal redirect
 *   6. Page CTA      — bottom call-to-action (ACF)
 *
 * ACF field group: group_license_plate_page
 *   (acf-json/group_license_plate_page.json)
 *
 * PayPal integration: reads the license-plate hosted button ID and sandbox
 * flag from
 *   PC4S\Admin\SettingsPage (PC4S → Settings in wp-admin).
 *
 * Form entry storage: PC4S\Classes\Custom_Forms ('license_plate' form ID).
 *   Successful submission saves the entry to the DB, then redirects to PayPal.
 *   Validation failure redirects back to this page with ?pc4s_form=error.
 *
 * @package PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Admin\SettingsPage;
use PC4S\Classes\Custom_Forms;

// ---------------------------------------------------------------------------
// Resolve all ACF fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Hero ---
$hero_badge_text   = (string) get_field( 'lp_hero_badge_text' );
$hero_title        = (string) get_field( 'lp_hero_title' );
$hero_lead         = (string) get_field( 'lp_hero_lead' );
$hero_price_label  = (string) get_field( 'lp_hero_price_label' );
$hero_price_amount = (string) get_field( 'lp_hero_price_amount' );
$hero_plate_image  = get_field( 'lp_hero_plate_image' );   // array: url, alt, width, height

// --- Campaign Progress ---
$progress_badge_text = (string) get_field( 'lp_progress_badge_text' );
$progress_title      = (string) get_field( 'lp_progress_title' );
$progress_subtitle   = (string) get_field( 'lp_progress_subtitle' );
$progress_current    = absint( get_field( 'lp_progress_current' ) );
$progress_goal       = absint( get_field( 'lp_progress_goal' ) ) ?: 1000;
$progress_note       = (string) get_field( 'lp_progress_note' );

// Derived: percentage (0–100, capped at 100).
$progress_pct = $progress_goal > 0
	? min( 100, round( ( $progress_current / $progress_goal ) * 100 ) )
	: 0;

// --- How It Works ---
$how_badge_text = (string) get_field( 'lp_how_badge_text' );
$how_title      = (string) get_field( 'lp_how_title' );
$how_subtitle   = (string) get_field( 'lp_how_subtitle' );
$how_steps      = (array)  get_field( 'lp_how_steps' );    // repeater: step_title, step_desc

// --- Pre-Order Form ---
$form_badge_text   = (string) get_field( 'lp_form_badge_text' );
$form_title        = (string) get_field( 'lp_form_title' );
$form_subtitle     = (string) get_field( 'lp_form_subtitle' );
$form_card_heading = (string) get_field( 'lp_form_card_heading' );
$form_card_note    = (string) get_field( 'lp_form_card_note' );
$form_submit_label = (string) get_field( 'lp_form_submit_label' );

// --- Page CTA ---
$cta_badge_text   = (string) get_field( 'lp_cta_badge_text' );
$cta_title        = (string) get_field( 'lp_cta_title' );
$cta_subtitle     = (string) get_field( 'lp_cta_subtitle' );
$cta_primary_link = get_field( 'lp_cta_primary_link' );   // array: url, title, target
$cta_outline_link = get_field( 'lp_cta_outline_link' );   // array: url, title, target

// ---------------------------------------------------------------------------
// PayPal settings (read from PC4S Settings admin page).
// ---------------------------------------------------------------------------
$paypal_button_id = SettingsPage::get_paypal_button_id( 'license_plate' );
$paypal_sandbox   = SettingsPage::is_enabled( 'paypal_sandbox' );
$paypal_base      = $paypal_sandbox
	? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
	: 'https://www.paypal.com/cgi-bin/webscr';
$paypal_has_config = ! empty( $paypal_button_id );

// ---------------------------------------------------------------------------
// Form feedback — query-string params set by Custom_Forms::handle_submission().
// ---------------------------------------------------------------------------
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$qs_status  = isset( $_GET['pc4s_form'] ) ? sanitize_key( $_GET['pc4s_form'] ) : '';
$qs_form_id = isset( $_GET['form_id'] )   ? sanitize_key( $_GET['form_id'] )   : '';
// phpcs:enable

$lp_success = ( 'success' === $qs_status && 'license_plate' === $qs_form_id );
$lp_error   = ( 'error'   === $qs_status && 'license_plate' === $qs_form_id );

/**
 * The redirect target passed to admin-post.php is always the *current* page so
 * that validation errors can be re-displayed here.
 * On success the submission handler overrides this redirect and sends the user
 * straight to PayPal (bypassing the ?pc4s_form=success query string entirely).
 *
 * If PayPal is not yet configured, the handler falls back to the standard
 * ?pc4s_form=success redirect, which is caught by $lp_success above.
 */
$form_redirect  = esc_url_raw( home_url( add_query_arg( [] ) ) );
$form_redirect  = remove_query_arg( [ 'pc4s_form', 'form_id' ], $form_redirect );

// Load form-field definitions (merged with any admin-saved overrides).
$_lp_fields = Custom_Forms::get_form( 'license_plate' )['fields'] ?? [];

// ---------------------------------------------------------------------------
// County list — all 95 Tennessee counties (static data).
// ---------------------------------------------------------------------------
$tn_counties = [
	'anderson'    => 'Anderson',
	'bedford'     => 'Bedford',
	'benton'      => 'Benton',
	'blount'      => 'Blount',
	'bradley'     => 'Bradley',
	'campbell'    => 'Campbell',
	'cannon'      => 'Cannon',
	'carroll'     => 'Carroll',
	'carter'      => 'Carter',
	'cheatham'    => 'Cheatham',
	'chester'     => 'Chester',
	'claiborne'   => 'Claiborne',
	'clay'        => 'Clay',
	'cocke'       => 'Cocke',
	'coffee'      => 'Coffee',
	'crockett'    => 'Crockett',
	'cumberland'  => 'Cumberland',
	'davidson'    => 'Davidson',
	'decatur'     => 'Decatur',
	'dekalb'      => 'DeKalb',
	'dickson'     => 'Dickson',
	'dyer'        => 'Dyer',
	'fayette'     => 'Fayette',
	'fentress'    => 'Fentress',
	'franklin'    => 'Franklin',
	'gibson'      => 'Gibson',
	'giles'       => 'Giles',
	'grainger'    => 'Grainger',
	'greene'      => 'Greene',
	'grundy'      => 'Grundy',
	'hamblen'     => 'Hamblen',
	'hamilton'    => 'Hamilton',
	'hancock'     => 'Hancock',
	'hardeman'    => 'Hardeman',
	'hardin'      => 'Hardin',
	'hawkins'     => 'Hawkins',
	'haywood'     => 'Haywood',
	'henderson'   => 'Henderson',
	'henry'       => 'Henry',
	'hickman'     => 'Hickman',
	'houston'     => 'Houston',
	'humphreys'   => 'Humphreys',
	'jackson'     => 'Jackson',
	'jefferson'   => 'Jefferson',
	'johnson'     => 'Johnson',
	'knox'        => 'Knox',
	'lake'        => 'Lake',
	'lauderdale'  => 'Lauderdale',
	'lawrence'    => 'Lawrence',
	'lewis'       => 'Lewis',
	'lincoln'     => 'Lincoln',
	'loudon'      => 'Loudon',
	'mcminn'      => 'McMinn',
	'mcnairy'     => 'McNairy',
	'macon'       => 'Macon',
	'madison'     => 'Madison',
	'marion'      => 'Marion',
	'marshall'    => 'Marshall',
	'maury'       => 'Maury',
	'meigs'       => 'Meigs',
	'monroe'      => 'Monroe',
	'montgomery'  => 'Montgomery',
	'moore'       => 'Moore',
	'morgan'      => 'Morgan',
	'obion'       => 'Obion',
	'overton'     => 'Overton',
	'perry'       => 'Perry',
	'pickett'     => 'Pickett',
	'polk'        => 'Polk',
	'putnam'      => 'Putnam',
	'rhea'        => 'Rhea',
	'roane'       => 'Roane',
	'robertson'   => 'Robertson',
	'rutherford'  => 'Rutherford',
	'scott'       => 'Scott',
	'sequatchie'  => 'Sequatchie',
	'sevier'      => 'Sevier',
	'shelby'      => 'Shelby',
	'smith'       => 'Smith',
	'stewart'     => 'Stewart',
	'sullivan'    => 'Sullivan',
	'sumner'      => 'Sumner',
	'tipton'      => 'Tipton',
	'trousdale'   => 'Trousdale',
	'unicoi'      => 'Unicoi',
	'union'       => 'Union',
	'van-buren'   => 'Van Buren',
	'warren'      => 'Warren',
	'washington'  => 'Washington',
	'wayne'       => 'Wayne',
	'weakley'     => 'Weakley',
	'white'       => 'White',
	'williamson'  => 'Williamson',
	'wilson'      => 'Wilson',
];

// US state options shown in the form (limited to nearby + general).
$us_states = [
	'TN' => 'Tennessee',
	'AL' => 'Alabama',
	'AR' => 'Arkansas',
	'GA' => 'Georgia',
	'KY' => 'Kentucky',
	'MS' => 'Mississippi',
	'MO' => 'Missouri',
	'NC' => 'North Carolina',
	'VA' => 'Virginia',
];

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main-content">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION: Hero / Intro
	   ================================================================ */ ?>
	<?php if ( $hero_title ) : ?>
	<section class="section lp-hero" aria-labelledby="lp-hero-heading">
		<div class="wrapper">
			<div class="lp-hero__inner">

				<!-- Text Content -->
				<div class="lp-hero__content">

					<?php if ( $hero_badge_text ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $hero_badge_text ); ?></span>
					</div>
					<?php endif; ?>

					<h2 id="lp-hero-heading" class="section__title">
						<?php echo esc_html( $hero_title ); ?>
					</h2>

					<?php if ( $hero_lead ) : ?>
						<p class="lp-hero__lead"><?php echo wp_kses_post( $hero_lead ); ?></p>
					<?php endif; ?>

					<?php if ( $hero_price_amount ) : ?>
					<p class="lp-hero__price">
						<?php if ( $hero_price_label ) : ?>
							<span class="lp-hero__price-label"><?php echo esc_html( $hero_price_label ); ?></span>
						<?php endif; ?>
						<strong class="lp-hero__price-amount"><?php echo esc_html( $hero_price_amount ); ?></strong>
					</p>
					<?php endif; ?>

<div class="lp-hero__actions">
					<a href="#preorder-form" class="btn btn--primary"><?php esc_html_e( 'Pre-Order Now', 'pc4s' ); ?></a>
					<a href="#how-it-works" class="btn btn--outline"><?php esc_html_e( 'How It Works', 'pc4s' ); ?></a>
				</div><!-- .lp-hero__actions -->

				</div><!-- .lp-hero__content -->

				<!-- Plate Image -->
				<?php if ( ! empty( $hero_plate_image['url'] ) ) : ?>
				<div class="lp-hero__visual">
					<div class="lp-hero__plate-wrapper">
						<img
							src="<?php echo esc_url( $hero_plate_image['url'] ); ?>"
							alt="<?php echo esc_attr( $hero_plate_image['alt'] ?? '' ); ?>"
							class="lp-hero__plate-img"
							<?php if ( ! empty( $hero_plate_image['width'] ) ) : ?>
								width="<?php echo absint( $hero_plate_image['width'] ); ?>"
							<?php endif; ?>
							<?php if ( ! empty( $hero_plate_image['height'] ) ) : ?>
								height="<?php echo absint( $hero_plate_image['height'] ); ?>"
							<?php endif; ?>
							loading="eager"
							decoding="async"
						/>
					</div>
				</div><!-- .lp-hero__visual -->
				<?php endif; ?>

			</div><!-- .lp-hero__inner -->
		</div><!-- .wrapper -->
	</section><!-- .lp-hero -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: Campaign Progress
	   ================================================================ */ ?>
	<?php if ( $progress_title ) : ?>
	<section class="section lp-progress" aria-labelledby="lp-progress-heading">
		<div class="wrapper">

			<div class="section__header">

				<?php if ( $progress_badge_text ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $progress_badge_text ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="lp-progress-heading" class="section__title">
					<?php echo esc_html( $progress_title ); ?>
				</h2>

				<?php if ( $progress_subtitle ) : ?>
					<p class="section__subtitle"><?php echo esc_html( $progress_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->

			<div class="lp-progress__card">
				<div class="lp-progress__stats">
					<div
						class="lp-progress__numbers"
						aria-label="<?php
							/* translators: 1: current orders 2: goal orders */
							echo esc_attr( sprintf( __( 'Campaign progress: %1$s of %2$s pre-orders', 'pc4s' ), number_format( $progress_current ), number_format( $progress_goal ) ) );
						?>"
					>
						<span class="lp-progress__current" aria-hidden="true"><?php echo esc_html( number_format( $progress_current ) ); ?></span>
						<span class="lp-progress__separator" aria-hidden="true">/</span>
						<span class="lp-progress__goal" aria-hidden="true"><?php echo esc_html( number_format( $progress_goal ) ); ?></span>
					</div>
					<div class="lp-progress__bar-wrap">
						<span class="lp-progress__label"><?php esc_html_e( 'pre-orders received', 'pc4s' ); ?></span>
						<progress
							class="lp-progress__track"
							value="<?php echo esc_attr( $progress_current ); ?>"
							max="<?php echo esc_attr( $progress_goal ); ?>"
							aria-label="<?php
								echo esc_attr( sprintf(
									/* translators: 1: current 2: goal */
									__( '%1$s out of %2$s pre-orders received', 'pc4s' ),
									number_format( $progress_current ),
									number_format( $progress_goal )
								) );
							?>"
						>
							<?php
							echo esc_html( sprintf(
								/* translators: 1: current 2: goal */
								__( '%1$s of %2$s pre-orders', 'pc4s' ),
								number_format( $progress_current ),
								number_format( $progress_goal )
							) );
							?>
						</progress>
					</div>
				</div><!-- .lp-progress__stats -->

				<?php if ( $progress_note ) : ?>
					<p class="lp-progress__note"><?php echo esc_html( $progress_note ); ?></p>
				<?php endif; ?>
			</div><!-- .lp-progress__card -->

		</div><!-- .wrapper -->
	</section><!-- .lp-progress -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: How It Works
	   ================================================================ */ ?>
	<?php if ( $how_title ) : ?>
	<section class="section lp-how" id="how-it-works" aria-labelledby="lp-how-heading">
		<div class="wrapper">

			<div class="section__header">

				<?php if ( $how_badge_text ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $how_badge_text ); ?></span>
				</div>
				<?php endif; ?>

				<h2 id="lp-how-heading" class="section__title">
					<?php echo esc_html( $how_title ); ?>
				</h2>

				<?php if ( $how_subtitle ) : ?>
					<p class="section__subtitle"><?php echo esc_html( $how_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->

			<?php if ( ! empty( $how_steps ) ) : ?>
			<ol class="lp-steps" aria-label="<?php esc_attr_e( 'Steps to pre-order your license plate', 'pc4s' ); ?>">
				<?php foreach ( $how_steps as $step ) :
					$step_title = sanitize_text_field( $step['step_title'] ?? '' );
					$step_desc  = sanitize_text_field( $step['step_desc']  ?? '' );
					if ( ! $step_title ) {
						continue;
					}
				?>
				<li class="lp-steps__item">
					<div class="lp-steps__number" aria-hidden="true"></div>
					<h3 class="lp-steps__title"><?php echo esc_html( $step_title ); ?></h3>
					<?php if ( $step_desc ) : ?>
						<p class="lp-steps__desc"><?php echo esc_html( $step_desc ); ?></p>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ol><!-- .lp-steps -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .lp-how -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION: Pre-Order Form
	   ================================================================ */ ?>
	<section class="section lp-preorder" id="preorder-form" aria-labelledby="lp-form-heading">
		<div class="wrapper">

			<div class="section__header">

				<?php if ( $form_badge_text ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $form_badge_text ); ?></span>
				</div>
				<?php endif; ?>

				<?php $form_heading_text = $form_title ?: __( 'Pre-Order Your License Plate', 'pc4s' ); ?>
				<h2 id="lp-form-heading" class="section__title">
					<?php echo esc_html( $form_heading_text ); ?>
				</h2>

				<?php if ( $form_subtitle ) : ?>
					<p class="section__subtitle"><?php echo esc_html( $form_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->

			<div class="lp-form-card">

				<?php if ( $form_card_heading ) : ?>
					<h3 class="lp-form-card__heading"><?php echo esc_html( $form_card_heading ); ?></h3>
				<?php endif; ?>

				<?php if ( $form_card_note ) : ?>
					<p class="lp-form-card__note"><?php echo esc_html( $form_card_note ); ?></p>
				<?php endif; ?>

				<?php
				// ── Form feedback ─────────────────────────────────────────────────
				if ( $lp_success ) : ?>
					<div class="form-message form-message--success" role="status">
						<p><?php esc_html_e( 'Your pre-order has been submitted! You have been redirected — if PayPal did not open, please try again.', 'pc4s' ); ?></p>
					</div>
				<?php elseif ( $lp_error ) : ?>
					<div class="form-message form-message--error" role="alert">
						<p><?php esc_html_e( 'Please fill in all required fields before submitting.', 'pc4s' ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( ! $paypal_has_config ) : ?>
				<div class="form-message form-message--warning" role="alert">
					<p>
						<?php esc_html_e( 'Online payment is not yet configured.', 'pc4s' ); ?>
						<?php if ( current_user_can( 'pc4s_manage' ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pc4s-settings' ) ); ?>">
								<?php esc_html_e( 'Configure PayPal in PC4S Settings →', 'pc4s' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
				<?php endif; ?>

				<?php if ( ! $lp_success ) : ?>
				<form
					class="lp-form"
					id="license-plate-form"
					method="post"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					novalidate
					aria-label="<?php esc_attr_e( 'License plate pre-order form', 'pc4s' ); ?>"
				>
					<?php wp_nonce_field( 'pc4s_form_license_plate', 'pc4s_form_nonce' ); ?>
					<input type="hidden" name="action"      value="pc4s_form_submit" />
					<input type="hidden" name="form_id"     value="license_plate" />
					<input type="hidden" name="source_page" value="<?php echo esc_attr( home_url( add_query_arg( [] ) ) ); ?>" />
					<input type="hidden" name="_redirect"   value="<?php echo esc_attr( $form_redirect ); ?>" />

					<!-- Name Row -->
					<div class="lp-form__row">
						<div class="lp-form__group">
							<label for="lp-first-name" class="lp-form__label">
							<?php echo esc_html( $_lp_fields['first_name']['label'] ); ?>
							<?php if ( ! empty( $_lp_fields['first_name']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<input
							type="text"
							id="lp-first-name"
							name="first_name"
							class="lp-form__input"
							autocomplete="given-name"
							<?php echo ! empty( $_lp_fields['first_name']['required'] ) ? 'required aria-required="true"' : ''; ?>
							aria-describedby="lp-first-name-hint"
							placeholder="<?php echo esc_attr( $_lp_fields['first_name']['placeholder'] ?? '' ); ?>"
							/>
							<span id="lp-first-name-hint" class="lp-form__hint">
								<?php esc_html_e( 'Enter your legal first name.', 'pc4s' ); ?>
							</span>
						</div>
						<div class="lp-form__group">
							<label for="lp-last-name" class="lp-form__label">
							<?php echo esc_html( $_lp_fields['last_name']['label'] ); ?>
							<?php if ( ! empty( $_lp_fields['last_name']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<input
							type="text"
							id="lp-last-name"
							name="last_name"
							class="lp-form__input"
							autocomplete="family-name"
							<?php echo ! empty( $_lp_fields['last_name']['required'] ) ? 'required aria-required="true"' : ''; ?>
							aria-describedby="lp-last-name-hint"
							placeholder="<?php echo esc_attr( $_lp_fields['last_name']['placeholder'] ?? '' ); ?>"
							/>
							<span id="lp-last-name-hint" class="lp-form__hint">
								<?php esc_html_e( 'Enter your legal last name.', 'pc4s' ); ?>
							</span>
						</div>
					</div><!-- .lp-form__row -->

					<!-- Street Address -->
					<div class="lp-form__group">
						<label for="lp-street-address" class="lp-form__label">
						<?php echo esc_html( $_lp_fields['street_address']['label'] ); ?>
						<?php if ( ! empty( $_lp_fields['street_address']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
					</label>
					<input
						type="text"
						id="lp-street-address"
						name="street_address"
						class="lp-form__input"
						autocomplete="street-address"
						<?php echo ! empty( $_lp_fields['street_address']['required'] ) ? 'required aria-required="true"' : ''; ?>
						aria-describedby="lp-street-hint"
						placeholder="<?php echo esc_attr( $_lp_fields['street_address']['placeholder'] ?? '' ); ?>"
						/>
						<span id="lp-street-hint" class="lp-form__hint">
							<?php esc_html_e( 'Include apartment or unit number if applicable.', 'pc4s' ); ?>
						</span>
					</div><!-- .lp-form__group -->

					<!-- City / State Row -->
					<div class="lp-form__row">
						<div class="lp-form__group">
							<label for="lp-city" class="lp-form__label">
							<?php echo esc_html( $_lp_fields['city']['label'] ); ?>
							<?php if ( ! empty( $_lp_fields['city']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<input
							type="text"
							id="lp-city"
							name="city"
							class="lp-form__input"
							autocomplete="address-level2"
							<?php echo ! empty( $_lp_fields['city']['required'] ) ? 'required aria-required="true"' : ''; ?>
							aria-describedby="lp-city-hint"
							placeholder="<?php echo esc_attr( $_lp_fields['city']['placeholder'] ?? '' ); ?>"
							/>
							<span id="lp-city-hint" class="lp-form__hint">
								<?php esc_html_e( 'Enter your city.', 'pc4s' ); ?>
							</span>
						</div>
						<div class="lp-form__group">
							<label for="lp-state" class="lp-form__label">
							<?php echo esc_html( $_lp_fields['state']['label'] ); ?>
							<?php if ( ! empty( $_lp_fields['state']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<select
							id="lp-state"
							name="state"
							class="lp-form__select"
							autocomplete="address-level1"
							<?php echo ! empty( $_lp_fields['state']['required'] ) ? 'required aria-required="true"' : ''; ?>
								aria-describedby="lp-state-hint"
							>
								<?php foreach ( $us_states as $abbr => $label ) : ?>
									<option value="<?php echo esc_attr( $abbr ); ?>"<?php selected( 'TN', $abbr ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
								<option value="other"><?php esc_html_e( 'Other', 'pc4s' ); ?></option>
							</select>
							<span id="lp-state-hint" class="lp-form__hint">
								<?php esc_html_e( 'Defaulted to Tennessee.', 'pc4s' ); ?>
							</span>
						</div>
					</div><!-- .lp-form__row -->

					<!-- Zip / County Row -->
					<div class="lp-form__row">
						<div class="lp-form__group">
							<label for="lp-zip-code" class="lp-form__label">
							<?php echo esc_html( $_lp_fields['zip_code']['label'] ); ?>
							<?php if ( ! empty( $_lp_fields['zip_code']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<input
							type="text"
							id="lp-zip-code"
							name="zip_code"
							class="lp-form__input"
							autocomplete="postal-code"
							<?php echo ! empty( $_lp_fields['zip_code']['required'] ) ? 'required aria-required="true"' : ''; ?>
							aria-describedby="lp-zip-hint"
							placeholder="<?php echo esc_attr( $_lp_fields['zip_code']['placeholder'] ?? '' ); ?>"
								pattern="\d{5}(-\d{4})?"
								inputmode="numeric"
							/>
							<span id="lp-zip-hint" class="lp-form__hint">
								<?php esc_html_e( '5 digits (or ZIP+4, e.g., 37129–1234).', 'pc4s' ); ?>
							</span>
						</div>
						<div class="lp-form__group">
							<label for="lp-county" class="lp-form__label">
							<?php echo esc_html( $_lp_fields['county']['label'] ); ?>
							<?php if ( ! empty( $_lp_fields['county']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<select
							id="lp-county"
							name="county"
							class="lp-form__select"
							<?php echo ! empty( $_lp_fields['county']['required'] ) ? 'required aria-required="true"' : ''; ?>
								aria-describedby="lp-county-hint"
							>
								<option value="" disabled selected><?php esc_html_e( 'Select your Tennessee county', 'pc4s' ); ?></option>
								<?php foreach ( $tn_counties as $val => $label ) : ?>
									<option value="<?php echo esc_attr( $val ); ?>"<?php selected( 'rutherford', $val ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span id="lp-county-hint" class="lp-form__hint">
								<?php esc_html_e( 'Select your Tennessee county to complete your pre-order.', 'pc4s' ); ?>
							</span>
						</div>
					</div><!-- .lp-form__row -->

					<!-- Email Address -->
					<div class="lp-form__group">
						<label for="lp-email" class="lp-form__label">
						<?php echo esc_html( $_lp_fields['email']['label'] ); ?>
						<?php if ( ! empty( $_lp_fields['email']['required'] ) ) : ?><span class="lp-form__required" aria-hidden="true">*</span><?php endif; ?>
					</label>
					<input
						type="email"
						id="lp-email"
						name="email"
						class="lp-form__input"
						autocomplete="email"
						<?php echo ! empty( $_lp_fields['email']['required'] ) ? 'required aria-required="true"' : ''; ?>
						aria-describedby="lp-email-hint"
						placeholder="<?php echo esc_attr( $_lp_fields['email']['placeholder'] ?? '' ); ?>"
							inputmode="email"
						/>
						<span id="lp-email-hint" class="lp-form__hint">
							<?php esc_html_e( "We'll send your confirmation and updates here.", 'pc4s' ); ?>
						</span>
					</div><!-- .lp-form__group -->

					<!-- Privacy Notice -->
					<p class="lp-form__privacy">
						<?php
						printf(
							/* translators: %s: privacy policy link */
							esc_html__( 'By submitting, you agree to our handling of your information per our %s.', 'pc4s' ),
							'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'pc4s' ) . '</a>'
						);
						?>
					</p>

					<!-- Submit -->
					<button type="submit" class="btn btn--primary lp-form__submit">
						<?php echo esc_html( $form_submit_label ?: __( 'Submit and Pay', 'pc4s' ) ); ?>
					</button>

				</form><!-- #license-plate-form -->
				<?php endif; ?>

			</div><!-- .lp-form-card -->

		</div><!-- .wrapper -->
	</section><!-- .lp-preorder -->


	<?php /* ================================================================
	   SECTION: Page CTA
	   ================================================================ */ ?>
	<?php if ( $cta_title ) : ?>
	<section class="section lp-cta page-cta" aria-labelledby="lp-cta-heading">
		<div class="wrapper">
			<div class="text-center">

				<?php if ( $cta_badge_text ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $cta_badge_text ); ?></span>
				</div>
				<?php endif; ?>

				<div class="page-cta__copy">
					<h2 id="lp-cta-heading" class="page-cta__title">
						<?php echo esc_html( $cta_title ); ?>
					</h2>
					<?php if ( $cta_subtitle ) : ?>
						<p class="page-cta__text"><?php echo esc_html( $cta_subtitle ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $cta_primary_link || $cta_outline_link ) : ?>
				<div class="page-cta__actions">

					<?php if ( ! empty( $cta_primary_link['url'] ) ) : ?>
						<a
							href="<?php echo esc_url( $cta_primary_link['url'] ); ?>"
							class="btn btn--primary"
							<?php if ( ! empty( $cta_primary_link['target'] ) ) : ?>
								target="<?php echo esc_attr( $cta_primary_link['target'] ); ?>"
								rel="noopener noreferrer"
							<?php endif; ?>
						>
							<?php echo esc_html( $cta_primary_link['title'] ); ?>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $cta_outline_link['url'] ) ) : ?>
						<a
							href="<?php echo esc_url( $cta_outline_link['url'] ); ?>"
							class="btn btn--outline"
							<?php if ( ! empty( $cta_outline_link['target'] ) ) : ?>
								target="<?php echo esc_attr( $cta_outline_link['target'] ); ?>"
								rel="noopener noreferrer"
							<?php endif; ?>
						>
							<?php echo esc_html( $cta_outline_link['title'] ); ?>
						</a>
					<?php endif; ?>

				</div><!-- .page-cta__actions -->
				<?php endif; ?>

			</div><!-- .text-center -->
		</div><!-- .wrapper -->
	</section><!-- .lp-cta -->
	<?php endif; ?>

</main><!-- #main-content -->

<?php
get_footer();
