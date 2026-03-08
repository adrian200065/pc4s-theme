<?php
/**
 * Breadcrumb
 *
 * Builds accessible breadcrumb navigation that reflects the current page
 * hierarchy dynamically. No values are hardcoded — all labels and URLs
 * derive from WordPress core functions and registered post-type data.
 *
 * Usage (inside a template part):
 *   echo PC4S\Classes\Breadcrumb::render(); // phpcs:ignore WordPress.Security.EscapeOutput
 *
 * @package PC4S
 */

namespace PC4S\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Breadcrumb {

	/**
	 * Build the breadcrumb trail and return escaped HTML.
	 *
	 * Returns an empty string on the front page (no trail needed).
	 *
	 * @return string Ready-to-echo HTML, or empty string.
	 */
	public static function render(): string {
		if ( is_front_page() ) {
			return '';
		}

		$items = self::build_items();

		if ( empty( $items ) ) {
			return '';
		}

		$html  = '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'pc4s' ) . '">';
		$html .= '<ol class="breadcrumbs__list" role="list">';

		$last_index = count( $items ) - 1;

		foreach ( $items as $index => $item ) {
			$is_current = ( $index === $last_index );

			if ( $is_current ) {
				$html .= '<li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">';
				$html .= esc_html( $item['label'] );
			} else {
				$html .= '<li class="breadcrumbs__item">';
				$html .= '<a href="' . esc_url( $item['url'] ) . '" class="breadcrumbs__link">'
					. esc_html( $item['label'] )
					. '</a>';
			}

			$html .= '</li>';
		}

		$html .= '</ol>';
		$html .= '</nav>';

		return $html;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Assemble an ordered array of breadcrumb items for the current request.
	 *
	 * Each item is: [ 'label' => string, 'url' => string ]
	 * The trailing (current) item intentionally carries a URL so callers can
	 * decide whether to link it.
	 *
	 * @return array<int, array{label: string, url: string}>
	 */
	private static function build_items(): array {
		$items = [];

		// Always start from Home.
		$items[] = [
			'label' => __( 'Home', 'pc4s' ),
			'url'   => home_url( '/' ),
		];

		if ( is_singular() ) {
			$items = array_merge( $items, self::items_for_singular() );
		} elseif ( is_post_type_archive() ) {
			$items[] = [
				'label' => (string) post_type_archive_title( '', false ),
				'url'   => (string) get_post_type_archive_link( get_post_type() ),
			];
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$items = array_merge( $items, self::items_for_taxonomy() );
		} elseif ( is_search() ) {
			$items[] = [
				/* translators: %s: search query */
				'label' => sprintf( __( 'Search: %s', 'pc4s' ), get_search_query() ),
				'url'   => (string) get_search_link(),
			];
		} elseif ( is_404() ) {
			$items[] = [
				'label' => __( 'Page Not Found', 'pc4s' ),
				'url'   => '',
			];
		} elseif ( is_home() ) {
			$blog_page_id = (int) get_option( 'page_for_posts' );
			$items[]      = [
				'label' => $blog_page_id ? get_the_title( $blog_page_id ) : __( 'Blog', 'pc4s' ),
				'url'   => $blog_page_id ? (string) get_permalink( $blog_page_id ) : (string) home_url( '/blog/' ),
			];
		}

		return $items;
	}

	/**
	 * Build trail segments for any singular view (page, post, CPT).
	 *
	 * @return array<int, array{label: string, url: string}>
	 */
	private static function items_for_singular(): array {
		$items   = [];
		$post_id = get_the_ID();

		if ( false === $post_id ) {
			return $items;
		}

		$post_type = get_post_type( $post_id );

		// For hierarchical pages: walk up the ancestor chain.
		if ( 'page' === $post_type ) {
			$ancestors = array_reverse( get_post_ancestors( $post_id ) );
			foreach ( $ancestors as $ancestor_id ) {
				$items[] = [
					'label' => get_the_title( $ancestor_id ),
					'url'   => (string) get_permalink( $ancestor_id ),
				];
			}
		}

		// For non-page CPTs, add the post-type archive as a crumb (if it has one).
		if ( 'page' !== $post_type && 'post' !== $post_type ) {
			$pto = get_post_type_object( $post_type );
			if ( $pto && $pto->has_archive ) {
				$items[] = [
					'label' => $pto->labels->name,
					'url'   => (string) get_post_type_archive_link( $post_type ),
				];
			}
		}

		// For standard posts, include the primary category.
		if ( 'post' === $post_type ) {
			$primary_cat = self::get_primary_category( $post_id );
			if ( $primary_cat ) {
				$items[] = [
					'label' => $primary_cat->name,
					'url'   => (string) get_category_link( $primary_cat->term_id ),
				];
			}
		}

		// Current singular item.
		$items[] = [
			'label' => (string) get_the_title( $post_id ),
			'url'   => (string) get_permalink( $post_id ),
		];

		return $items;
	}

	/**
	 * Build trail segments for taxonomy archive views.
	 *
	 * @return array<int, array{label: string, url: string}>
	 */
	private static function items_for_taxonomy(): array {
		$items    = [];
		$term     = get_queried_object();

		if ( ! ( $term instanceof \WP_Term ) ) {
			return $items;
		}

		// Walk up parent terms.
		if ( $term->parent ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				if ( $ancestor instanceof \WP_Term && ! is_wp_error( $ancestor ) ) {
					$items[] = [
						'label' => $ancestor->name,
						'url'   => (string) get_term_link( $ancestor ),
					];
				}
			}
		}

		$items[] = [
			'label' => $term->name,
			'url'   => (string) get_term_link( $term ),
		];

		return $items;
	}

	/**
	 * Retrieve the primary category for a post.
	 *
	 * Checks the Yoast primary category meta first, then falls back to the
	 * first assigned category.
	 *
	 * @param int $post_id Post ID.
	 * @return \WP_Term|null
	 */
	private static function get_primary_category( int $post_id ): ?\WP_Term {
		// Yoast SEO primary category.
		$primary_id = (int) get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );

		if ( $primary_id ) {
			$term = get_term( $primary_id, 'category' );
			if ( $term instanceof \WP_Term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		// Fallback to first assigned category.
		$categories = get_the_category( $post_id );
		return ! empty( $categories ) ? $categories[0] : null;
	}
}
