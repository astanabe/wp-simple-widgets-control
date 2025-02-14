<?php
/**
 * Plugin Name:       Simple Appearance Control
 * Plugin URI:        https://github.com/astanabe/wp-simple-appearance-control
 * Description:       Simple Appearance Control Plugin for WordPress
 * Author:            Akifumi S. Tanabe
 * Author URI:        https://github.com/astanabe
 * License:           GNU General Public License v2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-simple-appearance-control
 * Domain Path:       /languages
 * Version:           0.1.0
 * Requires at least: 6.4
 *
 * @package           WP_Simple_Appearance_Control
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

// プラグイン無効化時の設定削除処理
function wp_sac_plugin_deactivation() {
    if (get_option('wp_sac_remove_settings') === 'remove') {
        delete_option('wp_sac_remove_settings');
        delete_metadata('post', 0, 'wp_sac_menu_display_option', '', true);
        delete_metadata('post', 0, 'wp_sac_menu_roles', '', true);
        delete_metadata('post', 0, 'wp_sac_menu_groups', '', true);
    }
}
register_deactivation_hook(__FILE__, 'wp_sac_plugin_deactivation');

function wp_sac_plugin_deactivation_prompt($plugin) {
    if ($plugin === plugin_basename(__FILE__)) {
        echo '<div id="wp-sac-deactivation-dialog" class="notice notice-warning is-dismissible" style="display:none;">
                <p><strong>Do you want to remove all settings?</strong></p>
                <button id="wp-sac-remove-settings" class="button button-primary">Remove all settings</button>
                <button id="wp-sac-keep-settings" class="button">Leave settings for reactivation</button>
              </div>
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    let deactivateLinks = document.querySelectorAll(".deactivate a");
                    deactivateLinks.forEach(link => {
                        if (link.href.includes("plugin=widget-display-control")) {
                            link.addEventListener("click", function (event) {
                                event.preventDefault();
                                document.getElementById("wp-sac-deactivation-dialog").style.display = "block";
                                document.getElementById("wp-sac-remove-settings").addEventListener("click", function () {
                                    fetch("' . admin_url('admin-ajax.php') . '?action=wp_sac_remove_settings").then(() => {
                                        window.location.href = link.href;
                                    });
                                });
                                document.getElementById("wp-sac-keep-settings").addEventListener("click", function () {
                                    window.location.href = link.href;
                                });
                            });
                        }
                    });
                });
              </script>';
    }
}
add_action('admin_footer', 'wp_sac_plugin_deactivation_prompt');

// Add appearance control option for widgets
function wp_sac_widget_form_extend($widget, $return, $instance) {
    $display_option = isset($instance['wp_sac_display_option']) ? $instance['wp_sac_display_option'] : 'always';
    $selected_roles = isset($instance['wp_sac_roles']) ? (array) $instance['wp_sac_roles'] : [];
    $selected_groups = isset($instance['wp_sac_groups']) ? (array) $instance['wp_sac_groups'] : [];
    $roles = wp_roles()->roles;
    ?>
    <p>
        <label for="<?php echo $widget->get_field_id('wp_sac_display_option'); ?>">Display Option:</label>
        <br>
        <input type="radio" name="<?php echo $widget->get_field_name('wp_sac_display_option'); ?>" value="always" <?php checked($display_option, 'always'); ?>> Always display<br>
        <input type="radio" name="<?php echo $widget->get_field_name('wp_sac_display_option'); ?>" value="logged-in" <?php checked($display_option, 'logged-in'); ?> class="wp-sac-logged-in-radio"> Displays for Logged-in users only<br>
        <input type="radio" name="<?php echo $widget->get_field_name('wp_sac_display_option'); ?>" value="logged-out" <?php checked($display_option, 'logged-out'); ?>> Displays for Logged-out users only
    </p>
    <div class="wp-sac-role-selection" style="display: <?php echo ($display_option === 'logged-in') ? 'block' : 'none'; ?>;">
        <label>Restrict to specific roles:</label>
        <br>
        <?php foreach ($roles as $role_key => $role) : ?>
            <input type="checkbox" name="<?php echo $widget->get_field_name('wp_sac_roles'); ?>[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $selected_roles)); ?> class="wp-sac-role-checkbox"> <?php echo esc_html($role['name']); ?><br>
        <?php endforeach; ?>
    </div>
    <?php if (function_exists('bp_is_active') && bp_is_active('groups')): ?>
        <div class="wp-sac-group-selection" style="display: <?php echo ($display_option === 'logged-in') ? 'block' : 'none'; ?>;">
            <label>Restrict to specific BuddyPress Groups:</label>
            <br>
            <?php
            if (function_exists('groups_get_groups')) {
                $groups = groups_get_groups(array('show_hidden' => true));
                foreach ($groups['groups'] as $group) : ?>
                    <input type="checkbox" name="<?php echo $widget->get_field_name('wp_sac_groups'); ?>[]" value="<?php echo esc_attr($group->id); ?>" <?php checked(in_array($group->id, $selected_groups)); ?> class="wp-sac-group-checkbox"> <?php echo esc_html($group->name); ?><br>
                <?php endforeach;
            }
            ?>
        </div>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let roleCheckboxes = document.querySelectorAll('.wp-sac-role-checkbox');
            let groupCheckboxes = document.querySelectorAll('.wp-sac-group-checkbox');
            function toggleCheckboxes() {
                let roleChecked = Array.from(roleCheckboxes).some(cb => cb.checked);
                let groupChecked = Array.from(groupCheckboxes).some(cb => cb.checked);
                roleCheckboxes.forEach(cb => cb.disabled = groupChecked);
                groupCheckboxes.forEach(cb => cb.disabled = roleChecked);
            }
            roleCheckboxes.forEach(cb => cb.addEventListener('change', toggleCheckboxes));
            groupCheckboxes.forEach(cb => cb.addEventListener('change', toggleCheckboxes));
            toggleCheckboxes();
        });
    </script>
    <?php
    return $return;
}
add_filter('widget_form_callback', 'wp_sac_widget_form_extend', 10, 3);

// Save appearance control option of widgets
function wp_sac_widget_update($instance, $new_instance) {
    $instance['wp_sac_display_option'] = isset($new_instance['wp_sac_display_option']) ? sanitize_text_field($new_instance['wp_sac_display_option']) : 'always';
    $instance['wp_sac_roles'] = isset($new_instance['wp_sac_roles']) ? array_map('sanitize_text_field', (array) $new_instance['wp_sac_roles']) : [];
    $instance['wp_sac_groups'] = isset($new_instance['wp_sac_groups']) ? array_map('sanitize_text_field', (array) $new_instance['wp_sac_groups']) : [];
    return $instance;
}
add_filter('widget_update_callback', 'wp_sac_widget_update', 10, 2);

// Appearance control function for widgets
function wp_sac_widget_display($instance, $widget, $args) {
    if (isset($instance['wp_sac_display_option'])) {
        $display_option = $instance['wp_sac_display_option'];
        $allowed_roles = isset($instance['wp_sac_roles']) ? (array) $instance['wp_sac_roles'] : [];
        $allowed_groups = isset($instance['wp_sac_groups']) ? (array) $instance['wp_sac_groups'] : [];

        if ($display_option === 'logged-in' && is_user_logged_in()) {
            $user = wp_get_current_user();
            if (!empty($allowed_roles) && empty(array_intersect($allowed_roles, $user->roles))) {
                return false;
            }
            if (function_exists('groups_get_user_groups') && !empty($allowed_groups)) {
                $user_groups = groups_get_user_groups($user->ID)['groups'];
                if (empty(array_intersect($allowed_groups, $user_groups))) {
                    return false;
                }
            }
        } elseif ($display_option === 'logged-out' && is_user_logged_in()) {
            return false;
        }
    }
    return $instance;
}
add_filter('widget_display_callback', 'wp_sac_widget_display', 10, 3);

// Add appearance control option for menu items
function wp_sac_add_menu_meta_box() {
    add_meta_box('wp_sac_menu_meta', 'Display Control', 'wp_sac_menu_meta_box', 'nav-menus', 'side', 'low');
}
add_action('admin_init', 'wp_sac_add_menu_meta_box');

// Appearance control option configuration display function for menu items
function wp_sac_menu_meta_box() {
    $roles = wp_roles()->roles;
    ?>
    <p>
        <label>Display Option:</label><br>
        <input type="radio" name="wp_sac_menu_display_option" value="always" checked> Always display<br>
        <input type="radio" name="wp_sac_menu_display_option" value="logged-in" class="wp-sac-logged-in-radio"> Displays for Logged-in users only<br>
        <input type="radio" name="wp_sac_menu_display_option" value="logged-out"> Displays for Logged-out users only
    </p>
    <div class="wp-sac-role-selection" style="display: none;">
        <label>Restrict to specific roles:</label><br>
        <?php foreach ($roles as $role_key => $role) : ?>
            <input type="checkbox" name="wp_sac_menu_roles[]" value="<?php echo esc_attr($role_key); ?>"> <?php echo esc_html($role['name']); ?><br>
        <?php endforeach; ?>
    </div>
    <?php if (function_exists('bp_is_active') && bp_is_active('groups')): ?>
        <div class="wp-sac-group-selection" style="display: none;">
            <label>Restrict to specific BuddyPress Groups:</label><br>
            <?php
            if (function_exists('groups_get_groups')) {
                $groups = groups_get_groups(array('show_hidden' => true));
                foreach ($groups['groups'] as $group) : ?>
                    <input type="checkbox" name="wp_sac_menu_groups[]" value="<?php echo esc_attr($group->id); ?>"> <?php echo esc_html($group->name); ?><br>
                <?php endforeach;
            }
            ?>
        </div>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let displayRadios = document.querySelectorAll('input[name="wp_sac_menu_display_option"]');
            let roleSelection = document.querySelector('.wp-sac-role-selection');
            let groupSelection = document.querySelector('.wp-sac-group-selection');
            function toggleSections() {
                let loggedInSelected = document.querySelector('input[name="wp_sac_menu_display_option"][value="logged-in"]:checked');
                roleSelection.style.display = loggedInSelected ? 'block' : 'none';
                groupSelection.style.display = loggedInSelected ? 'block' : 'none';
            }
            displayRadios.forEach(radio => radio.addEventListener('change', toggleSections));
            toggleSections();
        });
    </script>
    <?php
}

// Appearance control function for menu items
function wp_sac_filter_menu_items($items, $args) {
    foreach ($items as $key => $item) {
        $display_option = get_post_meta($item->ID, 'wp_sac_menu_display_option', true);
        $allowed_roles = get_post_meta($item->ID, 'wp_sac_menu_roles', true);
        $allowed_groups = get_post_meta($item->ID, 'wp_sac_menu_groups', true);
        if ($display_option === 'logged-in' && !is_user_logged_in()) {
            unset($items[$key]);
        } elseif ($display_option === 'logged-out' && is_user_logged_in()) {
            unset($items[$key]);
        }
        if (!empty($allowed_roles) && is_user_logged_in()) {
            $user = wp_get_current_user();
            if (!array_intersect($allowed_roles, $user->roles)) {
                unset($items[$key]);
            }
        }
        if (!empty($allowed_groups) && is_user_logged_in()) {
            if (function_exists('groups_get_user_groups')) {
                $user_groups = groups_get_user_groups(get_current_user_id())['groups'];
                if (!array_intersect($allowed_groups, $user_groups)) {
                    unset($items[$key]);
                }
            }
        }
    }
    return $items;
}
add_filter('wp_nav_menu_objects', 'wp_sac_filter_menu_items', 10, 2);
