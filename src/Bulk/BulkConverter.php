<?php
/**
 * Bulk conversion service.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Bulk;

use Pluximo\ImageOptimizer\ConverterEngine;
use Pluximo\ImageOptimizer\MediaHelper;
use Pluximo\ImageOptimizer\Settings\SettingsRepository;
use Pluximo\Logging\Logger;
use WP_Error;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans the Media Library and converts PNG attachments on request.
 */
final class BulkConverter {

	/**
	 * Scan the Media Library for convertible PNG attachments.
	 *
	 * @return array<string, mixed>
	 */
	public function scan(): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/png',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$valid_ids     = array();
		$total_bytes   = 0;
		$skipped_count = 0;

		foreach ( $query->posts as $id ) {
			$attachment_id = (int) $id;
			$file_path     = (string) get_attached_file( $attachment_id );

			if ( '' !== $file_path && file_exists( $file_path ) ) {
				$valid_ids[]  = $attachment_id;
				$total_bytes += (int) filesize( $file_path );
			} else {
				$skipped_count++;
			}
		}

		$estimated_saved_bytes = (int) round( $total_bytes * 0.45 );

		$logger = new Logger();
		$logger->log( sprintf( 'Scanned Media Library: found %d eligible PNG attachments (%s total size)', count( $valid_ids ), size_format( $total_bytes, 2 ) ), 'info' );

		return array(
			'count'                   => count( $valid_ids ),
			'ids'                     => $valid_ids,
			'totalBytes'              => $total_bytes,
			'totalFormatted'          => size_format( $total_bytes, 2 ),
			'estimatedSavedBytes'     => $estimated_saved_bytes,
			'estimatedSavedFormatted' => size_format( $estimated_saved_bytes, 2 ),
			'skippedCount'            => $skipped_count,
			'backupEnabled'           => '1' === SettingsRepository::instance()->get( 'keep_backup', '1' ),
		);
	}

	/**
	 * Convert a single PNG attachment to the configured target format.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function convert( int $attachment_id ) {
		$logger = new Logger();

		if ( $attachment_id <= 0 ) {
			$logger->log( 'Bulk conversion error: invalid attachment ID', 'error' );

			return new WP_Error(
				'pluximo_image_optimizer_invalid_attachment',
				__( 'Invalid attachment ID.', 'pluximo-image-optimizer' ),
				array( 'status' => 400 )
			);
		}

		$file_path = (string) get_attached_file( $attachment_id );
		if ( '' === $file_path || ! file_exists( $file_path ) ) {
			$message = sprintf(
				/* translators: %d: attachment ID */
				__( 'Attachment #%d file not found.', 'pluximo-image-optimizer' ),
				$attachment_id
			);
			$logger->log( $message, 'error' );

			return new WP_Error( 'pluximo_image_optimizer_missing_file', $message, array( 'status' => 404 ) );
		}

		$target_format = strtolower( (string) SettingsRepository::instance()->get( 'output_format', 'auto' ) );
		if ( 'auto' === $target_format ) {
			$target_format = ConverterEngine::is_avif_supported() ? 'avif' : 'webp';
		} else {
			$target_format = 'avif' === $target_format ? 'avif' : 'webp';
		}

		$dest_file = (string) preg_replace( '/\.png$/i', '.' . $target_format, $file_path );
		$result    = ConverterEngine::convert_image( $file_path, $dest_file, null, $target_format );

		if ( ! isset( $result['success'] ) || true !== $result['success'] ) {
			$error = isset( $result['error'] ) ? (string) $result['error'] : __( 'Conversion failed.', 'pluximo-image-optimizer' );
			$logger->log( sprintf( 'Failed converting attachment #%d (%s): %s', $attachment_id, basename( $file_path ), $error ), 'error' );

			return new WP_Error( 'pluximo_image_optimizer_conversion_failed', $error, array( 'status' => 422 ) );
		}

		MediaHelper::cleanup_sub_sizes( $file_path, $attachment_id );
		MediaHelper::update_attachment_record( $attachment_id, $dest_file, $target_format );

		$saved_bytes = isset( $result['saved_bytes'] ) ? (int) $result['saved_bytes'] : 0;
		$total_saved = (int) get_option( SettingsRepository::TOTAL_SAVED_OPTION, 0 );
		$total_count = (int) get_option( SettingsRepository::TOTAL_COUNT_OPTION, 0 );

		update_option( SettingsRepository::TOTAL_SAVED_OPTION, $total_saved + $saved_bytes );
		update_option( SettingsRepository::TOTAL_COUNT_OPTION, $total_count + 1 );

		$logger->log( sprintf( 'Converted attachment #%d (%s -> %s) - Saved %s', $attachment_id, basename( $file_path ), basename( $dest_file ), size_format( $saved_bytes, 2 ) ), 'info' );

		return array(
			'attachmentId'   => $attachment_id,
			'filename'       => basename( $dest_file ),
			'savedBytes'     => $saved_bytes,
			'savedFormatted' => size_format( $saved_bytes, 2 ),
		);
	}
}
