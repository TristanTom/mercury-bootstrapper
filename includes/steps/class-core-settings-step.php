<?php
/**
 * Apply Estonian / Mercury-standard core WordPress settings:
 * timezone, date/time format, week start, permalinks, registration,
 * comment defaults, and the default category rename.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Core_Settings_Step extends Mercury_Bootstrapper_Step {

	public function get_id(): string {
		return 'core-settings';
	}

	public function get_label(): string {
		return __( 'Apply Estonian core settings (timezone, permalinks, comments, category)', 'mercury-bootstrapper' );
	}

	public function run(): array {
		$options = array(
			'timezone_string'        => 'Europe/Tallinn',
			'gmt_offset'             => '',
			'date_format'            => 'd.m.Y',
			'time_format'            => 'H:i',
			'start_of_week'          => 1,
			'users_can_register'     => 0,
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
			'default_pingback_flag'  => 0,
			'permalink_structure'    => '/%postname%/',
		);

		foreach ( $options as $name => $value ) {
			update_option( $name, $value );
		}

		$this->flush_permalinks();
		$category_message = $this->rename_default_category();

		return $this->ok( sprintf(
			/* translators: %s: default category status message */
			__( 'Timezone Europe/Tallinn, permalinks /%%postname%%/, comments closed by default. %s', 'mercury-bootstrapper' ),
			$category_message
		) );
	}

	private function flush_permalinks(): void {
		global $wp_rewrite;
		if ( isset( $wp_rewrite ) && is_object( $wp_rewrite ) ) {
			$wp_rewrite->set_permalink_structure( '/%postname%/' );
			$wp_rewrite->flush_rules( false );
		}
	}

	private function rename_default_category(): string {
		$default_cat_id = (int) get_option( 'default_category' );
		if ( $default_cat_id <= 0 ) {
			return __( 'Default category option missing — skipped rename.', 'mercury-bootstrapper' );
		}

		$term = get_term( $default_cat_id, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			return __( 'Default category term not found — skipped rename.', 'mercury-bootstrapper' );
		}

		if ( 'artiklid' === $term->slug ) {
			return __( 'Default category already renamed.', 'mercury-bootstrapper' );
		}

		$result = wp_update_term( $default_cat_id, 'category', array(
			'name' => 'Artiklid',
			'slug' => 'artiklid',
		) );

		if ( is_wp_error( $result ) ) {
			return sprintf(
				/* translators: %s: error message */
				__( 'Category rename failed: %s', 'mercury-bootstrapper' ),
				$result->get_error_message()
			);
		}

		return __( 'Default category renamed to "Artiklid".', 'mercury-bootstrapper' );
	}
}
