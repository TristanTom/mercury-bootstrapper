<?php
/**
 * Main plugin class — wires everything together.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mercury_Bootstrapper_Plugin {

	private static ?Mercury_Bootstrapper_Plugin $instance = null;

	private Mercury_Bootstrapper_Admin_Page $admin_page;

	public static function instance(): Mercury_Bootstrapper_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->admin_page = new Mercury_Bootstrapper_Admin_Page();
		$this->register_hooks();
	}

	private function load_dependencies(): void {
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-admin-page.php';
	}

	private function register_hooks(): void {
		add_action( 'admin_menu', array( $this->admin_page, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin_page, 'enqueue_assets' ) );
	}
}
