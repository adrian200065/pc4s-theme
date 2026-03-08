<?php
/**
 * Template part: Hero Section
 *
 * Flexible Content layout name: hero
 *
 * ACF sub fields:
 *   bg_image            (image, return: ID)
 *   title               (text)
 *   subtitle            (textarea)
 *   primary_cta_text    (text)
 *   primary_cta_url     (url)
 *   secondary_cta_text  (text)
 *   secondary_cta_url   (url)
 *
 * @package PC4S
 * @subpackage Template_Parts/Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title              = get_sub_field( 'title' );
$subtitle           = get_sub_field( 'subtitle' );
$primary_cta_text   = get_sub_field( 'primary_cta_text' );
$primary_cta_url    = get_sub_field( 'primary_cta_url' );
$secondary_cta_text = get_sub_field( 'secondary_cta_text' );
$secondary_cta_url  = get_sub_field( 'secondary_cta_url' );
$bg_image_id        = get_sub_field( 'bg_image' );

if ( ! $title ) {
    return;
}
?>

<section class="hero" aria-labelledby="hero-heading">

    <?php if ( $bg_image_id ) : ?>
    <div class="hero-bg" aria-hidden="true">
        <?php
        echo wp_get_attachment_image(
            $bg_image_id,
            'full',
            false,
            [
                'class'         => 'hero-bg-img',
                'role'          => 'presentation',
                'decoding'      => 'async',
                'fetchpriority' => 'high',
                'alt'           => '',
            ]
        );
        ?>
    </div>
    <?php endif; ?>

    <div class="wrapper">
        <div class="hero-body">

            <h1 id="hero-heading" class="hero-title">
                <?php echo wp_kses_post( $title ); ?>
            </h1>

            <?php if ( $subtitle ) : ?>
            <p class="hero-subtitle">
                <?php echo wp_kses_post( $subtitle ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $primary_cta_text || $secondary_cta_text ) : ?>
            <div class="hero-cta">

                <?php if ( $primary_cta_text && $primary_cta_url ) : ?>
                <a href="<?php echo esc_url( $primary_cta_url ); ?>" class="btn btn--primary">
                    <?php echo esc_html( $primary_cta_text ); ?>
                </a>
                <?php endif; ?>

                <?php if ( $secondary_cta_text && $secondary_cta_url ) : ?>
                <a href="<?php echo esc_url( $secondary_cta_url ); ?>" class="btn btn--ghost">
                    <?php echo esc_html( $secondary_cta_text ); ?>
                </a>
                <?php endif; ?>

            </div>
            <?php endif; ?>

        </div>
    </div>

</section>
