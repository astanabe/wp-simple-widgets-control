<?php
/**
 * Plugin Name:       Simple Widgets Control
 * Plugin URI:        https://github.com/astanabe/wp-simple-widgets-control
 * Description:       A simple widgets visibility control plugin for WordPress
 * Author:            Akifumi S. Tanabe
 * Author URI:        https://github.com/astanabe
 * License:           GNU General Public License v2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-simple-widgets-control
 * Domain Path:       /languages
 * Version:           0.1.0
 * Requires at least: 6.4
 *
 * @package           WP_Simple_Widgets_Control
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

// Add visibility field to widgets
function wp_simple_widgets_control_add_visibility_field($widget, $return, $instance) {
	$visibility = isset($instance['wp_simple_widgets_control_visibility']) ? $instance['wp_simple_widgets_control_visibility'] : 'always';
	$roles = isset($instance['wp_simple_widgets_control_roles']) ? (array)$instance['wp_simple_widgets_control_roles'] : [];
	$groups = isset($instance['wp_simple_widgets_control_groups']) ? (array)$instance['wp_simple_widgets_control_groups'] : [];
	$all_roles = wp_roles()->roles;
	$all_groups = function_exists('bp_is_active') && bp_is_active('groups') ? wp_simple_widgets_control_get_groups() : [];
	?>
	<p class="field-visibility description description-wide">
		<label>
			<?php esc_html_e('Visibility', 'wp-simple-widgets-control'); ?><br />
			<input type="radio" class="wp-simple-widgets-control-visibility-<?php echo $widget->id; ?>" name="<?php echo esc_attr($widget->get_field_name('wp_simple_widgets_control_visibility')); ?>" value="always" <?php checked($visibility, 'always'); ?>> Always display<br />
			<input type="radio" class="wp-simple-widgets-control-visibility-<?php echo $widget->id; ?>" name="<?php echo esc_attr($widget->get_field_name('wp_simple_widgets_control_visibility')); ?>" value="logged-out" <?php checked($visibility, 'logged-out'); ?>> Displays for Logged-out users only<br />
			<input type="radio" class="wp-simple-widgets-control-visibility-<?php echo $widget->id; ?>" name="<?php echo esc_attr($widget->get_field_name('wp_simple_widgets_control_visibility')); ?>" value="logged-in" <?php checked($visibility, 'logged-in'); ?>> Displays for Logged-in users only
		</label>
	</p>
	<div class="wp-simple-widgets-control-roles-groups-<?php echo $widget->id; ?>" <?php echo ($visibility === 'logged-in') ? '' : 'style="display:none;"'; ?>>
		<p class="field-roles description">
			<?php esc_html_e('Select Roles:', 'wp-simple-widgets-control'); ?><br />
			<?php foreach ($all_roles as $role_key => $role) : ?>
				<input type="checkbox" class="wp-simple-widgets-control-role-<?php echo $widget->id; ?>" name="<?php echo esc_attr($widget->get_field_name('wp_simple_widgets_control_roles')); ?>" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $roles)); ?>> <?php echo esc_html($role['name']); ?><br />
			<?php endforeach; ?>
		</p>
		<?php if (!empty($all_groups)) : ?>
			<p class="field-groups description">
				<?php esc_html_e('Select Groups:', 'wp-simple-widgets-control'); ?><br />
				<?php foreach ($all_groups as $group) : ?>
					<input type="checkbox" class="wp-simple-widgets-control-group-<?php echo $widget->id; ?>" name="<?php echo esc_attr($widget->get_field_name('wp_simple_widgets_control_groups')); ?>" value="<?php echo esc_attr($group['id']); ?>" <?php checked(in_array($group['id'], $groups)); ?>> <?php echo esc_html($group['name']); ?><br />
				<?php endforeach; ?>
			</p>
		<?php endif; ?>
	</div>
	<script>
		jQuery(document).ready(function($) {
			let widget_id = "<?php echo $widget->id; ?>";
			function updateRadiobuttonState() {
				let logeedinChecked = $('.wp-simple-widgets-control-visibility-' + widget_id + ':checked').val() == 'logged-in';
				if (logeedinChecked) {
					$('.wp-simple-widgets-control-roles-groups-' + widget_id).show();
				} else {
					$('.wp-simple-widgets-control-roles-groups-' + widget_id).hide();
				}
			}
			$('.wp-simple-widgets-control-visibility-' + widget_id).on('change', updateRadiobuttonState);
			updateRadiobuttonState();
			function updateCheckboxState() {
				let roleChecked = $('.wp-simple-widgets-control-role-' + widget_id + ':checked').length > 0;
				let groupChecked = $('.wp-simple-widgets-control-group-' + widget_id + ':checked').length > 0;
				if (roleChecked) {
					$('.wp-simple-widgets-control-group-' + widget_id).prop('disabled', true);
				} else if (groupChecked) {
					$('.wp-simple-widgets-control-role-' + widget_id).prop('disabled', true);
				} else {
					$('.wp-simple-widgets-control-role-' + widget_id + ', .wp-simple-widgets-control-group-' + widget_id).prop('disabled', false);
				}
			}
			$('.wp-simple-widgets-control-role-' + widget_id + ', .wp-simple-widgets-control-group-' + widget_id).on('change', updateCheckboxState);
			updateCheckboxState();
		});
	</script>
	<?php
}
add_filter('in_widget_form', 'wp_simple_widgets_control_add_visibility_field', 10, 3);

// Save visibility settings
function wp_simple_widgets_control_save_visibility_settings($instance, $new_instance) {
	$instance['wp_simple_widgets_control_visibility'] = $new_instance['wp_simple_widgets_control_visibility'];
	$instance['wp_simple_widgets_control_roles'] = isset($new_instance['wp_simple_widgets_control_roles']) ? (array)$new_instance['wp_simple_widgets_control_roles'] : [];
	$instance['wp_simple_widgets_control_groups'] = isset($new_instance['wp_simple_widgets_control_groups']) ? (array)$new_instance['wp_simple_widgets_control_groups'] : [];
	return $instance;
}
add_filter('widget_update_callback', 'wp_simple_widgets_control_save_visibility_settings', 10, 2);

// Filter widget based on visibility settings
function wp_simple_widgets_control_filter_widget($instance, $widget, $args) {
	if (!isset($instance['wp_simple_widgets_control_visibility']) || $instance['wp_simple_widgets_control_visibility'] === 'always') {
		return $instance;
	}
	if ($instance['wp_simple_widgets_control_visibility'] === 'logged-out' && is_user_logged_in()) {
		return false;
	}
	if ($instance['wp_simple_widgets_control_visibility'] === 'logged-in' && !is_user_logged_in()) {
		return false;
	}
	if ($instance['wp_simple_widgets_control_visibility'] === 'logged-in' && is_user_logged_in()) {
		if (!empty($instance['wp_simple_widgets_control_roles']) && !array_intersect(wp_get_current_user()->roles, $instance['wp_simple_widgets_control_roles'])) {
			return false;
		}
		if (function_exists('bp_is_active') && bp_is_active('groups') && !empty($instance['wp_simple_widgets_control_groups']) && !array_intersect(groups_get_user_groups(get_current_user_id())['groups'], $instance['wp_simple_widgets_control_groups'])) {
			return false;
		}
	}
	return $instance;
}
add_filter('widget_display_callback', 'wp_simple_widgets_control_filter_widget', 10, 3);

// Get top 50 groups
function wp_simple_widgets_control_get_groups() {
	if (!function_exists('bp_has_groups')) {
		return [];
	}
	$groups = [];
	if (bp_has_groups(['per_page' => 50, 'orderby' => 'total_member_count', 'order' => 'DESC'])) {
		while (bp_groups()) {
			bp_the_group();
			$groups[] = ['id' => bp_get_group_id(), 'name' => bp_get_group_name()];
		}
	}
	return $groups;
}

// Page for deactivation
function wp_simple_widgets_control_deactivate_page() {
	if (!current_user_can('manage_options')) {
		return;
	}
	if (isset($_POST['wp_simple_widgets_control_deactivate_confirm']) && check_admin_referer('wp_simple_widgets_control_deactivate_confirm', 'wp_simple_widgets_control_deactivate_confirm_nonce')) {
		if ($_POST['wp_simple_widgets_control_deactivate_confirm'] === 'remove') {
			update_option('wp_simple_widgets_control_uninstall_settings', 'remove');
		}
		else {
			update_option('wp_simple_widgets_control_uninstall_settings', 'keep');
		}
		deactivate_plugins(plugin_basename(__FILE__));
		wp_safe_redirect(admin_url('plugins.php?deactivated=true'));
		exit;
	}
	?>
	<div class="wrap">
		<h2>Deactivate Simple Widgets Control Plugin</h2>
		<form method="post">
			<?php wp_nonce_field('wp_simple_widgets_control_deactivate_confirm', 'wp_simple_widgets_control_deactivate_confirm_nonce'); ?>
			<p>Do you want to remove all settings of this plugin when uninstalling?</p>
			<p>
				<label>
					<input type="radio" name="wp_simple_widgets_control_deactivate_confirm" value="keep" checked />
					Leave settings (default)
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="wp_simple_widgets_control_deactivate_confirm" value="remove" />
					Remove all settings
				</label>
			</p>
			<p>
				<input type="submit" class="button button-primary" value="Deactivate" />
			</p>
		</form>
	</div>
	<?php
	exit;
}

// Intercept deactivation request and redirect to confirmation screen
function wp_simple_widgets_control_deactivate_hook() {
	if (isset($_GET['action']) && $_GET['action'] === 'deactivate' && isset($_GET['plugin']) && $_GET['plugin'] === plugin_basename(__FILE__)) {
		wp_safe_redirect(admin_url('admin.php?page=wp-simple-widgets-control-deactivate'));
		exit;
	}
}
add_action('admin_init', 'wp_simple_widgets_control_deactivate_hook');

// Add deactivation confirmation page to the admin menu
function wp_simple_widgets_control_add_deactivate_page() {
	add_submenu_page(
		null, // No parent menu, hidden page
		'Deactivate Simple Widgets Control Plugin',
		'Deactivate Simple Widgets Control Plugin',
		'manage_options',
		'wp-simple-widgets-control-deactivate',
		'wp_simple_widgets_control_deactivate_page'
	);
}
add_action('admin_menu', 'wp_simple_widgets_control_add_deactivate_page');

// Remove all settings when uninstalling if specified
function wp_simple_widgets_control_uninstall() {
	if (get_option('wp_simple_widgets_control_uninstall_settings') === 'remove') {
		global $wpdb;
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_widget_%'");
		$widget_options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'widget_%'");
		foreach ($widget_options as $option) {
			$option_name = $option->option_name;
			$widget_data = get_option($option_name);
			if (is_array($widget_data)) {
				foreach ($widget_data as $widget_id => $widget_instance) {
					if (isset($widget_instance['wp_simple_widgets_control_visibility'])) {
						unset($widget_data[$widget_id]['wp_simple_widgets_control_visibility']);
					}
					if (isset($widget_instance['wp_simple_widgets_control_roles'])) {
						unset($widget_data[$widget_id]['wp_simple_widgets_control_roles']);
					}
					if (isset($widget_instance['wp_simple_widgets_control_groups'])) {
						unset($widget_data[$widget_id]['wp_simple_widgets_control_groups']);
					}
				}
				update_option($option_name, $widget_data);
			}
		}
		delete_option('wp_simple_widgets_control_uninstall_settings');
	}
}
register_uninstall_hook(__FILE__, 'wp_simple_widgets_control_uninstall');
