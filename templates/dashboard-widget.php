<?php
/**
 * Custom Dashboard Widget Template
 *
 * Renders the PC4S Admin Center dashboard widget: a grouped set of
 * quick-action cards, a quick-links bar, and a contact-support form wired
 * to the dashboard_support form via the PC4S Custom_Forms system.
 *
 * Available variables (set by Dashboard::get_widget_data()):
 *   @var array  $data {
 *     @type string              $form_status   'success' | 'error' | ''
 *     @type string              $redirect_url  URL to return to after POST
 *     @type string              $support_email Site admin e-mail address
 *     @type array<string,string> $urls          Named admin URLs
 *   }
 *
 * @package Pc4s
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$_urls          = $data['urls']          ?? [];
$_form_status   = $data['form_status']   ?? '';
$_redirect_url  = $data['redirect_url']  ?? admin_url( 'index.php' );
$_support_email = $data['support_email'] ?? '';
?>
<div class="pc4s-dash-wrap">

	<!-- ── Hero ─────────────────────────────────────────────────────────── -->
	<header class="pc4s-dash-hero">
		<p class="pc4s-dash-hero__eyebrow"><?php esc_html_e( 'PC4S Admin Center', PC4S_TEXTDOMAIN ); ?></p>
		<h2 class="pc4s-dash-hero__title"><?php esc_html_e( 'Manage Your Site', PC4S_TEXTDOMAIN ); ?></h2>
		<p class="pc4s-dash-hero__subtitle">
			<?php esc_html_e( 'Quick access to core content, people, and site tools for the coalition.', PC4S_TEXTDOMAIN ); ?>
		</p>
	</header>

	<!-- ── Content & Pages ───────────────────────────────────────────── -->
	<section aria-labelledby="pc4s-dash-content-heading">
		<h3 id="pc4s-dash-content-heading" class="pc4s-section-heading">
			<?php esc_html_e( 'Content &amp; Pages', PC4S_TEXTDOMAIN ); ?>
		</h3>
		<div class="pc4s-dash-form-row">

			<a href="<?php echo esc_url( $_urls['new_page'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-edit-page" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Add New Page', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Create a new page for programs or resources.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['edit_pages'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-admin-page" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Edit Pages', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Update existing site pages and content.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['new_post'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-megaphone" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Publish News', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Share announcements and coalition updates.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['media'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-admin-media" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Media Library', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Manage images and downloadable files.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

		</div>
	</section>

	<!-- ── Programs & People ─────────────────────────────────────────── -->
	<section aria-labelledby="pc4s-dash-programs-heading">
		<h3 id="pc4s-dash-programs-heading" class="pc4s-section-heading">
			<?php esc_html_e( 'Programs &amp; People', PC4S_TEXTDOMAIN ); ?>
		</h3>
		<div class="pc4s-dash-form-row">

			<a href="<?php echo esc_url( $_urls['new_event'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-calendar-alt" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Add Event', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Schedule coalition events and meetings.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['events'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-calendar" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Manage Events', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Review upcoming and past events.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['new_staff'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-admin-users" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Add Staff', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Create new staff profiles and bios.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['staff'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-groups" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Manage Staff', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Update staff listings and roles.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

		</div>
	</section>

	<!-- ── Engagement & Admin ─────────────────────────────────────────── -->
	<section aria-labelledby="pc4s-dash-admin-heading">
		<h3 id="pc4s-dash-admin-heading" class="pc4s-section-heading">
			<?php esc_html_e( 'Engagement &amp; Admin', PC4S_TEXTDOMAIN ); ?>
		</h3>
		<div class="pc4s-dash-form-row">

			<a href="<?php echo esc_url( $_urls['form_entries'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-feedback" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Form Entries', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Review contact form submissions.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['forms'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-email-alt" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Newsletter &amp; Forms', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Manage form recipients and newsletter signups.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['settings'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'PC4S Settings', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Update branding, contact info, and SMTP.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

			<a href="<?php echo esc_url( $_urls['menus'] ?? '#' ); ?>" class="pc4s-quick-link-card">
				<span class="pc4s-quick-link-card__icon dashicons dashicons-menu" aria-hidden="true"></span>
				<strong class="pc4s-quick-link-card__title"><?php esc_html_e( 'Menus', PC4S_TEXTDOMAIN ); ?></strong>
				<span class="pc4s-quick-link-card__desc"><?php esc_html_e( 'Edit navigation and footer links.', PC4S_TEXTDOMAIN ); ?></span>
			</a>

		</div>
	</section>

	<!-- ── Quick links bar ───────────────────────────────────────────── -->
	<div class="pc4s-dash-quickbar">
		<span class="pc4s-dash-quickbar__label"><?php esc_html_e( 'Quick Links', PC4S_TEXTDOMAIN ); ?></span>
		<div class="pc4s-dash-quickbar__buttons">
			<a href="<?php echo esc_url( $_urls['view_site'] ?? '#' ); ?>"
			   class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm"
			   target="_blank"
			   rel="noopener noreferrer">
				<?php esc_html_e( 'View Site', PC4S_TEXTDOMAIN ); ?>
			</a>
			<a href="<?php echo esc_url( $_urls['customizer'] ?? '#' ); ?>"
			   class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm">
				<?php esc_html_e( 'Customize Theme', PC4S_TEXTDOMAIN ); ?>
			</a>
			<a href="<?php echo esc_url( $_urls['site_health'] ?? '#' ); ?>"
			   class="pc4s-btn pc4s-btn--ghost pc4s-btn--sm">
				<?php esc_html_e( 'Site Health', PC4S_TEXTDOMAIN ); ?>
			</a>
		</div>
	</div>

	<!-- ── Contact Support ────────────────────────────────────────────── -->
	<div class="pc4s-dash-support">

		<?php if ( $_support_email ) : ?>
		<p class="pc4s-dash-support__note">
			<?php
			printf(
				/* translators: %s: admin support email address */
				esc_html__( 'Need help managing the PC4S website? %s', PC4S_TEXTDOMAIN ),
				'<a href="mailto:' . esc_attr( $_support_email ) . '">' . esc_html( $_support_email ) . '</a>'
			);
			?>
		</p>
		<?php endif; ?>

		<p class="pc4s-dash-support__heading"><?php esc_html_e( 'Contact Support', PC4S_TEXTDOMAIN ); ?></p>

		<?php if ( 'success' === $_form_status ) : ?>
		<div class="pc4s-dash-form-notice pc4s-dash-form-notice--success" role="status" aria-live="polite">
			<?php esc_html_e( "Thank you! We'll get back to you shortly.", PC4S_TEXTDOMAIN ); ?>
		</div>
		<?php elseif ( 'error' === $_form_status ) : ?>
		<div class="pc4s-dash-form-notice pc4s-dash-form-notice--error" role="alert">
			<?php esc_html_e( 'Please fill in all required fields before submitting.', PC4S_TEXTDOMAIN ); ?>
		</div>
		<?php endif; ?>

		<?php if ( 'success' !== $_form_status ) : ?>
		<form
			class="pc4s-dash-support-form"
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			novalidate
		>
			<?php wp_nonce_field( 'pc4s_form_dashboard_support', 'pc4s_form_nonce' ); ?>
			<input type="hidden" name="action"      value="pc4s_form_submit" />
			<input type="hidden" name="form_id"     value="dashboard_support" />
			<input type="hidden" name="source_page" value="<?php echo esc_attr( admin_url( 'index.php' ) ); ?>" />
			<input type="hidden" name="_redirect"   value="<?php echo esc_attr( $_redirect_url ); ?>" />

			<?php /* Honeypot — bots fill every field; real users never see this. */ ?>
			<div style="position:absolute;inset-inline-start:-9999px;inset-block-start:auto;inline-size:1px;block-size:1px;overflow:hidden;" aria-hidden="true">
				<label for="dash-support-hp"><?php esc_html_e( 'Website', PC4S_TEXTDOMAIN ); ?></label>
				<input type="text" id="dash-support-hp" name="pc4s_hp_website" tabindex="-1" autocomplete="off" value="" />
			</div>

			<div class="pc4s-dash-form-row">
				<div class="pc4s-field-group">
					<label class="pc4s-field-label" for="dash-support-name">
						<?php esc_html_e( 'Name', PC4S_TEXTDOMAIN ); ?>
						<span class="pc4s-field-required" aria-hidden="true">*</span>
					</label>
					<input
						class="pc4s-field-input"
						id="dash-support-name"
						name="name"
						type="text"
						autocomplete="name"
						required
						aria-required="true"
						placeholder="<?php esc_attr_e( 'Your name', PC4S_TEXTDOMAIN ); ?>"
					/>
				</div>
				<div class="pc4s-field-group">
					<label class="pc4s-field-label" for="dash-support-email">
						<?php esc_html_e( 'Email', PC4S_TEXTDOMAIN ); ?>
						<span class="pc4s-field-required" aria-hidden="true">*</span>
					</label>
					<input
						class="pc4s-field-input"
						id="dash-support-email"
						name="email"
						type="email"
						autocomplete="email"
						required
						aria-required="true"
						placeholder="<?php esc_attr_e( 'Your email', PC4S_TEXTDOMAIN ); ?>"
					/>
				</div>
			</div>

			<div class="pc4s-field-group">
				<label class="pc4s-field-label" for="dash-support-subject">
					<?php esc_html_e( 'Subject', PC4S_TEXTDOMAIN ); ?>
					<span class="pc4s-field-required" aria-hidden="true">*</span>
				</label>
				<input
					class="pc4s-field-input"
					id="dash-support-subject"
					name="subject"
					type="text"
					required
					aria-required="true"
					placeholder="<?php esc_attr_e( 'Subject', PC4S_TEXTDOMAIN ); ?>"
				/>
			</div>

			<div class="pc4s-field-group">
				<label class="pc4s-field-label" for="dash-support-message">
					<?php esc_html_e( 'Message', PC4S_TEXTDOMAIN ); ?>
					<span class="pc4s-field-required" aria-hidden="true">*</span>
				</label>
				<textarea
					class="pc4s-field-textarea"
					id="dash-support-message"
					name="message"
					rows="5"
					required
					aria-required="true"
					placeholder="<?php esc_attr_e( 'Your message', PC4S_TEXTDOMAIN ); ?>"
				></textarea>
			</div>

			<button type="submit" class="pc4s-btn pc4s-btn--primary">
				<?php esc_html_e( 'Send', PC4S_TEXTDOMAIN ); ?>
			</button>

		</form>
		<?php endif; ?>

	</div><!-- /.pc4s-dash-support -->

</div><!-- /.pc4s-dash-wrap -->
