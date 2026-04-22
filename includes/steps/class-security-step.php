<?php
/**
 * Harden the site:
 * - Drop a mu-plugin that disables XML-RPC (persists after this plugin is removed).
 * - Remove readme.html and license.txt from the WordPress root.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Security_Step extends Mercury_Bootstrapper_Step {

	const MU_PLUGIN_FILENAME = 'mercury-disable-xmlrpc.php';

	private const MU_PLUGIN_CONTENTS = <<<'PHP'
<?php
/**
 * Plugin Name: Mercury — Disable XML-RPC
 * Description: Dropped in by mercury-bootstrapper. Disables XML-RPC globally.
 * Version:     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_filter( 'xmlrpc_enabled', '__return_false' );
PHP;

	public function get_id(): string {
		return 'security';
	}

	public function get_label(): string {
		return __( 'Harden: disable XML-RPC, remove readme.html / license.txt', 'mercury-bootstrapper' );
	}

	public function run(): array {
		$notes = array();

		$mu_result = $this->install_mu_plugin();
		if ( is_string( $mu_result ) ) {
			$notes[] = $mu_result;
		} else {
			return $this->error( $mu_result['error'] );
		}

		foreach ( array( 'readme.html', 'license.txt' ) as $filename ) {
			$notes[] = $this->delete_root_file( $filename );
		}

		return $this->ok( implode( ' ', $notes ) );
	}

	/**
	 * @return string|array{error: string}
	 */
	private function install_mu_plugin() {
		$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';

		if ( ! wp_mkdir_p( $mu_dir ) ) {
			return array( 'error' => __( 'Could not create mu-plugins directory.', 'mercury-bootstrapper' ) );
		}

		$target = trailingslashit( $mu_dir ) . self::MU_PLUGIN_FILENAME;

		if ( file_exists( $target ) && self::MU_PLUGIN_CONTENTS === @file_get_contents( $target ) ) {
			return __( 'XML-RPC already disabled via mu-plugin.', 'mercury-bootstrapper' );
		}

		$written = @file_put_contents( $target, self::MU_PLUGIN_CONTENTS );
		if ( false === $written ) {
			return array( 'error' => __( 'Could not write mu-plugin file.', 'mercury-bootstrapper' ) );
		}

		@chmod( $target, 0644 );

		return __( 'XML-RPC disabled via mu-plugin.', 'mercury-bootstrapper' );
	}

	private function delete_root_file( string $filename ): string {
		$path = ABSPATH . $filename;

		if ( ! file_exists( $path ) ) {
			return sprintf(
				/* translators: %s: filename */
				__( '%s already absent.', 'mercury-bootstrapper' ),
				$filename
			);
		}

		if ( @unlink( $path ) ) {
			return sprintf(
				/* translators: %s: filename */
				__( '%s removed.', 'mercury-bootstrapper' ),
				$filename
			);
		}

		return sprintf(
			/* translators: %s: filename */
			__( '%s could not be removed (permissions?).', 'mercury-bootstrapper' ),
			$filename
		);
	}
}
