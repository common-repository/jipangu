<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

if ( class_exists( 'MeowPro_JPNG_Core' ) && class_exists( 'Meow_JPNG_Core' ) ) {
	function jpng_thanks_admin_notices() {
		echo '<div class="error"><p>' . esc_html__( 'Thanks for installing the Pro version of Jipangu :) However, the free version is still enabled. Please disable or uninstall it.', 'jipangu' ) . '</p></div>';
	}
	add_action( 'admin_notices', 'jpng_thanks_admin_notices' );
	return;
}

spl_autoload_register(function ( $class ) {
  $necessary = true;
  $file = null;
  if ( strpos( $class, 'Meow_JPNG' ) !== false ) {
    $file = JPNG_PATH . '/classes/' . str_replace( 'meow_jpng_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowPro_JPNG' ) !== false ) {
    $necessary = false;
    $file = JPNG_PATH . '/premium/' . str_replace( 'meowpro_jpng_', '', strtolower( $class ) ) . '.php';
  }
  if ( $file ) {
    if ( !$necessary && !file_exists( $file ) ) {
      return;
    }
    require( $file );
  }
});

// In admin or Rest API request (REQUEST URI begins with '/wp-json/')
if ( is_admin() || Meow_JPNG_Helpers::is_rest() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	global $jpng_core;
	$jpng_core = new Meow_JPNG_Core();
  new Meow_JPNG_Shortcode( $jpng_core );
} else {
  new Meow_JPNG_Shortcode( new Meow_JPNG_Core() );
}
