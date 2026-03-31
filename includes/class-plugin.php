<?php
/**
 * Plugin bootstrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PV_Plugin {
	private $settings;
	private $repository;
	private $payment_link;
	private $webhook_handler;
	private $questionnaire;
	private $admin;

	public function __construct() {
		global $wpdb;
		$this->settings        = new PV_Settings();
		$this->repository      = new PV_Submissions_Repository( $wpdb );
		$this->payment_link    = new PV_Payment_Link( $this->settings, $this->repository );
		$this->webhook_handler = new PV_Webhook_Handler( $this->settings, $this->repository );
		$this->questionnaire   = new PV_Questionnaire( $this->settings, $this->repository, $this->payment_link );
		$this->admin           = new PV_Admin( $this->settings, $this->repository );
	}

	public function run() {
		$this->settings->hooks();
		$this->payment_link->hooks();
		$this->webhook_handler->hooks();
		$this->questionnaire->hooks();
		$this->admin->hooks();
	}
}
