<?php
/**
 * superWP Woo Stocktake
 *
 * @package       SUPERWPSTOCKTAKE
 * @author        Thiarara SuperWP
 * @license       gplv2-or-later
 * @version       1.0.01
 *
 * @wordpress-plugin
 * Plugin Name:   superWP Woo Stocktake
 * Plugin URI:    https://github.com/Thiararapeter/SuperWP-WooCommerce-Stocktaking
 * Description:   A stocktaking system for WooCommerce with count transfer, reset functionality, and totals
 * Version:       1.0.01
 * Author:        Thiarara SuperWP
 * Author URI:    https://profiles.wordpress.org/thiarara/
 * Text Domain:   superwp-woo-stocktake
 * Domain Path:   /languages
 * License:       GPLv2 or later
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with superWP Woo Stocktake. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'SUPERWPSTOCKTAKE_NAME',			'superWP Woo Stocktake' );

// Plugin version
define( 'SUPERWPSTOCKTAKE_VERSION',		'1.0.01' );

// Plugin Root File
define( 'SUPERWPSTOCKTAKE_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'SUPERWPSTOCKTAKE_PLUGIN_BASE',	plugin_basename( SUPERWPSTOCKTAKE_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'SUPERWPSTOCKTAKE_PLUGIN_DIR',	plugin_dir_path( SUPERWPSTOCKTAKE_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'SUPERWPSTOCKTAKE_PLUGIN_URL',	plugin_dir_url( SUPERWPSTOCKTAKE_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once SUPERWPSTOCKTAKE_PLUGIN_DIR . 'core/class-superwp-woo-stocktake.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Thiarara SuperWP
 * @since   1.0.01
 * @return  object|Superwp_Woo_Stocktake
 */
function SUPERWPSTOCKTAKE() {
	return Superwp_Woo_Stocktake::instance();
}

SUPERWPSTOCKTAKE();
