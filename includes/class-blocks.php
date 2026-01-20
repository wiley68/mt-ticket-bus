<?php
/**
 * Gutenberg blocks for ticket product UI (block themes)
 *
 * @package MT_Ticket_Bus
 */

if (! defined('ABSPATH')) {
    exit;
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
        if (isset($parsed_block['blockName']) && 
            ($parsed_block['blockName'] === 'mt-ticket-bus/seatmap' || 
             $parsed_block['blockName'] === 'mt-ticket-bus/ticket-summary')) {
            
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
            array('jquery'),
            MT_TICKET_BUS_VERSION,
            true
        );

        // Localize script for AJAX (shared by both scripts)
        wp_localize_script(
            'mt-ticket-bus-seatmap',
            'mtTicketBus',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ticket_bus_frontend'),
            )
        );
        
        // Also localize for ticket-summary script
        wp_localize_script(
            'mt-ticket-bus-ticket-summary',
            'mtTicketBus',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ticket_bus_frontend'),
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
