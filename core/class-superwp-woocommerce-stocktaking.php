<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Superwp_Woocommerce_Stocktaking' ) ) :

	/**
	 * Main Superwp_Woocommerce_Stocktaking Class.
	 *
	 * @package		SUPERWPWOO
	 * @subpackage	Classes/Superwp_Woocommerce_Stocktaking
	 * @since		0.9.9
	 * @author		thiarara
	 */
	final class Superwp_Woocommerce_Stocktaking {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	0.9.9
		 * @var		object|Superwp_Woocommerce_Stocktaking
		 */
		private static $instance;

		/**
		 * SUPERWPWOO helpers object.
		 *
		 * @access	public
		 * @since	0.9.9
		 * @var		object|Superwp_Woocommerce_Stocktaking_Helpers
		 */
		public $helpers;

		/**
		 * SUPERWPWOO settings object.
		 *
		 * @access	public
		 * @since	0.9.9
		 * @var		object|Superwp_Woocommerce_Stocktaking_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	0.9.9
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'superwp-woocommerce-stocktaking' ), '0.9.9' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	0.9.9
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'superwp-woocommerce-stocktaking' ), '0.9.9' );
		}

		/**
		 * Main Superwp_Woocommerce_Stocktaking Instance.
		 *
		 * Insures that only one instance of Superwp_Woocommerce_Stocktaking exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		0.9.9
		 * @static
		 * @return		object|Superwp_Woocommerce_Stocktaking	The one true Superwp_Woocommerce_Stocktaking
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Superwp_Woocommerce_Stocktaking ) ) {
				self::$instance					= new Superwp_Woocommerce_Stocktaking;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Superwp_Woocommerce_Stocktaking_Helpers();
				self::$instance->settings		= new Superwp_Woocommerce_Stocktaking_Settings();

				//Fire the plugin logic
				new Superwp_Woocommerce_Stocktaking_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'SUPERWPWOO/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   0.9.9
		 * @return  void
		 */
		private function includes() {
			require_once SUPERWPWOO_PLUGIN_DIR . 'core/includes/classes/class-superwp-woocommerce-stocktaking-helpers.php';
			require_once SUPERWPWOO_PLUGIN_DIR . 'core/includes/classes/class-superwp-woocommerce-stocktaking-settings.php';

			require_once SUPERWPWOO_PLUGIN_DIR . 'core/includes/classes/class-superwp-woocommerce-stocktaking-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   0.9.9
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   0.9.9
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'superwp-woocommerce-stocktaking', FALSE, dirname( plugin_basename( SUPERWPWOO_PLUGIN_FILE ) ) . '/languages/' );
		}
	}

	// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Hook to add a custom menu item in the admin panel after WooCommerce
add_action('admin_menu', 'wc_stocktaking_menu', 99);

function wc_stocktaking_menu() {
    $roles = get_option('wc_stocktaking_access_roles', ['administrator']);
    $user = wp_get_current_user();

    // Check if the current user can access the stocktaking dashboard
    if (array_intersect($roles, $user->roles)) {
        add_menu_page(
            'Stocktaking',
            'Stocktaking',
            'manage_woocommerce',
            'wc-stocktaking',
            'wc_stocktaking_page',
            'dashicons-chart-area',
            56
        );
    }

    // Only add the settings submenu for administrators
    if (current_user_can('administrator')) {
        add_submenu_page(
            'wc-stocktaking',
            'Stocktaking Settings',
            'Settings',
            'manage_options',
            'wc-stocktaking-settings',
            'wc_stocktaking_settings_page'
        );
    }
}

// Display the stocktaking page
function wc_stocktaking_page() {
    if (!current_user_can_access_stocktaking()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $options = get_option('wc_stocktaking_display_fields', ['product', 'sku', 'soh', 'co', 'variance', 'category', 'user']);
    echo '<div class="wrap">';
    echo '<h1>Stocktaking</h1>';
    echo '<input type="text" id="search-product" placeholder="Search Product" oninput="filterProducts()">';
    wc_stocktaking_display_products($options);
    echo '</div>';
}

function wc_stocktaking_display_products($options) {
    if (!class_exists('WooCommerce')) {
        echo '<p>WooCommerce is not active.</p>';
        return;
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );
    $products = get_posts($args);

    if (empty($products)) {
        echo '<p>No products found.</p>';
        return;
    }

    echo '<style>
       .wc-stocktaking-table {
            width: 100%;
            border-collapse: collapse;
        }
       .wc-stocktaking-table th, .wc-stocktaking-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
       .wc-stocktaking-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
       .negative-variance {
            color: red;
        }
       .positive-variance {
            color: green;
        }
       .no-variance {
            color: black;
        }
       .changed {
            background-color: #CCFFCC;
        }
       .below-soh {
            background-color: #FFC9C9;
        }
       .above-soh {
            background-color: #FEFFC9;
        }
    </style>';

    echo '<form method="post" action="'. admin_url('admin-post.php'). '">';
    echo '<input type="hidden" name="action" value="save_or_update_stock">';
    echo '<table class="wc-stocktaking-table">';
    echo '<thead><tr>';
    if (in_array('product', $options)) echo '<th>Product</th>';
    if (in_array('sku', $options)) echo '<th>SKU</th>';
    if (in_array('soh', $options)) echo '<th>Stock On Hand (SOH)</th>';
    if (in_array('co', $options)) echo '<th>Count Stock (CO)</th>';
    if (in_array('variance', $options)) echo '<th>Variance (CO - SOH)</th>';
    if (in_array('category', $options)) echo '<th>Category</th>';
    if (in_array('user', $options)) echo '<th>Counted By</th>';
    echo '</tr></thead>';
    echo '<tbody id="product-list">';

    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        $current_stock = $product->get_stock_quantity();
        $count_stock = get_post_meta($product->get_id(), '_count_stock', true);
        $count_stock = $count_stock !== '' ? $count_stock : 0;
        $user_id = get_post_meta($product->get_id(), '_count_stock_user', true);
        $user = $user_id ? get_user_by('id', $user_id)->display_name : 'admin';
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $category_names = array();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
        }
        $variance = $count_stock - $current_stock;
        $variance_class = 'no-variance';
        $row_class = '';
        if ($variance < 0) {
            $variance_class = 'negative-variance';
            $row_class = 'below-soh';
        } elseif ($variance > 0) {
            $variance_class = 'positive-variance';
            $row_class = 'above-soh';
        }
        echo '<tr class="product-row '. $row_class .'">';
        if (in_array('product', $options)) echo '<td>'. $product->get_name(). '</td>';
        if (in_array('sku', $options)) echo '<td>'. $product->get_sku(). '</td>';
        if (in_array('soh', $options)) echo '<td>'. $current_stock. '</td>';
        if (in_array('co', $options)) {
            echo '<td><input type="text" name="stock['. $product->get_id(). ']" value="'. $count_stock. '" data-current-stock="'. $count_stock .'" oninput="updateVariance(this, '. $current_stock. ')" '. (!current_user_can_update_stock() ? 'readonly' : '') .'></td>';
        }
        if (in_array('variance', $options)) echo '<td><span id="variance-'. $product->get_id(). '" class="variance '. $variance_class .'">'. $variance . '</span></td>';
        if (in_array('category', $options)) echo '<td>'. implode(', ', $category_names). '</td>';
        if (in_array('user', $options)) echo '<td id="user-'. $product->get_id(). '">'. $user . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '<br>';
    echo '<input type="submit" name="save_stock" class="button-primary" value="Save Current Count" style="margin-right: 10px;">';
    echo '<input type="submit" name="update_stock" class="button-primary" value="Update Stock" style="margin-left: 10px; margin-right: 10px;">';
    echo '<button type="button" class="button-secondary" onclick="downloadCSV()">Download CSV</button>';
    echo '</form>';

    echo '<script>
            function updateVariance(input, currentStock) {
                let newValue = input.value.trim();
                let currentInputStock = parseInt(input.dataset.currentStock);
                if (newValue.startsWith("+") || newValue.startsWith("-")) {
                    let change = parseInt(newValue);
                    newValue = currentInputStock + change;
                } else {
                    newValue = parseInt(newValue);
                }
                input.dataset.currentStock = newValue;

                const variance = newValue - currentStock;
                const varianceElement = document.getElementById("variance-" + input.name.match(/\\d+/)[0]);
                varianceElement.innerText = variance;

                const row = input.parentNode.parentNode;
                const wasChanged = row.classList.contains("changed");
                row.classList.add("changed");

                const userElement = document.getElementById("user-" + input.name.match(/\\d+/)[0]);
                userElement.innerText = "'. wp_get_current_user()->display_name .'";

                if (variance < 0) {
                    varianceElement.className = "variance negative-variance";
                    row.classList.add("below-soh");
                    row.classList.remove("above-soh");
                } else if (variance > 0) {
                    varianceElement.className = "variance positive-variance";
                    row.classList.add("above-soh");
                    row.classList.remove("below-soh");
                } else {
                    varianceElement.className = "variance no-variance";
                    row.classList.remove("above-soh");
                    row.classList.remove("below-soh");
                }

                if (wasChanged) {
                    row.classList.remove("changed"); // Remove the "changed" class if it was previously added
                }
            }

            function downloadCSV() {
                let csvContent = "data:text/csv;charset=utf-8,";
                csvContent += "Product,SKU,Stock On Hand (SOH),Count Stock (CO),Variance,Category,Counted By\n";

                const rows = document.querySelectorAll(".wc-stocktaking-table tbody tr");
                rows.forEach(row => {
                    const cols = row.querySelectorAll("td");
                    const product = cols[0] ? cols[0].innerText : "";
                    const sku = cols[1] ? cols[1].innerText : "";
                    const soh = cols[2] ? cols[2].innerText : "";
                    const co = cols[3] ? cols[3].querySelector("input").value : "";
                    const variance = cols[4] ? cols[4].innerText : "";
                    const category = cols[5] ? cols[5].innerText : "";
                    const user = cols[6] ? cols[6].innerText : "";

                    csvContent += `"${product}","${sku}",${soh},${co},${variance},"${category}","${user}"\n`;
                });

                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "stocktaking_data.csv");
                document.body.appendChild(link);

                link.click();
                document.body.removeChild(link);
            }

            function filterProducts() {
                const input = document.getElementById("search-product");
                const filter = input.value.toLowerCase();
                const rows = document.querySelectorAll(".wc-stocktaking-table tbody tr");

                rows.forEach(row => {
                    const product = row.querySelector("td").innerText.toLowerCase();
                    if (product.includes(filter)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }
        </script>';
}

add_action('admin_post_save_or_update_stock', 'wc_stocktaking_save_or_update_stock');

function wc_stocktaking_save_or_update_stock() {
    if (!current_user_can_access_stocktaking()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['stock'])) {
        $stock_data = $_POST['stock'];
        $user_id = get_current_user_id();
        $updated_products = [];

        foreach ($stock_data as $product_id => $new_count) {
            $current_stock = get_post_meta($product_id, '_stock', true);
            $new_count = trim($new_count);
            
            if ($new_count === '') {
                $new_count = 0;
            } elseif (preg_match('/^[+-]?\d+(\+\d+)*$/', $new_count)) {
                $parts = explode('+', $new_count);
                $new_count = array_sum(array_map('intval', $parts));
            } elseif (preg_match('/^[+-]\d+$/', $new_count)) {
                $new_count = $current_stock + intval($new_count);
            }

            $new_count = intval($new_count);

            if (isset($_POST['save_stock'])) {
                update_post_meta($product_id, '_count_stock', $new_count);
                update_post_meta($product_id, '_count_stock_user', $user_id);
            } elseif (isset($_POST['update_stock'])) {
                $updated_products[$product_id] = $new_count;
            }
        }

        if (isset($_POST['update_stock'])) {
            update_option('wc_stocktaking_updated_products', $updated_products);
            wp_redirect(admin_url('admin.php?page=wc-stocktaking-confirmation'));
            exit;
        }
    }

    wp_redirect(admin_url('admin.php?page=wc-stocktaking'));
    exit;
}

add_action('admin_menu', 'wc_stocktaking_add_confirmation_page');

function wc_stocktaking_add_confirmation_page() {
    add_submenu_page(
        null,
        'Stock Update Confirmation',
        'Stock Update Confirmation',
        'manage_woocommerce',
        'wc-stocktaking-confirmation',
        'wc_stocktaking_confirmation_page'
    );
}

function wc_stocktaking_confirmation_page() {
    if (!current_user_can_access_stocktaking()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $updated_products = get_option('wc_stocktaking_updated_products', []);
    echo '<div class="wrap">';
    echo '<h1>Stock Update Confirmation</h1>';
    echo '<p>Are you sure you want to update the stock for the following products?</p>';
    echo '<ul>';
    foreach ($updated_products as $product_id => $new_count) {
        $product = wc_get_product($product_id);
        echo '<li>'. $product->get_name(). ' (ID: '. $product_id. '): '. $new_count. ' units</li>';
    }
    echo '</ul>';
    echo '<p><strong>Note:</strong> Once the stock update is done, it cannot be undone.</p>';
    echo '<form method="post" action="'. admin_url('admin-post.php'). '">';
    echo '<input type="hidden" name="action" value="confirm_update_stock">';
    echo '<input type="submit" class="button-primary" value="Confirm">';
    echo '</form>';
    echo '<form method="post" action="'. admin_url('admin-post.php'). '">';
    echo '<input type="hidden" name="action" value="cancel_update_stock">';
    echo '<input type="submit" class="button-secondary" value="Cancel">';
    echo '</form>';
    echo '</div>';
}

add_action('admin_post_confirm_update_stock', 'wc_stocktaking_confirm_update_stock');

function wc_stocktaking_confirm_update_stock() {
    if (!current_user_can_access_stocktaking()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $updated_products = get_option('wc_stocktaking_updated_products', []);

    foreach ($updated_products as $product_id => $new_count) {
        update_post_meta($product_id, '_stock', $new_count);
        delete_post_meta($product_id, '_count_stock');
        delete_post_meta($product_id, '_count_stock_user');
    }

    delete_option('wc_stocktaking_updated_products');

    wp_redirect(admin_url('admin.php?page=wc-stocktaking'));
    exit;
}

add_action('admin_post_cancel_update_stock', 'wc_stocktaking_cancel_update_stock');

function wc_stocktaking_cancel_update_stock() {
    if (!current_user_can_access_stocktaking()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Clear the updated products option
    delete_option('wc_stocktaking_updated_products');

    // Redirect to the stocktaking page
    wp_redirect(admin_url('admin.php?page=wc-stocktaking'));
    exit;
}

// Stocktaking settings page
function wc_stocktaking_settings_page() {
    if (!current_user_can('administrator')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['wc_stocktaking_save_settings'])) {
        check_admin_referer('wc_stocktaking_settings_nonce');

        $roles = isset($_POST['wc_stocktaking_access_roles']) ? (array) $_POST['wc_stocktaking_access_roles'] : [];
        update_option('wc_stocktaking_access_roles', $roles);

        $display_fields = isset($_POST['wc_stocktaking_display_fields']) ? (array) $_POST['wc_stocktaking_display_fields'] : [];
        update_option('wc_stocktaking_display_fields', $display_fields);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $roles = get_option('wc_stocktaking_access_roles', ['administrator']);
    $all_roles = wp_roles()->roles;
    $display_fields = get_option('wc_stocktaking_display_fields', ['product', 'sku', 'soh', 'co', 'variance', 'category', 'user']);

    echo '<div class="wrap">';
    echo '<h1>Stocktaking Settings</h1>';
    echo '<form method="post" action="">';
    wp_nonce_field('wc_stocktaking_settings_nonce');

    echo '<h2>Access Roles</h2>';
    echo '<p>Select the roles that can access the stocktaking dashboard:</p>';
    foreach ($all_roles as $role_slug => $role) {
        $checked = in_array($role_slug, $roles) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="wc_stocktaking_access_roles[]" value="'. esc_attr($role_slug). '" '. $checked. '>';
        echo esc_html($role['name']);
        echo '</label><br>';
    }

    echo '<h2>Display Fields</h2>';
    echo '<p>Select the fields to display on the stocktaking dashboard:</p>';
    $fields = [
        'product' => 'Product',
        'sku' => 'SKU',
        'soh' => 'Stock On Hand (SOH)',
        'co' => 'Count Stock (CO)',
        'variance' => 'Variance',
        'category' => 'Category',
        'user' => 'Counted By'
    ];

    foreach ($fields as $field_key => $field_label) {
        $checked = in_array($field_key, $display_fields) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="wc_stocktaking_display_fields[]" value="'. esc_attr($field_key). '" '. $checked. '>';
        echo esc_html($field_label);
        echo '</label><br>';
    }

    echo '<p><input type="submit" name="wc_stocktaking_save_settings" class="button-primary" value="Save Settings"></p>';
    echo '</form>';
    echo '</div>';
}

function current_user_can_access_stocktaking() {
    $roles = get_option('wc_stocktaking_access_roles', ['administrator']);
    $user = wp_get_current_user();

    return array_intersect($roles, $user->roles);
}

function current_user_can_update_stock() {
    $roles = get_option('wc_stocktaking_access_roles', ['administrator']);
    $user = wp_get_current_user();

    return in_array('administrator', $user->roles);
}

endif; // End if class_exists check.