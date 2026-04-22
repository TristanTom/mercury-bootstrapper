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

	private Mercury_Bootstrapper_Runner $runner;
	private Mercury_Bootstrapper_Admin_Page $admin_page;

	public static function instance(): Mercury_Bootstrapper_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();

		$this->runner = new Mercury_Bootstrapper_Runner();
		$this->register_steps();

		$this->admin_page = new Mercury_Bootstrapper_Admin_Page( $this->runner );

		$this->register_hooks();
		$this->runner->register_hooks();
	}

	private function load_dependencies(): void {
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-sanity-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-cleanup-default-content-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-cleanup-default-plugins-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-cleanup-default-themes-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-cleanup-widgets-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-core-settings-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-language-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-runner.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-admin-page.php';
	}

	private function register_steps(): void {
		$this->runner->register_step( new Mercury_Bootstrapper_Sanity_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Cleanup_Default_Content_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Cleanup_Default_Plugins_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Cleanup_Widgets_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Core_Settings_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Language_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Cleanup_Default_Themes_Step() );
	}

	private function register_hooks(): void {
		add_action( 'admin_menu', array( $this->admin_page, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin_page, 'enqueue_assets' ) );
	}
}
