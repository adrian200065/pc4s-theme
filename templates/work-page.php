<?php
/**
 * Template Name: Our Work
 * Template Post Type: page
 *
 * Page template for the Our Work page.
 *
 * Sections rendered (in order):
 *   1. Page Banner          — parts/content/page-banner.php
 *   2. Mission & Goals      — two-column: mission (badge/heading/lead) + goals (heading/body)
 *   3. Approach             — badge, heading, subtitle + approach cards (repeater)
 *   4. Programs             — badge, heading, subtitle + program cards (repeater)
 *   5. Primary Prevention   — split: image left + badge/heading/bullet list right
 *   6. Compliance & Docs    — two-column: Title VI content + documents link list (repeater)
 *   7. Gallery              — badge, heading, subtitle + image grid (repeater)
 *   8. Page CTA             — badge, heading, subtitle, two action links
 *
 * ACF field group: group_work_page (acf-json/group_work_page.json)
 *   – Manages all editable content. No hardcoded strings in markup.
 *   – Field prefix: owp_ (our work page).
 *
 * @package    PC4S
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Resolve all ACF fields once — zero duplicate DB calls in the markup below.
// ---------------------------------------------------------------------------

// --- Mission & Goals Section ---
$owp_mission_badge   = (string) get_field( 'owp_mission_badge' );
$owp_mission_heading = (string) get_field( 'owp_mission_heading' );
$owp_mission_lead    = (string) get_field( 'owp_mission_lead' );
$owp_goals_heading   = (string) get_field( 'owp_goals_heading' );
$owp_goals_body      = (string) get_field( 'owp_goals_body' );   // wysiwyg

// --- Approach Section ---
$owp_approach_badge     = (string) get_field( 'owp_approach_badge' );
$owp_approach_heading   = (string) get_field( 'owp_approach_heading' );
$owp_approach_subtitle  = (string) get_field( 'owp_approach_subtitle' );
$owp_approach_cards     = (array)  get_field( 'owp_approach_cards' ) ?: [];

// --- Programs Section ---
$owp_programs_badge    = (string) get_field( 'owp_programs_badge' );
$owp_programs_heading  = (string) get_field( 'owp_programs_heading' );
$owp_programs_subtitle = (string) get_field( 'owp_programs_subtitle' );
$owp_programs          = (array)  get_field( 'owp_programs' ) ?: [];

// --- Primary Prevention Section ---
$owp_prev_badge    = (string) get_field( 'owp_prev_badge' );
$owp_prev_heading  = (string) get_field( 'owp_prev_heading' );
$owp_prev_image    = get_field( 'owp_prev_image' );   // image array|null
$owp_prev_items    = (array)  get_field( 'owp_prev_items' ) ?: [];

// --- Compliance & Documents Section ---
$owp_title6_badge    = (string) get_field( 'owp_title6_badge' );
$owp_title6_heading  = (string) get_field( 'owp_title6_heading' );
$owp_title6_body     = (string) get_field( 'owp_title6_body' );  // wysiwyg
$owp_docs_badge      = (string) get_field( 'owp_docs_badge' );
$owp_docs_heading    = (string) get_field( 'owp_docs_heading' );
$owp_docs_intro      = (string) get_field( 'owp_docs_intro' );
$owp_documents       = (array)  get_field( 'owp_documents' ) ?: [];

// --- Gallery Section ---
$owp_gallery_badge    = (string) get_field( 'owp_gallery_badge' );
$owp_gallery_heading  = (string) get_field( 'owp_gallery_heading' );
$owp_gallery_subtitle = (string) get_field( 'owp_gallery_subtitle' );
$owp_gallery_items    = (array)  get_field( 'owp_gallery_items' ) ?: [];

// --- CTA Section ---
$owp_cta_badge     = (string) get_field( 'owp_cta_badge' );
$owp_cta_heading   = (string) get_field( 'owp_cta_heading' );
$owp_cta_subtitle  = (string) get_field( 'owp_cta_subtitle' );
$owp_cta_primary   = get_field( 'owp_cta_primary' );   // link array|null
$owp_cta_secondary = get_field( 'owp_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Inline SVG — checkmark icon reused in the Primary Prevention bullet list.
// ---------------------------------------------------------------------------
$check_icon = '<svg class="work-prevention__check" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false"><polyline points="20 6 9 17 4 12"/></svg>';

// ---------------------------------------------------------------------------
// Inline SVG — document icon reused in the Documents link list.
// ---------------------------------------------------------------------------
$doc_icon   = '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
$arrow_icon = '<svg class="work-docs-list__arrow" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<main id="main-content" class="site-main" role="main">

	<?php get_template_part( 'parts/content/page-banner' ); ?>

	<?php /* ================================================================
	   SECTION 2: Mission & Goals — two-column split
	   ================================================================ */ ?>
	<?php if ( $owp_mission_heading || $owp_goals_heading ) : ?>
	<section class="section work-intro" aria-labelledby="work-intro-heading">
		<div class="wrapper">
			<div class="work-intro__grid">

				<?php if ( $owp_mission_heading || $owp_mission_lead ) : ?>
				<div class="work-intro__mission">

					<?php if ( $owp_mission_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $owp_mission_badge ); ?></span>
					</div>
					<?php endif; ?>

					<?php if ( $owp_mission_heading ) : ?>
					<h2 id="work-intro-heading" class="section__title">
						<?php echo esc_html( $owp_mission_heading ); ?>
					</h2>
					<?php endif; ?>

					<?php if ( $owp_mission_lead ) : ?>
					<p class="work-intro__lead"><?php echo esc_html( $owp_mission_lead ); ?></p>
					<?php endif; ?>

				</div><!-- .work-intro__mission -->
				<?php endif; ?>

				<?php if ( $owp_goals_heading || $owp_goals_body ) : ?>
				<div class="work-intro__goals">

					<?php if ( $owp_goals_heading ) : ?>
					<h3 class="section__title"><?php echo esc_html( $owp_goals_heading ); ?></h3>
					<?php endif; ?>

					<?php if ( $owp_goals_body ) : ?>
					<div class="work-intro__goals-body">
						<?php echo wp_kses_post( $owp_goals_body ); ?>
					</div>
					<?php endif; ?>

				</div><!-- .work-intro__goals -->
				<?php endif; ?>

			</div><!-- .work-intro__grid -->
		</div><!-- .wrapper -->
	</section><!-- .work-intro -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 3: Approach — badge, heading, subtitle + cards repeater
	   ================================================================ */ ?>
	<?php if ( $owp_approach_heading || $owp_approach_cards ) : ?>
	<section class="section work-approach" aria-labelledby="approach-heading">
		<div class="wrapper">

			<?php if ( $owp_approach_badge || $owp_approach_heading || $owp_approach_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $owp_approach_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $owp_approach_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $owp_approach_heading ) : ?>
				<h2 id="approach-heading" class="section__title">
					<?php echo esc_html( $owp_approach_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $owp_approach_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $owp_approach_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $owp_approach_cards ) : ?>
			<div class="approach-cards" role="list">
				<?php foreach ( $owp_approach_cards as $card ) :
					$card_title = (string) ( $card['owp_approach_card_title'] ?? '' );
					$card_body  = (string) ( $card['owp_approach_card_body']  ?? '' );
					if ( ! $card_title && ! $card_body ) { continue; }
				?>
				<article class="approach-card" role="listitem">
					<?php if ( $card_title ) : ?>
					<h3 class="approach-card__title"><?php echo esc_html( $card_title ); ?></h3>
					<?php endif; ?>
					<?php if ( $card_body ) : ?>
					<div class="approach-card__body">
						<?php echo wp_kses_post( $card_body ); ?>
					</div>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div><!-- .approach-cards -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .work-approach -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 4: Programs — badge, heading, subtitle + program cards (repeater)
	   ================================================================ */ ?>
	<?php if ( $owp_programs_heading || $owp_programs ) : ?>
	<section class="section work-programs" aria-labelledby="programs-heading">
		<div class="wrapper">

			<?php if ( $owp_programs_badge || $owp_programs_heading || $owp_programs_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $owp_programs_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $owp_programs_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $owp_programs_heading ) : ?>
				<h2 id="programs-heading" class="section__title">
					<?php echo esc_html( $owp_programs_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $owp_programs_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $owp_programs_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $owp_programs ) : ?>
			<ul class="program-list" role="list">
				<?php foreach ( $owp_programs as $index => $program ) :
					$prog_title = (string) ( $program['owp_program_title'] ?? '' );
					$prog_desc  = (string) ( $program['owp_program_desc']  ?? '' );
					if ( ! $prog_title && ! $prog_desc ) { continue; }
					$prog_id = 'prog-' . ( $index + 1 );
				?>
				<li class="program-item">
					<article class="program-card" aria-labelledby="<?php echo esc_attr( $prog_id ); ?>">
						<div class="program-card__accent" aria-hidden="true"></div>
						<div class="program-card__body">
							<?php if ( $prog_title ) : ?>
							<h3 id="<?php echo esc_attr( $prog_id ); ?>" class="program-card__title">
								<?php echo esc_html( $prog_title ); ?>
							</h3>
							<?php endif; ?>
							<?php if ( $prog_desc ) : ?>
							<div class="program-card__desc">
								<?php echo wp_kses_post( $prog_desc ); ?>
							</div>
							<?php endif; ?>
						</div>
					</article>
				</li>
				<?php endforeach; ?>
			</ul><!-- .program-list -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .work-programs -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 5: Primary Prevention — image + badge/heading/bullet list
	   ================================================================ */ ?>
	<?php if ( $owp_prev_heading || $owp_prev_items || $owp_prev_image ) : ?>
	<section class="section work-prevention" aria-labelledby="prevention-heading">
		<div class="wrapper">
			<div class="work-prevention__inner">

				<?php if ( $owp_prev_image ) :
					$prev_img_url = esc_url( $owp_prev_image['url'] ?? '' );
					$prev_img_alt = esc_attr( $owp_prev_image['alt'] ?? '' );
				?>
				<div class="work-prevention__media" aria-hidden="true">
					<img
						src="<?php echo $prev_img_url; ?>"
						alt="<?php echo $prev_img_alt; ?>"
						class="work-prevention__img"
						loading="lazy"
						decoding="async"
					/>
				</div>
				<?php endif; ?>

				<div class="work-prevention__content">

					<?php if ( $owp_prev_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $owp_prev_badge ); ?></span>
					</div>
					<?php endif; ?>

					<?php if ( $owp_prev_heading ) : ?>
					<h2 id="prevention-heading" class="work-prevention__title">
						<?php echo esc_html( $owp_prev_heading ); ?>
					</h2>
					<?php endif; ?>

					<?php if ( $owp_prev_items ) : ?>
					<ul class="work-prevention__list" role="list">
						<?php foreach ( $owp_prev_items as $item ) :
							$item_text = (string) ( $item['owp_prev_item_text'] ?? '' );
							if ( ! $item_text ) { continue; }
						?>
						<li class="work-prevention__list-item">
							<?php echo $check_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $item_text ); ?></span>
						</li>
						<?php endforeach; ?>
					</ul><!-- .work-prevention__list -->
					<?php endif; ?>

				</div><!-- .work-prevention__content -->

			</div><!-- .work-prevention__inner -->
		</div><!-- .wrapper -->
	</section><!-- .work-prevention -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 6: Compliance & Documents — two-column split
	   ================================================================ */ ?>
	<?php if ( $owp_title6_heading || $owp_docs_heading ) : ?>
	<section class="section work-compliance" aria-labelledby="compliance-heading">
		<div class="wrapper">
			<div class="work-compliance__grid">

				<?php if ( $owp_title6_heading || $owp_title6_body ) : ?>
				<div class="work-compliance__title6">

					<?php if ( $owp_title6_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $owp_title6_badge ); ?></span>
					</div>
					<?php endif; ?>

					<?php if ( $owp_title6_heading ) : ?>
					<h2 id="compliance-heading" class="section__title">
						<?php echo esc_html( $owp_title6_heading ); ?>
					</h2>
					<?php endif; ?>

					<?php if ( $owp_title6_body ) : ?>
					<div class="work-compliance__body">
						<?php echo wp_kses_post( $owp_title6_body ); ?>
					</div>
					<?php endif; ?>

				</div><!-- .work-compliance__title6 -->
				<?php endif; ?>

				<?php if ( $owp_docs_heading || $owp_documents ) : ?>
				<div class="work-compliance__docs">

					<?php if ( $owp_docs_badge ) : ?>
					<div class="section__badge">
						<span class="section__badge-icon" aria-hidden="true"></span>
						<span class="section__badge-text"><?php echo esc_html( $owp_docs_badge ); ?></span>
					</div>
					<?php endif; ?>

					<?php if ( $owp_docs_heading ) : ?>
					<h2 class="section__title"><?php echo esc_html( $owp_docs_heading ); ?></h2>
					<?php endif; ?>

					<?php if ( $owp_docs_intro ) : ?>
					<p><?php echo esc_html( $owp_docs_intro ); ?></p>
					<?php endif; ?>

					<?php if ( $owp_documents ) : ?>
					<ul class="work-docs-list" role="list">
						<?php foreach ( $owp_documents as $doc ) :
							$doc_label = (string) ( $doc['owp_doc_label'] ?? '' );
							$doc_url   = esc_url( $doc['owp_doc_url'] ?? '' );
							if ( ! $doc_label || ! $doc_url ) { continue; }
						?>
						<li class="work-docs-list__item">
							<a
								href="<?php echo $doc_url; ?>"
								class="work-docs-list__link"
								target="_blank"
								rel="noopener noreferrer"
								aria-label="<?php echo esc_attr( sprintf( __( 'Open %s (opens in new tab)', 'pc4s' ), $doc_label ) ); ?>"
							>
								<?php echo $doc_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $doc_label ); ?></span>
								<?php echo $arrow_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						</li>
						<?php endforeach; ?>
					</ul><!-- .work-docs-list -->
					<?php endif; ?>

				</div><!-- .work-compliance__docs -->
				<?php endif; ?>

			</div><!-- .work-compliance__grid -->
		</div><!-- .wrapper -->
	</section><!-- .work-compliance -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 7: Gallery — badge, heading, subtitle + image grid (repeater)
	   ================================================================ */ ?>
	<?php if ( $owp_gallery_heading || $owp_gallery_items ) : ?>
	<section class="section work-gallery" aria-labelledby="gallery-heading">
		<div class="wrapper">

			<?php if ( $owp_gallery_badge || $owp_gallery_heading || $owp_gallery_subtitle ) : ?>
			<div class="section__header">

				<?php if ( $owp_gallery_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $owp_gallery_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $owp_gallery_heading ) : ?>
				<h2 id="gallery-heading" class="section__title">
					<?php echo esc_html( $owp_gallery_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $owp_gallery_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $owp_gallery_subtitle ); ?></p>
				<?php endif; ?>

			</div><!-- .section__header -->
			<?php endif; ?>

			<?php if ( $owp_gallery_items ) : ?>
			<ul class="work-gallery__grid" role="list" aria-label="<?php esc_attr_e( 'Community event photos', 'pc4s' ); ?>">
				<?php foreach ( $owp_gallery_items as $gallery_item ) :
					$img = $gallery_item['owp_gallery_image'] ?? null;
					if ( ! $img || empty( $img['url'] ) ) { continue; }
					$img_url = esc_url( $img['url'] );
					$img_alt = esc_attr( $img['alt'] ?? '' );
				?>
				<li class="work-gallery__item">
					<figure class="work-gallery__figure">
						<img
							src="<?php echo $img_url; ?>"
							alt="<?php echo $img_alt; ?>"
							class="work-gallery__img"
							loading="lazy"
							decoding="async"
						/>
					</figure>
				</li>
				<?php endforeach; ?>
			</ul><!-- .work-gallery__grid -->
			<?php endif; ?>

		</div><!-- .wrapper -->
	</section><!-- .work-gallery -->
	<?php endif; ?>


	<?php /* ================================================================
	   SECTION 8: Page CTA — badge, heading, subtitle, two action links
	   ================================================================ */ ?>
	<?php if ( $owp_cta_heading || $owp_cta_primary || $owp_cta_secondary ) : ?>
	<section class="section page-cta" aria-labelledby="work-cta-heading">
		<div class="wrapper">
			<div class="section__header">

				<?php if ( $owp_cta_badge ) : ?>
				<div class="section__badge">
					<span class="section__badge-icon" aria-hidden="true"></span>
					<span class="section__badge-text"><?php echo esc_html( $owp_cta_badge ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $owp_cta_heading ) : ?>
				<h2 id="work-cta-heading" class="section__title">
					<?php echo esc_html( $owp_cta_heading ); ?>
				</h2>
				<?php endif; ?>

				<?php if ( $owp_cta_subtitle ) : ?>
				<p class="section__subtitle"><?php echo esc_html( $owp_cta_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $owp_cta_primary || $owp_cta_secondary ) : ?>
				<div class="page-cta__actions">

					<?php if ( $owp_cta_primary ) :
						$cta_p_url    = esc_url( $owp_cta_primary['url'] ?? '' );
						$cta_p_title  = esc_html( $owp_cta_primary['title'] ?? '' );
						$cta_p_target = ! empty( $owp_cta_primary['target'] )
							? ' target="' . esc_attr( $owp_cta_primary['target'] ) . '" rel="noopener noreferrer"'
							: '';
					?>
					<a href="<?php echo $cta_p_url; ?>" class="btn btn--primary"<?php echo $cta_p_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php echo $cta_p_title; ?>
					</a>
					<?php endif; ?>

					<?php if ( $owp_cta_secondary ) :
						$cta_s_url    = esc_url( $owp_cta_secondary['url'] ?? '' );
						$cta_s_title  = esc_html( $owp_cta_secondary['title'] ?? '' );
						$cta_s_target = ! empty( $owp_cta_secondary['target'] )
							? ' target="' . esc_attr( $owp_cta_secondary['target'] ) . '" rel="noopener noreferrer"'
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
