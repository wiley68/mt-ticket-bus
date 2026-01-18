<?php

/**
 * Reservations management class
 *
 * Handles CRUD operations for bus ticket reservations
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Reservations class
 */
class MT_Ticket_Bus_Reservations
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Reservations
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Reservations
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
        // Hook into WooCommerce order completion
        add_action('woocommerce_checkout_order_processed', array($this, 'create_reservations_from_order'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'update_reservations_status'), 10, 3);
    }

    /**
     * Get all reservations
     *
     * @param array $args Query arguments
     * @return array
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
     * Get reservation by ID
     *
     * @param int $id Reservation ID
     * @return object|null
     */
    public function get_reservation($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_reservations_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get reservations for an order
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function get_order_reservations($order_id)
    {
        return $this->get_all_reservations(array('order_id' => $order_id));
    }

    /**
     * Check if seat is available
     *
     * @param int    $schedule_id   Schedule ID
     * @param string $departure_date Departure date (Y-m-d)
     * @param string $departure_time Departure time (H:i:s)
     * @param string $seat_number    Seat number
     * @param int    $exclude_order_id Optional order ID to exclude from check
     * @return bool
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
     * Get available seats for a schedule and date/time
     *
     * @param int    $schedule_id   Schedule ID
     * @param string $departure_date Departure date (Y-m-d)
     * @param string $departure_time Departure time (H:i:s)
     * @param int    $bus_id        Bus ID
     * @return array Array of available seat numbers
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
     * Create reservation
     *
     * @param array $data Reservation data
     * @return int|WP_Error Reservation ID on success, WP_Error on failure
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

        // Check if seat is available
        if (!$this->is_seat_available($data['schedule_id'], $data['departure_date'], $data['departure_time'], $data['seat_number'])) {
            return new WP_Error('seat_not_available', __('Seat is not available.', 'mt-ticket-bus'));
        }

        // Sanitize data
        $sanitized_data = array(
            'order_id' => absint($data['order_id']),
            'order_item_id' => absint($data['order_item_id']),
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

        $result = $wpdb->insert($table, $sanitized_data);

        if ($result === false) {
            return new WP_Error('insert_failed', __('Failed to create reservation.', 'mt-ticket-bus'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update reservation status
     *
     * @param int    $id     Reservation ID
     * @param string $status New status
     * @return bool
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
     * Create reservations from WooCommerce order
     *
     * @param int $order_id Order ID
     */
    public function create_reservations_from_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            // Check if this is a bus ticket product
            $route_id = get_post_meta($product_id, '_mt_bus_route_id', true);
            $bus_id = get_post_meta($product_id, '_mt_bus_id', true);

            if (empty($route_id) || empty($bus_id)) {
                continue;
            }

            // Get schedule, seat, date, time from order item meta
            // This will be populated from the frontend when customer selects
            $schedule_id = wc_get_order_item_meta($item_id, '_mt_schedule_id', true);
            $seat_number = wc_get_order_item_meta($item_id, '_mt_seat_number', true);
            $departure_date = wc_get_order_item_meta($item_id, '_mt_departure_date', true);
            $departure_time = wc_get_order_item_meta($item_id, '_mt_departure_time', true);

            if (empty($schedule_id) || empty($seat_number) || empty($departure_date) || empty($departure_time)) {
                continue;
            }

            // Get passenger info
            $passenger_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $passenger_email = $order->get_billing_email();
            $passenger_phone = $order->get_billing_phone();

            // Create reservation
            $this->create_reservation(array(
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
                'status' => 'reserved',
            ));
        }
    }

    /**
     * Update reservations status when order status changes
     *
     * @param int    $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    public function update_reservations_status($order_id, $old_status, $new_status)
    {
        $reservations = $this->get_order_reservations($order_id);

        foreach ($reservations as $reservation) {
            $new_reservation_status = 'reserved';

            // Map WooCommerce order status to reservation status
            if (in_array($new_status, array('completed', 'processing'), true)) {
                $new_reservation_status = 'confirmed';
            } elseif (in_array($new_status, array('cancelled', 'refunded', 'failed'), true)) {
                $new_reservation_status = 'cancelled';
            }

            $this->update_reservation_status($reservation->id, $new_reservation_status);
        }
    }
}
