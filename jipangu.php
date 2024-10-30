<?php
/*
Plugin Name: Jipangu
Plugin URI: https://meowapps.com
Description: Jipangu is a community-driven map for discovering Japan. This plugin connects creators' websites to Jipangu, displaying their spots on a map.
Version: 0.0.2
Author: Jordy Meow
Author URI: https://jordymeow.com
Text Domain: jipangu
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

define( 'JPNG_VERSION', '0.0.2' );
define( 'JPNG_PREFIX', 'jpng' );
define( 'JPNG_DOMAIN', 'jipangu' );
define( 'JPNG_ENTRY', __FILE__ );
define( 'JPNG_PATH', dirname( __FILE__ ) );
define( 'JPNG_URL', plugin_dir_url( __FILE__ ) );

define( 'JPNG_WEB_IMAGE_URL', 'https://ik.jipangu.com/tr:w-160,h-160/' );
define( 'JPNG_WEB_DEV_IMAGE_URL', 'https://ik.jipangu.com/tr:w-160,h-160/' );

require_once( 'classes/init.php' );

?>
