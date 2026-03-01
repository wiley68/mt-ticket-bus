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

<?php
$admin_locale = function_exists('get_user_locale') ? call_user_func('get_user_locale') : (function_exists('get_locale') ? call_user_func('get_locale') : 'en_US');
$admin_locale_bg = (strpos($admin_locale, 'bg') === 0);
$url_tickets_base = $admin_locale_bg ? 'https://tickets-bg.avalonbg.com' : 'https://tickets-en.avalonbg.com';
$url_demo_base    = $admin_locale_bg ? 'https://busdemo-bg.avalonbg.com' : 'https://busdemo-en.avalonbg.com';
$url_doc          = $url_tickets_base . '/wp-content/uploads/sites/2/2026/02/mt-ticket-box-bg.pdf';
$url_news         = $url_tickets_base . '/news/';
?>
<div class="wrap mt-ticket-bus-overview">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="mt-ticket-bus-dashboard">
        <div class="mt-dashboard-widgets">
            <div class="mt-widget">
                <h2><?php esc_html_e('Welcome to MT Ticket Bus', 'mt-ticket-bus'); ?></h2>
                <p><?php esc_html_e('Manage your bus ticket sales system with ease.', 'mt-ticket-bus'); ?></p>
                <ul class="mt-overview-links">
                    <li><strong><?php esc_html_e('Application website:', 'mt-ticket-bus'); ?></strong> <a href="<?php echo esc_url($url_tickets_base . '/'); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url_tickets_base . '/'); ?></a></li>
                    <li><strong><?php esc_html_e('Demo site:', 'mt-ticket-bus'); ?></strong> <a href="<?php echo esc_url($url_demo_base . '/'); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url_demo_base . '/'); ?></a></li>
                    <li><strong><?php esc_html_e('Documentation for working with the application:', 'mt-ticket-bus'); ?></strong> <a href="<?php echo esc_url($url_doc); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url_doc); ?></a></li>
                    <li><strong><?php esc_html_e('Version control:', 'mt-ticket-bus'); ?></strong> <a href="<?php echo esc_url($url_news); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url_news); ?></a></li>
                </ul>
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
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-settings')); ?>"><?php esc_html_e('Settings', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-extras')); ?>"><?php esc_html_e('Manage Extras', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-buses')); ?>"><?php esc_html_e('Manage Buses', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-routes')); ?>"><?php esc_html_e('Manage Routes', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-schedules')); ?>"><?php esc_html_e('Schedules', 'mt-ticket-bus'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-reservations')); ?>"><?php esc_html_e('Reservations', 'mt-ticket-bus'); ?></a></li>
                </ul>
            </div>
        </div>

        <?php
        $current_year   = (int) date('Y');
        $sales_by_month = array();
        $chart_labels   = array();
        $chart_tickets  = array();
        $chart_amounts  = array();
        $currency_symbol = '';
        $best_customers  = array();
        if (class_exists('MT_Ticket_Bus_WooCommerce_Integration')) {
            $sales_by_month = MT_Ticket_Bus_WooCommerce_Integration::get_ticket_sales_by_month($current_year);
            $best_customers = MT_Ticket_Bus_WooCommerce_Integration::get_best_ticket_customers($current_year, 3);
            for ($m = 1; $m <= 12; $m++) {
                $chart_labels[] = date_i18n('M', mktime(0, 0, 0, $m, 1, $current_year));
                $chart_tickets[] = isset($sales_by_month[$m]) ? (int) $sales_by_month[$m]['tickets_count'] : 0;
                $chart_amounts[] = isset($sales_by_month[$m]) ? (float) $sales_by_month[$m]['total_amount'] : 0.0;
            }
            $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        }
        $sales_chart_data = array(
            'labels'   => $chart_labels,
            'tickets'  => $chart_tickets,
            'amounts'  => $chart_amounts,
            'currency' => $currency_symbol,
            'year'     => $current_year,
        );
        ?>

        <div class="mt-dashboard-row mt-dashboard-row-two">
            <div class="mt-widget">
                <h3><?php esc_html_e('Sales for the year', 'mt-ticket-bus'); ?></h3>
                <div class="mt-sales-chart-wrap">
                    <canvas id="mt-sales-chart" width="400" height="224" aria-label="<?php esc_attr_e('Ticket sales and revenue by month', 'mt-ticket-bus'); ?>"></canvas>
                </div>
                <script>
                    window.mtOverviewSalesData = <?php echo wp_json_encode($sales_chart_data); ?>;
                </script>
                <script>
                    (function() {
                        function initSalesChart() {
                            if (typeof Chart === 'undefined' || !window.mtOverviewSalesData) return;
                            var data = window.mtOverviewSalesData;
                            var el = document.getElementById('mt-sales-chart');
                            if (!el) return;
                            var cur = data.currency || '';
                            var i18n = (typeof mtTicketBusAdmin !== 'undefined' && mtTicketBusAdmin.i18n) ? mtTicketBusAdmin.i18n : {};
                            var ticketsLabel = i18n.salesChartTickets || 'Tickets sold';
                            var revenueLabel = i18n.salesChartRevenue || 'Revenue';
                            new Chart(el.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                            label: ticketsLabel,
                                            data: data.tickets,
                                            backgroundColor: 'rgba(0, 123, 255, 0.7)',
                                            yAxisID: 'y'
                                        },
                                        {
                                            label: revenueLabel,
                                            data: data.amounts,
                                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                            yAxisID: 'y1'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    interaction: {
                                        mode: 'index',
                                        intersect: false
                                    },
                                    plugins: {
                                        legend: {
                                            position: 'top'
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    var label = context.dataset.label || '';
                                                    var value = context.parsed.y;
                                                    if (context.dataset.yAxisID === 'y1' && cur) {
                                                        return label + ': ' + cur + ' ' + Number(value).toFixed(2);
                                                    }
                                                    return label + ': ' + value;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            type: 'linear',
                                            position: 'left',
                                            title: {
                                                display: true,
                                                text: ticketsLabel
                                            },
                                            ticks: {
                                                stepSize: 1
                                            }
                                        },
                                        y1: {
                                            type: 'linear',
                                            position: 'right',
                                            title: {
                                                display: true,
                                                text: revenueLabel
                                            },
                                            grid: {
                                                drawOnChartArea: false
                                            }
                                        }
                                    }
                                }
                            });
                        }
                        if (document.readyState === 'loading') {
                            window.addEventListener('load', initSalesChart);
                        } else {
                            initSalesChart();
                        }
                    })();
                </script>
            </div>
            <div class="mt-widget">
                <h3><?php esc_html_e('Best customers', 'mt-ticket-bus'); ?></h3>
                <?php if (empty($best_customers)) : ?>
                    <p class="mt-best-customers-empty"><?php esc_html_e('No ticket purchases this year yet.', 'mt-ticket-bus'); ?></p>
                <?php else : ?>
                    <div class="mt-best-customers-list">
                        <?php foreach ($best_customers as $customer) : ?>
                            <?php
                            $email = $customer['email'];
                            $name = $customer['name'];
                            $total_amount = $customer['total_amount'];
                            $tickets_count = $customer['tickets_count'];
                            $last_order_id = $customer['last_order_id'];
                            $order_link = admin_url('post.php?post=' . $last_order_id . '&action=edit');
                            ?>
                            <div class="mt-best-customer-card">
                                <div class="mt-best-customer-avatar">
                                    <?php echo call_user_func('get_avatar', $email, 48, '', '', array('class' => 'mt-best-customer-gravatar')); ?>
                                </div>
                                <div class="mt-best-customer-name"><?php echo esc_html($name); ?></div>
                                <div class="mt-best-customer-email"><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
                                <div class="mt-best-customer-stats">
                                    <?php
                                    $price_html = function_exists('wc_price') ? wc_price($total_amount) : esc_html(call_user_func('number_format_i18n', $total_amount, 2));
                                    printf(
                                        /* translators: 1: number of tickets, 2: formatted total amount (may contain HTML) */
                                        __('%1$d tickets, %2$s total', 'mt-ticket-bus'),
                                        (int) $tickets_count,
                                        wp_kses_post($price_html)
                                    );
                                    ?>
                                </div>
                                <div class="mt-best-customer-order-link">
                                    <a href="<?php echo esc_url($order_link); ?>"><?php esc_html_e('Last order', 'mt-ticket-bus'); ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>