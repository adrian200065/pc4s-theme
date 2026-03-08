<?php
/**
 * The template for displaying the footer
 *
 * Closes #main-content, renders the site footer partial, then closes the
 * document. Heavy lifting lives in parts/footer/site-footer.php so this
 * file stays a thin wrapper — consistent with the rest of the theme.
 *
 * @package PC4S
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

	</main><!-- #main-content -->

	<?php get_template_part( 'parts/footer/site-footer' ); ?>

	<?php wp_footer(); ?>

</body>
</html>
