<?php
/**
 * SuperWP WooCommerce Stocktaking
 *
 * @package       SUPERWPWOO
 * @author        thiarara
 * @license       gplv2
 * @version       0.9.9
 *
 * @wordpress-plugin
 * Plugin Name:   SuperWP WooCommerce Stocktaking
 * Plugin URI:    https://github.com/Thiararapeter/SuperWP-WooCommerce-Stocktaking
 * Description:   WooCommerce Stocktaking simplifies inventory management with tools for efficient and accurate stock takes. Enjoy real-time updates, user-friendly entry interfaces, and detailed reporting. Seamlessly integrated with WooCommerce, it helps maintain optimal inventory levels and prevents discrepancies. Keep your stock in check effortlessly.
 * Version:       0.9.9
 * Author:        thiarara
 * Author URI:    https://profiles.wordpress.org/thiarara/
 * Text Domain:   superwp-woocommerce-stocktaking
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with SuperWP WooCommerce Stocktaking. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'SUPERWPWOO_NAME',			'SuperWP WooCommerce Stocktaking' );

// Plugin version
define( 'SUPERWPWOO_VERSION',		'0.9.9' );

// Plugin Root File
define( 'SUPERWPWOO_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'SUPERWPWOO_PLUGIN_BASE',	plugin_basename( SUPERWPWOO_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'SUPERWPWOO_PLUGIN_DIR',	plugin_dir_path( SUPERWPWOO_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'SUPERWPWOO_PLUGIN_URL',	plugin_dir_url( SUPERWPWOO_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once SUPERWPWOO_PLUGIN_DIR . 'core/class-superwp-woocommerce-stocktaking.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  thiarara
 * @since   0.9.9
 * @return  object|Superwp_Woocommerce_Stocktaking
 */
function SUPERWPWOO() {
	return Superwp_Woocommerce_Stocktaking::instance();
}

SUPERWPWOO();
