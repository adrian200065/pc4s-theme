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

	/** White logo embedded as a base64 data URI so it renders in all email clients. */
	private const LOGO_DATA_URI = 'data:image/webp;base64,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';

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
		$form_label  = esc_html( $form['label'] ?? 'Form Submission' );
		$form_id     = $form['id'] ?? '';
		$field_defs  = $form['fields'] ?? [];

		// ── Logo — absolute public URL (data URIs are blocked by Gmail + most clients) ──
		$logo_url = esc_url( get_template_directory_uri() . '/assets/images/pc4s-logo-white.webp' );

		$date_iso = esc_attr( wp_date( 'Y-m-d', time() ) );
		$date_fmt = esc_html( wp_date( 'M j, Y', time() ) );
		$time_fmt = esc_html( wp_date( 'g:i A', time() ) . ' CST' );

		$badge_color = self::BADGE_COLORS[ $form_id ] ?? self::C_BLUE;

		// ── Hero title — use subject_line field if present, else default ─────────
		$hero_title = ! empty( $field_data['subject_line'] )
			? esc_html( $field_data['subject_line'] )
			: 'New ' . $form_label . ' Received';

		// ── Submitter name — first+last name, then email, then "Someone" ────────
		$first = ! empty( $field_data['first_name'] ) ? trim( $field_data['first_name'] ) : '';
		$last  = ! empty( $field_data['last_name'] )  ? trim( $field_data['last_name'] )  : '';
		if ( $first ) {
			$submitter_name = esc_html( $first . ( $last ? ' ' . $last : '' ) );
		} elseif ( ! empty( $field_data['email'] ) ) {
			$submitter_name = esc_html( $field_data['email'] );
		} else {
			$submitter_name = 'Someone';
		}

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
			$source_row,
			$hero_title,
			$submitter_name
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
            style="font-family:{$ff};font-size:.875rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:{$c_muted};width:38%;padding-right:1rem;vertical-align:top;">
          {$label}
        </td>
        <td class="field-value-td"
            style="font-family:{$ff};font-size:1rem;color:{$c_text};line-height:1.5;vertical-align:top;">
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
    <p style="margin:0 0 0.375rem;font-family:{$ff};font-size:.875rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:{$c_muted};">{$label}</p>
    <p style="margin:0;font-family:{$ff};font-size:1rem;color:{$c_text};line-height:1.65;">{$value_out}</p>
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
		string $source_row,
		string $hero_title,
		string $submitter_name
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
		.email-outer      { 
			padding-block: 0 !important; 
		}

		.email-container  { 
			border-radius: 0 !important; 
			box-shadow: none !important; 
		}

		.email-header     { 
			padding: 1.125rem 1.5rem !important; 
		}

		.email-hero       { 
			padding: 1.375rem 1.5rem 1.875rem !important; 
		}

		.hero-title       { 
			font-size: 1.5rem !important; 
		}

		.info-card        { 
			margin: 1.5rem 1.5rem 0 !important; 
		}

		.section-label    { 
			padding: 1.25rem 1.5rem 0.5rem !important; 
		}

		.email-footer     { 
			padding: 1.5rem !important; 
		}

		.field-row-cell   { 
			padding: 0.75rem 1.5rem !important; 
		}

		.field-label-td,
		.field-value-td   { 
			display: block !important; 
			width: 100% !important; 
			padding-right: 0 !important; 
		}

		.total-label-td,
		.total-value-td   { 
			display: block !important; 
			width: 100% !important; 
		}

		.total-value-td   { 
			text-align: left !important; 
			font-size: 1.5rem !important; 
		}
    }

    /* ── Dark mode ───────────────────────────────────────────────────── */
    @media (prefers-color-scheme: dark) {
        .email-body,
		.email-container  { 
			background-color: hsl(223,22%,15%) !important; 
		}

		.field-row-white  { 
			background-color: hsl(223,22%,17%) !important; 
		}

		.field-row-alt    { 
			background-color: hsl(223,22%,15%) !important; 
		}

		.info-card        { 
			background-color: hsl(230,35%,20%) !important; 
			border-color: hsl(230,35%,28%) !important; 
		}

		.info-text { 
			color: hsl(230,60%,85%) !important; 
		}
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
                   style="display:inline-block;font-family:<?php echo $ff; ?>;font-size:.875rem;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:<?php echo $c_white; ?>;background-color:<?php echo $badge_color; ?>;padding:.25rem .75rem;border-radius:6.25rem;margin-bottom:.875rem;">
                <?php echo $form_label; ?>
              </div>

              <!-- Heading -->
              <h1 class="hero-title"
                  role="heading" aria-level="1"
                  style="margin:0;font-family:<?php echo $ff; ?>;font-size:2rem;font-weight:800;color:<?php echo $c_white; ?>;line-height:1.15;letter-spacing:-0.02em;">
                <?php echo $hero_title; ?>
              </h1>

              <!-- Sub-heading -->
              <p class="hero-subtitle"
                 style="margin:.625rem 0 0;font-family:<?php echo $ff; ?>;font-size:.9375rem;color:hsl(210,20%,72%);line-height:1.5;">
                <?php echo $submitter_name; ?> submitted the
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
                        It was generated when <?php echo $submitter_name; ?> submitted the <?php echo $form_label; ?> form
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
                    style="margin:0;font-family:<?php echo $ff; ?>;font-size:.875rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:<?php echo $c_muted; ?>;">
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
