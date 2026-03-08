<?php
/**
 * Customizer functionality
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register customizer settings and controls
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function pc4s_customize_register($wp_customize) {

	// ── Footer Branding ───────────────────────────────────────────────────────
	$wp_customize->add_section(
		'pc4s_footer_branding',
		[
			'title'       => __( 'Footer Branding', PC4S_TEXTDOMAIN ),
			'description' => __(
				'Upload a logo specifically for the footer. If left empty the header logo is used as a fallback.',
				PC4S_TEXTDOMAIN
			),
			'priority'    => 25,
		]
	);

	$wp_customize->add_setting(
		'footer_logo_id',
		[
			'default'           => 0,
			'sanitize_callback' => 'absint',
		]
	);

	$wp_customize->add_control(
		new \WP_Customize_Media_Control(
			$wp_customize,
			'footer_logo_id',
			[
				'label'     => __( 'Footer Logo (white variant)', PC4S_TEXTDOMAIN ),
				'section'   => 'pc4s_footer_branding',
				'mime_type' => 'image',
			]
		)
	);

	// ── Theme Colors ──────────────────────────────────────────────────────────
    // Add theme color settings section
    $wp_customize->add_section('pc4s_colors', [
        'title'     => __('Theme Colors', PC4S_TEXTDOMAIN),
        'priority'  => 30,
    ]);

    // Primary Color
    $wp_customize->add_setting('primary_color', [
        'default'           => 'hsl(0, 85%, 52%)',
        'sanitize_callback' => 'pc4s_sanitize_color',
    ]);

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_color', [
        'label'     => __('Primary Color', PC4S_TEXTDOMAIN),
        'section'   => 'pc4s_colors',
        'settings'  => 'primary_color',
    ]));

    // Secondary Color
    $wp_customize->add_setting('secondary_color', [
        'default'           => 'hsl(230, 97%, 40%)',
        'sanitize_callback' => 'pc4s_sanitize_color',
    ]);

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'secondary_color', [
        'label'     => __('Secondary Color', PC4S_TEXTDOMAIN),
        'section'   => 'pc4s_colors',
        'settings'  => 'secondary_color',
    ]));
}
add_action('customize_register', 'pc4s_customize_register');

/**
 * Sanitize a color value that may be a hex color or an HSL/HSLA expression.
 *
 * Accepts: #rrggbb, #rgb, hsl(h, s%, l%), hsla(h, s%, l%, a)
 * Rejects anything else (returns empty string).
 *
 * @param string $value Raw customizer value.
 * @return string Sanitized color string, or '' on failure.
 */
function pc4s_sanitize_color( string $value ): string {
	// Try WP's built-in hex sanitizer first.
	$hex = sanitize_hex_color( $value );
	if ( $hex ) {
		return $hex;
	}

	// Allow hsl() / hsla() with numeric components only.
	// e.g. hsl(0, 85%, 52%) or hsla(230, 97%, 40%, 0.8)
	$num   = '\d+(?:\.\d+)?';
	$pct   = $num . '%';
	$alpha = '(?:\s*,\s*(?:0?\.\d+|[01]))?';
	$pattern = '/^hsla?\(\s*' . $num . '\s*,\s*' . $pct . '\s*,\s*' . $pct . $alpha . '\s*\)$/i';

	if ( preg_match( $pattern, $value ) ) {
		return $value;
	}

	return '';
}
