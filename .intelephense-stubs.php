<?php

/**
 * Intelephense Stubs for WordPress Functions
 * 
 * This file provides stub definitions for WordPress functions used in the plugin
 * to prevent Intelephense from showing false errors in the editor.
 * 
 * @package MT_Ticket_Bus
 */

// WordPress Constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

// WordPress Constants
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// WordPress Core Functions

/**
 * Hook a function or method to a specific filter action.
 *
 * @param string   $tag             The name of the filter to hook the $function_to_add to.
 * @param callable $function_to_add  The name of the function to be called when the filter is applied.
 * @param int      $priority         Optional. Used to specify the order in which the functions
 *                                   associated with a particular action are executed. Default 10.
 * @param int      $accepted_args    Optional. The number of arguments the function accepts. Default 1.
 * @return true
 */
function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1)
{
    return true;
}

/**
 * Hook a function or method to a specific filter action.
 *
 * @param string   $tag             The name of the filter to hook the $function_to_add to.
 * @param callable $function_to_add The name of the function to be called when the filter is applied.
 * @param int      $priority        Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed. Default 10.
 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
 * @return true
 */
function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
{
    return true;
}

/**
 * Register a plugin activation hook.
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callable $function The function hooked to the 'activate_PLUGINFILE' action.
 */
function register_activation_hook($file, $function) {}

/**
 * Retrieve the filesystem directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string The filesystem path of the directory that contains the plugin.
 */
function plugin_dir_path($file)
{
    return '';
}

/**
 * Retrieve a URL path to the plugin directory.
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string URL path to the plugin directory.
 */
function plugin_dir_url($file)
{
    return '';
}

/**
 * Gets the basename of a plugin.
 *
 * @param string $file The path to the plugin file.
 * @return string The plugin basename.
 */
function plugin_basename($file)
{
    return '';
}

/**
 * Check if the current request is for an administrative interface page.
 *
 * @return bool True if inside WordPress administration interface, false otherwise.
 */
function is_admin()
{
    return false;
}

/**
 * Load a plugin's translated strings.
 *
 * @param string $domain          Unique identifier for retrieving translated strings.
 * @param string|false $deprecated Optional. Deprecated. Use the $plugin_rel_path parameter instead.
 *                                Default false.
 * @param string|false $plugin_rel_path Optional. Relative path to WP_PLUGIN_DIR where the .mo file resides.
 *                                     Default false.
 * @return bool True when textdomain is successfully loaded, false otherwise.
 */
function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
{
    return true;
}

/**
 * Add a top-level menu page.
 *
 * @param string   $page_title The text to be displayed in the title tags of the page when the menu is selected.
 * @param string   $menu_title The text to be used for the menu.
 * @param string   $capability The capability required for this menu to be displayed to the user.
 * @param string   $menu_slug  The slug name to refer to this menu by (should be unique for this menu).
 * @param callable $function   The function to be called to output the content for this page.
 * @param string   $icon_url   Optional. The URL to the icon to be used for this menu.
 * @param int      $position   Optional. The position in the menu order this item should appear.
 * @return string The resulting page's hook_suffix.
 */
function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null)
{
    return '';
}

/**
 * Add a submenu page.
 *
 * @param string   $parent_slug The slug name for the parent menu (or the file name of a standard
 *                              WordPress admin page).
 * @param string   $page_title  The text to be displayed in the title tags of the page when the menu is selected.
 * @param string   $menu_title  The text to be used for the menu.
 * @param string   $capability  The capability required for this menu to be displayed to the user.
 * @param string   $menu_slug   The slug name to refer to this menu by (should be unique for this menu).
 * @param callable $function    Optional. The function to be called to output the content for this page.
 * @param int      $position    Optional. The position in the menu order this item should appear.
 * @return string|false The resulting page's hook_suffix, or false if the user does not have the capability required.
 */
function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null)
{
    return '';
}

/**
 * Enqueue a CSS stylesheet.
 *
 * @param string           $handle Name of the stylesheet. Should be unique.
 * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on.
 * @param string|bool|null $ver    Optional. String specifying stylesheet version number.
 * @param string           $media  Optional. The media for which this stylesheet has been defined.
 * @return bool Whether the style has been registered. True on success, false on failure.
 */
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
    return true;
}

/**
 * Enqueue a script.
 *
 * @param string           $handle    Name of the script. Should be unique.
 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 * @param string[]         $deps      Optional. An array of registered script handles this script depends on.
 * @param string|bool|null $ver       Optional. String specifying script version number.
 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 * @return bool Whether the script has been registered. True on success, false on failure.
 */
function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false)
{
    return true;
}

/**
 * Localize a script.
 *
 * @param string $handle      Script handle the data will be attached to.
 * @param string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
 * @param array  $l10n        The data itself. The data can be either a single or multi-dimensional array.
 * @return bool True on success, false on failure.
 */
function wp_localize_script($handle, $object_name, $l10n)
{
    return true;
}

/**
 * Retrieve the admin area URL path with optional path appended.
 *
 * @param string $path   Optional. Path relative to the admin URL.
 * @param string $scheme Optional. The scheme to use. Default is 'admin', which obeys force_ssl_admin()
 *                      and is_ssl(). 'http' or 'https' can be passed to force those schemes.
 * @return string Admin URL link with optional path appended.
 */
function admin_url($path = '', $scheme = 'admin')
{
    return '';
}

/**
 * Retrieve the admin page title.
 *
 * @return string The admin page title.
 */
function get_admin_page_title()
{
    return '';
}

/**
 * Create a cryptographic token tied to a specific action, user, user session, and window of time.
 *
 * @param string|int $action   Scalar value to add context to the nonce.
 * @param string     $name     Optional. Nonce name. Default '_wpnonce'.
 * @param bool       $referer  Optional. Whether to set the referer field for validation. Default true.
 * @param bool       $echo     Optional. Whether to display or return hidden form field. Default true.
 * @return string The nonce field HTML markup.
 */
function wp_create_nonce($action = -1, $name = '_wpnonce', $referer = true, $echo = true)
{
    return '';
}

/**
 * Display or retrieve hidden form field with nonce value.
 *
 * @param string|int $action  Optional. Action name. Default -1.
 * @param string     $name    Optional. Nonce name. Default '_wpnonce'.
 * @param bool       $referer Optional. Whether to set the referer field for validation. Default true.
 * @param bool       $echo    Optional. Whether to display or return hidden form field. Default true.
 * @return string Nonce field HTML markup.
 */
function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true)
{
    return '';
}

/**
 * Verify that a nonce is correct and unexpired with the respect to a specified action.
 *
 * @param string     $nonce  Nonce value that was used for verification.
 * @param string|int $action Should give context to what is taking place and be the same when nonce was created.
 * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
 *                   2 if the nonce is valid and generated between 12-24 hours ago.
 *                   False if the nonce is invalid.
 */
function wp_verify_nonce($nonce, $action = -1)
{
    return true;
}

/**
 * Verify the AJAX request to prevent processing requests external of the blog.
 *
 * @param string|int $action    Action nonce.
 * @param string     $query_arg Optional. Key to check for the nonce in `$_REQUEST`. Default '_ajax_nonce'.
 * @param bool       $die       Optional. Whether to die early when the nonce cannot be verified. Default true.
 * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
 *                   2 if the nonce is valid and generated between 12-24 hours ago.
 *                   False if the nonce is invalid.
 */
function check_ajax_referer($action = -1, $query_arg = '_ajax_nonce', $die = true)
{
    return true;
}

/**
 * Verify the nonce for the current request.
 *
 * @param string     $action    Action nonce.
 * @param string     $query_arg Optional. Key to check for the nonce in `$_REQUEST`. Default '_wpnonce'.
 * @param bool       $die       Optional. Whether to die early when the nonce cannot be verified. Default true.
 * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
 *                   2 if the nonce is valid and generated between 12-24 hours ago.
 *                   False if the nonce is invalid.
 */
function check_admin_referer($action = -1, $query_arg = '_wpnonce', $die = true)
{
    return true;
}

/**
 * Check whether the current user has a specific capability.
 *
 * @param string $capability Capability name.
 * @param mixed  $args       Optional. Further parameters, typically starting with an object ID.
 * @return bool Whether the current user has the given capability.
 */
function current_user_can($capability, ...$args)
{
    return true;
}

/**
 * Send a JSON response back to an Ajax request, indicating success.
 *
 * @param mixed $data Optional. Data to encode as JSON, then print and die. Default null.
 * @param int   $status_code Optional. The HTTP status code to output. Default null.
 */
function wp_send_json_success($data = null, $status_code = null) {}

/**
 * Send a JSON response back to an Ajax request, indicating failure.
 *
 * @param mixed $data Optional. Data to encode as JSON, then print and die. Default null.
 * @param int   $status_code Optional. The HTTP status code to output. Default null.
 */
function wp_send_json_error($data = null, $status_code = null) {}

/**
 * Retrieve option value based on name of option.
 *
 * @param string $option  Name of option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default Optional. Default value to return if the option does not exist.
 * @return mixed Value set for the option.
 */
function get_option($option, $default = false)
{
    return $default;
}

/**
 * Update the value of an option that was already added.
 *
 * @param string      $option   Option name. Expected to not be SQL-escaped.
 * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up.
 * @return bool True if the value was updated, false otherwise.
 */
function update_option($option, $value, $autoload = null)
{
    return true;
}

/**
 * Merge user defined arguments into defaults array.
 *
 * @param array|object $args     Value to merge with $defaults.
 * @param array|object $defaults  Array that serves as the defaults.
 * @return array|object Merged user defined values with defaults.
 */
function wp_parse_args($args, $defaults = '')
{
    return array();
}

/**
 * Sanitize a string from user input or from the database.
 *
 * @param string $str String to sanitize.
 * @return string Sanitized string.
 */
function sanitize_text_field($str)
{
    return '';
}

/**
 * Sanitize a textarea field from user input or from the database.
 *
 * @param string $str String to sanitize.
 * @return string Sanitized string.
 */
function sanitize_textarea_field($str)
{
    return '';
}

/**
 * Convert a value to non-negative integer.
 *
 * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
 * @return int A non-negative integer.
 */
function absint($maybeint)
{
    return 0;
}

/**
 * Escape data for use in a MySQL query.
 *
 * @param string|array $data Data to escape.
 * @return string|array Escaped data.
 */
function esc_sql($data)
{
    return $data;
}

/**
 * Retrieve post meta field for a post.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Optional. Whether to return a single value. Default false.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function get_post_meta($post_id, $key = '', $single = false)
{
    return '';
}

/**
 * Update a post meta field based on the given post ID.
 *
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before updating.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '')
{
    return true;
}

/**
 * Display translated text.
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 * @return string Translated text.
 */
function __($text, $domain = 'default')
{
    return $text;
}

/**
 * Display translated text with context.
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 * @return string Translated text.
 */
function _x($text, $context, $domain = 'default')
{
    return $text;
}

/**
 * Retrieve the translation of $text.
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 * @return string Translated text.
 */
function esc_html__($text, $domain = 'default')
{
    return $text;
}

/**
 * Display translated text that has been escaped for safe use in HTML output.
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 */
function esc_html_e($text, $domain = 'default') {}

/**
 * Escape and translate a string.
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 * @return string Translated text.
 */
function esc_attr__($text, $domain = 'default')
{
    return $text;
}

/**
 * Display translated text that has been escaped for safe use in an attribute.
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 */
function esc_attr_e($text, $domain = 'default') {}

/**
 * Escape for HTML blocks.
 *
 * @param string $text
 * @return string
 */
function esc_html($text)
{
    return '';
}

/**
 * Escape for HTML attributes.
 *
 * @param string $text
 * @return string
 */
function esc_attr($text)
{
    return '';
}

/**
 * Escape for textarea elements.
 *
 * @param string $text
 * @return string
 */
function esc_textarea($text)
{
    return '';
}

/**
 * Escape URL for use in an attribute.
 *
 * @param string $url
 * @return string
 */
function esc_url($url)
{
    return '';
}

/**
 * Escape URL for use in JavaScript.
 *
 * @param string $url
 * @return string
 */
function esc_js($text)
{
    return '';
}

/**
 * Compare two values and output selected attribute.
 *
 * @param mixed $selected One of the values to compare.
 * @param mixed $current  The other value to compare if true.
 * @param bool  $echo     Whether to echo or just return the string. Default true.
 * @return string HTML attribute or empty string.
 */
function selected($selected, $current, $echo = true)
{
    return '';
}

/**
 * Output or return a submit button.
 *
 * @param string       $text             The text of the button (defaults to 'Save Changes').
 * @param string       $type             The type of button. Accepts 'primary', 'secondary', 'delete'. Default 'primary'.
 * @param string       $name             The HTML name of the submit button. Default 'submit'.
 * @param bool         $wrap             Whether the output button should be wrapped in a paragraph tag. Default true.
 * @param array|string $other_attributes Other attributes that should be output with the button, mapping attributes to their values.
 * @return string Submit button HTML.
 */
function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null)
{
    return '';
}

/**
 * Returns a numerically indexed array containing all defined timezone identifiers.
 *
 * @param int $what One of DateTimeZone class constants.
 * @param string $country A two-letter ISO 3166-1 compatible country code.
 * @return array List of timezone identifiers.
 */
function timezone_identifiers_list($what = 2047, $country = null)
{
    return array();
}

/**
 * Create database tables.
 *
 * @param string $delta SQL statements to execute.
 */
function dbDelta($delta) {}

/**
 * Check if a class exists.
 *
 * @param string $class_name The class name. The name is matched in a case-insensitive manner.
 * @param bool   $autoload   Whether or not to call __autoload by default.
 * @return bool True if the class exists, false otherwise.
 */
function class_exists($class_name, $autoload = true)
{
    return false;
}

// WordPress Classes

/**
 * WordPress Post class
 */
class WP_Post
{
    /**
     * @var int
     */
    public $ID = 0;

    /**
     * @var string
     */
    public $post_title = '';
}

// WooCommerce Classes

/**
 * WooCommerce Product class
 */
class WC_Product
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * Get product ID
     *
     * @return int
     */
    public function get_id()
    {
        return 0;
    }
}

// WooCommerce Functions

/**
 * Output a select input box.
 *
 * @param array $field Field data.
 */
function woocommerce_wp_select($field) {}

/**
 * Get WooCommerce product object.
 *
 * @param int|WP_Post|WC_Product|null $product Product ID, post object, or product object.
 * @return WC_Product|false Product object or false on failure.
 */
function wc_get_product($product = null)
{
    return false;
}

// Global Variables

/**
 * @var wpdb
 */
global $wpdb;

/**
 * WordPress database abstraction object.
 */
class wpdb
{
    /**
     * @var string
     */
    public $prefix = 'wp_';

    /**
     * @var string
     */
    public $last_error = '';

    /**
     * @var int
     */
    public $num_rows = 0;

    /**
     * @var int
     */
    public $insert_id = 0;

    /**
     * Retrieve the character set for the current table.
     *
     * @return string The current character set.
     */
    public function get_charset_collate()
    {
        return '';
    }

    /**
     * Retrieve one row from the database.
     *
     * @param string|null $query  SQL query.
     * @param string      $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N.
     * @return array|object|null|void Database query result.
     */
    public function get_row($query = null, $output = OBJECT, $y = 0)
    {
        return null;
    }

    /**
     * Retrieve an entire SQL result set from the database.
     *
     * @param string|null $query  SQL query.
     * @param string      $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N.
     * @return array Database query results.
     */
    public function get_results($query = null, $output = OBJECT)
    {
        return array();
    }

    /**
     * Insert a row into a table.
     *
     * @param string       $table  Table name.
     * @param array        $data   Data to insert (in column => value pairs).
     * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data.
     * @return int|false The number of rows inserted, or false on error.
     */
    public function insert($table, $data, $format = null)
    {
        return 0;
    }

    /**
     * Update a row in the table.
     *
     * @param string       $table        Table name.
     * @param array        $data         Data to update (in column => value pairs).
     * @param array        $where        A named array of WHERE clauses (in column => value pairs).
     * @param array|string $format       Optional. An array of formats to be mapped to each of the values in $data.
     * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
     * @return int|false The number of rows updated, or false on error.
     */
    public function update($table, $data, $where, $format = null, $where_format = null)
    {
        return 0;
    }

    /**
     * Delete a row in the table.
     *
     * @param string       $table        Table name.
     * @param array        $where        A named array of WHERE clauses (in column => value pairs).
     * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
     * @return int|false The number of rows updated, or false on error.
     */
    public function delete($table, $where, $where_format = null)
    {
        return 0;
    }

    /**
     * Prepare a SQL query for safe execution.
     *
     * @param string $query   Query statement with sprintf()-like placeholders.
     * @param mixed  ...$args The arguments to be inserted into the placeholders.
     * @return string|void Sanitized query string.
     */
    public function prepare($query, ...$args)
    {
        return '';
    }
}
