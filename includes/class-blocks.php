<?php

/**
 * Gutenberg blocks for ticket product UI (block themes)
 *
 * @package MT_Ticket_Bus
 */

if (! defined('ABSPATH')) {
    exit;
}

// Polyfill for editor static analysis (and extreme legacy WP).
// WordPress core provides did_action(), so this will never run on normal installs.
if (! function_exists('did_action')) {
    /**
     * Retrieve the number of times an action has been fired during the current request.
     *
     * @param string $hook_name The name of the action hook.
     * @return int The number of times the action has been fired.
     */
    function did_action($hook_name)
    {
        return 0;
    }
}

class MT_Ticket_Bus_Blocks
{
    /**
     * @var MT_Ticket_Bus_Blocks|null
     */
    private static $instance = null;

    /**
     * @var bool Flag to prevent multiple direct registrations
     */
    private $direct_registered = false;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Register blocks on init with high priority to ensure they're registered early
        add_action('init', array($this, 'register_blocks'), 5);

        // Also try to register blocks immediately if we're past init (but only once)
        if (did_action('init') && ! $this->direct_registered) {
            $this->register_blocks();
            $this->direct_registered = true;
        }

        // Also register blocks on 'plugins_loaded' to ensure they're available early
        add_action('plugins_loaded', array($this, 'register_blocks'), 20);

        // Ensure blocks JS/CSS load in both Post Editor and Site Editor inserter
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_block_editor_assets'));

        // Enqueue frontend styles for CSS rules (hiding standard UI)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Also add inline style in head as backup
        add_action('wp_head', array($this, 'add_inline_css_fallback'), 999);

        add_filter('body_class', array($this, 'add_ticket_body_class'));

        // Ensure blocks are registered during render if they weren't registered earlier
        add_filter('render_block_data', array($this, 'ensure_blocks_registered'), 10, 2);
    }

    /**
     * Ensure blocks are registered during render if they weren't registered earlier
     */
    public function ensure_blocks_registered($parsed_block, $source_block)
    {
        if (
            isset($parsed_block['blockName']) &&
            ($parsed_block['blockName'] === 'mt-ticket-bus/seatmap' ||
                $parsed_block['blockName'] === 'mt-ticket-bus/ticket-summary')
        ) {

            $registry = WP_Block_Type_Registry::get_instance();
            $is_registered = $registry->is_registered($parsed_block['blockName']);

            if (! $is_registered) {
                // Try to register blocks immediately
                $this->register_blocks();
            }
        }
        return $parsed_block;
    }

    /**
     * Returns true if current page is a single product and the product is marked as ticket.
     */
    private function is_ticket_product_context()
    {
        if (! function_exists('is_product') || ! is_product()) {
            return false;
        }

        $product_id = get_queried_object_id();
        if (! $product_id) {
            return false;
        }

        return get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes';
    }

    public function add_ticket_body_class($classes)
    {
        if ($this->is_ticket_product_context()) {
            $classes[] = 'mt-is-ticket-product';
        }
        return $classes;
    }

    public function register_blocks()
    {
        // Prevent multiple registrations
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        // Styles
        wp_register_style(
            'mt-ticket-bus-blocks',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/css/blocks.css',
            array(),
            MT_TICKET_BUS_VERSION
        );

        // Editor script
        wp_register_script(
            'mt-ticket-bus-blocks',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor'),
            MT_TICKET_BUS_VERSION,
            true
        );

        // 1) Seatmap block (replaces gallery visually)
        register_block_type(
            'mt-ticket-bus/seatmap',
            array(
                'title'           => __('MT Ticket Seatmap', 'mt-ticket-bus'),
                'description'     => __('Seat selection block for ticket products.', 'mt-ticket-bus'),
                'category'        => 'widgets',
                'icon'            => 'tickets-alt',
                'render_callback' => array($this, 'render_seatmap_block'),
                'editor_script'   => 'mt-ticket-bus-blocks',
                'editor_style'    => 'mt-ticket-bus-blocks',
                'style'           => 'mt-ticket-bus-blocks',
                'attributes'      => array(),
            )
        );

        // 2) Ticket summary block (replaces right summary visually)
        register_block_type(
            'mt-ticket-bus/ticket-summary',
            array(
                'title'           => __('MT Ticket Summary', 'mt-ticket-bus'),
                'description'     => __('Ticket info/CTA block for ticket products.', 'mt-ticket-bus'),
                'category'        => 'widgets',
                'icon'            => 'id-alt',
                'render_callback' => array($this, 'render_ticket_summary_block'),
                'editor_script'   => 'mt-ticket-bus-blocks',
                'editor_style'    => 'mt-ticket-bus-blocks',
                'style'           => 'mt-ticket-bus-blocks',
                'attributes'      => array(),
            )
        );
    }

    /**
     * Explicitly enqueue editor assets so blocks show in the inserter in the Site Editor.
     */
    public function enqueue_block_editor_assets()
    {
        // Register script if not already registered
        if (! wp_script_is('mt-ticket-bus-blocks', 'registered')) {
            wp_register_script(
                'mt-ticket-bus-blocks',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/js/blocks.js',
                array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor'),
                MT_TICKET_BUS_VERSION,
                true
            );
        }

        // Register style if not already registered
        if (! wp_style_is('mt-ticket-bus-blocks', 'registered')) {
            wp_register_style(
                'mt-ticket-bus-blocks',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/css/blocks.css',
                array(),
                MT_TICKET_BUS_VERSION
            );
        }

        // Enqueue script and style
        wp_enqueue_script('mt-ticket-bus-blocks');
        wp_enqueue_style('mt-ticket-bus-blocks');
    }

    /**
     * Enqueue frontend assets (CSS and JS for seatmap functionality).
     */
    public function enqueue_frontend_assets()
    {
        // Only load on single product pages
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }

        // Check if it's a ticket product
        if (! $this->is_ticket_product_context()) {
            return;
        }

        // Ensure style is registered before enqueueing
        if (! wp_style_is('mt-ticket-bus-blocks', 'registered')) {
            wp_register_style(
                'mt-ticket-bus-blocks',
                MT_TICKET_BUS_PLUGIN_URL . 'assets/css/blocks.css',
                array(),
                MT_TICKET_BUS_VERSION
            );
        }

        wp_enqueue_style('mt-ticket-bus-blocks');

        // Enqueue SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11.0.0'
        );

        // Enqueue SweetAlert2 JS
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
            array(),
            '11.0.0',
            true
        );

        // Enqueue seatmap JavaScript
        wp_enqueue_script(
            'mt-ticket-bus-seatmap',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/seatmap.js',
            array('jquery'),
            MT_TICKET_BUS_VERSION,
            true
        );

        // Enqueue ticket summary JavaScript
        wp_enqueue_script(
            'mt-ticket-bus-ticket-summary',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/ticket-summary.js',
            array('jquery', 'sweetalert2'),
            MT_TICKET_BUS_VERSION,
            true
        );

        // Get calendar week start setting
        $settings = get_option('mt_ticket_bus_settings', array());
        $calendar_week_start = isset($settings['calendar_week_start']) ? $settings['calendar_week_start'] : 'monday';

        // Get WooCommerce price formatting settings
        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        $currency_position = get_option('woocommerce_currency_pos', 'left');
        $price_decimal_sep = function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.';
        // Get thousand separator - if function exists, use its value (even if empty string)
        // Only use fallback if function doesn't exist
        if (function_exists('wc_get_price_thousand_separator')) {
            $price_thousand_sep = wc_get_price_thousand_separator();
        } else {
            $price_thousand_sep = ',';
        }
        $price_num_decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

        // Localize script for AJAX (shared by both scripts)
        wp_localize_script(
            'mt-ticket-bus-seatmap',
            'mtTicketBus',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ticket_bus_frontend'),
                'calendarWeekStart' => $calendar_week_start, // 'monday' or 'sunday'
                'priceFormat' => array(
                    'currencySymbol' => $currency_symbol,
                    'currencyPosition' => $currency_position, // 'left', 'right', 'left_space', 'right_space'
                    'decimalSeparator' => $price_decimal_sep,
                    'thousandSeparator' => $price_thousand_sep,
                    'decimals' => $price_num_decimals,
                ),
                'i18n' => array(
                    'selectDate' => __('Select a date', 'mt-ticket-bus'),
                    'selectTime' => __('Select a time', 'mt-ticket-bus'),
                    'selectSeat' => __('Select your seat(s)', 'mt-ticket-bus'),
                    'monthNames' => array(
                        __('January', 'mt-ticket-bus'),
                        __('February', 'mt-ticket-bus'),
                        __('March', 'mt-ticket-bus'),
                        __('April', 'mt-ticket-bus'),
                        __('May', 'mt-ticket-bus'),
                        __('June', 'mt-ticket-bus'),
                        __('July', 'mt-ticket-bus'),
                        __('August', 'mt-ticket-bus'),
                        __('September', 'mt-ticket-bus'),
                        __('October', 'mt-ticket-bus'),
                        __('November', 'mt-ticket-bus'),
                        __('December', 'mt-ticket-bus'),
                    ),
                    'loading' => __('Loading...', 'mt-ticket-bus'),
                    'error' => __('Error', 'mt-ticket-bus'),
                    'noSchedulesFound' => __('No schedules found for this route.', 'mt-ticket-bus'),
                    'noSeatsFound' => __('No seats found for this schedule and date.', 'mt-ticket-bus'),
                    'seat' => __('Seat', 'mt-ticket-bus'),
                    'available' => __('Available', 'mt-ticket-bus'),
                    'reserved' => __('Reserved', 'mt-ticket-bus'),
                    'selected' => __('Selected', 'mt-ticket-bus'),
                    'disabled' => __('Disabled', 'mt-ticket-bus'),
                    'processing' => __('Processing...', 'mt-ticket-bus'),
                    'ok' => __('OK', 'mt-ticket-bus'),
                    'addedToCart' => __('Added to cart', 'mt-ticket-bus'),
                    'addToCartError' => __('Error adding to cart.', 'mt-ticket-bus'),
                    'addToCartErrorRetry' => __('Error adding to cart. Please try again.', 'mt-ticket-bus'),
                    'availableSeats' => __('available seats', 'mt-ticket-bus'),
                    'of' => __('of', 'mt-ticket-bus'),
                    'noAvailableSeats' => __('No available seats', 'mt-ticket-bus'),
                    'loadingError' => __('Error loading layout.', 'mt-ticket-bus'),
                    'invalidLayout' => __('Invalid seat layout.', 'mt-ticket-bus'),
                    'pastDate' => __('Past date', 'mt-ticket-bus'),
                    'unavailableDate' => __('Unavailable date', 'mt-ticket-bus'),
                ),
            )
        );

        // Also localize for ticket-summary script
        wp_localize_script(
            'mt-ticket-bus-ticket-summary',
            'mtTicketBus',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ticket_bus_frontend'),
                'i18n' => array(
                    'selectDate' => __('Select a date', 'mt-ticket-bus'),
                    'selectTime' => __('Select a time', 'mt-ticket-bus'),
                    'selectSeat' => __('Select your seat(s)', 'mt-ticket-bus'),
                    'monthNames' => array(
                        __('January', 'mt-ticket-bus'),
                        __('February', 'mt-ticket-bus'),
                        __('March', 'mt-ticket-bus'),
                        __('April', 'mt-ticket-bus'),
                        __('May', 'mt-ticket-bus'),
                        __('June', 'mt-ticket-bus'),
                        __('July', 'mt-ticket-bus'),
                        __('August', 'mt-ticket-bus'),
                        __('September', 'mt-ticket-bus'),
                        __('October', 'mt-ticket-bus'),
                        __('November', 'mt-ticket-bus'),
                        __('December', 'mt-ticket-bus'),
                    ),
                    'loading' => __('Loading...', 'mt-ticket-bus'),
                    'error' => __('Error', 'mt-ticket-bus'),
                    'noSchedulesFound' => __('No schedules found for this route.', 'mt-ticket-bus'),
                    'noSeatsFound' => __('No seats found for this schedule and date.', 'mt-ticket-bus'),
                    'seat' => __('Seat', 'mt-ticket-bus'),
                    'available' => __('Available', 'mt-ticket-bus'),
                    'reserved' => __('Reserved', 'mt-ticket-bus'),
                    'selected' => __('Selected', 'mt-ticket-bus'),
                    'disabled' => __('Disabled', 'mt-ticket-bus'),
                    'processing' => __('Processing...', 'mt-ticket-bus'),
                    'ok' => __('OK', 'mt-ticket-bus'),
                    'addedToCart' => __('Added to cart', 'mt-ticket-bus'),
                    'addToCartError' => __('Error adding to cart.', 'mt-ticket-bus'),
                    'addToCartErrorRetry' => __('Error adding to cart. Please try again.', 'mt-ticket-bus'),
                    'availableSeats' => __('available seats', 'mt-ticket-bus'),
                    'of' => __('of', 'mt-ticket-bus'),
                    'noAvailableSeats' => __('No available seats', 'mt-ticket-bus'),
                    'loadingError' => __('Error loading layout.', 'mt-ticket-bus'),
                    'invalidLayout' => __('Invalid seat layout.', 'mt-ticket-bus'),
                    'pastDate' => __('Past date', 'mt-ticket-bus'),
                    'unavailableDate' => __('Unavailable date', 'mt-ticket-bus'),
                ),
            )
        );
    }

    /**
     * Add inline CSS as fallback to ensure hiding rule works even if external CSS doesn't load.
     */
    public function add_inline_css_fallback()
    {
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }

        if ($this->is_ticket_product_context()) {
            echo '<style id="mt-ticket-bus-hide-standard-ui">body.mt-is-ticket-product .mt-standard-product-ui { display: none !important; }</style>' . "\n";
        }
    }

    public function render_seatmap_block($attributes = array(), $content = '', $block = null)
    {
        // Use shared renderer
        return MT_Ticket_Bus_Renderer::render_seatmap($block);
    }

    public function render_ticket_summary_block($attributes = array(), $content = '', $block = null)
    {
        // Use shared renderer
        return MT_Ticket_Bus_Renderer::render_ticket_summary($block);
    }
}
