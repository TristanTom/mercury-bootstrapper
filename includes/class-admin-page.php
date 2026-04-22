<?php
/**
 * "Mercury Setup" admin page.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Admin_Page {

	const MENU_SLUG = 'mercury-bootstrapper';
	const CAPABILITY = 'manage_options';

	public function register_menu(): void {
		add_menu_page(
			__( 'Mercury Setup', 'mercury-bootstrapper' ),
			__( 'Mercury Setup', 'mercury-bootstrapper' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' ),
			'dashicons-admin-tools',
			3
		);
	}

	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'mercury-bootstrapper-admin',
			MERCURY_BOOTSTRAPPER_URL . 'assets/css/admin.css',
			array(),
			MERCURY_BOOTSTRAPPER_VERSION
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'mercury-bootstrapper' ) );
		}
		?>
		<div class="wrap mercury-bootstrapper">
			<h1><?php esc_html_e( 'Mercury Setup', 'mercury-bootstrapper' ); ?></h1>

			<p class="mercury-bootstrapper__lead">
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'Version %s — setup runner and automation steps are coming in the next updates. This page is the entry point.', 'mercury-bootstrapper' ),
					esc_html( MERCURY_BOOTSTRAPPER_VERSION )
				);
				?>
			</p>

			<div class="mercury-bootstrapper__card">
				<h2><?php esc_html_e( 'Coming soon', 'mercury-bootstrapper' ); ?></h2>
				<ul>
					<li><?php esc_html_e( 'Run Full Setup button with live execution log', 'mercury-bootstrapper' ); ?></li>
					<li><?php esc_html_e( 'Upload fields for Elementor Pro and WP Rocket zip files', 'mercury-bootstrapper' ); ?></li>
					<li><?php esc_html_e( 'Launch Checklist showing what is automated vs still manual', 'mercury-bootstrapper' ); ?></li>
					<li><?php esc_html_e( 'Self-deactivate and uninstall button when you are done', 'mercury-bootstrapper' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}
}
