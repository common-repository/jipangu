<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

require_once( JPNG_PATH . '/constants/init.php' );
class Meow_JPNG_Core
{
	public $admin = null;
	public $is_rest = false;
	public $is_cli = false;
	public $site_url = null;
	private $option_name = 'jpng_options';

	public function __construct() {
		$this->site_url = get_site_url();
		$this->is_rest = Meow_JPNG_Helpers::is_rest();
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	function init() {
		// Part of the core, settings and stuff
		$this->admin = new Meow_JPNG_Admin( $this );

		// Only for REST
		if ( $this->is_rest ) {
			new Meow_JPNG_Rest( $this );
		}

	}

	#region Capabilities

	function can_access_settings() {
		return apply_filters( 'jpng_allow_setup', current_user_can( 'manage_options' ) );
	}

	function can_access_features() {
		return apply_filters( 'jpng_allow_usage', current_user_can( 'administrator' ) );
	}

	#endregion

	#region Options

	function get_option_name() {
		return $this->option_name;
	}

	function get_option( $option, $default = null ) {
		$options = $this->get_all_options();
		return $options[$option] ?? $default;
	}

	function get_all_options( $force = false ) {
		// We could cache options this way, but if we do, the apply_filters seems to be called too early.
		// That causes issues with filters used to modify the options dynamically (in AI Engine, for example).
		// if ( !$force && !is_null( $this->options ) ) {
		// 	return $this->options;
		// }
		$options = get_option( $this->option_name, [] );
		foreach ( JPNG_OPTIONS as $key => $value ) {
			if ( !isset( $options[$key] ) ) {
				$options[$key] = $value;
			}
		}
		return $options;
	}

	function update_options( $options ) {
		if ( !update_option( $this->option_name, $options, false ) ) {
			return false;
		}
		$options = $this->get_all_options( true );
		return $options;
	}

	function update_option( $option, $value ) {
		$options = $this->get_all_options( true );
		$options[$option] = $value;
		return $this->update_options( $options );
	}

	function reset_options() {
		delete_option( $this->option_name );
		return $this->get_all_options();
	}

	#endregion

	#region Jipangu Web

	/**
	 * Get spots from Jipangu Web
	 *
	 * @param string $access_token
	 * @return array
	 * @throws \Exception
	 */
	function get_spots( $access_token ) {
		$jipangu_server = $this->get_option( 'jipangu_server' );
		$scheme = $jipangu_server === 'localhost' ? 'http' : 'https';
		$port = $this->get_option( 'jipangu_localhost_port' );
		if ( $jipangu_server === 'localhost' && $port ) {
			$jipangu_server .= ':' . $port;
		}
		$jipangu_email = $this->get_option( 'jipangu_email' );
		$api_url = sprintf(
			'%s://%s/api/mydata?email=%s',
			$scheme,
			$jipangu_server,
			$jipangu_email
		);

		$response = wp_remote_get( $api_url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $access_token
			],
		] );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ), (int) $this->getErrorCode( $response ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( !$response_code || $response_code >= 400 ) {
			$body = $this->getBodyFromResponse( $response );
			throw new \Exception( esc_html( $body['message'] ?? 'Something went wrong.' ), (int) $this->getErrorCode( $response ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( !$data['success'] ) {
			throw new \Exception( esc_html( $data['message'] ?? 'Failed to fetch spots.' ), 200 );
		}
		if ( !isset( $data['spots'] ) ) {
			throw new \Exception( 'Spots key does not exist.', 200 );
		}
		return $data['spots'];
	}

    /**
     * Retrieve body from response
     *
     * @param array $response
     * @return array|null
     */
    protected function getBodyFromResponse( $response )
    {
        $body = wp_remote_retrieve_body( $response );
        if ( $body ) {
			return json_decode( $body, true );
        }
        return null;
    }

    /**
     * Get error code from response.
     * Return 500 if the response does not have the code.
	 *
     * @param mixed $response
     * @return int
     */
    protected function getErrorCode( $response ) {
        $code = wp_remote_retrieve_response_code( $response );
        if ( !empty( $code ) ) {
            return $code;
        }
        return 500;
    }

	#endregion
}

?>
