<?php
/**
 * Template part: License Plate CTA Section
 *
 * Flexible Content layout name: license_plate
 *
 * ACF sub fields:
 *   badge_text  (text)
 *   title       (text)
 *   subtitle    (textarea)
 *   cta_text    (text)
 *   cta_url     (url)
 *   plate_image (image, return: ID)
 *
 * @package PC4S
 * @subpackage Template_Parts/Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$badge_text   = get_sub_field( 'badge_text' );
$title        = get_sub_field( 'title' );
$subtitle     = get_sub_field( 'subtitle' );
$cta_text     = get_sub_field( 'cta_text' );
$cta_url      = get_sub_field( 'cta_url' );
$plate_img_id = get_sub_field( 'plate_image' );

if ( ! $title ) {
    return;
}
?>

<section class="section license-plate" aria-labelledby="plate-heading">
    <div class="wrapper plate-inner">

        <div class="plate-text">
            <div class="section__header section__header--start">

                <?php if ( $badge_text ) : ?>
                <div class="section__badge">
                    <span class="section__badge-icon" aria-hidden="true"></span>
                    <span class="section__badge-text"><?php echo esc_html( $badge_text ); ?></span>
                </div>
                <?php endif; ?>

                <h2 id="plate-heading" class="section__title">
                    <?php echo esc_html( $title ); ?>
                </h2>

                <?php if ( $subtitle ) : ?>
                <p class="section__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
                <?php endif; ?>

            </div>

            <?php if ( $cta_text && $cta_url ) : ?>
            <div class="plate-cta">
                <a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn--primary">
                    <?php echo esc_html( $cta_text ); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( $plate_img_id ) : ?>
        <div class="plate-image">
            <?php
            echo wp_get_attachment_image(
                $plate_img_id,
                'large',
                false,
                [
                    'decoding' => 'async',
                    'loading'  => 'lazy',
                ]
            );
            ?>
        </div>
        <?php endif; ?>

    </div>
</section>
