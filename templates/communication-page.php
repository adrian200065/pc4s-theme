<?php
/**
 * Template Name: Communication
 * Template Post Type: page
 *
 * Page template for the Communication page.
 *
 * Sections rendered (in order):
 *   1. Page Banner  — parts/content/page-banner.php
 *   2. Hero Strip   — badge, heading, lead + social navigation links
 *   3. Facebook Feed + Sidebar — embedded Facebook Page Plugin + channel cards
 *   4. Page CTA     — badge, heading, subtitle + two action buttons
 *
 * Facebook embed configuration is read from PC4S → Settings (SettingsPage):
 *   facebook_page_url, facebook_page_id, facebook_feed_limit.
 * Update these values at PC4S → Settings in the WP admin.
 *
 * Contact details (email, phone) are sourced from PC4S → Footer Settings
 * (FooterSettings). They are shared with the footer and Contact Us page.
 *
 * ACF field group: group_communication_page (acf-json/group_communication_page.json)
 *   — Field prefix: copp_
 *   — Location: Page Template == communication-page.php
 *
 * The Facebook JavaScript SDK is enqueued into wp_footer only when a Facebook
 * Page URL has been configured in Settings. No SDK is loaded otherwise.
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Admin\FooterSettings;
use PC4S\Admin\SettingsPage;

// ---------------------------------------------------------------------------
// Resolve all fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Hero Section (ACF) ---
$hero_badge     = (string) get_field( 'copp_hero_badge' );
$hero_heading   = (string) get_field( 'copp_hero_heading' );
$hero_lead      = (string) get_field( 'copp_hero_lead' );
$hero_fb_label  = (string) get_field( 'copp_hero_fb_label' ) ?: __( 'Facebook', 'pc4s' );
$hero_msg_label = (string) get_field( 'copp_hero_msg_label' ) ?: __( 'Message Us', 'pc4s' );
$hero_msg_url   = (string) get_field( 'copp_hero_msg_url' );

// --- Feed Section (ACF) ---
$feed_label         = (string) get_field( 'copp_feed_label' ) ?: __( 'PC4S on Facebook', 'pc4s' );
$feed_sidebar_title = (string) get_field( 'copp_feed_sidebar_title' ) ?: __( 'More Ways to Connect', 'pc4s' );
$feed_fb_handle     = (string) get_field( 'copp_feed_fb_handle' );

// --- Page CTA (ACF) ---
$cta_badge     = (string) get_field( 'copp_cta_badge' );
$cta_heading   = (string) get_field( 'copp_cta_heading' );
$cta_subtitle  = (string) get_field( 'copp_cta_subtitle' );
$cta_primary   = get_field( 'copp_cta_primary' );   // link field (array|null)
$cta_secondary = get_field( 'copp_cta_secondary' ); // link field (array|null)

// --- Contact Details — sourced from PC4S → Footer Settings (shared with footer). ---
$contact_email = FooterSettings::get( 'email' );
$contact_phone = FooterSettings::get( 'phone' );

// --- Facebook configuration — sourced from PC4S → Settings. ---
$facebook_page_url   = SettingsPage::get( 'facebook_page_url' );
$facebook_feed_limit = max( 1, (int) SettingsPage::get( 'facebook_feed_limit', '10' ) );

// ---------------------------------------------------------------------------
// Enqueue the Facebook JavaScript SDK into wp_footer only when configured.
// The SDK enables the fb-page Page Plugin (embedded timeline).
// ---------------------------------------------------------------------------
if ( $facebook_page_url ) {
	add_action(
		'wp_footer',
		static function (): void {
			echo '<div id="fb-root"></div>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0"></script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		},
		20
	);
}

// ---------------------------------------------------------------------------
// Inline SVG helper — returns a hardcoded safe SVG by icon key.
// ---------------------------------------------------------------------------
$copp_icon = static function ( string $type ): string {
	$icons = [
		'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
		'email'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
		'phone'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
	];
	return $icons[ $type ] ?? '';
};

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<?php /* ====================================================================
   SECTION: Comm Hero — badge, heading, lead + social navigation
   ==================================================================== */ ?>
<?php if ( $hero_heading ) : ?>
<section class="section comm-hero" aria-labelledby="comm-hero-heading">
	<div class="wrapper comm-hero__inner">

		<!-- Left: Copy -->
		<div class="comm-hero__copy">

			<?php if ( $hero_badge ) : ?>
			<span class="comm-hero__badge"><?php echo esc_html( $hero_badge ); ?></span>
			<?php endif; ?>

			<h2 id="comm-hero-heading" class="comm-hero__title">
				<?php echo esc_html( $hero_heading ); ?>
			</h2>

			<?php if ( $hero_lead ) : ?>
			<p class="comm-hero__lead"><?php echo esc_html( $hero_lead ); ?></p>
			<?php endif; ?>

		</div><!-- .comm-hero__copy -->

		<!-- Right: Social links -->
		<nav class="comm-hero__social-pill" aria-label="<?php esc_attr_e( 'PC4S social media profiles', 'pc4s' ); ?>">

			<?php if ( $facebook_page_url ) : ?>
			<a
				href="<?php echo esc_url( $facebook_page_url ); ?>"
				class="comm-hero__social-link"
				target="_blank"
				rel="noopener noreferrer"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %s: link label */ __( 'PC4S on %s (opens in new tab)', 'pc4s' ), $hero_fb_label ) ); ?>"
			>
				<?php echo $copp_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( $hero_fb_label ); ?>
			</a>
			<?php endif; ?>

			<?php
			$msg_href = $hero_msg_url ?: get_permalink( get_page_by_path( 'contact-us' ) );
			if ( $msg_href ) :
			?>
			<a
				href="<?php echo esc_url( $msg_href ); ?>"
				class="comm-hero__social-link"
				aria-label="<?php esc_attr_e( 'Send PC4S a message', 'pc4s' ); ?>"
			>
				<?php echo $copp_icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( $hero_msg_label ); ?>
			</a>
			<?php endif; ?>

		</nav><!-- .comm-hero__social-pill -->

	</div><!-- .wrapper -->
</section><!-- .comm-hero -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Facebook Feed + Sidebar
   ==================================================================== */ ?>
<section class="section comm-feed" aria-labelledby="comm-feed-heading">
	<div class="wrapper comm-feed__inner">

		<!-- Facebook Embed Column -->
		<div class="comm-feed__embed-col">

			<p class="comm-feed__embed-label" id="comm-feed-heading">
				<?php echo $copp_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( $feed_label ); ?>
			</p>

			<div class="comm-feed__embed-frame" aria-label="<?php esc_attr_e( 'PC4S Facebook page feed', 'pc4s' ); ?>" role="region">

				<?php if ( $facebook_page_url ) : ?>
				<!-- Facebook Page Plugin — powered by the JS SDK loaded in wp_footer. -->
				<div
					class="fb-page"
					data-href="<?php echo esc_url( $facebook_page_url ); ?>"
					data-tabs="timeline"
					data-width="500"
					data-height="700"
					data-small-header="false"
					data-adapt-container-width="true"
					data-hide-cover="false"
					data-show-facepile="true"
				>
					<!-- Fallback shown when FB SDK is unavailable or JS is disabled. -->
					<div class="comm-feed__embed-fallback" role="status">
						<?php echo $copp_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<p><?php esc_html_e( 'Unable to load the Facebook feed. Visit us directly on Facebook.', 'pc4s' ); ?></p>
						<a
							href="<?php echo esc_url( $facebook_page_url ); ?>"
							class="btn btn--primary"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php esc_html_e( 'Visit Our Facebook Page', 'pc4s' ); ?>
						</a>
					</div>
				</div>
				<?php else : ?>
				<!-- No Facebook URL configured — show admin prompt. -->
				<div class="comm-feed__embed-fallback" role="status">
					<?php echo $copp_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<p><?php esc_html_e( 'Facebook feed not configured. Add your Facebook Page URL in PC4S → Settings.', 'pc4s' ); ?></p>
				</div>
				<?php endif; ?>

			</div><!-- .comm-feed__embed-frame -->

		</div><!-- .comm-feed__embed-col -->

		<!-- Sidebar: More Ways to Connect -->
		<aside class="comm-feed__sidebar" aria-label="<?php esc_attr_e( 'Other ways to connect with PC4S', 'pc4s' ); ?>">

			<h2 class="comm-feed__sidebar-title"><?php echo esc_html( $feed_sidebar_title ); ?></h2>

			<ul class="comm-feed__channel-list" role="list">

				<?php if ( $facebook_page_url ) : ?>
				<!-- Facebook channel card -->
				<li>
					<a
						href="<?php echo esc_url( $facebook_page_url ); ?>"
						class="comm-feed__channel-card"
						target="_blank"
						rel="noopener noreferrer"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: Facebook handle */ __( 'Like PC4S on Facebook %s (opens in new tab)', 'pc4s' ), $feed_fb_handle ) ); ?>"
					>
						<span class="comm-feed__channel-icon comm-feed__channel-icon--facebook" aria-hidden="true">
							<?php echo $copp_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<span class="comm-feed__channel-info">
							<span class="comm-feed__channel-name"><?php esc_html_e( 'Facebook', 'pc4s' ); ?></span>
							<?php if ( $feed_fb_handle ) : ?>
							<span class="comm-feed__channel-handle"><?php echo esc_html( $feed_fb_handle ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( $contact_email ) : ?>
				<!-- Email channel card -->
				<li>
					<a
						href="mailto:<?php echo esc_attr( $contact_email ); ?>"
						class="comm-feed__channel-card"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: email address */ __( 'Email PC4S at %s', 'pc4s' ), $contact_email ) ); ?>"
					>
						<span class="comm-feed__channel-icon comm-feed__channel-icon--email" aria-hidden="true">
							<?php echo $copp_icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<span class="comm-feed__channel-info">
							<span class="comm-feed__channel-name"><?php esc_html_e( 'Email', 'pc4s' ); ?></span>
							<span class="comm-feed__channel-handle"><?php echo esc_html( $contact_email ); ?></span>
						</span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( $contact_phone ) : ?>
				<!-- Phone channel card -->
				<li>
					<a
						href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $contact_phone ) ); ?>"
						class="comm-feed__channel-card"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: phone number */ __( 'Call PC4S at %s', 'pc4s' ), $contact_phone ) ); ?>"
					>
						<span class="comm-feed__channel-icon comm-feed__channel-icon--phone" aria-hidden="true">
							<?php echo $copp_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<span class="comm-feed__channel-info">
							<span class="comm-feed__channel-name"><?php esc_html_e( 'Phone', 'pc4s' ); ?></span>
							<span class="comm-feed__channel-handle"><?php echo esc_html( $contact_phone ); ?></span>
						</span>
					</a>
				</li>
				<?php endif; ?>

			</ul><!-- .comm-feed__channel-list -->

		</aside><!-- .comm-feed__sidebar -->

	</div><!-- .wrapper -->
</section><!-- .comm-feed -->

<?php /* ====================================================================
   SECTION: Page CTA — badge, heading, subtitle + action buttons
   ==================================================================== */ ?>
<?php if ( $cta_heading ) : ?>
<section class="section page-cta" aria-labelledby="comm-cta-heading">
	<div class="wrapper">
		<div class="section__header">

			<?php if ( $cta_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $cta_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="comm-cta-heading" class="section__title">
				<?php echo esc_html( $cta_heading ); ?>
			</h2>

			<?php if ( $cta_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $cta_subtitle ); ?></p>
			<?php endif; ?>

			<?php if ( $cta_primary || $cta_secondary ) : ?>
			<div class="page-cta__actions">
				<?php if ( $cta_primary && ! empty( $cta_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $cta_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $cta_primary['target'] ) ) : ?>target="<?php echo esc_attr( $cta_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $cta_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $cta_secondary && ! empty( $cta_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $cta_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $cta_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $cta_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $cta_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .section__header -->
	</div><!-- .wrapper -->
</section><!-- .page-cta -->
<?php endif; ?>

<?php
get_footer();
