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
	 * @since	1.0.01
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles() {
		wp_enqueue_style( 'superwpstocktake-backend-styles', SUPERWPSTOCKTAKE_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), SUPERWPSTOCKTAKE_VERSION, 'all' );

		if( ! wp_script_is( 'heartbeat' ) ){
			//enqueue the Heartbeat API
			wp_enqueue_script( 'heartbeat' );
		}

		wp_enqueue_script( 'superwpstocktake-backend-scripts', SUPERWPSTOCKTAKE_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array( 'jquery' ), SUPERWPSTOCKTAKE_VERSION, true );
		wp_localize_script( 'superwpstocktake-backend-scripts', 'superwpstocktake', array(
			'plugin_name'   	=> __( SUPERWPSTOCKTAKE_NAME, 'superwp-woo-stocktake' ),
			'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
			'security_nonce'	=> wp_create_nonce( "your-nonce-name" ),
		));
	}


	/**
	 * Enqueue the frontend related scripts and styles for this plugin.
	 *
	 * @access	public
	 * @since	1.0.01
	 *
	 * @return	void
	 */
	public function enqueue_frontend_scripts_and_styles() {
		wp_enqueue_style( 'superwpstocktake-frontend-styles', SUPERWPSTOCKTAKE_PLUGIN_URL . 'core/includes/assets/css/frontend-styles.css', array(), SUPERWPSTOCKTAKE_VERSION, 'all' );

		if( ! wp_script_is( 'heartbeat' ) ){
			//enqueue the Heartbeat API
			wp_enqueue_script( 'heartbeat' );
		}

		wp_enqueue_script( 'superwpstocktake-frontend-scripts', SUPERWPSTOCKTAKE_PLUGIN_URL . 'core/includes/assets/js/frontend-scripts.js', array( 'jquery' ), SUPERWPSTOCKTAKE_VERSION, true );
		wp_localize_script( 'superwpstocktake-frontend-scripts', 'superwpstocktake', array(
			'demo_var'   		=> __( 'This is some demo text coming from the backend through a variable within javascript.', 'superwp-woo-stocktake' ),
			'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
			'security_nonce'	=> wp_create_nonce( "your-nonce-name" ),
		));
	}


	/**
	 * The callback function for my_demo_ajax_call
	 *
	 * @access	public
	 * @since	1.0.01
	 *
	 * @return	void
	 */
	public function my_demo_ajax_call_callback() {
		check_ajax_referer( 'your-nonce-name', 'ajax_nonce_parameter' );

		$demo_data = isset( $_REQUEST['demo_data'] ) ? sanitize_text_field( $_REQUEST['demo_data'] ) : '';
		$response = array( 'success' => false );

		if ( ! empty( $demo_data ) ) {
			$response['success'] = true;
			$response['msg'] = __( 'The value was successfully filled.', 'superwp-woo-stocktake' );
		} else {
			$response['msg'] = __( 'The sent value was empty.', 'superwp-woo-stocktake' );
		}

		if( $response['success'] ){
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}

		die();
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
