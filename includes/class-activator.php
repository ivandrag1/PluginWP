<?php
/**
 * Activation and uninstall logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Activator {
	/**
	 * Create required tables.
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$payments_table   = $wpdb->prefix . 'pv_payments';
		$questions_table  = $wpdb->prefix . 'pv_questions';
		$submissions_table = $wpdb->prefix . 'pv_submissions';

		$sql = array();
		$sql[] = "CREATE TABLE {$payments_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			internal_reference_id VARCHAR(64) NOT NULL,
			client_reference_id VARCHAR(64) NOT NULL,
			access_token VARCHAR(128) NOT NULL,
			payment_status VARCHAR(32) NOT NULL DEFAULT 'pending',
			stripe_session_id VARCHAR(191) DEFAULT NULL,
			stripe_event_id VARCHAR(191) DEFAULT NULL,
			amount_total BIGINT DEFAULT NULL,
			currency VARCHAR(16) DEFAULT NULL,
			customer_email VARCHAR(191) DEFAULT NULL,
			name VARCHAR(191) DEFAULT NULL,
			email VARCHAR(191) DEFAULT NULL,
			phone VARCHAR(100) DEFAULT NULL,
			questionnaire_answers LONGTEXT DEFAULT NULL,
			status_history LONGTEXT DEFAULT NULL,
			created_at DATETIME NOT NULL,
			paid_at DATETIME DEFAULT NULL,
			submitted_at DATETIME DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY client_reference_id (client_reference_id),
			UNIQUE KEY access_token (access_token),
			KEY payment_status (payment_status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$questions_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			question_key VARCHAR(64) NOT NULL,
			label_text TEXT NOT NULL,
			field_type VARCHAR(20) NOT NULL,
			help_text TEXT DEFAULT NULL,
			options_json LONGTEXT DEFAULT NULL,
			is_required TINYINT(1) NOT NULL DEFAULT 0,
			display_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$submissions_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			payment_id BIGINT UNSIGNED NOT NULL,
			client_reference_id VARCHAR(64) NOT NULL,
			access_token VARCHAR(128) NOT NULL,
			name VARCHAR(191) NOT NULL,
			email VARCHAR(191) NOT NULL,
			phone VARCHAR(100) NOT NULL,
			answers_json LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			submitted_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY payment_id (payment_id),
			KEY client_reference_id (client_reference_id)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		$defaults = array(
			'payment_link_url'       => '',
			'webhook_secret'         => '',
			'questionnaire_page_id'  => 0,
			'button_text'            => __( 'Стартирай', 'platen-vaprosnik' ),
			'mode'                   => 'test',
			'success_message'        => __( 'Благодарим! Вашият въпросник беше изпратен успешно.', 'platen-vaprosnik' ),
			'cleanup_on_uninstall'   => 0,
		);

		if ( ! get_option( 'pv_settings' ) ) {
			add_option( 'pv_settings', $defaults );
		} else {
			update_option( 'pv_settings', wp_parse_args( get_option( 'pv_settings', array() ), $defaults ) );
		}
	}

	/**
	 * Cleanup plugin data if enabled.
	 */
	public static function uninstall() {
		$settings = get_option( 'pv_settings', array() );
		if ( empty( $settings['cleanup_on_uninstall'] ) ) {
			return;
		}

		global $wpdb;
		$tables = array(
			$wpdb->prefix . 'pv_payments',
			$wpdb->prefix . 'pv_questions',
			$wpdb->prefix . 'pv_submissions',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		delete_option( 'pv_settings' );
	}
}
