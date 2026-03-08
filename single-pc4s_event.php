<?php
/**
 * Template for displaying a single pc4s_event post.
 *
 * Layout:
 *   - Page banner (title + breadcrumbs)
 *   - Two-column body:
 *       Left  — featured image + Event Details WYSIWYG
 *       Right — event meta card (date / time / location) + CTA button
 *
 * @package PC4S
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

<!-- ── Page Banner ──────────────────────────────────────────────────── -->
<?php get_template_part( 'parts/content/page-banner' ); ?>

<!-- ── Event Body ───────────────────────────────────────────────────── -->
<main id="main-content" class="event-single">
	<div class="wrapper">
		<div class="event-single__inner">

			<!-- ── Main Content ──────────────────────────────── -->
			<article class="event-single__content" id="post-<?php the_ID(); ?>" <?php post_class( 'event-single__article' ); ?>>

				<?php if ( has_post_thumbnail() ) : ?>
				<div class="event-single__image">
					<?php the_post_thumbnail( 'large', [ 'class' => 'event-single__featured-img', 'loading' => 'eager' ] ); ?>
				</div>
				<?php endif; ?>

				<?php
				$event_details = get_field( 'event_details' );
				if ( $event_details ) :
				?>
				<div class="event-single__details entry-content">
					<?php echo wp_kses_post( $event_details ); ?>
				</div>
				<?php endif; ?>

				<?php if ( get_edit_post_link() ) : ?>
				<footer class="entry-footer">
					<?php
					edit_post_link(
						sprintf(
							wp_kses(
								/* translators: %s: event title */
								__( 'Edit <span class="screen-reader-text">%s</span>', 'pc4s' ),
								[ 'span' => [ 'class' => [] ] ]
							),
							wp_kses_post( get_the_title() )
						),
						'<span class="edit-link">',
						'</span>'
					);
					?>
				</footer>
				<?php endif; ?>

			</article>

			<!-- ── Sidebar / Event Meta Card ─────────────────── -->
			<aside class="event-single__sidebar" aria-label="<?php esc_attr_e( 'Event details', 'pc4s' ); ?>">
				<div class="event-meta-card">

					<h2 class="event-meta-card__heading"><?php esc_html_e( 'Event Details', 'pc4s' ); ?></h2>

					<?php
					$event_date     = get_field( 'event_date' );       // Y-m-d
					$event_start    = get_field( 'event_start_time' ); // H:i
					$event_end      = get_field( 'event_end_time' );   // H:i
					$event_location = get_field( 'event_location' );
					$event_cta_url  = get_field( 'event_cta_url' ) ?: get_permalink();
					$event_cta_text = get_field( 'event_cta_text' ) ?: __( 'Register / Learn More', 'pc4s' );

					$is_recurring     = get_field( 'is_recurring' );
					$recurrence_rule  = get_field( 'recurrence_rule' );

					// Recurrence labels.
					$recurrence_labels = [
						'weekly'   => __( 'Every week', 'pc4s' ),
						'biweekly' => __( 'Every two weeks', 'pc4s' ),
						'monthly'  => __( 'Every month', 'pc4s' ),
					];
					?>

					<ul class="event-meta-card__list" role="list">

						<?php if ( $event_date ) :
							$ts           = strtotime( $event_date );
							$display_date = gmdate( 'l, F j, Y', $ts );
							$datetime_val = $event_date;
						?>
						<li class="event-meta-card__item">
							<span class="event-meta-card__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
									<line x1="16" y1="2" x2="16" y2="6"></line>
									<line x1="8" y1="2" x2="8" y2="6"></line>
									<line x1="3" y1="10" x2="21" y2="10"></line>
								</svg>
							</span>
							<span class="event-meta-card__label">
								<time datetime="<?php echo esc_attr( $datetime_val ); ?>"><?php echo esc_html( $display_date ); ?></time>
								<?php if ( $is_recurring && $recurrence_rule && isset( $recurrence_labels[ $recurrence_rule ] ) ) : ?>
								<span class="event-meta-card__recurrence">
									<?php echo esc_html( $recurrence_labels[ $recurrence_rule ] ); ?>
								</span>
								<?php endif; ?>
							</span>
						</li>
						<?php endif; ?>

						<?php if ( $event_start ) :
							$datetime_start = $event_date ? $event_date . 'T' . $event_start : '';
							$datetime_end   = $event_date && $event_end ? $event_date . 'T' . $event_end : '';
						?>
						<li class="event-meta-card__item">
							<span class="event-meta-card__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<circle cx="12" cy="12" r="10"></circle>
									<polyline points="12 6 12 12 16 14"></polyline>
								</svg>
							</span>
							<span class="event-meta-card__label">
								<?php if ( $datetime_start ) : ?>
								<time datetime="<?php echo esc_attr( $datetime_start ); ?>"><?php echo esc_html( gmdate( 'g:i A', strtotime( $event_start ) ) ); ?></time>
								<?php else : ?>
								<?php echo esc_html( gmdate( 'g:i A', strtotime( $event_start ) ) ); ?>
								<?php endif; ?>
								<?php if ( $event_end ) : ?>
								<span aria-hidden="true"> &ndash; </span>
								<?php if ( $datetime_end ) : ?>
								<time datetime="<?php echo esc_attr( $datetime_end ); ?>"><?php echo esc_html( gmdate( 'g:i A', strtotime( $event_end ) ) ); ?></time>
								<?php else : ?>
								<?php echo esc_html( gmdate( 'g:i A', strtotime( $event_end ) ) ); ?>
								<?php endif; ?>
								<?php endif; ?>
							</span>
						</li>
						<?php endif; ?>

						<?php if ( $event_location ) : ?>
						<li class="event-meta-card__item">
							<span class="event-meta-card__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
									<circle cx="12" cy="10" r="3"></circle>
								</svg>
							</span>
							<span class="event-meta-card__label"><?php echo esc_html( $event_location ); ?></span>
						</li>
						<?php endif; ?>

					</ul>

					<a href="<?php echo esc_url( $event_cta_url ); ?>" class="btn btn--primary event-meta-card__cta">
						<?php echo esc_html( $event_cta_text ); ?>
					</a>

				</div><!-- .event-meta-card -->
			</aside>

		</div><!-- .event-single__inner -->
	</div><!-- .wrapper -->
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
