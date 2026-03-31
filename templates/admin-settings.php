<?php
/**
 * Settings template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Настройки', 'platen-vaprosnik' ); ?></h1>
	<?php settings_errors( 'pv_messages' ); ?>

	<div class="notice notice-info"><p><?php echo esc_html__( 'Stripe webhook URL:', 'platen-vaprosnik' ) . ' ' . esc_html( $webhook_url ); ?></p></div>
	<?php if ( ! empty( $settings['webhook_secret'] ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html__( 'Webhook-ът е конфигуриран', 'platen-vaprosnik' ); ?></p></div>
	<?php endif; ?>
	<?php if ( empty( $settings['payment_link_url'] ) ) : ?>
		<div class="notice notice-warning"><p><?php echo esc_html__( 'Липсва Stripe Payment Link', 'platen-vaprosnik' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="options.php" class="pv-admin-form">
		<?php settings_fields( 'pv_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="pv_payment_link_url"><?php echo esc_html__( 'URL на Stripe Payment Link', 'platen-vaprosnik' ); ?></label></th>
				<td><input type="url" class="regular-text" id="pv_payment_link_url" name="pv_settings[payment_link_url]" value="<?php echo esc_attr( $settings['payment_link_url'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="pv_webhook_secret"><?php echo esc_html__( 'Stripe webhook secret', 'platen-vaprosnik' ); ?></label></th>
				<td><input type="text" class="regular-text" id="pv_webhook_secret" name="pv_settings[webhook_secret]" value="<?php echo esc_attr( $settings['webhook_secret'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="pv_questionnaire_page_id"><?php echo esc_html__( 'Страница за въпросник', 'platen-vaprosnik' ); ?></label></th>
				<td><?php wp_dropdown_pages( array( 'name' => 'pv_settings[questionnaire_page_id]', 'selected' => $settings['questionnaire_page_id'], 'show_option_none' => __( 'Изберете страница', 'platen-vaprosnik' ) ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="pv_button_text"><?php echo esc_html__( 'Текст на бутона', 'platen-vaprosnik' ); ?></label></th>
				<td><input type="text" class="regular-text" id="pv_button_text" name="pv_settings[button_text]" value="<?php echo esc_attr( $settings['button_text'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Режим', 'platen-vaprosnik' ); ?></th>
				<td>
					<label><input type="radio" name="pv_settings[mode]" value="test" <?php checked( $settings['mode'], 'test' ); ?> /> <?php echo esc_html__( 'Тестов', 'platen-vaprosnik' ); ?></label><br />
					<label><input type="radio" name="pv_settings[mode]" value="live" <?php checked( $settings['mode'], 'live' ); ?> /> <?php echo esc_html__( 'Реален', 'platen-vaprosnik' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pv_success_message"><?php echo esc_html__( 'Съобщение след успешно изпращане', 'platen-vaprosnik' ); ?></label></th>
				<td><textarea id="pv_success_message" class="large-text" rows="4" name="pv_settings[success_message]"><?php echo esc_textarea( $settings['success_message'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="pv_cleanup_on_uninstall"><?php echo esc_html__( 'Изтриване на данните при деинсталиране', 'platen-vaprosnik' ); ?></label></th>
				<td><label><input type="checkbox" id="pv_cleanup_on_uninstall" name="pv_settings[cleanup_on_uninstall]" value="1" <?php checked( ! empty( $settings['cleanup_on_uninstall'] ) ); ?> /> <?php echo esc_html__( 'Да, премахни всички таблици и настройки.', 'platen-vaprosnik' ); ?></label></td>
			</tr>
		</table>
		<?php submit_button( __( 'Запази настройките', 'platen-vaprosnik' ) ); ?>
	</form>
</div>
