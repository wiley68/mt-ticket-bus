<?php

/**
 * WooCommerce integration class
 *
 * Handles integration with WooCommerce for bus ticket products
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration class
 */
class MT_Ticket_Bus_WooCommerce_Integration
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_WooCommerce_Integration
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_WooCommerce_Integration
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
        // Add product meta fields
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_meta_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta_fields'));

        // Make product virtual by default
        add_action('woocommerce_product_options_general_product_data', array($this, 'set_virtual_default'));

        // Customize product page
        add_action('woocommerce_single_product_summary', array($this, 'display_bus_ticket_options'), 25);
    }

    /**
     * Add product meta fields for bus tickets
     */
    public function add_product_meta_fields()
    {
        global $post;

        $route_id = get_post_meta($post->ID, '_mt_bus_route_id', true);

        echo '<div class="options_group mt-bus-ticket-options">';

        // Route selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_route_id',
            'label'       => __('Bus Route', 'mt-ticket-bus'),
            'description' => __('Select the bus route for this ticket product.', 'mt-ticket-bus'),
            'options'     => $this->get_routes_options(),
            'value'       => $route_id,
        ));

        echo '</div>';
    }

    /**
     * Save product meta fields
     *
     * @param int $post_id Post ID
     */
    public function save_product_meta_fields($post_id)
    {
        if (isset($_POST['_mt_bus_route_id'])) {
            update_post_meta($post_id, '_mt_bus_route_id', sanitize_text_field($_POST['_mt_bus_route_id']));
        }
    }

    /**
     * Set virtual product by default
     */
    public function set_virtual_default()
    {
        global $post;

        if (! $post->ID) {
            return;
        }

        $is_virtual = get_post_meta($post->ID, '_virtual', true);

        if (empty($is_virtual)) {
?>
            <script>
                jQuery(document).ready(function($) {
                    $('#_virtual').prop('checked', true).trigger('change');
                });
            </script>
<?php
        }
    }

    /**
     * Get routes options for select field
     *
     * @return array
     */
    private function get_routes_options()
    {
        $routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes();
        $options = array('' => __('Select a route...', 'mt-ticket-bus'));

        foreach ($routes as $route) {
            $options[$route->id] = $route->name . ' (' . $route->start_station . ' - ' . $route->end_station . ')';
        }

        return $options;
    }

    /**
     * Display bus ticket options on product page
     */
    public function display_bus_ticket_options()
    {
        global $product;

        $route_id = get_post_meta($product->get_id(), '_mt_bus_route_id', true);

        if (! $route_id) {
            return;
        }

        // This will be expanded later with seat selection, schedule, etc.
        echo '<div class="mt-bus-ticket-selection">';
        echo '<h3>' . esc_html__('Select Your Ticket', 'mt-ticket-bus') . '</h3>';
        // Placeholder for future implementation
        echo '</div>';
    }
}
