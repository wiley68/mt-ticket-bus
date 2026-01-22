<?php

/**
 * Overview page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mt-ticket-bus-overview">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="mt-ticket-bus-dashboard">
        <div class="mt-dashboard-widgets">
            <div class="mt-widget">
                <h2><?php esc_html_e('Welcome to MT Ticket Bus', 'mt-ticket-bus'); ?></h2>
                <p><?php esc_html_e('Manage your bus ticket sales system with ease.', 'mt-ticket-bus'); ?></p>
            </div>

            <div class="mt-widget">
                <h3><?php esc_html_e('Quick Stats', 'mt-ticket-bus'); ?></h3>
                <?php
                $buses = MT_Ticket_Bus_Buses::get_instance()->get_all_buses();
                $routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes();
                ?>
                <ul>
                    <li><?php esc_html_e('Total Buses:', 'mt-ticket-bus'); ?> <strong><?php echo count($buses); ?></strong></li>
                    <li><?php esc_html_e('Total Routes:', 'mt-ticket-bus'); ?> <strong><?php echo count($routes); ?></strong></li>
                </ul>
            </div>

            <div class="mt-widget">
                <h3><?php esc_html_e('Quick Links', 'mt-ticket-bus'); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-buses')); ?>"><?php esc_html_e('Manage Buses', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-routes')); ?>"><?php esc_html_e('Manage Routes', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-settings')); ?>"><?php esc_html_e('Settings', 'mt-ticket-bus'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>