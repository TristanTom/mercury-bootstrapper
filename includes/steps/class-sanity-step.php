<?php
/**
 * Sanity-check step — confirms the runner pipeline works end-to-end.
 *
 * Does nothing beyond returning OK. Will be kept as the first step so we
 * always have one guaranteed-safe step to click.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Sanity_Step extends Mercury_Bootstrapper_Step {

	public function get_id(): string {
		return 'sanity-check';
	}

	public function get_label(): string {
		return __( 'Sanity check (plugin responding)', 'mercury-bootstrapper' );
	}

	public function run(): array {
		return $this->ok( sprintf(
			/* translators: %s: WordPress version */
			__( 'Running on WordPress %s', 'mercury-bootstrapper' ),
			get_bloginfo( 'version' )
		) );
	}
}
