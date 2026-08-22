<?php
/**
 * Graphic processing driver dispatcher for WebP and AVIF formats.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer;

use Pluximo\ImageOptimizer\Drivers\DriverInterface;
use Pluximo\ImageOptimizer\Drivers\WebpDriver;
use Pluximo\ImageOptimizer\Drivers\AvifDriver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ImageDrivers delegates format conversion to format-specific driver implementations.
 */
class ImageDrivers {

	/**
	 * Get driver instance for requested format.
	 *
	 * @param string $format Image format ('webp' or 'avif').
	 * @return DriverInterface
	 */
	public static function get_driver( string $format = 'webp' ): DriverInterface {
		if ( 'avif' === strtolower( $format ) ) {
			return new AvifDriver();
		}

		return new WebpDriver();
	}

	/**
	 * Check if conversion driver is available for specified format.
	 *
	 * @param string $format Target image format ('webp' or 'avif').
	 * @return bool
	 */
	public static function is_any_driver_available( string $format = 'webp' ): bool {
		return self::get_driver( $format )->is_available();
	}

	/**
	 * Get human readable name of active graphic engine for specified format.
	 *
	 * @param string $format Target image format ('webp' or 'avif').
	 * @return string
	 */
	public static function get_engine_name( string $format = 'webp' ): string {
		return self::get_driver( $format )->get_engine_name();
	}

	/**
	 * Try conversion using appropriate format driver.
	 *
	 * @param string $source_path   Source file path.
	 * @param string $dest_path     Destination file path.
	 * @param int    $quality       Quality value (1-100).
	 * @param string $error_msg     Reference variable for output error message.
	 * @param string $target_format Target image format ('webp' or 'avif').
	 *
	 * @return bool
	 */
	public static function try_conversion( string $source_path, string $dest_path, int $quality, string &$error_msg, string $target_format = 'webp' ): bool {
		return self::get_driver( $target_format )->convert( $source_path, $dest_path, $quality, $error_msg );
	}

	/**
	 * Convert PNG image using configured driver.
	 *
	 * @param string $source_path   Source file path.
	 * @param string $dest_path     Destination file path.
	 * @param int    $quality       Quality value (1-100).
	 * @param string $target_format Target image format.
	 *
	 * @return bool
	 */
	public static function convert_gd( string $source_path, string $dest_path, int $quality, string $target_format = 'webp' ): bool {
		$error_msg = '';
		return self::get_driver( $target_format )->convert( $source_path, $dest_path, $quality, $error_msg );
	}

	/**
	 * Convert PNG image using Imagick driver.
	 *
	 * @param string $source_path   Source file path.
	 * @param string $dest_path     Destination file path.
	 * @param int    $quality       Quality value (1-100).
	 * @param string $error_msg     Reference variable for output error message.
	 * @param string $target_format Target image format.
	 *
	 * @return bool
	 */
	public static function convert_imagick( string $source_path, string $dest_path, int $quality, string &$error_msg, string $target_format = 'webp' ): bool {
		return self::get_driver( $target_format )->convert( $source_path, $dest_path, $quality, $error_msg );
	}

	/**
	 * Convert PNG image using WP_Image_Editor driver.
	 *
	 * @param string $source_path   Source file path.
	 * @param string $dest_path     Destination file path.
	 * @param int    $quality       Quality value (1-100).
	 * @param string $error_msg     Reference variable for output error message.
	 * @param string $target_format Target image format.
	 *
	 * @return bool
	 */
	public static function convert_wp_editor( string $source_path, string $dest_path, int $quality, string &$error_msg, string $target_format = 'webp' ): bool {
		return self::get_driver( $target_format )->convert( $source_path, $dest_path, $quality, $error_msg );
	}
}
