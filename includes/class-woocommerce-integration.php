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

        // AJAX handlers for adding tickets to cart
        add_action('wp_ajax_mt_add_tickets_to_cart', array($this, 'ajax_add_tickets_to_cart'));
        add_action('wp_ajax_nopriv_mt_add_tickets_to_cart', array($this, 'ajax_add_tickets_to_cart'));

        // Save ticket meta data to cart items
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_ticket_cart_item_data'), 10, 3);

        // Display ticket meta in cart and checkout
        add_filter('woocommerce_get_item_data', array($this, 'display_ticket_cart_item_data'), 10, 2);

        // Save ticket meta to order items
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_ticket_order_item_meta'), 10, 4);

        // Display ticket reservation info in order received page
        add_action('woocommerce_order_item_meta_end', array($this, 'display_ticket_order_item_meta'), 10, 3);

        // Add print and download buttons to order received page
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_ticket_actions'), 10, 1);

        // Handle ticket print and download requests
        add_action('wp_ajax_mt_print_ticket', array($this, 'ajax_print_ticket'));
        add_action('wp_ajax_nopriv_mt_print_ticket', array($this, 'ajax_print_ticket'));
        add_action('wp_ajax_mt_download_ticket', array($this, 'ajax_download_ticket'));
        add_action('wp_ajax_nopriv_mt_download_ticket', array($this, 'ajax_download_ticket'));

        // Enqueue scripts for order received page
        add_action('wp_enqueue_scripts', array($this, 'enqueue_order_received_scripts'));

        // Handle print ticket request
        add_action('template_redirect', array($this, 'handle_print_ticket_request'));

        // Handle download ticket request via URL
        add_action('template_redirect', array($this, 'handle_download_ticket_request'));

        // Hide and customize order item meta display in admin
        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'hide_order_item_meta'), 10, 1);
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'format_order_item_meta_key'), 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'format_order_item_meta_value'), 10, 3);

        // Enqueue admin scripts for WooCommerce product edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
        echo MT_Ticket_Bus_Renderer::render_seatmap();
    }

    /**
     * Display ticket summary for standard themes.
     *
     * @since 1.0.0
     */
    public function display_ticket_summary()
    {
        echo MT_Ticket_Bus_Renderer::render_ticket_summary();
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
        $month = isset($_POST['month']) ? absint($_POST['month']) : (int) date('n');
        $year = isset($_POST['year']) ? absint($_POST['year']) : (int) date('Y');

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
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $departure_time = isset($_POST['departure_time']) ? sanitize_text_field($_POST['departure_time']) : '';

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
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

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
        $tickets = isset($_POST['tickets']) && is_array($_POST['tickets']) ? $_POST['tickets'] : array();
        $buy_now = isset($_POST['buy_now']) && $_POST['buy_now'] === 'true';

        if (! $product_id || empty($tickets)) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mt-ticket-bus')));
        }

        // Verify product exists and is purchasable
        $product = wc_get_product($product_id);
        if (! $product || ! $product->is_purchasable()) {
            wp_send_json_error(array('message' => __('Product is not available for purchase.', 'mt-ticket-bus')));
        }

        // Add each ticket as a separate cart item
        $added_count = 0;
        $errors = array();

        foreach ($tickets as $ticket) {
            if (! isset($ticket['date']) || ! isset($ticket['time']) || ! isset($ticket['seat'])) {
                continue;
            }

            // Validate ticket data
            $date = sanitize_text_field($ticket['date']);
            $time = sanitize_text_field($ticket['time']);
            $seat = sanitize_text_field($ticket['seat']);

            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $errors[] = sprintf(__('Invalid date format for seat %s.', 'mt-ticket-bus'), $seat);
                continue;
            }

            // Prepare cart item data with ticket meta
            $cart_item_data = array(
                'mt_ticket_date' => $date,
                'mt_ticket_time' => $time,
                'mt_ticket_seat' => $seat,
                'mt_ticket_product_id' => $product_id,
            );

            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

            if ($cart_item_key) {
                $added_count++;
            } else {
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

        // Ticket data should already be in cart_item_data from AJAX call
        // This filter is here to ensure it's preserved
        return $cart_item_data;
    }

    /**
     * Display ticket meta data in cart and checkout
     */
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
        $departure_date = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
        $departure_time = wc_get_order_item_meta($item_id, '_mt_departure_time', true);
        $seat_number = wc_get_order_item_meta($item_id, '_mt_seat_number', true);

        // Only display if we have reservation data
        if (empty($departure_date) && empty($departure_time) && empty($seat_number)) {
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

        if (!empty($formatted_date)) {
            echo '<span class="mt-reservation-date">';
            echo '<strong>' . esc_html__('Date:', 'mt-ticket-bus') . '</strong> ' . esc_html($formatted_date);
            echo '</span>';
        }

        if (!empty($formatted_time)) {
            if (!empty($formatted_date)) {
                echo ' | ';
            }
            echo '<span class="mt-reservation-time">';
            echo '<strong>' . esc_html__('Time:', 'mt-ticket-bus') . '</strong> ' . esc_html($formatted_time);
            echo '</span>';
        }

        if (!empty($seat_number)) {
            if (!empty($formatted_date) || !empty($formatted_time)) {
                echo ' | ';
            }
            echo '<span class="mt-reservation-seat">';
            echo '<strong>' . esc_html__('Seat:', 'mt-ticket-bus') . '</strong> ' . esc_html($seat_number);
            echo '</span>';
        }

        echo '</div>';
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
     * Enqueue scripts for order received page.
     *
     * @since 1.0.0
     */
    public function enqueue_order_received_scripts()
    {
        if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
            return;
        }

        // Enqueue QR code library (qrcodejs)
        wp_enqueue_script(
            'qrcodejs',
            'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js',
            array(),
            '1.0.0',
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
     * AJAX handler for printing ticket.
     *
     * @since 1.0.0
     */
    public function ajax_print_ticket()
    {
        check_ajax_referer('mt_ticket_order_actions', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order_key = isset($_POST['order_key']) ? sanitize_text_field($_POST['order_key']) : '';

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
        $order_key = isset($_POST['order_key']) ? sanitize_text_field($_POST['order_key']) : '';

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

        // Generate PDF download
        $this->generate_ticket_pdf($order);
    }

    /**
     * Handle download ticket request via URL.
     *
     * @since 1.0.0
     */
    public function handle_download_ticket_request()
    {
        if (!isset($_GET['mt_download_ticket']) || !isset($_GET['order_id']) || !isset($_GET['order_key'])) {
            return;
        }

        $order_id = absint($_GET['order_id']);
        $order_key = sanitize_text_field($_GET['order_key']);

        if (!$order_id || !$order_key) {
            wp_die(__('Invalid request.', 'mt-ticket-bus'));
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die(__('Order not found.', 'mt-ticket-bus'));
        }

        // For QR code downloads, we validate only by order key (no session required)
        // This allows the link to work from any device at any time
        // The order key is cryptographically secure and unique per order

        // Generate PDF download
        $this->generate_ticket_pdf($order);
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
            '_mt_seat_number' => __('Seat', 'mt-ticket-bus'),
            '_mt_departure_date' => __('Date', 'mt-ticket-bus'),
            '_mt_departure_time' => __('Time', 'mt-ticket-bus'),
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

        echo '</div>';

        // Add JavaScript for dynamic schedule loading
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var ticketCheckbox = $('#_mt_is_ticket_product');
                var routeSelect = $('#_mt_bus_route_id');
                var busSelect = $('#_mt_bus_id');
                var scheduleSelect = $('#_mt_bus_schedule_id');
                var savedScheduleId = scheduleSelect.val(); // Save the current schedule ID

                // Function to toggle fields based on checkbox state
                function toggleTicketFields() {
                    var isChecked = ticketCheckbox.is(':checked');
                    routeSelect.prop('disabled', !isChecked);
                    busSelect.prop('disabled', !isChecked);
                    scheduleSelect.prop('disabled', !isChecked);

                    // Clear values if checkbox is unchecked
                    if (!isChecked) {
                        routeSelect.val('').trigger('change');
                        busSelect.val('');
                        scheduleSelect.val('');
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
                            nonce: '<?php echo wp_create_nonce('mt_ticket_bus_admin'); ?>'
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
        // Save ticket product flag
        $is_ticket_product = isset($_POST['_mt_is_ticket_product']) ? 'yes' : 'no';
        update_post_meta($post_id, '_mt_is_ticket_product', $is_ticket_product);

        // Only save ticket-related fields if product is marked as ticket
        if ($is_ticket_product === 'yes') {
            if (isset($_POST['_mt_bus_route_id'])) {
                update_post_meta($post_id, '_mt_bus_route_id', sanitize_text_field($_POST['_mt_bus_route_id']));
            }

            if (isset($_POST['_mt_bus_id'])) {
                update_post_meta($post_id, '_mt_bus_id', sanitize_text_field($_POST['_mt_bus_id']));
            }

            if (isset($_POST['_mt_bus_schedule_id'])) {
                update_post_meta($post_id, '_mt_bus_schedule_id', sanitize_text_field($_POST['_mt_bus_schedule_id']));
            }
        } else {
            // Clear ticket-related fields if product is not a ticket
            delete_post_meta($post_id, '_mt_bus_route_id');
            delete_post_meta($post_id, '_mt_bus_id');
            delete_post_meta($post_id, '_mt_bus_schedule_id');
        }
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
                $time_value = date('H:i', strtotime($departure_time));
                $options[] = array(
                    'value' => $time_value,
                    'label' => $time_display
                );
            }
        }

        wp_send_json_success(array('courses' => $options));
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
        $order_key = sanitize_text_field($_GET['order_key']);
        $is_pdf_download = isset($_GET['mt_pdf']) && $_GET['mt_pdf'] == 1;
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';

        if (!$order_id || !$order_key) {
            wp_die(__('Invalid request.', 'mt-ticket-bus'));
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die(__('Order not found.', 'mt-ticket-bus'));
        }

        // For PDF downloads from QR code, skip nonce and permission checks
        // Order key validation is sufficient for security
        if (!$is_pdf_download) {
            // For regular print requests (from buttons), verify nonce
            if (!$nonce || !wp_verify_nonce($nonce, 'mt_print_ticket_' . $order_id)) {
                wp_die(__('Invalid request.', 'mt-ticket-bus'));
            }

            // Check if user has permission to view this order
            if (!current_user_can('view_order', $order_id) && $order->get_customer_id() !== get_current_user_id()) {
                wp_die(__('Permission denied.', 'mt-ticket-bus'));
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
        $billing_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();
        $billing_phone = $order->get_billing_phone();

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
            $seat_number = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
            $route_id = wc_get_order_item_meta($item_id, '_mt_route_id', true);
            $bus_id = wc_get_order_item_meta($item_id, '_mt_bus_id', true);

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
                'product_name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'departure_date' => $departure_date,
                'departure_time' => $departure_time,
                'seat_number' => $seat_number,
                'route_info' => $route_info,
                'bus_info' => $bus_info,
            );
        }

        // Include print template
        include MT_TICKET_BUS_PLUGIN_DIR . 'templates/ticket-print.php';
    }

    /**
     * Generate ticket PDF.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order Order object.
     */
    private function generate_ticket_pdf($order)
    {
        // For now, we'll use a simple approach with HTML to PDF conversion
        // In production, you might want to use TCPDF, mPDF, or similar library

        // Get order data (same as print template)
        $order_id = $order->get_id();
        $order_date = $order->get_date_created();
        $billing_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();
        $billing_phone = $order->get_billing_phone();

        // Get ticket items (same logic as print template)
        $ticket_items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
            if ($is_ticket_product !== 'yes') {
                continue;
            }

            $departure_date = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
            $departure_time = wc_get_order_item_meta($item_id, '_mt_departure_time', true);
            $seat_number = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
            $route_id = wc_get_order_item_meta($item_id, '_mt_route_id', true);
            $bus_id = wc_get_order_item_meta($item_id, '_mt_bus_id', true);

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
                'product_name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'departure_date' => $departure_date,
                'departure_time' => $departure_time,
                'seat_number' => $seat_number,
                'route_info' => $route_info,
                'bus_info' => $bus_info,
            );
        }

        // For now, redirect to print page with PDF parameter
        // In production, use a library like TCPDF or mPDF to generate actual PDF
        // Note: No nonce needed for QR code downloads - order_key is sufficient
        $pdf_url = add_query_arg(
            array(
                'mt_print_ticket' => 1,
                'mt_pdf' => 1,
                'order_id' => $order_id,
                'order_key' => $order->get_order_key(),
            ),
            home_url()
        );

        // For PDF generation, we'll use browser's print to PDF functionality
        // In a production environment, you'd want to use a proper PDF library
        wp_redirect($pdf_url);
        exit;
    }
}
