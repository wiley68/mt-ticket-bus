<?php

/**
 * Routes management class
 *
 * Handles CRUD operations for bus routes
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Routes class.
 *
 * Handles CRUD operations for bus routes including creation, updating,
 * deletion, and querying routes by various criteria.
 *
 * @since 1.0.0
 */
class MT_Ticket_Bus_Routes
{

    /**
     * Plugin instance.
     *
     * @since 1.0.0
     *
     * @var MT_Ticket_Bus_Routes
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @since 1.0.0
     *
     * @return MT_Ticket_Bus_Routes Plugin instance.
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
     * Initializes AJAX handlers for route management.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        add_action('wp_ajax_mt_save_route', array($this, 'ajax_save_route'));
        add_action('wp_ajax_mt_delete_route', array($this, 'ajax_delete_route'));
    }

    /**
     * Get all routes.
     *
     * @since 1.0.0
     *
     * @param array $args Query arguments. {
     *     Optional. Array of query parameters.
     *
     *     @var string $status  Route status ('active', 'inactive', or 'all'). Default 'active'.
     *     @var string $orderby Field to order by. Default 'id'.
     *     @var string $order   Order direction ('ASC' or 'DESC'). Default 'DESC'.
     * }
     * @return array Array of route objects.
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

        $where = '';
        if ($args['status'] !== 'all') {
            $where = "WHERE status = '" . esc_sql($args['status']) . "'";
        }

        $orderby = "ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        $results = $wpdb->get_results("SELECT * FROM $table $where $orderby");

        return $results;
    }

    /**
     * Get route by ID.
     *
     * @since 1.0.0
     *
     * @param int $id Route ID.
     * @return object|null Route object or null if not found.
     */
    public function get_route($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_routes_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Save route (create or update).
     *
     * @since 1.0.0
     *
     * @param array $data Route data. {
     *     Array of route parameters.
     *
     *     @var int    $id                      Route ID (for updates). Default 0.
     *     @var string $name                    Route name. Required.
     *     @var string $start_station            Starting station name. Required.
     *     @var string $start_station_address    Starting station address. Optional.
     *     @var float  $start_station_latitude   Starting station latitude. Optional.
     *     @var float  $start_station_longitude  Starting station longitude. Optional.
     *     @var string $end_station              Ending station name. Required.
     *     @var string $end_station_address      Ending station address. Optional.
     *     @var float  $end_station_latitude     Ending station latitude. Optional.
     *     @var float  $end_station_longitude   Ending station longitude. Optional.
     *     @var string $intermediate_stations     Intermediate stations (JSON array or line-separated text). Optional.
     *     @var float  $distance                 Route distance. Default 0.
     *     @var int    $duration                 Route duration in minutes. Default 0.
     *     @var string $status                   Route status ('active' or 'inactive'). Default 'active'.
     * }
     * @return int|false Route ID on success, false on failure.
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
            'start_station_address' => '',
            'start_station_latitude' => null,
            'start_station_longitude' => null,
            'end_station' => '',
            'end_station_address' => '',
            'end_station_latitude' => null,
            'end_station_longitude' => null,
            'intermediate_stations' => '',
            'distance' => 0,
            'duration' => 0,
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        // Process intermediate_stations - can be JSON array or legacy text format
        $intermediate_stations = '';
        if (isset($data['intermediate_stations'])) {
            $intermediate_raw = $data['intermediate_stations'];

            // Check if it's empty or just whitespace
            if (empty(trim($intermediate_raw))) {
                $intermediate_stations = '';
            } else {
                // Try to decode as JSON first
                // jQuery automatically URL-decodes POST data, so we can decode JSON directly
                $decoded = json_decode($intermediate_raw, true);

                // If decoding failed, try stripslashes (in case of magic quotes or double encoding)
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $intermediate_raw = stripslashes($intermediate_raw);
                    $decoded = json_decode($intermediate_raw, true);
                }

                if (is_array($decoded) && !empty($decoded)) {
                    // Validate and sanitize JSON structure
                    $sanitized_stations = array();
                    foreach ($decoded as $station) {
                        // Handle case where station might be double-encoded (string instead of array)
                        if (is_string($station)) {
                            // Try to decode the string as JSON
                            $station_decoded = json_decode($station, true);
                            if (is_array($station_decoded) && isset($station_decoded['name'])) {
                                // It was double-encoded, use the decoded version
                                $station = $station_decoded;
                            } else {
                                // If it's just a string name (legacy format), skip it (invalid format for new structure)
                                continue;
                            }
                        }

                        // Validate station structure
                        if (isset($station['name']) && !empty(trim($station['name']))) {
                            // Ensure name is a string, not an array or object
                            $station_name = is_string($station['name']) ? $station['name'] : '';
                            if (!empty($station_name)) {
                                $sanitized_stations[] = array(
                                    'name' => sanitize_text_field($station_name),
                                    'duration' => isset($station['duration']) ? absint($station['duration']) : 0
                                );
                            }
                        }
                    }
                    // Only save if we have valid stations, otherwise save empty string
                    if (!empty($sanitized_stations)) {
                        // Use json_encode with UNESCAPED_UNICODE flag for better readability
                        $intermediate_stations = json_encode($sanitized_stations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        $intermediate_stations = '';
                    }
                } else {
                    // Legacy format: line-separated text - convert to JSON
                    $lines = explode("\n", $intermediate_raw);
                    $stations_array = array();
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $stations_array[] = array(
                                'name' => sanitize_text_field($line),
                                'duration' => 0 // Default duration for legacy entries
                            );
                        }
                    }
                    // Only save if we have valid stations, otherwise save empty string
                    if (!empty($stations_array)) {
                        // Use json_encode with UNESCAPED_UNICODE flag for better readability
                        $intermediate_stations = json_encode($stations_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        $intermediate_stations = '';
                    }
                }
            }
        }

        // Sanitize data
        $data = array(
            'name' => sanitize_text_field($data['name']),
            'start_station' => sanitize_text_field($data['start_station']),
            'start_station_address' => isset($data['start_station_address']) ? sanitize_textarea_field($data['start_station_address']) : '',
            'start_station_latitude' => isset($data['start_station_latitude']) && $data['start_station_latitude'] !== '' ? floatval($data['start_station_latitude']) : null,
            'start_station_longitude' => isset($data['start_station_longitude']) && $data['start_station_longitude'] !== '' ? floatval($data['start_station_longitude']) : null,
            'end_station' => sanitize_text_field($data['end_station']),
            'end_station_address' => isset($data['end_station_address']) ? sanitize_textarea_field($data['end_station_address']) : '',
            'end_station_latitude' => isset($data['end_station_latitude']) && $data['end_station_latitude'] !== '' ? floatval($data['end_station_latitude']) : null,
            'end_station_longitude' => isset($data['end_station_longitude']) && $data['end_station_longitude'] !== '' ? floatval($data['end_station_longitude']) : null,
            'intermediate_stations' => $intermediate_stations,
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
     * Delete route.
     *
     * @since 1.0.0
     *
     * @param int $id Route ID.
     * @return bool True on success, false on failure.
     */
    public function delete_route($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_routes_table();

        return $wpdb->delete($table, array('id' => absint($id)), array('%d')) !== false;
    }

    /**
     * AJAX handler for saving route.
     *
     * @since 1.0.0
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
     * AJAX handler for deleting route.
     *
     * @since 1.0.0
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
