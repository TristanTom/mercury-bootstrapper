<?php
/**
 * GitHub Releases self-update:
 * - Queries the latest release via the public GitHub API.
 * - Injects update info into WordPress' update_plugins transient.
 * - Provides "View details" data via the plugins_api filter.
 * - Renames the extracted folder to 'mercury-bootstrapper' so updates land in place.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Updater {

	const GITHUB_USER    = 'TristanTom';
	const GITHUB_REPO    = 'mercury-bootstrapper';
	const TRANSIENT_KEY  = 'mercury_bootstrapper_gh_release';
	const TRANSIENT_TTL  = 12 * HOUR_IN_SECONDS;
	const ASSET_FILENAME = 'mercury-bootstrapper.zip';

	public function register_hooks(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'rename_source' ), 10, 4 );
	}

	/**
	 * @param mixed $transient
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->fetch_latest_release();
		if ( null === $release ) {
			return $transient;
		}

		$plugin_file = plugin_basename( MERCURY_BOOTSTRAPPER_FILE );
		$current     = MERCURY_BOOTSTRAPPER_VERSION;
		$latest      = $release['version'];

		if ( ! version_compare( $latest, $current, '>' ) ) {
			if ( isset( $transient->no_update ) ) {
				$transient->no_update[ $plugin_file ] = $this->build_update_object( $release, $plugin_file );
			}
			return $transient;
		}

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$transient->response[ $plugin_file ] = $this->build_update_object( $release, $plugin_file );

		return $transient;
	}

	/**
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || 'mercury-bootstrapper' !== $args->slug ) {
			return $result;
		}

		$release = $this->fetch_latest_release();
		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Mercury Bootstrapper',
			'slug'          => 'mercury-bootstrapper',
			'version'       => $release['version'],
			'author'        => '<a href="https://mercurymedia.ee">Mercury Media</a>',
			'homepage'      => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'download_link' => $release['zip_url'],
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'description' => wp_kses_post( '<p>Automates baseline WordPress site setup for Mercury Media projects.</p>' ),
				'changelog'   => wp_kses_post( '<pre>' . esc_html( $release['body'] ) . '</pre>' ),
			),
		);
	}

	/**
	 * @param string                $source
	 * @param string                $remote_source
	 * @param WP_Upgrader           $upgrader
	 * @param array<string, mixed>  $hook_extra
	 * @return string|WP_Error
	 */
	public function rename_source( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( empty( $hook_extra['plugin'] ) ) {
			return $source;
		}
		if ( plugin_basename( MERCURY_BOOTSTRAPPER_FILE ) !== $hook_extra['plugin'] ) {
			return $source;
		}

		$parent   = trailingslashit( dirname( untrailingslashit( $source ) ) );
		$expected = $parent . 'mercury-bootstrapper';

		if ( untrailingslashit( $source ) === $expected ) {
			return $source;
		}

		if ( ! @rename( untrailingslashit( $source ), $expected ) ) {
			return $source;
		}

		return trailingslashit( $expected );
	}

	/**
	 * @return array{version: string, zip_url: string, published_at: string, body: string}|null
	 */
	private function fetch_latest_release(): ?array {
		$cached = get_site_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) && isset( $cached['version'], $cached['zip_url'] ) ) {
			return $cached;
		}

		$url = 'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest';

		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'mercury-bootstrapper/' . MERCURY_BOOTSTRAPPER_VERSION,
			),
		) );

		if ( is_wp_error( $response ) ) {
			set_site_transient( self::TRANSIENT_KEY, 'error', MINUTE_IN_SECONDS * 10 );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			set_site_transient( self::TRANSIENT_KEY, 'error', MINUTE_IN_SECONDS * 10 );
			return null;
		}

		$body    = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( ! is_array( $decoded ) || empty( $decoded['tag_name'] ) ) {
			set_site_transient( self::TRANSIENT_KEY, 'error', MINUTE_IN_SECONDS * 10 );
			return null;
		}

		$version = ltrim( (string) $decoded['tag_name'], 'vV' );
		$zip_url = $this->find_asset_url( $decoded );

		if ( '' === $version || null === $zip_url ) {
			set_site_transient( self::TRANSIENT_KEY, 'error', MINUTE_IN_SECONDS * 10 );
			return null;
		}

		$data = array(
			'version'      => $version,
			'zip_url'      => $zip_url,
			'published_at' => isset( $decoded['published_at'] ) ? (string) $decoded['published_at'] : '',
			'body'         => isset( $decoded['body'] ) ? (string) $decoded['body'] : '',
		);

		set_site_transient( self::TRANSIENT_KEY, $data, self::TRANSIENT_TTL );

		return $data;
	}

	/**
	 * @param array<string, mixed> $release
	 */
	private function find_asset_url( array $release ): ?string {
		if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
			return null;
		}
		foreach ( $release['assets'] as $asset ) {
			if ( ! is_array( $asset ) ) {
				continue;
			}
			if ( isset( $asset['name'] ) && self::ASSET_FILENAME === $asset['name'] && isset( $asset['browser_download_url'] ) ) {
				return (string) $asset['browser_download_url'];
			}
		}
		return null;
	}

	private function build_update_object( array $release, string $plugin_file ): object {
		return (object) array(
			'id'            => 'github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
			'slug'          => 'mercury-bootstrapper',
			'plugin'        => $plugin_file,
			'new_version'   => $release['version'],
			'url'           => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
			'package'       => $release['zip_url'],
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => '',
			'requires_php'  => '7.4',
			'compatibility' => new stdClass(),
		);
	}
}
