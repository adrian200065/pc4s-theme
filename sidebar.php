<?php
/**
 * The sidebar containing the main widget area
 *
 * @package Pc4s
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside class="widget-area">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside>
