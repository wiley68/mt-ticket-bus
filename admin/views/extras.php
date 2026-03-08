<?php

/**
 * Extras Management Page Template
 *
 * This template displays the extras management page in the WordPress admin area.
 * It provides a form for creating and editing extras, and displays a list of all existing extras.
 *
 * @package MT_Ticket_Bus
 * @since 1.0.13
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

$extras_manager = MT_Ticket_Bus_Extras::get_instance();
$extras         = $extras_manager->get_all_extras(array('status' => 'all'));
$edit_id        = isset($_GET['edit']) ? absint($_GET['edit']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$edit_extra     = $edit_id ? $extras_manager->get_extra($edit_id) : null;
?>

<div class="wrap mt-ticket-bus-extras">
	<h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
	<a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-extras')); ?>" class="page-title-action"><?php esc_html_e('New Extra', 'mt-ticket-bus'); ?></a>
	<hr class="wp-header-end">

	<?php
	// Show success message after save/delete.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (isset($_GET['saved']) && $_GET['saved'] === '1') {
		$message = $edit_id > 0
			? __('Extra updated successfully.', 'mt-ticket-bus')
			: __('Extra created successfully.', 'mt-ticket-bus');
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Extra deleted successfully.', 'mt-ticket-bus') . '</p></div>';
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET params for display only (success/error messages after redirect).
	if (isset($_GET['error']) && $_GET['error'] !== '') {
		$error_message = sanitize_text_field(wp_unslash($_GET['error']));
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	?>

	<div class="mt-extras-container">
		<div class="mt-extras-form">
			<h2><?php echo $edit_extra ? esc_html__('Edit Extra', 'mt-ticket-bus') : esc_html__('Add New Extra', 'mt-ticket-bus'); ?></h2>

			<form id="mt-extra-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('mt_ticket_bus_save_extra', 'mt_ticket_bus_save_extra_nonce'); ?>
				<input type="hidden" name="action" value="mt_ticket_bus_save_extra" />
				<?php if ($edit_extra) : ?>
					<input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>" />
				<?php endif; ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="extra_name"><?php esc_html_e('Extra Name', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="extra_name" name="name" value="<?php echo $edit_extra ? esc_attr($edit_extra->name) : ''; ?>" class="regular-text" required />
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="extra_code"><?php esc_html_e('Extra Code (optional)', 'mt-ticket-bus'); ?></label>
							</th>
							<td>
								<input type="text" id="extra_code" name="code" value="<?php echo $edit_extra ? esc_attr($edit_extra->code) : ''; ?>" class="regular-text" />
								<p class="description"><?php esc_html_e('Unique identifier (used internally). Leave empty to auto-generate.', 'mt-ticket-bus'); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="extra_price"><?php esc_html_e('Price', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="number" id="extra_price" name="price" value="<?php echo $edit_extra ? esc_attr($edit_extra->price) : '0.00'; ?>" class="small-text" step="0.01" min="0" required />
								<p class="description"><?php esc_html_e('Fixed price per seat when this extra is selected.', 'mt-ticket-bus'); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="extra_status"><?php esc_html_e('Status', 'mt-ticket-bus'); ?></label>
							</th>
							<td>
								<select id="extra_status" name="status">
									<option value="active" <?php selected($edit_extra ? $edit_extra->status : 'active', 'active'); ?>><?php esc_html_e('Active', 'mt-ticket-bus'); ?></option>
									<option value="inactive" <?php selected($edit_extra ? $edit_extra->status : '', 'inactive'); ?>><?php esc_html_e('Inactive', 'mt-ticket-bus'); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button($edit_extra ? __('Update Extra', 'mt-ticket-bus') : __('Add Extra', 'mt-ticket-bus')); ?>
			</form>
		</div>

		<div class="mt-extras-list">
			<h2><?php esc_html_e('Extras List', 'mt-ticket-bus'); ?></h2>

			<?php if (empty($extras)) : ?>
				<p><?php esc_html_e('No extras found.', 'mt-ticket-bus'); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e('ID', 'mt-ticket-bus'); ?></th>
							<th class="mt-extra-name-col"><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
							<th><?php esc_html_e('Code', 'mt-ticket-bus'); ?></th>
							<th><?php esc_html_e('Price', 'mt-ticket-bus'); ?></th>
							<th><?php esc_html_e('Status', 'mt-ticket-bus'); ?></th>
							<th class="mt-extra-actions"><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($extras as $extra) : ?>
							<tr class="<?php echo esc_attr($extra->status === 'inactive' ? 'mt-extra-inactive' : ''); ?>">
								<td><?php echo esc_html($extra->id); ?></td>
								<td class="mt-extra-name-col"><?php echo esc_html($extra->name); ?></td>
								<td><?php echo esc_html($extra->code); ?></td>
								<td><?php echo esc_html(number_format((float) $extra->price, 2, '.', '')); ?></td>
								<td><?php echo esc_html(ucfirst($extra->status)); ?></td>
								<td class="mt-extra-actions">
									<a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-extras&edit=' . $extra->id)); ?>"><?php esc_html_e('Edit', 'mt-ticket-bus'); ?></a> |
									<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=mt_ticket_bus_delete_extra&id=' . $extra->id), 'mt_ticket_bus_delete_extra_' . $extra->id)); ?>" class="mt-delete-extra" data-id="<?php echo esc_attr($extra->id); ?>"><?php esc_html_e('Delete', 'mt-ticket-bus'); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>