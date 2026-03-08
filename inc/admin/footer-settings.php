<?php
/**
 * Footer Settings — Admin submenu page
 *
 * Manages editable footer content (tagline, contact info, newsletter text,
 * and the TN Department / funding logo) through a dedicated PC4S admin page.
 *
 * All content is stored in a single option key (`pc4s_footer_settings`) to
 * minimise database round-trips. Templates retrieve values via the static
 * `FooterSettings::get()` helper which caches the option in a static
 * variable, so the DB is read at most once per request.
 *
 * The footer brand logo is managed separately in
 * Appearance → Customize → Footer Branding (theme mod `footer_logo_id`).
 *
 * @package PC4S\Admin
 */

namespace PC4S\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FooterSettings {

	// ─── Constants ──────────────────────────────────────────────────────────

	/**
	 * WordPress options key for all footer settings.
	 */
	const OPTION_KEY = 'pc4s_footer_settings';

	/**
	 * Capability required to edit these settings.
	 */
	const CAPABILITY = 'manage_options';

	// ─── Singleton ──────────────────────────────────────────────────────────

	/** @var FooterSettings|null */
	private static ?FooterSettings $instance = null;

	public static function get_instance(): FooterSettings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// ─── State ──────────────────────────────────────────────────────────────

	/**
	 * Hook suffix returned by add_submenu_page(), stored so the
	 * admin-scripts callback can target only this page.
	 */
	private string $page_hook = '';

	// ─── Boot ───────────────────────────────────────────────────────────────

	private function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Called by Pc4sAdminMenu after add_submenu_page() so we can match
	 * the hook suffix in enqueue_admin_scripts().
	 *
	 * @param string $hook Hook suffix returned by add_submenu_page().
	 */
	public function set_page_hook( string $hook ): void {
		$this->page_hook = $hook;
	}

	// ─── Settings API ───────────────────────────────────────────────────────

	/**
	 * Register the option with the Settings API.
	 * Sanitization is applied on every save via options.php.
	 */
	public function register_settings(): void {
		register_setting(
			'pc4s_footer_settings_group',
			self::OPTION_KEY,
			[
				'sanitize_callback' => [ $this, 'sanitize' ],
			]
		);
	}

	/**
	 * Sanitize all footer settings fields before they are stored.
	 *
	 * @param mixed $input Raw $_POST data submitted by the settings form.
	 * @return array<string, mixed> Sanitized option array.
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		return [
			// Brand column
			'tagline'              => sanitize_textarea_field( $input['tagline'] ?? '' ),
			'address_line1'        => sanitize_text_field( $input['address_line1'] ?? '' ),
			'address_line2'        => sanitize_text_field( $input['address_line2'] ?? '' ),
			'phone'                => sanitize_text_field( $input['phone'] ?? '' ),
			'email'                => sanitize_email( $input['email'] ?? '' ),
			// Newsletter column
			'newsletter_heading'   => sanitize_text_field( $input['newsletter_heading'] ?? '' ),
			'newsletter_text'      => sanitize_textarea_field( $input['newsletter_text'] ?? '' ),
			'newsletter_disclaimer' => wp_kses_post( $input['newsletter_disclaimer'] ?? '' ),
			// Funding / TN Department logo
			'funding_logo_id'      => absint( $input['funding_logo_id'] ?? 0 ),
		];
	}

	// ─── Admin page ─────────────────────────────────────────────────────────

	/**
	 * Enqueue the WordPress media library on this settings page only.
	 * A small inline script wires up the "Upload" button + preview.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_scripts( string $hook_suffix ): void {
		if ( ! $this->page_hook || $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_media();

		// Minimal jQuery snippet — admin only, no frontend impact.
		$js = <<<'JS'
jQuery(function($){
	$('.pc4s-media-upload').on('click',function(e){
		e.preventDefault();
		var $btn=$this=this;
		var frame=wp.media({title:$($btn).data('title')||'Select Image',multiple:false,library:{type:'image'}});
		frame.on('select',function(){
			var a=frame.state().get('selection').first().toJSON();
			$($btn).closest('.pc4s-media-field').find('input[type="hidden"]').val(a.id);
			$($btn).closest('.pc4s-media-field').find('.pc4s-media-preview').html('<img src="'+a.url+'" style="max-width:220px;max-height:110px;display:block;object-fit:contain;margin-block-end:8px;" />');
			$($btn).closest('.pc4s-media-field').find('.pc4s-media-clear').show();
		});
		frame.open();
	});
	$('.pc4s-media-clear').on('click',function(e){
		e.preventDefault();
		$(this).closest('.pc4s-media-field').find('input[type="hidden"]').val('');
		$(this).closest('.pc4s-media-field').find('.pc4s-media-preview').html('');
		$(this).hide();
	});
});
JS;
		wp_add_inline_script( 'jquery', $js );
	}

	/**
	 * Render the admin settings page.
	 * Called by Pc4sAdminMenu as the submenu page callback.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pc4s' ) );
		}

		$opts             = (array) get_option( self::OPTION_KEY, [] );
		$funding_logo_id  = absint( $opts['funding_logo_id'] ?? 0 );
		$funding_logo_url = $funding_logo_id ? wp_get_attachment_image_url( $funding_logo_id, 'medium' ) : '';
		$customizer_url   = admin_url( 'customize.php?autofocus[section]=pc4s_footer_branding' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$just_saved = isset( $_GET['settings-updated'] ) && '1' === (string) $_GET['settings-updated'];
		// phpcs:enable
		?>
		<div class="wrap pc4s-admin-page pc4s-footer-page">

			<header class="pc4s-admin-header">
				<h1 class="pc4s-admin-header__title"><?php esc_html_e( 'Footer Settings', 'pc4s' ); ?></h1>
				<p class="pc4s-admin-header__description">
					<?php esc_html_e( 'Manage tagline, contact info, newsletter text, and the funding logo displayed in the site footer.', 'pc4s' ); ?>
				</p>
			</header>

			<?php if ( $just_saved ) : ?>
			<div class="pc4s-admin-notice pc4s-admin-notice--success" role="status" aria-live="polite">
				<svg class="pc4s-admin-notice__icon" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" focusable="false">
					<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
				</svg>
				<span><?php esc_html_e( 'Footer settings saved successfully.', 'pc4s' ); ?></span>
			</div>
			<?php endif; ?>

			<form method="post" action="options.php" novalidate>

				<?php settings_fields( 'pc4s_footer_settings_group' ); ?>

				<!-- ── Card 1: Brand & Contact ───────────────────────────── -->
				<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'Brand & Contact', 'pc4s' ); ?>">

					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php esc_html_e( 'Brand & Contact', 'pc4s' ); ?></h2>
						</div>
					</header>

					<div class="pc4s-form-card__body">

						<!-- Brand Column section -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Brand Column', 'pc4s' ); ?></h3>

							<p class="pc4s-field-hint" style="margin-block-end:var(--size-400)">
								<?php
								printf(
									wp_kses(
										/* translators: %s: Customizer URL */
										__( 'The <strong>footer logo</strong> is managed in <a href="%s">Appearance → Customize → Footer Branding</a>.', 'pc4s' ),
										[ 'strong' => [], 'a' => [ 'href' => [] ] ]
									),
									esc_url( $customizer_url )
								);
								?>
							</p>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_tagline">
									<?php esc_html_e( 'Tagline', 'pc4s' ); ?>
								</label>
								<textarea
									id="pc4s_footer_tagline"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[tagline]"
									rows="3"
									class="pc4s-field-textarea"
									aria-describedby="pc4s_footer_tagline_hint"
								><?php echo esc_textarea( $opts['tagline'] ?? '' ); ?></textarea>
								<p id="pc4s_footer_tagline_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Short description displayed below the footer logo.', 'pc4s' ); ?>
								</p>
							</div>
						</section>

						<!-- Contact Information section -->
						<section class="pc4s-settings-section">
							<h3 class="pc4s-settings-section__title"><?php esc_html_e( 'Contact Information', 'pc4s' ); ?></h3>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_address_line1">
									<?php esc_html_e( 'Address — Line 1', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_footer_address_line1"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[address_line1]"
									value="<?php echo esc_attr( $opts['address_line1'] ?? '' ); ?>"
									class="pc4s-field-input"
									placeholder="<?php esc_attr_e( 'e.g. 630 Broadmor Blvd., Suite 130', 'pc4s' ); ?>"
								/>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_address_line2">
									<?php esc_html_e( 'Address — Line 2', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_footer_address_line2"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[address_line2]"
									value="<?php echo esc_attr( $opts['address_line2'] ?? '' ); ?>"
									class="pc4s-field-input"
									placeholder="<?php esc_attr_e( 'e.g. Murfreesboro, TN 37129', 'pc4s' ); ?>"
								/>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_phone">
									<?php esc_html_e( 'Phone Number', 'pc4s' ); ?>
								</label>
								<input
									type="tel"
									id="pc4s_footer_phone"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[phone]"
									value="<?php echo esc_attr( $opts['phone'] ?? '' ); ?>"
									class="pc4s-field-input"
									placeholder="615-900-0000"
								/>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_email">
									<?php esc_html_e( 'Email Address', 'pc4s' ); ?>
								</label>
								<input
									type="email"
									id="pc4s_footer_email"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[email]"
									value="<?php echo esc_attr( $opts['email'] ?? '' ); ?>"
									class="pc4s-field-input"
									placeholder="info@example.org"
								/>
							</div>
						</section>

					</div><!-- .pc4s-form-card__body -->

				</article><!-- .pc4s-form-card -->

				<!-- ── Card 2: Newsletter Section ────────────────────────── -->
				<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'Newsletter Section', 'pc4s' ); ?>">

					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php esc_html_e( 'Newsletter Section', 'pc4s' ); ?></h2>
						</div>
					</header>

					<div class="pc4s-form-card__body">
						<section class="pc4s-settings-section">

							<p class="pc4s-field-hint" style="margin-block-end:var(--size-400)">
								<?php
								printf(
									wp_kses(
										__( 'The <strong>subscription form</strong> is rendered via the <code>%s</code> action hook.', 'pc4s' ),
										[ 'strong' => [], 'code' => [] ]
									),
									'pc4s_footer_newsletter_form'
								);
								?>
							</p>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_newsletter_heading">
									<?php esc_html_e( 'Heading', 'pc4s' ); ?>
								</label>
								<input
									type="text"
									id="pc4s_footer_newsletter_heading"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[newsletter_heading]"
									value="<?php echo esc_attr( $opts['newsletter_heading'] ?? '' ); ?>"
									class="pc4s-field-input"
									placeholder="<?php esc_attr_e( 'NEWSLETTER', 'pc4s' ); ?>"
								/>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_newsletter_text">
									<?php esc_html_e( 'Description', 'pc4s' ); ?>
								</label>
								<textarea
									id="pc4s_footer_newsletter_text"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[newsletter_text]"
									rows="3"
									class="pc4s-field-textarea"
								><?php echo esc_textarea( $opts['newsletter_text'] ?? '' ); ?></textarea>
							</div>

							<div class="pc4s-field-group">
								<label class="pc4s-field-label" for="pc4s_footer_newsletter_disclaimer">
									<?php esc_html_e( 'Disclaimer', 'pc4s' ); ?>
								</label>
								<textarea
									id="pc4s_footer_newsletter_disclaimer"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[newsletter_disclaimer]"
									rows="3"
									class="pc4s-field-textarea"
									aria-describedby="pc4s_disclaimer_hint"
								><?php echo esc_textarea( $opts['newsletter_disclaimer'] ?? '' ); ?></textarea>
								<p id="pc4s_disclaimer_hint" class="pc4s-field-hint">
									<?php esc_html_e( 'Basic HTML allowed (e.g. links). Displayed below the subscription form.', 'pc4s' ); ?>
								</p>
							</div>

						</section>
					</div><!-- .pc4s-form-card__body -->

				</article><!-- .pc4s-form-card -->

				<!-- ── Card 3: Funding / TN Dept Logo ────────────────────── -->
				<article class="pc4s-form-card" aria-label="<?php esc_attr_e( 'Funding Logo', 'pc4s' ); ?>">

					<header class="pc4s-form-card__header">
						<div class="pc4s-form-card__title-row">
							<h2 class="pc4s-form-card__title"><?php esc_html_e( 'Funding / TN Department Logo', 'pc4s' ); ?></h2>
						</div>
					</header>

					<div class="pc4s-form-card__body">
						<section class="pc4s-settings-section">

							<p class="pc4s-field-hint" style="margin-block-end:var(--size-400)">
								<?php esc_html_e( 'Displayed in the footer below the navigation columns (e.g. the TN Department funding-statement banner).', 'pc4s' ); ?>
							</p>

							<div class="pc4s-media-field">

								<input
									type="hidden"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[funding_logo_id]"
									value="<?php echo esc_attr( (string) $funding_logo_id ); ?>"
								/>

								<div class="pc4s-media-preview">
									<?php if ( $funding_logo_url ) : ?>
										<img
											src="<?php echo esc_url( $funding_logo_url ); ?>"
											alt=""
										/>
									<?php endif; ?>
								</div>

								<div class="pc4s-media-actions">
									<button
										type="button"
										class="pc4s-btn pc4s-btn--ghost pc4s-media-upload"
										data-title="<?php esc_attr_e( 'Select Funding Logo', 'pc4s' ); ?>"
									>
										<svg aria-hidden="true" focusable="false" viewBox="0 0 20 20" fill="currentColor" style="inline-size:1em;block-size:1em">
											<path fill-rule="evenodd" d="M3 17a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1zM6.293 6.707a1 1 0 0 1 0-1.414l3-3a1 1 0 0 1 1.414 0l3 3a1 1 0 0 1-1.414 1.414L11 5.414V13a1 1 0 1 1-2 0V5.414L7.707 6.707a1 1 0 0 1-1.414 0z" clip-rule="evenodd"/>
										</svg>
										<?php echo $funding_logo_id
											? esc_html__( 'Change Image', 'pc4s' )
											: esc_html__( 'Upload Image', 'pc4s' ); ?>
									</button>

									<button
										type="button"
										class="pc4s-btn pc4s-btn--danger pc4s-media-clear"
										<?php echo $funding_logo_id ? '' : 'style="display:none"'; ?>
									>
										<?php esc_html_e( 'Remove', 'pc4s' ); ?>
									</button>
								</div>

							</div><!-- .pc4s-media-field -->
						</section>
					</div><!-- .pc4s-form-card__body -->

					<footer class="pc4s-form-card__footer">
						<button type="submit" class="pc4s-btn pc4s-btn--primary">
							<?php esc_html_e( 'Save Footer Settings', 'pc4s' ); ?>
						</button>
					</footer>

				</article><!-- .pc4s-form-card -->

			</form>

		</div><!-- .pc4s-footer-page -->
		<?php
	}

	// ─── Template helpers ────────────────────────────────────────────────────

	/**
	 * Retrieve a single footer setting value.
	 *
	 * Results are cached in a static variable so the option is read from the
	 * database at most once per request, regardless of how many times this
	 * method is called.
	 *
	 * @param string $key     Setting key (e.g. 'tagline', 'phone').
	 * @param string $default Fallback value when the key is empty or unset.
	 * @return string
	 */
	public static function get( string $key, string $default = '' ): string {
		static $opts = null;
		if ( null === $opts ) {
			$opts = (array) get_option( self::OPTION_KEY, [] );
		}
		$value = $opts[ $key ] ?? '';
		return '' !== $value ? $value : $default;
	}

	/**
	 * Return the funding / TN Department logo <img> HTML, or an empty
	 * string if none is configured.
	 *
	 * @return string Escaped HTML or empty string.
	 */
	public static function get_funding_logo_html(): string {
		$id = (int) self::get( 'funding_logo_id', '0' );
		if ( ! $id ) {
			return '';
		}

		$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		if ( ! $alt ) {
			$alt = __( 'Funding statement and partner logos', 'pc4s' );
		}

		return wp_get_attachment_image(
			$id,
			'full',
			false,
			[
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => $alt,
			]
		);
	}
}
