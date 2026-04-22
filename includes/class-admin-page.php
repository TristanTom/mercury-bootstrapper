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

	const MENU_SLUG  = 'mercury-bootstrapper';
	const CAPABILITY = 'manage_options';

	private Mercury_Bootstrapper_Runner $runner;
	private Mercury_Bootstrapper_Premium_Uploads $premium_uploads;

	public function __construct( Mercury_Bootstrapper_Runner $runner, Mercury_Bootstrapper_Premium_Uploads $premium_uploads ) {
		$this->runner          = $runner;
		$this->premium_uploads = $premium_uploads;
	}

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

		wp_enqueue_script(
			'mercury-bootstrapper-setup',
			MERCURY_BOOTSTRAPPER_URL . 'assets/js/setup.js',
			array(),
			MERCURY_BOOTSTRAPPER_VERSION,
			true
		);

		wp_localize_script( 'mercury-bootstrapper-setup', 'mercuryBootstrapper', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => Mercury_Bootstrapper_Runner::AJAX_ACTION,
			'nonce'   => wp_create_nonce( Mercury_Bootstrapper_Runner::NONCE_ACTION ),
			'steps'   => $this->get_step_list_for_js(),
			'upload'  => array(
				'action' => Mercury_Bootstrapper_Premium_Uploads::AJAX_ACTION,
				'nonce'  => wp_create_nonce( Mercury_Bootstrapper_Premium_Uploads::NONCE_ACTION ),
			),
			'i18n'    => array(
				'running'       => __( 'Running…', 'mercury-bootstrapper' ),
				'completed'     => __( 'Completed', 'mercury-bootstrapper' ),
				'failed'        => __( 'Failed', 'mercury-bootstrapper' ),
				'retry'         => __( 'Retry Failed', 'mercury-bootstrapper' ),
				'requestError'  => __( 'Request error', 'mercury-bootstrapper' ),
				'uploading'     => __( 'Uploading…', 'mercury-bootstrapper' ),
				'uploaded'      => __( 'Uploaded', 'mercury-bootstrapper' ),
				'uploadFailed'  => __( 'Upload failed', 'mercury-bootstrapper' ),
			),
		) );
	}

	/** @return array<int, array{id: string, label: string}> */
	private function get_step_list_for_js(): array {
		$list = array();
		foreach ( $this->runner->get_steps() as $step ) {
			$list[] = array(
				'id'    => $step->get_id(),
				'label' => $step->get_label(),
			);
		}
		return $list;
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'mercury-bootstrapper' ) );
		}

		$steps = $this->runner->get_steps();
		?>
		<div class="wrap mercury-bootstrapper">
			<h1><?php esc_html_e( 'Mercury Setup', 'mercury-bootstrapper' ); ?></h1>

			<p class="mercury-bootstrapper__lead">
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'Version %s — press Run Full Setup to execute all registered steps.', 'mercury-bootstrapper' ),
					esc_html( MERCURY_BOOTSTRAPPER_VERSION )
				);
				?>
			</p>

			<h2><?php esc_html_e( 'Premium plugin zip uploads', 'mercury-bootstrapper' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Upload Elementor Pro and WP Rocket zip files before running the setup. If a zip is not uploaded, that step is skipped.', 'mercury-bootstrapper' ); ?>
			</p>
			<ul class="mercury-premium-uploads">
				<?php foreach ( $this->premium_uploads->get_allowed_slugs() as $slug => $label ) :
					$has_upload = null !== Mercury_Bootstrapper_Premium_Uploads::get_stored_path( $slug );
					?>
					<li class="mercury-premium-upload" data-slug="<?php echo esc_attr( $slug ); ?>">
						<span class="mercury-premium-upload__label"><?php echo esc_html( $label ); ?></span>
						<input type="file" accept=".zip,application/zip" class="mercury-premium-upload__input" />
						<button type="button" class="button mercury-premium-upload__button"><?php esc_html_e( 'Upload zip', 'mercury-bootstrapper' ); ?></button>
						<span class="mercury-premium-upload__status<?php echo $has_upload ? ' is-ok' : ''; ?>">
							<?php echo $has_upload ? esc_html__( 'Zip on file — ready to install.', 'mercury-bootstrapper' ) : esc_html__( 'No zip uploaded yet.', 'mercury-bootstrapper' ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="mercury-bootstrapper__actions">
				<button type="button" class="button button-primary button-hero" id="mercury-bootstrapper-run">
					<?php esc_html_e( 'Run Full Setup', 'mercury-bootstrapper' ); ?>
				</button>
				<button type="button" class="button" id="mercury-bootstrapper-retry" hidden>
					<?php esc_html_e( 'Retry Failed', 'mercury-bootstrapper' ); ?>
				</button>
				<span class="mercury-bootstrapper__summary" id="mercury-bootstrapper-summary" aria-live="polite"></span>
			</div>

			<h2><?php esc_html_e( 'Registered steps', 'mercury-bootstrapper' ); ?></h2>
			<ol class="mercury-step-list" id="mercury-step-list">
				<?php foreach ( $steps as $step ) : ?>
					<li class="mercury-step mercury-step--pending" data-step-id="<?php echo esc_attr( $step->get_id() ); ?>">
						<span class="mercury-step__icon" aria-hidden="true">•</span>
						<span class="mercury-step__label"><?php echo esc_html( $step->get_label() ); ?></span>
						<span class="mercury-step__message"></span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php
	}
}
