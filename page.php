<?php
/**
 * The template for displaying all pages
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

?>

<div id="primary" class="page-content">
    <div class="wrapper">
        <div class="page-layout">
            <div class="page-main">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('parts/content/content', 'page');

                    // If comments are open or we have at least one comment, load up the comment template.
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;
                endwhile; // End of the loop.
                ?>
            </div><!-- .page-main -->
        </div><!-- .page-layout -->
    </div><!-- /.wrapper -->
</div><!-- #primary -->

<?php
get_footer();

