<?php
/**
 * Stripe Payment Link flow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Payment_Link {
	private $settings;
	private $repository;

	public function __construct( PV_Settings $settings, PV_Submissions_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;
	}

	public function hooks() {
		add_action( 'admin_post_nopriv_pv_start_payment', array( $this, 'handle_start_payment' ) );
		add_action( 'admin_post_pv_start_payment', array( $this, 'handle_start_payment' ) );
	}

	public function handle_start_payment() {
		check_admin_referer( 'pv_start_payment', 'pv_nonce' );

		$link = $this->settings->get_setting( 'payment_link_url' );
		if ( empty( $link ) ) {
			wp_die( esc_html__( 'Липсва Stripe Payment Link.', 'platen-vaprosnik' ) );
		}

		$payment = $this->repository->create_pending_payment();
		if ( is_wp_error( $payment ) ) {
			wp_die( esc_html( $payment->get_error_message() ) );
		}

		$this->set_access_cookie( $payment['client_reference_id'], $payment['raw_token'] );

		$redirect_url = add_query_arg(
			array(
				'client_reference_id' => $payment['client_reference_id'],
			),
			$link
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function set_access_cookie( $reference, $token ) {
		$cookie = wp_json_encode(
			array(
				'reference' => $reference,
				'token'     => $token,
			)
		);

		setcookie( 'pv_access', base64_encode( $cookie ), time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE['pv_access'] = base64_encode( $cookie );
	}

	public function get_access_from_cookie() {
		if ( empty( $_COOKIE['pv_access'] ) ) {
			return null;
		}

		$decoded = base64_decode( sanitize_text_field( wp_unslash( $_COOKIE['pv_access'] ) ), true );
		if ( false === $decoded ) {
			return null;
		}

		$data = json_decode( $decoded, true );
		if ( empty( $data['reference'] ) || empty( $data['token'] ) ) {
			return null;
		}

		return array(
			'reference' => sanitize_text_field( $data['reference'] ),
			'token'     => sanitize_text_field( $data['token'] ),
		);
	}

	public function clear_access_cookie() {
		setcookie( 'pv_access', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		unset( $_COOKIE['pv_access'] );
	}
}
