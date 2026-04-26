<?php
/**
 * Configure Complianz GDPR/CCPA with Mercury Media defaults.
 *
 * Two storage areas:
 *   cmplz_options            — all wizard answers (regions, statistics, services …)
 *   wp_cmplz_cookiebanners   — banner styling and localised Estonian texts
 *
 * What this step sets (everything else is already correct in Complianz defaults):
 *
 *   cmplz_options:
 *     regions                                 → eu  (EU GDPR)
 *     country_company                         → EE  (Estonia)
 *     compile_statistics                      → google-tag-manager
 *     compile_statistics_more_info_tag_manager→ [accepted, ip-addresses-blocked, no-sharing]
 *     configuration_by_complianz              → no  (GTM is managed externally, not by Complianz)
 *     uses_thirdparty_services                → yes
 *     thirdparty_services_on_site             → [google-fonts, google-recaptcha]
 *     self_host_google_fonts                  → yes
 *
 *   Default banner (row with default = 1 in wp_cmplz_cookiebanners):
 *     title, header, accept, dismiss, view/save_preferences, manage_consent_options,
 *     message_optin, category_* and *_text, position, border radii, font_size …
 *
 * What is intentionally left for the admin to complete manually:
 *   - Wizard → Documents (cookie-policy & privacy-statement page URLs)
 *   - Wizard → Website Scan (one-off action)
 *   - wizard_completed flag (admin finishes remaining wizard steps)
 *   - Site-specific data: DPO e-mail, company details, licence keys
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Config_Complianz_Step extends Mercury_Bootstrapper_Step {

	const OPTION_KEY = 'cmplz_options';

	public function get_id(): string {
		return 'config-complianz';
	}

	public function get_label(): string {
		return __( 'Configure Complianz: EU/EE defaults, GTM consent, banner texts (ET)', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		$options = get_option( self::OPTION_KEY, array() );
		return is_array( $options )
			&& isset( $options['country_company'] )
			&& 'EE' === $options['country_company']
			&& isset( $options['regions'] )
			&& ! empty( $options['regions'] );
	}

	public function run(): array {
		$this->apply_cmplz_options();
		$banner_result = $this->apply_banner();
		return $this->ok(
			__( 'Complianz: EU region, Estonia, GTM + consent configured; banner styled with Estonian texts.', 'mercury-bootstrapper' )
			. ( $banner_result ? '' : ' ' . __( '(Banner table not found — run again after Complianz activates.)', 'mercury-bootstrapper' ) )
		);
	}

	// -------------------------------------------------------------------------
	// cmplz_options
	// -------------------------------------------------------------------------

	private function apply_cmplz_options(): void {
		$options = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options['regions']          = array( 'eu' => 1 );
		$options['country_company']  = 'EE';

		$options['compile_statistics']                       = 'google-tag-manager';
		$options['compile_statistics_more_info_tag_manager'] = array(
			'accepted'           => 1,
			'ip-addresses-blocked' => 1,
			'no-sharing'         => 1,
		);
		$options['configuration_by_complianz'] = 'no';
		$options['consent_for_anonymous_stats'] = 'yes';

		$options['uses_thirdparty_services']  = 'yes';
		$options['thirdparty_services_on_site'] = array(
			'google-fonts'      => 1,
			'google-recaptcha'  => 1,
		);
		$options['self_host_google_fonts'] = 'yes';

		update_option( self::OPTION_KEY, $options );
	}

	// -------------------------------------------------------------------------
	// Banner — wp_cmplz_cookiebanners custom table
	// -------------------------------------------------------------------------

	private function apply_banner(): bool {
		if ( ! class_exists( 'CMPLZ_COOKIEBANNER' ) ) {
			return false;
		}

		global $wpdb;
		$banner_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->prefix}cmplz_cookiebanners WHERE `default` = 1 LIMIT 1"
		);

		if ( ! $banner_id ) {
			return false;
		}

		$banner = new CMPLZ_COOKIEBANNER( (int) $banner_id );

		// Appearance.
		$banner->position                  = 'bottom-right';
		$banner->animation                 = 'none';
		$banner->banner_width              = 526;
		$banner->checkbox_style            = 'slider';
		$banner->use_logo                  = 0;
		$banner->close_button              = 0;
		$banner->use_box_shadow            = 1;
		$banner->header_footer_shadow      = 0;
		$banner->soft_cookiewall           = 0;
		$banner->colorpalette_border_radius = 20;
		$banner->buttons_border_radius     = 100;
		$banner->border_width              = 0;
		$banner->font_size                 = 12;
		$banner->dismiss_on_scroll         = 0;
		$banner->dismiss_on_timeout        = 0;

		// Estonian texts.
		$banner->title                  = 'Nõusolek küpsistega';
		$banner->header                 = 'Halda nõusolekut';
		$banner->manage_consent_options = 'Halda nõusolekut';
		$banner->accept                 = 'Nõustu';
		$banner->dismiss                = 'Keeldu';
		$banner->view_preferences       = 'Seaded';
		$banner->save_preferences       = 'Salvesta';

		$banner->message_optin =
			'Me kasutame oma veebisaidil küpsiseid ja muud sarnast tehnoloogiat ning töötleme teie ' .
			'isikuandmeid (nt IP-aadressi) muu hulgas sisu ja reklaami isikupärastamiseks, kolmandast ' .
			'isikust teenusepakkujate meedia integreerimiseks või meie veebisaidi külastatavuse ' .
			'analüüsimiseks. Andmeid võidakse töödelda ka küpsiste seadistamise tulemusena. Me jagame ' .
			'andmeid kolmandate isikutega, kes on täpsustatud privaatsusseadetes.';

		$banner->category_functional = 'Funktsionaalsed';
		$banner->functional_text     =
			'Neid teenuseid on vaja veebisaidi põhifunktsioonide jaoks. Need hõlmavad ainult ' .
			'tehniliselt vajalikke teenuseid. Olulistele teenustele ei saa vastuväiteid esitada.';

		$banner->category_stats            = 'Statistika';
		$banner->statistics_text_anonymous =
			'Neid teenuseid on vaja anonüümsete andmete kogumiseks veebisaidi külastajate kohta. ' .
			'Sellised andmed võimaldavad meil külastajaid paremini mõista ja veebisaiti optimeerida.';

		$banner->category_all  = 'Turundus';
		$banner->marketing_text =
			'Tehnilise salvestuse või juurdepääsu kasutamine on vajalik kasutajaprofiilide loomiseks ' .
			'reklaami saatmise eesmärgil või kasutaja jälgimiseks veebisaidil või mitmel veebisaidil ' .
			'sarnastel turunduslikel eesmärkidel.';

		$banner->save();

		return true;
	}
}
