<?php
/**
 * Handle uploads of premium plugin zip files (Elementor Pro, WP Rocket).
 * Stores uploads in a private directory inside wp-content/uploads
 * and records the path in a plugin option keyed by slug.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Premium_Uploads {

	const AJAX_ACTION      = 'mercury_bootstrapper_upload_premium';
	const NONCE_ACTION     = 'mercury_bootstrapper_upload';
	const OPTION_PREFIX    = 'mercury_bootstrapper_premium_zip_';
	const UPLOAD_SUBDIR    = 'mercury-bootstrapper';
	const MAX_FILE_BYTES   = 52428800; // 50 MB

	/**
	 * @var array<string, string> slug => human label
	 */
	private array $allowed_slugs = array(
		'elementor-pro' => 'Elementor Pro',
		'wp-rocket'     => 'WP Rocket',
	);

	public function register_hooks(): void {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_upload' ) );
	}

	public function get_allowed_slugs(): array {
		return $this->allowed_slugs;
	}

	public static function get_stored_path( string $slug ): ?string {
		$path = get_option( self::OPTION_PREFIX . $slug, '' );
		if ( ! is_string( $path ) || '' === $path ) {
			return null;
		}
		return file_exists( $path ) ? $path : null;
	}

	public function handle_upload(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'mercury-bootstrapper' ) ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( ! isset( $this->allowed_slugs[ $slug ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown slug.', 'mercury-bootstrapper' ) ), 400 );
		}

		if ( ! isset( $_FILES['zip'] ) || ! is_array( $_FILES['zip'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file received.', 'mercury-bootstrapper' ) ), 400 );
		}

		$file = $_FILES['zip'];

		if ( ! empty( $file['error'] ) ) {
			wp_send_json_error( array( 'message' => sprintf(
				/* translators: %d: PHP upload error code */
				__( 'Upload error (code %d).', 'mercury-bootstrapper' ),
				(int) $file['error']
			) ), 400 );
		}

		if ( (int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_FILE_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'File size out of range.', 'mercury-bootstrapper' ) ), 400 );
		}

		$name      = isset( $file['name'] ) ? (string) $file['name'] : '';
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( 'zip' !== $extension ) {
			wp_send_json_error( array( 'message' => __( 'Only .zip files are accepted.', 'mercury-bootstrapper' ) ), 400 );
		}

		if ( ! $this->is_zip_magic( (string) $file['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'File is not a valid zip archive.', 'mercury-bootstrapper' ) ), 400 );
		}

		$target_dir = $this->ensure_upload_dir();
		if ( null === $target_dir ) {
			wp_send_json_error( array( 'message' => __( 'Could not create upload directory.', 'mercury-bootstrapper' ) ), 500 );
		}

		$target_path = $target_dir . '/' . $slug . '.zip';

		$old_path = get_option( self::OPTION_PREFIX . $slug, '' );
		if ( is_string( $old_path ) && '' !== $old_path && file_exists( $old_path ) && $old_path !== $target_path ) {
			@unlink( $old_path );
		}

		if ( ! move_uploaded_file( (string) $file['tmp_name'], $target_path ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not move uploaded file.', 'mercury-bootstrapper' ) ), 500 );
		}

		@chmod( $target_path, 0644 );

		update_option( self::OPTION_PREFIX . $slug, $target_path, false );

		wp_send_json_success( array(
			'slug'    => $slug,
			'label'   => $this->allowed_slugs[ $slug ],
			'size'    => (int) $file['size'],
			'message' => __( 'Uploaded.', 'mercury-bootstrapper' ),
		) );
	}

	private function is_zip_magic( string $path ): bool {
		if ( '' === $path || ! is_readable( $path ) ) {
			return false;
		}
		$handle = fopen( $path, 'rb' );
		if ( ! $handle ) {
			return false;
		}
		$bytes = fread( $handle, 4 );
		fclose( $handle );
		return is_string( $bytes ) && "PK\x03\x04" === $bytes;
	}

	private function ensure_upload_dir(): ?string {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}
		$dir = trailingslashit( $uploads['basedir'] ) . self::UPLOAD_SUBDIR;

		if ( ! wp_mkdir_p( $dir ) ) {
			return null;
		}

		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			@file_put_contents( $htaccess, "Require all denied\n" );
		}

		$index = $dir . '/index.html';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, '' );
		}

		return $dir;
	}
}
