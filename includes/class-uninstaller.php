<?php
/**
 * AJAX handler that deactivates and deletes the plugin on request.
 * Actual database cleanup happens in uninstall.php (triggered by delete_plugins()).
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Uninstaller {

	const AJAX_ACTION  = 'mercury_bootstrapper_uninstall';
	const NONCE_ACTION = 'mercury_bootstrapper_uninstall';

	public function register_hooks(): void {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_uninstall' ) );
	}

	public function handle_uninstall(): void {
		if ( ! current_user_can( 'delete_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'mercury-bootstrapper' ) ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		if ( ! WP_Filesystem() ) {
			wp_send_json_error( array( 'message' => __( 'Could not initialize filesystem.', 'mercury-bootstrapper' ) ), 500 );
		}

		$plugin_file = plugin_basename( MERCURY_BOOTSTRAPPER_FILE );

		deactivate_plugins( $plugin_file, true );

		$result = delete_plugins( array( $plugin_file ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Plugin delete returned false.', 'mercury-bootstrapper' ) ), 500 );
		}

		wp_send_json_success( array(
			'redirect' => admin_url( 'plugins.php?deleted=true' ),
		) );
	}
}
