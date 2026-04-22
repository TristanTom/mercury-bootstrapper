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
	private Mercury_Bootstrapper_Premium_Uploads $premium_uploads;
	private Mercury_Bootstrapper_Uninstaller $uninstaller;
	private Mercury_Bootstrapper_Updater $updater;

	public static function instance(): Mercury_Bootstrapper_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();

		$this->runner          = new Mercury_Bootstrapper_Runner();
		$this->premium_uploads = new Mercury_Bootstrapper_Premium_Uploads();
		$this->uninstaller     = new Mercury_Bootstrapper_Uninstaller();
		$this->updater         = new Mercury_Bootstrapper_Updater();
		$this->register_steps();

		$this->admin_page = new Mercury_Bootstrapper_Admin_Page( $this->runner, $this->premium_uploads );

		$this->register_hooks();
		$this->runner->register_hooks();
		$this->premium_uploads->register_hooks();
		$this->uninstaller->register_hooks();
		$this->updater->register_hooks();
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
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-install-theme-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-install-plugin-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-install-premium-plugin-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-homepage-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/steps/class-security-step.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-premium-uploads.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-uninstaller.php';
		require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-updater.php';
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
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Theme_Step( 'hello-elementor', 'Hello Elementor' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Cleanup_Default_Themes_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Plugin_Step( 'elementor', 'Elementor' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Plugin_Step( 'wordpress-seo', 'Yoast SEO' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Plugin_Step( 'updraftplus', 'UpdraftPlus' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Plugin_Step( 'ewww-image-optimizer', 'EWWW Image Optimizer' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Plugin_Step( 'complianz-gdpr', 'Complianz GDPR/CCPA Cookie Consent' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Premium_Plugin_Step( 'elementor-pro', 'Elementor Pro' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Install_Premium_Plugin_Step( 'wp-rocket', 'WP Rocket' ) );
		$this->runner->register_step( new Mercury_Bootstrapper_Homepage_Step() );
		$this->runner->register_step( new Mercury_Bootstrapper_Security_Step() );
	}

	private function register_hooks(): void {
		add_action( 'admin_menu', array( $this->admin_page, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin_page, 'enqueue_assets' ) );
	}
}
