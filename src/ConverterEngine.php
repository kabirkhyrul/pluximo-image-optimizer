<?php
/**
 * Main conversion engine logic.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer;

use Pluximo\ImageOptimizer\Settings\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ConverterEngine handles conversion processing, source validation, and statistical tracking.
 */
class ConverterEngine {

	/**
	 * Check if WebP conversion is supported by server PHP environment.
	 *
	 * @return bool
	 */
	public static function is_webp_supported(): bool {
		return self::is_format_supported( 'webp' );
	}

	/**
	 * Check if AVIF conversion is supported by server PHP environment.
	 *
	 * @return bool
	 */
	public static function is_avif_supported(): bool {
		return self::is_format_supported( 'avif' );
	}

	/**
	 * Check if specified format conversion is supported by server PHP environment.
	 *
	 * @param string $format Image format ('webp' or 'avif').
	 * @return bool
	 */
	public static function is_format_supported( string $format = 'webp' ): bool {
		return ImageDrivers::is_any_driver_available( $format );
	}

	/**
	 * Get server graphic engine status for specified format.
	 *
	 * @param string $format Target image format ('webp' or 'avif').
	 * @return string
	 */
	public static function get_engine_name( string $format = 'webp' ): string {
		return ImageDrivers::get_engine_name( $format );
	}

	/**
	 * Convert a PNG file to WebP or AVIF format.
	 *
	 * @param string      $source_path   Full filesystem path to the PNG file.
	 * @param string|null $dest_path     Optional destination path for converted file.
	 * @param int|null    $quality       Optional quality rating (1-100).
	 * @param string|null $target_format Optional target format ('webp' or 'avif').
	 *
	 * @return array Result payload with status, file paths, format, and size delta.
	 */
	public static function convert_image( string $source_path, ?string $dest_path = null, ?int $quality = null, ?string $target_format = null ): array {
		$validation_error = self::validate_source_file( $source_path );
		if ( null !== $validation_error ) {
			return array(
				'success' => false,
				'error'   => $validation_error,
			);
		}

		$settings    = SettingsRepository::instance();
		$format      = strtolower( (string) ( $target_format ?? $settings->get( 'output_format', 'webp' ) ) );
		$format      = 'avif' === $format ? 'avif' : 'webp';
		$quality_int = (int) ( $quality ?? $settings->get( 'quality', 82 ) );
		$quality_val = max( 1, min( 100, $quality_int ) );
		$dest_path ??= (string) preg_replace( '/\.png$/i', '.' . $format, $source_path );

		$original_size = (int) filesize( $source_path );
		$error_msg     = '';

		$converted = self::try_conversion( $source_path, $dest_path, $quality_val, $error_msg, $format );

		if ( ! $converted || ! file_exists( $dest_path ) || 0 === filesize( $dest_path ) ) {
			return array(
				'success' => false,
				'error'   => '' !== $error_msg ? $error_msg : sprintf(
					/* translators: %s: Upper-case target format name, e.g. WEBP or AVIF */
					__( 'Failed to convert PNG to %s.', 'pluximo-image-optimizer' ),
					strtoupper( $format )
				),
			);
		}

		$new_size    = (int) filesize( $dest_path );
		$saved_bytes = max( 0, $original_size - $new_size );

		MediaHelper::cleanup_or_backup_source( $source_path, $dest_path );

		return array(
			'success'       => true,
			'source'        => $source_path,
			'destination'   => $dest_path,
			'original_size' => $original_size,
			'new_size'      => $new_size,
			'saved_bytes'   => $saved_bytes,
			'format'        => $format,
		);
	}

	/**
	 * Validate source PNG file existence and extension.
	 *
	 * @param string $source_path Source file path.
	 *
	 * @return string|null Error message or null if valid.
	 */
	private static function validate_source_file( string $source_path ): ?string {
		if ( ! file_exists( $source_path ) ) {
			return __( 'Source PNG file does not exist.', 'pluximo-image-optimizer' );
		}

		return 'png' === strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) ) ? null : __( 'File is not a PNG image.', 'pluximo-image-optimizer' );
	}

	/**
	 * Try conversion using available image processing engine.
	 *
	 * @param string $source_path   Source file path.
	 * @param string $dest_path     Destination file path.
	 * @param int    $quality       Quality rating (1-100).
	 * @param string $error_msg     Reference variable for output error message.
	 * @param string $target_format Target image format ('webp' or 'avif').
	 *
	 * @return bool
	 */
	private static function try_conversion( string $source_path, string $dest_path, int $quality, string &$error_msg, string $target_format = 'webp' ): bool {
		return ImageDrivers::try_conversion( $source_path, $dest_path, $quality, $error_msg, $target_format );
	}
}
