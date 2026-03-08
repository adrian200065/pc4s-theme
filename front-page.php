<?php
/**
 * The front page template
 *
 * Delegates all section rendering to ACF Flexible Content layouts.
 * Each layout maps directly to a template part in parts/sections/.
 *
 * Supported layouts (map to parts/sections/section-{layout}.php):
 *   hero, license_plate, about, events, hotlines, news
 *
 * @package PC4S
 * @link    https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

    <?php
    if ( function_exists( 'have_rows' ) && have_rows( 'front_page_sections' ) ) :
        while ( have_rows( 'front_page_sections' ) ) : the_row();
            get_template_part( 'parts/sections/section', get_row_layout() );
        endwhile;
    endif;
    ?>

<?php
get_footer();