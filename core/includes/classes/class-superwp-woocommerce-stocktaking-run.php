<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Superwp_Woocommerce_Stocktaking_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		SUPERWPWOO
 * @subpackage	Classes/Superwp_Woocommerce_Stocktaking_Run
 * @author		thiarara
 * @since		0.9.9
 */
class Superwp_Woocommerce_Stocktaking_Run{

	/**
	 * Our Superwp_Woocommerce_Stocktaking_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 0.9.9
	 */
	function __construct(){
		$this->add_hooks();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	0.9.9
	 * @return	void
	 */
	private function add_hooks(){
	
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts_and_styles' ), 20 );
	
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	/**
	 * Enqueue the backend related scripts and styles for this plugin.
	 * All of the added scripts andstyles will be available on every page within the backend.
	 *
	 * @access	public
	 * @since	0.9.9
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles() {
		wp_enqueue_style( 'superwpwoo-backend-styles', SUPERWPWOO_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), SUPERWPWOO_VERSION, 'all' );
		wp_enqueue_script( 'superwpwoo-backend-scripts', SUPERWPWOO_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array(), SUPERWPWOO_VERSION, false );
		wp_localize_script( 'superwpwoo-backend-scripts', 'superwpwoo', array(
			'plugin_name'   	=> __( SUPERWPWOO_NAME, 'superwp-woocommerce-stocktaking' ),
		));
	}

}
