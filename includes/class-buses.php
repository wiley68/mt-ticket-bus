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
        add_action('wp_ajax_mt_check_registration_number', array($this, 'ajax_check_registration_number'));
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
     * Check if registration number already exists
     *
     * @param string $registration_number Registration number to check
     * @param int    $exclude_id          Bus ID to exclude from check (for updates)
     * @return bool True if exists, false otherwise
     */
    public function registration_number_exists($registration_number, $exclude_id = 0)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_buses_table();
        $registration_number = sanitize_text_field($registration_number);

        if (empty($registration_number)) {
            return false;
        }

        $query = $wpdb->prepare(
            "SELECT id FROM $table WHERE registration_number = %s",
            $registration_number
        );

        if ($exclude_id > 0) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }

        $existing = $wpdb->get_var($query);

        return !empty($existing);
    }

    /**
     * Save bus (create or update)
     *
     * @param array $data Bus data
     * @return int|WP_Error Bus ID on success, WP_Error on failure
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

        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Bus name is required.', 'mt-ticket-bus'));
        }

        if (empty($data['registration_number'])) {
            return new WP_Error('missing_registration', __('Registration number is required.', 'mt-ticket-bus'));
        }

        // Store ID before processing (in case it gets lost)
        $bus_id = isset($data['id']) && $data['id'] > 0 ? absint($data['id']) : 0;

        // Sanitize data
        $registration_number = sanitize_text_field($data['registration_number']);
        $name = sanitize_text_field($data['name']);

        // Check for duplicate registration number (only if registration number is being changed)
        if ($bus_id > 0) {
            // For updates, check if registration number is being changed
            $existing_bus = $this->get_bus($bus_id);
            if ($existing_bus && $existing_bus->registration_number === $registration_number) {
                // Registration number hasn't changed, no need to check for duplicates
            } else {
                // Registration number is being changed, check for duplicates
                if ($this->registration_number_exists($registration_number, $bus_id)) {
                    return new WP_Error('duplicate_registration', __('This registration number already exists.', 'mt-ticket-bus'));
                }
            }
        } else {
            // For new buses, always check for duplicates
            if ($this->registration_number_exists($registration_number, 0)) {
                return new WP_Error('duplicate_registration', __('This registration number already exists.', 'mt-ticket-bus'));
            }
        }

        // Store ID before processing (in case it gets lost)
        $bus_id = isset($data['id']) && $data['id'] > 0 ? absint($data['id']) : 0;

        // Process seat layout JSON
        $seat_layout = '';
        $left_seats = isset($data['left_column_seats']) ? absint($data['left_column_seats']) : 0;
        $right_seats = isset($data['right_column_seats']) ? absint($data['right_column_seats']) : 0;
        $rows = isset($data['number_of_rows']) ? absint($data['number_of_rows']) : 10;

        if (!empty($data['seat_layout'])) {
            // Clean and decode JSON
            $seat_layout_raw = is_string($data['seat_layout']) ? stripslashes($data['seat_layout']) : $data['seat_layout'];
            $layout_json = json_decode($seat_layout_raw, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($layout_json['seats']) && is_array($layout_json['seats'])) {
                // Update config if provided
                if (isset($layout_json['config'])) {
                    $layout_json['config'] = array(
                        'left' => $left_seats,
                        'right' => $right_seats,
                        'rows' => $rows
                    );
                } else {
                    $layout_json['config'] = array(
                        'left' => $left_seats,
                        'right' => $right_seats,
                        'rows' => $rows
                    );
                }

                // Ensure all seats are present in the layout (add missing ones as available)
                for ($row = 1; $row <= $rows; $row++) {
                    for ($col = 0; $col < $left_seats; $col++) {
                        $col_letter = chr(65 + $col);
                        $seat_id = $col_letter . $row;
                        // Only add if not exists, preserve existing values
                        if (!isset($layout_json['seats'][$seat_id])) {
                            $layout_json['seats'][$seat_id] = true;
                        }
                    }
                    for ($col = 0; $col < $right_seats; $col++) {
                        $col_letter = chr(65 + $left_seats + $col);
                        $seat_id = $col_letter . $row;
                        // Only add if not exists, preserve existing values
                        if (!isset($layout_json['seats'][$seat_id])) {
                            $layout_json['seats'][$seat_id] = true;
                        }
                    }
                }

                // Remove seats that are no longer in the layout
                $valid_seat_ids = array();
                for ($row = 1; $row <= $rows; $row++) {
                    for ($col = 0; $col < $left_seats; $col++) {
                        $col_letter = chr(65 + $col);
                        $valid_seat_ids[] = $col_letter . $row;
                    }
                    for ($col = 0; $col < $right_seats; $col++) {
                        $col_letter = chr(65 + $left_seats + $col);
                        $valid_seat_ids[] = $col_letter . $row;
                    }
                }
                $layout_json['seats'] = array_intersect_key($layout_json['seats'], array_flip($valid_seat_ids));

                $seat_layout = wp_json_encode($layout_json);
            } else {
                // If invalid JSON, create from configuration
                $layout_json = array(
                    'config' => array(
                        'left' => $left_seats,
                        'right' => $right_seats,
                        'rows' => $rows
                    ),
                    'seats' => array()
                );

                // Generate default seats (all available)
                for ($row = 1; $row <= $rows; $row++) {
                    for ($col = 0; $col < $left_seats; $col++) {
                        $col_letter = chr(65 + $col); // A, B, C...
                        $seat_id = $col_letter . $row;
                        $layout_json['seats'][$seat_id] = true;
                    }
                    for ($col = 0; $col < $right_seats; $col++) {
                        $col_letter = chr(65 + $left_seats + $col);
                        $seat_id = $col_letter . $row;
                        $layout_json['seats'][$seat_id] = true;
                    }
                }

                $seat_layout = wp_json_encode($layout_json);
            }
        } else {
            // No seat_layout provided, generate from configuration
            $layout_json = array(
                'config' => array(
                    'left' => $left_seats,
                    'right' => $right_seats,
                    'rows' => $rows
                ),
                'seats' => array()
            );

            // Generate default seats (all available)
            for ($row = 1; $row <= $rows; $row++) {
                for ($col = 0; $col < $left_seats; $col++) {
                    $col_letter = chr(65 + $col);
                    $seat_id = $col_letter . $row;
                    $layout_json['seats'][$seat_id] = true;
                }
                for ($col = 0; $col < $right_seats; $col++) {
                    $col_letter = chr(65 + $left_seats + $col);
                    $seat_id = $col_letter . $row;
                    $layout_json['seats'][$seat_id] = true;
                }
            }

            $seat_layout = wp_json_encode($layout_json);
        }

        // Calculate total_seats from seat_layout
        $total_seats = 0;
        if (!empty($seat_layout)) {
            $layout_data = json_decode($seat_layout, true);
            if (isset($layout_data['seats']) && is_array($layout_data['seats'])) {
                $available_seats = 0;
                foreach ($layout_data['seats'] as $seat_id => $is_available) {
                    // Check if seat is available (true or 1)
                    if ($is_available === true || $is_available === 1 || $is_available === '1') {
                        $available_seats++;
                    }
                }
                $total_seats = $available_seats;
            }
        }

        // Fallback to provided total_seats if calculation failed
        if ($total_seats === 0 && isset($data['total_seats']) && absint($data['total_seats']) > 0) {
            $total_seats = absint($data['total_seats']);
        }

        $data = array(
            'name' => $name,
            'registration_number' => $registration_number,
            'total_seats' => $total_seats,
            'seat_layout' => $seat_layout,
            'features' => sanitize_textarea_field($data['features']),
            'status' => sanitize_text_field($data['status']),
        );

        if ($bus_id > 0) {
            // Update
            $result = $wpdb->update($table, $data, array('id' => $bus_id), null, array('%d'));

            if ($result === false) {
                // Check for specific database errors
                if (!empty($wpdb->last_error)) {
                    if (strpos($wpdb->last_error, 'Duplicate entry') !== false && strpos($wpdb->last_error, 'registration_number') !== false) {
                        return new WP_Error('duplicate_registration', __('This registration number already exists.', 'mt-ticket-bus'));
                    }
                    return new WP_Error('update_failed', __('Failed to update bus: ', 'mt-ticket-bus') . $wpdb->last_error);
                }
                return new WP_Error('update_failed', __('Failed to update bus.', 'mt-ticket-bus'));
            }

            return $bus_id;
        } else {
            // Insert
            $result = $wpdb->insert($table, $data);

            if ($result === false) {
                // Check for specific database errors
                if (!empty($wpdb->last_error)) {
                    if (strpos($wpdb->last_error, 'Duplicate entry') !== false && strpos($wpdb->last_error, 'registration_number') !== false) {
                        return new WP_Error('duplicate_registration', __('This registration number already exists.', 'mt-ticket-bus'));
                    }
                    return new WP_Error('insert_failed', __('Failed to create bus: ', 'mt-ticket-bus') . $wpdb->last_error);
                }
                return new WP_Error('insert_failed', __('Failed to create bus.', 'mt-ticket-bus'));
            }

            return $wpdb->insert_id;
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
        // Suppress database error output for AJAX requests
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
        }

        $result = $this->save_bus($data);

        // Re-enable errors after processing
        $wpdb->suppress_errors(false);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            $message = $is_update
                ? __('Bus updated successfully.', 'mt-ticket-bus')
                : __('Bus created successfully.', 'mt-ticket-bus');
            wp_send_json_success(array('id' => $result, 'message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to save bus.', 'mt-ticket-bus')));
        }
    }

    /**
     * AJAX handler for checking registration number uniqueness
     */
    public function ajax_check_registration_number()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'mt-ticket-bus')));
        }

        $registration_number = isset($_POST['registration_number']) ? sanitize_text_field($_POST['registration_number']) : '';
        $exclude_id = isset($_POST['exclude_id']) ? absint($_POST['exclude_id']) : 0;

        if (empty($registration_number)) {
            wp_send_json_error(array('message' => __('Registration number is required.', 'mt-ticket-bus')));
        }

        $exists = $this->registration_number_exists($registration_number, $exclude_id);

        if ($exists) {
            wp_send_json_error(array('message' => __('This registration number already exists.', 'mt-ticket-bus')));
        } else {
            wp_send_json_success(array('message' => __('Registration number is available.', 'mt-ticket-bus')));
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
