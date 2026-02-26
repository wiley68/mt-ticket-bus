<?php

/**
 * Extras management class.
 *
 * Handles CRUD operations for ticket extras stored in the custom database table.
 *
 * @package MT_Ticket_Bus
 * @since 1.0.13
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Extras class.
 *
 * @since 1.0.13
 */
class MT_Ticket_Bus_Extras
{
    /**
     * Plugin instance.
     *
     * @since 1.0.13
     *
     * @var MT_Ticket_Bus_Extras
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @since 1.0.13
     *
     * @return MT_Ticket_Bus_Extras Plugin instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get extras table name.
     *
     * @since 1.0.13
     *
     * @return string
     */
    private function get_table()
    {
        return MT_Ticket_Bus_Database::get_extras_table();
    }

    /**
     * Get all extras.
     *
     * @since 1.0.13
     *
     * @param array $args Optional. Arguments to filter extras.
     *                    - status: 'active', 'inactive', 'all'. Default 'active'.
     * @return array List of extras as stdClass objects.
     */
    public function get_all_extras($args = array())
    {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
        );

        $args = wp_parse_args($args, $defaults);

        $table  = $this->get_table();
        $where  = '1=1';
        $params = array();

        if ('all' !== $args['status']) {
            $where     .= ' AND status = %s';
            $params[] = $args['status'];
        }

        $sql = "SELECT * FROM $table WHERE $where ORDER BY name ASC";

        if (! empty($params)) {
            $prepared = $wpdb->prepare($sql, $params);
        } else {
            $prepared = $sql;
        }

        $results = $wpdb->get_results($prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return is_array($results) ? $results : array();
    }

    /**
     * Get single extra by ID.
     *
     * @since 1.0.13
     *
     * @param int $id Extra ID.
     * @return object|null
     */
    public function get_extra($id)
    {
        global $wpdb;

        $id = absint($id);
        if ($id <= 0) {
            return null;
        }

        $table = $this->get_table();

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        );

        $extra = $wpdb->get_row($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return $extra ? $extra : null;
    }

    /**
     * Create a new extra.
     *
     * @since 1.0.13
     *
     * @param array $data Extra data.
     * @return int|WP_Error Inserted ID on success or WP_Error on failure.
     */
    public function create_extra($data)
    {
        global $wpdb;

        $sanitized = $this->sanitize_extra_data($data);
        if (is_wp_error($sanitized)) {
            return $sanitized;
        }

        $table = $this->get_table();

        $inserted = $wpdb->insert(
            $table,
            array(
                'name'   => $sanitized['name'],
                'code'   => $sanitized['code'],
                'price'  => $sanitized['price'],
                'status' => $sanitized['status'],
            ),
            array(
                '%s',
                '%s',
                '%f',
                '%s',
            )
        );

        if (false === $inserted) {
            return new WP_Error(
                'mt_ticket_extra_create_failed',
                esc_html__('Failed to create extra.', 'mt-ticket-bus')
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update existing extra.
     *
     * @since 1.0.13
     *
     * @param int   $id   Extra ID.
     * @param array $data Extra data.
     * @return bool|WP_Error True on success or WP_Error on failure.
     */
    public function update_extra($id, $data)
    {
        global $wpdb;

        $id = absint($id);
        if ($id <= 0) {
            return new WP_Error(
                'mt_ticket_extra_invalid_id',
                esc_html__('Invalid extra ID.', 'mt-ticket-bus')
            );
        }

        $sanitized = $this->sanitize_extra_data($data, $id);
        if (is_wp_error($sanitized)) {
            return $sanitized;
        }

        $table = $this->get_table();

        $updated = $wpdb->update(
            $table,
            array(
                'name'   => $sanitized['name'],
                'code'   => $sanitized['code'],
                'price'  => $sanitized['price'],
                'status' => $sanitized['status'],
            ),
            array(
                'id' => $id,
            ),
            array(
                '%s',
                '%s',
                '%f',
                '%s',
            ),
            array(
                '%d',
            )
        );

        if (false === $updated) {
            return new WP_Error(
                'mt_ticket_extra_update_failed',
                esc_html__('Failed to update extra.', 'mt-ticket-bus')
            );
        }

        return true;
    }

    /**
     * Delete extra.
     *
     * Currently performs a hard delete.
     *
     * @since 1.0.13
     *
     * @param int $id Extra ID.
     * @return bool|WP_Error
     */
    public function delete_extra($id)
    {
        global $wpdb;

        $id = absint($id);
        if ($id <= 0) {
            return new WP_Error(
                'mt_ticket_extra_invalid_id',
                esc_html__('Invalid extra ID.', 'mt-ticket-bus')
            );
        }

        $table = $this->get_table();

        $deleted = $wpdb->delete(
            $table,
            array(
                'id' => $id,
            ),
            array(
                '%d',
            )
        );

        if (false === $deleted) {
            return new WP_Error(
                'mt_ticket_extra_delete_failed',
                esc_html__('Failed to delete extra.', 'mt-ticket-bus')
            );
        }

        return true;
    }

    /**
     * Get extras map for select fields.
     *
     * @since 1.0.13
     *
     * @param array $args Optional. Filter args.
     * @return array Array of id => label (name + price).
     */
    public function get_extras_options($args = array())
    {
        $extras = $this->get_all_extras($args);
        $options = array();

        foreach ($extras as $extra) {
            // Използваме стандартната PHP функция за да избегнем зависимост от WP helper в инструментите.
            $price = number_format((float) $extra->price, 2, '.', '');
            $label = sprintf(
                /* translators: 1: Extra name, 2: Extra price */
                __('%1$s (+%2$s)', 'mt-ticket-bus'),
                $extra->name,
                $price
            );
            $options[(int) $extra->id] = $label;
        }

        return $options;
    }

    /**
     * Sanitize and validate extra data.
     *
     * @since 1.0.13
     *
     * @param array    $data Raw data.
     * @param int|null $extra_id Optional. Existing extra ID for unique checks.
     * @return array|WP_Error
     */
    private function sanitize_extra_data($data, $extra_id = null)
    {
        global $wpdb;

        $name = isset($data['name']) ? sanitize_text_field(wp_unslash($data['name'])) : '';
        $code = isset($data['code']) ? sanitize_text_field(wp_unslash($data['code'])) : '';
        $price_raw = isset($data['price']) ? (string) $data['price'] : '0';
        $status = isset($data['status']) ? sanitize_text_field(wp_unslash($data['status'])) : 'active';

        if ('' === $name) {
            return new WP_Error(
                'mt_ticket_extra_name_required',
                esc_html__('Extra name is required.', 'mt-ticket-bus')
            );
        }

        $price = floatval($price_raw);
        if ($price < 0) {
            return new WP_Error(
                'mt_ticket_extra_price_invalid',
                esc_html__('Extra price must not be negative.', 'mt-ticket-bus')
            );
        }

        if ('inactive' !== $status) {
            $status = 'active';
        }

        // Ensure code is unique if provided.
        if ('' !== $code) {
            $table = $this->get_table();

            if ($extra_id) {
                $sql = $wpdb->prepare(
                    "SELECT id FROM $table WHERE code = %s AND id != %d",
                    $code,
                    $extra_id
                );
            } else {
                $sql = $wpdb->prepare(
                    "SELECT id FROM $table WHERE code = %s",
                    $code
                );
            }

            $existing_id = $wpdb->get_var($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

            if ($existing_id) {
                return new WP_Error(
                    'mt_ticket_extra_code_exists',
                    esc_html__('Extra code must be unique.', 'mt-ticket-bus')
                );
            }
        }

        return array(
            'name'   => $name,
            'code'   => $code,
            'price'  => $price,
            'status' => $status,
        );
    }
}
