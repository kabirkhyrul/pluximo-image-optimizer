<?php
/**
 * AVIF conversion driver implementation.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Drivers;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles graphic processing drivers specifically for AVIF format.
 */
class AvifDriver implements DriverInterface {

	/**
	 * Check if AVIF conversion is supported by server environment.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		if ( function_exists( 'imageavif' ) ) {
			return true;
		}

		if ( $this->has_imagick_avif_support() ) {
			return true;
		}

		return function_exists( 'wp_image_editor_supports' ) && wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) );
	}

	/**
	 * Check if Imagick extension supports AVIF format.
	 *
	 * @return bool
	 */
	private function has_imagick_avif_support(): bool {
		if ( ! class_exists( 'Imagick' ) || ! method_exists( 'Imagick', 'queryFormats' ) ) {
			return false;
		}

		try {
			$formats = \Imagick::queryFormats( 'AVIF' );
			return array() !== $formats;
		} catch ( Exception $e ) {
			unset( $e );
			return false;
		}
	}

	/**
	 * Get human readable name of active graphic engine for AVIF.
	 *
	 * @return string
	 */
	public function get_engine_name(): string {
		if ( function_exists( 'imageavif' ) ) {
			return __( 'GD Graphics Library', 'pluximo-image-optimizer' );
		}

		if ( $this->has_imagick_avif_support() ) {
			return __( 'ImageMagick (Imagick)', 'pluximo-image-optimizer' );
		}

		return __( 'WordPress WP_Image_Editor', 'pluximo-image-optimizer' );
	}

	/**
	 * Convert image file to AVIF format.
	 *
	 * @param string $source_path Source file path.
	 * @param string $dest_path   Destination file path.
	 * @param int    $quality     Quality value (1-100).
	 * @param string $error_msg   Reference variable for error output.
	 *
	 * @return bool
	 */
	public function convert( string $source_path, string $dest_path, int $quality, string &$error_msg ): bool {
		if ( function_exists( 'imageavif' ) ) {
			return $this->convert_gd( $source_path, $dest_path, $quality );
		}

		if ( class_exists( 'Imagick' ) ) {
			return $this->convert_imagick( $source_path, $dest_path, $quality, $error_msg );
		}

		return $this->convert_wp_editor( $source_path, $dest_path, $quality, $error_msg );
	}

	/**
	 * Convert to AVIF using GD library.
	 *
	 * @param string $source_path Source file path.
	 * @param string $dest_path   Destination file path.
	 * @param int    $quality     Quality value (1-100).
	 *
	 * @return bool
	 */
	private function convert_gd( string $source_path, string $dest_path, int $quality ): bool {
		$image = @imagecreatefrompng( $source_path );
		if ( false === $image || ! $image ) {
			return false;
		}

		imagealphablending( $image, false );
		imagesavealpha( $image, true );
		$converted = @imageavif( $image, $dest_path, $quality );
		imagedestroy( $image );

		return (bool) $converted;
	}

	/**
	 * Convert to AVIF using Imagick.
	 *
	 * @param string $source_path Source file path.
	 * @param string $dest_path   Destination file path.
	 * @param int    $quality     Quality value (1-100).
	 * @param string $error_msg   Reference variable for error output.
	 *
	 * @return bool
	 */
	private function convert_imagick( string $source_path, string $dest_path, int $quality, string &$error_msg ): bool {
		try {
			$imagick = new \Imagick( $source_path );
			$imagick->setImageFormat( 'avif' );
			$imagick->setImageCompressionQuality( $quality );
			$converted = $imagick->writeImage( $dest_path );
			$imagick->clear();
			$imagick->destroy();
			return (bool) $converted;
		} catch ( Exception $e ) {
			$error_msg = $e->getMessage();
			return false;
		}
	}

	/**
	 * Convert to AVIF using WP_Image_Editor.
	 *
	 * @param string $source_path Source file path.
	 * @param string $dest_path   Destination file path.
	 * @param int    $quality     Quality value (1-100).
	 * @param string $error_msg   Reference variable for error output.
	 *
	 * @return bool
	 */
	private function convert_wp_editor( string $source_path, string $dest_path, int $quality, string &$error_msg ): bool {
		$editor = wp_get_image_editor( $source_path );
		if ( is_wp_error( $editor ) ) {
			$error_msg = $editor->get_error_message();
			return false;
		}

		$editor->set_quality( $quality );
		$saved = $editor->save( $dest_path, 'image/avif' );
		if ( is_wp_error( $saved ) ) {
			$error_msg = $saved->get_error_message();
			return false;
		}

		return true;
	}
}
