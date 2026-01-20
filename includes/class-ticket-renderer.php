<?php
/**
 * Ticket product renderer class
 *
 * Shared rendering logic for ticket products (used by both block themes and standard themes)
 *
 * @package MT_Ticket_Bus
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Ticket Renderer class
 */
class MT_Ticket_Bus_Renderer
{
    /**
     * Get product ID from various contexts
     *
     * @param mixed $block Block object (for block themes) or null
     * @return int|null Product ID or null if not found
     */
    public static function get_product_id($block = null)
    {
        // Try block context first (for block themes)
        if ($block && isset($block->context['postId'])) {
            return (int) $block->context['postId'];
        }
        
        // Try queried object
        if (function_exists('get_queried_object_id')) {
            $product_id = get_queried_object_id();
            if ($product_id) {
                return (int) $product_id;
            }
        }
        
        // Try global post
        if (is_singular('product')) {
            global $post;
            if ($post && isset($post->ID)) {
                return (int) $post->ID;
            }
        }
        
        return null;
    }

    /**
     * Check if product is a ticket product
     *
     * @param int $product_id Product ID
     * @return bool True if product is a ticket product
     */
    public static function is_ticket_product($product_id)
    {
        if (! $product_id) {
            return false;
        }

        return get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes';
    }

    /**
     * Render seatmap section (replaces gallery/images)
     *
     * @param mixed $block Block object (for block themes) or null
     * @return string HTML output
     */
    public static function render_seatmap($block = null)
    {
        $product_id = self::get_product_id($block);
        
        if (! $product_id) {
            return ''; // No product context
        }

        if (! self::is_ticket_product($product_id)) {
            return ''; // Not a ticket product
        }

        // TODO: Replace with actual seatmap rendering logic
        $output = '<div class="mt-ticket-block mt-ticket-seatmap-block">';
        $output .= '<div class="mt-ticket-block__inner">';
        $output .= '<strong>MT SEATMAP BLOCK</strong>';
        $output .= '<div>Този блок ще замени лявата секция (галерия/снимки).</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render ticket summary section (replaces right summary/info/button)
     *
     * @param mixed $block Block object (for block themes) or null
     * @return string HTML output
     */
    public static function render_ticket_summary($block = null)
    {
        $product_id = self::get_product_id($block);
        
        if (! $product_id) {
            return ''; // No product context
        }

        if (! self::is_ticket_product($product_id)) {
            return ''; // Not a ticket product
        }

        // TODO: Replace with actual ticket summary rendering logic
        $output = '<div class="mt-ticket-block mt-ticket-summary-block">';
        $output .= '<div class="mt-ticket-block__inner">';
        $output .= '<strong>MT TICKET SUMMARY BLOCK</strong>';
        $output .= '<div>Този блок ще замени дясната секция (инфо + бутон).</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
}
