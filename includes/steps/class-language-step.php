<?php
/**
 * Download the Estonian language pack from translate.wordpress.org
 * and set the site locale to et.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Language_Step extends Mercury_Bootstrapper_Step {

	const TARGET_LOCALE = 'et';

	public function get_id(): string {
		return 'language';
	}

	public function get_label(): string {
		return __( 'Install Estonian language pack', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		return self::TARGET_LOCALE === get_locale() && self::TARGET_LOCALE === get_option( 'WPLANG' );
	}

	public function run(): array {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! WP_Filesystem() ) {
			return $this->error( __( 'Could not initialize filesystem for language pack install.', 'mercury-bootstrapper' ) );
		}

		$downloaded = wp_download_language_pack( self::TARGET_LOCALE );
		if ( false === $downloaded ) {
			return $this->error( __( 'Failed to download Estonian language pack from translate.wordpress.org.', 'mercury-bootstrapper' ) );
		}

		update_option( 'WPLANG', self::TARGET_LOCALE );

		return $this->ok( __( 'Estonian (et) installed and set as site language.', 'mercury-bootstrapper' ) );
	}
}
