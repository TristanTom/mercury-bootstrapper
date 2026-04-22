<?php
/**
 * Install a theme from wp.org by slug and activate it.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Install_Theme_Step extends Mercury_Bootstrapper_Step {

	private string $slug;
	private string $label;

	public function __construct( string $slug, string $label ) {
		$this->slug  = $slug;
		$this->label = $label;
	}

	public function get_id(): string {
		return 'install-theme-' . $this->slug;
	}

	public function get_label(): string {
		return sprintf(
			/* translators: %s: theme name */
			__( 'Install theme: %s', 'mercury-bootstrapper' ),
			$this->label
		);
	}

	public function is_done(): bool {
		return get_stylesheet() === $this->slug;
	}

	public function run(): array {
		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/theme-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		if ( ! WP_Filesystem() ) {
			return $this->error( __( 'Could not initialize filesystem.', 'mercury-bootstrapper' ) );
		}

		$theme = wp_get_theme( $this->slug );
		$newly_installed = false;

		if ( ! $theme->exists() ) {
			$api = themes_api( 'theme_information', array(
				'slug'   => $this->slug,
				'fields' => array( 'sections' => false ),
			) );
			if ( is_wp_error( $api ) ) {
				return $this->error( sprintf(
					/* translators: %s: error message */
					__( 'themes_api failed: %s', 'mercury-bootstrapper' ),
					$api->get_error_message()
				) );
			}

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Theme_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				return $this->error( $result->get_error_message() );
			}
			if ( is_wp_error( $skin->result ) ) {
				return $this->error( $skin->result->get_error_message() );
			}
			if ( false === $result ) {
				return $this->error( __( 'Theme install returned false — filesystem or download error.', 'mercury-bootstrapper' ) );
			}
			$newly_installed = true;
		}

		switch_theme( $this->slug );

		return $this->ok( $newly_installed
			? __( 'Installed and activated.', 'mercury-bootstrapper' )
			: __( 'Already installed — activated.', 'mercury-bootstrapper' )
		);
	}
}
