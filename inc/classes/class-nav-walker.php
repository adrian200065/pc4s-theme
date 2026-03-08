<?php
/**
 * Custom Navigation Walker
 *
 * Produces the exact HTML structure used in the static site:
 *
 *  • Top-level items with children    → <li class="nav-item has-dropdown"> + <button> toggle
 *  • Top-level items without children → <li class="nav-item"> + <a class="nav-link">
 *  • Submenu items                    → plain <li><a> with no extra classes
 *
 * Dropdown IDs are derived from the menu item title so they remain stable
 * across page loads and can be targeted by JavaScript.
 *
 * Special modifier classes (nav-link--donate, nav-link--login, …) are read
 * from the "CSS Classes" field on each menu item in the WordPress admin
 * (Appearance → Menus → Screen Options → CSS Classes). Any class that starts
 * with "nav-link--" is automatically forwarded to the rendered <a>/<button>.
 *
 * @package PC4S
 */

namespace PC4S\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nav_Walker extends \Walker_Nav_Menu {

	/**
	 * The last top-level parent item encountered.
	 * Stored so start_lvl() can generate the correct submenu id attribute,
	 * since Walker_Nav_Menu does not pass the parent item into that callback.
	 *
	 * @var \WP_Post|null
	 */
	private ?\WP_Post $last_parent = null;

	/**
	 * Whether the current top-level item has children.
	 * Derived from $item->classes ('menu-item-has-children') in start_el
	 * and consumed in start_lvl / render_li.
	 *
	 * Using the class flag is more reliable than $args->has_children across
	 * all WordPress versions and custom walker invocations.
	 *
	 * @var bool
	 */
	private bool $current_has_children = false;

	// =========================================================================
	// Level hooks
	// =========================================================================

	/**
	 * Opens a submenu <ul>.
	 *
	 * $depth here is the depth of the *parent*, so 0 means we are opening
	 * a first-level dropdown.
	 *
	 * {@inheritdoc}
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		if ( 0 === $depth && $this->current_has_children && $this->last_parent ) {
			$id      = $this->make_submenu_id( $this->last_parent );
			$output .= '<ul class="submenu" id="' . esc_attr( $id ) . '" role="list">' . "\n";
		}
	}

	/**
	 * Closes a submenu <ul>.
	 *
	 * {@inheritdoc}
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		if ( 0 === $depth && $this->current_has_children ) {
			$output .= '</ul>' . "\n";
		}
	}

	// =========================================================================
	// Element hooks
	// =========================================================================

	/**
	 * Outputs the opening <li> and the link / button for one menu item.
	 *
	 * {@inheritdoc}
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$classes = empty( $item->classes ) ? [] : (array) $item->classes;

		// WordPress stamps 'menu-item-has-children' on items that have child
		// menu items. Using this class is more reliable than $args->has_children
		// across all WordPress versions and walker invocations.
		$has_children = in_array( 'menu-item-has-children', $classes, true );
		$is_current   = in_array( 'current-menu-item', $classes, true );

		// Persist on the instance so start_lvl / end_lvl have access.
		if ( 0 === $depth ) {
			$this->current_has_children = $has_children;
		}

		// ── <li> ─────────────────────────────────────────────────────────────
		$output .= $this->render_li( $depth, $classes, $has_children );

		// ── Link or button ────────────────────────────────────────────────────
		if ( 0 === $depth && $has_children ) {
			// Store parent so start_lvl() can read it for the submenu id.
			$this->last_parent = $item;
			$output           .= $this->render_dropdown_button( $item, $is_current );
		} elseif ( 0 === $depth ) {
			$output .= $this->render_top_link( $item, $classes, $is_current );
		} else {
			$output .= $this->render_sub_link( $item );
		}
	}

	/**
	 * Closes the <li>.
	 *
	 * {@inheritdoc}
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ): void {
		$output .= '</li>' . "\n";
	}

	// =========================================================================
	// Private rendering helpers
	// =========================================================================

	/**
	 * Build the <li> opening tag with the appropriate classes.
	 *
	 * @param int   $depth        Walker depth (0 = top level).
	 * @param array $item_classes WordPress item class array.
	 * @param bool  $has_children Whether the item has submenu children.
	 * @return string
	 */
	private function render_li( int $depth, array $item_classes, bool $has_children ): string {
		if ( 0 !== $depth ) {
			return '<li>';
		}

		$li = [ 'nav-item' ];

		if ( $has_children ) {
			$li[] = 'has-dropdown';
		}

		// Forward WP active / ancestor classes so JS and CSS can use them.
		$wp_active = array_intersect(
			$item_classes,
			[ 'current-menu-item', 'current-menu-parent', 'current-menu-ancestor' ]
		);
		$li = array_merge( $li, array_values( $wp_active ) );

		return '<li class="' . esc_attr( implode( ' ', $li ) ) . '">';
	}

	/**
	 * Render a <button> for top-level items that have children.
	 *
	 * @param \WP_Post $item       Menu item object.
	 * @param bool     $is_current Whether this is the active page.
	 * @return string
	 */
	private function render_dropdown_button( \WP_Post $item, bool $is_current ): string {
		$submenu_id = $this->make_submenu_id( $item );
		$link_class = 'nav-link' . ( $is_current ? ' nav-link--active' : '' );

		return sprintf(
			'<button class="%1$s" type="button" aria-expanded="false" aria-haspopup="menu" aria-controls="%2$s">%3$s%4$s</button>',
			esc_attr( $link_class ),
			esc_attr( $submenu_id ),
			esc_html( apply_filters( 'the_title', $item->title, $item->ID ) ),
			$this->arrow_svg()
		);
	}

	/**
	 * Render an <a> for a top-level item without children.
	 *
	 * Any "nav-link--*" classes assigned to the item in the admin are
	 * appended to the link element (e.g. nav-link--donate, nav-link--login).
	 *
	 * @param \WP_Post $item       Menu item object.
	 * @param array    $classes    Full class array from WordPress.
	 * @param bool     $is_current Whether this is the active page.
	 * @return string
	 */
	private function render_top_link( \WP_Post $item, array $classes, bool $is_current ): string {
		$link_class = $this->build_link_class( $classes, $is_current );

		return sprintf(
			'<a href="%1$s" class="%2$s"%3$s%4$s%5$s>%6$s</a>',
			esc_url( $item->url ),
			esc_attr( $link_class ),
			( $item->attr_title ? ' title="' . esc_attr( $item->attr_title ) . '"' : '' ),
			( $item->target     ? ' target="' . esc_attr( $item->target ) . '"' : '' ),
			( $item->xfn        ? ' rel="' . esc_attr( $item->xfn ) . '"' : '' ),
			esc_html( apply_filters( 'the_title', $item->title, $item->ID ) )
		);
	}

	/**
	 * Render a plain <a> for submenu (depth ≥ 1) items.
	 *
	 * @param \WP_Post $item Menu item object.
	 * @return string
	 */
	private function render_sub_link( \WP_Post $item ): string {
		return sprintf(
			'<a href="%1$s"%2$s%3$s%4$s>%5$s</a>',
			esc_url( $item->url ),
			( $item->attr_title ? ' title="' . esc_attr( $item->attr_title ) . '"' : '' ),
			( $item->target     ? ' target="' . esc_attr( $item->target ) . '"' : '' ),
			( $item->xfn        ? ' rel="' . esc_attr( $item->xfn ) . '"' : '' ),
			esc_html( apply_filters( 'the_title', $item->title, $item->ID ) )
		);
	}

	/**
	 * Build the full CSS class string for a top-level link element.
	 *
	 * Extracts any "nav-link--*" modifier classes from the item's class list
	 * (set via the admin CSS Classes field) and merges them in.
	 *
	 * @param array $item_classes WordPress item classes.
	 * @param bool  $is_current   Whether to add the active modifier.
	 * @return string Space-separated class string.
	 */
	private function build_link_class( array $item_classes, bool $is_current ): string {
		$classes = [ 'nav-link' ];

		// Forward any nav-link--* modifiers added via WP admin CSS Classes field.
		// Exclude nav-link--active — that is reserved for dynamic active-state
		// detection only and must never be hardcoded in the admin.
		foreach ( $item_classes as $class ) {
			if ( str_starts_with( $class, 'nav-link--' ) && 'nav-link--active' !== $class ) {
				$classes[] = $class;
			}
		}

		if ( $is_current ) {
			$classes[] = 'nav-link--active';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Derive a stable HTML id for a submenu from its parent item title.
	 *
	 * @param \WP_Post $item Parent menu item.
	 * @return string  e.g. "submenu-about-pc4s"
	 */
	private function make_submenu_id( \WP_Post $item ): string {
		return 'submenu-' . sanitize_title( $item->title );
	}

	/**
	 * Return the shared dropdown-arrow SVG.
	 *
	 * @return string
	 */
	private function arrow_svg(): string {
		return '<svg class="nav-arrow" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true">'
			. '<path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="2" fill="none" />'
			. '</svg>';
	}
}
