<?php
/**
 * Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Settings {
	const OPTION_NAME = 'pv_settings';

	public function hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting(
			'pv_settings_group',
			self::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);
	}

	public function get_settings() {
		$defaults = array(
			'payment_link_url'      => '',
			'webhook_secret'        => '',
			'questionnaire_page_id' => 0,
			'button_text'           => __( 'Стартирай', 'platen-vaprosnik' ),
			'mode'                  => 'test',
			'success_message'       => __( 'Благодарим! Вашият въпросник беше изпратен успешно.', 'platen-vaprosnik' ),
			'cleanup_on_uninstall'  => 0,
		);

		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
	}

	public function sanitize_settings( $input ) {
		$sanitized = array(
			'payment_link_url'      => isset( $input['payment_link_url'] ) ? esc_url_raw( trim( $input['payment_link_url'] ) ) : '',
			'webhook_secret'        => isset( $input['webhook_secret'] ) ? sanitize_text_field( trim( $input['webhook_secret'] ) ) : '',
			'questionnaire_page_id' => isset( $input['questionnaire_page_id'] ) ? absint( $input['questionnaire_page_id'] ) : 0,
			'button_text'           => isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : __( 'Стартирай', 'platen-vaprosnik' ),
			'mode'                  => ( isset( $input['mode'] ) && 'live' === $input['mode'] ) ? 'live' : 'test',
			'success_message'       => isset( $input['success_message'] ) ? sanitize_textarea_field( $input['success_message'] ) : '',
			'cleanup_on_uninstall'  => ! empty( $input['cleanup_on_uninstall'] ) ? 1 : 0,
		);

		add_settings_error( 'pv_messages', 'pv_settings_saved', __( 'Настройките са запазени успешно', 'platen-vaprosnik' ), 'updated' );
		return $sanitized;
	}

	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
}
