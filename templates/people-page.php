<?php
/**
 * Template Name: Our People
 * Template Post Type: page
 *
 * Page template for the Our People page.
 *
 * Sections rendered (in order):
 *   1. Page Banner         — parts/content/page-banner.php
 *   2. Staff Section       — badge, heading, subtitle + Staff CPT grid
 *   3. Governing Board     — badge, heading, subtitle + ACF repeater
 *   4. Advisory Board      — badge, heading, subtitle + ACF repeater
 *   5. Page CTA            — badge, heading, subtitle, two action links
 *
 * ACF field group: group_people_page (acf-json/group_people_page.json)
 *   – Manages all editable page-level content. Prefix: pp_
 *
 * Staff CPT field group: group_staff (acf-json/group_staff.json)
 *   – Manages per-staff-member meta (job title, phone, email). Prefix: staff_
 *   – Staff name comes from the post title.
 *   – Staff photo comes from the featured image.
 *
 * Staff query is executed once and cached locally.
 * Board members are fully ACF-managed (no CPT).
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Resolve all page-level ACF fields once — zero duplicate DB calls in markup.
// ---------------------------------------------------------------------------

// --- Staff Section ---
$pp_staff_badge    = (string) get_field( 'pp_staff_badge' );
$pp_staff_heading  = (string) get_field( 'pp_staff_heading' );
$pp_staff_subtitle = (string) get_field( 'pp_staff_subtitle' );

// --- Governing Board Section ---
$pp_gov_badge    = (string) get_field( 'pp_gov_badge' );
$pp_gov_heading  = (string) get_field( 'pp_gov_heading' );
$pp_gov_subtitle = (string) get_field( 'pp_gov_subtitle' );
$pp_gov_members  = (array)  get_field( 'pp_gov_members' ) ?: [];

// --- Advisory Board Section ---
$pp_adv_badge    = (string) get_field( 'pp_adv_badge' );
$pp_adv_heading  = (string) get_field( 'pp_adv_heading' );
$pp_adv_subtitle = (string) get_field( 'pp_adv_subtitle' );
$pp_adv_members  = (array)  get_field( 'pp_adv_members' ) ?: [];

// --- CTA Section ---
$pp_cta_badge     = (string) get_field( 'pp_cta_badge' );
$pp_cta_heading   = (string) get_field( 'pp_cta_heading' );
$pp_cta_subtitle  = (string) get_field( 'pp_cta_subtitle' );
$pp_cta_primary   = get_field( 'pp_cta_primary' );   // link array|null
$pp_cta_secondary = get_field( 'pp_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Staff Query — executed once, cached locally.
// Results ordered by menu_order (drag-and-drop in admin) then title.
// ---------------------------------------------------------------------------
$staff_query = new WP_Query( [
	'post_type'              => \PC4S\Classes\PostTypes\Staff::POST_TYPE,
	'post_status'            => 'publish',
	'posts_per_page'         => -1,
	'orderby'                => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	'no_found_rows'          => true,   // Skip pagination count query.
	'update_post_term_cache' => false,  // No taxonomies on this CPT.
] );

$has_staff = $staff_query->have_posts();

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main-content" class="site-main" role="main">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION 2: Staff — badge, heading, subtitle + CPT grid
	   ================================================================ */ ?>
	<?php if ( $pp_staff_heading || $has_staff ) : ?>
	<section class="section staff-section" aria-labelledby="staff-heading">
		<div class="wrapper">

			<?php if ( $pp_staff_badge || $pp_staff_heading || $pp_staff_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $pp_staff_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $pp_staff_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $pp_staff_heading ) : ?>
				<h2 id="staff-heading" class="section__title">
					<?php echo esc_html( $pp_staff_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $pp_staff_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $pp_staff_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $has_staff ) : ?>
			<ul class="staff-grid" role="list" aria-label="<?php esc_attr_e( 'PC4S staff members', 'pc4s' ); ?>">

				<?php while ( $staff_query->have_posts() ) :
					$staff_query->the_post();

					$member_name  = get_the_title();
					$member_title = (string) ( function_exists( 'get_field' ) ? get_field( 'staff_job_title' ) : '' );
					$member_phone = (string) ( function_exists( 'get_field' ) ? get_field( 'staff_phone' )     : '' );
					$member_email = (string) ( function_exists( 'get_field' ) ? get_field( 'staff_email' )     : '' );
					$has_photo    = has_post_thumbnail();
					$photo_url    = $has_photo ? get_the_post_thumbnail_url( null, 'medium' ) : '';
					$photo_alt    = ''; // Decorative — name shown in overlay and body.
				?>
				<li class="staff-card">
					<article class="staff-card__inner" tabindex="0">

						<div class="staff-card__photo-wrap" aria-hidden="true">

							<?php if ( $has_photo ) : ?>
							<img
								src="<?php echo esc_url( $photo_url ); ?>"
								alt="<?php echo esc_attr( $photo_alt ); ?>"
								class="staff-card__photo"
								loading="lazy"
								decoding="async"
							/>
							<?php endif; ?>

							<?php if ( $member_phone || $member_email ) : ?>
							<div class="staff-card__overlay">
								<div class="staff-card__contact">

									<?php if ( $member_name ) : ?>
									<p class="staff-card__contact-name"><?php echo esc_html( $member_name ); ?></p>
									<?php endif; ?>

									<?php if ( $member_phone ) : ?>
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $member_phone ) ); ?>" class="staff-card__contact-link">
										<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
										<span><?php echo esc_html( $member_phone ); ?></span>
									</a>
									<?php endif; ?>

									<?php if ( $member_email ) : ?>
									<a href="mailto:<?php echo esc_attr( sanitize_email( $member_email ) ); ?>" class="staff-card__contact-link">
										<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
										<span><?php echo esc_html( $member_email ); ?></span>
									</a>
									<?php endif; ?>

								</div><!-- .staff-card__contact -->
							</div><!-- .staff-card__overlay -->
							<?php endif; ?>

						</div><!-- .staff-card__photo-wrap -->

						<div class="staff-card__body">
							<?php if ( $member_name ) : ?>
							<p class="staff-card__name"><?php echo esc_html( $member_name ); ?></p>
							<?php endif; ?>
							<?php if ( $member_title ) : ?>
							<p class="staff-card__title"><?php echo esc_html( $member_title ); ?></p>
							<?php endif; ?>
						</div><!-- .staff-card__body -->

					</article>
				</li>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>

			</ul><!-- .staff-grid -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .staff-section -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 3: Governing Board — badge, heading, subtitle + ACF repeater
	   ================================================================ */ ?>
	<?php if ( $pp_gov_heading || $pp_gov_members ) : ?>
	<section class="section board-section" aria-labelledby="governing-board-heading">
		<div class="wrapper">

			<?php if ( $pp_gov_badge || $pp_gov_heading || $pp_gov_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $pp_gov_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $pp_gov_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $pp_gov_heading ) : ?>
				<h2 id="governing-board-heading" class="section__title">
					<?php echo esc_html( $pp_gov_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $pp_gov_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $pp_gov_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $pp_gov_members ) : ?>
			<ul class="board-grid" role="list" aria-label="<?php esc_attr_e( 'Governing board members', 'pc4s' ); ?>">
				<?php foreach ( $pp_gov_members as $member ) :
					$name        = (string) ( $member['pp_board_name']        ?? '' );
					$affiliation = (string) ( $member['pp_board_affiliation']  ?? '' );
					if ( ! $name ) { continue; }
				?>
				<li class="board-card board-card--governing">
					<p class="board-card__name"><?php echo esc_html( $name ); ?></p>
					<?php if ( $affiliation ) : ?>
					<p class="board-card__affiliation"><?php echo esc_html( $affiliation ); ?></p>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul><!-- .board-grid -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .board-section -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 4: Advisory Board — badge, heading, subtitle + ACF repeater
	   ================================================================ */ ?>
	<?php if ( $pp_adv_heading || $pp_adv_members ) : ?>
	<section class="section advisory-section" aria-labelledby="advisory-board-heading">
		<div class="wrapper">

			<?php if ( $pp_adv_badge || $pp_adv_heading || $pp_adv_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $pp_adv_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $pp_adv_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $pp_adv_heading ) : ?>
				<h2 id="advisory-board-heading" class="section__title">
					<?php echo esc_html( $pp_adv_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $pp_adv_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $pp_adv_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $pp_adv_members ) : ?>
			<ul class="board-grid board-grid--advisory" role="list" aria-label="<?php esc_attr_e( 'Advisory board members', 'pc4s' ); ?>">
				<?php foreach ( $pp_adv_members as $member ) :
					$name        = (string) ( $member['pp_board_name']        ?? '' );
					$affiliation = (string) ( $member['pp_board_affiliation']  ?? '' );
					if ( ! $name ) { continue; }
				?>
				<li class="board-card board-card--advisory">
					<p class="board-card__name"><?php echo esc_html( $name ); ?></p>
					<?php if ( $affiliation ) : ?>
					<p class="board-card__affiliation"><?php echo esc_html( $affiliation ); ?></p>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul><!-- .board-grid.board-grid--advisory -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .advisory-section -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 5: Page CTA — badge, heading, subtitle, two action links
	   ================================================================ */ ?>
	<?php if ( $pp_cta_heading || $pp_cta_primary || $pp_cta_secondary ) : ?>
	<section class="section page-cta" aria-labelledby="people-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $pp_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $pp_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $pp_cta_heading ) : ?>
				<h2 id="people-cta-heading" class="section__title">
					<?php echo esc_html( $pp_cta_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $pp_cta_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $pp_cta_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $pp_cta_primary || $pp_cta_secondary ) : ?>
				<div class="page-cta__actions">

					<?php if ( $pp_cta_primary ) :
						$cta_p_url    = esc_url( $pp_cta_primary['url'] ?? '' );
						$cta_p_title  = esc_html( $pp_cta_primary['title'] ?? '' );
						$cta_p_target = ! empty( $pp_cta_primary['target'] )
							? ' target="' . esc_attr( $pp_cta_primary['target'] ) . '" rel="noopener noreferrer"'
							: '';
					?>
					<a href="<?php echo $cta_p_url; ?>" class="btn btn--primary"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( $pp_cta_secondary ) :
						$cta_s_url    = esc_url( $pp_cta_secondary['url'] ?? '' );
						$cta_s_title  = esc_html( $pp_cta_secondary['title'] ?? '' );
						$cta_s_target = ! empty( $pp_cta_secondary['target'] )
							? ' target="' . esc_attr( $pp_cta_secondary['target'] ) . '" rel="noopener noreferrer"'
							: '';
					?>
					<a href="<?php echo $cta_s_url; ?>" class="btn btn--outline"<?php echo $cta_s_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php echo $cta_s_title; ?>
					</a>
					<?php endif; ?>

				</div><!-- .page-cta__actions -->
				<?php endif; ?>

			</div><!-- .section__header -->
		</div><!-- .wrapper -->
	</section><!-- .page-cta -->
	<?php endif; ?>

</main><!-- #main-content -->

<?php get_footer(); ?>
