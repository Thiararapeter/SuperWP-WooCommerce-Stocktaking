<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Superwp_Woo_Stocktake_Settings' ) ) :

class Superwp_Woo_Stocktake_Settings {

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.0.01
	 */
	private $plugin_name;

	/**
	 * Our Superwp_Woo_Stocktake_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.01
	 */
	function __construct(){

		$this->plugin_name = SUPERWPSTOCKTAKE_NAME;

		add_action('admin_menu', array($this, 'add_settings_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_notices', array($this, 'display_admin_notice'));

		$this->features = array();
		$this->actions = array();
		$this->load_features_and_actions();
	}

	/**
	 * ######################
	 * ###
	 * #### CALLABLE FUNCTIONS
	 * ###
	 * ######################
	 */

	/**
	 * Return the plugin name
	 *
	 * @access	public
	 * @since	1.0.01
	 * @return	string The plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'SUPERWPSTOCKTAKE/settings/get_plugin_name', $this->plugin_name );
	}

	public function add_settings_menu() {
		add_submenu_page(
			'wc-stocktaking',
			'Stocktake Settings',
			'Settings',
			'manage_options',
			'wc-stocktaking-settings',
			array($this, 'render_settings_page')
		);
	}

	public function register_settings() {
		register_setting('wc_stocktaking_settings', 'wc_stocktaking_role_permissions', array($this, 'sanitize_settings'));
		register_setting('wc_stocktaking_settings', 'wc_stocktaking_enable_role_permissions');
	}

	public function sanitize_settings($input) {
		// Initialize sanitized input
		$sanitized_input = array();

		// Check if input is an array
		if (!is_array($input)) {
			add_settings_error(
				'wc_stocktaking_settings',
				'wc_stocktaking_error',
				'Invalid input format. Settings not saved.',
				'error'
			);
			return get_option('wc_stocktaking_role_permissions', array());
		}

		// Sanitize each role and its permissions
		foreach ($input as $role => $permissions) {
			if (!is_array($permissions)) {
				continue;
			}

			$sanitized_input[$role] = array();
			foreach ($permissions as $permission => $value) {
				$sanitized_input[$role][$permission] = (bool) $value;
			}
		}

		// Ensure administrator always has plugin access
		$sanitized_input['administrator']['plugin_access'] = true;

		// If sanitized input is empty, it means no valid data was provided
		if (empty($sanitized_input)) {
			add_settings_error(
				'wc_stocktaking_settings',
				'wc_stocktaking_error',
				'No valid permissions were provided. Settings not saved.',
				'error'
			);
			return get_option('wc_stocktaking_role_permissions', array());
		}

		$this->message = 'Settings saved successfully.';
		$this->message_type = 'success';
		return $sanitized_input;
	}

	public function display_admin_notice() {
		if (!empty($this->message)) {
			$class = ($this->message_type === 'success') ? 'notice notice-success' : 'notice notice-error';
			echo "<div class='$class is-dismissible'><p>$this->message</p></div>";
		}
	}

	public function render_settings_page() {
		$roles = wp_roles()->get_names();
		$saved_permissions = get_option('wc_stocktaking_role_permissions', array());
		$enable_permissions = get_option('wc_stocktaking_enable_role_permissions', 0);
		?>
		<div class="wrap">
			<h1>Stocktake Settings</h1>
			
			<?php settings_errors(); ?>
			
			<form method="post" action="options.php">
				<?php settings_fields('wc_stocktaking_settings'); ?>
				<?php do_settings_sections('wc_stocktaking_settings'); ?>
				
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Enable Role Permissions</th>
						<td>
							<label>
								<input type="checkbox" name="wc_stocktaking_enable_role_permissions" value="1" <?php checked(1, $enable_permissions); ?>>
								Enable Role Permissions
							</label>
						</td>
					</tr>
				</table>

				<h2>Access Permissions</h2>
				<table class="widefat" style="margin-top: 20px;">
					<thead>
						<tr>
							<th>Role</th>
							<?php foreach ($this->features as $feature_slug => $feature_name) : ?>
								<th><?php echo esc_html($feature_name); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($roles as $role_slug => $role_name) : 
							$is_admin = $role_slug === 'administrator';
						?>
							<tr>
								<td><?php echo esc_html($role_name); ?></td>
								<?php foreach ($this->features as $feature_slug => $feature_name) : 
									$checked = ($is_admin && $feature_slug === 'plugin_access') || isset($saved_permissions[$role_slug][$feature_slug]) ? 'checked' : '';
									$disabled = ($is_admin && $feature_slug === 'plugin_access') ? 'disabled' : '';
									?>
									<td>
										<input type="checkbox" 
											   name="wc_stocktaking_role_permissions[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($feature_slug); ?>]" 
											   value="1" 
											   <?php echo $checked; ?>
											   <?php echo $disabled; ?>>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2>Action Permissions</h2>
				<table class="widefat" style="margin-top: 20px;">
					<thead>
						<tr>
							<th>Role</th>
							<?php foreach ($this->actions as $action_slug => $action_name) : ?>
								<th><?php echo esc_html($action_name); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($roles as $role_slug => $role_name) : ?>
							<tr>
								<td><?php echo esc_html($role_name); ?></td>
								<?php foreach ($this->actions as $action_slug => $action_name) : 
										$checked = isset($saved_permissions[$role_slug][$action_slug]) ? 'checked' : '';
									?>
									<td>
										<input type="checkbox" 
											   name="wc_stocktaking_role_permissions[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($action_slug); ?>]" 
											   value="1" 
											   <?php echo $checked; ?>>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button('Save Settings'); ?>
			</form>
		</div>
		<?php
	}

	public static function user_can_access_feature($feature) {
		$enable_permissions = get_option('wc_stocktaking_enable_role_permissions', 0);
		if (!$enable_permissions) {
			return current_user_can('manage_options');
		}

		$user = wp_get_current_user();
		if (in_array('administrator', $user->roles)) {
			return true;
		}
		$permissions = get_option('wc_stocktaking_role_permissions', array());
		foreach ($user->roles as $role) {
			if (isset($permissions[$role][$feature]) && $permissions[$role][$feature]) {
				return true;
			}
		}
		return false;
	}

	public function load_features_and_actions() {
		$this->features = array(
			'plugin_access' => 'Plugin Access',
			'report_access' => 'Report Access',
			'audit_access' => 'Audit Access'
		);

		$this->actions = array(
			'edit_stock_count' => 'Edit Stock Count',
			'update_stock' => 'Update Stock',
			'update_audit' => 'Update Audit',
			'update_report' => 'Update Report'
		);
	}

	public function wc_stocktaking_enqueue($hook) {
		if ('toplevel_page_wc-stocktaking' !== $hook && 'stocktaking_page_wc-stocktaking-wizard' !== $hook && 'stocktaking_page_wc-stocktaking-reports' !== $hook) {
			return;
		}
		wp_enqueue_style('wc-stocktaking-style', plugin_dir_url(__FILE__) . 'includes/assets/css/stocktaking.css');
		wp_enqueue_style('wc-product-display-style', plugin_dir_url(__FILE__) . 'includes/assets/css/product-display.css');
		wp_enqueue_script('wc-stocktaking-script', plugin_dir_url(__FILE__) . 'includes/assets/js/stocktaking.js', array('jquery'), '1.0', true);
		wp_localize_script('wc-stocktaking-script', 'wc_stocktaking_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('wc_stocktaking_nonce')
		));
	}
}

endif;
