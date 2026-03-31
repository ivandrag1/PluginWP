<?php
/**
 * Plugin Name: Платен въпросник
 * Description: Платен достъп до въпросник чрез Stripe Payment Link с потвърждение през webhook.
 * Version: 1.0.0
 * Author: OpenAI
 * Text Domain: platen-vaprosnik
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PV_PLUGIN_FILE', __FILE__ );
define( 'PV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PV_VERSION', '1.0.0' );

require_once PV_PLUGIN_DIR . 'includes/class-activator.php';
require_once PV_PLUGIN_DIR . 'includes/class-submissions-repository.php';
require_once PV_PLUGIN_DIR . 'includes/class-settings.php';
require_once PV_PLUGIN_DIR . 'includes/class-payment-link.php';
require_once PV_PLUGIN_DIR . 'includes/class-webhook-handler.php';
require_once PV_PLUGIN_DIR . 'includes/class-questionnaire.php';
require_once PV_PLUGIN_DIR . 'includes/class-admin.php';
require_once PV_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'PV_Activator', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'PV_Activator', 'uninstall' ) );

add_action(
	'plugins_loaded',
	static function() {
		load_plugin_textdomain( 'platen-vaprosnik', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		$plugin = new PV_Plugin();
		$plugin->run();
	}
);
