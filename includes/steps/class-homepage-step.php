<?php
/**
 * Create an "Avaleht" page and set it as the static front page.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Homepage_Step extends Mercury_Bootstrapper_Step {

	const SLUG  = 'avaleht';
	const TITLE = 'Avaleht';

	public function get_id(): string {
		return 'homepage';
	}

	public function get_label(): string {
		return __( 'Create "Avaleht" and set as static front page', 'mercury-bootstrapper' );
	}

	public function is_done(): bool {
		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return false;
		}
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id <= 0 ) {
			return false;
		}
		$front = get_post( $front_id );
		return $front instanceof WP_Post && 'page' === $front->post_type && 'publish' === $front->post_status;
	}

	public function run(): array {
		$page = get_page_by_path( self::SLUG, OBJECT, 'page' );

		if ( $page instanceof WP_Post ) {
			if ( 'publish' !== $page->post_status ) {
				wp_update_post( array(
					'ID'          => $page->ID,
					'post_status' => 'publish',
				) );
			}
			$page_id = (int) $page->ID;
		} else {
			$page_id = wp_insert_post( array(
				'post_title'   => self::TITLE,
				'post_name'    => self::SLUG,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			), true );

			if ( is_wp_error( $page_id ) ) {
				return $this->error( sprintf(
					/* translators: %s: error message */
					__( 'Failed to create Avaleht: %s', 'mercury-bootstrapper' ),
					$page_id->get_error_message()
				) );
			}
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		return $this->ok( sprintf(
			/* translators: %d: page ID */
			__( 'Avaleht (ID %d) set as static front page.', 'mercury-bootstrapper' ),
			$page_id
		) );
	}
}
