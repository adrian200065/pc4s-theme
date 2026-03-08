<?php
/**
 * Template Name: Drug Use: Know The Facts
 * Template Post Type: page
 *
 * Page template for the "Drug Use: Know The Facts" page.
 *
 * Sections rendered (in order):
 *   1. Page Banner         — parts/content/page-banner.php
 *   2. Hero                — badge, heading, lead paragraph, CTA buttons, decorative accent pills
 *   3. Myths & Facts       — section header + repeater of myth/fact pairs + source attribution
 *   4. Warning Signs       — section header + repeater of sign cards (heading + list items) + source
 *   5. Commonly Abused     — section header + repeater of drug cards (name + subtitle + signs) + source
 *   6. CTA                 — centered section header with badge, heading, subtitle, and two action buttons
 *
 * ACF field group: group_drug_use_page (acf-json/group_drug_use_page.json)
 *   — Field prefix: dktf_
 *   — Location: Page Template == drug-use-page.php
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

// --- Hero Section ---
$dktf_hero_badge     = (string) get_field( 'dktf_hero_badge' );
$dktf_hero_heading   = (string) get_field( 'dktf_hero_heading' );
$dktf_hero_lead      = (string) get_field( 'dktf_hero_lead' );
$dktf_hero_primary   = get_field( 'dktf_hero_primary' );   // link array|null
$dktf_hero_secondary = get_field( 'dktf_hero_secondary' ); // link array|null
$dktf_hero_pill_1    = (string) get_field( 'dktf_hero_pill_1' );
$dktf_hero_pill_2    = (string) get_field( 'dktf_hero_pill_2' );

// --- Myths & Facts Section ---
$dktf_myths_badge         = (string) get_field( 'dktf_myths_badge' );
$dktf_myths_heading       = (string) get_field( 'dktf_myths_heading' );
$dktf_myths_subtitle      = (string) get_field( 'dktf_myths_subtitle' );
$dktf_myths_items         = (array)  get_field( 'dktf_myths_items' ) ?: [];
$dktf_myths_source_label  = (string) get_field( 'dktf_myths_source_label' );
$dktf_myths_source_url    = (string) get_field( 'dktf_myths_source_url' );

// --- Warning Signs Section ---
$dktf_signs_badge         = (string) get_field( 'dktf_signs_badge' );
$dktf_signs_heading       = (string) get_field( 'dktf_signs_heading' );
$dktf_signs_subtitle      = (string) get_field( 'dktf_signs_subtitle' );
$dktf_signs_cards         = (array)  get_field( 'dktf_signs_cards' ) ?: [];
$dktf_signs_source_label  = (string) get_field( 'dktf_signs_source_label' );
$dktf_signs_source_url    = (string) get_field( 'dktf_signs_source_url' );

// --- Commonly Abused Drugs Section ---
$dktf_drugs_badge         = (string) get_field( 'dktf_drugs_badge' );
$dktf_drugs_heading       = (string) get_field( 'dktf_drugs_heading' );
$dktf_drugs_subtitle      = (string) get_field( 'dktf_drugs_subtitle' );
$dktf_drugs_items         = (array)  get_field( 'dktf_drugs_items' ) ?: [];
$dktf_drugs_source_label  = (string) get_field( 'dktf_drugs_source_label' );
$dktf_drugs_source_url    = (string) get_field( 'dktf_drugs_source_url' );

// --- CTA Section ---
$dktf_cta_badge      = (string) get_field( 'dktf_cta_badge' );
$dktf_cta_heading    = (string) get_field( 'dktf_cta_heading' );
$dktf_cta_subtitle   = (string) get_field( 'dktf_cta_subtitle' );
$dktf_cta_primary    = get_field( 'dktf_cta_primary' );   // link array|null
$dktf_cta_secondary  = get_field( 'dktf_cta_secondary' ); // link array|null

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
get_header();
?>

<?php get_template_part( 'parts/content/page-banner' ); ?>

<?php /* ====================================================================
   SECTION: Hero — badge, heading, lead, CTA buttons, accent pills
   ==================================================================== */ ?>
<?php if ( $dktf_hero_heading ) : ?>
<section class="what-we-do-hero" aria-labelledby="dktf-hero-heading">
	<div class="wrapper what-we-do-hero__inner">

		<!-- Copy -->
		<div class="what-we-do-hero__copy">

			<?php if ( $dktf_hero_badge ) : ?>
			<span class="what-we-do-hero__badge"><?php echo esc_html( $dktf_hero_badge ); ?></span>
			<?php endif; ?>

			<h2 id="dktf-hero-heading" class="what-we-do-hero__title">
				<?php echo esc_html( $dktf_hero_heading ); ?>
			</h2>

			<?php if ( $dktf_hero_lead ) : ?>
			<p class="what-we-do-hero__lead"><?php echo esc_html( $dktf_hero_lead ); ?></p>
			<?php endif; ?>

			<?php if ( $dktf_hero_primary || $dktf_hero_secondary ) : ?>
			<div class="what-we-do-hero__actions">
				<?php if ( $dktf_hero_primary && ! empty( $dktf_hero_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $dktf_hero_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $dktf_hero_primary['target'] ) ) : ?>target="<?php echo esc_attr( $dktf_hero_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $dktf_hero_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $dktf_hero_secondary && ! empty( $dktf_hero_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $dktf_hero_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $dktf_hero_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $dktf_hero_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $dktf_hero_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__copy -->

		<!-- Decorative accent pills -->
		<?php if ( $dktf_hero_pill_1 || $dktf_hero_pill_2 ) : ?>
		<div class="what-we-do-hero__accent" aria-hidden="true">

			<?php if ( $dktf_hero_pill_1 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"></circle>
					<line x1="12" y1="16" x2="12" y2="12"></line>
					<line x1="12" y1="8" x2="12.01" y2="8"></line>
				</svg>
				<?php echo esc_html( $dktf_hero_pill_1 ); ?>
			</span>
			<?php endif; ?>

			<?php if ( $dktf_hero_pill_2 ) : ?>
			<span class="what-we-do-hero__accent-pill what-we-do-hero__accent-pill--secondary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="20 6 9 17 4 12"></polyline>
				</svg>
				<?php echo esc_html( $dktf_hero_pill_2 ); ?>
			</span>
			<?php endif; ?>

		</div><!-- .what-we-do-hero__accent -->
		<?php endif; ?>

	</div><!-- .wrapper -->
</section><!-- .what-we-do-hero -->
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Myths & Facts — myth/fact repeater pairs with source attribution
   ==================================================================== */ ?>
<?php if ( $dktf_myths_heading ) : ?>
<section class="section" id="myths" aria-labelledby="dktf-myths-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $dktf_myths_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $dktf_myths_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="dktf-myths-heading" class="section__title">
				<?php echo esc_html( $dktf_myths_heading ); ?>
			</h2>

			<?php if ( $dktf_myths_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $dktf_myths_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $dktf_myths_items ) : ?>
		<div class="what-we-do__myths" role="list">
			<?php foreach ( $dktf_myths_items as $myth ) :
				$myth_text = (string) ( $myth['myth_text'] ?? '' );
				$fact_text = (string) ( $myth['fact_text'] ?? '' );
				if ( ! $myth_text && ! $fact_text ) {
					continue;
				}
			?>
			<article class="what-we-do__myth" role="listitem">
				<?php if ( $myth_text ) : ?>
				<div class="what-we-do__myth-row what-we-do__myth-row--myth">
					<span class="what-we-do__myth-tag">Myth</span>
					<p class="what-we-do__myth-body"><?php echo esc_html( $myth_text ); ?></p>
				</div>
				<?php endif; ?>
				<?php if ( $fact_text ) : ?>
				<div class="what-we-do__myth-row what-we-do__myth-row--fact">
					<span class="what-we-do__myth-tag">Fact</span>
					<p class="what-we-do__myth-body"><?php echo esc_html( $fact_text ); ?></p>
				</div>
				<?php endif; ?>
			</article>
			<?php endforeach; ?>
		</div><!-- .what-we-do__myths -->
		<?php endif; ?>

		<?php if ( $dktf_myths_source_label ) : ?>
		<p class="what-we-do__source" role="note">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
				<polyline points="14 2 14 8 20 8"></polyline>
			</svg>
			<?php if ( $dktf_myths_source_url ) : ?>
			Information sourced from
			<a href="<?php echo esc_url( $dktf_myths_source_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $dktf_myths_source_label ); ?>
			</a>
			<?php else : ?>
			<?php echo esc_html( $dktf_myths_source_label ); ?>
			<?php endif; ?>
		</p>
		<?php endif; ?>

	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Warning Signs — cards repeater (heading + newline-separated items)
   ==================================================================== */ ?>
<?php if ( $dktf_signs_heading ) : ?>
<section class="section background-light" id="warning-signs" aria-labelledby="dktf-signs-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $dktf_signs_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $dktf_signs_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="dktf-signs-heading" class="section__title">
				<?php echo esc_html( $dktf_signs_heading ); ?>
			</h2>

			<?php if ( $dktf_signs_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $dktf_signs_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $dktf_signs_cards ) : ?>
		<div class="what-we-do__signs-grid">
			<?php foreach ( $dktf_signs_cards as $card ) :
				$card_heading  = (string) ( $card['card_heading']  ?? '' );
				$card_modifier = (string) ( $card['card_modifier'] ?? '' );
				$card_items    = (string) ( $card['card_items']    ?? '' );
				if ( ! $card_heading ) {
					continue;
				}
				$modifier_class = $card_modifier
					? ' what-we-do__signs-heading--' . sanitize_html_class( $card_modifier )
					: '';
				$list_items = array_filter( array_map( 'trim', explode( "\n", $card_items ) ) );
			?>
			<div class="what-we-do__signs-card">
				<h3 class="what-we-do__signs-heading<?php echo esc_attr( $modifier_class ); ?>">
					<?php echo esc_html( $card_heading ); ?>
				</h3>
				<?php if ( $list_items ) : ?>
				<ul class="what-we-do__signs-list" role="list">
					<?php foreach ( $list_items as $item ) : ?>
					<li class="what-we-do__signs-item"><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div><!-- .what-we-do__signs-grid -->
		<?php endif; ?>

		<?php if ( $dktf_signs_source_label ) : ?>
		<p class="what-we-do__source" role="note">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
				<polyline points="14 2 14 8 20 8"></polyline>
			</svg>
			<?php if ( $dktf_signs_source_url ) : ?>
			Information sourced from
			<a href="<?php echo esc_url( $dktf_signs_source_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $dktf_signs_source_label ); ?>
			</a>
			<?php else : ?>
			<?php echo esc_html( $dktf_signs_source_label ); ?>
			<?php endif; ?>
		</p>
		<?php endif; ?>

	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php /* ====================================================================
   SECTION: Commonly Abused Drugs — drug cards repeater with source
   ==================================================================== */ ?>
<?php if ( $dktf_drugs_heading ) : ?>
<section class="section" aria-labelledby="dktf-drugs-heading">
	<div class="wrapper">

		<div class="section__header">

			<?php if ( $dktf_drugs_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $dktf_drugs_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="dktf-drugs-heading" class="section__title">
				<?php echo esc_html( $dktf_drugs_heading ); ?>
			</h2>

			<?php if ( $dktf_drugs_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $dktf_drugs_subtitle ); ?></p>
			<?php endif; ?>

		</div><!-- .section__header -->

		<?php if ( $dktf_drugs_items ) : ?>
		<ul class="what-we-do__drugs-list" role="list">
			<?php foreach ( $dktf_drugs_items as $drug ) :
				$drug_name     = (string) ( $drug['drug_name']     ?? '' );
				$drug_subtitle = (string) ( $drug['drug_subtitle'] ?? '' );
				$drug_signs    = (string) ( $drug['drug_signs']    ?? '' );
				if ( ! $drug_name ) {
					continue;
				}
			?>
			<li class="what-we-do__drug-card">
				<h3 class="what-we-do__drug-name">
					<?php echo esc_html( $drug_name ); ?>
					<?php if ( $drug_subtitle ) : ?>
					<span style="font-weight: 400; font-size: var(--fs-200)"><?php echo esc_html( $drug_subtitle ); ?></span>
					<?php endif; ?>
				</h3>
				<?php if ( $drug_signs ) : ?>
				<p class="what-we-do__drug-signs"><?php echo esc_html( $drug_signs ); ?></p>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php if ( $dktf_drugs_source_label ) : ?>
		<p class="what-we-do__source" role="note">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
				<polyline points="14 2 14 8 20 8"></polyline>
			</svg>
			<?php if ( $dktf_drugs_source_url ) : ?>
			Information sourced from
			<a href="<?php echo esc_url( $dktf_drugs_source_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $dktf_drugs_source_label ); ?>
			</a>
			<?php else : ?>
			<?php echo esc_html( $dktf_drugs_source_label ); ?>
			<?php endif; ?>
		</p>
		<?php endif; ?>

	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php /* ====================================================================
   SECTION: CTA — centered badge + heading + subtitle + two action buttons
   ==================================================================== */ ?>
<?php if ( $dktf_cta_heading ) : ?>
<section class="section page-cta" aria-labelledby="dktf-cta-heading">
	<div class="wrapper">
		<div class="section__header">

			<?php if ( $dktf_cta_badge ) : ?>
			<div class="section__badge">
				<span class="section__badge-text"><?php echo esc_html( $dktf_cta_badge ); ?></span>
			</div>
			<?php endif; ?>

			<h2 id="dktf-cta-heading" class="section__title">
				<?php echo esc_html( $dktf_cta_heading ); ?>
			</h2>

			<?php if ( $dktf_cta_subtitle ) : ?>
			<p class="section__subtitle"><?php echo esc_html( $dktf_cta_subtitle ); ?></p>
			<?php endif; ?>

			<?php if ( $dktf_cta_primary || $dktf_cta_secondary ) : ?>
			<div class="page-cta__actions">
				<?php if ( $dktf_cta_primary && ! empty( $dktf_cta_primary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $dktf_cta_primary['url'] ); ?>"
					class="btn btn--primary"
					<?php if ( ! empty( $dktf_cta_primary['target'] ) ) : ?>target="<?php echo esc_attr( $dktf_cta_primary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $dktf_cta_primary['title'] ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $dktf_cta_secondary && ! empty( $dktf_cta_secondary['url'] ) ) : ?>
				<a
					href="<?php echo esc_url( $dktf_cta_secondary['url'] ); ?>"
					class="btn btn--outline"
					<?php if ( ! empty( $dktf_cta_secondary['target'] ) ) : ?>target="<?php echo esc_attr( $dktf_cta_secondary['target'] ); ?>" rel="noopener noreferrer"<?php endif; ?>
				>
					<?php echo esc_html( $dktf_cta_secondary['title'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .section__header -->
	</div><!-- .wrapper -->
</section>
<?php endif; ?>

<?php
get_footer();
