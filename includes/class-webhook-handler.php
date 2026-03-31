<?php
/**
 * Stripe webhook endpoint and payment status polling.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Webhook_Handler {
	private $settings;
	private $repository;

	public function __construct( PV_Settings $settings, PV_Submissions_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;
	}

	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'platen-vaprosnik/v1',
			'/stripe-webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'platen-vaprosnik/v1',
			'/payment-status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'payment_status' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle_webhook( WP_REST_Request $request ) {
		$payload = $request->get_body();
		$secret  = $this->settings->get_setting( 'webhook_secret' );
		$header  = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';

		if ( empty( $secret ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Липсва webhook secret.', 'platen-vaprosnik' ) ), 400 );
		}

		if ( ! $this->is_valid_signature( $payload, $header, $secret ) ) {
			error_log( 'Платен въпросник: Невалиден webhook подпис.' );
			return new WP_REST_Response( array( 'message' => __( 'Невалиден webhook подпис', 'platen-vaprosnik' ) ), 400 );
		}

		$event = json_decode( $payload, true );
		if ( empty( $event['type'] ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Невалидно Stripe събитие.', 'platen-vaprosnik' ) ), 400 );
		}

		if ( 'checkout.session.completed' !== $event['type'] ) {
			return new WP_REST_Response( array( 'received' => true ), 200 );
		}

		$session = isset( $event['data']['object'] ) ? $event['data']['object'] : array();
		$client_reference_id = isset( $session['client_reference_id'] ) ? sanitize_text_field( $session['client_reference_id'] ) : '';
		if ( empty( $client_reference_id ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Липсва client_reference_id.', 'platen-vaprosnik' ) ), 400 );
		}

		$result = $this->repository->mark_payment_paid(
			$client_reference_id,
			array(
				'stripe_session_id' => isset( $session['id'] ) ? $session['id'] : '',
				'stripe_event_id'   => isset( $event['id'] ) ? $event['id'] : '',
				'amount_total'      => isset( $session['amount_total'] ) ? (int) $session['amount_total'] : 0,
				'currency'          => isset( $session['currency'] ) ? $session['currency'] : '',
				'customer_email'    => isset( $session['customer_details']['email'] ) ? $session['customer_details']['email'] : '',
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->repository->record_webhook_failure(
				$client_reference_id,
				$result->get_error_message(),
				array( 'event_id' => isset( $event['id'] ) ? $event['id'] : '' )
			);
			return new WP_REST_Response( array( 'message' => $result->get_error_message() ), 400 );
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}

	public function payment_status( WP_REST_Request $request ) {
		$reference = sanitize_text_field( (string) $request->get_param( 'reference' ) );
		$token     = sanitize_text_field( (string) $request->get_param( 'token' ) );
		$record    = $this->repository->get_payment_by_access( $reference, $token );

		if ( empty( $record ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'forbidden',
					'message' => __( 'Нямате достъп до този въпросник.', 'platen-vaprosnik' ),
				),
				403
			);
		}

		return new WP_REST_Response(
			array(
				'status'       => $record['payment_status'],
				'is_submitted' => ! empty( $record['submitted_at'] ),
			),
			200
		);
	}

	private function is_valid_signature( $payload, $header, $secret ) {
		if ( empty( $header ) ) {
			return false;
		}

		$parts = array();
		foreach ( explode( ',', $header ) as $item ) {
			$pair = explode( '=', trim( $item ), 2 );
			if ( 2 === count( $pair ) ) {
				$parts[ $pair[0] ][] = $pair[1];
			}
		}

		if ( empty( $parts['t'][0] ) || empty( $parts['v1'] ) ) {
			return false;
		}

		$timestamp = (int) $parts['t'][0];
		if ( abs( time() - $timestamp ) > 300 ) {
			return false;
		}

		$signed_payload = $timestamp . '.' . $payload;
		$expected       = hash_hmac( 'sha256', $signed_payload, $secret );

		foreach ( $parts['v1'] as $signature ) {
			if ( hash_equals( $expected, $signature ) ) {
				return true;
			}
		}

		return false;
	}
}
