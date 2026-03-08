<?php
/**
 * Template part: News & Articles Section
 *
 * Flexible Content layout name: news
 *
 * ACF sub fields:
 *   title        (text)
 *   subtitle     (text)
 *   posts_count  (number, default: 3)
 *
 * Posts are pulled from the default 'post' post type, ordered by
 * date descending. No static content — everything is dynamic.
 *
 * @package PC4S
 * @subpackage Template_Parts/Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title       = get_sub_field( 'title' );
$subtitle    = get_sub_field( 'subtitle' );
$posts_count = absint( get_sub_field( 'posts_count' ) ) ?: 3;

if ( ! $title ) {
    return;
}

// Only show posts published within the last 3 months.
$cutoff_date = new DateTime( '-3 months' );

$news_query = new WP_Query(
    [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $posts_count,
        'no_found_rows'  => true,
        'date_query'     => [
            [
                'after'     => [
                    'year'  => (int) $cutoff_date->format( 'Y' ),
                    'month' => (int) $cutoff_date->format( 'n' ),
                    'day'   => (int) $cutoff_date->format( 'j' ),
                ],
                'inclusive' => false,
            ],
        ],
    ]
);

if ( ! $news_query->have_posts() ) {
    wp_reset_postdata();
    return;
}
?>

<section class="section news" aria-labelledby="news-heading">
    <div class="wrapper">

        <header class="section__header">
            <h2 id="news-heading" class="section__title">
                <?php echo esc_html( $title ); ?>
            </h2>
            <?php if ( $subtitle ) : ?>
            <p class="section__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
            <?php endif; ?>
        </header>

        <div class="news-content">
            <?php
            while ( $news_query->have_posts() ) :
                $news_query->the_post();
            ?>
            <article class="news-item">
                <h3 class="news-item__title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <div class="news-item__excerpt">
                    <?php the_excerpt(); ?>
                </div>
                <a href="<?php the_permalink(); ?>" class="btn btn--primary">
                    <?php esc_html_e( 'Read More', 'pc4s' ); ?>
                </a>
            </article>
            <?php endwhile; ?>
        </div>

    </div>
</section>

<?php wp_reset_postdata(); ?>
