<?php

/**
 * Routes management class
 *
 * Handles CRUD operations for bus routes
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Routes class
 */
class MT_Ticket_Bus_Routes
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Routes
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Routes
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
        add_action('wp_ajax_mt_save_route', array($this, 'ajax_save_route'));
        add_action('wp_ajax_mt_delete_route', array($this, 'ajax_delete_route'));
    }

    /**
     * Get all routes
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all_routes($args = array())
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_routes_table();

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
     * Get route by ID
     *
     * @param int $id Route ID
     * @return object|null
     */
    public function get_route($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_routes_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Save route (create or update)
     *
     * @param array $data Route data
     * @return int|false Route ID on success, false on failure
     */
    public function save_route($data)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_routes_table();

        // Store ID before processing (in case it gets lost)
        $route_id = isset($data['id']) && $data['id'] > 0 ? absint($data['id']) : 0;

        $defaults = array(
            'name' => '',
            'start_station' => '',
            'end_station' => '',
            'intermediate_stations' => '',
            'distance' => 0,
            'duration' => 0,
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        // Sanitize data
        $data = array(
            'name' => sanitize_text_field($data['name']),
            'start_station' => sanitize_text_field($data['start_station']),
            'end_station' => sanitize_text_field($data['end_station']),
            'intermediate_stations' => sanitize_textarea_field($data['intermediate_stations']),
            'distance' => floatval($data['distance']),
            'duration' => absint($data['duration']),
            'status' => sanitize_text_field($data['status']),
        );

        if ($route_id > 0) {
            // Update
            $result = $wpdb->update($table, $data, array('id' => $route_id));

            return $result !== false ? $route_id : false;
        } else {
            // Insert
            $result = $wpdb->insert($table, $data);

            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete route
     *
     * @param int $id Route ID
     * @return bool
     */
    public function delete_route($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_routes_table();

        return $wpdb->delete($table, array('id' => absint($id)), array('%d')) !== false;
    }

    /**
     * AJAX handler for saving route
     */
    public function ajax_save_route()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $data = $_POST;

        // Ensure ID is passed correctly for updates
        $is_update = isset($data['id']) && !empty($data['id']);
        if ($is_update) {
            $data['id'] = absint($data['id']);
        } else {
            // Remove id if it's empty or 0 to ensure new route is created
            unset($data['id']);
        }

        $result = $this->save_route($data);

        if ($result) {
            $message = $is_update
                ? __('Route updated successfully.', 'mt-ticket-bus')
                : __('Route created successfully.', 'mt-ticket-bus');
            wp_send_json_success(array('id' => $result, 'message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to save route.', 'mt-ticket-bus')));
        }
    }

    /**
     * AJAX handler for deleting route
     */
    public function ajax_delete_route()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if ($this->delete_route($id)) {
            wp_send_json_success(array('message' => __('Route deleted successfully.', 'mt-ticket-bus')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete route.', 'mt-ticket-bus')));
        }
    }
}
