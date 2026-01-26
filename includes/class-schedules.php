<?php

/**
 * Schedules management class
 *
 * Handles CRUD operations for bus route schedules
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Schedules class.
 *
 * Handles CRUD operations for bus route schedules including creation,
 * updating, deletion, and querying schedules by various criteria.
 *
 * @since 1.0.0
 */
class MT_Ticket_Bus_Schedules
{

    /**
     * Plugin instance.
     *
     * @since 1.0.0
     *
     * @var MT_Ticket_Bus_Schedules
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @since 1.0.0
     *
     * @return MT_Ticket_Bus_Schedules Plugin instance.
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
     * Initializes AJAX handlers for schedule management.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        add_action('wp_ajax_mt_save_schedule', array($this, 'ajax_save_schedule'));
        add_action('wp_ajax_mt_delete_schedule', array($this, 'ajax_delete_schedule'));
    }

    /**
     * Get all schedules.
     *
     * @since 1.0.0
     *
     * @param array $args Query arguments. {
     *     Optional. Array of query parameters.
     *
     *     @var string $status   Schedule status ('active', 'inactive', or 'all'). Default 'active'.
     *     @var int    $route_id Route ID to filter by. Default 0 (all routes).
     *     @var string $orderby  Field to order by. Default 'id'.
     *     @var string $order    Order direction ('ASC' or 'DESC'). Default 'DESC'.
     * }
     * @return array Array of schedule objects.
     */
    public function get_all_schedules($args = array())
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        $defaults = array(
            'status' => 'active',
            'route_id' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $where = array();

        if ($args['status'] !== 'all') {
            $where[] = "status = '" . esc_sql($args['status']) . "'";
        }

        if (!empty($args['route_id'])) {
            $where[] = "route_id = " . absint($args['route_id']);
        }

        $where_clause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';
        $orderby = "ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        $results = $wpdb->get_results("SELECT * FROM $table $where_clause $orderby");

        return $results;
    }

    /**
     * Get schedule by ID.
     *
     * @since 1.0.0
     *
     * @param int $id Schedule ID.
     * @return object|null Schedule object or null if not found.
     */
    public function get_schedule($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Save schedule (create or update).
     *
     * @since 1.0.0
     *
     * @param array $data Schedule data. {
     *     Array of schedule parameters.
     *
     *     @var int    $id          Schedule ID (for updates). Default 0.
     *     @var string $name        Schedule name. Optional.
     *     @var int    $route_id    Route ID. Required.
     *     @var string $courses     JSON string or array of courses. Required.
     *     @var string $days_of_week Days of week ('all', 'weekdays', 'weekend', or JSON array). Optional.
     *     @var string $status      Schedule status ('active' or 'inactive'). Default 'active'.
     * }
     * @return int|WP_Error Schedule ID on success, WP_Error on failure.
     */
    public function save_schedule($data)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        // Store ID before processing
        $schedule_id = isset($data['id']) && $data['id'] > 0 ? absint($data['id']) : 0;

        $defaults = array(
            'name' => '',
            'route_id' => 0,
            'bus_id' => 0,
            'courses' => '[]',
            'days_of_week' => '',
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['route_id'])) {
            return new WP_Error('missing_route_id', __('Route is required.', 'mt-ticket-bus'));
        }

        // Validate courses
        $courses = array();

        // Check if courses data exists
        if (!isset($data['courses'])) {
            return new WP_Error('missing_courses', __('Courses field is missing. At least one course is required.', 'mt-ticket-bus'));
        }

        $courses_input = $data['courses'];

        // Handle empty string or empty array
        if (empty($courses_input) || $courses_input === '[]' || $courses_input === 'null') {
            return new WP_Error('missing_courses', __('At least one course is required.', 'mt-ticket-bus'));
        }

        if (is_string($courses_input)) {
            // Try to decode JSON (slashes already stripped by stripslashes_deep in AJAX handler)
            $decoded = json_decode($courses_input, true);
            $last_error = json_last_error();

            // If first decode fails, try decoding again in case it's double encoded
            if ($last_error !== JSON_ERROR_NONE && is_string($decoded) && !empty($decoded)) {
                $decoded = json_decode($decoded, true);
                $last_error = json_last_error();
            }

            if ($last_error === JSON_ERROR_NONE && is_array($decoded)) {
                $courses = $decoded;
            } else {
                return new WP_Error('invalid_courses_json', __('Invalid courses JSON format. Error: ' . json_last_error_msg(), 'mt-ticket-bus'));
            }
        } elseif (is_array($courses_input)) {
            $courses = $courses_input;
        } else {
            return new WP_Error('invalid_courses_type', __('Courses must be a JSON string or array.', 'mt-ticket-bus'));
        }

        if (!is_array($courses) || empty($courses)) {
            return new WP_Error('missing_courses', __('At least one course is required.', 'mt-ticket-bus'));
        }

        // Validate each course
        foreach ($courses as $course) {
            if (empty($course['departure_time']) || empty($course['arrival_time'])) {
                return new WP_Error('invalid_course', __('Each course must have both departure and arrival times.', 'mt-ticket-bus'));
            }
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

        // Sanitize courses
        $sanitized_courses = array();
        foreach ($courses as $course) {
            $sanitized_courses[] = array(
                'departure_time' => sanitize_text_field($course['departure_time']),
                'arrival_time' => sanitize_text_field($course['arrival_time']),
            );
        }

        // Sanitize data
        $sanitized_data = array(
            'name' => !empty($data['name']) ? sanitize_text_field($data['name']) : null,
            'route_id' => absint($data['route_id']),
            'courses' => wp_json_encode($sanitized_courses),
            'days_of_week' => $days_of_week,
            'status' => sanitize_text_field($data['status']),
        );

        if ($schedule_id > 0) {
            // Update
            $result = $wpdb->update($table, $sanitized_data, array('id' => $schedule_id));

            if ($result === false) {
                $error_msg = __('Failed to update schedule.', 'mt-ticket-bus');
                if ($wpdb->last_error) {
                    $error_msg .= ' ' . __('Database error: ', 'mt-ticket-bus') . $wpdb->last_error;
                }
                return new WP_Error('update_failed', $error_msg);
            }

            return $schedule_id;
        } else {
            // Insert
            $result = $wpdb->insert($table, $sanitized_data);

            if ($result === false) {
                $error_msg = __('Failed to create schedule.', 'mt-ticket-bus');
                if ($wpdb->last_error) {
                    $error_msg .= ' ' . __('Database error: ', 'mt-ticket-bus') . $wpdb->last_error;
                }
                return new WP_Error('insert_failed', $error_msg);
            }

            return $wpdb->insert_id;
        }
    }

    /**
     * Delete schedule.
     *
     * @since 1.0.0
     *
     * @param int $id Schedule ID.
     * @return bool True on success, false on failure.
     */
    public function delete_schedule($id)
    {
        global $wpdb;

        $table = MT_Ticket_Bus_Database::get_schedules_table();

        return $wpdb->delete($table, array('id' => absint($id)), array('%d')) !== false;
    }

    /**
     * AJAX handler for saving schedule.
     *
     * @since 1.0.0
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

        // WordPress may add slashes to POST data, so strip them
        $data = stripslashes_deep($_POST);

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
            $error_message = $result->get_error_message();
            $error_code = $result->get_error_code();

            wp_send_json_error(array(
                'message' => $error_message,
                'code' => $error_code
            ));
        } elseif ($result) {
            $message = $is_update
                ? __('Schedule updated successfully.', 'mt-ticket-bus')
                : __('Schedule created successfully.', 'mt-ticket-bus');
            wp_send_json_success(array('id' => $result, 'message' => $message));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save schedule.', 'mt-ticket-bus')
            ));
        }
    }

    /**
     * AJAX handler for deleting schedule.
     *
     * @since 1.0.0
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
     * Parse days of week from stored format.
     *
     * Converts stored days_of_week value to a usable format, handling
     * special values ('all', 'weekdays', 'weekend') and JSON arrays.
     *
     * @since 1.0.0
     *
     * @param string $days_of_week Stored days of week value.
     * @return array|string Parsed days (array, 'all', 'weekdays', 'weekend', or single day string).
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

    /**
     * Get schedules by route ID.
     *
     * @since 1.0.0
     *
     * @param int   $route_id Route ID.
     * @param array $args     Additional query arguments (see get_all_schedules() for options).
     * @return array Array of schedule objects for the specified route.
     */
    public function get_schedules_by_route($route_id, $args = array())
    {
        $args['route_id'] = absint($route_id);
        return $this->get_all_schedules($args);
    }
}
