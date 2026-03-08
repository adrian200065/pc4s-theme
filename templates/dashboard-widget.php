<?php
/**
 * Custom Dashboard Widget Template
 *
 * This template is used to render the content of the custom dashboard widget.
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="custom-dashboard-widget">
    <h2><?php esc_html_e('Welcome to Your Custom Dashboard', PC4S_TEXTDOMAIN); ?></h2>
    <p><?php esc_html_e('Here you can find quick links to manage your site, access theme settings, and more.', PC4S_TEXTDOMAIN); ?></p>
    <ul>
        <li><a href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e('Customize Theme', PC4S_TEXTDOMAIN); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('edit.php')); ?>"><?php esc_html_e('Manage Posts', PC4S_TEXTDOMAIN); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>"><?php esc_html_e('Manage Pages', PC4S_TEXTDOMAIN); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('options-general.php')); ?>"><?php esc_html_e('General Settings', PC4S_TEXTDOMAIN); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>"><?php esc_html_e('Permalink Settings', PC4S_TEXTDOMAIN); ?></a></li>
    </ul>
    <p><?php esc_html_e('For more information, visit our documentation or support page.', PC4S_TEXTDOMAIN); ?></p>
    <p><a href="https://lucidsitesstudio.com" target="_blank"><?php esc_html_e('Lucid Site Studio', PC4S_TEXTDOMAIN); ?></a></p>
</div><!-- /.custom-dashboard-widget -->
