<?php

/**
 * Reservations management class
 *
 * Handles CRUD operations for bus ticket reservations
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Reservations class.
 *
 * Handles CRUD operations for bus ticket reservations including creation,
 * updating, deletion, and querying reservations by various criteria.
 * Also manages reservation status synchronization with WooCommerce orders.
 *
 * @since 1.0.0
 */
class MT_Ticket_Bus_Reservations
{

    /**
     * Plugin instance.
     *
     * @since 1.0.0
     *
     * @var MT_Ticket_Bus_Reservations
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @since 1.0.0
     *
     * @return MT_Ticket_Bus_Reservations Plugin instance.
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
     * Initializes WooCommerce hooks for order creation and status changes
     * to automatically create and update reservations.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        // Hook into WooCommerce order creation and status changes
        // Use multiple hooks to ensure reservations are created regardless of payment method
        add_action('woocommerce_new_order', array($this, 'create_reservations_from_order'), 20, 1);
        add_action('woocommerce_checkout_order_processed', array($this, 'create_reservations_from_order'), 20, 1);
        add_action('woocommerce_order_status_on-hold', array($this, 'create_reservations_from_order'), 10, 1);
        add_action('woocommerce_order_status_pending', array($this, 'create_reservations_from_order'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'create_reservations_from_order'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'create_reservations_from_order'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'update_reservations_status'), 10, 3);
        add_action('woocommerce_before_delete_order', array($this, 'delete_reservations_on_order_delete'), 10, 2);
    }

    /**
     * Get all reservations.
     *
     * @since 1.0.0
     *
     * @param array $args Query arguments. {
     *     Optional. Array of query parameters.
     *
     *     @var int    $order_id      Order ID to filter by. Default 0 (all orders).
     *     @var int    $product_id     Product ID to filter by. Default 0 (all products).
     *     @var int    $schedule_id    Schedule ID to filter by. Default 0 (all schedules).
     *     @var int    $bus_id         Bus ID to filter by. Default 0 (all buses).
     *     @var int    $route_id       Route ID to filter by. Default 0 (all routes).
     *     @var string $departure_date Departure date (Y-m-d) to filter by. Default '' (all dates).
     *     @var string $status         Reservation status to filter by. Default '' (all statuses).
     *     @var string $orderby        Field to order by. Default 'departure_date'.
     *     @var string $order          Order direction ('ASC' or 'DESC'). Default 'DESC'.
     * }
     * @return array Array of reservation objects.
     */
    public function get_all_reservations($args = array())
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();

        $defaults = array(
            'order_id' => 0,
            'product_id' => 0,
            'schedule_id' => 0,
            'bus_id' => 0,
            'route_id' => 0,
            'departure_date' => '',
            'status' => '',
            'orderby' => 'departure_date',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');

        if ($args['order_id'] > 0) {
            $where[] = "order_id = " . absint($args['order_id']);
        }

        if ($args['product_id'] > 0) {
            $where[] = "product_id = " . absint($args['product_id']);
        }

        if ($args['schedule_id'] > 0) {
            $where[] = "schedule_id = " . absint($args['schedule_id']);
        }

        if ($args['bus_id'] > 0) {
            $where[] = "bus_id = " . absint($args['bus_id']);
        }

        if ($args['route_id'] > 0) {
            $where[] = "route_id = " . absint($args['route_id']);
        }

        if (!empty($args['departure_date'])) {
            $where[] = "departure_date = '" . esc_sql($args['departure_date']) . "'";
        }

        if (!empty($args['status'])) {
            $where[] = "status = '" . esc_sql($args['status']) . "'";
        }

        $where_clause = "WHERE " . implode(' AND ', $where);
        $orderby = "ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        $results = $wpdb->get_results("SELECT * FROM $table $where_clause $orderby");

        return $results;
    }

    /**
     * Get reservation by ID.
     *
     * @since 1.0.0
     *
     * @param int $id Reservation ID.
     * @return object|null Reservation object or null if not found.
     */
    public function get_reservation($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get reservations for an order.
     *
     * @since 1.0.0
     *
     * @param int $order_id Order ID.
     * @return array Array of reservation objects for the specified order.
     */
    public function get_order_reservations($order_id)
    {
        return $this->get_all_reservations(array('order_id' => $order_id));
    }

    /**
     * Check if seat is available.
     *
     * @since 1.0.0
     *
     * @param int    $schedule_id     Schedule ID.
     * @param string $departure_date  Departure date (Y-m-d format).
     * @param string $departure_time  Departure time (H:i:s format).
     * @param string $seat_number     Seat number.
     * @param int    $exclude_order_id Optional. Order ID to exclude from availability check. Default 0.
     * @return bool True if seat is available, false otherwise.
     */
    public function is_seat_available($schedule_id, $departure_date, $departure_time, $seat_number, $exclude_order_id = 0)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();

        $where = $wpdb->prepare(
            "schedule_id = %d AND departure_date = %s AND departure_time = %s AND seat_number = %s AND status IN ('reserved', 'confirmed')",
            $schedule_id,
            $departure_date,
            $departure_time,
            $seat_number
        );

        if ($exclude_order_id > 0) {
            $where .= $wpdb->prepare(" AND order_id != %d", $exclude_order_id);
        }

        $reserved = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");

        return $reserved == 0;
    }

    /**
     * Get available seats for a schedule and date/time.
     *
     * @since 1.0.0
     *
     * @param int    $schedule_id   Schedule ID.
     * @param string $departure_date Departure date (Y-m-d format).
     * @param string $departure_time Departure time (H:i:s format).
     * @param int    $bus_id        Bus ID.
     * @return array Array of available seat numbers.
     */
    public function get_available_seats($schedule_id, $departure_date, $departure_time, $bus_id)
    {
        // Get bus seat layout
        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
        if (!$bus || empty($bus->seat_layout)) {
            return array();
        }

        $layout = json_decode($bus->seat_layout, true);
        if (!isset($layout['seats']) || !is_array($layout['seats'])) {
            return array();
        }

        // Get all available seats from layout
        $available_seats = array();
        foreach ($layout['seats'] as $seat_id => $is_available) {
            if ($is_available === true) {
                $available_seats[] = $seat_id;
            }
        }

        // Get reserved seats
        global $wpdb;
        $table = MT_Ticket_Bus_Database::get_reservations_table();
        $reserved_seats = $wpdb->get_col($wpdb->prepare(
            "SELECT seat_number FROM $table WHERE schedule_id = %d AND departure_date = %s AND departure_time = %s AND status IN ('reserved', 'confirmed')",
            $schedule_id,
            $departure_date,
            $departure_time
        ));

        // Remove reserved seats
        $available_seats = array_diff($available_seats, $reserved_seats);

        return array_values($available_seats);
    }

    /**
     * Create reservation.
     *
     * @since 1.0.0
     *
     * @param array $data Reservation data. {
     *     Array of reservation parameters.
     *
     *     @var int    $order_id       Order ID. Required.
     *     @var int    $order_item_id  Order item ID. Optional.
     *     @var int    $product_id     Product ID. Optional.
     *     @var int    $schedule_id    Schedule ID. Required.
     *     @var int    $bus_id         Bus ID. Optional.
     *     @var int    $route_id       Route ID. Optional.
     *     @var string $seat_number    Seat number. Required.
     *     @var string $departure_date Departure date (Y-m-d format). Required.
     *     @var string $departure_time Departure time (H:i:s format). Required.
     *     @var string $passenger_name Passenger name. Optional.
     *     @var string $passenger_email Passenger email. Optional.
     *     @var string $passenger_phone Passenger phone. Optional.
     *     @var string $status         Reservation status ('reserved', 'confirmed', 'cancelled'). Default 'reserved'.
     * }
     * @return int|WP_Error Reservation ID on success, WP_Error on failure.
     */
    public function create_reservation($data)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();

        $defaults = array(
            'order_id' => 0,
            'order_item_id' => 0,
            'product_id' => 0,
            'schedule_id' => 0,
            'bus_id' => 0,
            'route_id' => 0,
            'seat_number' => '',
            'departure_date' => '',
            'departure_time' => '',
            'passenger_name' => '',
            'passenger_email' => '',
            'passenger_phone' => '',
            'status' => 'reserved',
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['order_id'])) {
            return new WP_Error('missing_order', __('Order ID is required.', 'mt-ticket-bus'));
        }

        if (empty($data['schedule_id'])) {
            return new WP_Error('missing_schedule', __('Schedule ID is required.', 'mt-ticket-bus'));
        }

        if (empty($data['seat_number'])) {
            return new WP_Error('missing_seat', __('Seat number is required.', 'mt-ticket-bus'));
        }

        if (empty($data['departure_date'])) {
            return new WP_Error('missing_date', __('Departure date is required.', 'mt-ticket-bus'));
        }

        if (empty($data['departure_time'])) {
            return new WP_Error('missing_time', __('Departure time is required.', 'mt-ticket-bus'));
        }

        // Check if seat is available (exclude current order if updating existing reservation)
        $exclude_order_id = isset($data['order_id']) ? absint($data['order_id']) : 0;
        if (!$this->is_seat_available($data['schedule_id'], $data['departure_date'], $data['departure_time'], $data['seat_number'], $exclude_order_id)) {
            return new WP_Error('seat_not_available', __('Seat is not available.', 'mt-ticket-bus'));
        }

        // Check if a reservation already exists for this seat/date/time (could be cancelled)
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $table WHERE schedule_id = %d AND departure_date = %s AND departure_time = %s AND seat_number = %s",
            $data['schedule_id'],
            $data['departure_date'],
            $data['departure_time'],
            $data['seat_number']
        ));

        // Sanitize data
        $sanitized_data = array(
            'order_id' => absint($data['order_id']),
            'order_item_id' => isset($data['order_item_id']) ? absint($data['order_item_id']) : null,
            'product_id' => absint($data['product_id']),
            'schedule_id' => absint($data['schedule_id']),
            'bus_id' => absint($data['bus_id']),
            'route_id' => absint($data['route_id']),
            'seat_number' => sanitize_text_field($data['seat_number']),
            'departure_date' => sanitize_text_field($data['departure_date']),
            'departure_time' => sanitize_text_field($data['departure_time']),
            'passenger_name' => sanitize_text_field($data['passenger_name']),
            'passenger_email' => sanitize_email($data['passenger_email']),
            'passenger_phone' => sanitize_text_field($data['passenger_phone']),
            'status' => sanitize_text_field($data['status']),
        );

        if ($existing) {
            // Update existing reservation (e.g., if it was cancelled and now being rebooked)
            $result = $wpdb->update(
                $table,
                $sanitized_data,
                array('id' => $existing->id),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );

            if ($result === false) {
                return new WP_Error('update_failed', __('Failed to update reservation.', 'mt-ticket-bus'));
            }

            return $existing->id;
        } else {
            // Insert new reservation
            $result = $wpdb->insert($table, $sanitized_data);

            if ($result === false) {
                return new WP_Error('insert_failed', __('Failed to create reservation.', 'mt-ticket-bus'));
            }

            return $wpdb->insert_id;
        }
    }

    /**
     * Update reservation status.
     *
     * @since 1.0.0
     *
     * @param int    $id     Reservation ID.
     * @param string $status New status ('reserved', 'confirmed', 'cancelled').
     * @return bool True on success, false on failure.
     */
    public function update_reservation_status($id, $status)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();

        return $wpdb->update(
            $table,
            array('status' => sanitize_text_field($status)),
            array('id' => absint($id)),
            array('%s'),
            array('%d')
        ) !== false;
    }

    /**
     * Delete all reservations for an order (e.g. when order is permanently deleted).
     *
     * @since 1.0.0
     *
     * @param int $order_id Order ID.
     * @return int Number of deleted rows.
     */
    public function delete_reservations_by_order_id($order_id)
    {
        global $wpdb;

        $order_id = absint($order_id);
        if ($order_id <= 0) {
            return 0;
        }

        $table = MT_Ticket_Bus_Database::get_reservations_table();
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE order_id = %d", $order_id));

        return $deleted !== false ? $deleted : 0;
    }

    /**
     * WooCommerce hook: delete reservations when an order is permanently deleted.
     *
     * @param int      $order_id Order ID.
     * @param WC_Order $order    Order object (optional).
     * @return void
     */
    public function delete_reservations_on_order_delete($order_id, $order = null)
    {
        $this->delete_reservations_by_order_id($order_id);
    }

    /**
     * Create reservations from WooCommerce order.
     *
     * Automatically creates reservations for all ticket products in a WooCommerce order.
     * This method is called via WordPress hooks when orders are created or status changes.
     *
     * @since 1.0.0
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function create_reservations_from_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log(sprintf('MT Ticket Bus: Order %d not found', $order_id));
            return;
        }

        // Check if reservations already exist for this order to prevent duplicates
        $existing_reservations = $this->get_order_reservations($order_id);
        if (!empty($existing_reservations)) {
            // Reservations already exist for this order, skip creation
            error_log(sprintf('MT Ticket Bus: Reservations already exist for order %d, skipping creation', $order_id));
            return;
        }

        $processed_items = 0;
        $skipped_items = 0;

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);

            if (!$product) {
                error_log(sprintf('MT Ticket Bus: Product %d not found for order %d, item %d', $product_id, $order_id, $item_id));
                $skipped_items++;
                continue;
            }

            // Check if this is a bus ticket product
            $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
            if ($is_ticket_product !== 'yes') {
                // Not a ticket product, skip silently
                $skipped_items++;
                continue;
            }

            // Get schedule, seat, date, time, bus_id, route_id from order item meta
            // These are saved during checkout in save_ticket_order_item_meta
            $schedule_id = wc_get_order_item_meta($item_id, '_mt_schedule_id', true);
            $bus_id = wc_get_order_item_meta($item_id, '_mt_bus_id', true);
            $route_id = wc_get_order_item_meta($item_id, '_mt_route_id', true);
            $seat_number = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
            $departure_date = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
            $departure_time = wc_get_order_item_meta($item_id, '_mt_departure_time', true);

            // Validate all required fields
            if (
                empty($schedule_id) || empty($bus_id) || empty($route_id) ||
                empty($seat_number) || empty($departure_date) || empty($departure_time)
            ) {
                // Log missing data for debugging
                error_log(sprintf(
                    'MT Ticket Bus: Missing reservation data for order %d, item %d, product %d. Schedule: %s, Bus: %s, Route: %s, Seat: %s, Date: %s, Time: %s',
                    $order_id,
                    $item_id,
                    $product_id,
                    $schedule_id ?: 'missing',
                    $bus_id ?: 'missing',
                    $route_id ?: 'missing',
                    $seat_number ?: 'missing',
                    $departure_date ?: 'missing',
                    $departure_time ?: 'missing'
                ));
                $skipped_items++;
                continue;
            }

            // Get passenger info from order
            $passenger_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            $passenger_email = $order->get_billing_email();
            $passenger_phone = $order->get_billing_phone();

            // Initial reservation status from order status (same logic as update_reservations_status)
            $order_status = $order->get_status();
            $reservation_status = 'reserved';
            if (in_array($order_status, array('cancelled', 'refunded', 'failed'), true)) {
                $reservation_status = 'cancelled';
            } elseif ($order_status === 'completed') {
                $reservation_status = 'confirmed';
            } elseif ($order_status === 'processing') {
                $payment_method = $order->get_payment_method();
                $reservation_status = ($payment_method === 'cod') ? 'reserved' : 'confirmed';
            }

            // Create reservation
            $result = $this->create_reservation(array(
                'order_id' => $order_id,
                'order_item_id' => $item_id,
                'product_id' => $product_id,
                'schedule_id' => $schedule_id,
                'bus_id' => $bus_id,
                'route_id' => $route_id,
                'seat_number' => $seat_number,
                'departure_date' => $departure_date,
                'departure_time' => $departure_time,
                'passenger_name' => $passenger_name,
                'passenger_email' => $passenger_email,
                'passenger_phone' => $passenger_phone,
                'status' => $reservation_status,
            ));

            if (is_wp_error($result)) {
                error_log(sprintf(
                    'MT Ticket Bus: Failed to create reservation for order %d, item %d: %s',
                    $order_id,
                    $item_id,
                    $result->get_error_message()
                ));
                $skipped_items++;
            } else {
                $processed_items++;
            }
        }
    }

    /**
     * Update reservations status when order status changes.
     *
     * Maps WooCommerce order statuses to reservation statuses:
     * - 'completed' -> 'confirmed' (payment received)
     * - 'processing' -> 'confirmed' except when payment method is COD (cash on delivery), then 'reserved'
     * - 'cancelled', 'refunded', 'failed' -> 'cancelled'
     * - Other statuses -> 'reserved'
     *
     * @since 1.0.0
     *
     * @param int    $order_id   Order ID.
     * @param string $old_status Old order status.
     * @param string $new_status New order status.
     * @return void
     */
    public function update_reservations_status($order_id, $old_status, $new_status)
    {
        $reservations = $this->get_order_reservations($order_id);

        $new_reservation_status = 'reserved';
        if (in_array($new_status, array('cancelled', 'refunded', 'failed'), true)) {
            $new_reservation_status = 'cancelled';
        } elseif ($new_status === 'completed') {
            $new_reservation_status = 'confirmed';
        } elseif ($new_status === 'processing') {
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            $new_reservation_status = ($payment_method === 'cod') ? 'reserved' : 'confirmed';
        }

        foreach ($reservations as $reservation) {
            $this->update_reservation_status($reservation->id, $new_reservation_status);
        }
    }

    /**
     * Get reservations dashboard data for a range of N days.
     *
     * Returns per-day, per-course aggregated counts (schedule_id, route_id, route_name, departure_time, count)
     * for reserved/confirmed reservations. Used by the admin reservations dashboard.
     *
     * @since 1.0.6
     *
     * @param int $days              Number of days to include. Default 30.
     * @param int $start_offset_days Offset from today for the first day (e.g. -15 for 15 days before today). Default 0.
     * @return array Associative array keyed by date (Y-m-d), values are arrays of course rows with keys:
     *               schedule_id, route_id, route_name, departure_time (H:i), departure_time_display, count.
     */
    public function get_reservations_dashboard_data($days = 30, $start_offset_days = 0)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();
        $start_date = date('Y-m-d', strtotime("{$start_offset_days} days", current_time('timestamp')));
        $end_date = date('Y-m-d', strtotime(($start_offset_days + $days - 1) . ' days', current_time('timestamp')));

        $query = $wpdb->prepare(
            "SELECT departure_date, schedule_id, route_id, departure_time, COUNT(*) AS cnt
             FROM {$table}
             WHERE departure_date >= %s AND departure_date <= %s
             AND status IN ('reserved', 'confirmed')
             GROUP BY departure_date, schedule_id, route_id, departure_time
             ORDER BY departure_date ASC, departure_time ASC",
            $start_date,
            $end_date
        );

        $rows = $wpdb->get_results($query);
        if (!is_array($rows)) {
            $rows = array();
        }

        $routes = MT_Ticket_Bus_Routes::get_instance();
        $by_date = array();

        for ($i = 0; $i < $days; $i++) {
            $d = date('Y-m-d', strtotime(($start_offset_days + $i) . ' days', current_time('timestamp')));
            $by_date[$d] = array();
        }

        foreach ($rows as $row) {
            $route = $routes->get_route($row->route_id);
            $route_name = $route ? $route->name : sprintf(__('Route #%d', 'mt-ticket-bus'), $row->route_id);
            $time_value = date('H:i', strtotime($row->departure_time));
            $time_display = $time_value;

            $course = array(
                'schedule_id' => (int) $row->schedule_id,
                'route_id' => (int) $row->route_id,
                'route_name' => $route_name,
                'departure_time' => $time_value,
                'departure_time_display' => $time_display,
                'count' => (int) $row->cnt,
            );

            if (!isset($by_date[$row->departure_date])) {
                $by_date[$row->departure_date] = array();
            }
            $by_date[$row->departure_date][] = $course;
        }

        return $by_date;
    }

    /**
     * Cleanup old reservations.
     *
     * Deletes all reservations with departure_date older than one year from current date.
     * This helps keep the database clean by removing expired reservations while
     * preserving recent historical data for up to one year.
     *
     * @since 1.0.0
     *
     * @return int Number of deleted rows.
     */
    public function cleanup_old_reservations()
    {
        global $wpdb;

        $table_name = MT_Ticket_Bus_Database::get_reservations_table();
        $one_year_ago = date('Y-m-d', strtotime('-1 year', current_time('timestamp')));

        // Delete reservations where departure_date is older than one year
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE departure_date < %s",
                $one_year_ago
            )
        );

        return $deleted !== false ? $deleted : 0;
    }

    /**
     * Get reservation rows for XLSX export (same fields as "Reservation Information" panel).
     *
     * Returns one row per reserved/confirmed seat for the given date, schedule and departure time,
     * enriched with order data (order date, status, payment method, product name, notes).
     *
     * @since 1.0.0
     *
     * @param string $departure_date  Departure date (Y-m-d).
     * @param int    $schedule_id     Schedule ID.
     * @param string $departure_time  Departure time (H:i or H:i:s).
     * @return array Array of associative arrays with keys: order_id, order_date, product_name,
     *               order_status, order_status_name, payment_method, order_notes, seat_number,
     *               passenger_name, passenger_email, passenger_phone, departure_date, departure_time, status.
     */
    public function get_reservations_export_rows($departure_date, $schedule_id, $departure_time)
    {
        $reservations = $this->get_all_reservations(array(
            'schedule_id' => $schedule_id,
            'departure_date' => $departure_date,
            'status' => '',
        ));

        $departure_time_compare = date('H:i', strtotime($departure_time));
        $rows = array();

        foreach ($reservations as $reservation) {
            $reservation_time = date('H:i', strtotime($reservation->departure_time));
            if ($reservation_time !== $departure_time_compare) {
                continue;
            }
            if (!in_array($reservation->status, array('reserved', 'confirmed'), true)) {
                continue;
            }

            $row = array(
                'order_id' => $reservation->order_id,
                'order_date' => '',
                'product_name' => '',
                'order_status' => '',
                'order_status_name' => '',
                'payment_method' => '',
                'order_notes' => '',
                'seat_number' => $reservation->seat_number,
                'passenger_name' => $reservation->passenger_name,
                'passenger_email' => $reservation->passenger_email,
                'passenger_phone' => $reservation->passenger_phone,
                'departure_date' => $reservation->departure_date,
                'departure_time' => date('H:i', strtotime($reservation->departure_time)),
                'status' => $reservation->status,
            );

            if (!empty($reservation->order_id) && function_exists('wc_get_order')) {
                $order = wc_get_order($reservation->order_id);
                if ($order) {
                    $order_date_obj = @$order->get_date_created();
                    if ($order_date_obj && method_exists($order_date_obj, 'date')) {
                        $row['order_date'] = $order_date_obj->date('Y-m-d H:i:s');
                    }
                    if (empty($row['order_date'])) {
                        $order_post = get_post($reservation->order_id);
                        if ($order_post && isset($order_post->post_date)) {
                            $row['order_date'] = $order_post->post_date;
                        }
                    }
                    $ost = $order->get_status();
                    if ($ost) {
                        $row['order_status'] = $ost;
                        $name = function_exists('wc_get_order_status_name') ? wc_get_order_status_name($ost) : '';
                        $row['order_status_name'] = $name ?: ucfirst($ost);
                    }
                    $pm = $order->get_payment_method_title();
                    if ($pm) {
                        $row['payment_method'] = $pm;
                    }
                    if (!empty($reservation->order_item_id)) {
                        foreach ($order->get_items() as $item_id => $order_item) {
                            if ((int) $item_id === (int) $reservation->order_item_id && is_callable(array($order_item, 'get_name'))) {
                                $row['product_name'] = $order_item->get_name();
                                break;
                            }
                        }
                    }
                    $notes_text = array();
                    $customer_note = $order->get_customer_note();
                    if (!empty($customer_note)) {
                        $notes_text[] = trim($customer_note);
                    }
                    $order_notes = array();
                    if (function_exists('wc_get_order_notes')) {
                        $order_notes = @wc_get_order_notes(array('order_id' => $reservation->order_id, 'limit' => 50));
                    }
                    if (empty($order_notes) || !is_array($order_notes)) {
                        $order_notes = get_comments(array(
                            'post_id' => $reservation->order_id,
                            'status' => 'approve',
                            'type' => 'order_note',
                            'number' => 50,
                            'orderby' => 'comment_date',
                            'order' => 'DESC',
                        ));
                    }
                    if (!empty($order_notes) && is_array($order_notes)) {
                        foreach ($order_notes as $note) {
                            if (!is_object($note)) {
                                continue;
                            }
                            $note_content = '';
                            if (isset($note->comment_content) && trim($note->comment_content) !== '') {
                                $note_content = trim($note->comment_content);
                            } elseif (isset($note->content) && trim($note->content) !== '') {
                                $note_content = trim($note->content);
                            } elseif (method_exists($note, 'get_content')) {
                                $note_content = trim($note->get_content());
                            }
                            if ($note_content !== '') {
                                $notes_text[] = $note_content;
                            }
                        }
                    }
                    if (!empty($notes_text)) {
                        $row['order_notes'] = implode("\n", $notes_text);
                    }
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
