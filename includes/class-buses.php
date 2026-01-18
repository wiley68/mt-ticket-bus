<?php

/**
 * Buses management class
 *
 * Handles CRUD operations for buses
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Buses class
 */
class MT_Ticket_Bus_Buses
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Buses
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Buses
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
        // AJAX handlers will be added here
        add_action('wp_ajax_mt_save_bus', array($this, 'ajax_save_bus'));
        add_action('wp_ajax_mt_delete_bus', array($this, 'ajax_delete_bus'));
    }

    /**
     * Get all buses
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all_buses($args = array())
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_buses_table();

        $defaults = array(
            'status' => 'active',
            'orderby' => 'id',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = "WHERE status = '" . esc_sql($args['status']) . "'";
        $orderby = "ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        $results = $wpdb->get_results("SELECT * FROM $table $where $orderby");

        return $results;
    }

    /**
     * Get bus by ID
     *
     * @param int $id Bus ID
     * @return object|null
     */
    public function get_bus($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_buses_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Save bus (create or update)
     *
     * @param array $data Bus data
     * @return int|false Bus ID on success, false on failure
     */
    public function save_bus($data)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_buses_table();

        $defaults = array(
            'name' => '',
            'registration_number' => '',
            'total_seats' => 0,
            'seat_layout' => '',
            'features' => '',
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        // Sanitize data
        $data = array(
            'name' => sanitize_text_field($data['name']),
            'registration_number' => sanitize_text_field($data['registration_number']),
            'total_seats' => absint($data['total_seats']),
            'seat_layout' => sanitize_textarea_field($data['seat_layout']),
            'features' => sanitize_textarea_field($data['features']),
            'status' => sanitize_text_field($data['status']),
        );

        if (isset($data['id']) && $data['id'] > 0) {
            // Update
            $id = absint($data['id']);
            unset($data['id']);

            $result = $wpdb->update($table, $data, array('id' => $id));

            return $result !== false ? $id : false;
        } else {
            // Insert
            $result = $wpdb->insert($table, $data);

            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete bus
     *
     * @param int $id Bus ID
     * @return bool
     */
    public function delete_bus($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_buses_table();

        return $wpdb->delete($table, array('id' => absint($id)), array('%d')) !== false;
    }

    /**
     * AJAX handler for saving bus
     */
    public function ajax_save_bus()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $data = $_POST;
        $result = $this->save_bus($data);

        if ($result) {
            wp_send_json_success(array('id' => $result, 'message' => __('Bus saved successfully.', 'mt-ticket-bus')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save bus.', 'mt-ticket-bus')));
        }
    }

    /**
     * AJAX handler for deleting bus
     */
    public function ajax_delete_bus()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if ($this->delete_bus($id)) {
            wp_send_json_success(array('message' => __('Bus deleted successfully.', 'mt-ticket-bus')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete bus.', 'mt-ticket-bus')));
        }
    }
}
