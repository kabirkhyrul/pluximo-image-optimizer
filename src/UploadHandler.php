<?php
/**
 * Upload hooks and inline conversion interceptor.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer;

use Pluximo\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UploadHandler hooks into WordPress file uploads to automatically convert PNG files to WebP or AVIF.
 */
class UploadHandler {

	/**
	 * Ensure WP_Image_Editor formats PNG output as configured target format when auto-convert is enabled.
	 *
	 * @param array $formats Image format mappings.
	 *
	 * @return array
	 */
	public function filter_image_editor_output_format( array $formats ): array {
		if ( '1' !== get_option( 'png2webp_auto_convert', '1' ) || 'immediate' !== $this->get_conversion_timing() ) {
			return $formats;
		}

		$target_format = $this->get_target_format();
		if ( ConverterEngine::is_format_supported( $target_format ) ) {
			$formats['image/png'] = 'image/' . $target_format;
		}

		return $formats;
	}

	/**
	 * Intercept upload data array before media attachment is created.
	 *
	 * @param array  $upload  Array of upload data containing file, url, and type.
	 * @param string $context The type of upload action ('upload' or 'sideload').
	 *
	 * @return array Modified upload data array.
	 */
	public function handle_upload( array $upload, string $context = 'upload' ): array {
		if ( 'immediate' !== $this->get_conversion_timing() || ! $this->should_convert_upload( $upload ) ) {
			return $upload;
		}

		$target_format = $this->get_target_format();
		if ( ! ConverterEngine::is_format_supported( $target_format ) ) {
			return $upload;
		}

		return $this->process_upload_conversion( $upload, $target_format );
	}

	/**
	 * Queue a newly-created PNG attachment for conversion by WP-Cron.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return void
	 */
	public function schedule_attachment_conversion( int $attachment_id ): void {
		if ( '1' !== get_option( 'png2webp_auto_convert', '1' ) || 'cron' !== $this->get_conversion_timing() ) {
			return;
		}

		$file_path = (string) get_attached_file( $attachment_id );
		if ( '' === $file_path || 'png' !== strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) ) ) {
			return;
		}

		$args = array( $attachment_id );
		if ( ! wp_next_scheduled( 'png2webp_convert_attachment', $args ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'png2webp_convert_attachment', $args );
		}
	}

	/**
	 * Convert a queued PNG attachment and refresh its Media Library metadata.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return void
	 */
	public function convert_scheduled_attachment( int $attachment_id ): void {
		if ( '1' !== get_option( 'png2webp_auto_convert', '1' ) ) {
			return;
		}

		$file_path = (string) get_attached_file( $attachment_id );
		if ( '' === $file_path || ! file_exists( $file_path ) || 'png' !== strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) ) ) {
			return;
		}

		$target_format = $this->get_target_format();
		if ( ! ConverterEngine::is_format_supported( $target_format ) ) {
			return;
		}

		$dest_file = (string) preg_replace( '/\.png$/i', '.' . $target_format, $file_path );
		$result    = ConverterEngine::convert_png_to_webp( $file_path, $dest_file, null, $target_format );
		if ( true !== ( $result['success'] ?? false ) || ! file_exists( $dest_file ) ) {
			return;
		}

		MediaHelper::cleanup_sub_sizes( $file_path, $attachment_id );
		MediaHelper::update_attachment_record( $attachment_id, $dest_file, $target_format );
		$this->record_conversion_stats( $result );
	}

	/**
	 * Execute PNG conversion on upload payload and update properties.
	 *
	 * @param array  $upload        Upload array.
	 * @param string $target_format Output format ('webp' or 'avif').
	 *
	 * @return array
	 */
	private function process_upload_conversion( array $upload, string $target_format ): array {
		$source_file = (string) $upload['file'];
		$dest_file   = (string) preg_replace( '/\.png$/i', '.' . $target_format, $source_file );

		$result = ConverterEngine::convert_png_to_webp( $source_file, $dest_file, null, $target_format );
		if ( true !== ( $result['success'] ?? false ) || ! file_exists( $dest_file ) ) {
			return $upload;
		}

		$upload['file'] = $dest_file;
		$upload['url']  = (string) preg_replace( '/\.png$/i', '.' . $target_format, (string) $upload['url'] );
		$upload['type'] = 'image/' . $target_format;

		$this->record_conversion_stats( $result );

		return $upload;
	}

	/**
	 * Determine if upload array represents a PNG that should be converted.
	 *
	 * @param array $upload Upload data array.
	 *
	 * @return bool
	 */
	private function should_convert_upload( array $upload ): bool {
		if ( '1' !== get_option( 'png2webp_auto_convert', '1' ) ) {
			return false;
		}

		$file = (string) ( $upload['file'] ?? '' );
		$type = (string) ( $upload['type'] ?? '' );

		if ( '' === $file || '' === $type ) {
			return false;
		}

		return $this->is_png_upload( $file, $type );
	}

	/**
	 * Check if file or MIME type corresponds to PNG.
	 *
	 * @param string $file File path.
	 * @param string $type MIME type.
	 *
	 * @return bool
	 */
	private function is_png_upload( string $file, string $type ): bool {
		if ( 'image/png' === strtolower( $type ) ) {
			return true;
		}

		return 'png' === strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	}

	/**
	 * Get normalized target output format.
	 *
	 * @return string
	 */
	private function get_target_format(): string {
		$format = strtolower( (string) get_option( 'png2webp_output_format', 'auto' ) );

		if ( 'auto' === $format ) {
			return ConverterEngine::is_avif_supported() ? 'avif' : 'webp';
		}

		if ( 'avif' === $format ) {
			return 'avif';
		}

		return 'webp';
	}

	/**
	 * Get the automatic upload conversion timing.
	 *
	 * @return string
	 */
	private function get_conversion_timing(): string {
		return 'cron' === get_option( 'png2webp_conversion_timing', 'immediate' ) ? 'cron' : 'immediate';
	}

	/**
	 * Record cumulative conversion statistics options.
	 *
	 * @param array $result Conversion result array.
	 *
	 * @return void
	 */
	private function record_conversion_stats( array $result ): void {
		$saved_bytes = (int) ( $result['saved_bytes'] ?? 0 );
		$total_saved = (int) get_option( 'png2webp_total_saved_bytes', 0 );
		$total_count = (int) get_option( 'png2webp_total_converted_count', 0 );

		update_option( 'png2webp_total_saved_bytes', $total_saved + $saved_bytes );
		update_option( 'png2webp_total_converted_count', $total_count + 1 );

		$logger = new Logger();
		$logger->log( sprintf( 'Upload conversion completed - Saved %s', size_format( $saved_bytes, 2 ) ), 'info' );
	}
}
