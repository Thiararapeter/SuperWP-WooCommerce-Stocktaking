<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Superwp_Woocommerce_Stocktaking_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		SUPERWPWOO
 * @subpackage	Classes/Superwp_Woocommerce_Stocktaking_Settings
 * @author		thiarara
 * @since		0.9.9
 */
class Superwp_Woocommerce_Stocktaking_Settings{

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   0.9.9
	 */
	private $plugin_name;

	/**
	 * Our Superwp_Woocommerce_Stocktaking_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 0.9.9
	 */
	function __construct(){

		$this->plugin_name = SUPERWPWOO_NAME;
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
	 * @since	0.9.9
	 * @return	string The plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'SUPERWPWOO/settings/get_plugin_name', $this->plugin_name );
	}
}
