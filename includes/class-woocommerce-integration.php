<?php

/**
 * WooCommerce integration class
 *
 * Handles integration with WooCommerce for bus ticket products
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration class
 */
class MT_Ticket_Bus_WooCommerce_Integration
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_WooCommerce_Integration
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_WooCommerce_Integration
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
        
        // AJAX handlers for adding tickets to cart
        add_action('wp_ajax_mt_add_tickets_to_cart', array($this, 'ajax_add_tickets_to_cart'));
        add_action('wp_ajax_nopriv_mt_add_tickets_to_cart', array($this, 'ajax_add_tickets_to_cart'));
        
        // Save ticket meta data to cart items
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_ticket_cart_item_data'), 10, 3);
        
        // Display ticket meta in cart and checkout
        add_filter('woocommerce_get_item_data', array($this, 'display_ticket_cart_item_data'), 10, 2);
        
        // Save ticket meta to order items
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_ticket_order_item_meta'), 10, 4);

        // Enqueue admin scripts for WooCommerce product edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

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
     * Display ticket seatmap (for standard themes)
     */
    public function display_ticket_seatmap()
    {
        echo MT_Ticket_Bus_Renderer::render_seatmap();
    }

    /**
     * Display ticket summary (for standard themes)
     */
    public function display_ticket_summary()
    {
        echo MT_Ticket_Bus_Renderer::render_ticket_summary();
    }

    /**
     * AJAX handler: Get available dates for a schedule
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
     * AJAX handler: Get available seats for a specific date/time
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
     * AJAX handler: Get course availability for a specific date
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
     * AJAX handler: Add tickets to cart
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
     * Save ticket meta to order item
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
     * Add product meta fields for bus tickets
     */
    public function add_product_meta_fields()
    {
        global $post;

        $route_id = get_post_meta($post->ID, '_mt_bus_route_id', true);
        $bus_id = get_post_meta($post->ID, '_mt_bus_id', true);
        $schedule_id = get_post_meta($post->ID, '_mt_bus_schedule_id', true);

        echo '<div class="options_group mt-bus-ticket-options">';

        // Checkbox to mark product as bus ticket
        $is_ticket_product = get_post_meta($post->ID, '_mt_is_ticket_product', true);
        woocommerce_wp_checkbox(array(
            'id'          => '_mt_is_ticket_product',
            'label'       => __('Is Bus Ticket Product', 'mt-ticket-bus'),
            'description' => __('Check this box if this is a bus ticket product.', 'mt-ticket-bus'),
            'value'       => $is_ticket_product ? 'yes' : 'no',
        ));

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
     * Save product meta fields
     *
     * @param int $post_id Post ID
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
     * Set virtual product by default
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
     * Get routes options for select field
     *
     * @return array
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
     * Get buses options for select field
     *
     * @return array
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
     * Get schedules options for select field
     *
     * @param int $route_id Optional route ID to filter schedules
     * @return array
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
     * AJAX handler for getting schedules by route
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
     * Enqueue admin scripts for WooCommerce product edit page
     *
     * @param string $hook Current admin page hook
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

}
