<?php
/**
 * Frontend start button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( $data['start_action'] ); ?>" class="pv-start-form">
	<input type="hidden" name="action" value="pv_start_payment" />
	<input type="hidden" name="pv_nonce" value="<?php echo esc_attr( $data['nonce'] ); ?>" />
	<button type="submit" class="pv-button"><?php echo esc_html( $data['button_text'] ); ?></button>
</form>
