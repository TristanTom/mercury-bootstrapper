<?php
/**
 * Apply Mercury's EWWW Image Optimizer defaults and mark the setup
 * wizard as complete so it doesn't auto-open.
 *
 * Option keys and integer level codes (JPG/PNG/GIF=10 = Pixel Perfect,
 * PDF/SVG/WebP=0 = No Compression) verified against EWWW's own defaults
 * in classes/class-plugin.php register_setting defaults.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Ewww_Step extends Mercury_Bootstrapper_Step {

	const WIZARD_OPTION = 'ewww_image_optimizer_wizard_complete';

	public function get_id(): string {
		return 'config-ewww';
	}

	public function get_label(): string {
		return __( 'Configure EWWW Image Optimizer: Mercury image defaults', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		if ( ! get_option( self::WIZARD_OPTION ) ) {
			return false;
		}
		foreach ( $this->mercury_defaults() as $key => $expected ) {
			$actual = get_option( $key );
			if ( $actual != $expected ) {
				return false;
			}
		}
		return true;
	}

	public function run(): array {
		update_option( self::WIZARD_OPTION, true, false );

		foreach ( $this->mercury_defaults() as $key => $value ) {
			update_option( $key, $value );
		}

		return $this->ok( __( 'EWWW defaults applied: Pixel Perfect JPG/PNG/GIF, 1920 px cap, lazy load with above-the-fold skip=2, JS WebP.', 'mercury-bootstrapper' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mercury_defaults(): array {
		return array(
			'ewww_image_optimizer_metadata_remove'        => 1,
			'ewww_image_optimizer_maxmediawidth'          => 1920,
			'ewww_image_optimizer_maxmediaheight'         => 1920,
			'ewww_image_optimizer_add_missing_dims'       => 0,
			'ewww_image_optimizer_lazy_load'              => 1,
			'ewww_image_optimizer_ll_autoscale'           => 1,
			'ewww_image_optimizer_ll_abovethefold'        => 2,
			'ewww_image_optimizer_use_lqip'               => 0,
			'ewww_image_optimizer_use_dcip'               => 0,
			'ewww_image_optimizer_webp'                   => 1,
			'ewww_image_optimizer_webp_for_cdn'           => 1,
			'ewww_image_optimizer_picture_webp'           => 0,
			'ewww_image_optimizer_jpg_level'              => 10,
			'ewww_image_optimizer_png_level'              => 10,
			'ewww_image_optimizer_gif_level'              => 10,
			'ewww_image_optimizer_pdf_level'              => 0,
			'ewww_image_optimizer_svg_level'              => 0,
			'ewww_image_optimizer_webp_level'             => 0,
			'ewww_image_optimizer_resize_existing'        => 1,
			'ewww_image_optimizer_resize_other_existing'  => 0,
			'ewww_image_optimizer_resize_detection'       => 0,
			'ewww_image_optimizer_scheduled_optimization' => 0,
			'ewww_image_optimizer_sharpen'                => 0,
		);
	}
}
