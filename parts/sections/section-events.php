<?php
/**
 * Template part: Events Preview Section
 *
 * Flexible Content layout name: events
 *
 * ACF sub fields:
 *   badge_text        (text)
 *   title             (text)
 *   subtitle          (text)
 *   view_all_url      (page_link)
 *   event_source      (select: auto|manual)
 *   event_type_rows   (repeater: type_slug + type_count) — visible when auto
 *   featured_events   (relationship → pc4s_event)   — visible when manual
 *
 * Events are queried via PC4S\Classes\EventQuery which orders by the
 * pre-computed _event_next_occurrence meta key.
 *
 * @package PC4S
 * @subpackage Template_Parts/Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PC4S\Classes\EventQuery;

$badge_text   = get_sub_field( 'badge_text' );
$title        = get_sub_field( 'title' );
$subtitle     = get_sub_field( 'subtitle' );
$view_all_url = get_sub_field( 'view_all_url' ) ?: get_permalink( get_page_by_path( 'events' ) );

if ( ! $title ) {
    return;
}

$event_source = get_sub_field( 'event_source' ) ?: 'auto';

if ( 'manual' === $event_source ) {
    // Hand-picked events — respect the admin's ordering.
    $featured_ids = get_sub_field( 'featured_events' ); // returns array of post IDs
    $featured_ids = is_array( $featured_ids ) ? array_map( 'absint', $featured_ids ) : [];

    if ( empty( $featured_ids ) ) {
        return;
    }

    $events_query = EventQuery::get_upcoming( count( $featured_ids ), [], $featured_ids );
} else {
    // Automatic — pull events using per-type row configuration.
    $type_rows = get_sub_field( 'event_type_rows' );

    if ( ! empty( $type_rows ) && is_array( $type_rows ) ) {
        $events_query = EventQuery::get_upcoming_mixed( $type_rows );
    } else {
        // No rows configured — fall back to 4 upcoming events of any type.
        $events_query = EventQuery::get_upcoming( 4 );
    }
}

if ( ! $events_query->have_posts() ) {
    wp_reset_postdata();
    return;
}

$total_events = $events_query->post_count;
?>

<section class="section events-preview" id="events" aria-labelledby="events-heading">
    <div class="wrapper">
        <div class="events-header">
            <div class="section__header section__header--start">

                <?php if ( $badge_text ) : ?>
                <div class="section__badge">
                    <span class="section__badge-icon" aria-hidden="true"></span>
                    <span class="section__badge-text"><?php echo esc_html( $badge_text ); ?></span>
                </div>
                <?php endif; ?>

                <h2 id="events-heading" class="section__title">
                    <?php echo esc_html( $title ); ?>
                </h2>

                <?php if ( $subtitle ) : ?>
                <p class="section__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
                <?php endif; ?>

            </div>

            <?php if ( $view_all_url ) : ?>
            <a href="<?php echo esc_url( $view_all_url ); ?>" class="view-all-link" aria-label="<?php esc_attr_e( 'View all upcoming events', 'pc4s' ); ?>">
                <?php esc_html_e( 'View All Events', 'pc4s' ); ?>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
            <?php endif; ?>

        </div>
    </div>

    <div class="wrapper">
        <div class="events-slider" role="region" aria-label="<?php esc_attr_e( 'Upcoming events slider', 'pc4s' ); ?>" aria-roledescription="carousel">

            <button class="events-nav events-nav--prev" type="button" aria-label="<?php esc_attr_e( 'Previous event', 'pc4s' ); ?>" aria-controls="events-track">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>

            <ol class="events-track" id="events-track" aria-live="polite">
                <?php
                $i = 0;
                while ( $events_query->have_posts() ) :
                    $events_query->the_post();
                    $i++;

                    // Use the pre-computed next-occurrence date so recurring events
                    // always display their upcoming date, not the original base date.
                    $event_date       = (string) get_post_meta( get_the_ID(), EventQuery::NEXT_OCC_META, true )
                                        ?: (string) get_field( 'event_date' ); // Y-m-d
                    $event_start      = get_field( 'event_start_time' ); // H:i
                    $event_end        = get_field( 'event_end_time' );   // H:i
                    $event_location   = get_field( 'event_location' );
                    $event_cta_url    = get_field( 'event_cta_url' ) ?: get_permalink();
                    $event_cta_text   = get_field( 'event_cta_text' ) ?: __( 'Find Out More', 'pc4s' );
                    $event_day        = $event_date ? gmdate( 'j', strtotime( $event_date ) ) : '';
                    $event_month      = $event_date ? strtoupper( gmdate( 'M', strtotime( $event_date ) ) ) : '';
                    $datetime_start   = $event_date && $event_start ? esc_attr( $event_date . 'T' . $event_start ) : '';
                    $datetime_end     = $event_date && $event_end   ? esc_attr( $event_date . 'T' . $event_end )   : '';

                    /* translators: 1: event number, 2: total events, 3: event title */
                    $slide_label = sprintf(
                        esc_attr__( 'Event %1$d of %2$d: %3$s', 'pc4s' ),
                        $i,
                        $total_events,
                        get_the_title()
                    );
                ?>
                <li class="event-slide" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( $slide_label ); ?>">

                    <div class="event-slide-panel" aria-hidden="true">
                        <?php if ( $event_day && $event_month ) : ?>
                        <div class="event-badge">
                            <span class="event-badge-day"><?php echo esc_html( $event_day ); ?></span>
                            <span class="event-badge-month"><?php echo esc_html( $event_month ); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <article class="event-info-card">
                        <h3 class="event-title"><?php the_title(); ?></h3>

                        <ul class="event-meta" role="list" aria-label="<?php esc_attr_e( 'Event details', 'pc4s' ); ?>">

                            <?php if ( $event_start ) : ?>
                            <li class="event-meta-item">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>
                                    <?php if ( $datetime_start ) : ?>
                                    <time datetime="<?php echo esc_attr( $datetime_start ); ?>">
                                        <?php echo esc_html( gmdate( 'g:i A', strtotime( $event_start ) ) ); ?>
                                    </time>
                                    <?php else : ?>
                                    <?php echo esc_html( gmdate( 'g:i A', strtotime( $event_start ) ) ); ?>
                                    <?php endif; ?>
                                    <?php if ( $event_end ) : ?>
                                    &ndash;
                                    <?php if ( $datetime_end ) : ?>
                                    <time datetime="<?php echo esc_attr( $datetime_end ); ?>">
                                        <?php echo esc_html( gmdate( 'g:i A', strtotime( $event_end ) ) ); ?>
                                    </time>
                                    <?php else : ?>
                                    <?php echo esc_html( gmdate( 'g:i A', strtotime( $event_end ) ) ); ?>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <?php endif; ?>

                            <?php if ( $event_location ) : ?>
                            <li class="event-meta-item">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span><?php echo esc_html( $event_location ); ?></span>
                            </li>
                            <?php endif; ?>

                        </ul>

                        <a href="<?php echo esc_url( $event_cta_url ); ?>" class="btn btn--primary"
                           aria-label="<?php echo esc_attr( sprintf( __( 'Find out more about %s', 'pc4s' ), get_the_title() ) ); ?>">
                            <?php echo esc_html( $event_cta_text ); ?>
                        </a>

                    </article>
                </li>
                <?php endwhile; ?>
            </ol>

            <button class="events-nav events-nav--next" type="button" aria-label="<?php esc_attr_e( 'Next event', 'pc4s' ); ?>" aria-controls="events-track">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>

        </div>
    </div>
</section>

<?php wp_reset_postdata(); ?>
