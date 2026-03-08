<?php
/**
 * Footer Navigation Walker
 *
 * Produces minimal, accessible markup for footer navigation menus.
 * Designed for flat (single-level) lists; sub-menus are intentionally
 * suppressed — use `'depth' => 1` in wp_nav_menu() for best results.
 *
 * Differences from the default Walker_Nav_Menu:
 * - No per-item `id` attributes  (avoids DOM noise)
 * - No WordPress per-item class bloat on <li> elements
 * - Only the `is-current` class is added on active items
 * - `target="_blank"` links automatically receive `rel="noopener noreferrer"`
 *
 * @package PC4S
 */

namespace PC4S\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Footer_Nav_Walker extends \Walker_Nav_Menu {

	/**
	 * Suppress sub-menu output (opening tag).
	 * Footer menus are single-level flat lists.
	 *
	 * @param string    $output Passed by reference.
	 * @param int       $depth  Depth of menu item.
	 * @param \stdClass $args   wp_nav_menu() args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		// No nested lists in footer navigation.
	}

	/**
	 * Suppress sub-menu output (closing tag).
	 *
	 * @param string    $output Passed by reference.
	 * @param int       $depth  Depth of menu item.
	 * @param \stdClass $args   wp_nav_menu() args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		// No nested lists in footer navigation.
	}

	/**
	 * Output a single navigation item.
	 *
	 * @param string    $output Passed by reference — appended to.
	 * @param \WP_Post  $item   Menu item data object.
	 * @param int       $depth  Depth of the item.
	 * @param \stdClass $args   wp_nav_menu() arguments.
	 * @param int       $id     Current item/post ID (unused).
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		// Only add a class when this is the current page item.
		$is_current = in_array( 'current-menu-item', (array) $item->classes, true );
		$li_attrs   = $is_current ? ' class="is-current"' : '';

		$output .= '<li' . $li_attrs . '>';

		// ── <a> attributes ──────────────────────────────────────────────────
		$href       = ! empty( $item->url ) ? esc_url( $item->url ) : '#';
		$target     = ! empty( $item->target )
						? ' target="' . esc_attr( $item->target ) . '"'
						: '';

		// Always add security attributes when opening in a new tab/window.
		if ( '_blank' === $item->target ) {
			$rel = ' rel="noopener noreferrer"';
		} elseif ( ! empty( $item->xfn ) ) {
			$rel = ' rel="' . esc_attr( $item->xfn ) . '"';
		} else {
			$rel = '';
		}

		$attr_title = ! empty( $item->attr_title )
						? ' title="' . esc_attr( $item->attr_title ) . '"'
						: '';

		// Current-page link gets aria-current for screen readers.
		$aria_current = $is_current ? ' aria-current="page"' : '';

		$output .= '<a href="' . $href . '"' . $target . $rel . $attr_title . $aria_current . '>';
		$output .= wp_kses_post( apply_filters( 'the_title', $item->title, $item->ID ) );
		$output .= '</a>';
	}

	/**
	 * Close the list item.
	 *
	 * @param string    $output Passed by reference — appended to.
	 * @param \WP_Post  $item   Menu item data object.
	 * @param int       $depth  Depth of the item.
	 * @param \stdClass $args   wp_nav_menu() arguments.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ): void {
		$output .= "</li>\n";
	}
}
