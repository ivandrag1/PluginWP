<?php
/**
 * Frontend questionnaire rendering and submission.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Questionnaire {
	private $settings;
	private $repository;
	private $payment_link;

	public function __construct( PV_Settings $settings, PV_Submissions_Repository $repository, PV_Payment_Link $payment_link ) {
		$this->settings     = $settings;
		$this->repository   = $repository;
		$this->payment_link = $payment_link;
	}

	public function hooks() {
		add_shortcode( 'platen_vaprosnik', array( $this, 'render_shortcode' ) );
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'admin_post_nopriv_pv_submit_questionnaire', array( $this, 'handle_submission' ) );
		add_action( 'admin_post_pv_submit_questionnaire', array( $this, 'handle_submission' ) );
	}

	public function register_assets() {
		wp_register_style( 'pv-frontend', PV_PLUGIN_URL . 'assets/css/frontend.css', array(), PV_VERSION );
		wp_register_script( 'pv-frontend', PV_PLUGIN_URL . 'assets/js/frontend.js', array(), PV_VERSION, true );
		wp_register_script( 'pv-block', PV_PLUGIN_URL . 'assets/js/block.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor' ), PV_VERSION, true );
	}

	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'platen-vaprosnik/button',
			array(
				'editor_script'   => 'pv-block',
				'render_callback' => array( $this, 'render_shortcode' ),
			)
		);
	}

	public function render_shortcode() {
		wp_enqueue_style( 'pv-frontend' );
		wp_enqueue_script( 'pv-frontend' );

		$access  = $this->payment_link->get_access_from_cookie();
		$record  = null;
		if ( ! empty( $access ) ) {
			$record = $this->repository->get_payment_by_access( $access['reference'], $access['token'] );
		}

		$data = array(
			'button_text'       => $this->settings->get_setting( 'button_text', __( 'Стартирай', 'platen-vaprosnik' ) ),
			'start_action'      => esc_url( admin_url( 'admin-post.php' ) ),
			'nonce'             => wp_create_nonce( 'pv_start_payment' ),
			'questions'         => $this->normalize_questions( $this->repository->get_questions() ),
			'record'            => $record,
			'access'            => $access,
			'questionnaire_page_id' => (int) $this->settings->get_setting( 'questionnaire_page_id', 0 ),
			'poll_url'          => esc_url( rest_url( 'platen-vaprosnik/v1/payment-status' ) ),
			'submit_action'     => esc_url( admin_url( 'admin-post.php' ) ),
			'submit_nonce'      => wp_create_nonce( 'pv_submit_questionnaire' ),
			'messages'          => array(
				'waiting' => __( 'Плащането се потвърждава. Моля, изчакайте...', 'platen-vaprosnik' ),
				'timeout' => __( 'Все още не можем да потвърдим плащането. Моля, опитайте отново след малко.', 'platen-vaprosnik' ),
				'forbidden' => __( 'Нямате достъп до този въпросник.', 'platen-vaprosnik' ),
				'success' => $this->settings->get_setting( 'success_message' ),
			),
			'request_error'     => $this->get_error_message_from_request(),
			'form_errors'       => isset( $_GET['pv_errors'] ) ? $this->decode_json_query( wp_unslash( $_GET['pv_errors'] ) ) : array(),
			'form_old'          => isset( $_GET['pv_old'] ) ? $this->decode_json_query( wp_unslash( $_GET['pv_old'] ) ) : array(),
			'submitted'         => ! empty( $_GET['pv_submitted'] ),
		);

		ob_start();
		include PV_PLUGIN_DIR . 'templates/frontend-questionnaire.php';
		return ob_get_clean();
	}

	public function handle_submission() {
		check_admin_referer( 'pv_submit_questionnaire', 'pv_nonce' );

		$reference = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
		$token     = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$record    = $this->repository->get_payment_by_access( $reference, $token );
		$page_id   = (int) $this->settings->get_setting( 'questionnaire_page_id', 0 );
		$redirect  = $page_id ? get_permalink( $page_id ) : home_url( '/' );

		if ( empty( $reference ) || empty( $token ) ) {
			$this->redirect_with_error( 'invalid_request', $redirect );
		}

		if ( empty( $record ) ) {
			$this->redirect_with_error( 'forbidden', $redirect );
		}

		if ( 'paid' !== $record['payment_status'] ) {
			$this->redirect_with_error( 'payment_pending', $redirect );
		}

		$questions = $this->normalize_questions( $this->repository->get_questions() );
		$answers   = array();
		$errors    = array();

		foreach ( $questions as $question ) {
			$key = $question['question_key'];
			$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$sanitized = $this->sanitize_answer( $question, $value );
			if ( $question['is_required'] && $this->is_empty_answer( $sanitized ) ) {
				$errors[ $key ] = sprintf( __( 'Полето „%s“ е задължително.', 'platen-vaprosnik' ), $question['label_text'] );
			}
			$answers[ $key ] = $sanitized;
		}

		$name  = isset( $_POST['pv_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pv_name'] ) ) : '';
		$email = isset( $_POST['pv_email'] ) ? sanitize_email( wp_unslash( $_POST['pv_email'] ) ) : '';
		$phone = isset( $_POST['pv_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['pv_phone'] ) ) : '';

		if ( '' === $name ) {
			$errors['pv_name'] = __( 'Полето „Име и фамилия“ е задължително.', 'platen-vaprosnik' );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			$errors['pv_email'] = __( 'Моля, въведете валиден имейл адрес.', 'platen-vaprosnik' );
		}
		if ( '' === $phone ) {
			$errors['pv_phone'] = __( 'Полето „Телефон“ е задължително.', 'platen-vaprosnik' );
		}

		if ( ! empty( $errors ) ) {
			$old_values = array_merge( $answers, compact( 'name', 'email', 'phone' ) );
			wp_safe_redirect(
				add_query_arg(
						array(
							'pv_error'  => 'validation_failed',
							'pv_errors' => wp_json_encode( $errors ),
							'pv_old'    => wp_json_encode( $old_values ),
						),
						$redirect
					)
				);
			exit;
		}

		$result = $this->repository->save_submission( (int) $record['id'], $name, $email, $phone, $answers );
		if ( is_wp_error( $result ) ) {
			$this->redirect_with_error( $this->map_wp_error_to_frontend_code( $result ), $redirect );
		}

		$this->payment_link->clear_access_cookie();
		wp_safe_redirect( add_query_arg( 'pv_submitted', 1, $redirect ) );
		exit;
	}

	private function normalize_questions( $questions ) {
		foreach ( $questions as &$question ) {
			$question['options'] = array_filter( (array) json_decode( $question['options_json'], true ) );
			$question['is_required'] = ! empty( $question['is_required'] );
		}

		return $questions;
	}

	private function sanitize_answer( $question, $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		switch ( $question['field_type'] ) {
			case 'email':
				return sanitize_email( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	private function is_empty_answer( $value ) {
		if ( is_array( $value ) ) {
			return empty( array_filter( $value ) );
		}

		return '' === (string) $value;
	}

	private function decode_json_query( $value ) {
		$decoded = json_decode( rawurldecode( $value ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function get_error_message_from_request() {
		if ( empty( $_GET['pv_error'] ) ) {
			return '';
		}

		$error_code = sanitize_key( wp_unslash( $_GET['pv_error'] ) );
		$messages   = $this->get_error_messages();

		if ( isset( $messages[ $error_code ] ) ) {
			return $messages[ $error_code ];
		}

		return $messages['generic_error'];
	}

	private function get_error_messages() {
		return array(
			'forbidden'         => __( 'Нямате достъп до този въпросник.', 'platen-vaprosnik' ),
			'already_submitted' => __( 'Този въпросник вече е изпратен.', 'platen-vaprosnik' ),
			'invalid_request'   => __( 'Невалидна заявка.', 'platen-vaprosnik' ),
			'payment_pending'   => __( 'Плащането все още не е потвърдено.', 'platen-vaprosnik' ),
			'validation_failed' => __( 'Моля, поправете отбелязаните грешки.', 'platen-vaprosnik' ),
			'generic_error'     => __( 'Възникна грешка. Моля, опитайте отново.', 'platen-vaprosnik' ),
		);
	}

	private function map_wp_error_to_frontend_code( WP_Error $error ) {
		$map = array(
			'pv_already_submitted' => 'already_submitted',
			'pv_missing_payment'   => 'invalid_request',
			'pv_submission_failed' => 'generic_error',
		);

		$error_code = $error->get_error_code();
		return isset( $map[ $error_code ] ) ? $map[ $error_code ] : 'generic_error';
	}

	private function redirect_with_error( $error_code, $redirect ) {
		$allowed_codes = array_keys( $this->get_error_messages() );
		if ( ! in_array( $error_code, $allowed_codes, true ) ) {
			$error_code = 'generic_error';
		}

		wp_safe_redirect( add_query_arg( 'pv_error', $error_code, $redirect ) );
		exit;
	}
}
