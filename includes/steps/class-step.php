<?php
/**
 * Abstract base class for setup steps.
 *
 * @package Mercury_Bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Mercury_Bootstrapper_Step {

	const STATUS_OK      = 'ok';
	const STATUS_SKIPPED = 'skipped';
	const STATUS_ERROR   = 'error';

	abstract public function get_id(): string;

	abstract public function get_label(): string;

	abstract public function run(): array;

	public function is_done(): bool {
		return false;
	}

	protected function ok( string $message = '' ): array {
		return array(
			'status'  => self::STATUS_OK,
			'message' => $message,
		);
	}

	protected function skipped( string $message = '' ): array {
		return array(
			'status'  => self::STATUS_SKIPPED,
			'message' => $message,
		);
	}

	protected function error( string $message ): array {
		return array(
			'status'  => self::STATUS_ERROR,
			'message' => $message,
		);
	}
}
