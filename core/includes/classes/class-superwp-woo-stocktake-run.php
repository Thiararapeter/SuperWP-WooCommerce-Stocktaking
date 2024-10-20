<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Superwp_Woo_Stocktake_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		SUPERWPSTOCKTAKE
 * @subpackage	Classes/Superwp_Woo_Stocktake_Run
 * @author		Thiarara SuperWP
 * @since		1.0.01
 */
class Superwp_Woo_Stocktake_Run{

	/**
	 * Our Superwp_Woo_Stocktake_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.01
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
	 * @since	1.0.01
	 * @return	void
	 */
	private function add_hooks(){
	
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts_and_styles' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts_and_styles' ), 20 );
		add_action( 'wp_ajax_nopriv_my_demo_ajax_call', array( $this, 'my_demo_ajax_call_callback' ), 20 );
		add_action( 'wp_ajax_my_demo_ajax_call', array( $this, 'my_demo_ajax_call_callback' ), 20 );
		add_action( 'heartbeat_nopriv_received', array( $this, 'myplugin_receive_heartbeat' ), 20, 2 );
		add_action( 'heartbeat_received', array( $this, 'myplugin_receive_heartbeat' ), 20, 2 );
	
	}


	/**
	 * The callback function for heartbeat_received
	 *
	 * @access	public
	 * @since	1.0.01
	 *
	 * @param	array	$response	Heartbeat response data to pass back to front end.
	 * @param	array	$data		Data received from the front end (unslashed).
	 *
	 * @return	array	$response	The adjusted heartbeat response data
	 */
	public function myplugin_receive_heartbeat( $response, $data ) {

		//If we didn't receive our data, don't send any back.
		if( empty( $data['myplugin_customfield'] ) ){
			return $response;
		}

		// Calculate our data and pass it back. For this example, we'll hash it.
		$received_data = $data['myplugin_customfield'];

		$response['myplugin_customfield_hashed'] = sha1( $received_data );

		return $response;
	}

}
