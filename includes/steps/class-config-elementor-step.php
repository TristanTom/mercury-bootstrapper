<?php
/**
 * Mark Elementor's onboarding as complete and clear the activation redirect
 * transient so the wizard never auto-opens on a fresh site. Same mechanism
 * that Astra theme uses to skip Elementor onboarding.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Elementor_Step extends Mercury_Bootstrapper_Step {

	const ONBOARDED_OPTION      = 'elementor_onboarded';
	const ACTIVATION_TRANSIENT  = 'elementor_activation_redirect';

	public function get_id(): string {
		return 'config-elementor';
	}

	public function get_label(): string {
		return __( 'Configure Elementor: skip onboarding wizard', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		return (bool) get_option( self::ONBOARDED_OPTION )
			&& false === get_transient( self::ACTIVATION_TRANSIENT );
	}

	public function run(): array {
		update_option( self::ONBOARDED_OPTION, true );
		delete_transient( self::ACTIVATION_TRANSIENT );

		return $this->ok( __( 'Onboarding marked complete, activation redirect cleared.', 'mercury-bootstrapper' ) );
	}
}
