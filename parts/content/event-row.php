<?php
/**
 * Template part: Event Row
 *
 * Renders a single event as a calendar list row inside the events archive.
 * Must be called after `setup_postdata( $GLOBALS['post'] = $event_post )`.
 *
 * ACF fields consumed (read once per call — no duplicate queries):
 *   _event_next_occurrence  (post meta, pre-computed)
 *   event_date              (ACF date_picker, Y-m-d  — fallback)
 *   event_start_time        (ACF time_picker, H:i)
 *   event_location          (ACF text)
 *   event_details           (ACF wysiwyg  — trimmed to excerpt)
 *   event_cta_url           (ACF page_link — optional override)
 *   event_cta_text          (ACF text      — optional override)
 *
 * @package PC4S
 * @subpackage Template_Parts/Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Classes\EventQuery;

// ---------------------------------------------------------------------------
// Resolve fields — one read per field, stored in local variables.
// ---------------------------------------------------------------------------
$event_id   = (int) get_the_ID();
$title      = get_the_title();
$permalink  = (string) get_permalink();

// The archive passes the specific occurrence date via $args so that recurring
// events display the correct date for each individual occurrence rather than
// always showing the next-occurrence meta value.
$occurrence_date = isset( $args['occurrence_date'] ) ? (string) $args['occurrence_date'] : '';

// Resolution order: explicit occurrence date → pre-computed meta → ACF field.
$next_occ   = $occurrence_date ?: (string) get_post_meta( $event_id, EventQuery::NEXT_OCC_META, true );
$event_date = $next_occ ?: (string) get_field( 'event_date' ); // Y-m-d
$event_ts   = $event_date ? strtotime( $event_date ) : 0;

$start_time = (string) get_field( 'event_start_time' ); // H:i
$location   = (string) get_field( 'event_location' );

// Short excerpt from the WYSIWYG details field.
$details_raw = (string) get_field( 'event_details' );
$excerpt     = $details_raw
	? wp_trim_words( wp_strip_all_tags( $details_raw ), 20, '&hellip;' )
	: (string) get_the_excerpt();

$cta_url  = (string) ( get_field( 'event_cta_url' ) ?: $permalink );
$cta_text = (string) ( get_field( 'event_cta_text' ) ?: __( 'Learn More', 'pc4s' ) );

// ---------------------------------------------------------------------------
// Build derived display values — no logic inside the markup below.
// ---------------------------------------------------------------------------

// Machine-readable datetime attribute: 2026-03-05T18:00
$dt_attr = '';
if ( $event_ts && $start_time ) {
	$dt_attr = gmdate( 'Y-m-d', $event_ts ) . 'T' . $start_time;
} elseif ( $event_ts ) {
	$dt_attr = gmdate( 'Y-m-d', $event_ts );
}

// Human-readable display: "March 5 @ 6:00 pm"
$display_date = '';
if ( $event_ts ) {
	$display_date = $start_time
		? gmdate( 'F j', $event_ts ) . ' @ ' . gmdate( 'g:i a', strtotime( $start_time ) )
		: gmdate( 'F j, Y', $event_ts );
}

// Day-of-week and day number for the date block.
$dow = $event_ts ? strtoupper( gmdate( 'D', $event_ts ) ) : '';
$day = $event_ts ? gmdate( 'j', $event_ts ) : '';
?>

<li class="event-row">

	<?php if ( $event_ts ) : ?>
	<div class="event-row__date" aria-hidden="true">
		<span class="event-row__dow"><?php echo esc_html( $dow ); ?></span>
		<span class="event-row__day"><?php echo esc_html( $day ); ?></span>
	</div>
	<?php endif; ?>

	<article
		class="event-row__card"
		aria-labelledby="evt-<?php echo esc_attr( (string) $event_id ); ?>"
	>

		<?php if ( $display_date ) : ?>
		<p class="event-row__datetime">
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="12" cy="12" r="10"></circle>
				<polyline points="12 6 12 12 16 14"></polyline>
			</svg>
			<time <?php echo $dt_attr ? 'datetime="' . esc_attr( $dt_attr ) . '"' : ''; ?>>
				<?php echo esc_html( $display_date ); ?>
			</time>
		</p>
		<?php endif; ?>

		<h4
			id="evt-<?php echo esc_attr( (string) $event_id ); ?>"
			class="event-row__title"
		>
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
		</h4>

		<?php if ( $location ) : ?>
		<p class="event-row__location">
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
				<circle cx="12" cy="10" r="3"></circle>
			</svg>
			<span><?php echo esc_html( $location ); ?></span>
		</p>
		<?php endif; ?>

		<?php if ( $excerpt ) : ?>
		<p class="event-row__desc"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>

		<a href="<?php echo esc_url( $cta_url ); ?>" class="event-row__link">
			<?php echo esc_html( $cta_text ); ?>
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="5" y1="12" x2="19" y2="12"></line>
				<polyline points="12 5 19 12 12 19"></polyline>
			</svg>
		</a>

	</article>

	<div class="event-row__media" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'medium', [ 'class' => 'event-row__img' ] ); ?>
		<?php endif; ?>
	</div>

</li><!-- .event-row -->
