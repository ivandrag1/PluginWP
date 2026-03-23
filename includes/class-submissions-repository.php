<?php
/**
 * Storage layer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Submissions_Repository {
	/** @var wpdb */
	private $wpdb;
	private $payments_table;
	private $questions_table;
	private $submissions_table;

	public function __construct( $wpdb ) {
		$this->wpdb              = $wpdb;
		$this->payments_table    = $wpdb->prefix . 'pv_payments';
		$this->questions_table   = $wpdb->prefix . 'pv_questions';
		$this->submissions_table = $wpdb->prefix . 'pv_submissions';
	}

	public function create_pending_payment() {
		$client_reference_id = 'pv_' . wp_generate_password( 20, false, false );
		$access_token        = wp_generate_password( 48, false, false );
		$internal_reference  = 'INT-' . strtoupper( wp_generate_password( 10, false, false ) );
		$now                 = current_time( 'mysql' );
		$history             = array(
			array(
				'status'    => 'pending',
				'message'   => __( 'Създадено изчакващо плащане.', 'platen-vaprosnik' ),
				'timestamp' => current_time( 'mysql', true ),
			),
		);

		$this->wpdb->insert(
			$this->payments_table,
			array(
				'internal_reference_id' => $internal_reference,
				'client_reference_id'   => $client_reference_id,
				'access_token'          => wp_hash_password( $access_token ),
				'payment_status'        => 'pending',
				'status_history'        => wp_json_encode( $history ),
				'created_at'            => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$payment_id = (int) $this->wpdb->insert_id;
		if ( ! $payment_id ) {
			return new WP_Error( 'pv_create_payment_failed', __( 'Неуспешно създаване на плащане.', 'platen-vaprosnik' ) );
		}

		$record                  = $this->get_payment_by_id( $payment_id );
		$record['raw_token']     = $access_token;
		$record['payment_id']    = $payment_id;
		return $record;
	}

	public function get_payment_by_id( $payment_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->payments_table} WHERE id = %d", $payment_id ),
			ARRAY_A
		);
	}

	public function get_payment_by_reference( $client_reference_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->payments_table} WHERE client_reference_id = %s", $client_reference_id ),
			ARRAY_A
		);
	}

	public function get_payment_by_access( $client_reference_id, $token ) {
		$record = $this->get_payment_by_reference( $client_reference_id );
		if ( empty( $record ) || empty( $record['access_token'] ) ) {
			return null;
		}

		if ( ! wp_check_password( $token, $record['access_token'] ) ) {
			return null;
		}

		return $record;
	}

	public function mark_payment_paid( $client_reference_id, $data ) {
		$record = $this->get_payment_by_reference( $client_reference_id );
		if ( empty( $record ) ) {
			return new WP_Error( 'pv_payment_not_found', __( 'Плащането не е намерено.', 'platen-vaprosnik' ) );
		}

		$history = $this->append_history(
			$record,
			'paid',
			__( 'Плащането е потвърдено от Stripe webhook.', 'platen-vaprosnik' ),
			$data
		);

		$updated = $this->wpdb->update(
			$this->payments_table,
			array(
				'payment_status' => 'paid',
				'stripe_session_id' => isset( $data['stripe_session_id'] ) ? sanitize_text_field( $data['stripe_session_id'] ) : '',
				'stripe_event_id' => isset( $data['stripe_event_id'] ) ? sanitize_text_field( $data['stripe_event_id'] ) : '',
				'amount_total' => isset( $data['amount_total'] ) ? (int) $data['amount_total'] : null,
				'currency' => isset( $data['currency'] ) ? sanitize_text_field( $data['currency'] ) : '',
				'customer_email' => isset( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '',
				'paid_at' => current_time( 'mysql' ),
				'status_history' => wp_json_encode( $history ),
			),
			array( 'id' => (int) $record['id'] ),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'pv_payment_update_failed', __( 'Неуспешно обновяване на плащане.', 'platen-vaprosnik' ) );
		}

		return $this->get_payment_by_id( (int) $record['id'] );
	}

	public function record_webhook_failure( $client_reference_id, $message, $context = array() ) {
		$record = $this->get_payment_by_reference( $client_reference_id );
		if ( empty( $record ) ) {
			error_log( 'Платен въпросник: ' . $message . ' ' . wp_json_encode( $context ) );
			return;
		}

		$history = $this->append_history( $record, 'webhook_error', $message, $context );
		$this->wpdb->update(
			$this->payments_table,
			array( 'status_history' => wp_json_encode( $history ) ),
			array( 'id' => (int) $record['id'] ),
			array( '%s' ),
			array( '%d' )
		);

		error_log( 'Платен въпросник: ' . $message . ' ' . wp_json_encode( $context ) );
	}

	public function save_submission( $payment_id, $name, $email, $phone, $answers ) {
		$record = $this->get_payment_by_id( $payment_id );
		if ( empty( $record ) ) {
			return new WP_Error( 'pv_missing_payment', __( 'Свързаното плащане липсва.', 'platen-vaprosnik' ) );
		}

		if ( ! empty( $record['submitted_at'] ) ) {
			return new WP_Error( 'pv_already_submitted', __( 'Този въпросник вече е изпратен.', 'platen-vaprosnik' ) );
		}

		$now     = current_time( 'mysql' );
		$history = $this->append_history( $record, 'submitted', __( 'Въпросникът е изпратен успешно.', 'platen-vaprosnik' ) );

		$this->wpdb->insert(
			$this->submissions_table,
			array(
				'payment_id'           => (int) $payment_id,
				'client_reference_id'  => $record['client_reference_id'],
				'access_token'         => $record['access_token'],
				'name'                 => $name,
				'email'                => $email,
				'phone'                => $phone,
				'answers_json'         => wp_json_encode( $answers ),
				'created_at'           => $record['created_at'],
				'submitted_at'         => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $this->wpdb->insert_id ) {
			return new WP_Error( 'pv_submission_failed', __( 'Неуспешно записване на отговорите.', 'platen-vaprosnik' ) );
		}

		$this->wpdb->update(
			$this->payments_table,
			array(
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'questionnaire_answers' => wp_json_encode( $answers ),
				'submitted_at' => $now,
				'status_history' => wp_json_encode( $history ),
			),
			array( 'id' => (int) $payment_id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return $this->get_payment_by_id( $payment_id );
	}

	public function get_questions() {
		$results = $this->wpdb->get_results( "SELECT * FROM {$this->questions_table} ORDER BY display_order ASC, id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $results ) ? $results : array();
	}

	public function get_question( $question_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->questions_table} WHERE id = %d", $question_id ),
			ARRAY_A
		);
	}

	public function save_question( $data ) {
		$payload = array(
			'question_key'  => sanitize_key( $data['question_key'] ),
			'label_text'    => sanitize_text_field( $data['label_text'] ),
			'field_type'    => sanitize_text_field( $data['field_type'] ),
			'help_text'     => sanitize_textarea_field( $data['help_text'] ),
			'options_json'  => wp_json_encode( $data['options'] ),
			'is_required'   => ! empty( $data['is_required'] ) ? 1 : 0,
			'display_order' => isset( $data['display_order'] ) ? (int) $data['display_order'] : 0,
			'updated_at'    => current_time( 'mysql' ),
		);

		if ( ! empty( $data['id'] ) ) {
			return $this->wpdb->update(
				$this->questions_table,
				$payload,
				array( 'id' => (int) $data['id'] ),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);
		}

		$payload['created_at'] = current_time( 'mysql' );
		return $this->wpdb->insert(
			$this->questions_table,
			$payload,
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	public function delete_question( $question_id ) {
		return $this->wpdb->delete( $this->questions_table, array( 'id' => (int) $question_id ), array( '%d' ) );
	}

	public function update_question_order( $orders ) {
		foreach ( $orders as $id => $display_order ) {
			$this->wpdb->update(
				$this->questions_table,
				array( 'display_order' => (int) $display_order, 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => (int) $id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}
	}

	public function get_submissions( $limit = 100 ) {
		$query = $this->wpdb->prepare( "SELECT * FROM {$this->payments_table} ORDER BY created_at DESC LIMIT %d", $limit );
		$items = $this->wpdb->get_results( $query, ARRAY_A );
		return is_array( $items ) ? $items : array();
	}

	public function get_submission_detail( $payment_id ) {
		$payment = $this->get_payment_by_id( $payment_id );
		if ( empty( $payment ) ) {
			return null;
		}

		$submission = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->submissions_table} WHERE payment_id = %d", $payment_id ),
			ARRAY_A
		);

		return array(
			'payment'    => $payment,
			'submission' => $submission,
		);
	}

	private function append_history( $record, $status, $message, $context = array() ) {
		$history = array();
		if ( ! empty( $record['status_history'] ) ) {
			$decoded = json_decode( $record['status_history'], true );
			if ( is_array( $decoded ) ) {
				$history = $decoded;
			}
		}

		$history[] = array(
			'status'    => sanitize_text_field( $status ),
			'message'   => sanitize_text_field( $message ),
			'context'   => $context,
			'timestamp' => current_time( 'mysql', true ),
		);

		return $history;
	}
}
