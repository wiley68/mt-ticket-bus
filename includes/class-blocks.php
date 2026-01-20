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

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', array($this, 'register_blocks'));
        // Ensure blocks JS/CSS load in both Post Editor and Site Editor inserter.
        // Use both hooks to ensure script loads in all editor contexts
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_block_editor_assets'));
        // Enqueue frontend styles for CSS rules (hiding standard UI)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        // Also add inline style in head as backup
        add_action('wp_head', array($this, 'add_inline_css_fallback'), 999);
        add_filter('body_class', array($this, 'add_ticket_body_class'));
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
        // Debug: Log block registration
        if (current_user_can('manage_options')) {
            error_log('MT Ticket Bus: register_blocks() called');
        }

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
        // Register block directly with all parameters to ensure render_callback works
        $seatmap_result = register_block_type(
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

        if (current_user_can('manage_options')) {
            error_log('MT Ticket Bus: Seatmap block registered: ' . ($seatmap_result ? 'YES' : 'NO'));
        }

        // 2) Ticket summary block (replaces right summary visually)
        $summary_result = register_block_type(
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

        if (current_user_can('manage_options')) {
            error_log('MT Ticket Bus: Ticket summary block registered: ' . ($summary_result ? 'YES' : 'NO'));
        }
    }

    /**
     * Explicitly enqueue editor assets so blocks show in the inserter in the Site Editor.
     */
    public function enqueue_block_editor_assets()
    {
        // Debug: Log that hook is being called
        if (current_user_can('manage_options')) {
            error_log('MT Ticket Bus: enqueue_block_editor_assets() called');
        }

        // Register script if not already registered
        if (! wp_script_is('mt-ticket-bus-blocks', 'registered')) {
            $script_url = MT_TICKET_BUS_PLUGIN_URL . 'assets/js/blocks.js';
            $script_path = MT_TICKET_BUS_PLUGIN_DIR . 'assets/js/blocks.js';
            
            if (current_user_can('manage_options')) {
                error_log('MT Ticket Bus: Registering script. URL: ' . $script_url . ', File exists: ' . (file_exists($script_path) ? 'YES' : 'NO'));
            }

            wp_register_script(
                'mt-ticket-bus-blocks',
                $script_url,
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

        if (current_user_can('manage_options')) {
            error_log('MT Ticket Bus: Script enqueued. Is registered: ' . (wp_script_is('mt-ticket-bus-blocks', 'registered') ? 'YES' : 'NO'));
        }
    }

    /**
     * Enqueue frontend assets (CSS for hiding standard UI when ticket product).
     */
    public function enqueue_frontend_assets()
    {
        // Only load on single product pages
        if (! function_exists('is_product') || ! is_product()) {
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
        // Get product ID from block context or current post
        $product_id = null;
        
        if ($block && isset($block->context['postId'])) {
            $product_id = $block->context['postId'];
        } elseif (function_exists('get_queried_object_id')) {
            $product_id = get_queried_object_id();
        } elseif (is_singular('product')) {
            global $post;
            $product_id = $post ? $post->ID : null;
        }

        // Debug (temporary) - try multiple methods
        if (current_user_can('manage_options')) {
            $debug_msg = 'MT Ticket Bus: render_seatmap_block called. Product ID: ' . ($product_id ?: 'NULL') . ', Block context: ' . ($block && isset($block->context['postId']) ? $block->context['postId'] : 'N/A');
            error_log($debug_msg);
            // Also output to screen if WP_DEBUG_DISPLAY is true
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
                echo '<!-- ' . esc_html($debug_msg) . ' -->';
            }
        }

        if (! $product_id) {
            return ''; // No product context
        }

        // Check if product is ticket
        $is_ticket = get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes';
        
        if (current_user_can('manage_options')) {
            $debug_msg = 'MT Ticket Bus: Product ID ' . $product_id . ' is ticket: ' . ($is_ticket ? 'YES' : 'NO');
            error_log($debug_msg);
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
                echo '<!-- ' . esc_html($debug_msg) . ' -->';
            }
        }
        
        if (! $is_ticket) {
            return ''; // Not a ticket product
        }

        $output .= '<div class="mt-ticket-block mt-ticket-seatmap-block"><div class="mt-ticket-block__inner"><strong>MT SEATMAP BLOCK</strong><div>Този блок ще замени лявата секция (галерия/снимки).</div></div></div>';
        return $output;
    }

    public function render_ticket_summary_block($attributes = array(), $content = '', $block = null)
    {
        // Always output something for testing - even if not ticket
        $output = '<!-- MT Ticket Bus: render_ticket_summary_block CALLED -->';
        
        // Get product ID from block context or current post
        $product_id = null;
        
        if ($block && isset($block->context['postId'])) {
            $product_id = $block->context['postId'];
        } elseif (function_exists('get_queried_object_id')) {
            $product_id = get_queried_object_id();
        } elseif (is_singular('product')) {
            global $post;
            $product_id = $post ? $post->ID : null;
        }

        // Debug (temporary) - try multiple methods
        if (current_user_can('manage_options')) {
            $debug_msg = 'MT Ticket Bus: render_ticket_summary_block called. Product ID: ' . ($product_id ?: 'NULL') . ', Block context: ' . ($block && isset($block->context['postId']) ? $block->context['postId'] : 'N/A');
            error_log($debug_msg);
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
                echo '<!-- ' . esc_html($debug_msg) . ' -->';
            }
        }

        if (! $product_id) {
            return ''; // No product context
        }

        // Check if product is ticket
        $is_ticket = get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes';
        
        if (current_user_can('manage_options')) {
            $debug_msg = 'MT Ticket Bus: Product ID ' . $product_id . ' is ticket: ' . ($is_ticket ? 'YES' : 'NO');
            error_log($debug_msg);
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
                echo '<!-- ' . esc_html($debug_msg) . ' -->';
            }
        }
        
        if (! $is_ticket) {
            return ''; // Not a ticket product
        }

        $output .= '<div class="mt-ticket-block mt-ticket-summary-block"><div class="mt-ticket-block__inner"><strong>MT TICKET SUMMARY BLOCK</strong><div>Този блок ще замени дясната секция (инфо + бутон).</div></div></div>';
        return $output;
    }
}

