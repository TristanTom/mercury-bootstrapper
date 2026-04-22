<?php
/**
 * Install a premium plugin from a user-uploaded local zip file
 * (path stored in a plugin option by the premium uploads handler).
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Install_Premium_Plugin_Step extends Mercury_Bootstrapper_Step {

	private string $slug;
	private string $label;

	public function __construct( string $slug, string $label ) {
		$this->slug  = $slug;
		$this->label = $label;
	}

	public function get_id(): string {
		return 'install-premium-' . $this->slug;
	}

	public function get_label(): string {
		return sprintf(
			/* translators: %s: plugin name */
			__( 'Install premium plugin: %s', 'mercury-bootstrapper' ),
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
		$zip_path = Mercury_Bootstrapper_Premium_Uploads::get_stored_path( $this->slug );

		if ( null === $zip_path ) {
			return $this->skipped( sprintf(
				/* translators: %s: plugin name */
				__( 'No zip uploaded for %s — skipping.', 'mercury-bootstrapper' ),
				$this->label
			) );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		if ( ! WP_Filesystem() ) {
			return $this->error( __( 'Could not initialize filesystem.', 'mercury-bootstrapper' ) );
		}

		$file            = $this->find_plugin_file();
		$newly_installed = false;

		if ( null === $file ) {
			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $zip_path );

			if ( is_wp_error( $result ) ) {
				return $this->error( $result->get_error_message() );
			}
			if ( is_wp_error( $skin->result ) ) {
				return $this->error( $skin->result->get_error_message() );
			}
			if ( false === $result ) {
				return $this->error( __( 'Premium plugin install returned false.', 'mercury-bootstrapper' ) );
			}

			wp_cache_flush();
			$file            = $this->find_plugin_file();
			$newly_installed = true;

			if ( null === $file ) {
				return $this->error( __( 'Premium plugin installed but main file not found.', 'mercury-bootstrapper' ) );
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
			? __( 'Installed and activated from uploaded zip.', 'mercury-bootstrapper' )
			: __( 'Already installed — activated.', 'mercury-bootstrapper' )
		);
	}

	private function find_plugin_file(): ?string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins  = get_plugins();
		$haystack = $this->slug_aliases();

		foreach ( $plugins as $file => $_data ) {
			$folder = strtolower( strtok( $file, '/' ) );
			if ( in_array( $folder, $haystack, true ) ) {
				return $file;
			}
		}

		return null;
	}

	private function slug_aliases(): array {
		$aliases = array( $this->slug );
		if ( 'elementor-pro' === $this->slug ) {
			$aliases[] = 'elementor-pro';
		}
		if ( 'wp-rocket' === $this->slug ) {
			$aliases[] = 'wp-rocket';
		}
		return $aliases;
	}
}
