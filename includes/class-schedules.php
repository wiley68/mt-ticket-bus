<?php

/**
 * Schedules management class
 *
 * Handles CRUD operations for bus route schedules
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Schedules class
 */
class MT_Ticket_Bus_Schedules
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Schedules
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Schedules
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
        add_action('wp_ajax_mt_save_schedule', array($this, 'ajax_save_schedule'));
        add_action('wp_ajax_mt_delete_schedule', array($this, 'ajax_delete_schedule'));
    }

    /**
     * Get all schedules
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all_schedules($args = array())
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        $defaults = array(
            'route_id' => 0,
            'bus_id' => 0,
            'status' => 'active',
            'orderby' => 'departure_time',
            'order' => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);
        $where = array("status = '" . esc_sql($args['status']) . "'");

        if ($args['route_id'] > 0) {
            $where[] = "route_id = " . absint($args['route_id']);
        }

        if ($args['bus_id'] > 0) {
            $where[] = "bus_id = " . absint($args['bus_id']);
        }

        $where_clause = "WHERE " . implode(' AND ', $where);
        $orderby = "ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        $results = $wpdb->get_results("SELECT * FROM $table $where_clause $orderby");

        return $results;
    }

    /**
     * Get schedule by ID
     *
     * @param int $id Schedule ID
     * @return object|null
     */
    public function get_schedule($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Save schedule (create or update)
     *
     * @param array $data Schedule data
     * @return int|WP_Error Schedule ID on success, WP_Error on failure
     */
    public function save_schedule($data)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        // Store ID before processing
        $schedule_id = isset($data['id']) && $data['id'] > 0 ? absint($data['id']) : 0;

        $defaults = array(
            'route_id' => 0,
            'bus_id' => 0,
            'departure_time' => '',
            'arrival_time' => '',
            'frequency_type' => 'single',
            'days_of_week' => '',
            'price' => 0,
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['route_id'])) {
            return new WP_Error('missing_route', __('Route is required.', 'mt-ticket-bus'));
        }

        if (empty($data['bus_id'])) {
            return new WP_Error('missing_bus', __('Bus is required.', 'mt-ticket-bus'));
        }

        if (empty($data['departure_time'])) {
            return new WP_Error('missing_departure_time', __('Departure time is required.', 'mt-ticket-bus'));
        }

        // Process days_of_week
        $days_of_week = '';
        if (!empty($data['days_of_week'])) {
            if (is_array($data['days_of_week'])) {
                $days_of_week = wp_json_encode($data['days_of_week']);
            } else {
                $days_of_week = sanitize_text_field($data['days_of_week']);
            }
        }

        // Sanitize data
        $sanitized_data = array(
            'route_id' => absint($data['route_id']),
            'bus_id' => absint($data['bus_id']),
            'departure_time' => sanitize_text_field($data['departure_time']),
            'arrival_time' => !empty($data['arrival_time']) ? sanitize_text_field($data['arrival_time']) : null,
            'frequency_type' => sanitize_text_field($data['frequency_type']),
            'days_of_week' => $days_of_week,
            'price' => floatval($data['price']),
            'status' => sanitize_text_field($data['status']),
        );

        if ($schedule_id > 0) {
            // Update
            $result = $wpdb->update($table, $sanitized_data, array('id' => $schedule_id));

            if ($result === false) {
                return new WP_Error('update_failed', __('Failed to update schedule.', 'mt-ticket-bus'));
            }

            return $schedule_id;
        } else {
            // Insert
            $result = $wpdb->insert($table, $sanitized_data);

            if ($result === false) {
                return new WP_Error('insert_failed', __('Failed to create schedule.', 'mt-ticket-bus'));
            }

            return $wpdb->insert_id;
        }
    }

    /**
     * Delete schedule
     *
     * @param int $id Schedule ID
     * @return bool
     */
    public function delete_schedule($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        return $wpdb->delete($table, array('id' => absint($id)), array('%d')) !== false;
    }

    /**
     * AJAX handler for saving schedule
     */
    public function ajax_save_schedule()
    {
        global $wpdb;
        $wpdb->suppress_errors(true);
        $wpdb->hide_errors();

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
            unset($data['id']);
        }

        $result = $this->save_schedule($data);

        $wpdb->suppress_errors(false);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            $message = $is_update
                ? __('Schedule updated successfully.', 'mt-ticket-bus')
                : __('Schedule created successfully.', 'mt-ticket-bus');
            wp_send_json_success(array('id' => $result, 'message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to save schedule.', 'mt-ticket-bus')));
        }
    }

    /**
     * AJAX handler for deleting schedule
     */
    public function ajax_delete_schedule()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if ($this->delete_schedule($id)) {
            wp_send_json_success(array('message' => __('Schedule deleted successfully.', 'mt-ticket-bus')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete schedule.', 'mt-ticket-bus')));
        }
    }

    /**
     * Parse days of week from stored format
     *
     * @param string $days_of_week Stored days of week value
     * @return array|string
     */
    public function parse_days_of_week($days_of_week)
    {
        if (empty($days_of_week)) {
            return array();
        }

        // Check if it's a special value
        if (in_array($days_of_week, array('all', 'weekdays', 'weekend'), true)) {
            return $days_of_week;
        }

        // Try to decode as JSON
        $decoded = json_decode($days_of_week, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Return as is if it's a single day
        return $days_of_week;
    }
}
