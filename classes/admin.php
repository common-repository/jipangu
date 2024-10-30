<?php
class Meow_JPNG_Admin {

	/** @var Meow_JPNG_Core */
	public $core;
	/** @var string */
	protected $menu_slug = 'jipangu_settings';
	/** @var string */
	protected $option_group = JPNG_DOMAIN;
	/** @var string */
	protected $option_name;
	/** @var string */
	protected $section_id = JPNG_PREFIX . '_settings_id';
	/** @var string */
	protected $options;

	public function __construct( $core ) {
		$this->core = $core;
		$this->option_name = $this->core->get_option_name();
		$this->options = $this->core->get_all_options();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( JPNG_ENTRY ), array( $this, 'plugin_action_links' ), 10, 2 );
		}
	}

	public function app_menu() {
		add_submenu_page(
			'options-general.php',
			'Jipangu',
			'Jipangu',
			'manage_options',
			$this->menu_slug, array( $this, 'admin_settings' )
		);
	}

	public function register_settings() {
		register_setting(
			$this->option_group,
			$this->option_name,
			array( $this, 'sanitize' )
		);

		add_settings_section(
            $this->section_id,
            '',
            null,
            $this->menu_slug
        );

		add_settings_field(
            JPNG_PREFIX . '_google_maps_api_key',
            'Google Maps API Key',
            array( $this, 'render_google_maps_api_key' ),
            $this->menu_slug,
			$this->section_id,
			array( 'label_for' => 'google_maps_api_key' )
        );

		add_settings_field(
            JPNG_PREFIX . '_jipangu_server',
            'Jipangu Server',
            array( $this, 'render_jipangu_server' ),
            $this->menu_slug,
			$this->section_id,
			array( 'label_for' => 'jipangu_server' )
        );

		add_settings_field(
            JPNG_PREFIX . '_jipangu_localhost_port',
            'Localhost Port',
            array( $this, 'render_jipangu_localhost_port' ),
            $this->menu_slug,
			$this->section_id,
			array( 'label_for' => 'jipangu_localhost_port' )
        );

		add_settings_field(
            JPNG_PREFIX . '_jipangu_email',
            'Jipangu Email',
            array( $this, 'render_jipangu_email' ),
            $this->menu_slug,
			$this->section_id,
			array( 'label_for' => 'jipangu_email' )
        );

		add_settings_field(
            JPNG_PREFIX . '_jipangu_token',
            'Jipangu Token',
            array( $this, 'render_jipangu_token' ),
            $this->menu_slug,
			$this->section_id,
			array( 'label_for' => 'jipangu_token' )
        );
	}

	public function admin_settings() {
		include_once 'views/settings.php';
	}

	public function plugin_action_links( array $actions, string $plugin_file ): array {
		if ( plugin_basename( JPNG_ENTRY ) !== $plugin_file ) {
			return $actions;
		}

		$base_url = admin_url( 'options-general.php' );
		$url = add_query_arg( 'page', $this->menu_slug, $base_url );
		return array_merge(
			array(
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( $url ),
					esc_html__( 'Settings', 'jipangu' )
				),
			),
			$actions,
		);
	}

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();

		foreach ( $input as $key => $value ) {
			$sanitized_value = sanitize_text_field( $value );

			// Jipnagu Server should be one of the JPNG_SERVERS
			if ( $key === 'jipangu_server' ) {
				$new_input[$key] = in_array( $sanitized_value, JPNG_SERVERS )
					? $sanitized_value
					: $this->options[$key];
				continue;
			}

			$new_input[$key] = $sanitized_value;
		}

		// `Jipnagu data` will be updated when the token is updated.
		// The token is also updated when the sync by the new token ha been
		// done, so we can always use the option value as a condition.
		$new_input['jipangu_data'] = ( $new_input['jipangu_token'] !== $this->options['jipangu_token'] )
			? null
			: $this->options['jipangu_data'];

        return $new_input;
    }

    /**
     * Print Google Maps API Key text field
	 *
	 * @return void
     */
    public function render_google_maps_api_key(): void {
		$this->render_text_field( 'google_maps_api_key' );
    }

    /**
     * Print Jipangu Server select field
	 *
	 * @return void
     */
    public function render_jipangu_server(): void {
		$key = 'jipangu_server';

		$value = isset( $this->options[$key] ) ? esc_attr( $this->options[$key] ) : '';

		$options_html = array_map( function( $option ) use ( $value ) {
			$selected = selected( $option, $value, false );
			return "<option value='$option' $selected>$option</option>";
		}, JPNG_SERVERS );

		printf(
			'<select id="%s" name="%s">%s</select>',
			esc_attr( $key ),
			esc_attr( $this->option_name . '[' . $key . ']' ),
			wp_kses( implode( '', $options_html ), array(
				'option' => array(
					'value' => true,
					'selected' => true,
				),
			) )
		);
    }

    /**
     * Print Jipnagu Server Port text field
	 *
	 * @return void
     */
    public function render_jipangu_localhost_port(): void {
		$this->render_text_field( 'jipangu_localhost_port', 'number' );
    }

    /**
     * Print Jipnagu Email text field
	 *
	 * @return void
     */
    public function render_jipangu_email(): void {
		$this->render_text_field( 'jipangu_email' );
    }

    /**
     * Print Jipangu Token text field
	 *
	 * @return void
     */
    public function render_jipangu_token(): void {
		$key = 'jipangu_token';

		echo '<div style="display:flex;">';

		$this->render_text_field( $key );

		// Sync button shows only when the token, server and email are set.
		$required_keys = [ $key, 'jipangu_server', 'jipangu_email' ];
		$show_sync_button = array_reduce( $required_keys, function( $carry, $key ) {
			return $carry && isset( $this->options[$key] ) && ! empty( $this->options[$key] );
		}, true );
		if ( $show_sync_button ) {
			printf(
				'<input type="button" id="sync-with-jipangu" class="button button-secondary" value="%s"><span class="spinner"></span>',
				esc_attr__( 'Synchronize with Jipangu', 'jipangu' )
			);
		}

		echo '</div>';
    }

    /**
     * Print text field for specific key passed in $args
	 *
	 * @param string $args
     */
    public function render_text_field( $key, $type = 'text' ) {
		$value = isset( $this->options[$key] ) ? esc_attr( $this->options[$key] ) : '';

		printf(
            '<input type="%s" id="%s" name="%s" value="%s" />',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( $this->option_name . '[' . $key . ']' ),
            esc_attr( $value )
        );
    }
}

?>
