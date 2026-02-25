<?php

/**
 * Ticket Print Template
 *
 * This template is used to display a printable bus ticket for a WooCommerce order.
 * It shows order information, passenger details, and ticket details including
 * route, departure date/time, seat number, and bus information.
 *
 * Expected variables:
 * - $order_id (int) Order ID
 * - $order_date_formatted (string) Formatted order date
 * - $order_status_label (string) Order status label (e.g. Processing, Completed)
 * - $payment_method_title (string) Payment method title (e.g. Cash on delivery)
 * - $reservation_status_label (string) Reservation status label (Reserved, Confirmed, Cancelled)
 * - $billing_name (string) Passenger name
 * - $billing_email (string) Passenger email (optional)
 * - $billing_phone (string) Passenger phone (optional)
 * - $ticket_items (array) Array of ticket items, each containing:
 *   - product_name (string) Product name
 *   - route_info (array) Route information with:
 *     - start_station (string) Start station name
 *     - end_station (string) End station name
 *     - intermediate_stations (string) JSON encoded array of intermediate stations
 *   - departure_date (string) Departure date
 *   - departure_time (string) Departure time
 *   - seat_number (string) Seat number
 *   - bus_info (array) Bus information with:
 *     - name (string) Bus name
 *     - registration_number (string) Bus registration number
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('Ticket', 'mt-ticket-bus'); ?> - <?php echo esc_html($order_id); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            /* DejaVu Sans is embedded by Dompdf and supports Cyrillic; Arial fallback for browser print */
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.2;
            color: #333;
            background: #fff;
            padding: 8px;
        }

        @media print {
            body {
                padding: 0;
            }

            @page {
                size: A4;
                margin: 1cm;
            }
        }

        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #333;
            padding: 14px;
        }

        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 18px;
            margin-bottom: 10px;
        }

        .ticket-header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
            color: #333;
        }

        .ticket-header .ticket-number {
            font-size: 13px;
            color: #666;
        }

        .ticket-info {
            margin-bottom: 12px;
        }

        .ticket-info-section {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }

        .ticket-info-section:last-child {
            border-bottom: none;
        }

        .ticket-info-section h2 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3px;
            color: #333;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            margin-bottom: 4px;
        }

        .info-label {
            font-weight: bold;
            min-width: 130px;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .ticket-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
        }

        .ticket-item h3 {
            font-size: 13px;
            margin-bottom: 4px;
            color: #333;
        }

        .ticket-footer {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1><?php esc_html_e('Bus Ticket', 'mt-ticket-bus'); ?></h1>
            <div class="ticket-number">
                <?php echo esc_html__('Order #', 'mt-ticket-bus') . esc_html($order_id); ?>
            </div>
        </div>

        <div class="ticket-info">
            <div class="ticket-info-section">
                <h2><?php esc_html_e('Order Information', 'mt-ticket-bus'); ?></h2>
                <div class="info-row">
                    <span class="info-label"><?php esc_html_e('Order Number:', 'mt-ticket-bus'); ?></span>
                    <span class="info-value">#<?php echo esc_html($order_id); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php esc_html_e('Order Date:', 'mt-ticket-bus'); ?></span>
                    <span class="info-value"><?php echo esc_html($order_date_formatted); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php esc_html_e('Order Status:', 'mt-ticket-bus'); ?></span>
                    <span class="info-value"><?php echo esc_html($order_status_label); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php esc_html_e('Payment Method:', 'mt-ticket-bus'); ?></span>
                    <span class="info-value"><?php echo esc_html($payment_method_title); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php esc_html_e('Status:', 'mt-ticket-bus'); ?></span>
                    <span class="info-value"><?php echo esc_html($reservation_status_label); ?></span>
                </div>
            </div>

            <div class="ticket-info-section">
                <h2><?php esc_html_e('Passenger Information', 'mt-ticket-bus'); ?></h2>
                <div class="info-row">
                    <span class="info-label"><?php esc_html_e('Name:', 'mt-ticket-bus'); ?></span>
                    <span class="info-value"><?php echo esc_html($billing_name); ?></span>
                </div>
                <?php if (!empty($billing_email)) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Email:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value"><?php echo esc_html($billing_email); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($billing_phone)) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Phone:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value"><?php echo esc_html($billing_phone); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php foreach ($ticket_items as $ticket) : ?>
            <div class="ticket-item">
                <h3><?php echo esc_html($ticket['product_name']); ?></h3>

                <?php if (!empty($ticket['route_info'])) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Route:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value">
                            <?php
                            $route_parts = array();
                            if (!empty($ticket['route_info']['start_station'])) {
                                $route_parts[] = esc_html($ticket['route_info']['start_station']);
                            }
                            if (!empty($ticket['route_info']['intermediate_stations'])) {
                                $intermediate = json_decode($ticket['route_info']['intermediate_stations'], true);
                                if (is_array($intermediate)) {
                                    foreach ($intermediate as $station) {
                                        if (isset($station['name'])) {
                                            $route_parts[] = esc_html($station['name']);
                                        }
                                    }
                                }
                            }
                            if (!empty($ticket['route_info']['end_station'])) {
                                $route_parts[] = esc_html($ticket['route_info']['end_station']);
                            }
                            echo implode(' → ', $route_parts);
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ticket['departure_date'])) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Departure Date:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value">
                            <?php
                            $date_obj = strtotime($ticket['departure_date']);
                            if ($date_obj !== false) {
                                echo esc_html(date_i18n(get_option('date_format'), $date_obj));
                            } else {
                                echo esc_html($ticket['departure_date']);
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ticket['departure_time'])) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Departure Time:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value">
                            <?php
                            $time_obj = strtotime($ticket['departure_time']);
                            if ($time_obj !== false) {
                                echo esc_html(date_i18n(get_option('time_format'), $time_obj));
                            } else {
                                echo esc_html($ticket['departure_time']);
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ticket['seat_number'])) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Seat Number:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value"><?php echo esc_html($ticket['seat_number']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ticket['extras']) && is_array($ticket['extras'])) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Extras:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value">
                            <?php
                            $labels = array();
                            foreach ($ticket['extras'] as $extra) {
                                if (empty($extra['name'])) {
                                    continue;
                                }
                                $price = isset($extra['price']) ? (float) $extra['price'] : 0.0;
                                $labels[] = sprintf(
                                    /* translators: 1: Extra name, 2: Extra price */
                                    '%1$s (+%2$s)',
                                    $extra['name'],
                                    number_format($price, 2, '.', '')
                                );
                            }
                            echo esc_html(implode(', ', $labels));
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ticket['bus_info'])) : ?>
                    <div class="info-row">
                        <span class="info-label"><?php esc_html_e('Bus:', 'mt-ticket-bus'); ?></span>
                        <span class="info-value">
                            <?php
                            if (!empty($ticket['bus_info']['name'])) {
                                echo esc_html($ticket['bus_info']['name']);
                            }
                            if (!empty($ticket['bus_info']['registration_number'])) {
                                if (!empty($ticket['bus_info']['name'])) {
                                    echo ' (';
                                }
                                echo esc_html($ticket['bus_info']['registration_number']);
                                if (!empty($ticket['bus_info']['name'])) {
                                    echo ')';
                                }
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="ticket-footer">
            <p><?php esc_html_e('Thank you for your purchase!', 'mt-ticket-bus'); ?></p>
            <p><?php esc_html_e('Please arrive at least 15 minutes before departure time.', 'mt-ticket-bus'); ?></p>
        </div>
    </div>
</body>

</html>