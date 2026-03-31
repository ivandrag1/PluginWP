<?php
/**
 * Admin screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Admin {
	private $settings;
	private $repository;

	public function __construct( PV_Settings $settings, PV_Submissions_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;
	}

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_question_actions' ) );
	}

	public function register_menu() {
		$capability = 'manage_options';
		$slug       = 'platen-vaprosnik';

		add_menu_page(
			esc_html__( 'Платен въпросник', 'platen-vaprosnik' ),
			esc_html__( 'Платен въпросник', 'platen-vaprosnik' ),
			$capability,
			$slug,
			array( $this, 'render_settings_page' ),
			'dashicons-feedback',
			56
		);

		add_submenu_page( $slug, esc_html__( 'Настройки', 'platen-vaprosnik' ), esc_html__( 'Настройки', 'platen-vaprosnik' ), $capability, $slug, array( $this, 'render_settings_page' ) );
		add_submenu_page( $slug, esc_html__( 'Въпроси', 'platen-vaprosnik' ), esc_html__( 'Въпроси', 'platen-vaprosnik' ), $capability, 'platen-vaprosnik-questions', array( $this, 'render_questions_page' ) );
		add_submenu_page( $slug, esc_html__( 'Плащания и отговори', 'platen-vaprosnik' ), esc_html__( 'Плащания и отговори', 'platen-vaprosnik' ), $capability, 'platen-vaprosnik-submissions', array( $this, 'render_submissions_page' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'pv-admin', PV_PLUGIN_URL . 'assets/css/admin.css', array(), PV_VERSION );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings    = $this->settings->get_settings();
		$webhook_url = rest_url( 'platen-vaprosnik/v1/stripe-webhook' );
		include PV_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	public function render_questions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$question = $edit_id ? $this->repository->get_question( $edit_id ) : null;
		$questions = $this->repository->get_questions();
		include PV_PLUGIN_DIR . 'templates/admin-questions.php';
	}

	public function render_submissions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$detail_id = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		if ( $detail_id ) {
			$detail = $this->repository->get_submission_detail( $detail_id );
			include PV_PLUGIN_DIR . 'templates/admin-submissions.php';
			return;
		}

		$items = $this->repository->get_submissions();
		include PV_PLUGIN_DIR . 'templates/admin-submissions.php';
	}

	public function handle_question_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['pv_question_action'] ) && 'save' === $_POST['pv_question_action'] ) {
			check_admin_referer( 'pv_save_question' );
			$options = isset( $_POST['options'] ) ? array_filter( array_map( 'sanitize_text_field', array_map( 'trim', explode( PHP_EOL, wp_unslash( $_POST['options'] ) ) ) ) ) : array();
			$data = array(
				'id' => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
				'question_key' => isset( $_POST['question_key'] ) ? wp_unslash( $_POST['question_key'] ) : '',
				'label_text' => isset( $_POST['label_text'] ) ? wp_unslash( $_POST['label_text'] ) : '',
				'field_type' => isset( $_POST['field_type'] ) ? wp_unslash( $_POST['field_type'] ) : 'text',
				'help_text' => isset( $_POST['help_text'] ) ? wp_unslash( $_POST['help_text'] ) : '',
				'options' => $options,
				'is_required' => ! empty( $_POST['is_required'] ),
				'display_order' => isset( $_POST['display_order'] ) ? absint( $_POST['display_order'] ) : 0,
			);
			$this->repository->save_question( $data );
			wp_safe_redirect( admin_url( 'admin.php?page=platen-vaprosnik-questions&message=saved' ) );
			exit;
		}

		if ( isset( $_POST['pv_question_action'] ) && 'reorder' === $_POST['pv_question_action'] ) {
			check_admin_referer( 'pv_reorder_questions' );
			$orders = isset( $_POST['display_order'] ) ? array_map( 'absint', wp_unslash( $_POST['display_order'] ) ) : array();
			$this->repository->update_question_order( $orders );
			wp_safe_redirect( admin_url( 'admin.php?page=platen-vaprosnik-questions&message=reordered' ) );
			exit;
		}

		if ( isset( $_GET['pv_delete_question'] ) ) {
			check_admin_referer( 'pv_delete_question_' . absint( $_GET['pv_delete_question'] ) );
			$this->repository->delete_question( absint( $_GET['pv_delete_question'] ) );
			wp_safe_redirect( admin_url( 'admin.php?page=platen-vaprosnik-questions&message=deleted' ) );
			exit;
		}
	}
}
