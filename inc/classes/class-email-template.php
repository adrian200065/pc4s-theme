<?php
/**
 * Email Template Builder
 *
 * Generates the branded, accessible HTML notification email used whenever a
 * site form (contact, donate, newsletter, license‑plate pre‑order, etc.) is
 * submitted. All three rendering engines — desktop clients, webmail, and
 * mobile — are handled through a hybrid table/CSS approach.
 *
 * Design system (all colors in HSL, mirroring _custom-properties.scss):
 *   Dark bg:     hsl(223, 48%, 11%)  — header / footer (--clr-dark-blue)
 *   Red accent:  hsl(0, 85%, 52%)    — accent bar / badge (--clr-primary-500)
 *   Blue:        hsl(230, 97%, 30%)  — links / info card  (--clr-secondary-600)
 *   Text:        hsl(210, 12%, 20%)  — body text          (--clr-neutral-800)
 *   Muted:       hsl(210, 8%, 40%)   — labels / subtext   (--clr-neutral-600)
 *   Border:      hsl(210, 10%, 90%)  — row dividers       (--clr-neutral-300)
 *   Page bg:     hsl(210, 15%, 95%)  — outer wrapper      (--clr-neutral-200)
 *
 * Accessibility: WCAG 2.2 AA contrast ratios for all text/background pairs.
 * Responsive:    Fluid at 100 %; max-width 640 px; column stack on mobile via
 *                media query.
 * Dark mode:     @media (prefers-color-scheme: dark) overrides provided.
 * Outlook:       MSO conditional comments + VML declarations included.
 *
 * @package PC4S
 */

namespace PC4S\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Template {

	// ─── Design Tokens ───────────────────────────────────────────────────────

	private const C_DARK    = 'hsl(223, 48%, 11%)';   // header / footer bg
	private const C_RED     = 'hsl(0, 85%, 52%)';     // primary accent
	private const C_BLUE    = 'hsl(230, 97%, 30%)';   // secondary / links
	private const C_TEXT    = 'hsl(210, 12%, 20%)';   // body text
	private const C_MUTED   = 'hsl(210, 8%, 40%)';    // label / subtext
	private const C_BORDER  = 'hsl(210, 10%, 90%)';   // row dividers
	private const C_PAGE_BG = 'hsl(210, 15%, 95%)';   // outer wrapper
	private const C_WHITE   = 'hsl(0, 0%, 100%)';
	private const C_ROW_ALT = 'hsl(217, 33%, 97%)';   // alternating row
	private const C_INFO_BG = 'hsl(230, 60%, 97%)';   // info notice bg
	private const C_INFO_BD = 'hsl(230, 45%, 88%)';   // info notice border
	private const C_INFO_TX = 'hsl(230, 97%, 25%)';   // info notice text

	/** Per-form-type badge colors (fallback: blue). */
	private const BADGE_COLORS = [
		'contact_us'    => self::C_BLUE,
		'donate'        => 'hsl(142, 71%, 35%)',  // green
		'newsletter'    => self::C_RED,
		'license_plate' => 'hsl(27, 98%, 48%)',   // orange
	];

	// ─── Public API ──────────────────────────────────────────────────────────

	/**
	 * Build the complete HTML notification email for a form submission.
	 *
	 * @param array  $form        Form definition (id, label, fields, …).
	 * @param array  $field_data  Submitted and sanitized field values.
	 * @param string $source_page URL of the originating page.
	 * @return string             Complete UTF-8 HTML email string.
	 */
	public static function build( array $form, array $field_data, string $source_page = '' ): string {

		// ── Metadata ─────────────────────────────────────────────────────────
		$site_name   = esc_html( get_bloginfo( 'name' ) );
		$site_url    = esc_url( home_url( '/' ) );
		$logo_url    = esc_url( get_template_directory_uri() . '/assets/images/pc4s-logo-white.webp' );
		$form_label  = esc_html( $form['label'] ?? 'Form Submission' );
		$form_id     = $form['id'] ?? '';
		$field_defs  = $form['fields'] ?? [];

		$date_iso = esc_attr( wp_date( 'Y-m-d', current_time( 'timestamp' ) ) );
		$date_fmt = esc_html( wp_date( 'M j, Y', current_time( 'timestamp' ) ) );
		$time_fmt = esc_html( wp_date( 'g:i A T', current_time( 'timestamp' ) ) );

		$badge_color = self::BADGE_COLORS[ $form_id ] ?? self::C_BLUE;

		// ── Preheader (hidden preview text for email clients) ─────────────────
		$preheader = sprintf(
			'%s — submitted %s at %s via %s',
			$form_label,
			$date_fmt,
			$time_fmt,
			$site_name
		);

		// ── Donation total block (mirrors the Uber receipt "total" pattern) ───
		$donation_block = '';
		if ( ! empty( $field_data['amount'] ) && (float) $field_data['amount'] > 0 ) {
			$amount_fmt     = esc_html( '$' . number_format( (float) $field_data['amount'], 2 ) );
			$donation_block = self::donation_total_block( $amount_fmt );
		}

		// ── Field rows ────────────────────────────────────────────────────────
		$rows_html   = '';
		$alt         = false;
		foreach ( $field_data as $key => $value ) {
			// Amount is shown in the prominent total block above.
			if ( 'amount' === $key ) {
				continue;
			}
			$label = isset( $field_defs[ $key ]['label'] )
				? esc_html( $field_defs[ $key ]['label'] )
				: esc_html( ucwords( str_replace( '_', ' ', $key ) ) );

			$field_type = $field_defs[ $key ]['type'] ?? 'text';
			$row_bg     = $alt ? self::C_ROW_ALT : self::C_WHITE;

			if ( 'textarea' === $field_type ) {
				$rows_html .= self::textarea_row( $label, $value, $row_bg );
			} else {
				$rows_html .= self::field_row( $label, $value, $row_bg );
			}
			$alt = ! $alt;
		}

		// ── Source page ───────────────────────────────────────────────────────
		$source_row = '';
		if ( $source_page ) {
			$source_row = self::field_row(
				'Source Page',
				'<a href="' . esc_url( $source_page ) . '" style="color:' . self::C_BLUE . ';text-decoration:underline;word-break:break-all;">' . esc_html( $source_page ) . '</a>',
				$alt ? self::C_ROW_ALT : self::C_WHITE,
				false // value is already escaped HTML
			);
		}

		// ── Site display URL (strip protocol for footer) ──────────────────────
		$site_display_url = esc_html( preg_replace( '/^https?:\/\//', '', rtrim( home_url( '/' ), '/' ) ) );

		// ── Render full template ──────────────────────────────────────────────
		ob_start();
		self::render_template(
			$site_name,
			$site_url,
			$site_display_url,
			$logo_url,
			$form_label,
			$badge_color,
			$date_iso,
			$date_fmt,
			$time_fmt,
			$preheader,
			$donation_block,
			$rows_html,
			$source_row
		);
		return ob_get_clean();
	}

	/**
	 * Return the extra headers array required for HTML email via wp_mail().
	 *
	 * Usage:
	 *   wp_mail( $to, $subject, Email_Template::build(…), Email_Template::headers() );
	 *
	 * @return string[]
	 */
	public static function headers(): array {
		return [ 'Content-Type: text/html; charset=UTF-8' ];
	}

	// ─── Private helpers ─────────────────────────────────────────────────────

	/**
	 * Render the donation-total highlight block (visible only for donate form).
	 */
	private static function donation_total_block( string $amount_fmt ): string {
		$c_text   = self::C_TEXT;
		$c_red    = self::C_RED;
		$c_border = self::C_BORDER;

		return <<<HTML
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
  <tr>
    <td style="padding:2rem 2.5rem 0;">
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
        <tr>
          <td class="total-label-td"
              id="donation-amount-label"
              style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:1.25rem;font-weight:700;color:{$c_text};line-height:1.2;vertical-align:middle;">
            Donation Amount
          </td>
          <td class="total-value-td"
              align="right"
              aria-labelledby="donation-amount-label"
              style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:2rem;font-weight:800;color:{$c_red};line-height:1.2;vertical-align:middle;">
            {$amount_fmt}
          </td>
        </tr>
      </table>
      <hr role="separator" style="height:1px;background-color:{$c_border};border:none;margin:1.25rem 0 0;">
    </td>
  </tr>
</table>
HTML;
	}

	/**
	 * Build a single-line label / value field row.
	 *
	 * @param string $label     Field label (already escaped).
	 * @param string $value     Field value (already escaped unless $raw = true).
	 * @param string $bg        Row background color.
	 * @param bool   $escape    Whether to esc_html() the value.
	 */
	private static function field_row( string $label, string $value, string $bg, bool $escape = true ): string {
		$value_out = $escape ? esc_html( $value ) : $value;
		$c_muted   = self::C_MUTED;
		$c_text    = self::C_TEXT;
		$c_border  = self::C_BORDER;
		$ff        = "-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif";

		return <<<HTML
<tr>
  <td class="field-row-cell"
      style="background-color:{$bg};padding:.875rem 2.5rem;border-bottom:1px solid {$c_border};">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
      <tr>
        <td class="field-label-td"
            style="font-family:{$ff};font-size:.6875rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:{$c_muted};width:38%;padding-right:1rem;vertical-align:top;">
          {$label}
        </td>
        <td class="field-value-td"
            style="font-family:{$ff};font-size:.9375rem;color:{$c_text};line-height:1.5;vertical-align:top;">
          {$value_out}
        </td>
      </tr>
    </table>
  </td>
</tr>
HTML;
	}

	/**
	 * Build a multi-line textarea field row (preserves line-breaks).
	 */
	private static function textarea_row( string $label, string $value, string $bg ): string {
		$value_out = nl2br( esc_html( $value ) );
		$c_muted   = self::C_MUTED;
		$c_text    = self::C_TEXT;
		$c_border  = self::C_BORDER;
		$ff        = "-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif";

		return <<<HTML
<tr>
  <td class="field-row-cell"
      style="background-color:{$bg};padding:1rem 2.5rem;border-bottom:1px solid {$c_border};">
    <p style="margin:0 0 0.375rem;font-family:{$ff};font-size:.6875rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:{$c_muted};">{$label}</p>
    <p style="margin:0;font-family:{$ff};font-size:.9375rem;color:{$c_text};line-height:1.65;">{$value_out}</p>
  </td>
</tr>
HTML;
	}

	// ─── Main template render ─────────────────────────────────────────────────

	/**
	 * Echo the complete HTML email document.
	 * Called inside ob_start() / ob_get_clean() in build().
	 */
	private static function render_template(
		string $site_name,
		string $site_url,
		string $site_display_url,
		string $logo_url,
		string $form_label,
		string $badge_color,
		string $date_iso,
		string $date_fmt,
		string $time_fmt,
		string $preheader,
		string $donation_block,
		string $rows_html,
		string $source_row
	): void {
		$c_dark    = self::C_DARK;
		$c_red     = self::C_RED;
		$c_blue    = self::C_BLUE;
		$c_text    = self::C_TEXT;
		$c_muted   = self::C_MUTED;
		$c_border  = self::C_BORDER;
		$c_page_bg = self::C_PAGE_BG;
		$c_white   = self::C_WHITE;
		$c_info_bg = self::C_INFO_BG;
		$c_info_bd = self::C_INFO_BD;
		$c_info_tx = self::C_INFO_TX;
		$ff        = "-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif";
		?>
<!DOCTYPE html>
<html lang="en"
      xmlns="http://www.w3.org/1999/xhtml"
      xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!-- Disable auto-detection of phone numbers / addresses / dates / emails -->
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
  <!-- Prevent Apple Mail from scaling small text -->
  <meta name="x-apple-disable-message-reformatting">
  <title><?php echo esc_html( $form_label . ' — ' . $site_name ); ?></title>

  <!-- MSO VML support for Outlook rendering engine -->
  <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <![endif]-->

  <style>
    /* ── Reset ───────────────────────────────────────────────────────── */
    *, 
	*::before, 
	*::after { 
		box-sizing: border-box; 
	}
    body, table, td, a {
		-webkit-text-size-adjust: 100%;
		-ms-text-size-adjust:     100%;
    }
    table, td {
		mso-table-lspace: 0pt;
		mso-table-rspace: 0pt;
    }
    img {
		line-height:     100%;
		text-decoration: none;
		-ms-interpolation-mode: bicubic;
		border:          0;
		height:          auto;
		outline:         none;
    }

    /* ── Suppress Apple data-detectors link styling ─────────────────── */
    a[x-apple-data-detectors] {
		color:           inherit !important;
		text-decoration: none   !important;
		font-size:       inherit !important;
		font-family:     inherit !important;
		font-weight:     inherit !important;
		line-height:     inherit !important;
    }
    /* Gmail blue-link override */
    u + #body a,
    #MessageViewBody a {
		color:           inherit;
		font-size:       inherit;
		font-family:     inherit;
		font-weight:     inherit;
		text-decoration: none;
		line-height:     inherit;
    }

    /* ── CSS Custom Properties (supported in modern clients) ─────────── */
    :root {
		--email-dark:    <?php echo $c_dark; ?>;
		--email-red:     <?php echo $c_red; ?>;
		--email-blue:    <?php echo $c_blue; ?>;
		--email-text:    <?php echo $c_text; ?>;
		--email-muted:   <?php echo $c_muted; ?>;
		--email-border:  <?php echo $c_border; ?>;
		--email-page-bg: <?php echo $c_page_bg; ?>;
		--email-white:   <?php echo $c_white; ?>;
    }

    /* ── Base ────────────────────────────────────────────────────────── */
    body {
		color:            <?php echo $c_text; ?>;
		background-color: <?php echo $c_page_bg; ?>;
		font-family:      <?php echo $ff; ?>;
		padding:          0 !important;
		margin:           0 !important;
    }

    /* ── Container ───────────────────────────────────────────────────── */
    .email-outer {
		background-color: <?php echo $c_page_bg; ?>;
		padding-block:    2rem;
    }
    .email-container {
		background-color: <?php echo $c_white; ?>;
		max-width:        62.5rem;
		margin-inline:    auto;
		border-radius:    .5rem;
		overflow:         hidden;
		box-shadow:       0 4px 24px hsl(210,15%,12%,0.10);
    }

    /* ── Header ─────────────────────────────────────────────────────── */
    .email-header {
		background-color: <?php echo $c_dark; ?>;
		padding:          1.75rem 2.5rem;
    }
    .header-date {
		color:       hsl(210,20%,70%);
		font-family: <?php echo $ff; ?>;
		font-size:   .8125rem;
		text-align:  right;
		line-height: 1.5;
		white-space: nowrap;
    }

    /* ── Accent bar ──────────────────────────────────────────────────── */
    .accent-bar {
		background-color: <?php echo $c_red; ?>;
		font-size:        0;
		line-height:      0;
		height:           .25rem;
    }

    /* ── Hero ────────────────────────────────────────────────────────── */
    .email-hero {
		background-color: <?php echo $c_dark; ?>;
		padding:          2rem 2.5rem 2.5rem;
    }
    .hero-badge {
		color:            <?php echo $c_white; ?>;
		background-color: <?php echo $badge_color; ?>;
		font-family:      <?php echo $ff; ?>;
		font-size:        .75rem;
		font-weight:      700;
		letter-spacing:   0.10em;
		text-transform:   uppercase;
		display:          inline-block;
		padding:          .25rem .75rem;
		margin-bottom:    .875rem;
		border-radius:    6.25rem;
    }
    .hero-title {
		color:            <?php echo $c_white; ?>;
		font-family:      <?php echo $ff; ?>;
		font-size:        2rem;
		font-weight:      800;
		line-height:      1.15;
		letter-spacing:   -0.02em;
		margin:           0;
    }
    .hero-subtitle {
		color:       hsl(210,20%,72%);
		font-family: <?php echo $ff; ?>;
		font-size:   .9375rem;
		line-height: 1.5;
		margin:      10px 0 0;
    }

    /* ── Body ────────────────────────────────────────────────────────── */
    .email-body { 
		background-color: <?php echo $c_white; ?>;
	 }

    /* Info notice card */
    .info-card {
		background-color: <?php echo $c_info_bg; ?>;
		border:           1px solid <?php echo $c_info_bd; ?>;
		border-radius:    .5rem;
		padding:          .875rem 1.125rem;
		margin:           1.75rem 2.5rem 0;
    }
    .info-icon {
		color:            <?php echo $c_white; ?>;
		background-color: <?php echo $c_blue; ?>;
		font-family:      <?php echo $ff; ?>;
		font-size:        .8125rem;
		font-weight:      800;
		text-align:       center;
		line-height:      1.375rem;
		display:          inline-block;
		flex-shrink:      0;
		width:            1.375rem;
		height:           1.375rem;
		border-radius:    50%;
    }
    .info-text {
		color:       <?php echo $c_info_tx; ?>;
		font-family: <?php echo $ff; ?>;
		font-size:   .875rem;
		line-height: 1.55;
		margin:      0;
    }

    /* Fields section header */
    .section-label {
		padding:     1.5rem 2.5rem .625rem;
    }
    .section-label-text {
		color:           <?php echo $c_muted; ?>;
		font-family:     <?php echo $ff; ?>;
		font-size:       .75rem;
		font-weight:     700;
		letter-spacing:  0.08em;
		text-transform:  uppercase;
		margin:          0;
    }

    /* ── Footer ─────────────────────────────────────────────────────── */
    .email-footer {
		background-color: <?php echo $c_dark; ?>;
		padding:          2rem 2.5rem;
    }
    .footer-addr {
		color:       hsl(210,20%,60%);
		font-family: <?php echo $ff; ?>;
		font-size:   .8125rem;
		line-height: 1.7;
		margin:      0 0 1rem;
    }
    .footer-divider {
		background-color: hsl(223,40%,20%);
		border:           none;
		margin:           0 0 1.125rem;
		height:           1px;
    }
    .footer-note {
		color:       hsl(210,15%,45%);
		font-family: <?php echo $ff; ?>;
		font-size:   .75rem;
		line-height: 1.6;
		margin:      0;
    }

    /* ── Responsive ──────────────────────────────────────────────────── */
    @media screen and (max-width: 62.5rem) {
      .email-outer      { padding-block: 0 !important; }
      .email-container  { border-radius: 0 !important; box-shadow: none !important; }
      .email-header     { padding: 1.125rem 1.5rem !important; }
      .email-hero       { padding: 1.375rem 1.5rem 1.875rem !important; }
      .hero-title       { font-size: 1.5rem !important; }
      .info-card        { margin: 1.5rem 1.5rem 0 !important; }
      .section-label    { padding: 1.25rem 1.5rem 0.5rem !important; }
      .email-footer     { padding: 1.5rem !important; }
      .field-row-cell   { padding: 0.75rem 1.5rem !important; }
      /* Stack label / value on narrow screens */
      .field-label-td,
      .field-value-td   { display: block !important; width: 100% !important; padding-right: 0 !important; }
      /* Stack donation total */
      .total-label-td,
      .total-value-td   { display: block !important; width: 100% !important; }
      .total-value-td   { text-align: left !important; font-size: 1.5rem !important; }
    }

    /* ── Dark mode ───────────────────────────────────────────────────── */
    @media (prefers-color-scheme: dark) {
      .email-body,
      .email-container  { background-color: hsl(223,22%,15%) !important; }
      .field-row-white  { background-color: hsl(223,22%,17%) !important; }
      .field-row-alt    { background-color: hsl(223,22%,15%) !important; }
      .info-card        { background-color: hsl(230,35%,20%) !important; border-color: hsl(230,35%,28%) !important; }
      .info-text        { color: hsl(230,60%,85%) !important; }
    }
  </style>
</head>

<body id="body" role="document">

  <!--
    ══════════════════════════════════════════════════════════════════════
    PREHEADER — shown as preview text in email clients; hidden visually.
    Padded with zero-width non-joiner chars to prevent bleed-through.
    ══════════════════════════════════════════════════════════════════════
  -->
  <div aria-hidden="true"
       style="display:none;overflow:hidden;max-height:0;max-width:0;opacity:0;visibility:hidden;mso-hide:all;font-size:1px;line-height:1px;">
    <?php echo esc_html( $preheader ); ?>&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
  </div>

  <!--
    ══════════════════════════════════════════════════════════════════════
    OUTER WRAPPER — full-width background color
    ══════════════════════════════════════════════════════════════════════
  -->
  <table role="presentation"
         border="0" cellpadding="0" cellspacing="0"
         width="100%"
         class="email-outer"
         style="border-collapse:collapse;background-color:<?php echo $c_page_bg; ?>;padding-top:2rem;padding-bottom:2rem;">
    <tr>
      <td align="center" style="padding:0;">

        <!--
          ════════════════════════════════════════════════════════════════
          EMAIL CONTAINER — 640 px max-width
          ════════════════════════════════════════════════════════════════
        -->
        <table role="presentation"
               border="0" cellpadding="0" cellspacing="0"
               width="100%"
               class="email-container"
               style="border-collapse:collapse;max-width:62.5rem;background-color:<?php echo $c_white; ?>;border-radius:.5rem;overflow:hidden;box-shadow:0 .25rem 1.5rem hsl(210,15%,12%,0.10);">

          <!-- ╔══════════════════════════════════════════════════════════╗ -->
          <!-- ║  HEADER — dark background, logo left, date right        ║ -->
          <!-- ╚══════════════════════════════════════════════════════════╝ -->
          <tr>
            <td class="email-header"
                style="background-color:<?php echo $c_dark; ?>;padding:1.75rem 2.5rem;">
              <table role="presentation"
                     border="0" cellpadding="0" cellspacing="0"
                     width="100%"
                     style="border-collapse:collapse;">
                <tr>
                  <!-- Logo -->
                  <td valign="middle">
                    <a href="<?php echo $site_url; ?>"
                       aria-label="<?php echo esc_attr( $site_name . ' website' ); ?>">
                      <img src="<?php echo $logo_url; ?>"
                           alt="<?php echo esc_attr( $site_name ); ?>"
                           width="140"
                           height="auto"
                           style="display:block;border:0;height:auto;max-height:3.25rem;width:auto;max-width:10rem;">
                    </a>
                  </td>
                  <!-- Date/time -->
                  <td valign="middle"
                      align="right"
                      class="header-date"
                      style="font-family:<?php echo $ff; ?>;font-size:.8125rem;color:hsl(210,20%,70%);text-align:right;line-height:1.5;white-space:nowrap;">
                    <time datetime="<?php echo $date_iso; ?>">
                      <?php echo $date_fmt; ?><br>
                      <span aria-label="at <?php echo $time_fmt; ?>"><?php echo $time_fmt; ?></span>
                    </time>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ╔══════════════════════════════════════════════════════════╗ -->
          <!-- ║  RED ACCENT BAR                                          ║ -->
          <!-- ╚══════════════════════════════════════════════════════════╝ -->
          <tr>
            <td class="accent-bar"
                role="presentation"
                style="height:4px;background-color:<?php echo $c_red; ?>;line-height:0;font-size:0;">&nbsp;</td>
          </tr>

          <!-- ╔══════════════════════════════════════════════════════════╗ -->
          <!-- ║  HERO — form-type badge + heading                        ║ -->
          <!-- ╚══════════════════════════════════════════════════════════╝ -->
          <tr>
            <td class="email-hero"
                style="background-color:<?php echo $c_dark; ?>;padding:2rem 2.5rem 2.5rem;">

              <!-- Badge pill -->
              <div class="hero-badge"
                   style="display:inline-block;font-family:<?php echo $ff; ?>;font-size:.6875rem;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:<?php echo $c_white; ?>;background-color:<?php echo $badge_color; ?>;padding:.25rem .75rem;border-radius:6.25rem;margin-bottom:.875rem;">
                <?php echo $form_label; ?>
              </div>

              <!-- Heading -->
              <h1 class="hero-title"
                  role="heading" aria-level="1"
                  style="margin:0;font-family:<?php echo $ff; ?>;font-size:2rem;font-weight:800;color:<?php echo $c_white; ?>;line-height:1.15;letter-spacing:-0.02em;">
                New <?php echo $form_label; ?> Received
              </h1>

              <!-- Sub-heading -->
              <p class="hero-subtitle"
                 style="margin:.625rem 0 0;font-family:<?php echo $ff; ?>;font-size:.9375rem;color:hsl(210,20%,72%);line-height:1.5;">
                Someone submitted the
                <strong style="color:<?php echo $c_white; ?>;font-weight:600;"><?php echo $form_label; ?></strong>
                form on your website.
              </p>

            </td>
          </tr>

          <!-- ╔══════════════════════════════════════════════════════════╗ -->
          <!-- ║  BODY                                                     ║ -->
          <!-- ╚══════════════════════════════════════════════════════════╝ -->
          <tr>
            <td class="email-body" style="background-color:<?php echo $c_white; ?>;">

              <?php if ( $donation_block ) : ?>
                <!-- Donation amount highlight (mirrors receipt "Total" pattern) -->
                <?php echo $donation_block; ?>
              <?php endif; ?>

              <!-- Info notice card -->
              <div class="info-card"
                   role="note"
                   aria-label="Notification information"
                   style="margin:1.75rem 2.5rem 0;padding:.875rem 1.125rem;background-color:<?php echo $c_info_bg; ?>;border:1px solid <?php echo $c_info_bd; ?>;border-radius:.5rem;">
                <table role="presentation"
                       border="0" cellpadding="0" cellspacing="0"
                       width="100%"
                       style="border-collapse:collapse;">
                  <tr>
                    <!-- "i" icon -->
                    <td valign="top"
                        width="32"
                        style="padding-right:.625rem;padding-top:.1215rem;">
                      <div class="info-icon"
                           aria-hidden="true"
                           style="display:inline-block;width:1.375rem;height:1.375rem;background-color:<?php echo $c_blue; ?>;border-radius:50%;color:<?php echo $c_white; ?>;text-align:center;line-height:1.375rem;font-size:.8125rem;font-weight:800;font-family:<?php echo $ff; ?>;">
                        i
                      </div>
                    </td>
                    <!-- Text -->
                    <td valign="top">
                      <p class="info-text"
                         style="margin:0;font-family:<?php echo $ff; ?>;font-size:.875rem;color:<?php echo $c_info_tx; ?>;line-height:1.55;">
                        <strong>This is an automated notification.</strong>
                        It was generated when someone submitted the <?php echo $form_label; ?> form
                        on <a href="<?php echo $site_url; ?>"
                               style="color:<?php echo $c_blue; ?>;text-decoration:underline;"><?php echo $site_name; ?></a>.
                        Review the submission details below and respond as needed.
                      </p>
                    </td>
                  </tr>
                </table>
              </div><!-- / .info-card -->

              <!-- "Submission Details" section label -->
              <div class="section-label" style="padding:1.5rem 2.5rem .625rem;">
                <h2 class="section-label-text"
                    role="heading" aria-level="2"
                    style="margin:0;font-family:<?php echo $ff; ?>;font-size:.6875rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:<?php echo $c_muted; ?>;">
                  Submission Details
                </h2>
              </div>

              <!-- Fields table -->
              <table role="presentation"
                     border="0" cellpadding="0" cellspacing="0"
                     width="100%"
                     aria-label="Form submission field values"
                     style="border-collapse:collapse;border-top:1px solid <?php echo $c_border; ?>;">
                <?php echo $rows_html; ?>
                <?php echo $source_row; ?>
              </table>

              <!-- Bottom spacer -->
              <div style="height:2.5rem;line-height:2.5rem;font-size:0;" aria-hidden="true">&nbsp;</div>

            </td>
          </tr><!-- / BODY -->

          <!-- ╔══════════════════════════════════════════════════════════╗ -->
          <!-- ║  FOOTER                                                   ║ -->
          <!-- ╚══════════════════════════════════════════════════════════╝ -->
          <tr>
            <td class="email-footer"
                style="background-color:<?php echo $c_dark; ?>;padding:2rem 2.5rem;">

              <!-- Footer logo -->
              <a href="<?php echo $site_url; ?>"
                 aria-label="<?php echo esc_attr( $site_name . ' website' ); ?>">
                <img src="<?php echo $logo_url; ?>"
                     alt="<?php echo esc_attr( $site_name ); ?>"
                     width="100"
                     height="auto"
                     style="display:block;border:0;height:auto;max-height:2.5rem;width:auto;margin-bottom:1rem;">
              </a>

              <!-- Organization info -->
              <address style="font-style:normal;" aria-label="Organization contact information">
                <p class="footer-addr"
                   style="margin:0 0 1rem;font-family:<?php echo $ff; ?>;font-size:.8125rem;color:hsl(210,20%,60%);line-height:1.7;">
                  <?php echo $site_name; ?><br>
                  <a href="<?php echo $site_url; ?>"
                     style="color:hsl(210,20%,72%);text-decoration:underline;"><?php echo $site_display_url; ?></a>
                </p>
              </address>

              <!-- Divider -->
              <hr class="footer-divider"
                  role="separator"
                  style="height:1px;background-color:hsl(223,40%,20%);border:none;margin:0 0 1.125rem;">

              <!-- Automated-message note -->
              <p class="footer-note"
                 style="margin:0;font-family:<?php echo $ff; ?>;font-size:.75rem;color:hsl(210,15%,45%);line-height:1.6;">
                This is an automated admin notification sent by
                <a href="<?php echo $site_url; ?>"
                   style="color:hsl(210,20%,65%);text-decoration:underline;"><?php echo $site_name; ?></a>.
                Please do not reply directly to this email.
              </p>

            </td>
          </tr><!-- / FOOTER -->

        </table><!-- / .email-container -->
      </td>
    </tr>
  </table><!-- / .email-outer -->

</body>
</html>
<?php
	}
}
