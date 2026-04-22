<?php
/**
 * Delete WordPress's bundled default themes (Twenty*), keeping only the
 * currently active theme (expected to be Hello Elementor after the theme
 * install step has run).
 *
 * Registered AFTER theme activation in the plugin so the currently-active
 * theme is never a Twenty* theme when this runs.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Cleanup_Default_Themes_Step extends Mercury_Bootstrapper_Step {

	public function get_id(): string {
		return 'cleanup-default-themes';
	}

	public function get_label(): string {
		return __( 'Delete default themes (Twenty*)', 'mercury-bootstrapper' );
	}

	public function run(): array {
		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! WP_Filesystem() ) {
			return $this->error( __( 'Could not initialize filesystem.', 'mercury-bootstrapper' ) );
		}

		$active_stylesheet = get_stylesheet();
		$active_template   = get_template();
		$deleted           = array();

		foreach ( wp_get_themes() as $slug => $theme ) {
			if ( 0 !== strpos( $slug, 'twenty' ) ) {
				continue;
			}
			if ( $slug === $active_stylesheet || $slug === $active_template ) {
				continue;
			}
			$result = delete_theme( $slug );
			if ( true === $result ) {
				$deleted[] = $theme->get( 'Name' );
			}
		}

		if ( empty( $deleted ) ) {
			return $this->skipped( __( 'No default themes to delete.', 'mercury-bootstrapper' ) );
		}

		return $this->ok( sprintf(
			/* translators: %s: comma-separated theme names */
			__( 'Deleted: %s', 'mercury-bootstrapper' ),
			implode( ', ', $deleted )
		) );
	}
}
