<?php
/**
 * Runs when the plugin is deleted via WP admin.
 * Removes plugin-specific options and uploaded premium zip files.
 * Leaves the mu-plugin (mercury-disable-xmlrpc.php) in place by design.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = array(
	'mercury_bootstrapper_premium_zip_elementor-pro',
	'mercury_bootstrapper_premium_zip_wp-rocket',
);
foreach ( $options as $option_name ) {
	delete_option( $option_name );
}

$uploads = wp_upload_dir();
if ( empty( $uploads['error'] ) ) {
	$dir = trailingslashit( $uploads['basedir'] ) . 'mercury-bootstrapper';
	if ( is_dir( $dir ) ) {
		$entries = @scandir( $dir );
		if ( is_array( $entries ) ) {
			foreach ( $entries as $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					continue;
				}
				$path = $dir . '/' . $entry;
				if ( is_file( $path ) ) {
					@unlink( $path );
				}
			}
		}
		@rmdir( $dir );
	}
}
