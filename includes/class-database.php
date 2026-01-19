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
			registration_number varchar(100) NOT NULL,
			total_seats int(11) NOT NULL DEFAULT 0,
			seat_layout text DEFAULT NULL,
			features text DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY registration_number (registration_number),
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
			name varchar(255) DEFAULT NULL,
			route_id bigint(20) UNSIGNED DEFAULT NULL,
			courses text DEFAULT NULL,
			days_of_week text DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY route_id (route_id)
		) $charset_collate;";

		// Table for ticket reservations/bookings
		$table_reservations = $wpdb->prefix . 'mt_ticket_reservations';
		$sql_reservations = "CREATE TABLE $table_reservations (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id bigint(20) UNSIGNED NOT NULL,
			order_item_id bigint(20) UNSIGNED DEFAULT NULL,
			product_id bigint(20) UNSIGNED NOT NULL,
			schedule_id bigint(20) UNSIGNED NOT NULL,
			bus_id bigint(20) UNSIGNED NOT NULL,
			route_id bigint(20) UNSIGNED NOT NULL,
			seat_number varchar(10) NOT NULL,
			departure_date date NOT NULL,
			departure_time time NOT NULL,
			passenger_name varchar(255) DEFAULT NULL,
			passenger_email varchar(255) DEFAULT NULL,
			passenger_phone varchar(50) DEFAULT NULL,
			status varchar(20) DEFAULT 'reserved',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY schedule_id (schedule_id),
			KEY bus_id (bus_id),
			KEY route_id (route_id),
			KEY departure_date (departure_date),
			KEY status (status),
			UNIQUE KEY unique_booking (schedule_id, departure_date, departure_time, seat_number, status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta($sql_buses);
		dbDelta($sql_routes);
		dbDelta($sql_schedules);
		dbDelta($sql_reservations);

		// Store database version
		update_option('mt_ticket_bus_db_version', MT_TICKET_BUS_DB_VERSION);
	}

	/**
	 * Check and create tables if needed
	 */
	public function maybe_create_tables()
	{
		global $wpdb;
		
		$db_version = get_option('mt_ticket_bus_db_version');

		if ($db_version !== MT_TICKET_BUS_DB_VERSION) {
			// If tables exist, update them; otherwise create new ones
			$table_schedules = $wpdb->prefix . 'mt_ticket_route_schedules';
			$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_schedules'") === $table_schedules;
			
			if ($table_exists) {
				// Update existing tables
				$this->update_existing_tables();
				// Also run create_tables to ensure structure is correct
				$this->create_tables();
			} else {
				// Create new tables
				$this->create_tables();
			}

			// Update stored version after successful update
			update_option('mt_ticket_bus_db_version', MT_TICKET_BUS_DB_VERSION);
		}
	}

	/**
	 * Update existing tables structure
	 * 
	 * Updates existing tables when database version changes
	 */
	private function update_existing_tables()
	{
		global $wpdb;

		$table_schedules = $wpdb->prefix . 'mt_ticket_route_schedules';
		
		// Check if table exists
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_schedules'") === $table_schedules;
		if (!$table_exists) {
			return;
		}
		
		// Get current columns
		$columns = $wpdb->get_col("DESCRIBE $table_schedules");
		
		// Remove old departure_time column if it exists
		if (in_array('departure_time', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules DROP COLUMN departure_time");
		}
		
		// Remove old arrival_time column if it exists
		if (in_array('arrival_time', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules DROP COLUMN arrival_time");
		}
		
		// Remove old frequency_type column if it exists
		if (in_array('frequency_type', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules DROP COLUMN frequency_type");
		}
		
		// Remove old price column if it exists
		if (in_array('price', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules DROP COLUMN price");
		}
		
		// Get columns again after removals
		$columns = $wpdb->get_col("DESCRIBE $table_schedules");
		
		// Add new columns if they don't exist
		if (!in_array('name', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules ADD COLUMN name varchar(255) DEFAULT NULL AFTER id");
		}
		
		if (!in_array('route_id', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules ADD COLUMN route_id bigint(20) UNSIGNED DEFAULT NULL AFTER name");
			// Check if index exists before adding
			$indexes = $wpdb->get_results("SHOW INDEXES FROM $table_schedules WHERE Key_name = 'route_id'");
			if (empty($indexes)) {
				$wpdb->query("ALTER TABLE $table_schedules ADD INDEX route_id (route_id)");
			}
		}
		
		if (!in_array('courses', $columns)) {
			$wpdb->query("ALTER TABLE $table_schedules ADD COLUMN courses text DEFAULT NULL AFTER route_id");
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

	/**
	 * Get table name for ticket reservations
	 *
	 * @return string
	 */
	public static function get_reservations_table()
	{
		global $wpdb;
		return $wpdb->prefix . 'mt_ticket_reservations';
	}
}
