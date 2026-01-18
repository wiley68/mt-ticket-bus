<?php

/**
 * Database management class
 *
 * Handles creation and management of plugin database tables
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Database class
 */
class MT_Ticket_Bus_Database
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Database
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Database
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
        register_activation_hook(MT_TICKET_BUS_PLUGIN_BASENAME, array($this, 'create_tables'));
        add_action('admin_init', array($this, 'maybe_create_tables'));
    }

    /**
     * Create database tables
     */
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for buses
        $table_buses = $wpdb->prefix . 'mt_ticket_buses';
        $sql_buses = "CREATE TABLE $table_buses (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			registration_number varchar(100) DEFAULT NULL,
			total_seats int(11) NOT NULL DEFAULT 0,
			seat_layout text DEFAULT NULL,
			features text DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

        // Table for routes
        $table_routes = $wpdb->prefix . 'mt_ticket_routes';
        $sql_routes = "CREATE TABLE $table_routes (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			start_station varchar(255) NOT NULL,
			end_station varchar(255) NOT NULL,
			intermediate_stations text DEFAULT NULL,
			distance decimal(10,2) DEFAULT NULL,
			duration int(11) DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

        // Table for route schedules
        $table_schedules = $wpdb->prefix . 'mt_ticket_route_schedules';
        $sql_schedules = "CREATE TABLE $table_schedules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			route_id bigint(20) UNSIGNED NOT NULL,
			bus_id bigint(20) UNSIGNED NOT NULL,
			departure_time time NOT NULL,
			arrival_time time NOT NULL,
			day_of_week varchar(20) DEFAULT NULL,
			price decimal(10,2) DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY route_id (route_id),
			KEY bus_id (bus_id),
			KEY status (status),
			FOREIGN KEY (route_id) REFERENCES {$table_routes}(id) ON DELETE CASCADE,
			FOREIGN KEY (bus_id) REFERENCES {$table_buses}(id) ON DELETE CASCADE
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql_buses);
        dbDelta($sql_routes);
        dbDelta($sql_schedules);

        // Store database version
        update_option('mt_ticket_bus_db_version', MT_TICKET_BUS_VERSION);
    }

    /**
     * Check and create tables if needed
     */
    public function maybe_create_tables()
    {
        $db_version = get_option('mt_ticket_bus_db_version');

        if ($db_version !== MT_TICKET_BUS_VERSION) {
            $this->create_tables();
        }
    }

    /**
     * Get table name for buses
     *
     * @return string
     */
    public static function get_buses_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'mt_ticket_buses';
    }

    /**
     * Get table name for routes
     *
     * @return string
     */
    public static function get_routes_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'mt_ticket_routes';
    }

    /**
     * Get table name for route schedules
     *
     * @return string
     */
    public static function get_schedules_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'mt_ticket_route_schedules';
    }
}
