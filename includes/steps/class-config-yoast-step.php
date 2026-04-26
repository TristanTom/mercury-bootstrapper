<?php
/**
 * Configure Yoast SEO with Mercury Media defaults:
 *   - Skip the first-time configuration wizard and activation redirect
 *   - Enable llms.txt feature (off by default in Yoast)
 *   - Enable Schema aggregation endpoint (off by default in Yoast)
 *   - Disable Yoast usage tracking (null by default in Yoast)
 *   - Set Site representation to "Organization" (company)
 *
 * Yoast splits its options across multiple wp_options arrays:
 *   wpseo         — main toggles (analysis, schema, llms.txt, tracking, …)
 *   wpseo_titles  — titles, breadcrumbs, organization vs. person, schema info
 *
 * Everything else on the Yoast Site features and Crawl optimization screens
 * is already correct out of the box (Yoast 26.x), so we only touch the
 * keys that diverge from Mercury defaults.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Yoast_Step extends Mercury_Bootstrapper_Step {

	const OPTION_WPSEO  = 'wpseo';
	const OPTION_TITLES = 'wpseo_titles';

	public function get_id(): string {
		return 'config-yoast';
	}

	public function get_label(): string {
		return __( 'Configure Yoast SEO: skip wizard, enable llms.txt + schema endpoint, set Organization', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		$wpseo  = get_option( self::OPTION_WPSEO, array() );
		$titles = get_option( self::OPTION_TITLES, array() );

		if ( ! is_array( $wpseo ) || ! is_array( $titles ) ) {
			return false;
		}

		$wizard_skipped = empty( $wpseo['should_redirect_after_install_free'] )
			&& ! empty( $wpseo['activation_redirect_timestamp_free'] )
			&& ! empty( $wpseo['dismiss_configuration_workout_notice'] );

		$features_set = ! empty( $wpseo['enable_llms_txt'] )
			&& ! empty( $wpseo['enable_schema_aggregation_endpoint'] )
			&& isset( $wpseo['tracking'] )
			&& false === $wpseo['tracking'];

		$org_set = isset( $titles['company_or_person'] )
			&& 'company' === $titles['company_or_person'];

		return $wizard_skipped && $features_set && $org_set;
	}

	public function run(): array {
		$wpseo = get_option( self::OPTION_WPSEO, array() );
		if ( ! is_array( $wpseo ) ) {
			$wpseo = array();
		}

		$wpseo['should_redirect_after_install_free']   = false;
		$wpseo['activation_redirect_timestamp_free']   = time();
		$wpseo['dismiss_configuration_workout_notice'] = true;
		$wpseo['enable_llms_txt']                      = true;
		$wpseo['enable_schema_aggregation_endpoint']   = true;
		$wpseo['tracking']                             = false;

		update_option( self::OPTION_WPSEO, $wpseo );

		$titles = get_option( self::OPTION_TITLES, array() );
		if ( ! is_array( $titles ) ) {
			$titles = array();
		}

		$titles['company_or_person'] = 'company';

		update_option( self::OPTION_TITLES, $titles );

		return $this->ok( __( 'Wizard dismissed; llms.txt and Schema endpoint enabled; tracking disabled; site represented as Organization.', 'mercury-bootstrapper' ) );
	}
}
