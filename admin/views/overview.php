<?php

/**
 * Overview Page Template
 *
 * This template displays the plugin dashboard/overview page in the WordPress admin area.
 * It provides a welcome message, quick statistics, and navigation links to all plugin pages.
 *
 * The page displays:
 * - Welcome message introducing the plugin
 * - Quick statistics widget showing:
 *   - Total number of buses
 *   - Total number of routes
 *   - Total number of schedules
 *   - Number of reservations for today (with link to view them if available)
 * - Quick links widget with navigation to all plugin management pages:
 *   - Buses management
 *   - Routes management
 *   - Schedules management
 *   - Reservations management
 *   - Settings
 *
 * Statistics calculation:
 * - Buses: Counts all active buses
 * - Routes: Counts all active routes
 * - Schedules: Counts all schedules (active and inactive)
 * - Reservations Today: Counts all reservations with departure_date matching current date
 *
 * Reservations link:
 * - If reservations exist for today, a "Show Reservations" link is displayed
 * - The link is dynamically built using the first reservation's route_id, schedule_id,
 *   and departure_time to pre-fill the reservations page filters
 * - Link format: admin.php?page=mt-ticket-bus-reservations&date=YYYY-MM-DD&route_id=X&schedule_id=Y&departure_time=HH:MM&submit=...
 *
 * Expected variables:
 * - None (this is a standalone template that retrieves its own data)
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
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
                $schedules = MT_Ticket_Bus_Schedules::get_instance()->get_all_schedules(array('status' => 'all'));

                // Get reservations for today
                $today = date('Y-m-d');
                $today_reservations = MT_Ticket_Bus_Reservations::get_instance()->get_all_reservations(array(
                    'departure_date' => $today,
                    'status' => ''
                ));
                $today_reservations_count = count($today_reservations);

                // Build link to reservations page with first reservation's parameters
                $reservations_link = admin_url('admin.php?page=mt-ticket-bus-reservations&date=' . urlencode($today));
                if (!empty($today_reservations)) {
                    // Get first reservation to extract route_id, schedule_id, and departure_time
                    $first_reservation = $today_reservations[0];
                    if (!empty($first_reservation->route_id) && !empty($first_reservation->schedule_id) && !empty($first_reservation->departure_time)) {
                        // Format departure_time as H:i (without seconds)
                        $departure_time_formatted = date('H:i', strtotime($first_reservation->departure_time));
                        $reservations_link = admin_url('admin.php?page=mt-ticket-bus-reservations') .
                            '&date=' . urlencode($today) .
                            '&route_id=' . absint($first_reservation->route_id) .
                            '&schedule_id=' . absint($first_reservation->schedule_id) .
                            '&departure_time=' . urlencode($departure_time_formatted) .
                            '&submit=' . urlencode(__('Show Reservations', 'mt-ticket-bus'));
                    }
                }
                ?>
                <ul>
                    <li><?php esc_html_e('Total Buses:', 'mt-ticket-bus'); ?> <strong><?php echo count($buses); ?></strong></li>
                    <li><?php esc_html_e('Total Routes:', 'mt-ticket-bus'); ?> <strong><?php echo count($routes); ?></strong></li>
                    <li><?php esc_html_e('Total Schedules:', 'mt-ticket-bus'); ?> <strong><?php echo count($schedules); ?></strong></li>
                    <li>
                        <?php esc_html_e('Reservations Today:', 'mt-ticket-bus'); ?> <strong><?php echo $today_reservations_count; ?></strong>
                        <?php if ($today_reservations_count > 0) : ?>
                            <a href="<?php echo esc_url($reservations_link); ?>" style="margin-left: 10px;">
                                <?php esc_html_e('Show Reservations', 'mt-ticket-bus'); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>

            <div class="mt-widget">
                <h3><?php esc_html_e('Quick Links', 'mt-ticket-bus'); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-buses')); ?>"><?php esc_html_e('Manage Buses', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-routes')); ?>"><?php esc_html_e('Manage Routes', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-schedules')); ?>"><?php esc_html_e('Schedules', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-reservations')); ?>"><?php esc_html_e('Reservations', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-settings')); ?>"><?php esc_html_e('Settings', 'mt-ticket-bus'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>