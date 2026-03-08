<?php
/**
 * Theme Helper Utilities
 *
 * Provides reusable helper methods shared across template parts and components.
 *
 * @package PC4S
 */

namespace PC4S\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helper {

	/**
	 * Get logo markup for a given context.
	 *
	 * Logo resolution order:
	 *   - 'header' → uses the native WordPress Custom Logo
	 *               (Appearance → Customize → Site Identity → Logo).
	 *   - 'footer' → uses the Footer Logo uploaded via
	 *               (Appearance → Customize → Footer Branding → Footer Logo).
	 *               Falls back to the header logo when no footer logo is set.
	 *
	 * This keeps two distinct logos manageable from the Customizer without
	 * any hardcoded paths, and both fall back gracefully to the site name.
	 *
	 * @param string $context 'header' (default) | 'footer'
	 * @return string Escaped logo HTML ready to echo.
	 */
	public static function get_logo( string $context = 'header' ): string {

		$logo_id = 0;

		// For the footer, try the dedicated footer logo first.
		if ( 'footer' === $context ) {
			$logo_id = (int) get_theme_mod( 'footer_logo_id', 0 );
		}

		// Fallback: use the native WordPress custom_logo for both contexts.
		if ( ! $logo_id ) {
			$logo_id = (int) get_theme_mod( 'custom_logo', 0 );
		}

		if ( ! $logo_id ) {
			return self::get_logo_fallback();
		}

		$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );

		if ( ! $logo_url ) {
			return self::get_logo_fallback();
		}

		// Alt text: prefer the image's own alt field, fall back to site name.
		$logo_alt = trim( (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) );
		if ( ! $logo_alt ) {
			$logo_alt = get_bloginfo( 'name' );
		}

		// aria-label for screen readers.
		$aria_label = sprintf(
			/* translators: %s: site name */
			__( '%s - Home', PC4S_TEXTDOMAIN ),
			get_bloginfo( 'name' )
		);

		// Include width/height attributes to prevent layout shift (LCP).
		$size        = wp_get_attachment_image_src( $logo_id, 'full' );
		$width_attr  = ( $size && $size[1] ) ? ' width="' . (int) $size[1] . '"' : '';
		$height_attr = ( $size && $size[2] ) ? ' height="' . (int) $size[2] . '"' : '';

		return sprintf(
			'<a href="%1$s" class="logo" rel="home" aria-label="%2$s">'
			. '<img src="%3$s" alt="%4$s" class="logo-img"%5$s%6$s decoding="async" />'
			. '</a>',
			esc_url( home_url( '/' ) ),
			esc_attr( $aria_label ),
			esc_url( $logo_url ),
			esc_attr( $logo_alt ),
			$width_attr,
			$height_attr
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Text-only fallback when no logo image is configured.
	 *
	 * @return string
	 */
	private static function get_logo_fallback(): string {
		return sprintf(
			'<a href="%1$s" class="logo" rel="home" aria-label="%2$s">'
			. '<span class="logo-text">%3$s</span>'
			. '</a>',
			esc_url( home_url( '/' ) ),
			esc_attr( get_bloginfo( 'name' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}
}
