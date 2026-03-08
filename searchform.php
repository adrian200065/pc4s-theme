<?php
/**
 * The template for the search form
 *
 * Called via get_search_form() anywhere in the theme.
 * A unique ID suffix is generated per page render so that multiple instances
 * (e.g. header + search page) never share the same <label>/<input> pairing.
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search_id = 'search-' . wp_unique_id();
?>

<form
	role="search"
	method="get"
	class="search-form"
	action="<?php echo esc_url( home_url( '/' ) ); ?>"
	aria-label="<?php esc_attr_e( 'Search site', 'pc4s' ); ?>"
>
	<label class="search-form__label visually-hidden" for="<?php echo esc_attr( $search_id ); ?>">
		<?php esc_html_e( 'Search', 'pc4s' ); ?>
	</label>

	<div class="search-form__inner">
		<input
			class="search-form__input"
			type="search"
			id="<?php echo esc_attr( $search_id ); ?>"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php esc_attr_e( 'Search&hellip;', 'pc4s' ); ?>"
			autocomplete="off"
			spellcheck="false"
			aria-label="<?php esc_attr_e( 'Search query', 'pc4s' ); ?>"
		/>

		<button class="search-form__btn" type="submit" aria-label="<?php esc_attr_e( 'Submit search', 'pc4s' ); ?>">
			<svg
				class="search-form__icon"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				aria-hidden="true"
				focusable="false"
			>
				<circle cx="11" cy="11" r="8" />
				<line x1="21" y1="21" x2="16.65" y2="16.65" />
			</svg>
			<span class="visually-hidden"><?php esc_html_e( 'Search', 'pc4s' ); ?></span>
		</button>
	</div><!-- .search-form__inner -->
</form>
