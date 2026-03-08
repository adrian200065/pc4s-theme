<?php
/**
 * Template part: About Section
 *
 * Flexible Content layout name: about
 *
 * ACF sub fields:
 *   badge_text  (text)
 *   title       (text)
 *   subtitle    (textarea)
 *   cards       (repeater):
 *     - icon         (select: vision|mission|coalition|compliance)
 *     - card_title   (text)
 *     - card_content (textarea)
 *
 * @package PC4S
 * @subpackage Template_Parts/Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns an inline SVG string for a given icon key.
 *
 * @param string $icon Icon key.
 * @return string SVG markup (already safe for output; no user input reaches this).
 */
function pc4s_about_icon( string $icon ): string {
    $icons = [
        'vision' =>
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>'
            . '<circle cx="12" cy="12" r="3"></circle>'
            . '</svg>',
        'mission' =>
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<circle cx="12" cy="12" r="10"></circle>'
            . '<circle cx="12" cy="12" r="6"></circle>'
            . '<circle cx="12" cy="12" r="2"></circle>'
            . '</svg>',
        'coalition' =>
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>'
            . '<circle cx="9" cy="7" r="4"></circle>'
            . '<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>'
            . '<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'
            . '</svg>',
        'compliance' =>
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>'
            . '</svg>',
    ];

    return $icons[ $icon ] ?? '';
}

$badge_text = get_sub_field( 'badge_text' );
$title      = get_sub_field( 'title' );
$subtitle   = get_sub_field( 'subtitle' );

if ( ! $title ) {
    return;
}
?>

<section class="section about" id="about" aria-labelledby="about-heading">
    <div class="wrapper about-inner">

        <div class="section__header">

            <?php if ( $badge_text ) : ?>
            <div class="section__badge">
                <span class="section__badge-icon" aria-hidden="true"></span>
                <span class="section__badge-text"><?php echo esc_html( $badge_text ); ?></span>
            </div>
            <?php endif; ?>

            <h2 id="about-heading" class="section__title">
                <?php echo esc_html( $title ); ?>
            </h2>

            <?php if ( $subtitle ) : ?>
            <p class="section__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
            <?php endif; ?>

        </div>

        <?php if ( have_rows( 'cards' ) ) : ?>
        <div class="about-grid">
            <?php
            while ( have_rows( 'cards' ) ) : the_row();
                $icon    = (string) get_sub_field( 'icon' );
                $c_title = get_sub_field( 'card_title' );
                $c_body  = get_sub_field( 'card_content' );
            ?>
            <div class="about-card">

                <?php if ( $icon ) : ?>
                <div class="about-card-icon" aria-hidden="true">
                    <?php echo pc4s_about_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe SVG literals ?>
                </div>
                <?php endif; ?>

                <?php if ( $c_title ) : ?>
                <h3><?php echo esc_html( $c_title ); ?></h3>
                <?php endif; ?>

                <?php if ( $c_body ) : ?>
                <p><?php echo wp_kses_post( $c_body ); ?></p>
                <?php endif; ?>

            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

    </div>
</section>
