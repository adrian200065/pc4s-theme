<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @package PC4S
 * @subpackage Template_Parts/Content
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>

<div id="main-content">
    <div class="wrapper">
        <section class="no-content">
            <header class="section-title section-title--centered">
                <h1 class="section-title__heading"><?php esc_html_e('Nothing here', PC4S_TEXTDOMAIN); ?></h1>
            </header>
            <div class="no-content__message">
                <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try going back to the homepage?', PC4S_TEXTDOMAIN); ?></p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="button button--primary">
                    <?php esc_html_e('Go to Homepage', PC4S_TEXTDOMAIN); ?>
                </a>
            </div><!-- /.no-content__message -->
        </section>
    </div><!-- /.wrapper -->
</div><!-- #main-content -->
