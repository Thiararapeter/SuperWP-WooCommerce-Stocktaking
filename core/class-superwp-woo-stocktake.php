<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Superwp_Woo_Stocktake' ) ) :

	/**
	 * Main Superwp_Woo_Stocktake Class.
	 *
	 * @package		SUPERWPSTOCKTAKE
	 * @subpackage	Classes/Superwp_Woo_Stocktake
	 * @since		1.0.01
	 * @author		Thiarara SuperWP
	 */
	final class Superwp_Woo_Stocktake {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.01
		 * @var		object|Superwp_Woo_Stocktake
		 */
		private static $instance;

		/**
		 * SUPERWPSTOCKTAKE helpers object.
		 *
		 * @access	public
		 * @since	1.0.01
		 * @var		object|Superwp_Woo_Stocktake_Helpers
		 */
		public $helpers;

		/**
		 * SUPERWPSTOCKTAKE settings object.
		 *
		 * @access	public
		 * @since	1.0.01
		 * @var		object|Superwp_Woo_Stocktake_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.01
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'superwp-woo-stocktake' ), '1.0.01' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.01
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'superwp-woo-stocktake' ), '1.0.01' );
		}

		/**
		 * Main Superwp_Woo_Stocktake Instance.
		 *
		 * Insures that only one instance of Superwp_Woo_Stocktake exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.01
		 * @static
		 * @return		object|Superwp_Woo_Stocktake	The one true Superwp_Woo_Stocktake
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Superwp_Woo_Stocktake ) ) {
				self::$instance = new Superwp_Woo_Stocktake;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers = new Superwp_Woo_Stocktake_Helpers();
				self::$instance->settings = new Superwp_Woo_Stocktake_Settings();

				// Initialize the wizard
				new Superwp_Woo_Stocktake_Wizard();

				// Initialize the reports
				new Superwp_Woo_Stocktake_Reports();

				// Initialize the audit
				new Superwp_Woo_Stocktake_Audit();

				//Fire the plugin logic
				new Superwp_Woo_Stocktake_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'SUPERWPSTOCKTAKE/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.01
		 * @return  void
		 */
		private function includes() {
			require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/includes/classes/class-superwp-woo-stocktake-helpers.php';
			require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/includes/classes/class-superwp-woo-stocktake-settings.php';
			require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/includes/classes/class-superwp-woo-stocktake-run.php';
			require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/includes/classes/class-superwp-woo-stocktake-wizard.php';
			require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/includes/classes/class-superwp-woo-stocktake-reports.php';
			require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/includes/classes/class-superwp-woo-stocktake-audit.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.01
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.01
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'superwp-woo-stocktake', FALSE, dirname( plugin_basename( SUPERWPSTOCKTAKE_PLUGIN_FILE ) ) . '/languages/' );
		}

		public function __construct() {
			// Initialize the plugin
			add_action('admin_menu', array($this, 'wc_stocktaking_menu'));
			add_action('admin_enqueue_scripts', array($this, 'wc_stocktaking_enqueue'));
			add_action('wp_ajax_wc_stocktaking_update_count', array($this, 'wc_stocktaking_update_count'));
			add_action('wp_ajax_wc_stocktaking_save_count', array($this, 'wc_stocktaking_save_count'));
			add_action('wp_ajax_wc_stocktaking_search_products', array($this, 'wc_stocktaking_search_products'));
		}

		// Add admin menu items
		public function wc_stocktaking_menu() {
			add_menu_page(
				'WooCommerce Stocktaking',
				'Stocktaking',
				'manage_options',
				'wc-stocktaking',
				array($this, 'wc_stocktaking_page'),
				'dashicons-clipboard',
				56
			);
			add_submenu_page(
				null,
				'Close Stocktake',
				'Close Stocktake',
				'manage_options',
				'wc-stocktaking-close',
				array($this, 'wc_stocktaking_closing_wizard')
			);
		}

		// Stocktaking page content
		public function wc_stocktaking_page() {
			$active_stocktake = $this->wc_stocktaking_get_active_stocktake();
			if (!$active_stocktake) {
				echo '<div class="wrap"><h1>WooCommerce Stocktaking</h1>';
				echo '<p>There is no active stocktake. Please <a href="' . admin_url('admin.php?page=wc-stocktaking-wizard') . '">start a new stocktake</a>.</p>';
				echo '</div>';
				return;
			}

			$stocktake_id = $active_stocktake->ID;
			$stocktake_name = $active_stocktake->post_title;
			$stocktake_date = get_post_meta($stocktake_id, '_stocktake_date', true);
			$include_out_of_stock = get_post_meta($stocktake_id, '_include_out_of_stock', true);
			$product_categories = get_post_meta($stocktake_id, '_product_categories', true);

			$total_counted = 0;
			$total_counted_value = 0;
			$total_current_stock = 0;
			$total_current_stock_value = 0;
			?>
			<div class="wrap">
				<h1>WooCommerce Stocktaking: <?php echo esc_html($stocktake_name); ?></h1>
				<p>Date: <?php echo esc_html($stocktake_date); ?></p>
				
				<!-- Add search form with stocktake ID -->
				<form id="product-search-form">
					<input type="hidden" id="stocktake-id" value="<?php echo esc_attr($stocktake_id); ?>">
					<input type="text" id="product-search" placeholder="Search products...">
					<button type="submit" class="button">Search</button>
				</form>

				<div id="loading-indicator" style="display: none;">
					<img src="<?php echo admin_url('images/spinner.gif'); ?>" alt="Loading..."> Updating...
				</div>

				<form id="stocktaking-form" method="post">
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>Product</th>
								<th>SKU</th>
								<th>Current Stock</th>
								<th>Current Stock Value</th>
								<th>New Count</th>
								<th>Counted</th>
								<th>Counted Value</th>
							</tr>
						</thead>
						<tbody id="product-list">
							<?php
							$args = array(
								'post_type' => 'product',
								'posts_per_page' => -1,
							);
							if (!empty($product_categories)) {
								$args['tax_query'] = array(
									array(
										'taxonomy' => 'product_cat',
										'field' => 'term_id',
										'terms' => $product_categories,
									),
								);
							}
							if (!$include_out_of_stock) {
								$args['meta_query'] = array(
									array(
										'key' => '_stock_status',
										'value' => 'instock',
										'compare' => '=',
									),
								);
							}
							$products = wc_get_products($args);
							foreach ($products as $product) :
								$product_id = $product->get_id();
								$current_stock = $product->get_stock_quantity();
								$price = $product->get_price();
								$current_stock_value = $current_stock * $price;
								$counted_stock = get_post_meta($product_id, '_counted_stock', true) ?: 0;
								$counted_value = $counted_stock * $price;

								$total_current_stock += $current_stock;
								$total_current_stock_value += $current_stock_value;
								$total_counted += $counted_stock;
								$total_counted_value += $counted_value;
								?>
								<tr>
									<td><?php echo esc_html($product->get_name()); ?></td>
									<td><?php echo esc_html($product->get_sku()); ?></td>
									<td class="current-stock"><?php echo esc_html($current_stock); ?></td>
									<td class="current-stock-value"><?php echo wc_price($current_stock_value); ?></td>
									<td>
										<input type="number" class="new-count" name="new_count[<?php echo esc_attr($product_id); ?>]" 
											   data-product-id="<?php echo esc_attr($product_id); ?>" 
											   data-price="<?php echo esc_attr($price); ?>" value="0" min="0">
									</td>
									<td>
										<span class="counted" id="counted_<?php echo esc_attr($product_id); ?>"><?php echo esc_html($counted_stock); ?></span>
									</td>
									<td>
										<span class="counted-value" id="counted_value_<?php echo esc_attr($product_id); ?>"><?php echo wc_price($counted_value); ?></span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="2">Totals:</th>
								<th>Products: <span id="total-products"></span></th>
								<th>Current Products Stock: <span id="total-current-stock"></span></th>
								<th>Current Stock Value: <span id="total-current-stock-value"></span></th>
								<th>Total Products Counted: <span id="total-counted"></span></th>
								<th>Total Counted Value: <span id="total-counted-value"></span></th>
							</tr>
						</tfoot>
					</table>
					<div class="stocktaking-actions">
						<button id="save-count" class="button button-primary">Save Count</button>
						<a href="<?php echo admin_url('admin.php?page=wc-stocktaking-close&stocktake_id=' . $stocktake_id); ?>" class="button">Close Stocktake</a>
					</div>
				</form>
				<div id="stocktaking-messages"></div>
			</div>
			<?php
		}

		// Closing wizard
		public function wc_stocktaking_closing_wizard() {
			$active_stocktake = $this->wc_stocktaking_get_active_stocktake();
			if (!$active_stocktake) {
				wp_die('There is no active stocktake to close.');
			}

			$stocktake_id = $active_stocktake->ID;

			if (isset($_POST['close_stocktake'])) {
				$this->wc_stocktaking_process_closing($stocktake_id);
			}

			?>
			<div class="wrap">
				<h1>Close Stocktake: <?php echo esc_html($active_stocktake->post_title); ?></h1>
				<form method="post" action="">
					<?php wp_nonce_field('close_stocktake', 'stocktake_closing_nonce'); ?>
					<p>Are you sure you want to close this stocktake? This will update the stock levels in WooCommerce and mark the stocktake as closed.</p>
					<input type="submit" name="close_stocktake" class="button button-primary" value="Close Stocktake">
				</form>
			</div>
			<?php
		}

		// Process closing stocktake
		public function wc_stocktaking_process_closing($stocktake_id) {
			check_admin_referer('close_stocktake', 'stocktake_closing_nonce');

			$discrepancies = array();
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
			);
			$products = wc_get_products($args);

			foreach ($products as $product) {
				$product_id = $product->get_id();
				$expected_stock = $product->get_stock_quantity();
				$counted_stock = get_post_meta($product_id, '_counted_stock', true) ?: 0;
				$discrepancy = $counted_stock - $expected_stock;

				if ($discrepancy != 0) {
					$discrepancies[$product_id] = array(
						'expected' => $expected_stock,
						'counted' => $counted_stock,
						'discrepancy' => $discrepancy
					);

					// Update WooCommerce stock
					wc_update_product_stock($product, $counted_stock, 'set');
				}

				// Reset counted stock
				delete_post_meta($product_id, '_counted_stock');
			}

			// Save discrepancies and update stocktake status
			update_post_meta($stocktake_id, '_stock_discrepancies', $discrepancies);
			update_post_meta($stocktake_id, '_stocktake_status', 'Closed');
			update_post_meta($stocktake_id, '_stocktake_closed_date', current_time('mysql'));

			wp_redirect(admin_url('admin.php?page=wc-stocktaking-reports&stocktake_id=' . $stocktake_id));
			exit;
		}

		// Helper function to get active stocktake
		public function wc_stocktaking_get_active_stocktake() {
			$args = array(
				'post_type' => 'stocktake',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key' => '_stocktake_status',
						'value' => 'Open',
					),
				),
			);
			$stocktakes = get_posts($args);
			return !empty($stocktakes) ? $stocktakes[0] : null;
		}

		// Enqueue styles and scripts
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
			wp_enqueue_script('wc-stocktake-audit-report', plugin_dir_url(__FILE__) . 'includes/assets/js/stocktake-audit-report.js', array('jquery'), '1.0', true);
		}

		// Modify the AJAX handler for updating count
		public function wc_stocktaking_update_count() {
			check_ajax_referer('wc_stocktaking_nonce', 'security');

			$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
			$new_count = isset($_POST['new_count']) ? intval($_POST['new_count']) : 0;
			$reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';

			$product = wc_get_product($product_id);
			if (!$product) {
				wp_send_json_error(array('message' => 'Invalid product.'));
				return;
			}

			$current_stock = $product->get_stock_quantity();
			$discrepancy = $new_count - $current_stock;
			$variance_type = $discrepancy > 0 ? 'Positive Variance' : ($discrepancy < 0 ? 'Negative Variance' : 'No Variance');

			$stocktake_id = get_option('current_stocktake_id');
			$discrepancies = get_post_meta($stocktake_id, '_stock_discrepancies', true) ?: array();

			$discrepancies[$product_id] = array(
				'expected' => $current_stock,
				'counted' => $new_count,
				'discrepancy' => $discrepancy,
				'variance_type' => $variance_type,
				'reason' => $reason
			);

			update_post_meta($stocktake_id, '_stock_discrepancies', $discrepancies);

			wp_send_json_success(array(
				'message' => 'Count updated successfully.',
				'new_count' => $new_count,
				'discrepancy' => $discrepancy,
				'variance_type' => $variance_type
			));
		}

		// Save count
		public function wc_stocktaking_save_count() {
			check_ajax_referer('wc_stocktaking_nonce', 'security');

			$new_counts = isset($_POST['new_counts']) ? $_POST['new_counts'] : array();
			$stocktake_id = isset($_POST['stocktake_id']) ? intval($_POST['stocktake_id']) : 0;

			if ($stocktake_id <= 0) {
				wp_send_json_error(array('message' => 'Invalid stocktake ID'));
				return;
			}

			$this->process_stocktake_data($stocktake_id, $new_counts);

			wp_send_json_success(array('message' => 'Count saved and processed successfully.'));
		}

		// Process stocktake data
		private function process_stocktake_data($stocktake_id, $stocktake_data) {
			$discrepancies = array();
			$total_value = 0;
			$sku_count_with_discrepancies = 0;

			foreach ($stocktake_data as $product_id => $count) {
				$product = wc_get_product($product_id);
				if (!$product) continue;

				$current_stock = $product->get_stock_quantity();
				$discrepancy = $count - $current_stock;

				if ($discrepancy !== 0) {
					$discrepancies[$product_id] = array(
						'counted' => $count,
						'current_stock' => $current_stock,
						'discrepancy' => $discrepancy,
						'reason' => '',  // This can be filled later in the audit process
					);
					$sku_count_with_discrepancies++;
				}

				$total_value += $product->get_price() * $count;
			}

			update_post_meta($stocktake_id, '_stock_discrepancies', $discrepancies);
			update_post_meta($stocktake_id, '_total_value', $total_value);
			update_post_meta($stocktake_id, '_sku_count_with_discrepancies', $sku_count_with_discrepancies);
		}

		// Modify the AJAX handler for product search
		public function wc_stocktaking_search_products() {
			check_ajax_referer('wc_stocktaking_nonce', 'security');

			$search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
			$stocktake_id = isset($_POST['stocktake_id']) ? intval($_POST['stocktake_id']) : 0;

			if ($stocktake_id <= 0) {
				wp_send_json_error(array('message' => 'Invalid stocktake ID'));
				return;
			}

			$include_out_of_stock = get_post_meta($stocktake_id, '_include_out_of_stock', true);
			$product_categories = get_post_meta($stocktake_id, '_product_categories', true);

			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				's' => $search_term,
			);

			if (!empty($product_categories)) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'term_id',
						'terms' => $product_categories,
					),
				);
			}

			if (!$include_out_of_stock) {
				$args['meta_query'] = array(
					array(
						'key' => '_stock_status',
						'value' => 'instock',
						'compare' => '=',
					),
				);
			}

			$products = wc_get_products($args);

			ob_start();
			foreach ($products as $product) :
				$product_id = $product->get_id();
				$current_stock = $product->get_stock_quantity();
				$price = $product->get_price();
				$current_stock_value = $current_stock * $price;
				$counted_stock = get_post_meta($product_id, '_counted_stock', true) ?: 0;
				$counted_value = $counted_stock * $price;
				?>
				<tr>
					<td><?php echo esc_html($product->get_name()); ?></td>
					<td><?php echo esc_html($product->get_sku()); ?></td>
					<td class="current-stock"><?php echo esc_html($current_stock); ?></td>
					<td class="current-stock-value"><?php echo wc_price($current_stock_value); ?></td>
					<td>
						<input type="number" class="new-count" name="new_count[<?php echo esc_attr($product_id); ?>]" 
							   data-product-id="<?php echo esc_attr($product_id); ?>" 
							   data-price="<?php echo esc_attr($price); ?>" value="0" min="0">
					</td>
					<td>
						<span class="counted" id="counted_<?php echo esc_attr($product_id); ?>"><?php echo esc_html($counted_stock); ?></span>
					</td>
					<td>
						<span class="counted-value" id="counted_value_<?php echo esc_attr($product_id); ?>"><?php echo wc_price($counted_value); ?></span>
					</td>
				</tr>
			<?php
			endforeach;
			$html = ob_get_clean();

			wp_send_json_success(array('html' => $html));
		}
	}

endif; // End if class_exists check.