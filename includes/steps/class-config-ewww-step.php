<?php
/**
 * Mark EWWW Image Optimizer's setup wizard as completed so it
 * doesn't auto-open on a fresh site. Matches the option key and
 * autoload flag that EWWW itself uses internally.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Ewww_Step extends Mercury_Bootstrapper_Step {

	const OPTION_KEY = 'ewww_image_optimizer_wizard_complete';

	public function get_id(): string {
		return 'config-ewww';
	}

	public function get_label(): string {
		return __( 'Configure EWWW Image Optimizer: skip setup wizard', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		return (bool) get_option( self::OPTION_KEY );
	}

	public function run(): array {
		update_option( self::OPTION_KEY, true, false );

		return $this->ok( __( 'Setup wizard marked complete.', 'mercury-bootstrapper' ) );
	}
}
