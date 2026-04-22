<?php
/**
 * Delete WordPress's default content on a fresh install:
 * "Hello world!" post, "Sample Page", the "A WordPress Commenter" comment,
 * and the WP-generated Privacy Policy draft.
 *
 * Identifies defaults by slug + title match to avoid touching user content
 * — but this plugin still targets fresh installs only (per spec).
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Cleanup_Default_Content_Step extends Mercury_Bootstrapper_Step {

	public function get_id(): string {
		return 'cleanup-default-content';
	}

	public function get_label(): string {
		return __( 'Delete default content (Hello world, Sample Page, comment, Privacy Policy draft)', 'mercury-bootstrapper' );
	}

	public function run(): array {
		$deleted = array();

		$hello_world = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $hello_world && 'Hello world!' === $hello_world->post_title ) {
			wp_delete_post( (int) $hello_world->ID, true );
			$deleted[] = __( 'Hello world post', 'mercury-bootstrapper' );
		}

		$sample_page = get_page_by_path( 'sample-page', OBJECT, 'page' );
		if ( $sample_page && 'Sample Page' === $sample_page->post_title ) {
			wp_delete_post( (int) $sample_page->ID, true );
			$deleted[] = __( 'Sample Page', 'mercury-bootstrapper' );
		}

		$default_comment = get_comment( 1 );
		if ( $default_comment && false !== stripos( (string) $default_comment->comment_author, 'WordPress Commenter' ) ) {
			wp_delete_comment( (int) $default_comment->comment_ID, true );
			$deleted[] = __( 'default comment', 'mercury-bootstrapper' );
		}

		$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy_id > 0 ) {
			$privacy_page = get_post( $privacy_id );
			if ( $privacy_page && 'draft' === $privacy_page->post_status ) {
				wp_delete_post( $privacy_id, true );
				delete_option( 'wp_page_for_privacy_policy' );
				$deleted[] = __( 'Privacy Policy draft', 'mercury-bootstrapper' );
			}
		}

		if ( empty( $deleted ) ) {
			return $this->skipped( __( 'Default content not present.', 'mercury-bootstrapper' ) );
		}

		return $this->ok( sprintf(
			/* translators: %s: comma-separated list of deleted items */
			__( 'Deleted: %s', 'mercury-bootstrapper' ),
			implode( ', ', $deleted )
		) );
	}
}
