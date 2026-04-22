<?php
/**
 * Install a plugin from wp.org by slug and activate it.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Install_Plugin_Step extends Mercury_Bootstrapper_Step {

	private string $slug;
	private string $label;

	public function __construct( string $slug, string $label ) {
		$this->slug  = $slug;
		$this->label = $label;
	}

	public function get_id(): string {
		return 'install-plugin-' . $this->slug;
	}

	public function get_label(): string {
		return sprintf(
			/* translators: %s: plugin name */
			__( 'Install plugin: %s', 'mercury-bootstrapper' ),
			$this->label
		);
	}

	public function is_done(): bool {
		$file = $this->find_plugin_file();
		if ( null === $file ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $file );
	}

	public function run(): array {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		if ( ! WP_Filesystem() ) {
			return $this->error( __( 'Could not initialize filesystem.', 'mercury-bootstrapper' ) );
		}

		$file            = $this->find_plugin_file();
		$newly_installed = false;

		if ( null === $file ) {
			$api = plugins_api( 'plugin_information', array(
				'slug'   => $this->slug,
				'fields' => array( 'sections' => false ),
			) );
			if ( is_wp_error( $api ) ) {
				return $this->error( sprintf(
					/* translators: %s: error message */
					__( 'plugins_api failed: %s', 'mercury-bootstrapper' ),
					$api->get_error_message()
				) );
			}

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				return $this->error( $result->get_error_message() );
			}
			if ( is_wp_error( $skin->result ) ) {
				return $this->error( $skin->result->get_error_message() );
			}
			if ( false === $result ) {
				return $this->error( __( 'Plugin install returned false — filesystem or download error.', 'mercury-bootstrapper' ) );
			}

			wp_cache_flush();
			$file            = $this->find_plugin_file();
			$newly_installed = true;

			if ( null === $file ) {
				return $this->error( __( 'Plugin installed but main file could not be located.', 'mercury-bootstrapper' ) );
			}
		}

		if ( ! is_plugin_active( $file ) ) {
			$activated = activate_plugin( $file );
			if ( is_wp_error( $activated ) ) {
				return $this->error( sprintf(
					/* translators: %s: error message */
					__( 'Activation failed: %s', 'mercury-bootstrapper' ),
					$activated->get_error_message()
				) );
			}
		}

		return $this->ok( $newly_installed
			? __( 'Installed and activated.', 'mercury-bootstrapper' )
			: __( 'Already installed — activated.', 'mercury-bootstrapper' )
		);
	}

	private function find_plugin_file(): ?string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$prefix  = $this->slug . '/';
		$plugins = get_plugins();

		foreach ( $plugins as $file => $_data ) {
			if ( 0 === strpos( $file, $prefix ) ) {
				return $file;
			}
		}

		return null;
	}
}
