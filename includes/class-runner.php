<?php
/**
 * Step registry + AJAX runner.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercury_Bootstrapper_Runner {

	const AJAX_ACTION   = 'mercury_bootstrapper_run_step';
	const NONCE_ACTION  = 'mercury_bootstrapper_run';

	/** @var array<string, Mercury_Bootstrapper_Step> */
	private array $steps = array();

	public function register_step( Mercury_Bootstrapper_Step $step ): void {
		$this->steps[ $step->get_id() ] = $step;
	}

	/** @return array<string, Mercury_Bootstrapper_Step> */
	public function get_steps(): array {
		return $this->steps;
	}

	public function register_hooks(): void {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_run_step' ) );
	}

	public function handle_run_step(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to run setup steps.', 'mercury-bootstrapper' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$step_id = isset( $_POST['step_id'] )
			? sanitize_key( wp_unslash( $_POST['step_id'] ) )
			: '';

		if ( ! isset( $this->steps[ $step_id ] ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: step id */
						__( 'Unknown step: %s', 'mercury-bootstrapper' ),
						$step_id
					),
				),
				400
			);
		}

		$step = $this->steps[ $step_id ];

		if ( $step->is_done() ) {
			wp_send_json_success( array(
				'status'  => Mercury_Bootstrapper_Step::STATUS_SKIPPED,
				'label'   => $step->get_label(),
				'message' => __( 'Already done — skipped.', 'mercury-bootstrapper' ),
			) );
		}

		try {
			$result = $step->run();
		} catch ( \Throwable $e ) {
			wp_send_json_success( array(
				'status'  => Mercury_Bootstrapper_Step::STATUS_ERROR,
				'label'   => $step->get_label(),
				'message' => $e->getMessage(),
			) );
		}

		wp_send_json_success( array_merge(
			array( 'label' => $step->get_label() ),
			$result
		) );
	}
}
