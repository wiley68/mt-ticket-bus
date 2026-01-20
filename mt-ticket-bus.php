<?php

/**
 * Plugin Name: MT Ticket Bus
 * Plugin URI: https://avalonbg.com/mt-ticket-bus
 * Description: A comprehensive WordPress plugin for bus ticket sales management integrated with WooCommerce.
 * Version: 1.0.0
 * Author: Ilko Ivanov
 * Author URI: https://avalonbg.com/ilko-ivanov
 * Text Domain: mt-ticket-bus
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 10.4.3
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_TICKET_BUS_VERSION', '1.0.0');
define('MT_TICKET_BUS_DB_VERSION', '1.0.1'); // Increment this when database structure changes
define('MT_TICKET_BUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_TICKET_BUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_TICKET_BUS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class MT_Ticket_Bus
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus
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
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init()
    {
        // Declare WooCommerce features compatibility
        add_action('before_woocommerce_init', array($this, 'declare_woocommerce_compatibility'));

        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));

        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Initialize WooCommerce integration
        add_action('plugins_loaded', array($this, 'init_woocommerce_integration'), 20);

        // Include required files
        $this->includes();

        // Initialize components
        add_action('init', array($this, 'init_components'));
    }

    /**
     * Declare WooCommerce features compatibility
     */
    public function declare_woocommerce_compatibility()
    {
        // Check if FeaturesUtil class exists (available in WooCommerce 8.0+)
        // @phpstan-ignore-next-line
        // @psalm-suppress UndefinedClass
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                MT_TICKET_BUS_PLUGIN_BASENAME,
                true
            );
        }
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce()
    {
        if (! class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
?>
        <div class="error">
            <p><?php esc_html_e('MT Ticket Bus requires WooCommerce to be installed and active.', 'mt-ticket-bus'); ?></p>
        </div>
<?php
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'mt-ticket-bus',
            false,
            dirname(MT_TICKET_BUS_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize WooCommerce integration
     */
    public function init_woocommerce_integration()
    {
        if (class_exists('WooCommerce')) {
            MT_Ticket_Bus_WooCommerce_Integration::get_instance();
        }
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-database.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-admin.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-blocks.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-buses.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-routes.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-schedules.php';
        require_once MT_TICKET_BUS_PLUGIN_DIR . 'includes/class-reservations.php';
    }

    /**
     * Initialize plugin components
     */
    public function init_components()
    {
        // Initialize database
        MT_Ticket_Bus_Database::get_instance();

        // Initialize admin
        if (is_admin()) {
            MT_Ticket_Bus_Admin::get_instance();
        }

        // Initialize blocks (block themes / Site Editor)
        MT_Ticket_Bus_Blocks::get_instance();

        // Initialize buses manager
        MT_Ticket_Bus_Buses::get_instance();

        // Initialize routes manager
        MT_Ticket_Bus_Routes::get_instance();

        // Initialize schedules manager
        MT_Ticket_Bus_Schedules::get_instance();

        // Initialize reservations manager
        MT_Ticket_Bus_Reservations::get_instance();
    }
}

/**
 * Initialize plugin
 */
function mt_ticket_bus_init()
{
    return MT_Ticket_Bus::get_instance();
}

// Start the plugin
mt_ticket_bus_init();
