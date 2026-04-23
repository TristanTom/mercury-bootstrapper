<?php
/**
 * Apply Mercury's WP Rocket defaults on top of whatever is in
 * wp_rocket_settings. We only set keys we care about and merge
 * over the existing option so license keys and other untouched
 * values stay intact.
 *
 * Key names verified against WP Rocket source (inc/Engine/Media/Fonts/Admin/Settings.php,
 * views/settings/fields/cache-lifespan.php) and the MainWP Child
 * integration.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Wp_Rocket_Step extends Mercury_Bootstrapper_Step {

	const OPTION_KEY = 'wp_rocket_settings';

	public function get_id(): string {
		return 'config-wp-rocket';
	}

	public function get_label(): string {
		return __( 'Configure WP Rocket: Mercury performance defaults', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		$settings = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			return false;
		}
		foreach ( $this->mercury_defaults() as $key => $value ) {
			if ( ! array_key_exists( $key, $settings ) || $settings[ $key ] != $value ) {
				return false;
			}
		}
		return true;
	}

	public function run(): array {
		$current = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$merged = array_merge( $current, $this->mercury_defaults() );
		update_option( self::OPTION_KEY, $merged );

		return $this->ok( __( 'WP Rocket defaults applied: minify, defer, lazyload, preload, 10 h cache lifespan.', 'mercury-bootstrapper' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mercury_defaults(): array {
		return array(
			'minify_css'          => 1,
			'minify_js'           => 1,
			'async_css'           => 1,
			'remove_unused_css'   => 0,
			'defer_all_js'        => 1,
			'delay_js'            => 0,
			'lazyload'            => 1,
			'lazyload_css_bg_img' => 1,
			'lazyload_iframes'    => 1,
			'lazyload_youtube'    => 1,
			'image_dimensions'    => 1,
			'auto_preload_fonts'  => 1,
			'host_fonts_locally'  => 1,
			'manual_preload'      => 1,
			'preload_links'       => 1,
			'purge_cron_interval' => 10,
			'purge_cron_unit'     => 'HOUR_IN_SECONDS',
		);
	}
}
