<?php
/**
 * Empty every sidebar / widget area so the site starts without stock
 * widgets (Search, Recent Posts, Archives, etc.).
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Cleanup_Widgets_Step extends Mercury_Bootstrapper_Step {

	public function get_id(): string {
		return 'cleanup-widgets';
	}

	public function get_label(): string {
		return __( 'Empty all widget areas', 'mercury-bootstrapper' );
	}

	public function run(): array {
		$sidebars = wp_get_sidebars_widgets();
		$removed  = 0;

		foreach ( $sidebars as $sidebar_id => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar_id || 'array_version' === $sidebar_id ) {
				continue;
			}
			if ( is_array( $widgets ) ) {
				$removed += count( $widgets );
				$sidebars[ $sidebar_id ] = array();
			}
		}

		update_option( 'sidebars_widgets', $sidebars );

		if ( 0 === $removed ) {
			return $this->skipped( __( 'No widgets to remove.', 'mercury-bootstrapper' ) );
		}

		return $this->ok( sprintf(
			/* translators: %d: number of widgets removed */
			_n( 'Removed %d widget from sidebars.', 'Removed %d widgets from sidebars.', $removed, 'mercury-bootstrapper' ),
			$removed
		) );
	}
}
