<?php
/**
 * Media library and file cleanup helper routines.
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
 * Handles WordPress media library updates, backups, and file cleanup.
 */
class MediaHelper {

	/**
	 * Remove old PNG sub-size images for an attachment.
	 *
	 * @param string $file_path     Main attachment file path.
	 * @param int    $attachment_id Attachment post ID.
	 *
	 * @return void
	 */
	public static function cleanup_sub_sizes( string $file_path, int $attachment_id ): void {
		$old_meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! isset( $old_meta['sizes'] ) || ! is_array( $old_meta['sizes'] ) ) {
			return;
		}

		$base_dir = dirname( $file_path );
		foreach ( $old_meta['sizes'] as $size_data ) {
			if ( ! isset( $size_data['file'] ) || '' === $size_data['file'] ) {
				continue;
			}

			$old_sub_file = trailingslashit( $base_dir ) . $size_data['file'];
			if ( file_exists( $old_sub_file ) && preg_match( '/\.png$/i', $old_sub_file ) ) {
				@unlink( $old_sub_file );
			}
		}
	}

	/**
	 * Update attachment post record and metadata.
	 *
	 * @param int    $attachment_id Attachment post ID.
	 * @param string $dest_file     Destination WebP/AVIF file path.
	 * @param string $target_format Target format ('webp' or 'avif').
	 *
	 * @return void
	 */
	public static function update_attachment_record( int $attachment_id, string $dest_file, string $target_format = 'webp' ): void {
		update_attached_file( $attachment_id, $dest_file );

		$ext       = strtolower( pathinfo( $dest_file, PATHINFO_EXTENSION ) );
		$mime_type = ( 'avif' === $ext || 'avif' === strtolower( $target_format ) ) ? 'image/avif' : 'image/webp';

		wp_update_post(
			array(
				'ID'             => $attachment_id,
				'post_mime_type' => $mime_type,
			)
		);

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$new_meta = wp_generate_attachment_metadata( $attachment_id, $dest_file );
		wp_update_attachment_metadata( $attachment_id, $new_meta );
	}

	/**
	 * Handle original PNG backup or removal after successful conversion.
	 *
	 * @param string $source_path Source file path.
	 * @param string $dest_path   Destination file path.
	 *
	 * @return void
	 */
	public static function cleanup_or_backup_source( string $source_path, string $dest_path ): void {
		if ( '1' === SettingsRepository::instance()->get( 'keep_backup', '0' ) ) {
			self::backup_original_png( $source_path );
			return;
		}

		if ( $source_path !== $dest_path && file_exists( $source_path ) ) {
			@unlink( $source_path );
		}
	}

	/**
	 * Copy original PNG into a backup folder before removal.
	 *
	 * @param string $source_path Source file path.
	 *
	 * @return void
	 */
	private static function backup_original_png( string $source_path ): void {
		$upload_dir = wp_upload_dir();
		$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'pluximo-image-optimizer-backups/';
		if ( ! file_exists( $backup_dir ) ) {
			wp_mkdir_p( $backup_dir );
		}

		@copy( $source_path, $backup_dir . time() . '_' . basename( $source_path ) );
	}

	/**
	 * Delete any backup PNGs associated with the given attachment when it is removed.
	 *
	 * @param int $attachment_id Attachment post ID being deleted.
	 *
	 * @return void
	 */
	public static function delete_backup_on_attachment_delete( int $attachment_id ): void {
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'pluximo-image-optimizer-backups/';
		if ( ! is_dir( $backup_dir ) ) {
			return;
		}

		$basename      = basename( $file_path );
		$filename_stem = pathinfo( $file_path, PATHINFO_FILENAME );

		// Backups are stored with a timestamp prefix, e.g. 1630456789_original.png
		$patterns = array(
			$backup_dir . '*_' . $basename,
			$backup_dir . '*_' . $filename_stem . '.*',
		);

		$backup_files = array();
		foreach ( $patterns as $pattern ) {
			$matches = glob( $pattern );
			if ( ! is_array( $matches ) ) {
				continue;
			}

			$backup_files = array_merge( $backup_files, $matches );
		}

		$backup_files = array_values( array_unique( $backup_files ) );
		if ( array() === $backup_files ) {
			return;
		}

		if ( 'cron' === SettingsRepository::instance()->get( 'backup_delete_timing', 'immediate' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'pluximo_image_optimizer_delete_backup_files', array( $backup_files ) );
			return;
		}

		self::delete_backup_files( $backup_files );
	}

	/**
	 * Delete backup files collected before an attachment record was removed.
	 *
	 * @param array $backup_files List of file paths to delete.
	 *
	 * @return void
	 */
	public static function delete_backup_files( array $backup_files ): void {
		$upload_dir = wp_upload_dir();
		$backup_dir = str_replace( '\\', '/', trailingslashit( $upload_dir['basedir'] ) . 'pluximo-image-optimizer-backups/' );

		foreach ( $backup_files as $backup_file ) {
			$backup_file = str_replace( '\\', '/', (string) $backup_file );
			if ( 0 !== strpos( $backup_file, $backup_dir ) || ! is_file( $backup_file ) ) {
				continue;
			}
			@unlink( $backup_file );
		}
	}
}
