<?php
/**
 * Template part: Search Result Item
 *
 * Displays a single post within the search results loop (search.php).
 * Rendered inside an <li> wrapper provided by the parent template.
 *
 * Includes:
 *   - Post type badge
 *   - Post title (linked to permalink)
 *   - Publication date
 *   - Excerpt
 *   - "Read more" link
 *
 * @package    PC4S
 * @subpackage Template_Parts/Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Resolve post type label for the badge.
$post_type_obj   = get_post_type_object( get_post_type() );
$post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : '';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-result' ); ?>>

	<div class="search-result__body">

		<?php if ( $post_type_label ) : ?>
		<span class="search-result__type" aria-label="<?php echo esc_attr( sprintf( __( 'Post type: %s', 'pc4s' ), $post_type_label ) ); ?>">
			<?php echo esc_html( $post_type_label ); ?>
		</span>
		<?php endif; ?>

		<h2 class="search-result__title">
			<a class="search-result__title-link" href="<?php the_permalink(); ?>" rel="bookmark">
				<?php the_title(); ?>
			</a>
		</h2>

		<div class="search-result__meta">
			<time class="search-result__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</div><!-- .search-result__meta -->

		<?php
		$excerpt = get_the_excerpt();
		if ( $excerpt ) :
		?>
		<div class="search-result__excerpt">
			<?php echo wp_kses_post( $excerpt ); ?>
		</div><!-- .search-result__excerpt -->
		<?php endif; ?>

		<a
			class="search-result__read-more"
			href="<?php the_permalink(); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Read more about %s', 'pc4s' ), get_the_title() ) ); ?>"
		>
			<?php esc_html_e( 'Read more', 'pc4s' ); ?>
			<svg
				viewBox="0 0 16 16"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				aria-hidden="true"
				focusable="false"
			>
				<path d="M3 8h10M9 4l4 4-4 4" />
			</svg>
		</a>

	</div><!-- .search-result__body -->

	<?php if ( has_post_thumbnail() ) : ?>
	<div class="search-result__thumbnail" aria-hidden="true">
		<a href="<?php the_permalink(); ?>" tabindex="-1">
			<?php the_post_thumbnail( 'medium', [ 'class' => 'search-result__img' ] ); ?>
		</a>
	</div><!-- .search-result__thumbnail -->
	<?php endif; ?>

</article><!-- #post-<?php the_ID(); ?> -->
