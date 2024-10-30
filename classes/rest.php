<?php

class Meow_JPNG_Rest
{
	private $core = null;
	private $namespace = 'jipangu/v1';

	public function __construct( $core ) {
		if ( !current_user_can( 'administrator' ) ) {
			return;
		} 
		$this->core = $core;
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		try {
			register_rest_route( $this->namespace, '/sync_with_jipangu', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_sync_with_jipangu' )
			) );
		}
		catch ( Exception $e ) {
			var_dump( $e );
		}
	}

	public function rest_sync_with_jipangu( $request ) {
		try	{
			$json = $request->get_json_params();
			$jipangu_token = isset( $json['jipangu_token'] ) ? $json['jipangu_token'] : null;
			if ( !$jipangu_token ) {
				throw new Exception( 'Jipangu Token is required.' );
			}

			$jipangu_data = $this->core->get_spots( $jipangu_token );

			$this->core->update_option( 'jipangu_data', $jipangu_data );
			$this->core->update_option( 'jipangu_token', $jipangu_token );

			return new WP_REST_Response( [
				'message' => 'Registered successfully.',
			], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'message' => $e->getMessage() ], 500 );
		}
	}
}
