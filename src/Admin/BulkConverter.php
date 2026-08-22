<?php
/**
 * Bulk converter AJAX handler.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Admin;

use WP_Query;
use Pluximo\Logging\Logger;
use Pluximo\ImageOptimizer\ConverterEngine;
use Pluximo\ImageOptimizer\MediaHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BulkConverter processes bulk conversion AJAX calls from the admin panel.
 */
class BulkConverter {

	/**
	 * Verify AJAX nonce and user permissions.
	 *
	 * @return void
	 */
	private function verify_ajax_request(): void {
		check_ajax_referer( 'png2webp_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pluximo-image-optimizer' ) ) );
		}
	}

	/**
	 * AJAX endpoint to scan media library for existing PNG attachments.
	 *
	 * @return void
	 */
	public function ajax_bulk_scan(): void {
		$this->verify_ajax_request();

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image/png',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		$ids   = $query->posts;

		$valid_ids     = array();
		$total_bytes   = 0;
		$skipped_count = 0;

		foreach ( $ids as $id ) {
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
		$keep_backup           = '1' === get_option( 'png2webp_keep_backup', '1' );

		$logger = new Logger();
		$logger->log( sprintf( 'Scanned Media Library: found %d eligible PNG attachments (%s total size)', count( $valid_ids ), size_format( $total_bytes, 2 ) ), 'info' );

		wp_send_json_success(
			array(
				'count'                  => count( $valid_ids ),
				'ids'                    => $valid_ids,
				'total_bytes'            => $total_bytes,
				'total_formatted'        => size_format( $total_bytes, 2 ),
				'estimated_saved_bytes'  => $estimated_saved_bytes,
				'estimated_saved_format' => size_format( $estimated_saved_bytes, 2 ),
				'skipped_count'          => $skipped_count,
				'backup_enabled'         => $keep_backup,
			)
		);
	}

	/**
	 * AJAX endpoint to convert a single PNG attachment to WebP.
	 *
	 * @return void
	 */
	public function ajax_bulk_process_item(): void {
		$this->verify_ajax_request();

		$logger        = new Logger();
		$attachment_id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
		if ( 0 === $attachment_id ) {
			$logger->log( 'Bulk conversion error: invalid attachment ID', 'error' );
			wp_send_json_error( array( 'message' => __( 'Invalid attachment ID.', 'pluximo-image-optimizer' ) ) );
		}

		$file_path = (string) get_attached_file( $attachment_id );
		if ( '' === $file_path || ! file_exists( $file_path ) ) {
			$err_msg = sprintf(
				/* translators: %d: attachment ID */
				__( 'Attachment #%d file not found.', 'pluximo-image-optimizer' ),
				$attachment_id
			);
			$logger->log( $err_msg, 'error' );
			wp_send_json_error( array( 'message' => $err_msg ) );
		}

		$target_format = strtolower( (string) get_option( 'png2webp_output_format', 'auto' ) );
		if ( 'auto' === $target_format ) {
			$target_format = ConverterEngine::is_avif_supported() ? 'avif' : 'webp';
		} else {
			$target_format = 'avif' === $target_format ? 'avif' : 'webp';
		}

		$dest_file = (string) preg_replace( '/\.png$/i', '.' . $target_format, $file_path );
		$result    = ConverterEngine::convert_png_to_webp( $file_path, $dest_file, null, $target_format );

		if ( ! isset( $result['success'] ) || true !== $result['success'] ) {
			$err = isset( $result['error'] ) ? (string) $result['error'] : __( 'Conversion failed.', 'pluximo-image-optimizer' );
			$logger->log( sprintf( 'Failed converting attachment #%d (%s): %s', $attachment_id, basename( $file_path ), $err ), 'error' );
			wp_send_json_error( array( 'message' => $err ) );
		}

		MediaHelper::cleanup_sub_sizes( $file_path, $attachment_id );
		MediaHelper::update_attachment_record( $attachment_id, $dest_file, $target_format );

		$saved_bytes = isset( $result['saved_bytes'] ) ? (int) $result['saved_bytes'] : 0;
		$total_saved = (int) get_option( 'png2webp_total_saved_bytes', 0 );
		$total_count = (int) get_option( 'png2webp_total_converted_count', 0 );

		update_option( 'png2webp_total_saved_bytes', $total_saved + $saved_bytes );
		update_option( 'png2webp_total_converted_count', $total_count + 1 );

		$logger->log( sprintf( 'Converted attachment #%d (%s -> %s) - Saved %s', $attachment_id, basename( $file_path ), basename( $dest_file ), size_format( $saved_bytes, 2 ) ), 'info' );

		wp_send_json_success(
			array(
				'attachment_id'   => $attachment_id,
				'filename'        => basename( $dest_file ),
				'saved_bytes'     => $saved_bytes,
				'saved_formatted' => size_format( $saved_bytes, 2 ),
			)
		);
	}
}
