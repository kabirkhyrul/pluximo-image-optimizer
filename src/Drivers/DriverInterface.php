<?php
/**
 * Driver interface for format conversion engines.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Drivers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface DriverInterface
 */
interface DriverInterface {

	/**
	 * Check if format conversion is supported by server environment.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Get human readable name of active graphic engine.
	 *
	 * @return string
	 */
	public function get_engine_name(): string;

	/**
	 * Convert source image file to target format.
	 *
	 * @param string $source_path Source file path.
	 * @param string $dest_path   Destination file path.
	 * @param int    $quality     Quality value (1-100).
	 * @param string $error_msg   Reference variable for error output.
	 *
	 * @return bool
	 */
	public function convert( string $source_path, string $dest_path, int $quality, string &$error_msg ): bool;
}
