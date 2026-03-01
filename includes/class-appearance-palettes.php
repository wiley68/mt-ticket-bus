<?php

/**
 * Appearance palettes for ticket product blocks
 *
 * Defines predefined color palettes and effective colors from settings.
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Ticket_Bus_Appearance_Palettes
 *
 * @since 1.0.0
 */
class MT_Ticket_Bus_Appearance_Palettes
{

    /**
     * Color keys used in product page blocks (CSS variables and form).
     *
     * @since 1.0.0
     * @var string[]
     */
    /**
     * Human-readable labels for color keys (admin form).
     *
     * @since 1.0.0
     * @return string[]
     */
    public static function get_color_labels()
    {
        return array(
            'primary' => __('Primary (buttons, links)', 'mt-ticket-bus'),
            'primary_hover' => __('Primary hover', 'mt-ticket-bus'),
            'primary_light' => __('Primary light background', 'mt-ticket-bus'),
            'success' => __('Success (available, confirm)', 'mt-ticket-bus'),
            'success_light' => __('Success light background', 'mt-ticket-bus'),
            'error' => __('Error (unavailable, danger)', 'mt-ticket-bus'),
            'error_light' => __('Error light background', 'mt-ticket-bus'),
            'seatmap_bg_start' => __('Seatmap block gradient start', 'mt-ticket-bus'),
            'seatmap_bg_end' => __('Seatmap block gradient end', 'mt-ticket-bus'),
            'summary_bg_start' => __('Summary block gradient start', 'mt-ticket-bus'),
            'summary_bg_end' => __('Summary block gradient end', 'mt-ticket-bus'),
            'text' => __('Text', 'mt-ticket-bus'),
            'text_muted' => __('Text muted', 'mt-ticket-bus'),
            'border' => __('Border', 'mt-ticket-bus'),
            'bg' => __('Background (cards)', 'mt-ticket-bus'),
        );
    }

    /**
     * Color keys used in product page blocks (CSS variables and form).
     *
     * @since 1.0.0
     * @return string[]
     */
    public static function get_color_keys()
    {
        return array(
            'primary',
            'primary_hover',
            'primary_light',
            'success',
            'success_light',
            'error',
            'error_light',
            'seatmap_bg_start',
            'seatmap_bg_end',
            'summary_bg_start',
            'summary_bg_end',
            'text',
            'text_muted',
            'border',
            'bg',
        );
    }

    /**
     * Get predefined palettes (id => label + colors).
     *
     * Palette 1 = current/default colors from blocks.css.
     *
     * @since 1.0.0
     * @return array[]
     */
    public static function get_palettes()
    {
        return array(
            '1' => array(
                'label' => __('Default (blue)', 'mt-ticket-bus'),
                'colors' => array(
                    'primary' => '#3b82f6',
                    'primary_hover' => '#2563eb',
                    'primary_light' => '#eff6ff',
                    'success' => '#10b981',
                    'success_light' => '#d1fae5',
                    'error' => '#ef4444',
                    'error_light' => '#fee2e2',
                    'seatmap_bg_start' => '#e0f2fe',
                    'seatmap_bg_end' => '#ecfdf5',
                    'summary_bg_start' => '#f5f3ff',
                    'summary_bg_end' => '#fff7ed',
                    'text' => '#1f2937',
                    'text_muted' => '#6b7280',
                    'border' => '#e5e7eb',
                    'bg' => '#ffffff',
                ),
            ),
            '2' => array(
                'label' => __('Warm (orange)', 'mt-ticket-bus'),
                'colors' => array(
                    'primary' => '#ea580c',
                    'primary_hover' => '#c2410c',
                    'primary_light' => '#ffedd5',
                    'success' => '#059669',
                    'success_light' => '#d1fae5',
                    'error' => '#dc2626',
                    'error_light' => '#fee2e2',
                    'seatmap_bg_start' => '#fff7ed',
                    'seatmap_bg_end' => '#fef3c7',
                    'summary_bg_start' => '#fef3c7',
                    'summary_bg_end' => '#fde68a',
                    'text' => '#1f2937',
                    'text_muted' => '#6b7280',
                    'border' => '#e5e7eb',
                    'bg' => '#ffffff',
                ),
            ),
            '3' => array(
                'label' => __('Nature (green)', 'mt-ticket-bus'),
                'colors' => array(
                    'primary' => '#059669',
                    'primary_hover' => '#047857',
                    'primary_light' => '#d1fae5',
                    'success' => '#059669',
                    'success_light' => '#a7f3d0',
                    'error' => '#dc2626',
                    'error_light' => '#fee2e2',
                    'seatmap_bg_start' => '#ecfdf5',
                    'seatmap_bg_end' => '#d1fae5',
                    'summary_bg_start' => '#d1fae5',
                    'summary_bg_end' => '#a7f3d0',
                    'text' => '#1f2937',
                    'text_muted' => '#6b7280',
                    'border' => '#d1d5db',
                    'bg' => '#ffffff',
                ),
            ),
            '4' => array(
                'label' => __('Slate (neutral)', 'mt-ticket-bus'),
                'colors' => array(
                    'primary' => '#475569',
                    'primary_hover' => '#334155',
                    'primary_light' => '#f1f5f9',
                    'success' => '#0d9488',
                    'success_light' => '#ccfbf1',
                    'error' => '#dc2626',
                    'error_light' => '#fee2e2',
                    'seatmap_bg_start' => '#f1f5f9',
                    'seatmap_bg_end' => '#e2e8f0',
                    'summary_bg_start' => '#e2e8f0',
                    'summary_bg_end' => '#cbd5e1',
                    'text' => '#1e293b',
                    'text_muted' => '#64748b',
                    'border' => '#e2e8f0',
                    'bg' => '#ffffff',
                ),
            ),
        );
    }

    /**
     * Get effective appearance colors (saved overrides or selected palette).
     *
     * @since 1.0.0
     * @return array Color key => hex value.
     */
    public static function get_effective_colors()
    {
        $settings = get_option('mt_ticket_bus_settings', array());
        $palettes = self::get_palettes();
        $keys = self::get_color_keys();

        $saved = isset($settings['appearance_colors']) && is_array($settings['appearance_colors'])
            ? $settings['appearance_colors']
            : array();
        $palette_id = isset($settings['appearance_palette']) ? $settings['appearance_palette'] : '1';
        $base = isset($palettes[$palette_id]['colors']) ? $palettes[$palette_id]['colors'] : $palettes['1']['colors'];

        $out = array();
        foreach ($keys as $key) {
            if (isset($saved[$key]) && self::is_valid_hex($saved[$key])) {
                $out[$key] = self::normalize_hex($saved[$key]);
            } elseif (isset($base[$key])) {
                $out[$key] = $base[$key];
            }
        }

        return $out;
    }

    /**
     * Check if string is a valid hex color.
     *
     * @since 1.0.0
     * @param string $value Value to check.
     * @return bool
     */
    public static function is_valid_hex($value)
    {
        if (! is_string($value) || $value === '') {
            return false;
        }
        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value);
    }

    /**
     * Normalize hex to 6-digit lowercase.
     *
     * @since 1.0.0
     * @param string $hex Hex color.
     * @return string
     */
    public static function normalize_hex($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return '#' . strtolower($hex);
    }
}
