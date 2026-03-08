<?php
/**
 * Template part: Crisis & Emergency Resources Section
 *
 * Flexible Content layout name: hotlines
 *
 * ACF sub fields:
 *   title           (text)
 *   subtitle        (textarea – may contain links; output via wp_kses_post)
 *   hotlines        (repeater):
 *     - name         (text)
 *     - number       (text – e.g. 800-889-9789)
 *     - is_emergency (true_false)
 *
 * @package PC4S
 * @subpackage Template_Parts/Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title    = get_sub_field( 'title' );
$subtitle = get_sub_field( 'subtitle' );

if ( ! $title || ! have_rows( 'hotlines' ) ) {
    return;
}

// Phone SVG used for every item — defined once to avoid repetition.
$phone_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12.9a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.44 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>'
    . '</svg>';
?>

<section class="section hotlines" aria-labelledby="hotlines-heading">
    <div class="wrapper">

        <header class="section__header">
            <div class="hotlines-heading-row">
                <span class="hotlines-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </span>
                <h2 id="hotlines-heading" class="section__title">
                    <?php echo esc_html( $title ); ?>
                </h2>
            </div>

            <?php if ( $subtitle ) : ?>
            <p class="section__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
            <?php endif; ?>
        </header>

        <ul class="hotlines-grid" role="list" aria-label="<?php esc_attr_e( 'Emergency crisis hotlines', 'pc4s' ); ?>">
            <?php
            while ( have_rows( 'hotlines' ) ) : the_row();
                $name         = get_sub_field( 'name' );
                $number       = get_sub_field( 'number' );
                $is_emergency = get_sub_field( 'is_emergency' );

                if ( ! $name || ! $number ) {
                    continue;
                }

                // Build a tel: href — strip all non-digit characters for the href,
                // but display the original formatted number.
                $tel_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $number );
                $item_class = 'hotline-item' . ( $is_emergency ? ' hotline-item--emergency' : '' );
            ?>
            <li class="<?php echo esc_attr( $item_class ); ?>">
                <span class="hotline-icon" aria-hidden="true">
                    <?php echo $phone_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe SVG literal ?>
                </span>
                <div class="hotline-body">
                    <span class="hotline-name"><?php echo esc_html( $name ); ?></span>
                    <a href="<?php echo esc_url( $tel_href ); ?>" class="hotline-number">
                        <?php echo esc_html( $number ); ?>
                    </a>
                </div>
            </li>
            <?php endwhile; ?>
        </ul>

    </div>
</section>
