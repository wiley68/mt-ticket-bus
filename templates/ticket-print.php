<?php

/**
 * Ticket Print Template
 *
 * @package MT_Ticket_Bus
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('Ticket', 'mt-ticket-bus'); ?> - <?php echo esc_html($order_id); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 20px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
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
            padding: 30px;
        }

        .ticket-header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .ticket-header h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .ticket-header .ticket-number {
            font-size: 18px;
            color: #666;
        }

        .ticket-info {
            margin-bottom: 30px;
        }

        .ticket-info-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .ticket-info-section:last-child {
            border-bottom: none;
        }

        .ticket-info-section h2 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            min-width: 150px;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .ticket-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .ticket-item h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .ticket-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .print-button {
            text-align: center;
            margin: 20px 0;
        }

        .print-button button {
            padding: 12px 30px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .print-button button:hover {
            background: #2563eb;
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

    <div class="print-button no-print">
        <button onclick="window.print()"><?php esc_html_e('Print Ticket', 'mt-ticket-bus'); ?></button>
    </div>

    <script>
        // Auto-print when page loads (if not PDF mode)
        <?php if (!isset($_GET['mt_pdf'])) : ?>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        <?php endif; ?>
    </script>
</body>

</html>