<?php

/**
 * WooCommerce integration class
 *
 * Handles integration with WooCommerce for bus ticket products
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration class.
 *
 * Handles all integration between MT Ticket Bus plugin and WooCommerce,
 * including product meta fields, cart functionality, order processing,
 * and ticket display/printing.
 *
 * @since 1.0.0
 */
class MT_Ticket_Bus_WooCommerce_Integration
{

    /**
     * Plugin instance.
     *
     * @since 1.0.0
     *
     * @var MT_Ticket_Bus_WooCommerce_Integration
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @since 1.0.0
     *
     * @return MT_Ticket_Bus_WooCommerce_Integration Plugin instance.
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
     * Initializes WooCommerce integration hooks and filters.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        // Add product meta fields
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_meta_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta_fields'));

        // Make product virtual by default
        add_action('woocommerce_product_options_general_product_data', array($this, 'set_virtual_default'));

        // Customize product page (for standard themes - block themes use blocks)
        // Only activate if not using block theme
        if (! wp_is_block_theme()) {
            add_action('woocommerce_before_single_product', array($this, 'maybe_customize_single_product'), 1);
        }

        // AJAX handler for getting schedules by route
        add_action('wp_ajax_mt_get_schedules_by_route', array($this, 'ajax_get_schedules_by_route'));
        add_action('wp_ajax_nopriv_mt_get_schedules_by_route', array($this, 'ajax_get_schedules_by_route'));

        // AJAX handlers for seatmap functionality
        add_action('wp_ajax_mt_get_available_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_nopriv_mt_get_available_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_mt_get_available_seats', array($this, 'ajax_get_available_seats'));
        add_action('wp_ajax_nopriv_mt_get_available_seats', array($this, 'ajax_get_available_seats'));
        add_action('wp_ajax_mt_get_course_availability', array($this, 'ajax_get_course_availability'));
        add_action('wp_ajax_nopriv_mt_get_course_availability', array($this, 'ajax_get_course_availability'));

        // AJAX handlers for admin reservations page
        add_action('wp_ajax_mt_get_courses_by_schedule', array($this, 'ajax_get_courses_by_schedule'));
        add_action('wp_ajax_mt_get_available_dates_admin', array($this, 'ajax_get_available_dates_admin'));
        add_action('wp_ajax_mt_get_available_seats_admin', array($this, 'ajax_get_available_seats_admin'));

        // AJAX handlers for adding tickets to cart
        add_action('wp_ajax_mt_add_tickets_to_cart', array($this, 'ajax_add_tickets_to_cart'));
        add_action('wp_ajax_nopriv_mt_add_tickets_to_cart', array($this, 'ajax_add_tickets_to_cart'));

        // Save ticket meta data to cart items
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_ticket_cart_item_data'), 10, 3);

        // Display ticket meta in cart and checkout
        add_filter('woocommerce_get_item_data', array($this, 'display_ticket_cart_item_data'), 10, 2);

        // Save ticket meta to order items
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_ticket_order_item_meta'), 10, 4);
        // Apply extras pricing on cart items
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_extras_price_adjustments'), 20, 1);

        // Ticket = one seat = quantity always 1 in cart: hide quantity input and enforce 1
        add_filter('woocommerce_cart_item_quantity', array($this, 'cart_item_quantity_ticket_one'), 10, 3);
        add_action('woocommerce_cart_loaded_from_session', array($this, 'cart_normalize_ticket_quantity'), 10, 1);

        // Display ticket reservation info in order received page
        add_action('woocommerce_order_item_meta_end', array($this, 'display_ticket_order_item_meta'), 10, 3);

        // Add print and download buttons to order received page
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_ticket_actions'), 10, 1);
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_order_received_additional_information'), 5, 1);

        // Handle ticket print and download requests
        add_action('wp_ajax_mt_print_ticket', array($this, 'ajax_print_ticket'));
        add_action('wp_ajax_nopriv_mt_print_ticket', array($this, 'ajax_print_ticket'));
        add_action('wp_ajax_mt_download_ticket', array($this, 'ajax_download_ticket'));
        add_action('wp_ajax_nopriv_mt_download_ticket', array($this, 'ajax_download_ticket'));

        // Enqueue scripts for order received page
        add_action('wp_enqueue_scripts', array($this, 'enqueue_order_received_scripts'));

        // Block cart: hide quantity for ticket products (one seat = 1)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_cart_block_ticket_qty'), 25);

        // Handle print ticket request
        add_action('template_redirect', array($this, 'handle_print_ticket_request'));

        // Handle download ticket request via URL
        add_action('template_redirect', array($this, 'handle_download_ticket_request'));

        // Hide and customize order item meta display in admin
        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'hide_order_item_meta'), 10, 1);
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'format_order_item_meta_key'), 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'format_order_item_meta_value'), 10, 3);

        // Customize order emails for ticket orders (subject, heading, content, PDF attachment)
        add_filter('woocommerce_email_subject', array($this, 'customize_ticket_order_email_subject'), 10, 3);
        add_filter('woocommerce_email_heading', array($this, 'customize_ticket_order_email_heading'), 10, 3);
        add_filter('woocommerce_email_additional_content_customer_processing_order', array($this, 'add_ticket_order_email_content'), 10, 3);
        add_filter('woocommerce_email_additional_content_customer_completed_order', array($this, 'add_ticket_order_email_content'), 10, 3);
        add_filter('woocommerce_email_additional_content_new_order', array($this, 'add_ticket_order_email_content'), 10, 3);

        add_filter('woocommerce_email_attachments', array($this, 'attach_ticket_pdf_to_email'), 10, 4);
        add_filter('woocommerce_email_order_items_args', array($this, 'hide_ticket_order_email_product_image'), 10, 1);
        add_filter('woocommerce_email_recipient', array($this, 'add_passenger_email_recipient'), 10, 3);

        // Checkout: "Buy for someone else" (after billing form so visible on all themes/shortcode checkout)
        add_action('woocommerce_after_checkout_billing_form', array($this, 'checkout_buy_for_someone_else_fields'), 10, 1);
        add_action('woocommerce_init', array($this, 'register_block_checkout_buy_for_other_fields'), 20);
        add_action('woocommerce_checkout_process', array($this, 'checkout_validate_buy_for_someone_else'), 10, 0);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'checkout_save_buy_for_someone_else'), 10, 2);
        add_filter('woocommerce_checkout_get_value', array($this, 'checkout_get_buy_for_someone_else_value'), 10, 2);

        // Enqueue admin scripts for WooCommerce product edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        // Checkout page styles (e.g. block passenger field labels)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_styles'), 20);
        // Order received: body class to hide "Additional information" when passenger_show is no; styles for that section
        add_filter('body_class', array($this, 'order_received_body_class'), 10, 1);
    }

    /**
     * Conditionally customize single product page for ticket products.
     *
     * Only applies to standard themes (not block themes).
     * Removes default WooCommerce elements and adds custom ticket display.
     *
     * @since 1.0.0
     */
    public function maybe_customize_single_product()
    {
        if (!function_exists('is_product') || !is_product()) return;

        $product_id = get_queried_object_id();
        if (!$product_id) return;

        $product = wc_get_product($product_id);
        if (!$product) return;

        $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
        if ($is_ticket_product !== 'yes') return;

        // Remove default WooCommerce elements
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

        // Add our custom ticket blocks using shared renderer
        add_action('woocommerce_before_single_product_summary', array($this, 'display_ticket_seatmap'), 20);
        add_action('woocommerce_single_product_summary', array($this, 'display_ticket_summary'), 10);
    }

    /**
     * Display ticket seatmap for standard themes.
     *
     * @since 1.0.0
     */
    public function display_ticket_seatmap()
    {
        echo wp_kses_post(MT_Ticket_Bus_Renderer::render_seatmap());
    }

    /**
     * Display ticket summary for standard themes.
     *
     * @since 1.0.0
     */
    public function display_ticket_summary()
    {
        echo wp_kses_post(MT_Ticket_Bus_Renderer::render_ticket_summary());
    }

    /**
     * AJAX handler: Get available dates for a schedule.
     *
     * @since 1.0.0
     */
    public function ajax_get_available_dates()
    {
        check_ajax_referer('mt_ticket_bus_frontend', 'nonce');

        $schedule_id = isset($_POST['schedule_id']) ? absint($_POST['schedule_id']) : 0;
        $bus_id = isset($_POST['bus_id']) ? absint($_POST['bus_id']) : 0;
        $month = isset($_POST['month']) ? absint($_POST['month']) : (int) gmdate('n');
        $year = isset($_POST['year']) ? absint($_POST['year']) : (int) gmdate('Y');

        if (! $schedule_id || ! $bus_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }

        $schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($schedule_id);
        if (! $schedule) {
            wp_send_json_error(array('message' => __('Schedule not found.', 'mt-ticket-bus')));
        }

        $available_dates = MT_Ticket_Bus_Renderer::get_available_dates($schedule, $month, $year);

        // Just mark dates as available (without seat count) - seat availability will be shown per course
        $dates_with_availability = array();
        foreach ($available_dates as $date_info) {
            // Check if date has at least one available course
            $has_availability = MT_Ticket_Bus_Renderer::check_date_availability(
                $schedule_id,
                $bus_id,
                $date_info['date']
            );
            $dates_with_availability[] = array_merge($date_info, array(
                'available' => $has_availability['available'],
            ));
        }

        wp_send_json_success(array(
            'dates' => $dates_with_availability,
            'month' => $month,
            'year' => $year,
        ));
    }

    /**
     * AJAX handler: Get available seats for a specific date/time.
     *
     * @since 1.0.0
     */
    public function ajax_get_available_seats()
    {
        check_ajax_referer('mt_ticket_bus_frontend', 'nonce');

        $schedule_id = isset($_POST['schedule_id']) ? absint($_POST['schedule_id']) : 0;
        $bus_id = isset($_POST['bus_id']) ? absint($_POST['bus_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
        $departure_time = isset($_POST['departure_time']) ? sanitize_text_field(wp_unslash($_POST['departure_time'])) : '';

        if (! $schedule_id || ! $bus_id || ! $date || ! $departure_time) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }

        // Validate date format
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'mt-ticket-bus')));
        }

        // Get bus seat layout
        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
        if (! $bus || empty($bus->seat_layout)) {
            wp_send_json_error(array('message' => __('Bus seat layout not found.', 'mt-ticket-bus')));
        }

        $layout_data = json_decode($bus->seat_layout, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! isset($layout_data['seats'])) {
            wp_send_json_error(array('message' => __('Invalid seat layout.', 'mt-ticket-bus')));
        }

        // Get available seats
        $available_seats = MT_Ticket_Bus_Reservations::get_instance()->get_available_seats(
            $schedule_id,
            $date,
            $departure_time,
            $bus_id
        );

        wp_send_json_success(array(
            'seat_layout' => $layout_data,
            'available_seats' => $available_seats,
            'total_seats' => (int) $bus->total_seats,
            'reserved_count' => (int) $bus->total_seats - count($available_seats),
        ));
    }

    /**
     * AJAX handler: Get course availability for a specific date.
     *
     * @since 1.0.0
     */
    public function ajax_get_course_availability()
    {
        check_ajax_referer('mt_ticket_bus_frontend', 'nonce');

        $schedule_id = isset($_POST['schedule_id']) ? absint($_POST['schedule_id']) : 0;
        $bus_id = isset($_POST['bus_id']) ? absint($_POST['bus_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

        if (! $schedule_id || ! $bus_id || ! $date) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }

        // Validate date format
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'mt-ticket-bus')));
        }

        $schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($schedule_id);
        if (! $schedule) {
            wp_send_json_error(array('message' => __('Schedule not found.', 'mt-ticket-bus')));
        }

        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
        if (! $bus) {
            wp_send_json_error(array('message' => __('Bus not found.', 'mt-ticket-bus')));
        }

        $total_seats = (int) $bus->total_seats;
        $courses = MT_Ticket_Bus_Renderer::get_schedule_courses($schedule);

        if (empty($courses)) {
            wp_send_json_error(array('message' => __('No courses found for this schedule.', 'mt-ticket-bus')));
        }

        // Get availability for each course
        $courses_availability = array();
        foreach ($courses as $course) {
            $departure_time = $course['departure_time'];
            $available_seats = MT_Ticket_Bus_Reservations::get_instance()->get_available_seats(
                $schedule_id,
                $date,
                $departure_time,
                $bus_id
            );
            $available_count = count($available_seats);

            $courses_availability[] = array(
                'departure_time' => $departure_time,
                'arrival_time' => $course['arrival_time'],
                'available_seats' => $available_count,
                'total_seats' => $total_seats,
                'reserved_seats' => $total_seats - $available_count,
                'available' => $available_count > 0,
            );
        }

        wp_send_json_success(array(
            'courses' => $courses_availability,
            'date' => $date,
        ));
    }

    /**
     * AJAX handler: Add tickets to cart.
     *
     * @since 1.0.0
     */
    public function ajax_add_tickets_to_cart()
    {
        check_ajax_referer('mt_ticket_bus_frontend', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below per field.
        $raw_tickets = isset($_POST['tickets']) && is_array($_POST['tickets']) ? wp_unslash($_POST['tickets']) : array();
        $tickets = array();
        foreach ($raw_tickets as $t) {
            if (! is_array($t)) {
                continue;
            }
            $tickets[] = array(
                'date' => isset($t['date']) ? sanitize_text_field($t['date']) : '',
                'time' => isset($t['time']) ? sanitize_text_field($t['time']) : '',
                'seat' => isset($t['seat']) ? sanitize_text_field($t['seat']) : '',
                'extras' => isset($t['extras']) && is_array($t['extras']) ? array_map('absint', $t['extras']) : array(),
                'segment_start_index' => isset($t['segment_start_index']) ? absint($t['segment_start_index']) : 0,
                'segment_end_index' => isset($t['segment_end_index']) ? absint($t['segment_end_index']) : 0,
                'segment_start_name' => isset($t['segment_start_name']) ? sanitize_text_field($t['segment_start_name']) : '',
                'segment_end_name' => isset($t['segment_end_name']) ? sanitize_text_field($t['segment_end_name']) : '',
            );
        }
        $buy_now = isset($_POST['buy_now']) && sanitize_text_field(wp_unslash($_POST['buy_now'] ?? '')) === 'true';

        if (! $product_id || empty($tickets)) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }

        // Verify product exists and is purchasable
        $product = wc_get_product($product_id);
        if (! $product || ! $product->is_purchasable()) {
            wp_send_json_error(array('message' => __('Product is not available for purchase.', 'mt-ticket-bus')));
        }

        // Get allowed extras for this ticket product (used for validation and pricing).
        $allowed_extras = get_post_meta($product_id, '_mt_ticket_extras_ids', true);
        if (! is_array($allowed_extras)) {
            $allowed_extras = array();
        }
        $allowed_extras = array_map('absint', $allowed_extras);

        // Add each ticket as a separate cart item
        $added_count = 0;
        $errors      = array();

        foreach ($tickets as $ticket) {
            if (! isset($ticket['date']) || ! isset($ticket['time']) || ! isset($ticket['seat'])) {
                continue;
            }

            // Validate ticket data
            $date = sanitize_text_field($ticket['date']);
            $time = sanitize_text_field($ticket['time']);
            $seat = sanitize_text_field($ticket['seat']);

            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                /* translators: %s: seat number or identifier */
                $errors[] = sprintf(__('Invalid date format for seat %s.', 'mt-ticket-bus'), $seat);
                continue;
            }

            // Extras selected for this ticket (optional).
            $ticket_extras = array();
            if (isset($ticket['extras']) && is_array($ticket['extras']) && ! empty($allowed_extras)) {
                foreach ($ticket['extras'] as $extra_id) {
                    $extra_id = absint($extra_id);
                    if ($extra_id > 0 && in_array($extra_id, $allowed_extras, true)) {
                        $ticket_extras[] = $extra_id;
                    }
                }
                $ticket_extras = array_values(array_unique($ticket_extras));
            }

            // Segment (start/end stop) – optional; validated against product route
            $segment_start_index = isset($ticket['segment_start_index']) ? absint($ticket['segment_start_index']) : 0;
            $segment_end_index   = isset($ticket['segment_end_index']) ? absint($ticket['segment_end_index']) : 0;
            $segment_start_name  = isset($ticket['segment_start_name']) ? sanitize_text_field($ticket['segment_start_name']) : '';
            $segment_end_name    = isset($ticket['segment_end_name']) ? sanitize_text_field($ticket['segment_end_name']) : '';
            $route_id            = get_post_meta($product_id, '_mt_bus_route_id', true);
            $stops               = array();
            if ($route_id) {
                $route = MT_Ticket_Bus_Routes::get_instance()->get_route($route_id);
                if ($route) {
                    $stops = $this->get_route_stops_for_pricing($route);
                }
            }
            $stops_count = count($stops);
            if ($stops_count === 0 || $segment_end_index <= $segment_start_index || $segment_end_index >= $stops_count) {
                $segment_start_index = 0;
                $segment_end_index   = $stops_count > 0 ? $stops_count - 1 : 0;
                $segment_start_name  = $stops_count > 0 ? $stops[0]['name'] : '';
                $segment_end_name    = $stops_count > 0 ? $stops[$stops_count - 1]['name'] : '';
            }

            // Prepare cart item data with ticket meta
            $cart_item_data = array(
                'mt_ticket_date' => $date,
                'mt_ticket_time' => $time,
                'mt_ticket_seat' => $seat,
                'mt_ticket_product_id' => $product_id,
                'mt_ticket_extras' => $ticket_extras,
                'mt_ticket_segment_start_index' => $segment_start_index,
                'mt_ticket_segment_end_index'   => $segment_end_index,
                'mt_ticket_segment_start_name' => $segment_start_name,
                'mt_ticket_segment_end_name'   => $segment_end_name,
            );

            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

            if ($cart_item_key) {
                $added_count++;
            } else {
                /* translators: %s: seat number or identifier */
                $errors[] = sprintf(__('Failed to add seat %s to cart.', 'mt-ticket-bus'), $seat);
            }
        }

        if ($added_count === 0) {
            wp_send_json_error(array(
                'message' => __('Failed to add tickets to cart.', 'mt-ticket-bus'),
                'errors' => $errors,
            ));
        }

        // Return success response
        $response = array(
            'message' => sprintf(
                /* translators: %d: number of tickets */
                _n('%d ticket added to cart.', '%d tickets added to cart.', $added_count, 'mt-ticket-bus'),
                $added_count
            ),
            'added_count' => $added_count,
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url(),
        );

        if ($buy_now) {
            $response['redirect'] = wc_get_checkout_url();
        } else {
            // Return cart fragments for AJAX cart update
            ob_start();
            woocommerce_mini_cart();
            $mini_cart = ob_get_clean();

            $response['fragments'] = apply_filters('woocommerce_add_to_cart_fragments', array(
                'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
            ));
            $response['cart_hash'] = WC()->cart->get_cart_hash();
        }

        wp_send_json_success($response);
    }

    /**
     * Add ticket meta data to cart item
     */
    /**
     * Add ticket data to cart item.
     *
     * @since 1.0.0
     *
     * @param array $cart_item_data Cart item data.
     * @param int   $product_id     Product ID.
     * @param int   $variation_id   Variation ID.
     * @return array Modified cart item data.
     */
    public function add_ticket_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        // Check if this is a ticket product
        if (get_post_meta($product_id, '_mt_is_ticket_product', true) !== 'yes') {
            return $cart_item_data;
        }

        // Ensure extras key exists even if empty so later logic can rely on it.
        if (! isset($cart_item_data['mt_ticket_extras'])) {
            $cart_item_data['mt_ticket_extras'] = array();
        }

        // Ensure segment keys exist for ticket items
        if (! isset($cart_item_data['mt_ticket_segment_start_index'])) {
            $cart_item_data['mt_ticket_segment_start_index'] = 0;
        }
        if (! isset($cart_item_data['mt_ticket_segment_end_index'])) {
            $cart_item_data['mt_ticket_segment_end_index'] = 0;
        }
        if (! isset($cart_item_data['mt_ticket_segment_start_name'])) {
            $cart_item_data['mt_ticket_segment_start_name'] = '';
        }
        if (! isset($cart_item_data['mt_ticket_segment_end_name'])) {
            $cart_item_data['mt_ticket_segment_end_name'] = '';
        }

        return $cart_item_data;
    }

    /**
     * Build route stops array (name + cumulative percent) for segment pricing.
     *
     * @param object $route Route object with start_station, end_station, intermediate_stations (JSON).
     * @return array List of {name, percent}. Start 0%, end 100%.
     */
    private function get_route_stops_for_pricing($route)
    {
        $stops = array();
        $start = ! empty($route->start_station) ? $route->start_station : __('Start', 'mt-ticket-bus');
        $stops[] = array('name' => $start, 'percent' => 0);

        if (! empty($route->intermediate_stations)) {
            $decoded = json_decode($route->intermediate_stations, true);
            if (is_array($decoded)) {
                foreach ($decoded as $st) {
                    $name = isset($st['name']) ? $st['name'] : '';
                    if ($name === '') {
                        continue;
                    }
                    $pct = isset($st['price_percent']) ? max(0, min(100, round((float) $st['price_percent'], 2))) : 0;
                    $stops[] = array('name' => $name, 'percent' => $pct);
                }
            }
        }

        $end = ! empty($route->end_station) ? $route->end_station : __('End', 'mt-ticket-bus');
        $stops[] = array('name' => $end, 'percent' => 100);

        return $stops;
    }

    /**
     * Display ticket data in cart and checkout.
     *
     * @since 1.0.0
     *
     * @param array $item_data Cart item data to display.
     * @param array $cart_item Cart item.
     * @return array Modified item data.
     */
    public function display_ticket_cart_item_data($item_data, $cart_item)
    {
        if (! isset($cart_item['mt_ticket_date']) || ! isset($cart_item['mt_ticket_time']) || ! isset($cart_item['mt_ticket_seat'])) {
            return $item_data;
        }

        $date = $cart_item['mt_ticket_date'];
        $time = $cart_item['mt_ticket_time'];
        $seat = $cart_item['mt_ticket_seat'];

        // Segment (starting / final stop) - show only when segment discount applies (partial route with pricing).
        $segment_start_index = isset($cart_item['mt_ticket_segment_start_index']) ? (int) $cart_item['mt_ticket_segment_start_index'] : 0;
        $segment_end_index   = isset($cart_item['mt_ticket_segment_end_index']) ? (int) $cart_item['mt_ticket_segment_end_index'] : 0;
        $segment_start_name  = isset($cart_item['mt_ticket_segment_start_name']) ? trim((string) $cart_item['mt_ticket_segment_start_name']) : '';
        $segment_end_name    = isset($cart_item['mt_ticket_segment_end_name']) ? trim((string) $cart_item['mt_ticket_segment_end_name']) : '';
        $product_id          = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;

        $has_segment_discount = false;
        if ($segment_end_index > $segment_start_index && $product_id) {
            $route_id = get_post_meta($product_id, '_mt_bus_route_id', true);
            if ($route_id) {
                $route = MT_Ticket_Bus_Routes::get_instance()->get_route($route_id);
                if ($route) {
                    $stops = $this->get_route_stops_for_pricing($route);
                    $count_stops = count($stops);
                    if ($count_stops > 1 && $segment_start_index >= 0 && $segment_end_index < $count_stops) {
                        $start_pct = (float) $stops[$segment_start_index]['percent'];
                        $end_pct   = (float) $stops[$segment_end_index]['percent'];
                        if (($end_pct - $start_pct) < 100) {
                            $has_segment_discount = true;
                        }
                    }
                }
            }
        }

        if ($has_segment_discount && ($segment_start_name !== '' || $segment_end_name !== '')) {
            if ($segment_start_name !== '') {
                $item_data[] = array(
                    'name'  => __('Starting bus stop', 'mt-ticket-bus'),
                    'value' => '<span class="mt-cart-segment-value">' . esc_html($segment_start_name) . '</span>',
                );
            }
            if ($segment_end_name !== '') {
                $item_data[] = array(
                    'name'  => __('Final bus stop', 'mt-ticket-bus'),
                    'value' => '<span class="mt-cart-segment-value">' . esc_html($segment_end_name) . '</span>',
                );
            }
        }

        // Format date
        $date_obj = new DateTime($date);
        $date_formatted = $date_obj->format(get_option('date_format'));

        // Format time
        $time_formatted = date_i18n(get_option('time_format'), strtotime($time));

        $item_data[] = array(
            'name' => __('Date', 'mt-ticket-bus'),
            'value' => $date_formatted,
        );

        $item_data[] = array(
            'name' => __('Time', 'mt-ticket-bus'),
            'value' => $time_formatted,
        );

        $item_data[] = array(
            'name' => __('Seat', 'mt-ticket-bus'),
            'value' => $seat,
        );

        // Extras (if any).
        if (! empty($cart_item['mt_ticket_extras']) && is_array($cart_item['mt_ticket_extras'])) {
            $extras_manager = MT_Ticket_Bus_Extras::get_instance();
            $extras_labels  = array();

            foreach ($cart_item['mt_ticket_extras'] as $extra_id) {
                $extra = $extras_manager->get_extra($extra_id);
                if ($extra && $extra->status === 'active') {
                    // Use standard number_format for portability in tools/static analysis.
                    $price = number_format((float) $extra->price, 2, '.', '');
                    $extras_labels[] = sprintf(
                        /* translators: 1: Extra name, 2: Extra price */
                        __('%1$s (+%2$s)', 'mt-ticket-bus'),
                        $extra->name,
                        $price
                    );
                }
            }

            if (! empty($extras_labels)) {
                $item_data[] = array(
                    'name'  => __('Extras', 'mt-ticket-bus'),
                    'value' => implode(', ', $extras_labels),
                );
            }
        }

        return $item_data;
    }

    /**
     * Save ticket meta data to order items.
     *
     * @since 1.0.0
     *
     * @param WC_Order_Item $item         Order item.
     * @param string        $cart_item_key Cart item key.
     * @param array         $values       Cart item values.
     * @param WC_Order      $order        Order object.
     */
    public function save_ticket_order_item_meta($item, $cart_item_key, $values, $order)
    {
        if (! isset($values['mt_ticket_date']) || ! isset($values['mt_ticket_time']) || ! isset($values['mt_ticket_seat'])) {
            return;
        }

        // Get product data
        $product_id = $item->get_product_id();
        $schedule_id = get_post_meta($product_id, '_mt_bus_schedule_id', true);
        $bus_id = get_post_meta($product_id, '_mt_bus_id', true);
        $route_id = get_post_meta($product_id, '_mt_bus_route_id', true);

        // Save ticket meta to order item
        $item->add_meta_data('_mt_schedule_id', $schedule_id);
        $item->add_meta_data('_mt_bus_id', $bus_id);
        $item->add_meta_data('_mt_route_id', $route_id);
        $item->add_meta_data('_mt_seat_number', $values['mt_ticket_seat']);
        $item->add_meta_data('_mt_departure_date', $values['mt_ticket_date']);
        $item->add_meta_data('_mt_departure_time', $values['mt_ticket_time']);

        // Save extras (if any) as structured meta so we can show them later in emails/tickets.
        if (! empty($values['mt_ticket_extras']) && is_array($values['mt_ticket_extras'])) {
            $extras_manager = MT_Ticket_Bus_Extras::get_instance();
            $extras_payload = array();

            foreach ($values['mt_ticket_extras'] as $extra_id) {
                $extra = $extras_manager->get_extra($extra_id);
                if ($extra) {
                    $extras_payload[] = array(
                        'id'    => (int) $extra->id,
                        'name'  => $extra->name,
                        'price' => (float) $extra->price,
                    );
                }
            }

            if (! empty($extras_payload)) {
                $item->add_meta_data('_mt_ticket_extras', wp_json_encode($extras_payload));
            }
        }

        if (isset($values['mt_ticket_segment_start_name'])) {
            $item->add_meta_data('_mt_segment_start_name', sanitize_text_field($values['mt_ticket_segment_start_name']));
        }
        if (isset($values['mt_ticket_segment_end_name'])) {
            $item->add_meta_data('_mt_segment_end_name', sanitize_text_field($values['mt_ticket_segment_end_name']));
        }
    }

    /**
     * Show quantity as fixed "1" in cart for ticket products (no input).
     *
     * Each ticket line is one seat; quantity is not editable.
     *
     * @since 1.0.0
     *
     * @param string $product_quantity Default quantity HTML.
     * @param string $cart_item_key   Cart item key.
     * @param array  $cart_item       Cart item.
     * @return string Quantity HTML (e.g. "1" for ticket products).
     */
    public function cart_item_quantity_ticket_one($product_quantity, $cart_item_key, $cart_item)
    {
        if (! isset($cart_item['mt_ticket_product_id'])) {
            return $product_quantity;
        }
        return '<span class="mt-ticket-cart-qty">1</span>';
    }

    /**
     * Force quantity to 1 for ticket products when cart is loaded from session.
     *
     * @since 1.0.0
     *
     * @param WC_Cart $cart Cart object.
     * @return void
     */
    public function cart_normalize_ticket_quantity($cart)
    {
        if (! $cart || ! method_exists($cart, 'get_cart') || ! method_exists($cart, 'set_quantity')) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (! isset($cart_item['mt_ticket_product_id'])) {
                continue;
            }
            $qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;
            if ($qty !== 1 && method_exists($cart, 'set_quantity')) {
                call_user_func(array($cart, 'set_quantity'), $cart_item_key, 1);
            }
        }
    }

    /**
     * Apply price adjustments for selected extras in cart items.
     *
     * Each ticket cart line is one seat (quantity 1); we add the sum of
     * selected extras to the base product price.
     *
     * @since 1.0.13
     *
     * @param WC_Cart $cart Cart object.
     * @return void
     */
    public function apply_extras_price_adjustments($cart)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }

        if (! $cart || ! method_exists($cart, 'get_cart')) {
            return;
        }

        $extras_manager = MT_Ticket_Bus_Extras::get_instance();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (! isset($cart_item['mt_ticket_product_id'])) {
                continue;
            }

            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if (! $product || ! is_a($product, 'WC_Product')) {
                continue;
            }

            $product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
            $base_price = $product_id ? (float) get_post_meta($product_id, '_price', true) : 0.0;
            if ($base_price <= 0) {
                $base_price = (float) $product->get_price();
            }

            // Segment multiplier (0–1): when segment is selected, price = base * (end% - start%)
            $segment_mult = 1.0;
            $start_idx = isset($cart_item['mt_ticket_segment_start_index']) ? (int) $cart_item['mt_ticket_segment_start_index'] : 0;
            $end_idx   = isset($cart_item['mt_ticket_segment_end_index']) ? (int) $cart_item['mt_ticket_segment_end_index'] : 0;
            $route_id  = $product_id ? get_post_meta($product_id, '_mt_bus_route_id', true) : '';
            if ($route_id && $end_idx > $start_idx) {
                $route = MT_Ticket_Bus_Routes::get_instance()->get_route($route_id);
                if ($route) {
                    $stops = $this->get_route_stops_for_pricing($route);
                    $n     = count($stops);
                    if ($n > 0 && $end_idx < $n) {
                        $start_pct = (float) $stops[$start_idx]['percent'];
                        $end_pct   = (float) $stops[$end_idx]['percent'];
                        $segment_mult = max(0, min(1, ($end_pct - $start_pct) / 100));
                    }
                }
            }
            $base_price = $base_price * $segment_mult;

            // Extras on top of (possibly segmented) base
            $extras_total = 0.0;
            if (! empty($cart_item['mt_ticket_extras']) && is_array($cart_item['mt_ticket_extras'])) {
                $extras_ids_seen = array();
                foreach ($cart_item['mt_ticket_extras'] as $extra_id) {
                    if (in_array($extra_id, $extras_ids_seen, true)) {
                        continue;
                    }
                    $extras_ids_seen[] = $extra_id;
                    $extra             = $extras_manager->get_extra($extra_id);
                    if ($extra && $extra->status === 'active') {
                        $extras_total += (float) $extra->price;
                    }
                }
            }

            $new_price = $base_price + $extras_total;
            if ($new_price > 0 && method_exists($product, 'set_price')) {
                $product->set_price($new_price);
            }
        }
    }

    /**
     * Display ticket reservation info in order received page.
     *
     * @since 1.0.0
     *
     * @param int            $item_id Order item ID.
     * @param WC_Order_Item  $item    Order item object.
     * @param WC_Order       $order   Order object.
     */
    public function display_ticket_order_item_meta($item_id, $item, $order)
    {
        // Only show for ticket products
        $product_id = $item->get_product_id();
        $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
        if ($is_ticket_product !== 'yes') {
            return;
        }

        // Get ticket reservation data from order item meta
        $departure_date      = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
        $departure_time      = wc_get_order_item_meta($item_id, '_mt_departure_time', true);
        $seat_number         = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
        $extras_json         = wc_get_order_item_meta($item_id, '_mt_ticket_extras', true);
        $segment_start_name  = wc_get_order_item_meta($item_id, '_mt_segment_start_name', true);
        $segment_end_name    = wc_get_order_item_meta($item_id, '_mt_segment_end_name', true);

        // Only display if we have reservation data
        if (
            empty($departure_date)
            && empty($departure_time)
            && empty($seat_number)
            && empty($extras_json)
            && empty($segment_start_name)
            && empty($segment_end_name)
        ) {
            return;
        }

        // Format date for display
        $formatted_date = '';
        if (!empty($departure_date)) {
            $date_obj = strtotime($departure_date);
            if ($date_obj !== false) {
                $formatted_date = date_i18n(get_option('date_format'), $date_obj);
            }
        }

        // Format time for display
        $formatted_time = '';
        if (!empty($departure_time)) {
            $time_obj = strtotime($departure_time);
            if ($time_obj !== false) {
                $formatted_time = date_i18n(get_option('time_format'), $time_obj);
            }
        }

        // Display reservation info
        echo '<div class="mt-order-item-reservation-info" style="margin-top: 0.5em; font-size: 0.9em; color: #666;">';

        $has_output = false;

        // Segment start / end (important for pricing)
        if (! empty($segment_start_name)) {
            echo '<span class="mt-reservation-segment-start">';
            echo '<strong>' . esc_html__('Start Station:', 'mt-ticket-bus') . '</strong> ' . esc_html($segment_start_name);
            echo '</span>';
            $has_output = true;
        }

        if (! empty($segment_end_name)) {
            if ($has_output) {
                echo ' | ';
            }
            echo '<span class="mt-reservation-segment-end">';
            echo '<strong>' . esc_html__('End Station:', 'mt-ticket-bus') . '</strong> ' . esc_html($segment_end_name);
            echo '</span>';
            $has_output = true;
        }

        if (! empty($formatted_date)) {
            if ($has_output) {
                echo ' | ';
            }
            echo '<span class="mt-reservation-date">';
            echo '<strong>' . esc_html__('Date:', 'mt-ticket-bus') . '</strong> ' . esc_html($formatted_date);
            echo '</span>';
            $has_output = true;
        }

        if (! empty($formatted_time)) {
            if ($has_output) {
                echo ' | ';
            }
            echo '<span class="mt-reservation-time">';
            echo '<strong>' . esc_html__('Time:', 'mt-ticket-bus') . '</strong> ' . esc_html($formatted_time);
            echo '</span>';
            $has_output = true;
        }

        if (! empty($seat_number)) {
            if ($has_output) {
                echo ' | ';
            }
            echo '<span class="mt-reservation-seat">';
            echo '<strong>' . esc_html__('Seat:', 'mt-ticket-bus') . '</strong> ' . esc_html($seat_number);
            echo '</span>';
            $has_output = true;
        }

        // Extras text (if any).
        if (! empty($extras_json)) {
            $decoded_extras = json_decode($extras_json, true);
            if (is_array($decoded_extras) && ! empty($decoded_extras)) {
                $labels = array();
                foreach ($decoded_extras as $extra) {
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

                if (! empty($labels)) {
                    if ($has_output) {
                        echo ' | ';
                    }
                    echo '<span class="mt-reservation-extras">';
                    echo '<strong>' . esc_html__('Extras:', 'mt-ticket-bus') . '</strong> ' . esc_html(implode(', ', $labels));
                    echo '</span>';
                }
            }
        }

        echo '</div>';
    }

    /**
     * Display "Additional information" section on order-received/view-order for orders placed via classic checkout.
     * Uses same structure and classes as block checkout so existing CSS applies.
     *
     * @since 1.0.11
     *
     * @param WC_Order $order Order object.
     */
    public function display_order_received_additional_information($order)
    {
        if (! $order || ! is_a($order, 'WC_Order')) {
            return;
        }
        $show = $order->get_meta('_mt_passenger_show');
        if ($show !== 'yes' && $show !== '1') {
            return;
        }
        $first  = $order->get_meta('_mt_passenger_first_name');
        $last   = $order->get_meta('_mt_passenger_last_name');
        $email  = $order->get_meta('_mt_passenger_email');
        $phone  = $order->get_meta('_mt_passenger_phone');
        $labels = array(
            'show'  => __('Would you like to send the ticket to someone else?', 'mt-ticket-bus'),
            'first' => __('Passenger first name', 'mt-ticket-bus'),
            'last'  => __('Passenger last name', 'mt-ticket-bus'),
            'email' => __('Passenger email', 'mt-ticket-bus'),
            'phone' => __('Passenger phone', 'mt-ticket-bus'),
        );
        $show_value = ($show === 'yes' || $show === '1') ? __('Yes', 'mt-ticket-bus') : __('No', 'mt-ticket-bus');
        echo '<section class="wc-block-order-confirmation-additional-fields-wrapper mt-order-received-additional-fields">';
        echo '<h2>' . esc_html__('Additional information', 'mt-ticket-bus') . '</h2>';
        echo '<dl class="wc-block-components-additional-fields-list">';
        echo '<dt>' . esc_html($labels['show']) . '</dt><dd>' . esc_html($show_value) . '</dd>';
        echo '<dt>' . esc_html($labels['first']) . '</dt><dd>' . esc_html($first) . '</dd>';
        echo '<dt>' . esc_html($labels['last']) . '</dt><dd>' . esc_html($last) . '</dd>';
        echo '<dt>' . esc_html($labels['email']) . '</dt><dd>' . esc_html($email) . '</dd>';
        echo '<dt>' . esc_html($labels['phone']) . '</dt><dd>' . esc_html($phone) . '</dd>';
        echo '</dl></section>';
    }

    /**
     * Display ticket actions (Print and Download buttons) on order received page.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order Order object.
     */
    public function display_ticket_actions($order)
    {
        // Check if order has any ticket products
        $has_ticket_product = false;
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
            if ($is_ticket_product === 'yes') {
                $has_ticket_product = true;
                break;
            }
        }

        if (!$has_ticket_product) {
            return;
        }

        $order_id = $order->get_id();
        $order_key = $order->get_order_key();

        echo '<div class="mt-ticket-actions" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">';
        echo '<h3 style="margin-bottom: 1rem;">' . esc_html__('Ticket Actions', 'mt-ticket-bus') . '</h3>';

        // Download instruction text
        echo '<p style="margin-bottom: 1rem; color: #666; font-size: 0.95rem;">' . esc_html__('Download the ticket to your phone for identification on the bus.', 'mt-ticket-bus') . '</p>';

        // QR Code container
        // Note: We don't use nonce for QR code URLs as they can be opened from different devices/sessions
        // Order key is sufficient for security validation
        $download_url = add_query_arg(
            array(
                'mt_download_ticket' => 1,
                'order_id' => $order_id,
                'order_key' => $order_key,
            ),
            home_url()
        );
        echo '<div class="mt-qr-code-container" style="text-align: center; margin-bottom: 1.5rem;">';
        echo '<div id="mt-ticket-qr-code" data-download-url="' . esc_url($download_url) . '" style="display: inline-block; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 8px;"></div>';
        echo '</div>';

        echo '<div class="mt-ticket-actions-buttons" style="display: flex; gap: 1rem; flex-wrap: nowrap; align-items: center;">';

        // Print button
        echo '<button type="button" class="button mt-btn-print-ticket" data-order-id="' . esc_attr($order_id) . '" data-order-key="' . esc_attr($order_key) . '" style="padding: 0.75rem 1.5rem; background: transparent; color: #3b82f6; border: 2px solid #3b82f6; border-radius: 6px; cursor: pointer; font-size: 1rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><title>printer-outline</title><path d="M19 8C20.66 8 22 9.34 22 11V17H18V21H6V17H2V11C2 9.34 3.34 8 5 8H6V3H18V8H19M8 5V8H16V5H8M16 19V15H8V19H16M18 15H20V11C20 10.45 19.55 10 19 10H5C4.45 10 4 10.45 4 11V15H6V13H18V15M19 11.5C19 12.05 18.55 12.5 18 12.5C17.45 12.5 17 12.05 17 11.5C17 10.95 17.45 10.5 18 10.5C18.55 10.5 19 10.95 19 11.5Z" /></svg>';
        echo esc_html__('Print Ticket', 'mt-ticket-bus');
        echo '</button>';

        // Download button
        echo '<button type="button" class="button mt-btn-download-ticket" data-order-id="' . esc_attr($order_id) . '" data-order-key="' . esc_attr($order_key) . '" style="padding: 0.75rem 1.5rem; background: transparent; color: #059669; border: 2px solid #059669; border-radius: 6px; cursor: pointer; font-size: 1rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><title>download</title><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" /></svg>';
        echo esc_html__('Download Ticket', 'mt-ticket-bus');
        echo '</button>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * Enqueue scripts for order received page and view order page.
     *
     * Loads scripts for both the order received page (after checkout) and
     * the view order page in My Account section.
     *
     * @since 1.0.0
     */
    public function enqueue_order_received_scripts()
    {
        if (!function_exists('is_wc_endpoint_url')) {
            return;
        }

        // Check if we're on order-received or view-order page
        if (!is_wc_endpoint_url('order-received') && !is_wc_endpoint_url('view-order')) {
            return;
        }

        $settings = get_option('mt_ticket_bus_settings', array());
        if (! empty($settings['allow_buy_for_other']) && $settings['allow_buy_for_other'] === 'yes') {
            wp_enqueue_style(
                'mt-ticket-bus-checkout',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/css/checkout.css',
                array(),
                mt_ticket_bus_get_asset_version('assets/css/checkout.css')
            );
        }

        // Enqueue QR code library (qrcodejs)
        wp_enqueue_script(
            'qrcodejs',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/qrcode.min.js',
            array(),
            mt_ticket_bus_get_asset_version('assets/js/qrcode.min.js'),
            true
        );

        wp_enqueue_script(
            'mt-ticket-order-actions',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/order-actions.js',
            array('jquery', 'qrcodejs'),
            mt_ticket_bus_get_asset_version('assets/js/order-actions.js'),
            true
        );

        wp_localize_script(
            'mt-ticket-order-actions',
            'mtTicketOrderActions',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ticket_order_actions'),
                'i18n' => array(
                    'printTicket' => __('Print Ticket', 'mt-ticket-bus'),
                    'downloadTicket' => __('Download Ticket', 'mt-ticket-bus'),
                    'error' => __('An error occurred. Please try again.', 'mt-ticket-bus'),
                ),
            )
        );
    }

    /**
     * Enqueue checkout page styles and script (block checkout: paler labels; checkout.js for future use).
     *
     * @since 1.0.11
     */
    public function enqueue_checkout_styles()
    {
        if (! function_exists('is_checkout') || ! is_checkout()) {
            return;
        }
        $settings = get_option('mt_ticket_bus_settings', array());
        if (empty($settings['allow_buy_for_other']) || $settings['allow_buy_for_other'] !== 'yes') {
            return;
        }
        if (! $this->cart_contains_ticket_products()) {
            return;
        }
        wp_enqueue_style(
            'mt-ticket-bus-checkout',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/css/checkout.css',
            [],
            mt_ticket_bus_get_asset_version('assets/css/checkout.css')
        );
    }

    /**
     * Enqueue script and styles for block cart: hide quantity for ticket products (one seat = 1).
     *
     * @since 1.0.0
     */
    public function enqueue_cart_block_ticket_qty()
    {
        if (! function_exists('is_cart') || ! function_exists('is_checkout')) {
            return;
        }
        if (! call_user_func('is_cart') && ! call_user_func('is_checkout')) {
            return;
        }
        $ids = self::get_ticket_product_ids();
        if (empty($ids)) {
            return;
        }
        wp_enqueue_script(
            'mt-ticket-cart-block-qty',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/cart-block-ticket-qty.js',
            array(),
            mt_ticket_bus_get_asset_version('assets/js/cart-block-ticket-qty.js'),
            true
        );
        wp_localize_script('mt-ticket-cart-block-qty', 'mtTicketCartBlock', array(
            'ticketProductIds' => array_map('intval', $ids),
        ));
        wp_register_style('mt-ticket-cart-block-qty', false, array(), MT_TICKET_BUS_VERSION);
        wp_enqueue_style('mt-ticket-cart-block-qty');
        if (function_exists('wp_add_inline_style')) {
            call_user_func('wp_add_inline_style', 'mt-ticket-cart-block-qty', $this->get_cart_block_ticket_qty_css());
        }
    }

    /**
     * CSS to hide quantity selector for ticket items in block cart/checkout.
     *
     * @since 1.0.0
     * @return string
     */
    private function get_cart_block_ticket_qty_css()
    {
        return '
/* Block cart/checkout: hide quantity selector for ticket products (one seat = 1) */
.mt-ticket-cart-item .wc-block-components-quantity-selector {
    display: none !important;
}
';
    }

    /**
     * Add body class on order-received/view-order to hide "Additional information" when passenger_show is not checked.
     *
     * @since 1.0.11
     *
     * @param array $classes Body classes.
     * @return array
     */
    public function order_received_body_class($classes)
    {
        if (! function_exists('is_wc_endpoint_url') || ! (is_wc_endpoint_url('order-received') || is_wc_endpoint_url('view-order'))) {
            return $classes;
        }
        $order_id = 0;
        if (is_wc_endpoint_url('order-received')) {
            $order_id = absint(get_query_var('order-received', 0));
        } elseif (is_wc_endpoint_url('view-order')) {
            $order_id = absint(get_query_var('view-order', 0));
        }
        if (! $order_id) {
            return $classes;
        }
        $order = wc_get_order($order_id);
        if (! $order || ! is_a($order, 'WC_Order')) {
            return $classes;
        }
        $show = $order->get_meta('_mt_passenger_show');
        if ($show === '' || $show === 'no') {
            $show = $order->get_meta('_wc_other/mt_ticket_bus/passenger_show');
            if ($show === '1' || $show === 1 || $show === 'yes') {
                return $classes;
            }
        } elseif ($show === 'yes' || $show === '1') {
            return $classes;
        }
        $classes[] = 'mt-hide-order-additional-information';
        return $classes;
    }

    /**
     * AJAX handler for printing ticket.
     *
     * @since 1.0.0
     */
    public function ajax_print_ticket()
    {
        check_ajax_referer('mt_ticket_order_actions', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order_key = isset($_POST['order_key']) ? sanitize_text_field(wp_unslash($_POST['order_key'])) : '';

        if (!$order_id || !$order_key) {
            wp_send_json_error(array('message' => __('Invalid request.', 'mt-ticket-bus')));
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_send_json_error(array('message' => __('Order not found.', 'mt-ticket-bus')));
        }

        // Check if user has permission to view this order
        if (!current_user_can('view_order', $order_id) && $order->get_customer_id() !== get_current_user_id()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        // Generate print URL
        $print_url = add_query_arg(
            array(
                'mt_print_ticket' => 1,
                'order_id' => $order_id,
                'order_key' => $order_key,
                'nonce' => wp_create_nonce('mt_print_ticket_' . $order_id),
            ),
            home_url()
        );

        wp_send_json_success(array('print_url' => $print_url));
    }

    /**
     * AJAX handler for downloading ticket as PDF.
     *
     * @since 1.0.0
     */
    public function ajax_download_ticket()
    {
        check_ajax_referer('mt_ticket_order_actions', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order_key = isset($_POST['order_key']) ? sanitize_text_field(wp_unslash($_POST['order_key'])) : '';

        if (!$order_id || !$order_key) {
            wp_send_json_error(array('message' => __('Invalid request.', 'mt-ticket-bus')));
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_send_json_error(array('message' => __('Order not found.', 'mt-ticket-bus')));
        }

        // Check if user has permission to view this order
        if (!current_user_can('view_order', $order_id) && $order->get_customer_id() !== get_current_user_id()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $this->output_ticket_pdf_to_browser($order);
    }

    /**
     * Handle download ticket request via URL.
     *
     * @since 1.0.0
     */
    public function handle_download_ticket_request()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public download URL; validated by order_key (secret per order), no nonce in link.
        if (!isset($_GET['mt_download_ticket']) || !isset($_GET['order_id']) || !isset($_GET['order_key'])) {
            return;
        }

        $order_id = absint($_GET['order_id']);
        $order_key = sanitize_text_field(wp_unslash($_GET['order_key'] ?? ''));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (!$order_id || !$order_key) {
            wp_die(esc_html__('Invalid request.', 'mt-ticket-bus'));
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die(esc_html__('Order not found.', 'mt-ticket-bus'));
        }

        // For QR code downloads, we validate only by order key (no session required)
        // This allows the link to work from any device at any time
        // The order key is cryptographically secure and unique per order

        $this->output_ticket_pdf_to_browser($order);
    }

    /**
     * Output ticket as PDF in the browser (inline) for view/download/print.
     *
     * If Dompdf is available, generates PDF and sends it with Content-Disposition: inline.
     * Otherwise redirects to the print HTML page with mt_pdf=1.
     *
     * @since 1.0.5
     *
     * @param WC_Order $order Order object.
     */
    private function output_ticket_pdf_to_browser($order)
    {
        $dompdf_class = '\Dompdf\Dompdf';
        if (!class_exists($dompdf_class)) {
            $this->redirect_to_ticket_print_page($order);
            return;
        }
        $html = $this->get_ticket_print_html($order, true);
        if ($html === '') {
            wp_die(esc_html__('No ticket data.', 'mt-ticket-bus'));
        }
        try {
            /** @var \Dompdf\Dompdf $dompdf */
            $dompdf = new $dompdf_class(array('isRemoteEnabled' => true));
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf_output = $dompdf->output();
            if ($pdf_output === '') {
                $this->redirect_to_ticket_print_page($order);
                return;
            }
            $filename = 'ticket-' . $order->get_id() . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary PDF output, escaping would corrupt the file.
            echo $pdf_output;
            exit;
        } catch (\Exception $e) {
            $this->redirect_to_ticket_print_page($order);
        }
    }

    /**
     * Redirect to the ticket print HTML page (fallback when PDF cannot be generated).
     *
     * @since 1.0.5
     *
     * @param WC_Order $order Order object.
     */
    private function redirect_to_ticket_print_page($order)
    {
        $pdf_url = add_query_arg(
            array(
                'mt_print_ticket' => 1,
                'mt_pdf' => 1,
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key(),
            ),
            home_url()
        );
        wp_safe_redirect($pdf_url);
        exit;
    }

    /**
     * Hide specific order item meta fields in admin.
     *
     * @since 1.0.0
     *
     * @param array $hidden_meta Array of hidden meta keys.
     * @return array Modified array of hidden meta keys.
     */
    public function hide_order_item_meta($hidden_meta)
    {
        $hidden_meta[] = '_mt_schedule_id';
        return $hidden_meta;
    }

    /**
     * Format order item meta key (label) for display in admin.
     *
     * @since 1.0.0
     *
     * @param string         $display_key Display key.
     * @param object         $meta        Meta object.
     * @param WC_Order_Item  $item        Order item object.
     * @return string Formatted display key.
     */
    public function format_order_item_meta_key($display_key, $meta, $item)
    {
        // Only process our ticket meta fields
        if (strpos($meta->key, '_mt_') !== 0) {
            return $display_key;
        }

        // Check if this is a ticket product
        $product_id = $item->get_product_id();
        $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
        if ($is_ticket_product !== 'yes') {
            return $display_key;
        }

        // Map meta keys to user-friendly labels
        $labels = array(
            '_mt_bus_id' => __('Bus', 'mt-ticket-bus'),
            '_mt_route_id' => __('Route', 'mt-ticket-bus'),
            '_mt_segment_start_name' => __('Start Station', 'mt-ticket-bus'),
            '_mt_segment_end_name'   => __('End Station', 'mt-ticket-bus'),
            '_mt_seat_number' => __('Seat', 'mt-ticket-bus'),
            '_mt_departure_date' => __('Date', 'mt-ticket-bus'),
            '_mt_departure_time' => __('Time', 'mt-ticket-bus'),
            '_mt_ticket_extras' => __('Extras', 'mt-ticket-bus'),
        );

        if (isset($labels[$meta->key])) {
            return $labels[$meta->key];
        }

        return $display_key;
    }

    /**
     * Format order item meta value for display in admin.
     *
     * @since 1.0.0
     *
     * @param string         $display_value Display value.
     * @param object         $meta          Meta object.
     * @param WC_Order_Item  $item          Order item object.
     * @return string Formatted display value.
     */
    public function format_order_item_meta_value($display_value, $meta, $item)
    {
        // Only process our ticket meta fields
        if (strpos($meta->key, '_mt_') !== 0) {
            return $display_value;
        }

        // Check if this is a ticket product
        $product_id = $item->get_product_id();
        $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
        if ($is_ticket_product !== 'yes') {
            return $display_value;
        }

        // Format based on meta key
        switch ($meta->key) {
            case '_mt_bus_id':
                $bus_id = intval($meta->value);
                if ($bus_id > 0) {
                    $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
                    if ($bus) {
                        $bus_name = !empty($bus->name) ? esc_html($bus->name) : '';
                        $bus_reg = !empty($bus->registration_number) ? esc_html($bus->registration_number) : '';
                        if ($bus_name && $bus_reg) {
                            return $bus_name . ' (' . $bus_reg . ')';
                        } elseif ($bus_name) {
                            return $bus_name;
                        } elseif ($bus_reg) {
                            return $bus_reg;
                        }
                    }
                }
                return $display_value;

            case '_mt_route_id':
                $route_id = intval($meta->value);
                if ($route_id > 0) {
                    $route = MT_Ticket_Bus_Routes::get_instance()->get_route($route_id);
                    if ($route) {
                        $route_parts = array();

                        // Add start station
                        if (!empty($route->start_station)) {
                            $route_parts[] = esc_html($route->start_station);
                        }

                        // Add intermediate stations
                        if (!empty($route->intermediate_stations)) {
                            $decoded = json_decode($route->intermediate_stations, true);
                            if (is_array($decoded) && !empty($decoded)) {
                                // New JSON format with name and duration
                                foreach ($decoded as $station) {
                                    $station_name = is_array($station) && isset($station['name']) ? $station['name'] : (is_string($station) ? $station : '');
                                    if (!empty($station_name)) {
                                        $route_parts[] = esc_html($station_name);
                                    }
                                }
                            } else {
                                // Legacy format: line-separated text
                                $intermediate = array_filter(array_map('trim', explode("\n", $route->intermediate_stations)));
                                foreach ($intermediate as $station_name) {
                                    if (!empty($station_name)) {
                                        $route_parts[] = esc_html($station_name);
                                    }
                                }
                            }
                        }

                        // Add end station
                        if (!empty($route->end_station)) {
                            $route_parts[] = esc_html($route->end_station);
                        }

                        if (!empty($route_parts)) {
                            return implode(', ', $route_parts);
                        }
                    }
                }
                return $display_value;

            case '_mt_segment_start_name':
                $start_name = trim((string) $meta->value);
                if ($start_name !== '') {
                    return esc_html($start_name);
                }
                return $display_value;

            case '_mt_segment_end_name':
                $end_name = trim((string) $meta->value);
                if ($end_name !== '') {
                    return esc_html($end_name);
                }
                return $display_value;

            case '_mt_departure_date':
                if (!empty($meta->value)) {
                    $date_obj = strtotime($meta->value);
                    if ($date_obj !== false) {
                        return date_i18n(get_option('date_format'), $date_obj);
                    }
                }
                return $display_value;

            case '_mt_departure_time':
                if (!empty($meta->value)) {
                    $time_obj = strtotime($meta->value);
                    if ($time_obj !== false) {
                        return date_i18n(get_option('time_format'), $time_obj);
                    }
                }
                return $display_value;

            case '_mt_ticket_extras':
                if (empty($meta->value)) {
                    return $display_value;
                }
                $decoded = json_decode($meta->value, true);
                if (! is_array($decoded) || empty($decoded)) {
                    return $display_value;
                }
                $parts = array();
                foreach ($decoded as $extra) {
                    if (! is_array($extra)) {
                        continue;
                    }
                    $name = isset($extra['name']) ? $extra['name'] : '';
                    $price = isset($extra['price']) ? (float) $extra['price'] : 0.0;
                    if ($name !== '') {
                        $parts[] = sprintf(
                            /* translators: 1: Extra name, 2: Extra price */
                            '%1$s (+%2$s)',
                            esc_html($name),
                            number_format($price, 2, '.', '')
                        );
                    }
                }
                return ! empty($parts) ? implode(', ', $parts) : $display_value;

            default:
                return $display_value;
        }
    }

    /**
     * Add product meta fields for bus tickets.
     *
     * @since 1.0.0
     */
    public function add_product_meta_fields()
    {
        global $post;

        $route_id = get_post_meta($post->ID, '_mt_bus_route_id', true);
        $bus_id = get_post_meta($post->ID, '_mt_bus_id', true);
        $schedule_id = get_post_meta($post->ID, '_mt_bus_schedule_id', true);
        $extras_ids  = get_post_meta($post->ID, '_mt_ticket_extras_ids', true);
        if (! is_array($extras_ids)) {
            $extras_ids = array();
        }

        echo '<div class="options_group mt-bus-ticket-options">';

        // Checkbox to mark product as bus ticket
        // Get the meta value - will be empty string, false, or 'yes'/'no'
        $is_ticket_product = get_post_meta($post->ID, '_mt_is_ticket_product', true);
        // Normalize: only 'yes' means checked, everything else (empty, false, 'no') means unchecked
        $is_checked = ($is_ticket_product === 'yes');

        // woocommerce_wp_checkbox checks if value equals cbvalue (default 'yes') to determine if checked
        // The checkbox is checked only if value === cbvalue
        // We must explicitly set value to 'yes' when checked, and NOT set it (or set to something else) when unchecked
        $checkbox_args = array(
            'id'          => '_mt_is_ticket_product',
            'label'       => __('Is Bus Ticket Product', 'mt-ticket-bus'),
            'description' => __('Check this box if this is a bus ticket product.', 'mt-ticket-bus'),
            'cbvalue'     => 'yes',
        );
        // Only add 'value' parameter when checkbox should be checked
        // If we don't add 'value' or set it to something other than 'yes', checkbox will be unchecked
        if ($is_checked) {
            $checkbox_args['value'] = 'yes';
        }
        woocommerce_wp_checkbox($checkbox_args);

        // Route selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_route_id',
            'label'       => __('Bus Route', 'mt-ticket-bus'),
            'description' => __('Select the bus route for this ticket product.', 'mt-ticket-bus'),
            'options'     => $this->get_routes_options(),
            'value'       => $route_id,
            'custom_attributes' => array(
                'disabled' => $is_ticket_product ? '' : 'disabled',
            ),
        ));

        // Bus selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_id',
            'label'       => __('Bus', 'mt-ticket-bus'),
            'description' => __('Select the bus for this ticket product.', 'mt-ticket-bus'),
            'options'     => $this->get_buses_options(),
            'value'       => $bus_id,
            'custom_attributes' => array(
                'disabled' => $is_ticket_product ? '' : 'disabled',
            ),
        ));

        // Schedule selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_schedule_id',
            'label'       => __('Schedule', 'mt-ticket-bus'),
            'description' => __('Select the schedule for this ticket product. Schedules will be filtered based on the selected route.', 'mt-ticket-bus'),
            'options'     => $this->get_schedules_options($route_id),
            'value'       => $schedule_id,
            'custom_attributes' => array(
                'disabled' => $is_ticket_product ? '' : 'disabled',
            ),
        ));

        // Extras selection (multi-select) – only when module allows paid extras (show_pay_extras)
        $plugin_settings   = get_option('mt_ticket_bus_settings', array());
        $show_pay_extras   = isset($plugin_settings['show_pay_extras']) ? $plugin_settings['show_pay_extras'] : 'yes';
        $extras_manager    = MT_Ticket_Bus_Extras::get_instance();
        $extras_options    = $extras_manager->get_extras_options();
        $extras_allowed    = ($show_pay_extras === 'yes' && ! empty($extras_options));

        if ($extras_allowed) {
            echo '<p class="form-field _mt_ticket_extras_ids_field">';
            echo '<label for="_mt_ticket_extras_ids">' . esc_html__('Extras', 'mt-ticket-bus') . '</label>';
            echo '<select id="_mt_ticket_extras_ids" name="_mt_ticket_extras_ids[]" multiple="multiple" class="wc-enhanced-select" style="width: 100%;" ' . ($is_ticket_product ? '' : 'disabled="disabled"') . '>';

            foreach ($extras_options as $extra_id => $label) {
                printf(
                    '<option value="%1$d"%2$s>%3$s</option>',
                    (int) $extra_id,
                    in_array((int) $extra_id, $extras_ids, true) ? ' selected="selected"' : '',
                    esc_html($label)
                );
            }

            echo '</select>';
            echo '<span class="description">' . esc_html__('Select one or more extras that can be attached to this ticket product.', 'mt-ticket-bus') . '</span>';
            echo '</p>';
            echo '<style type="text/css">._mt_ticket_extras_ids_field .select2-container { width: 100% !important; }</style>';
        }

        echo '</div>';

        // Add JavaScript for dynamic schedule loading
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var ticketCheckbox = $('#_mt_is_ticket_product');
                var routeSelect = $('#_mt_bus_route_id');
                var busSelect = $('#_mt_bus_id');
                var scheduleSelect = $('#_mt_bus_schedule_id');
                var extrasSelect = $('#_mt_ticket_extras_ids');
                var savedScheduleId = scheduleSelect.val(); // Save the current schedule ID

                // Function to toggle fields based on checkbox state
                function toggleTicketFields() {
                    var isChecked = ticketCheckbox.is(':checked');
                    routeSelect.prop('disabled', !isChecked);
                    busSelect.prop('disabled', !isChecked);
                    scheduleSelect.prop('disabled', !isChecked);
                    extrasSelect.prop('disabled', !isChecked);

                    // Clear values if checkbox is unchecked
                    if (!isChecked) {
                        routeSelect.val('').trigger('change');
                        busSelect.val('');
                        scheduleSelect.val('');
                        extrasSelect.val(null).trigger('change');
                    }
                }

                // Toggle fields when checkbox changes
                ticketCheckbox.on('change', function() {
                    toggleTicketFields();
                });

                // Initialize on page load
                toggleTicketFields();

                // Function to load schedules by route
                function loadSchedulesByRoute(routeId, preserveScheduleId) {
                    if (!routeId || routeId === '') {
                        scheduleSelect.html('<option value=""><?php echo esc_js(__('Select a schedule...', 'mt-ticket-bus')); ?></option>');
                        return;
                    }

                    scheduleSelect.prop('disabled', true);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mt_get_schedules_by_route',
                            route_id: routeId,
                            nonce: '<?php echo esc_js(wp_create_nonce('mt_ticket_bus_admin')); ?>'
                        },
                        success: function(response) {
                            var isTicketChecked = ticketCheckbox.is(':checked');
                            scheduleSelect.prop('disabled', !isTicketChecked);

                            if (response.success && response.data && response.data.schedules) {
                                var options = '<option value=""><?php echo esc_js(__('Select a schedule...', 'mt-ticket-bus')); ?></option>';

                                $.each(response.data.schedules, function(id, label) {
                                    options += '<option value="' + id + '">' + label + '</option>';
                                });

                                scheduleSelect.html(options);

                                // Restore saved schedule ID if it exists in the new options
                                if (preserveScheduleId && savedScheduleId) {
                                    if (scheduleSelect.find('option[value="' + savedScheduleId + '"]').length > 0) {
                                        scheduleSelect.val(savedScheduleId);
                                    }
                                }
                            } else {
                                scheduleSelect.html('<option value=""><?php echo esc_js(__('No schedules found for this route.', 'mt-ticket-bus')); ?></option>');
                            }
                        },
                        error: function() {
                            var isTicketChecked = ticketCheckbox.is(':checked');
                            scheduleSelect.prop('disabled', !isTicketChecked);
                            scheduleSelect.html('<option value=""><?php echo esc_js(__('Error loading schedules.', 'mt-ticket-bus')); ?></option>');
                        }
                    });
                }

                // Load schedules when route changes
                routeSelect.on('change', function() {
                    var routeId = $(this).val();
                    savedScheduleId = ''; // Clear saved schedule when route changes
                    loadSchedulesByRoute(routeId, false);
                });

                // Load schedules on page load if route is already selected
                if (routeSelect.val() && ticketCheckbox.is(':checked')) {
                    loadSchedulesByRoute(routeSelect.val(), true);
                }
            });
        </script>
        <?php
    }

    /**
     * Save product meta fields.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     */
    public function save_product_meta_fields($post_id)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- save_post/save_product callback; WordPress and WooCommerce verify nonce before firing.
        // Save ticket product flag
        $is_ticket_product = isset($_POST['_mt_is_ticket_product']) ? 'yes' : 'no';
        update_post_meta($post_id, '_mt_is_ticket_product', $is_ticket_product);

        // Only save ticket-related fields if product is marked as ticket
        if ($is_ticket_product === 'yes') {
            if (isset($_POST['_mt_bus_route_id'])) {
                update_post_meta($post_id, '_mt_bus_route_id', sanitize_text_field(wp_unslash((string) $_POST['_mt_bus_route_id'])));
            }

            if (isset($_POST['_mt_bus_id'])) {
                update_post_meta($post_id, '_mt_bus_id', sanitize_text_field(wp_unslash((string) $_POST['_mt_bus_id'])));
            }

            if (isset($_POST['_mt_bus_schedule_id'])) {
                update_post_meta($post_id, '_mt_bus_schedule_id', sanitize_text_field(wp_unslash((string) $_POST['_mt_bus_schedule_id'])));
            }

            // Extras (array of IDs).
            if (isset($_POST['_mt_ticket_extras_ids']) && is_array($_POST['_mt_ticket_extras_ids'])) {
                $extras_ids = array_values(array_unique(array_filter(array_map('absint', wp_unslash($_POST['_mt_ticket_extras_ids'])))));
                update_post_meta($post_id, '_mt_ticket_extras_ids', $extras_ids);
            } else {
                delete_post_meta($post_id, '_mt_ticket_extras_ids');
            }
        } else {
            // Clear ticket-related fields if product is not a ticket
            delete_post_meta($post_id, '_mt_bus_route_id');
            delete_post_meta($post_id, '_mt_bus_id');
            delete_post_meta($post_id, '_mt_bus_schedule_id');
            delete_post_meta($post_id, '_mt_ticket_extras_ids');
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Set virtual product by default for ticket products.
     *
     * @since 1.0.0
     */
    public function set_virtual_default()
    {
        global $post;

        if (! $post->ID) {
            return;
        }

        $is_virtual = get_post_meta($post->ID, '_virtual', true);

        if (empty($is_virtual)) {
        ?>
            <script>
                jQuery(document).ready(function($) {
                    $('#_virtual').prop('checked', true).trigger('change');
                });
            </script>
        <?php
        }
    }

    /**
     * Get routes options for select field.
     *
     * @since 1.0.0
     *
     * @return array Array of route options (id => label).
     */
    private function get_routes_options()
    {
        $routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes();
        $options = array('' => __('Select a route...', 'mt-ticket-bus'));

        foreach ($routes as $route) {
            $options[$route->id] = $route->name . ' (' . $route->start_station . ' - ' . $route->end_station . ')';
        }

        return $options;
    }

    /**
     * Get buses options for select field.
     *
     * @since 1.0.0
     *
     * @return array Array of bus options (id => label).
     */
    private function get_buses_options()
    {
        $buses = MT_Ticket_Bus_Buses::get_instance()->get_all_buses();
        $options = array('' => __('Select a bus...', 'mt-ticket-bus'));

        foreach ($buses as $bus) {
            $options[$bus->id] = $bus->name . ' (' . $bus->registration_number . ')';
        }

        return $options;
    }

    /**
     * Get schedules options for select field.
     *
     * @since 1.0.0
     *
     * @param int $route_id Optional route ID to filter schedules.
     * @return array Array of schedule options (id => label).
     */
    private function get_schedules_options($route_id = 0)
    {
        $args = array('status' => 'all');
        if (!empty($route_id)) {
            $args['route_id'] = absint($route_id);
        }

        $schedules = MT_Ticket_Bus_Schedules::get_instance()->get_all_schedules($args);
        $options = array('' => __('Select a schedule...', 'mt-ticket-bus'));

        foreach ($schedules as $schedule) {
            $label_parts = array();

            // Add name if available
            if (!empty($schedule->name)) {
                $label_parts[] = $schedule->name;
            }

            // Parse and add courses
            $courses_display = '';
            if (!empty($schedule->courses)) {
                $courses = json_decode($schedule->courses, true);
                if (is_array($courses) && !empty($courses)) {
                    $course_times = array();
                    foreach ($courses as $course) {
                        if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                            $course_times[] = $course['departure_time'] . ' - ' . $course['arrival_time'];
                        }
                    }
                    if (!empty($course_times)) {
                        $courses_display = implode(', ', $course_times);
                    }
                }
            }

            if ($courses_display) {
                $label_parts[] = $courses_display;
            }

            // Add days of week
            $days_info = '';
            if (!empty($schedule->days_of_week)) {
                $parsed_days = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($schedule->days_of_week);
                if ($parsed_days === 'all') {
                    $days_info = ' (' . __('Every day', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekdays') {
                    $days_info = ' (' . __('Weekdays', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekend') {
                    $days_info = ' (' . __('Weekend', 'mt-ticket-bus') . ')';
                } elseif (is_array($parsed_days)) {
                    $days_info = ' (' . implode(', ', array_map('ucfirst', $parsed_days)) . ')';
                }
            }

            $label = implode(' - ', $label_parts) . $days_info;
            $options[$schedule->id] = $label;
        }

        return $options;
    }

    /**
     * AJAX handler for getting schedules by route.
     *
     * @since 1.0.0
     */
    public function ajax_get_schedules_by_route()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        $route_id = isset($_POST['route_id']) ? absint($_POST['route_id']) : 0;

        if (empty($route_id)) {
            wp_send_json_error(array('message' => __('Route ID is required.', 'mt-ticket-bus')));
        }

        $schedules = MT_Ticket_Bus_Schedules::get_instance()->get_schedules_by_route($route_id, array('status' => 'all'));
        $options = array();

        foreach ($schedules as $schedule) {
            $label_parts = array();

            // Add name if available
            if (!empty($schedule->name)) {
                $label_parts[] = $schedule->name;
            }

            // Parse and add courses
            $courses_display = '';
            if (!empty($schedule->courses)) {
                $courses = json_decode($schedule->courses, true);
                if (is_array($courses) && !empty($courses)) {
                    $course_times = array();
                    foreach ($courses as $course) {
                        if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                            $course_times[] = $course['departure_time'] . ' - ' . $course['arrival_time'];
                        }
                    }
                    if (!empty($course_times)) {
                        $courses_display = implode(', ', $course_times);
                    }
                }
            }

            if ($courses_display) {
                $label_parts[] = $courses_display;
            }

            // Add days of week
            $days_info = '';
            if (!empty($schedule->days_of_week)) {
                $parsed_days = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($schedule->days_of_week);
                if ($parsed_days === 'all') {
                    $days_info = ' (' . __('Every day', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekdays') {
                    $days_info = ' (' . __('Weekdays', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekend') {
                    $days_info = ' (' . __('Weekend', 'mt-ticket-bus') . ')';
                } elseif (is_array($parsed_days)) {
                    $days_info = ' (' . implode(', ', array_map('ucfirst', $parsed_days)) . ')';
                }
            }

            $label = implode(' - ', $label_parts) . $days_info;
            $options[$schedule->id] = $label;
        }

        wp_send_json_success(array('schedules' => $options));
    }

    /**
     * AJAX handler for getting courses by schedule.
     *
     * @since 1.0.0
     */
    public function ajax_get_courses_by_schedule()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        $schedule_id = isset($_POST['schedule_id']) ? absint($_POST['schedule_id']) : 0;

        if (empty($schedule_id)) {
            wp_send_json_error(array('message' => __('Schedule ID is required.', 'mt-ticket-bus')));
        }

        $schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($schedule_id);
        if (!$schedule) {
            wp_send_json_error(array('message' => __('Schedule not found.', 'mt-ticket-bus')));
        }

        $courses = array();
        if (!empty($schedule->courses)) {
            $decoded = json_decode($schedule->courses, true);
            if (is_array($decoded)) {
                $courses = $decoded;
            }
        }

        $options = array();
        foreach ($courses as $course) {
            if (isset($course['departure_time'])) {
                $departure_time = $course['departure_time'];
                $arrival_time = isset($course['arrival_time']) ? $course['arrival_time'] : '';
                $time_display = $departure_time . ($arrival_time ? ' → ' . $arrival_time : '');
                $time_value = gmdate('H:i', strtotime($departure_time));
                $options[] = array(
                    'value' => $time_value,
                    'label' => $time_display
                );
            }
        }

        wp_send_json_success(array('courses' => $options));
    }

    /**
     * AJAX handler for admin: get available dates for a schedule (same logic as frontend – by days_of_week).
     *
     * @since 1.0.0
     */
    public function ajax_get_available_dates_admin()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }
        $schedule_id = isset($_POST['schedule_id']) ? absint($_POST['schedule_id']) : 0;
        $bus_id = isset($_POST['bus_id']) ? absint($_POST['bus_id']) : 0;
        $month = isset($_POST['month']) ? absint($_POST['month']) : (int) gmdate('n');
        $year = isset($_POST['year']) ? absint($_POST['year']) : (int) gmdate('Y');

        if (! $schedule_id || ! $bus_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }

        $schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($schedule_id);
        if (! $schedule) {
            wp_send_json_error(array('message' => __('Schedule not found.', 'mt-ticket-bus')));
        }

        $available_dates = MT_Ticket_Bus_Renderer::get_available_dates($schedule, $month, $year);

        $dates_with_availability = array();
        foreach ($available_dates as $date_info) {
            $has_availability = MT_Ticket_Bus_Renderer::check_date_availability(
                $schedule_id,
                $bus_id,
                $date_info['date']
            );
            $dates_with_availability[] = array_merge($date_info, array(
                'available' => $has_availability['available'],
            ));
        }

        wp_send_json_success(array(
            'dates' => $dates_with_availability,
            'month' => $month,
            'year' => $year,
        ));
    }

    /**
     * AJAX handler for admin: get available seats (same as frontend but with admin nonce).
     *
     * @since 1.0.0
     */
    public function ajax_get_available_seats_admin()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }
        $schedule_id = isset($_POST['schedule_id']) ? absint($_POST['schedule_id']) : 0;
        $bus_id = isset($_POST['bus_id']) ? absint($_POST['bus_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
        $departure_time = isset($_POST['departure_time']) ? sanitize_text_field(wp_unslash($_POST['departure_time'])) : '';
        if (! $schedule_id || ! $bus_id || ! $date || ! $departure_time) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'mt-ticket-bus')));
        }
        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
        if (! $bus || empty($bus->seat_layout)) {
            wp_send_json_error(array('message' => __('Bus seat layout not found.', 'mt-ticket-bus')));
        }
        $layout_data = json_decode($bus->seat_layout, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! isset($layout_data['seats'])) {
            wp_send_json_error(array('message' => __('Invalid seat layout.', 'mt-ticket-bus')));
        }
        $available_seats = MT_Ticket_Bus_Reservations::get_instance()->get_available_seats(
            $schedule_id,
            $date,
            $departure_time,
            $bus_id
        );
        wp_send_json_success(array(
            'seat_layout' => $layout_data,
            'available_seats' => $available_seats,
            'total_seats' => (int) $bus->total_seats,
            'reserved_count' => (int) $bus->total_seats - count($available_seats),
        ));
    }

    /**
     * Enqueue admin scripts for WooCommerce product edit page.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on product edit page
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        global $post;
        if (!$post || $post->post_type !== 'product') {
            return;
        }

        // Make sure ajaxurl is available
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
    }

    /**
     * Handle print ticket request.
     *
     * @since 1.0.0
     */
    public function handle_print_ticket_request()
    {
        if (!isset($_GET['mt_print_ticket']) || !isset($_GET['order_id']) || !isset($_GET['order_key'])) {
            return;
        }

        $order_id = absint($_GET['order_id']);
        $order_key = sanitize_text_field(wp_unslash($_GET['order_key']));
        $is_pdf_download = isset($_GET['mt_pdf']) && $_GET['mt_pdf'] == 1;
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';

        if (!$order_id || !$order_key) {
            wp_die(esc_html__('Invalid request.', 'mt-ticket-bus'));
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die(esc_html__('Order not found.', 'mt-ticket-bus'));
        }

        // For PDF downloads from QR code, skip nonce and permission checks
        // Order key validation is sufficient for security
        if (!$is_pdf_download) {
            // For regular print requests (from buttons), verify nonce
            if (!$nonce || !wp_verify_nonce($nonce, 'mt_print_ticket_' . $order_id)) {
                wp_die(esc_html__('Invalid request.', 'mt-ticket-bus'));
            }

            // Check if user has permission to view this order
            if (!current_user_can('view_order', $order_id) && $order->get_customer_id() !== get_current_user_id()) {
                wp_die(esc_html__('Permission denied.', 'mt-ticket-bus'));
            }
        }

        // Render print template
        $this->render_ticket_print_template($order);
        exit;
    }

    /**
     * Render ticket print template.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order Order object.
     */
    private function render_ticket_print_template($order)
    {
        // Get order data
        $order_id = $order->get_id();
        $order_date_obj = $order->get_date_created();
        $order_date_formatted = '';
        if ($order_date_obj) {
            $order_date_formatted = $order_date_obj->date_i18n(get_option('date_format')) . ' ' . $order_date_obj->date_i18n(get_option('time_format'));
        }
        $passenger = $this->get_passenger_display_data_for_ticket($order);
        $billing_name = $passenger['name'];
        $billing_email = $passenger['email'];
        $billing_phone = $passenger['phone'];
        $order_status_label = function_exists('wc_get_order_status_name') ? wc_get_order_status_name($order->get_status()) : $order->get_status();
        $payment_method_title = method_exists($order, 'get_payment_method_title') ? $order->get_payment_method_title() : '';
        if ($payment_method_title === '') {
            $payment_method_title = get_post_meta($order_id, '_payment_method_title', true);
        }
        if ($payment_method_title === '') {
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            $known_methods = array(
                'cod' => __('Cash on delivery', 'mt-ticket-bus'),
                'bacs' => __('Direct bank transfer', 'mt-ticket-bus'),
                'cheque' => __('Check payments', 'mt-ticket-bus'),
            );
            $payment_method_title = isset($known_methods[$payment_method]) ? $known_methods[$payment_method] : __('N/A', 'mt-ticket-bus');
        }
        $reservations = MT_Ticket_Bus_Reservations::get_instance()->get_order_reservations($order_id);
        $reservation_status_raw = (!empty($reservations) && isset($reservations[0]->status)) ? $reservations[0]->status : 'reserved';
        $reservation_status_labels = array(
            'reserved' => __('Reserved (status)', 'mt-ticket-bus'),
            'confirmed' => __('Confirmed (status)', 'mt-ticket-bus'),
            'cancelled' => __('Cancelled (status)', 'mt-ticket-bus'),
        );
        $reservation_status_label = isset($reservation_status_labels[$reservation_status_raw]) ? $reservation_status_labels[$reservation_status_raw] : $reservation_status_raw;

        // Order total for ticket print (includes all ticket items and their extras)
        $order_total = is_callable(array($order, 'get_total')) ? $order->get_total() : '0';
        $order_total_formatted = function_exists('wc_price') ? wc_price($order_total) : number_format((float) $order_total, 2, '.', '');

        // Get ticket items
        $ticket_items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
            if ($is_ticket_product !== 'yes') {
                continue;
            }

            // Get ticket data
            $departure_date = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
            $departure_time = wc_get_order_item_meta($item_id, '_mt_departure_time', true);
            $seat_number    = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
            $extras_json    = wc_get_order_item_meta($item_id, '_mt_ticket_extras', true);
            $extras_array   = array();
            if (! empty($extras_json)) {
                $decoded = json_decode($extras_json, true);
                if (is_array($decoded)) {
                    $extras_array = $decoded;
                }
            }
            $route_id = wc_get_order_item_meta($item_id, '_mt_route_id', true);
            $bus_id = wc_get_order_item_meta($item_id, '_mt_bus_id', true);
            $segment_start_name = wc_get_order_item_meta($item_id, '_mt_segment_start_name', true);
            $segment_end_name   = wc_get_order_item_meta($item_id, '_mt_segment_end_name', true);

            // Derive effective base seat price for this ticket (honouring segment pricing and excluding paid extras).
            // Use line total including taxes so printed \"Seat price\" matches the customer-facing total.
            $line_total = is_callable(array($item, 'get_total')) ? (float) $item->get_total() : 0.0;
            if (is_callable(array($item, 'get_total_tax'))) {
                $line_total += (float) $item->get_total_tax();
            }
            $qty        = max(1, (int) $item->get_quantity());
            $per_seat_total = $line_total / $qty;
            $extras_per_seat = 0.0;
            if (! empty($extras_array)) {
                foreach ($extras_array as $extra) {
                    $extras_per_seat += isset($extra['price']) ? (float) $extra['price'] : 0.0;
                }
            }
            $seat_price = max(0.0, $per_seat_total - $extras_per_seat);
            $seat_price_formatted = function_exists('wc_price') ? wc_price($seat_price) : number_format($seat_price, 2, '.', '');

            // Get route info
            $route_info = array();
            if ($route_id) {
                $routes = MT_Ticket_Bus_Routes::get_instance();
                $route = $routes->get_route($route_id);
                if ($route) {
                    $route_info = array(
                        'name' => $route->name,
                        'start_station' => $route->start_station,
                        'end_station' => $route->end_station,
                        'intermediate_stations' => $route->intermediate_stations,
                    );
                }
            }

            // Get bus info
            $bus_info = array();
            if ($bus_id) {
                $buses = MT_Ticket_Bus_Buses::get_instance();
                $bus = $buses->get_bus($bus_id);
                if ($bus) {
                    $bus_info = array(
                        'name' => $bus->name,
                        'registration_number' => $bus->registration_number,
                    );
                }
            }

            $ticket_items[] = array(
                'product_name'        => $item->get_name(),
                'quantity'            => $item->get_quantity(),
                'departure_date'      => $departure_date,
                'departure_time'      => $departure_time,
                'seat_number'         => $seat_number,
                'route_info'          => $route_info,
                'bus_info'            => $bus_info,
                'extras'              => $extras_array,
                'seat_price'          => $seat_price,
                'seat_price_formatted' => $seat_price_formatted,
                'segment_start_name'  => $segment_start_name,
                'segment_end_name'    => $segment_end_name,
            );
        }

        // Include print template
        include MT_TICKET_BUS_PLUGIN_DIR . 'templates/ticket-print.php';
    }

    /**
     * Register passenger fields for the Checkout Block when "buy for someone else" is enabled.
     *
     * @since 1.0.11
     */
    public function register_block_checkout_buy_for_other_fields()
    {
        $settings = get_option('mt_ticket_bus_settings', array());
        if (empty($settings['allow_buy_for_other']) || $settings['allow_buy_for_other'] !== 'yes') {
            return;
        }
        /* Cart check omitted here: at woocommerce_init the cart may not be loaded yet, so we register whenever the setting is on. */
        if (! function_exists('woocommerce_register_additional_checkout_field')) {
            return;
        }

        $namespace = 'mt_ticket_bus';

        woocommerce_register_additional_checkout_field(array(
            'id'       => $namespace . '/passenger_show',
            'label'    => __('Would you like to send the ticket to someone else?', 'mt-ticket-bus'),
            'location' => 'order',
            'type'     => 'checkbox',
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'       => $namespace . '/passenger_first_name',
            'label'    => __('Passenger first name', 'mt-ticket-bus'),
            'location' => 'order',
            'type'     => 'text',
            'required' => false,
            /* Hide field if passenger_show is NOT checked */
            'hidden'   => array(
                'checkout' => array(
                    'properties' => array(
                        'additional_fields' => array(
                            'properties' => array(
                                $namespace . '/passenger_show' => array(
                                    // скрий ако НЕ е чекнато
                                    'not' => array(
                                        'anyOf' => array(
                                            array('const' => true),
                                            array('const' => '1'),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Условно "required" чрез валидатор
            'validate_callback' => function ($value, $field, $request) use ($namespace) {
                // В request-а идват additional_fields
                $additional = isset($request['additional_fields']) ? (array) $request['additional_fields'] : array();

                $show = $additional[$namespace . '/passenger_show'] ?? null;

                $is_on = ($show === true || $show === '1' || $show === 1);

                if ($is_on && ($value === null || $value === '')) {
                    return new WP_Error('required_field', __('Passenger first name is required.', 'mt-ticket-bus'));
                }
            },
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'       => $namespace . '/passenger_last_name',
            'label'    => __('Passenger last name', 'mt-ticket-bus'),
            'location' => 'order',
            'type'     => 'text',
            'required' => false,
            /* Hide field if passenger_show is NOT checked */
            'hidden'   => array(
                'checkout' => array(
                    'properties' => array(
                        'additional_fields' => array(
                            'properties' => array(
                                $namespace . '/passenger_show' => array(
                                    // скрий ако НЕ е чекнато
                                    'not' => array(
                                        'anyOf' => array(
                                            array('const' => true),
                                            array('const' => '1'),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Условно "required" чрез валидатор
            'validate_callback' => function ($value, $field, $request) use ($namespace) {
                // В request-а идват additional_fields
                $additional = isset($request['additional_fields']) ? (array) $request['additional_fields'] : array();

                $show = $additional[$namespace . '/passenger_show'] ?? null;

                $is_on = ($show === true || $show === '1' || $show === 1);

                if ($is_on && ($value === null || $value === '')) {
                    return new WP_Error('required_field', __('Passenger first name is required.', 'mt-ticket-bus'));
                }
            },
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'                => $namespace . '/passenger_email',
            'label'             => __('Passenger email', 'mt-ticket-bus'),
            'location'          => 'order',
            'type'              => 'text',
            'required' => false,
            /* Hide field if passenger_show is NOT checked */
            'hidden'   => array(
                'checkout' => array(
                    'properties' => array(
                        'additional_fields' => array(
                            'properties' => array(
                                $namespace . '/passenger_show' => array(
                                    // скрий ако НЕ е чекнато
                                    'not' => array(
                                        'anyOf' => array(
                                            array('const' => true),
                                            array('const' => '1'),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'validate_callback' => function ($value, $field, $request) use ($namespace) {
                // В request-а идват additional_fields
                $additional = isset($request['additional_fields']) ? (array) $request['additional_fields'] : array();

                $show = $additional[$namespace . '/passenger_show'] ?? null;

                $is_on = ($show === true || $show === '1' || $show === 1);

                if ($is_on && ($value === null || $value === '')) {
                    return new WP_Error('required_field', __('Passenger first name is required.', 'mt-ticket-bus'));
                }
                if ($value !== '' && $value !== null && ! is_email($value)) {
                    return new WP_Error('invalid_email', __('Please enter a valid passenger email address.', 'mt-ticket-bus'));
                }
            },
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'         => $namespace . '/passenger_phone',
            'label'      => __('Passenger phone', 'mt-ticket-bus'),
            'location'   => 'order',
            'type'       => 'text',
            'required' => false,
            /* Hide field if passenger_show is NOT checked */
            'hidden'   => array(
                'checkout' => array(
                    'properties' => array(
                        'additional_fields' => array(
                            'properties' => array(
                                $namespace . '/passenger_show' => array(
                                    // скрий ако НЕ е чекнато
                                    'not' => array(
                                        'anyOf' => array(
                                            array('const' => true),
                                            array('const' => '1'),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Условно "required" чрез валидатор
            'validate_callback' => function ($value, $field, $request) use ($namespace) {
                // В request-а идват additional_fields
                $additional = isset($request['additional_fields']) ? (array) $request['additional_fields'] : array();

                $show = $additional[$namespace . '/passenger_show'] ?? null;

                $is_on = ($show === true || $show === '1' || $show === 1);

                if ($is_on && ($value === null || $value === '')) {
                    return new WP_Error('required_field', __('Passenger first name is required.', 'mt-ticket-bus'));
                }
            },
        ));
    }

    /**
     * Check if the current cart contains at least one ticket product.
     *
     * @since 1.0.11
     *
     * @return bool
     */
    private function cart_contains_ticket_products()
    {
        $cart = WC()->cart;
        if (! $cart) {
            return false;
        }
        foreach ($cart->get_cart() as $cart_item) {
            $product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
            if ($product_id && get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes') {
                return true;
            }
        }
        return false;
    }

    /**
     * Output passenger fields on checkout when "buy for someone else" is enabled (after billing form).
     *
     * Only shown when setting allow_buy_for_other is enabled and cart contains ticket products.
     *
     * @since 1.0.11
     *
     * @param object $checkout Checkout object (WC_Checkout).
     */
    public function checkout_buy_for_someone_else_fields($checkout)
    {
        $settings = get_option('mt_ticket_bus_settings', array());
        if (empty($settings['allow_buy_for_other']) || $settings['allow_buy_for_other'] !== 'yes') {
            return;
        }
        if (! $this->cart_contains_ticket_products()) {
            return;
        }
        ?>
        <div class="mt-passenger-fields mt-buy-for-someone-else" style="margin-top: 1em; padding: 1em; background: #f8f8f8; border-radius: 4px;">
            <p class="form-row form-row-wide">
                <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                    <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="mt_passenger_show" id="mt_passenger_show" value="1" <?php checked($checkout->get_value('mt_passenger_show'), '1'); ?> />
                    <span><?php esc_html_e('Do you want to send the ticket to someone else? (optional)', 'mt-ticket-bus'); ?></span>
                </label>
            </p>
            <div id="mt-passenger-fields-block" class="mt-passenger-fields-block" style="display: none; margin-top: 1em;">
                <p class="mt-passenger-fields-intro" style="margin: 0 0 1em 0;"><?php esc_html_e('If you would like to purchase the ticket in someone else\'s name, please fill in the details below.', 'mt-ticket-bus'); ?></p>
                <p class="form-row form-row-first">
                    <label for="mt_passenger_first_name"><?php esc_html_e('Passenger first name', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                    <span class="woocommerce-input-wrapper">
                        <input type="text" class="input-text" name="mt_passenger_first_name" id="mt_passenger_first_name" value="<?php echo esc_attr($checkout->get_value('mt_passenger_first_name')); ?>" />
                    </span>
                </p>
                <p class="form-row form-row-last">
                    <label for="mt_passenger_last_name"><?php esc_html_e('Passenger last name', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                    <span class="woocommerce-input-wrapper">
                        <input type="text" class="input-text" name="mt_passenger_last_name" id="mt_passenger_last_name" value="<?php echo esc_attr($checkout->get_value('mt_passenger_last_name')); ?>" />
                    </span>
                </p>
                <p class="form-row form-row-wide">
                    <label for="mt_passenger_email"><?php esc_html_e('Passenger email', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                    <span class="woocommerce-input-wrapper">
                        <input type="email" class="input-text" name="mt_passenger_email" id="mt_passenger_email" value="<?php echo esc_attr($checkout->get_value('mt_passenger_email')); ?>" />
                    </span>
                </p>
                <p class="form-row form-row-wide">
                    <label for="mt_passenger_phone"><?php esc_html_e('Passenger phone', 'mt-ticket-bus'); ?></label>
                    <span class="woocommerce-input-wrapper">
                        <input type="tel" class="input-text" name="mt_passenger_phone" id="mt_passenger_phone" value="<?php echo esc_attr($checkout->get_value('mt_passenger_phone')); ?>" />
                    </span>
                </p>
            </div>
        </div>
        <script>
            (function() {
                var cb = document.getElementById('mt_passenger_show');
                var block = document.getElementById('mt-passenger-fields-block');
                if (!cb || !block) return;

                function toggle() {
                    block.style.display = cb.checked ? 'block' : 'none';
                }
                cb.addEventListener('change', toggle);
                toggle();
            })();
        </script>
<?php
    }

    /**
     * Validate passenger fields when any passenger field or passenger_show is filled.
     *
     * @since 1.0.11
     */
    public function checkout_validate_buy_for_someone_else()
    {
        $settings = get_option('mt_ticket_bus_settings', array());
        if (empty($settings['allow_buy_for_other']) || $settings['allow_buy_for_other'] !== 'yes') {
            return;
        }
        if (! $this->cart_contains_ticket_products()) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce checkout hook; nonce verified by WooCommerce before firing.
        $first = isset($_POST['mt_passenger_first_name']) ? sanitize_text_field(wp_unslash($_POST['mt_passenger_first_name'])) : '';
        $last  = isset($_POST['mt_passenger_last_name']) ? sanitize_text_field(wp_unslash($_POST['mt_passenger_last_name'])) : '';
        $email = isset($_POST['mt_passenger_email']) ? sanitize_email(wp_unslash($_POST['mt_passenger_email'])) : '';
        $any_filled = (trim($first) !== '' || trim($last) !== '' || trim($email) !== '');
        $show_checked = ! empty($_POST['mt_passenger_show']) && $_POST['mt_passenger_show'] === '1';
        if (! $any_filled && ! $show_checked) {
            return;
        }

        if (trim($first) === '') {
            wc_add_notice(__('Please enter the passenger first name.', 'mt-ticket-bus'), 'error');
        }
        if (trim($last) === '') {
            wc_add_notice(__('Please enter the passenger last name.', 'mt-ticket-bus'), 'error');
        }
        if (trim($email) === '' || ! is_email($email)) {
            wc_add_notice(__('Please enter a valid passenger email address.', 'mt-ticket-bus'), 'error');
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Save "buy for someone else" data to order meta.
     *
     * @since 1.0.11
     *
     * @param int   $order_id Order ID.
     * @param array $data    Posted checkout data.
     */
    public function checkout_save_buy_for_someone_else($order_id, $data)
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }
        $settings = get_option('mt_ticket_bus_settings', array());
        if (empty($settings['allow_buy_for_other']) || $settings['allow_buy_for_other'] !== 'yes') {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce checkout hook; nonce verified by WooCommerce before firing.
        $first = isset($_POST['mt_passenger_first_name']) ? sanitize_text_field(wp_unslash($_POST['mt_passenger_first_name'])) : '';
        $last  = isset($_POST['mt_passenger_last_name']) ? sanitize_text_field(wp_unslash($_POST['mt_passenger_last_name'])) : '';
        $email = isset($_POST['mt_passenger_email']) ? sanitize_email(wp_unslash($_POST['mt_passenger_email'])) : '';
        $phone = isset($_POST['mt_passenger_phone']) ? sanitize_text_field(wp_unslash($_POST['mt_passenger_phone'])) : '';

        $order->update_meta_data('_mt_passenger_first_name', $first);
        $order->update_meta_data('_mt_passenger_last_name', $last);
        $order->update_meta_data('_mt_passenger_email', $email);
        $order->update_meta_data('_mt_passenger_phone', $phone);
        $passenger_show = (isset($_POST['mt_passenger_show']) && sanitize_text_field(wp_unslash($_POST['mt_passenger_show'])) === '1') ? 'yes' : 'no';
        $order->update_meta_data('_mt_passenger_show', $passenger_show);
        $order->update_meta_data('_mt_purchased_for_other', (trim($first) !== '' || trim($last) !== '' || trim($email) !== '') ? 'yes' : 'no');
        $order->save();
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Repopulate passenger fields on checkout when validation fails.
     *
     * @since 1.0.11
     *
     * @param mixed  $value Current value.
     * @param string $input Field name.
     * @return mixed
     */
    public function checkout_get_buy_for_someone_else_value($value, $input)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce checkout hook; nonce verified by WooCommerce before firing.
        $keys = array('mt_passenger_show', 'mt_passenger_first_name', 'mt_passenger_last_name', 'mt_passenger_email', 'mt_passenger_phone');
        if (! in_array($input, $keys, true) || ! isset($_POST[$input]) || $_POST[$input] === '') {
            return $value;
        }
        if ($input === 'mt_passenger_email') {
            return sanitize_email(wp_unslash($_POST[$input]));
        }
        if ($input === 'mt_passenger_show') {
            return '1';
        }
        return sanitize_text_field(wp_unslash($_POST[$input]));
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Add passenger email as additional recipient for ticket order emails when passenger data is present.
     *
     * @since 1.0.11
     *
     * @param string   $recipient Comma-separated recipients.
     * @param WC_Order $order     Order (may be null for some email types).
     * @param object   $email     WC_Email instance.
     * @return string
     */
    public function add_passenger_email_recipient($recipient, $order, $email)
    {
        if (! $order || ! is_a($order, 'WC_Order')) {
            return $recipient;
        }
        $email_ids = array('customer_processing_order', 'customer_completed_order');
        if (! is_object($email) || ! isset($email->id) || ! in_array($email->id, $email_ids, true)) {
            return $recipient;
        }
        if (! $this->order_contains_ticket_products($order)) {
            return $recipient;
        }
        $passenger_email = $order->get_meta('_mt_passenger_email');
        if (empty($passenger_email) || ! is_email($passenger_email)) {
            $passenger_email = $order->get_meta('_wc_other/mt_ticket_bus/passenger_email');
        }
        if (empty($passenger_email) || ! is_email($passenger_email)) {
            return $recipient;
        }
        $recipient = trim($recipient);
        if (strpos($recipient, $passenger_email) !== false) {
            return $recipient;
        }
        return $recipient . ',' . $passenger_email;
    }

    /**
     * Check if order contains at least one ticket product.
     *
     * @since 1.0.2
     *
     * @param WC_Order $order Order object.
     * @return bool True if order contains ticket products.
     */
    private function order_contains_ticket_products($order)
    {
        if (!$order || !is_a($order, 'WC_Order')) {
            return false;
        }
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes') {
                return true;
            }
        }
        return false;
    }

    /**
     * Hide product image in order email items table for ticket orders.
     *
     * Keeps product name and description; only the thumbnail is disabled.
     *
     * @since 1.0.5
     *
     * @param array $args Arguments passed to email order items template (includes 'order', 'show_image', etc.).
     * @return array Modified args.
     */
    public function hide_ticket_order_email_product_image($args)
    {
        if (empty($args['order']) || !$this->order_contains_ticket_products($args['order'])) {
            return $args;
        }
        $args['show_image'] = false;
        return $args;
    }

    /**
     * Customize email subject for ticket orders.
     *
     * @since 1.0.2
     *
     * @param string   $subject  Email subject.
     * @param WC_Order $order    Order object.
     * @param object   $email    WC_Email object.
     * @return string Modified subject.
     */
    public function customize_ticket_order_email_subject($subject, $order, $email)
    {
        if (!$order || !is_object($email) || !isset($email->id) || !$this->order_contains_ticket_products($order)) {
            return $subject;
        }
        $email_ids = array('customer_processing_order', 'customer_completed_order', 'new_order');
        if (!in_array($email->id, $email_ids, true)) {
            return $subject;
        }
        $order_number = $order->get_order_number();
        return sprintf(
            /* translators: %s: order number */
            __('Your bus ticket – Order #%s', 'mt-ticket-bus'),
            $order_number
        );
    }

    /**
     * Customize email heading for ticket orders.
     *
     * @since 1.0.2
     *
     * @param string   $heading Email heading.
     * @param WC_Order $order   Order object.
     * @param object   $email  WC_Email object.
     * @return string Modified heading.
     */
    public function customize_ticket_order_email_heading($heading, $order, $email)
    {
        if (!$order || !is_object($email) || !isset($email->id) || !$this->order_contains_ticket_products($order)) {
            return $heading;
        }
        $email_ids = array('customer_processing_order', 'customer_completed_order', 'new_order');
        if (!in_array($email->id, $email_ids, true)) {
            return $heading;
        }
        return __('Your bus ticket', 'mt-ticket-bus');
    }

    /**
     * Add ticket-specific content to order emails.
     *
     * @since 1.0.2
     *
     * @param string   $content Additional content (may be empty).
     * @param WC_Order $order   Order object.
     * @param object   $email   WC_Email object.
     * @return string Modified content.
     */
    public function add_ticket_order_email_content($content, $order, $email)
    {
        if (! $order || ! $this->order_contains_ticket_products($order)) {
            return $content;
        }
        $ticket_message = '<p style="margin-top:16px;">' . __('Thank you for your ticket purchase. Your reservation is confirmed. You can print or download your ticket from the order details page.', 'mt-ticket-bus') . '</p>';

        $additional = '';
        $show = $order->get_meta('_mt_passenger_show');
        if ($show === 'yes' || $show === '1') {
            $first = $order->get_meta('_mt_passenger_first_name');
            $last  = $order->get_meta('_mt_passenger_last_name');
            $email_val = $order->get_meta('_mt_passenger_email');
            $phone = $order->get_meta('_mt_passenger_phone');
            $show_label = __('Would you like to send the ticket to someone else?', 'mt-ticket-bus');
            $show_value = ($show === 'yes' || $show === '1') ? __('Yes', 'mt-ticket-bus') : __('No', 'mt-ticket-bus');
            $additional = '<div style="margin-top:20px; padding-top:16px; border-top:1px solid #eee;">';
            $additional .= '<h3 style="margin:0 0 10px 0; font-size:14px;">' . esc_html__('Additional information', 'mt-ticket-bus') . '</h3>';
            $additional .= '<table style="width:100%; border-collapse:collapse; font-size:13px;" cellpadding="0" cellspacing="0">';
            $additional .= '<tr><td style="padding:4px 8px 4px 0; vertical-align:top; font-weight:400;">' . esc_html($show_label) . '</td><td style="padding:4px 0;">' . esc_html($show_value) . '</td></tr>';
            $additional .= '<tr><td style="padding:4px 8px 4px 0; vertical-align:top; font-weight:400;">' . esc_html__('Passenger first name', 'mt-ticket-bus') . '</td><td style="padding:4px 0;">' . esc_html($first) . '</td></tr>';
            $additional .= '<tr><td style="padding:4px 8px 4px 0; vertical-align:top; font-weight:400;">' . esc_html__('Passenger last name', 'mt-ticket-bus') . '</td><td style="padding:4px 0;">' . esc_html($last) . '</td></tr>';
            $additional .= '<tr><td style="padding:4px 8px 4px 0; vertical-align:top; font-weight:400;">' . esc_html__('Passenger email', 'mt-ticket-bus') . '</td><td style="padding:4px 0;">' . esc_html($email_val) . '</td></tr>';
            $additional .= '<tr><td style="padding:4px 8px 4px 0; vertical-align:top; font-weight:400;">' . esc_html__('Passenger phone', 'mt-ticket-bus') . '</td><td style="padding:4px 0;">' . esc_html($phone) . '</td></tr>';
            $additional .= '</table></div>';
        }

        return $content . $ticket_message . $additional;
    }

    /**
     * Attach ticket PDF to order emails when order contains tickets.
     *
     * PDF path can be provided by implementing the filter mt_ticket_bus_ticket_pdf_path
     * (e.g. using Dompdf or another PDF library to generate from the ticket print template).
     *
     * @since 1.0.2
     *
     * @param array    $attachments Array of attachment file paths.
     * @param string   $email_id    Email ID (e.g. customer_processing_order).
     * @param WC_Order $order       Order object.
     * @param object   $email       WC_Email object.
     * @return array Modified attachments.
     */
    public function attach_ticket_pdf_to_email($attachments, $email_id, $order, $email)
    {
        if (!$order || !$this->order_contains_ticket_products($order)) {
            return $attachments;
        }
        $email_ids = array('customer_processing_order', 'customer_completed_order');
        if (!in_array($email_id, $email_ids, true)) {
            return $attachments;
        }
        $pdf_path = $this->get_ticket_pdf_path_for_email($order);
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }
        return $attachments;
    }

    /**
     * Get path to generated ticket PDF for email attachment.
     *
     * Returns path only if a PDF is generated (e.g. via filter or Dompdf).
     * Use filter mt_ticket_bus_ticket_pdf_path to provide a path from custom code
     * or another plugin that generates the PDF.
     *
     * @since 1.0.2
     *
     * @param WC_Order $order Order object.
     * @return string|null Full path to PDF file or null if no PDF.
     */
    public function get_ticket_pdf_path_for_email($order)
    {
        $path = apply_filters('mt_ticket_bus_ticket_pdf_path', null, $order);
        if (is_string($path) && $path !== '' && file_exists($path)) {
            return $path;
        }
        // Optional: generate PDF with Dompdf if available (e.g. via Composer)
        return $this->generate_ticket_pdf_file($order);
    }

    /**
     * Generate ticket PDF file for email attachment (requires Dompdf).
     *
     * If Dompdf is available (e.g. via Composer: composer require dompdf/dompdf),
     * generates a PDF from the ticket print template and returns the file path.
     * Otherwise returns null.
     *
     * @since 1.0.2
     *
     * @param WC_Order $order Order object.
     * @return string|null Full path to PDF file or null.
     */
    private function generate_ticket_pdf_file($order)
    {
        $dompdf_class = '\Dompdf\Dompdf';
        if (!class_exists($dompdf_class)) {
            return null;
        }
        $html = $this->get_ticket_print_html($order, true);
        if ($html === '') {
            return null;
        }
        try {
            /** @var \Dompdf\Dompdf $dompdf */
            $dompdf = new $dompdf_class(array('isRemoteEnabled' => true));
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $upload_dir = wp_upload_dir();
            $dir = $upload_dir['basedir'] . '/mt-ticket-bus-pdfs';
            if (!wp_mkdir_p($dir)) {
                return null;
            }
            $filename = 'ticket-order-' . $order->get_id() . '-' . wp_generate_password(8, false) . '.pdf';
            $path = $dir . '/' . $filename;
            file_put_contents($path, $dompdf->output());
            return file_exists($path) ? $path : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get passenger name, email, phone for ticket/PDF display.
     * When the order is "for another passenger", uses data from the first reservation; otherwise order billing.
     *
     * @since 1.0.11
     *
     * @param WC_Order $order Order object.
     * @return array{name: string, email: string, phone: string}
     */
    private function get_passenger_display_data_for_ticket($order)
    {
        $name  = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();

        $show = $order->get_meta('_mt_passenger_show');
        if ($show !== 'yes' && $show !== '1') {
            $show = $order->get_meta('_wc_other/mt_ticket_bus/passenger_show');
            if ($show !== '1' && $show !== 1 && $show !== 'yes') {
                return array('name' => $name, 'email' => $email, 'phone' => $phone);
            }
        }

        $reservations = MT_Ticket_Bus_Reservations::get_instance()->get_order_reservations($order->get_id());
        if (empty($reservations) || ! isset($reservations[0])) {
            return array('name' => $name, 'email' => $email, 'phone' => $phone);
        }

        $r = $reservations[0];
        if (trim((string) $r->passenger_name) !== '') {
            $name = trim($r->passenger_name);
        }
        if (trim((string) $r->passenger_email) !== '' && is_email($r->passenger_email)) {
            $email = $r->passenger_email;
        }
        if (trim((string) $r->passenger_phone) !== '') {
            $phone = $r->passenger_phone;
        }
        return array('name' => $name, 'email' => $email, 'phone' => $phone);
    }

    /**
     * Get ticket print template output as HTML string.
     *
     * @since 1.0.2
     *
     * @param WC_Order $order   Order object.
     * @param bool     $for_pdf If true, output is for PDF (e.g. email attachment); hides print button and print script.
     * @return string HTML output.
     */
    private function get_ticket_print_html($order, $for_pdf = false)
    {
        $order_id = $order->get_id();
        $order_date_obj = $order->get_date_created();
        $order_date_formatted = '';
        if ($order_date_obj) {
            $order_date_formatted = $order_date_obj->date_i18n(get_option('date_format')) . ' ' . $order_date_obj->date_i18n(get_option('time_format'));
        }
        $passenger = $this->get_passenger_display_data_for_ticket($order);
        $billing_name = $passenger['name'];
        $billing_email = $passenger['email'];
        $billing_phone = $passenger['phone'];
        $order_status_label = function_exists('wc_get_order_status_name') ? wc_get_order_status_name($order->get_status()) : $order->get_status();
        $payment_method_title = method_exists($order, 'get_payment_method_title') ? $order->get_payment_method_title() : '';
        if ($payment_method_title === '') {
            $payment_method_title = get_post_meta($order_id, '_payment_method_title', true);
        }
        if ($payment_method_title === '') {
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            $known_methods = array(
                'cod' => __('Cash on delivery', 'mt-ticket-bus'),
                'bacs' => __('Direct bank transfer', 'mt-ticket-bus'),
                'cheque' => __('Check payments', 'mt-ticket-bus'),
            );
            $payment_method_title = isset($known_methods[$payment_method]) ? $known_methods[$payment_method] : __('N/A', 'mt-ticket-bus');
        }
        $reservations = MT_Ticket_Bus_Reservations::get_instance()->get_order_reservations($order_id);
        $reservation_status_raw = (!empty($reservations) && isset($reservations[0]->status)) ? $reservations[0]->status : 'reserved';
        $reservation_status_labels = array(
            'reserved' => __('Reserved (status)', 'mt-ticket-bus'),
            'confirmed' => __('Confirmed (status)', 'mt-ticket-bus'),
            'cancelled' => __('Cancelled (status)', 'mt-ticket-bus'),
        );
        $reservation_status_label = isset($reservation_status_labels[$reservation_status_raw]) ? $reservation_status_labels[$reservation_status_raw] : $reservation_status_raw;
        $order_total = is_callable(array($order, 'get_total')) ? $order->get_total() : '0';
        $order_total_formatted = function_exists('wc_price') ? wc_price($order_total) : number_format((float) $order_total, 2, '.', '');
        $ticket_items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            if (get_post_meta($product_id, '_mt_is_ticket_product', true) !== 'yes') {
                continue;
            }
            $departure_date = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
            $departure_time = wc_get_order_item_meta($item_id, '_mt_departure_time', true);
            $seat_number    = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
            $extras_json    = wc_get_order_item_meta($item_id, '_mt_ticket_extras', true);
            $extras_array   = array();
            if (! empty($extras_json)) {
                $decoded = json_decode($extras_json, true);
                if (is_array($decoded)) {
                    $extras_array = $decoded;
                }
            }
            $route_id = wc_get_order_item_meta($item_id, '_mt_route_id', true);
            $bus_id = wc_get_order_item_meta($item_id, '_mt_bus_id', true);
            $segment_start_name = wc_get_order_item_meta($item_id, '_mt_segment_start_name', true);
            $segment_end_name   = wc_get_order_item_meta($item_id, '_mt_segment_end_name', true);
            // Derive effective base seat price for this ticket (honouring segment pricing and excluding paid extras).
            // Use line total including taxes so printed \"Seat price\" matches the customer-facing total.
            $line_total = is_callable(array($item, 'get_total')) ? (float) $item->get_total() : 0.0;
            if (is_callable(array($item, 'get_total_tax'))) {
                $line_total += (float) $item->get_total_tax();
            }
            $qty        = max(1, (int) $item->get_quantity());
            $per_seat_total = $line_total / $qty;
            $extras_per_seat = 0.0;
            if (! empty($extras_array)) {
                foreach ($extras_array as $extra) {
                    $extras_per_seat += isset($extra['price']) ? (float) $extra['price'] : 0.0;
                }
            }
            $seat_price = max(0.0, $per_seat_total - $extras_per_seat);
            $seat_price_formatted = function_exists('wc_price') ? wc_price($seat_price) : number_format($seat_price, 2, '.', '');

            $route_info = array();
            if ($route_id) {
                $routes = MT_Ticket_Bus_Routes::get_instance();
                $route = $routes->get_route($route_id);
                if ($route) {
                    $route_info = array(
                        'name' => $route->name,
                        'start_station' => $route->start_station,
                        'end_station' => $route->end_station,
                        'intermediate_stations' => $route->intermediate_stations,
                    );
                }
            }
            $bus_info = array();
            if ($bus_id) {
                $buses = MT_Ticket_Bus_Buses::get_instance();
                $bus = $buses->get_bus($bus_id);
                if ($bus) {
                    $bus_info = array(
                        'name' => $bus->name,
                        'registration_number' => $bus->registration_number,
                    );
                }
            }
            $ticket_items[] = array(
                'product_name'         => $item->get_name(),
                'quantity'             => $item->get_quantity(),
                'departure_date'       => $departure_date,
                'departure_time'       => $departure_time,
                'seat_number'          => $seat_number,
                'route_info'           => $route_info,
                'bus_info'             => $bus_info,
                'extras'               => $extras_array,
                'seat_price'           => $seat_price,
                'seat_price_formatted' => $seat_price_formatted,
                'segment_start_name'   => $segment_start_name,
                'segment_end_name'     => $segment_end_name,
            );
        }
        if (empty($ticket_items)) {
            return '';
        }
        $mt_for_pdf = $for_pdf;
        ob_start();
        include MT_TICKET_BUS_PLUGIN_DIR . 'templates/ticket-print.php';
        return ob_get_clean();
    }

    /**
     * Get ticket product IDs (products marked as ticket products).
     *
     * @since 1.0.0
     * @return int[] Array of product post IDs.
     */
    public static function get_ticket_product_ids()
    {
        $query = new WP_Query(array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Ticket products by _mt_is_ticket_product; product count typically small.
            'meta_query'     => array(
                array(
                    'key'   => '_mt_is_ticket_product',
                    'value' => 'yes',
                ),
            ),
        ));
        return $query->posts ? array_map('intval', $query->posts) : array();
    }

    /**
     * Get ticket sales aggregated by month for a given year.
     * Returns counts and totals for paid orders containing ticket products.
     *
     * @since 1.0.0
     * @param int $year Year (e.g. 2025).
     * @return array<int, array{month: int, tickets_count: int, total_amount: float}> Index 1–12, keys are month numbers.
     */
    public static function get_ticket_sales_by_month($year)
    {
        if (! function_exists('wc_get_orders') || ! function_exists('wc_get_is_paid_statuses')) {
            $defaults = array();
            for ($m = 1; $m <= 12; $m++) {
                $defaults[$m] = array('month' => $m, 'tickets_count' => 0, 'total_amount' => 0.0);
            }
            return $defaults;
        }

        $ticket_product_ids = self::get_ticket_product_ids();
        if (empty($ticket_product_ids)) {
            $defaults = array();
            for ($m = 1; $m <= 12; $m++) {
                $defaults[$m] = array('month' => $m, 'tickets_count' => 0, 'total_amount' => 0.0);
            }
            return $defaults;
        }

        $paid_statuses = call_user_func('wc_get_is_paid_statuses');
        $orders = call_user_func('wc_get_orders', array(
            'status'     => $paid_statuses,
            'date_query'  => array(
                array(
                    'after'  => array('year' => (int) $year, 'month' => 1, 'day' => 1),
                    'before' => array('year' => (int) $year, 'month' => 12, 'day' => 31),
                    'inclusive' => true,
                ),
            ),
            'return'      => 'ids',
            'limit'       => -1,
        ));
        if (! is_array($orders)) {
            $defaults = array();
            for ($m = 1; $m <= 12; $m++) {
                $defaults[$m] = array('month' => $m, 'tickets_count' => 0, 'total_amount' => 0.0);
            }
            return $defaults;
        }

        $by_month = array();
        for ($m = 1; $m <= 12; $m++) {
            $by_month[$m] = array('month' => $m, 'tickets_count' => 0, 'total_amount' => 0.0);
        }

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (! $order) {
                continue;
            }
            $order_month = (int) $order->get_date_created()->format('n');
            foreach ($order->get_items() as $item) {
                $product_id = (int) $item->get_product_id();
                if (! in_array($product_id, $ticket_product_ids, true)) {
                    continue;
                }
                $qty = (int) $item->get_quantity();
                $total = (float) $item->get_total();
                $by_month[$order_month]['tickets_count'] += $qty;
                $by_month[$order_month]['total_amount'] += $total;
            }
        }

        return $by_month;
    }

    /**
     * Get best ticket customers for a year (by total amount spent on ticket products).
     *
     * @since 1.0.0
     * @param int $year  Year (e.g. 2025).
     * @param int $limit Maximum number of customers to return. Default 3.
     * @return array<int, array{email: string, name: string, total_amount: float, tickets_count: int, last_order_id: int}>
     */
    public static function get_best_ticket_customers($year, $limit = 3)
    {
        if (! function_exists('wc_get_orders') || ! function_exists('wc_get_is_paid_statuses')) {
            return array();
        }

        $ticket_product_ids = self::get_ticket_product_ids();
        if (empty($ticket_product_ids)) {
            return array();
        }

        $paid_statuses = call_user_func('wc_get_is_paid_statuses');
        $orders = call_user_func('wc_get_orders', array(
            'status'     => $paid_statuses,
            'date_query' => array(
                array(
                    'after'     => array('year' => (int) $year, 'month' => 1, 'day' => 1),
                    'before'    => array('year' => (int) $year, 'month' => 12, 'day' => 31),
                    'inclusive' => true,
                ),
            ),
            'return'     => 'ids',
            'limit'     => -1,
        ));
        if (! is_array($orders)) {
            return array();
        }

        $by_customer = array();

        foreach ($orders as $order_id) {
            try {
                $order = wc_get_order($order_id);
                if (! $order || ! is_object($order) || ! method_exists($order, 'get_billing_email')) {
                    continue;
                }
                $email = $order->get_billing_email();
                if (empty($email)) {
                    continue;
                }
                $key = strtolower(trim($email));
                $first = $order->get_billing_first_name();
                $last  = $order->get_billing_last_name();
                $name  = trim((string) $first . ' ' . (string) $last);
                if ($name === '') {
                    $user_id = $order->get_customer_id();
                    if ($user_id) {
                        $user = call_user_func('get_userdata', $user_id);
                        $name = $user ? $user->display_name : $email;
                    } else {
                        $name = $email;
                    }
                }
                if (! isset($by_customer[$key])) {
                    $by_customer[$key] = array(
                        'email'          => $email,
                        'name'           => $name,
                        'total_amount'   => 0.0,
                        'tickets_count'  => 0,
                        'last_order_id'  => (int) $order_id,
                    );
                }
                foreach ($order->get_items() as $item) {
                    $product_id = (int) $item->get_product_id();
                    if (! in_array($product_id, $ticket_product_ids, true)) {
                        continue;
                    }
                    $by_customer[$key]['total_amount']  += (float) $item->get_total();
                    $by_customer[$key]['tickets_count'] += (int) $item->get_quantity();
                    $by_customer[$key]['last_order_id']  = max((int) $by_customer[$key]['last_order_id'], (int) $order_id);
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        usort($by_customer, function ($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        return array_slice($by_customer, 0, (int) $limit);
    }
}
