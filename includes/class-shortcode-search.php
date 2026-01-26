<?php

/**
 * Ticket Search Shortcode
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode class for ticket search functionality
 */
class MT_Ticket_Bus_Shortcode_Search
{
    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Shortcode_Search
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Shortcode_Search
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Register shortcode
        add_shortcode('mt_ticket_search', array($this, 'render_search_form'));

        // AJAX handlers
        add_action('wp_ajax_mt_get_start_stations', array($this, 'ajax_get_start_stations'));
        add_action('wp_ajax_nopriv_mt_get_start_stations', array($this, 'ajax_get_start_stations'));

        add_action('wp_ajax_mt_get_end_stations', array($this, 'ajax_get_end_stations'));
        add_action('wp_ajax_nopriv_mt_get_end_stations', array($this, 'ajax_get_end_stations'));

        add_action('wp_ajax_mt_search_tickets', array($this, 'ajax_search_tickets'));
        add_action('wp_ajax_nopriv_mt_search_tickets', array($this, 'ajax_search_tickets'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Handle search results page
        add_action('template_redirect', array($this, 'handle_search_results_page'));
    }

    /**
     * Render search form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_search_form($atts)
    {
        $atts = shortcode_atts(
            array(
                'class' => '',
            ),
            $atts,
            'mt_ticket_search'
        );

        ob_start();
?>
        <div class="mt-ticket-search-form-container <?php echo esc_attr($atts['class']); ?>">
            <form id="mt-ticket-search-form" class="mt-ticket-search-form" method="get" action="<?php echo esc_url(home_url('/ticket-search-results/')); ?>">
                <div class="mt-search-form-wrapper">
                    <div class="mt-search-form-left">
                        <div class="mt-search-form-row">
                            <h3 class="mt-ticket-search-title" style="text-align: left;"><?php esc_html_e('BUY TICKET', 'mt-ticket-bus'); ?></h3>
                        </div>
                        <div class="mt-search-form-row">
                            <div class="mt-search-field">
                                <label for="mt-search-from"><?php esc_html_e('From:', 'mt-ticket-bus'); ?></label>
                                <select id="mt-search-from" name="from" class="mt-select2-input" required>
                                    <option value=""><?php esc_html_e('Select departure station', 'mt-ticket-bus'); ?></option>
                                </select>
                                <input type="hidden" id="mt-search-from-value" name="from_value">
                            </div>

                            <div class="mt-search-field">
                                <label for="mt-search-to"><?php esc_html_e('To:', 'mt-ticket-bus'); ?></label>
                                <select id="mt-search-to" name="to" class="mt-select2-input" required disabled>
                                    <option value=""><?php esc_html_e('Select arrival station', 'mt-ticket-bus'); ?></option>
                                </select>
                                <input type="hidden" id="mt-search-to-value" name="to_value">
                            </div>

                            <div class="mt-search-field">
                                <label for="mt-search-date-from"><?php esc_html_e('Date From:', 'mt-ticket-bus'); ?></label>
                                <div style="position: relative;">
                                    <input type="date" id="mt-search-date-from" name="date_from" class="mt-date-input" required>
                                </div>
                            </div>

                            <div class="mt-search-field">
                                <label for="mt-search-date-to"><?php esc_html_e('Date To:', 'mt-ticket-bus'); ?></label>
                                <div style="position: relative;">
                                    <input type="date" id="mt-search-date-to" name="date_to" class="mt-date-input" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-search-form-right">
                        <div class="mt-search-field mt-search-submit">
                            <button type="submit" class="mt-search-button"><?php esc_html_e('Search', 'mt-ticket-bus'); ?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting start stations
     */
    public function ajax_get_start_stations()
    {
        check_ajax_referer('mt_ticket_search', 'nonce');

        $routes = MT_Ticket_Bus_Routes::get_instance();
        $all_routes = $routes->get_all_routes();

        $start_stations = array();
        foreach ($all_routes as $route) {
            if ($route->status === 'active' && !empty($route->start_station)) {
                if (!in_array($route->start_station, $start_stations)) {
                    $start_stations[] = $route->start_station;
                }
            }
        }

        sort($start_stations);

        wp_send_json_success(array('stations' => $start_stations));
    }

    /**
     * AJAX handler for getting end stations based on start station
     */
    public function ajax_get_end_stations()
    {
        check_ajax_referer('mt_ticket_search', 'nonce');

        $start_station = isset($_POST['start_station']) ? sanitize_text_field($_POST['start_station']) : '';

        if (empty($start_station)) {
            wp_send_json_error(array('message' => __('Start station is required.', 'mt-ticket-bus')));
        }

        $routes = MT_Ticket_Bus_Routes::get_instance();
        $all_routes = $routes->get_all_routes();

        $end_stations = array();
        foreach ($all_routes as $route) {
            if ($route->status === 'active' && $route->start_station === $start_station && !empty($route->end_station)) {
                if (!in_array($route->end_station, $end_stations)) {
                    $end_stations[] = $route->end_station;
                }
            }
        }

        sort($end_stations);

        wp_send_json_success(array('stations' => $end_stations));
    }

    /**
     * AJAX handler for searching tickets
     */
    public function ajax_search_tickets()
    {
        check_ajax_referer('mt_ticket_search', 'nonce');

        $from = isset($_POST['from']) ? sanitize_text_field($_POST['from']) : '';
        $to = isset($_POST['to']) ? sanitize_text_field($_POST['to']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';

        if (empty($from) || empty($to) || empty($date_from) || empty($date_to)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'mt-ticket-bus')));
        }

        // Perform search
        $results = $this->search_tickets($from, $to, $date_from, $date_to);

        wp_send_json_success(array('results' => $results));
    }

    /**
     * Search for tickets
     *
     * @param string $from Start station
     * @param string $to End station
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Search results
     */
    private function search_tickets($from, $to, $date_from, $date_to)
    {
        $results = array();

        // Get routes matching from/to
        $routes = MT_Ticket_Bus_Routes::get_instance();
        $all_routes = $routes->get_all_routes();

        $matching_routes = array();
        foreach ($all_routes as $route) {
            if ($route->status === 'active' && $route->start_station === $from && $route->end_station === $to) {
                $matching_routes[] = $route;
            }
        }

        if (empty($matching_routes)) {
            return $results;
        }

        // Get all ticket products
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mt_is_ticket_product',
                    'value' => 'yes',
                ),
            ),
        );

        $products = get_posts($args);

        // Convert date range to timestamps
        $date_from_ts = strtotime($date_from);
        $date_to_ts = strtotime($date_to . ' 23:59:59');

        foreach ($products as $product) {
            $product_id = $product->ID;
            // Use correct meta field names that match what's saved in WooCommerce integration
            $route_id = get_post_meta($product_id, '_mt_bus_route_id', true);
            $schedule_id = get_post_meta($product_id, '_mt_bus_schedule_id', true);

            if (!$route_id || !$schedule_id) {
                continue;
            }

            // Check if route matches
            $route_matches = false;
            foreach ($matching_routes as $route) {
                if ($route->id == $route_id) {
                    $route_matches = true;
                    break;
                }
            }

            if (!$route_matches) {
                continue;
            }

            // Get schedule and courses
            $schedules = MT_Ticket_Bus_Schedules::get_instance();
            $schedule = $schedules->get_schedule($schedule_id);

            if (!$schedule || $schedule->status !== 'active') {
                continue;
            }

            // Get courses for the date range
            $courses = $this->get_courses_for_date_range($schedule, $date_from_ts, $date_to_ts);

            foreach ($courses as $course) {
                $results[] = array(
                    'product_id' => $product_id,
                    'product_name' => $product->post_title,
                    'route_id' => $route_id,
                    'schedule_id' => $schedule_id,
                    'bus_id' => get_post_meta($product_id, '_mt_bus_id', true),
                    'departure_date' => $course['date'],
                    'departure_time' => $course['departure_time'],
                    'arrival_time' => $course['arrival_time'],
                    'price' => get_post_meta($product_id, '_price', true),
                );
            }
        }

        return $results;
    }

    /**
     * Get courses for date range
     *
     * @param object $schedule Schedule object
     * @param int $date_from_ts Start date timestamp
     * @param int $date_to_ts End date timestamp
     * @return array Courses
     */
    private function get_courses_for_date_range($schedule, $date_from_ts, $date_to_ts)
    {
        $courses = array();
        $current_date = $date_from_ts;

        while ($current_date <= $date_to_ts) {
            $day_of_week = date('w', $current_date); // 0 = Sunday, 6 = Saturday
            $day_name = $this->get_day_name($day_of_week);

            // Check if schedule runs on this day
            if ($this->schedule_runs_on_day($schedule, $day_name)) {
                // Get courses for this day
                $day_courses = $this->parse_schedule_courses($schedule);
                foreach ($day_courses as $course) {
                    $course_datetime = strtotime(date('Y-m-d', $current_date) . ' ' . $course['departure_time']);

                    // Check if course is in date range
                    if ($course_datetime >= $date_from_ts && $course_datetime <= $date_to_ts) {
                        // Check if course has already passed (for today's date)
                        $today = date('Y-m-d');
                        $course_date = date('Y-m-d', $current_date);
                        $current_time = current_time('timestamp'); // Use WordPress current time

                        if ($course_date === $today && $course_datetime < $current_time) {
                            continue; // Skip courses that have already passed today
                        }

                        $courses[] = array(
                            'date' => date('Y-m-d', $current_date),
                            'departure_time' => $course['departure_time'],
                            'arrival_time' => $course['arrival_time'],
                        );
                    }
                }
            }

            $current_date = strtotime('+1 day', $current_date);
        }

        return $courses;
    }


    /**
     * Get day name from day number
     *
     * @param int $day_number Day number (0-6)
     * @return string Day name
     */
    private function get_day_name($day_number)
    {
        $days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
        return isset($days[$day_number]) ? $days[$day_number] : '';
    }

    /**
     * Parse schedule courses
     *
     * @param object $schedule Schedule object
     * @return array Courses
     */
    private function parse_schedule_courses($schedule)
    {
        $courses = array();

        if (empty($schedule->courses)) {
            return $courses;
        }

        $courses_data = json_decode($schedule->courses, true);
        if (!is_array($courses_data)) {
            return $courses;
        }

        foreach ($courses_data as $course) {
            if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                $courses[] = array(
                    'departure_time' => $course['departure_time'],
                    'arrival_time' => $course['arrival_time'],
                );
            }
        }

        return $courses;
    }

    /**
     * Check if schedule runs on specific day (handles special values like 'all', 'weekdays', 'weekend')
     *
     * @param object $schedule Schedule object
     * @param string $day_name Day name
     * @return bool
     */
    private function schedule_runs_on_day($schedule, $day_name)
    {
        if (empty($schedule->days_of_week)) {
            return false;
        }

        $schedules = MT_Ticket_Bus_Schedules::get_instance();
        $parsed_days = $schedules->parse_days_of_week($schedule->days_of_week);

        // Handle special values
        if ($parsed_days === 'all') {
            return true;
        }

        if ($parsed_days === 'weekdays') {
            $weekdays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
            return in_array($day_name, $weekdays);
        }

        if ($parsed_days === 'weekend') {
            $weekend = array('saturday', 'sunday');
            return in_array($day_name, $weekend);
        }

        // Array of days
        if (is_array($parsed_days)) {
            return in_array($day_name, $parsed_days);
        }

        // Single day string
        return $parsed_days === $day_name;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts()
    {
        // Only enqueue on pages with shortcode or search results page
        global $post;
        $has_shortcode = is_object($post) && has_shortcode($post->post_content, 'mt_ticket_search');
        $is_search_results = isset($_GET['from']) && isset($_GET['to']) && isset($_GET['date_from']) && isset($_GET['date_to']);

        if (!$has_shortcode && !$is_search_results) {
            return;
        }

        // Enqueue Select2 CSS
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            array(),
            '4.1.0'
        );

        // Enqueue SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11.0.0'
        );

        wp_enqueue_style(
            'mt-ticket-search',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/css/search.css',
            array('select2'),
            mt_ticket_bus_get_asset_version('assets/css/search.css')
        );

        // Enqueue Select2 JS
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            '4.1.0',
            true
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
            'mt-ticket-search',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/search.js',
            array('jquery', 'select2', 'sweetalert2'),
            mt_ticket_bus_get_asset_version('assets/js/search.js'),
            true
        );

        wp_localize_script(
            'mt-ticket-search',
            'mtTicketSearch',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ticket_search'),
                'seatmapNonce' => wp_create_nonce('mt_ticket_bus_frontend'),
                'resultsUrl' => home_url('/ticket-search-results/'),
                'i18n' => array(
                    'selectStation' => __('Select station', 'mt-ticket-bus'),
                    'noResults' => __('No results found', 'mt-ticket-bus'),
                    'searching' => __('Searching...', 'mt-ticket-bus'),
                    'selectSeatFirst' => __('Please select a seat from the seat map first.', 'mt-ticket-bus'),
                    'fillAllFields' => __('Please fill in all fields', 'mt-ticket-bus'),
                    'selectSeat' => __('Please select a seat first', 'mt-ticket-bus'),
                    'ticketAdded' => __('Ticket added to cart successfully!', 'mt-ticket-bus'),
                    'errorAddingToCart' => __('Error adding to cart', 'mt-ticket-bus'),
                    'ok' => __('OK', 'mt-ticket-bus'),
                ),
            )
        );

        // Enqueue seatmap and ticket summary scripts for results page
        if (isset($_GET['from']) && isset($_GET['to']) && isset($_GET['date_from']) && isset($_GET['date_to'])) {
            // Enqueue seatmap styles
            wp_enqueue_style(
                'mt-ticket-bus-blocks',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/css/blocks.css',
                array(),
                mt_ticket_bus_get_asset_version('assets/css/blocks.css')
            );

            // Enqueue seatmap script
            wp_enqueue_script(
                'mt-ticket-bus-seatmap',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/js/seatmap.js',
                array('jquery'),
                mt_ticket_bus_get_asset_version('assets/js/seatmap.js'),
                true
            );

            // Enqueue ticket summary script for cart functionality
            wp_enqueue_script(
                'mt-ticket-bus-ticket-summary',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/js/ticket-summary.js',
                array('jquery'),
                mt_ticket_bus_get_asset_version('assets/js/ticket-summary.js'),
                true
            );

            // Localize seatmap script (reuse from blocks class)
            wp_localize_script(
                'mt-ticket-bus-seatmap',
                'mtTicketBus',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mt_ticket_bus_frontend'),
                    'calendarWeekStart' => get_option('start_of_week') == 1 ? 'monday' : 'sunday',
                    'i18n' => array(
                        'selectDate' => __('Select a date', 'mt-ticket-bus'),
                        'selectTime' => __('Select a time', 'mt-ticket-bus'),
                        'selectSeat' => __('Select your seat(s)', 'mt-ticket-bus'),
                        'available' => __('Available', 'mt-ticket-bus'),
                        'reserved' => __('Reserved', 'mt-ticket-bus'),
                        'selected' => __('Selected', 'mt-ticket-bus'),
                        'disabled' => __('Disabled', 'mt-ticket-bus'),
                    ),
                )
            );

            // Localize ticket summary script for cart functionality
            wp_localize_script(
                'mt-ticket-bus-ticket-summary',
                'mtTicketSummary',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mt_ticket_bus_frontend'),
                    'cartUrl' => wc_get_cart_url(),
                    'checkoutUrl' => wc_get_checkout_url(),
                )
            );
        }
    }

    /**
     * Handle search results page
     */
    public function handle_search_results_page()
    {
        // Check if we're on a search results request
        if (!isset($_GET['from']) || !isset($_GET['to']) || !isset($_GET['date_from']) || !isset($_GET['date_to'])) {
            return;
        }

        // Set page title
        add_filter('document_title_parts', array($this, 'set_search_results_title'));
        add_filter('wp_title', array($this, 'set_search_results_title_legacy'), 10, 2);

        // Load search results template
        add_filter('template_include', array($this, 'load_search_results_template'));
    }

    /**
     * Set search results page title (WordPress 4.4+)
     *
     * @param array $title_parts Title parts
     * @return array Modified title parts
     */
    public function set_search_results_title($title_parts)
    {
        if (isset($_GET['from']) && isset($_GET['to']) && isset($_GET['date_from']) && isset($_GET['date_to'])) {
            $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
            $to = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : '';
            $title_parts['title'] = sprintf(__('Search Results: %s to %s', 'mt-ticket-bus'), $from, $to);
        }
        return $title_parts;
    }

    /**
     * Set search results page title (Legacy WordPress)
     *
     * @param string $title Page title
     * @param string $sep Title separator
     * @return string Modified title
     */
    public function set_search_results_title_legacy($title, $sep = '')
    {
        if (isset($_GET['from']) && isset($_GET['to']) && isset($_GET['date_from']) && isset($_GET['date_to'])) {
            $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
            $to = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : '';
            $title = sprintf(__('Search Results: %s to %s', 'mt-ticket-bus'), $from, $to);
        }
        return $title;
    }

    /**
     * Load search results template
     *
     * @param string $template Default template
     * @return string Template path
     */
    public function load_search_results_template($template)
    {
        // Only intercept if we have search parameters
        if (isset($_GET['from']) && isset($_GET['to']) && isset($_GET['date_from']) && isset($_GET['date_to'])) {
            $search_template = MT_TICKET_BUS_PLUGIN_DIR . 'templates/search-results.php';
            if (file_exists($search_template)) {
                return $search_template;
            }
        }
        return $template;
    }
}
