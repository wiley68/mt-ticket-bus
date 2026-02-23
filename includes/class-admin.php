<?php

/**
 * Admin interface class
 *
 * Handles all admin menu pages and settings
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin class.
 *
 * Handles WordPress admin interface including menu registration,
 * page rendering, and asset enqueuing for all admin pages.
 *
 * @since 1.0.0
 */
class MT_Ticket_Bus_Admin
{

    /**
     * Plugin instance.
     *
     * @since 1.0.0
     *
     * @var MT_Ticket_Bus_Admin
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @since 1.0.0
     *
     * @return MT_Ticket_Bus_Admin Plugin instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Initializes admin menu and script enqueuing hooks.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widget'));
        add_action('admin_post_mt_ticket_bus_export_reservations_xlsx', array($this, 'export_reservations_xlsx'));
    }

    /**
     * Add admin menu.
     *
     * Registers main menu and all submenu pages for the plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_admin_menu()
    {
        $menu_slug = 'mt-ticket-bus';

        // Main menu
        add_menu_page(
            __('MT Ticket Bus', 'mt-ticket-bus'),
            __('MT Ticket Bus', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug,
            array($this, 'render_overview_page'),
            'dashicons-tickets-alt',
            56
        );

        // Overview submenu (duplicate of main menu)
        add_submenu_page(
            $menu_slug,
            __('Overview', 'mt-ticket-bus'),
            __('Overview', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug,
            array($this, 'render_overview_page')
        );

        // Settings submenu
        add_submenu_page(
            $menu_slug,
            __('Settings', 'mt-ticket-bus'),
            __('Settings', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-settings',
            array($this, 'render_settings_page')
        );

        // Buses submenu
        add_submenu_page(
            $menu_slug,
            __('Buses', 'mt-ticket-bus'),
            __('Buses', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-buses',
            array($this, 'render_buses_page')
        );

        // Routes submenu
        add_submenu_page(
            $menu_slug,
            __('Routes', 'mt-ticket-bus'),
            __('Routes', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-routes',
            array($this, 'render_routes_page')
        );

        // Schedules submenu
        add_submenu_page(
            $menu_slug,
            __('Schedules', 'mt-ticket-bus'),
            __('Schedules', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-schedules',
            array($this, 'render_schedules_page')
        );

        // Reservations submenu
        add_submenu_page(
            $menu_slug,
            __('Reservations', 'mt-ticket-bus'),
            __('Reservations', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-reservations',
            array($this, 'render_reservations_page')
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * Enqueues CSS and JavaScript files for plugin admin pages.
     * Also localizes script with AJAX URL, nonce, and i18n strings.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_scripts($hook)
    {
        $is_our_plugin_page = (strpos($hook, 'mt-ticket-bus') !== false);
        $is_dashboard_with_widget = ($hook === 'index.php' && $this->is_dashboard_widget_enabled());

        // Load only on our plugin pages, or on main Dashboard when sales widget is enabled
        if (! $is_our_plugin_page && ! $is_dashboard_with_widget) {
            return;
        }

        // On Dashboard with widget: only Chart.js + i18n for the chart (no full admin assets)
        if ($is_dashboard_with_widget && ! $is_our_plugin_page) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js',
                array(),
                '4.4.6',
                false // In head so it runs before the widget's inline script
            );
            wp_localize_script(
                'chartjs',
                'mtTicketBusAdmin',
                array(
                    'i18n' => array(
                        'salesChartTickets' => __('Tickets sold', 'mt-ticket-bus'),
                        'salesChartRevenue' => __('Revenue', 'mt-ticket-bus'),
                    ),
                )
            );
            return;
        }

        wp_enqueue_style(
            'mt-ticket-bus-admin',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            mt_ticket_bus_get_asset_version('assets/css/admin.css')
        );

        // Enqueue SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11.0.0'
        );

        // Enqueue SweetAlert2 JS
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
            array(),
            '11.0.0',
            true
        );

        wp_enqueue_script(
            'mt-ticket-bus-admin',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'sweetalert2'),
            mt_ticket_bus_get_asset_version('assets/js/admin.js'),
            true
        );

        // Chart.js on Overview page and on main Dashboard when sales widget is enabled
        $chart_needed = ($hook === 'toplevel_page_mt-ticket-bus') || ($hook === 'index.php' && $this->is_dashboard_widget_enabled());
        if ($chart_needed) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js',
                array(),
                '4.4.6',
                true
            );
        }

        wp_localize_script(
            'mt-ticket-bus-admin',
            'mtTicketBusAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('mt_ticket_bus_admin'),
                'i18n'    => array(
                    'registrationNumberRequired' => __('Registration number is required.', 'mt-ticket-bus'),
                    'fixRegistrationError' => __('Please fix the registration number error before submitting.', 'mt-ticket-bus'),
                    'configureSeatColumns' => __('Please configure at least one column with seats. Both columns cannot be 0.', 'mt-ticket-bus'),
                    'configureSeatColumnsFirst' => __('Please configure seat columns first.', 'mt-ticket-bus'),
                    'saving' => __('Saving...', 'mt-ticket-bus'),
                    'errorOccurred' => __('An error occurred. Please try again.', 'mt-ticket-bus'),
                    'errorOccurredSaving' => __('An error occurred while saving the bus.', 'mt-ticket-bus'),
                    'errorOccurredDeleting' => __('An error occurred while deleting the bus.', 'mt-ticket-bus'),
                    'errorOccurredSavingRoute' => __('An error occurred while saving the route.', 'mt-ticket-bus'),
                    'errorOccurredDeletingRoute' => __('An error occurred while deleting the route.', 'mt-ticket-bus'),
                    'confirmDeleteBus' => __('Are you sure you want to delete this bus?', 'mt-ticket-bus'),
                    'confirmDeleteRoute' => __('Are you sure you want to delete this route?', 'mt-ticket-bus'),
                    'confirmDeleteSchedule' => __('Are you sure you want to delete this schedule?', 'mt-ticket-bus'),
                    'errorOccurredSavingSchedule' => __('An error occurred while saving the schedule.', 'mt-ticket-bus'),
                    'scheduleInfo' => __('Schedule Information', 'mt-ticket-bus'),
                    'scheduleName' => __('Name', 'mt-ticket-bus'),
                    'scheduleRoute' => __('Route', 'mt-ticket-bus'),
                    'scheduleCourses' => __('Courses', 'mt-ticket-bus'),
                    'scheduleFrequency' => __('Frequency', 'mt-ticket-bus'),
                    'scheduleStatus' => __('Status', 'mt-ticket-bus'),
                    'loading' => __('Loading...', 'mt-ticket-bus'),
                    'ok' => __('OK', 'mt-ticket-bus'),
                    'yes' => __('Yes', 'mt-ticket-bus'),
                    'cancel' => __('Cancel', 'mt-ticket-bus'),
                    'selectRoute' => __('-- Select Route --', 'mt-ticket-bus'),
                    'selectSchedule' => __('-- Select Schedule --', 'mt-ticket-bus'),
                    'selectCourse' => __('-- Select Course --', 'mt-ticket-bus'),
                    'noSchedulesFound' => __('No schedules found.', 'mt-ticket-bus'),
                    'noCoursesFound' => __('No courses found.', 'mt-ticket-bus'),
                    'errorLoadingSchedules' => __('Error loading schedules.', 'mt-ticket-bus'),
                    'errorLoadingCourses' => __('Error loading courses.', 'mt-ticket-bus'),
                    'noSeatLayoutData' => __('No seat layout data available.', 'mt-ticket-bus'),
                    'invalidSeatLayout' => __('Invalid seat layout.', 'mt-ticket-bus'),
                    'orderId' => __('Order ID', 'mt-ticket-bus'),
                    'productName' => __('Ticket', 'mt-ticket-bus'),
                    'orderStatus' => __('Order Status', 'mt-ticket-bus'),
                    'paymentMethod' => __('Payment Method', 'mt-ticket-bus'),
                    'orderNotes' => __('Order Notes', 'mt-ticket-bus'),
                    'seatNumber' => __('Seat Number', 'mt-ticket-bus'),
                    'passengerName' => __('Passenger Name', 'mt-ticket-bus'),
                    'status' => __('Status', 'mt-ticket-bus'),
                    'statusReserved' => __('Reserved (status)', 'mt-ticket-bus'),
                    'statusConfirmed' => __('Confirmed (status)', 'mt-ticket-bus'),
                    'statusCancelled' => __('Cancelled (status)', 'mt-ticket-bus'),
                    'passengerEmail' => __('Passenger Email', 'mt-ticket-bus'),
                    'passengerPhone' => __('Passenger Phone', 'mt-ticket-bus'),
                    'departureDate' => __('Departure Date', 'mt-ticket-bus'),
                    'departureTime' => __('Departure Time', 'mt-ticket-bus'),
                    'clickReservedSeat' => __('Click on a reserved seat to view reservation details.', 'mt-ticket-bus'),
                    'legendAvailable' => __('Available', 'mt-ticket-bus'),
                    'legendReserved' => __('Reserved', 'mt-ticket-bus'),
                    'legendDisabled' => __('Disabled', 'mt-ticket-bus'),
                    'salesChartTickets' => __('Tickets sold', 'mt-ticket-bus'),
                    'salesChartRevenue' => __('Revenue', 'mt-ticket-bus'),
                ),
                'adminUrl' => admin_url('post.php'),
            )
        );
    }

    /**
     * Render overview page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_overview_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/overview.php';
    }

    /**
     * Render settings page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_settings_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render buses page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_buses_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/buses.php';
    }

    /**
     * Render routes page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_routes_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/routes.php';
    }

    /**
     * Render schedules page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_schedules_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/schedules.php';
    }

    /**
     * Render reservations page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_reservations_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/reservations.php';
    }

    /**
     * Whether the "Sales for the year" dashboard widget is enabled.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function is_dashboard_widget_enabled()
    {
        $settings = get_option('mt_ticket_bus_settings', array());
        $show = isset($settings['show_dashboard_widget']) ? $settings['show_dashboard_widget'] : 'yes';
        return ($show !== 'no');
    }

    /**
     * Register the MT Ticket Bus dashboard widget (Sales for the year).
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_dashboard_widget()
    {
        if (! $this->is_dashboard_widget_enabled()) {
            return;
        }
        wp_add_dashboard_widget(
            'mt_ticket_bus_sales',
            __('MT Ticket Bus – Sales for the year', 'mt-ticket-bus'),
            array($this, 'render_dashboard_sales_widget'),
            null,
            null,
            'normal'
        );
    }

    /**
     * Export reservations for the selected course to an XLSX file.
     *
     * Handles admin_post_mt_ticket_bus_export_reservations_xlsx. Requires manage_options,
     * nonce and filter params (date, schedule_id, departure_time). Outputs Excel file download.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function export_reservations_xlsx()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'mt-ticket-bus'), 403);
        }

        if (! isset($_GET['nonce']) || ! wp_verify_nonce(sanitize_text_field(stripslashes((string) ($_GET['nonce'] ?? ''))), 'mt_ticket_bus_export_reservations_xlsx')) {
            wp_die(esc_html__('Security check failed.', 'mt-ticket-bus'), 403);
        }

        $date = isset($_GET['date']) ? sanitize_text_field(stripslashes((string) ($_GET['date'] ?? ''))) : '';
        $schedule_id = isset($_GET['schedule_id']) ? absint($_GET['schedule_id']) : 0;
        $departure_time = isset($_GET['departure_time']) ? sanitize_text_field(stripslashes((string) ($_GET['departure_time'] ?? ''))) : '';

        if ($date === '' || $schedule_id <= 0 || $departure_time === '') {
            wp_die(esc_html__('Missing required parameters (date, schedule, course).', 'mt-ticket-bus'), 400);
        }

        if (! class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            wp_die(
                esc_html__('Excel export requires PhpSpreadsheet. Run: composer require phpoffice/phpspreadsheet', 'mt-ticket-bus'),
                503
            );
        }

        $rows = MT_Ticket_Bus_Reservations::get_instance()->get_reservations_export_rows($date, $schedule_id, $departure_time);

        $headers = array(
            __('Order ID', 'mt-ticket-bus'),
            __('Order Date', 'mt-ticket-bus'),
            __('Product / Ticket', 'mt-ticket-bus'),
            __('Order Status', 'mt-ticket-bus'),
            __('Payment Method', 'mt-ticket-bus'),
            __('Order Notes', 'mt-ticket-bus'),
            __('Seat Number', 'mt-ticket-bus'),
            __('Passenger Name', 'mt-ticket-bus'),
            __('Passenger Email', 'mt-ticket-bus'),
            __('Passenger Phone', 'mt-ticket-bus'),
            __('Departure Date', 'mt-ticket-bus'),
            __('Departure Time', 'mt-ticket-bus'),
            __('Status', 'mt-ticket-bus'),
        );

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('Reservations', 'mt-ticket-bus'));

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $status_labels = array(
            'reserved' => __('Reserved', 'mt-ticket-bus'),
            'confirmed' => __('Confirmed', 'mt-ticket-bus'),
            'cancelled' => __('Cancelled', 'mt-ticket-bus'),
        );

        $DataType = \PhpOffice\PhpSpreadsheet\Cell\DataType::class;
        $row_num = 2;
        foreach ($rows as $row) {
            $status_display = isset($status_labels[$row['status']]) ? $status_labels[$row['status']] : $row['status'];
            $values = array(
                $row['order_id'],
                $row['order_date'],
                $row['product_name'],
                $row['order_status_name'] ?: $row['order_status'],
                $row['payment_method'],
                $row['order_notes'],
                $row['seat_number'],
                $row['passenger_name'],
                $row['passenger_email'],
                $row['passenger_phone'],
                $row['departure_date'],
                $row['departure_time'],
                $status_display,
            );
            $col = 'A';
            $stringColumns = array('A', 'G', 'J'); // Order ID, Seat Number, Passenger Phone – export as string
            foreach ($values as $val) {
                $cell = $col . $row_num;
                if (in_array($col, $stringColumns, true)) {
                    $sheet->setCellValueExplicit($cell, (string) $val, $DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $val);
                }
                $col++;
            }
            $row_num++;
        }

        $filename = 'reservations-' . $date . '-' . $schedule_id . '-' . preg_replace('/[^0-9\-]/', '', $departure_time) . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Build sales chart data for the current year (same structure as overview page).
     *
     * @since 1.0.0
     *
     * @return array{labels: string[], tickets: int[], amounts: float[], currency: string, year: int}
     */
    private function get_sales_chart_data()
    {
        $current_year   = (int) date('Y');
        $chart_labels  = array();
        $chart_tickets = array();
        $chart_amounts = array();
        $currency_symbol = '';
        if (class_exists('MT_Ticket_Bus_WooCommerce_Integration')) {
            $sales_by_month = MT_Ticket_Bus_WooCommerce_Integration::get_ticket_sales_by_month($current_year);
            for ($m = 1; $m <= 12; $m++) {
                $chart_labels[] = date_i18n('M', mktime(0, 0, 0, $m, 1, $current_year));
                $chart_tickets[] = isset($sales_by_month[$m]) ? (int) $sales_by_month[$m]['tickets_count'] : 0;
                $chart_amounts[] = isset($sales_by_month[$m]) ? (float) $sales_by_month[$m]['total_amount'] : 0.0;
            }
            $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        }
        return array(
            'labels'   => $chart_labels,
            'tickets'  => $chart_tickets,
            'amounts'  => $chart_amounts,
            'currency' => $currency_symbol,
            'year'     => $current_year,
        );
    }

    /**
     * Render the dashboard widget content: "Sales for the year" chart.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_dashboard_sales_widget()
    {
        $sales_chart_data = $this->get_sales_chart_data();
        $canvas_id = 'mt-dashboard-sales-chart';
        $data_var = 'mtDashboardSalesData';
?>
        <div class="mt-sales-chart-wrap" style="max-width: 100%; height: 224px;">
            <canvas id="<?php echo esc_attr($canvas_id); ?>" width="400" height="224" aria-label="<?php esc_attr_e('Ticket sales and revenue by month', 'mt-ticket-bus'); ?>"></canvas>
        </div>
        <script>
            window.<?php echo esc_js($data_var); ?> = <?php echo wp_json_encode($sales_chart_data); ?>;
        </script>
        <script>
            (function() {
                function initDashboardSalesChart() {
                    if (typeof Chart === 'undefined' || !window.<?php echo esc_js($data_var); ?>) return;
                    var data = window.<?php echo esc_js($data_var); ?>;
                    var el = document.getElementById('<?php echo esc_js($canvas_id); ?>');
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
                            }, {
                                label: revenueLabel,
                                data: data.amounts,
                                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                yAxisID: 'y1'
                            }]
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

                function tryInit(retries) {
                    retries = retries || 0;
                    if (typeof Chart !== 'undefined' && window.<?php echo esc_js($data_var); ?>) {
                        initDashboardSalesChart();
                        return;
                    }
                    if (retries < 50) {
                        setTimeout(function() {
                            tryInit(retries + 1);
                        }, 100);
                    }
                }
                if (document.readyState === 'loading') {
                    window.addEventListener('load', function() {
                        tryInit(0);
                    });
                } else {
                    tryInit(0);
                }
            })();
        </script>
<?php
    }
}
