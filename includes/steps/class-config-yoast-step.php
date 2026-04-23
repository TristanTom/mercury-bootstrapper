<?php
/**
 * Dismiss Yoast SEO's first-time configuration wizard and the
 * activation redirect to wpseo_installation_successful_free.
 *
 * Both flags live as sub-keys of the main `wpseo` option array.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Yoast_Step extends Mercury_Bootstrapper_Step {

	const OPTION_KEY = 'wpseo';

	public function get_id(): string {
		return 'config-yoast';
	}

	public function get_label(): string {
		return __( 'Configure Yoast SEO: skip first-time wizard', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		$options = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $options ) ) {
			return false;
		}
		$no_redirect = empty( $options['should_redirect_after_install_free'] )
			&& ! empty( $options['activation_redirect_timestamp_free'] );
		$dismissed   = ! empty( $options['dismiss_configuration_workout_notice'] );
		return $no_redirect && $dismissed;
	}

	public function run(): array {
		$options = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options['should_redirect_after_install_free']   = false;
		$options['activation_redirect_timestamp_free']   = time();
		$options['dismiss_configuration_workout_notice'] = true;

		update_option( self::OPTION_KEY, $options );

		return $this->ok( __( 'Activation redirect cleared, first-time configuration notice dismissed.', 'mercury-bootstrapper' ) );
	}
}
