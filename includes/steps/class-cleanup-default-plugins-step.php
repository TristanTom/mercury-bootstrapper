<?php
/**
 * Delete the default plugins that ship with a fresh WordPress install:
 * Akismet and Hello Dolly.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Cleanup_Default_Plugins_Step extends Mercury_Bootstrapper_Step {

	/** Default plugin slugs to remove (path relative to wp-content/plugins). */
	private const TARGETS = array( 'akismet/akismet.php', 'hello.php' );

	public function get_id(): string {
		return 'cleanup-default-plugins';
	}

	public function get_label(): string {
		return __( 'Delete default plugins (Akismet, Hello Dolly)', 'mercury-bootstrapper' );
	}

	public function run(): array {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! WP_Filesystem() ) {
			return $this->error( __( 'Could not initialize filesystem. Host may require FTP credentials or direct write access is disabled.', 'mercury-bootstrapper' ) );
		}

		$installed = get_plugins();
		$to_delete = array();
		foreach ( self::TARGETS as $plugin_file ) {
			if ( isset( $installed[ $plugin_file ] ) ) {
				if ( is_plugin_active( $plugin_file ) ) {
					deactivate_plugins( $plugin_file, true );
				}
				$to_delete[] = $plugin_file;
			}
		}

		if ( empty( $to_delete ) ) {
			return $this->skipped( __( 'Default plugins not installed.', 'mercury-bootstrapper' ) );
		}

		$result = delete_plugins( $to_delete );
		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message() );
		}
		if ( false === $result ) {
			return $this->error( __( 'delete_plugins() returned false — filesystem access likely denied.', 'mercury-bootstrapper' ) );
		}

		return $this->ok( sprintf(
			/* translators: %s: comma-separated list of plugin files */
			__( 'Deleted: %s', 'mercury-bootstrapper' ),
			implode( ', ', $to_delete )
		) );
	}
}
