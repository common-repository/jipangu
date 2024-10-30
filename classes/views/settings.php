<?php
/**
 * Settings view
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

// Load the jQuery UI dialog dependencies
wp_enqueue_script( 'jquery-ui-dialog' );
wp_enqueue_style( 'wp-jquery-ui-dialog' );

include_once 'settings_script.php';
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Jipangu Settings', 'jipangu' ); ?></h1>
    <form method="post" action="options.php">
        <?php
            settings_fields( $this->option_group );
            do_settings_sections( $this->menu_slug );
            submit_button();
        ?>
    </form>
</div>
<?php wp_nonce_field( 'wp_rest', '_wpnonce_rest', false ); ?>

<?php
include_once 'action_result_dialog.php';
?>

