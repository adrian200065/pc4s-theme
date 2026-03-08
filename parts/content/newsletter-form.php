<?php
/**
 * Template Part — Newsletter Form
 *
 * Delegates all rendering to Custom_Forms::render_form().
 * No logic lives here; this file is intentionally thin.
 *
 * Accepted $args (passed via get_template_part()):
 *   context        string  'footer' (default) | 'inline'
 *                          'footer'  → class="newsletter-form",          id="newsletter-email"
 *                          'inline'  → class="comm-feed__newsletter-form", id="comm-newsletter-email"
 *   source_page    string  Full URL of the originating page.
 *   email_input_id string  HTML id for the email <input>.
 *
 * @package PC4S
 * @subpackage Template_Parts/Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PC4S\Classes\Custom_Forms;

/** @var array $args */
$context        = isset( $args['context'] )        ? sanitize_key( $args['context'] )                    : 'footer';
$source_page    = isset( $args['source_page'] )    ? esc_url_raw( $args['source_page'] )                 : '';
$email_input_id = isset( $args['email_input_id'] ) ? sanitize_html_class( $args['email_input_id'] )      : 'newsletter-email';

Custom_Forms::render_form( 'newsletter', [
	'context'        => $context,
	'source_page'    => $source_page,
	'email_input_id' => $email_input_id,
] );
