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
if (!defined('DB_NAME')) {
    define('DB_NAME', '');
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
 * Call the functions added to a filter hook.
 *
 * @param string $tag     The name of the filter hook.
 * @param mixed  $value   The value to filter.
 * @param mixed  ...$args Additional parameters to pass to the callback functions.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function apply_filters($tag, $value, ...$args)
{
    return $value;
}

/**
 * Remove a function from a specified action hook.
 *
 * @param string   $tag                The action hook to which the function to be removed is hooked.
 * @param callable $function_to_remove The name of the function which should be removed.
 * @param int      $priority           Optional. The priority of the function. Default 10.
 * @return bool Whether the function was removed.
 */
function remove_action($tag, $function_to_remove, $priority = 10)
{
    return true;
}

/**
 * Check if any action has been registered for a hook.
 *
 * @param string        $tag               The name of the action hook.
 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
 * @return int|bool The priority of that hook is returned, or false if the function is not attached, or true if the function is attached but priority cannot be determined.
 */
function has_action($tag, $function_to_check = false)
{
    return false;
}

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

/**
 * Retrieve the ID of the current item in the WordPress Loop.
 *
 * @return int|false The ID of the current item. Default false.
 */
function get_the_ID()
{
    return 0;
}

/**
 * Retrieve the ID of the currently queried object.
 *
 * @return int The ID of the currently queried object.
 */
function get_queried_object_id()
{
    return 0;
}

/**
 * Is the query for an existing single product?
 *
 * @return bool True when viewing a single product.
 */
function is_product()
{
    return false;
}

/**
 * Determines whether the query is for an existing single post of any post type.
 *
 * @param string|string[] $post_types Optional. Post type or array of post types to check against.
 * @return bool Whether the query is for an existing single post of any of the given post types.
 */
function is_singular($post_types = '')
{
    return false;
}

/**
 * Register a plugin activation hook.
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callable $function The function hooked to the 'activate_PLUGINFILE' action.
 */
function register_activation_hook($file, $function) {}

/**
 * Register a plugin uninstall hook.
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callable $function The function to be called when the plugin is uninstalled.
 */
function register_uninstall_hook($file, $function) {}

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
 * Determines whether the current theme is a block theme.
 *
 * @since 5.9.0
 * @return bool True if the current theme is a block theme, false otherwise.
 */
function wp_is_block_theme()
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
 * Register a CSS stylesheet.
 *
 * @param string           $handle Name of the stylesheet. Should be unique.
 * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on.
 * @param string|bool|null $ver    Optional. String specifying stylesheet version number.
 * @param string           $media  Optional. The media for which this stylesheet has been defined.
 * @return bool Whether the style has been registered. True on success, false on failure.
 */
function wp_register_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
    return true;
}

/**
 * Check whether a CSS stylesheet has been registered/enqueued.
 *
 * @param string $handle Name of the stylesheet.
 * @param string $list   Optional. Status to check. Default 'enqueued'.
 * @return bool
 */
function wp_style_is($handle, $list = 'enqueued')
{
    return false;
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
 * Register a script.
 *
 * @param string           $handle    Name of the script. Should be unique.
 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 * @param string[]         $deps      Optional. An array of registered script handles this script depends on.
 * @param string|bool|null $ver       Optional. String specifying script version number.
 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 * @return bool Whether the script has been registered. True on success, false on failure.
 */
function wp_register_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false)
{
    return true;
}

/**
 * Check whether a script has been registered/enqueued.
 *
 * @param string $handle Name of the script.
 * @param string $list   Optional. Status to check. Default 'enqueued'.
 * @return bool
 */
function wp_script_is($handle, $list = 'enqueued')
{
    return false;
}

/**
 * Registers a block type.
 *
 * @param string|object $block_type Block type name or WP_Block_Type instance.
 * @param array         $args       Optional. Array of block type arguments. Default empty array.
 * @return mixed The registered block type object, or false on failure.
 */
function register_block_type($block_type, $args = array())
{
    return null;
}

/**
 * Add extra code to a registered script.
 *
 * @param string $handle   Name of the script to add the inline script to.
 * @param string $data     String containing the JavaScript to be added.
 * @param string $position Optional. Whether to add the inline script before the handle or after. Default 'after'.
 * @return bool True on success, false on failure.
 */
function wp_add_inline_script($handle, $data, $position = 'after')
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
 * Removes option by name. Prevents removal of protected WordPress options.
 *
 * @param string $option Name of option to remove. Expected to not be SQL-escaped.
 * @return bool True, if option is successfully deleted. False on failure.
 */
function delete_option($option)
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
 * Sanitize an email address.
 *
 * @param string $email Email address to sanitize.
 * @return string Sanitized email address.
 */
function sanitize_email($email)
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
 * Delete a post meta field for the given post ID.
 *
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if non-scalar. If provided, rows will only be removed that match the value.
 * @return bool True on success, false on failure.
 */
function delete_post_meta($post_id, $meta_key, $meta_value = '')
{
    return true;
}

/**
 * Retrieve an array of posts matching the criteria provided in $args.
 *
 * @param array|string $args Optional. Array or string of arguments. See WP_Query::parse_query() for all available arguments.
 * @return array|int Array of post objects or post IDs.
 */
function get_posts($args = null)
{
    return array();
}

/**
 * Retrieve a post by its ID.
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @param string $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Post object, an associative array, or a numeric array, respectively. Default OBJECT.
 * @param string $filter Optional. Type of filter to apply. Accepts 'raw', 'edit', 'db', 'display', 'attribute', 'js', 'rss'. Default 'raw'.
 * @return WP_Post|array|null WP_Post (or array) on success, null on failure.
 */
function get_post($post = null, $output = 'OBJECT', $filter = 'raw')
{
    return null;
}

/**
 * Retrieve comments.
 *
 * @param array|string $args Optional. Array or string of arguments. See WP_Comment_Query::__construct() for information on accepted arguments.
 * @return int[]|WP_Comment[] Array of comment objects or comment IDs.
 */
function get_comments($args = '')
{
    return array();
}

/**
 * Retrieve the terms of the taxonomy that are attached to the post.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy name.
 * @param array  $args      Optional. Arguments to pass to get_terms(). Default empty array.
 * @return array|WP_Error List of term objects or WP_Error on failure.
 */
function wp_get_post_terms($post_id, $taxonomy, $args = array())
{
    return array();
}

/**
 * Retrieve the permalink for a term.
 *
 * @param int|object $term     Term ID or term object.
 * @param string     $taxonomy Optional. Taxonomy name. Default empty.
 * @return string|WP_Error URL of the taxonomy term archive on success, WP_Error on failure.
 */
function get_term_link($term, $taxonomy = '')
{
    return '';
}

/**
 * Retrieve the permalink for a post.
 *
 * @param int|WP_Post $post      Optional. Post ID or post object. Default is the current post.
 * @param bool        $leavename Optional. Whether to keep post name. Default false.
 * @return string|false The permalink URL or false if post does not exist.
 */
function get_permalink($post = 0, $leavename = false)
{
    return '';
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
 * Retrieve the plural or single form based on the supplied number.
 *
 * @param string $single The text that will be used if the number is 1.
 * @param string $plural The text that will be used if the number is not 1.
 * @param int    $number The number to compare against to use either the singular or plural form.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 * @return string The translated singular or plural form.
 */
function _n($single, $plural, $number, $domain = 'default')
{
    return $number == 1 ? $single : $plural;
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
 * Sanitize content for allowed HTML tags for post content.
 *
 * @param string $data Post content to filter.
 * @return string Filtered post content with allowed HTML tags and attributes.
 */
function wp_kses_post($data)
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
 * Retrieve the date in localized format, based on timestamp.
 *
 * @param string   $dateformatstring Format for displaying the date.
 * @param int|bool $unixtimestamp    Optional. Unix timestamp. Default false (current time).
 * @param bool     $gmt               Optional. Whether to use GMT timezone. Default false.
 * @return string The date, translated if locale specifies it.
 */
function date_i18n($dateformatstring, $unixtimestamp = false, $gmt = false)
{
    return '';
}

/**
 * Retrieve the current time based on specified type.
 *
 * @param string $type   Type of time to retrieve. Accepts 'mysql', 'timestamp', or PHP date format string (e.g. 'Y-m-d').
 * @param int|bool $gmt  Optional. Whether to use GMT timezone. Default false.
 * @return int|string Integer if $type is 'timestamp', string otherwise.
 */
function current_time($type, $gmt = false)
{
    return $type === 'timestamp' ? 0 : '';
}

/**
 * Returns a numerically indexed array containing all defined timezone identifiers.
 *
 * @param int $what One of DateTimeZone class constants.
 * @param string $country A two-letter ISO 3166-1 compatible country code.
 * @return array List of timezone identifiers.
 */
if (!function_exists('timezone_identifiers_list')) {
    function timezone_identifiers_list($what = 2047, $country = null)
    {
        return array();
    }
}

/**
 * Encode a variable into JSON, with some sanity checks.
 *
 * @param mixed $data    Variable (usually an array or object) to encode as JSON.
 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
 * @param int   $depth   Optional. Maximum depth to walk through $data. Must be greater than 0. Default 512.
 * @return string|false The JSON encoded string, or false if it cannot be encoded.
 */
function wp_json_encode($data, $options = 0, $depth = 512)
{
    return '';
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
if (!function_exists('class_exists')) {
    function class_exists($class_name, $autoload = true)
    {
        return false;
    }
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

    /**
     * @var string
     */
    public $post_date = '';
}

/**
 * WordPress Comment class
 */
class WP_Comment
{
    /**
     * @var int
     */
    public $comment_ID = 0;

    /**
     * @var int
     */
    public $comment_post_ID = 0;

    /**
     * @var string
     */
    public $comment_content = '';

    /**
     * @var string
     */
    public $content = '';

    /**
     * @var string
     */
    public $comment_date = '';

    /**
     * @var string
     */
    public $comment_date_gmt = '';

    /**
     * @var string
     */
    public $date = '';

    /**
     * @var string
     */
    public $comment_author = '';
}

/**
 * WordPress Error class
 */
class WP_Error
{
    /**
     * @var string
     */
    public $errors = array();

    /**
     * @var string
     */
    public $error_data = array();

    /**
     * Constructor
     *
     * @param string|int $code Error code
     * @param string $message Error message
     * @param mixed $data Optional. Error data.
     */
    public function __construct($code = '', $message = '', $data = '') {}

    /**
     * Retrieve all error codes.
     *
     * @return array List of error codes, if available.
     */
    public function get_error_codes()
    {
        return array();
    }

    /**
     * Retrieve first error code available.
     *
     * @return string|int Empty string, if no error codes.
     */
    public function get_error_code()
    {
        return '';
    }

    /**
     * Retrieve all error messages or error messages matching code.
     *
     * @param string|int $code Optional. Retrieve messages matching code, if exists.
     * @return array Error strings on success, or empty array on failure (if using code parameter).
     */
    public function get_error_messages($code = '')
    {
        return array();
    }

    /**
     * Get single error message.
     *
     * @param string|int $code Optional. Error code to retrieve message.
     * @return string Error message on success, or empty string on failure.
     */
    public function get_error_message($code = '')
    {
        return '';
    }

    /**
     * Retrieve error data for error code.
     *
     * @param string|int $code Optional. Error code.
     * @param mixed $key Optional. If set, retrieve a specific key from error data.
     * @return mixed Error data, if it exists.
     */
    public function get_error_data($code = '', $key = '')
    {
        return null;
    }
}

/**
 * Check whether variable is a WordPress Error.
 *
 * @param mixed $thing Check if unknown variable is a WP_Error object.
 * @return bool True, if WP_Error. False, if not WP_Error.
 */
function is_wp_error($thing)
{
    return false;
}

/**
 * WordPress Block Type Registry class (Gutenberg).
 */
class WP_Block_Type_Registry
{
    /**
     * @return WP_Block_Type_Registry
     */
    public static function get_instance()
    {
        return new self();
    }

    /**
     * Check if a block type is registered.
     *
     * @param string $name Block type name.
     * @return bool
     */
    public function is_registered($name)
    {
        return false;
    }
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

    /**
     * Get product name
     *
     * @return string
     */
    public function get_name()
    {
        return '';
    }

    /**
     * Check if a product is purchasable.
     *
     * @return bool True if the product is purchasable, false otherwise.
     */
    public function is_purchasable()
    {
        return false;
    }

    /**
     * Get the product's price in HTML format.
     *
     * @return string Price in HTML format.
     */
    public function get_price_html()
    {
        return '';
    }

    /**
     * Get the product's price (numeric value).
     *
     * @return string|float Price as numeric value.
     */
    public function get_price()
    {
        return 0.0;
    }

    /**
     * Get the product short description.
     *
     * @return string Product short description.
     */
    public function get_short_description()
    {
        return '';
    }

    /**
     * Get the product SKU.
     *
     * @return string Product SKU.
     */
    public function get_sku()
    {
        return '';
    }

    /**
     * Get the product rating count.
     *
     * @return int Product rating count.
     */
    public function get_rating_count()
    {
        return 0;
    }

    /**
     * Get the product average rating.
     *
     * @return float Product average rating.
     */
    public function get_average_rating()
    {
        return 0.0;
    }
}

/**
 * WooCommerce Order class
 */
class WC_Order
{
    /**
     * Get order ID
     *
     * @return int
     */
    public function get_id()
    {
        return 0;
    }

    /**
     * Get order items
     *
     * @return array
     */
    public function get_items()
    {
        return array();
    }

    /**
     * Get billing first name
     *
     * @return string
     */
    public function get_billing_first_name()
    {
        return '';
    }

    /**
     * Get billing last name
     *
     * @return string
     */
    public function get_billing_last_name()
    {
        return '';
    }

    /**
     * Get billing email
     *
     * @return string
     */
    public function get_billing_email()
    {
        return '';
    }

    /**
     * Get billing phone
     *
     * @return string
     */
    public function get_billing_phone()
    {
        return '';
    }

    /**
     * Get order status
     *
     * @return string Order status (e.g., 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed')
     */
    public function get_status()
    {
        return '';
    }

    /**
     * Get order date created
     *
     * @return WC_DateTime|null
     */
    public function get_date_created()
    {
        return null;
    }

    /**
     * Get payment method title
     *
     * @return string Payment method title
     */
    public function get_payment_method_title()
    {
        return '';
    }

    /**
     * Get customer note
     *
     * @return string Customer note
     */
    public function get_customer_note()
    {
        return '';
    }
}

/**
 * PHP DateTime class (built-in)
 */
if (!class_exists('DateTime')) {
    class DateTime
    {
        public function format($format)
        {
            return '';
        }
    }
}

/**
 * WooCommerce DateTime class
 */
class WC_DateTime extends DateTime
{
    /**
     * Format the date
     *
     * @param string $format Date format
     * @return string
     */
    public function date($format)
    {
        return '';
    }
}

/**
 * WooCommerce Cart class
 */
class WC_Cart
{
    /**
     * Add a product to the cart.
     *
     * @param int   $product_id   Product ID.
     * @param int   $quantity     Quantity to add.
     * @param int   $variation_id Variation ID.
     * @param array $variation     Variation data.
     * @param array $cart_item_data Extra cart item data.
     * @return string|false Cart item key on success, false on failure.
     */
    public function add_to_cart($product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array())
    {
        return '';
    }

    /**
     * Get cart hash.
     *
     * @return string Cart hash.
     */
    public function get_cart_hash()
    {
        return '';
    }
}

/**
 * Main WooCommerce class
 */
class WooCommerce
{
    /**
     * @var WC_Cart
     */
    public $cart;
}

// WooCommerce Namespace Classes
// Note: Cannot declare namespace here as it must be at the top of file
// Using class_alias approach for Intelephense to recognize the fully qualified class name

/**
 * Stub class for \Automattic\WooCommerce\Utilities\FeaturesUtil
 * 
 * This is a workaround since namespace declaration must be at the top of file.
 * Intelephense will recognize this through the class name and PHPDoc.
 * 
 * @see \Automattic\WooCommerce\Utilities\FeaturesUtil
 */
class Automattic_WooCommerce_Utilities_FeaturesUtil
{
    /**
     * Declare compatibility or incompatibility with a given feature for a given plugin.
     *
     * @param string $feature_id Feature id, e.g. 'custom_order_tables'.
     * @param string $plugin_file The main plugin file.
     * @param bool   $positive_compatibility True if the plugin declares being compatible with the feature, false if it declares incompatibility.
     * @return void
     */
    public static function declare_compatibility($feature_id, $plugin_file, $positive_compatibility = true) {}
}

// Create alias for fully qualified namespace class name
if (!class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil', false)) {
    class_alias('Automattic_WooCommerce_Utilities_FeaturesUtil', '\Automattic\WooCommerce\Utilities\FeaturesUtil');
}

// WooCommerce Functions

/**
 * Output a select input box.
 *
 * @param array $field Field data.
 */
function woocommerce_wp_select($field) {}

/**
 * Output a checkbox input box.
 *
 * @param array $field Field data.
 */
function woocommerce_wp_checkbox($field) {}

/**
 * Output the mini cart.
 */
function woocommerce_mini_cart() {}

/**
 * Main instance of WooCommerce.
 *
 * @return WooCommerce The main WooCommerce instance.
 */
function WC()
{
    return new WooCommerce();
}

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

/**
 * Get WooCommerce products.
 *
 * @param array $args Query arguments.
 * @return WC_Product[] Array of product objects.
 */
function wc_get_products($args = array())
{
    return array();
}

/**
 * Get WooCommerce order object.
 *
 * @param int|WC_Order $order Order ID or order object.
 * @return WC_Order|false Order object or false on failure.
 */
function wc_get_order($order = null)
{
    return false;
}

/**
 * Get order item meta.
 *
 * @param int    $item_id Order item ID.
 * @param string $key     Optional. Meta key. Default empty.
 * @param bool   $single  Optional. Whether to return a single value. Default true.
 * @return mixed Meta value(s).
 */
function wc_get_order_item_meta($item_id, $key = '', $single = true)
{
    return '';
}

/**
 * Get order status name.
 *
 * @param string $status Order status.
 * @return string Translated order status name.
 */
function wc_get_order_status_name($status)
{
    return '';
}

/**
 * Get order notes.
 *
 * @param array $args Optional. Arguments for retrieving order notes.
 * @return array Array of order note objects.
 */
function wc_get_order_notes($args = array())
{
    return array();
}

/**
 * Get the cart page URL.
 *
 * @return string Cart page URL.
 */
function wc_get_cart_url()
{
    return '';
}

/**
 * Get the checkout page URL.
 *
 * @return string Checkout page URL.
 */
function wc_get_checkout_url()
{
    return '';
}

/**
 * Compare two values and output checked attribute.
 *
 * @param mixed $checked One of the values to compare.
 * @param mixed $current The other value to compare if true.
 * @param bool  $echo    Whether to echo or just return the string. Default true.
 * @return string HTML attribute or empty string.
 */
function checked($checked, $current = true, $echo = true)
{
    return '';
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

    /**
     * Suppress database errors.
     *
     * @param bool $suppress Optional. Whether to suppress errors. Default true.
     * @return bool Previous suppress_errors value.
     */
    public function suppress_errors($suppress = true)
    {
        return false;
    }

    /**
     * Hide database errors.
     */
    public function hide_errors() {}
}

/**
 * Get WooCommerce currency symbol.
 *
 * @return string Currency symbol.
 */
function get_woocommerce_currency_symbol()
{
    return '';
}

/**
 * Get WooCommerce price decimal separator.
 *
 * @return string Decimal separator.
 */
function wc_get_price_decimal_separator()
{
    return '.';
}

/**
 * Get WooCommerce price thousand separator.
 *
 * @return string Thousand separator.
 */
function wc_get_price_thousand_separator()
{
    return ',';
}

/**
 * Get WooCommerce price decimals.
 *
 * @return int Number of decimals.
 */
function wc_get_price_decimals()
{
    return 2;
}
