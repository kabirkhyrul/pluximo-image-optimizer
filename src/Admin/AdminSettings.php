<?php
/**
 * Admin settings page renderer and script loader.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Admin;

use Pluximo\AdminShell\View;
use Pluximo\AdminShell\AdminMenu;
use Pluximo\ImageOptimizer\ConverterEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AdminSettings manages the admin options page and asset loading.
 */
class AdminSettings {

	/**
	 * View template renderer.
	 *
	 * @var View
	 */
	private View $view;

	/**
	 * Core Admin Menu instance.
	 *
	 * @var AdminMenu
	 */
	private AdminMenu $admin_menu;

	/**
	 * Constructor.
	 *
	 * @param View      $view       Template renderer.
	 * @param AdminMenu $admin_menu Core Admin Menu service.
	 */
	public function __construct( View $view, AdminMenu $admin_menu ) {
		$this->view       = $view;
		$this->admin_menu = $admin_menu;
	}

	/**
	 * Register plugin admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		$this->admin_menu->register_subpage(
			__( 'PNG to WebP & AVIF Converter', 'pluximo-image-optimizer' ),
			__( 'PNG Optimizer', 'pluximo-image-optimizer' ),
			'png-to-webp-converter',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( 'png2webp_settings_group', 'png2webp_auto_convert' );
		register_setting( 'png2webp_settings_group', 'png2webp_conversion_timing' );
		register_setting( 'png2webp_settings_group', 'png2webp_output_format' );
		register_setting( 'png2webp_settings_group', 'png2webp_quality' );
		register_setting( 'png2webp_settings_group', 'png2webp_keep_backup' );
		register_setting( 'png2webp_settings_group', 'png2webp_backup_delete_timing' );
	}

	/**
	 * Enqueue admin scripts and stylesheets.
	 *
	 * @param string $hook Page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'toplevel_page_pluximo', 'toplevel_page_pluximo-network', 'settings_page_png-to-webp-converter', 'pluximo_page_png-to-webp-converter' ), true ) ) {
			return;
		}

		$plugin_file = dirname( __DIR__, 2 ) . '/pluximo-image-optimizer.php';
		$plugin_url  = plugin_dir_url( $plugin_file );
		$module_dir  = dirname( __DIR__, 2 ) . '/modules/ecosystem';
		$lucide_file = $module_dir . '/assets/js/vendor/lucide.min.js';

		$js_deps = array( 'jquery' );

		if ( file_exists( $lucide_file ) ) {
			$lucide_ver = (string) filemtime( $lucide_file );
			$lucide_url = plugins_url( 'modules/ecosystem/assets/js/vendor/lucide.min.js', $plugin_file );

			if ( ! wp_script_is( 'pluximo-lucide', 'registered' ) ) {
				wp_register_script(
					'pluximo-lucide',
					$lucide_url,
					array(),
					$lucide_ver,
					true
				);
			}

			if ( ! wp_script_is( 'pluximo-core-lucide', 'registered' ) ) {
				wp_register_script(
					'pluximo-core-lucide',
					$lucide_url,
					array(),
					$lucide_ver,
					true
				);
			}

			wp_enqueue_script( 'pluximo-lucide' );
			$js_deps[] = 'pluximo-lucide';
		}

		$css_file = dirname( __DIR__, 2 ) . '/assets/css/admin-style.css';
		$js_file  = dirname( __DIR__, 2 ) . '/assets/js/bulk-converter.js';
		$css_ver  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : '1.0.0';
		$js_ver   = file_exists( $js_file ) ? (string) filemtime( $js_file ) : '1.0.0';

		wp_enqueue_style(
			'png2webp-admin-css',
			$plugin_url . 'assets/css/admin-style.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'png2webp-bulk-js',
			$plugin_url . 'assets/js/bulk-converter.js',
			$js_deps,
			$js_ver,
			true
		);

		wp_localize_script(
			'png2webp-bulk-js',
			'png2webp_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'png2webp_admin_nonce' ),
				'i18n'     => array(
					'scanning'            => __( 'Scanning Media Library for PNG images...', 'pluximo-image-optimizer' ),
					'scanning_btn'        => __( 'Scanning...', 'pluximo-image-optimizer' ),
					'scan_btn'            => __( 'Scan Media Library', 'pluximo-image-optimizer' ),
					'rescan_btn'          => __( 'Re-scan Library', 'pluximo-image-optimizer' ),
					'no_pngs'             => __( 'No unconverted PNG images found in Media Library.', 'pluximo-image-optimizer' ),
					/* translators: %d: Number of PNG images found. */
					'found_pngs'          => __( 'Found %d PNG images to convert.', 'pluximo-image-optimizer' ),
					/* translators: 1: Current image index, 2: Total image count. */
					'processing'          => __( 'Converting image %1$d of %2$d...', 'pluximo-image-optimizer' ),
					/* translators: 1: Processed count, 2: Total count, 3: Percentage completed. */
					'processed_status'    => __( '%1$d of %2$d images processed (%3$d%%)', 'pluximo-image-optimizer' ),
					'paused_status'       => __( 'Conversion paused. Click Resume to continue.', 'pluximo-image-optimizer' ),
					'cancelled_status'    => __( 'Conversion cancelled by user.', 'pluximo-image-optimizer' ),
					'complete'            => __( 'Bulk conversion completed successfully!', 'pluximo-image-optimizer' ),
					'complete_btn'        => __( 'Bulk Conversion Complete', 'pluximo-image-optimizer' ),
					'pause_btn'           => __( 'Pause', 'pluximo-image-optimizer' ),
					'resume_btn'          => __( 'Resume', 'pluximo-image-optimizer' ),
					'cancel_btn'          => __( 'Cancel', 'pluximo-image-optimizer' ),
					'download_log_btn'    => __( 'Download Error Log', 'pluximo-image-optimizer' ),
					/* translators: 1: Processed count, 2: Total count, 3: Image ID, 4: Image filename, 5: Saved size. */
					'converted_msg'       => __( '[%1$d/%2$d] Converted #%3$d (%4$s) - Saved %5$s', 'pluximo-image-optimizer' ),
					/* translators: 1: Processed count, 2: Total count, 3: Image ID, 4: Error message. */
					'error_item_msg'      => __( '[%1$d/%2$d] Error #%3$d: %4$s', 'pluximo-image-optimizer' ),
					/* translators: 1: Processed count, 2: Total count, 3: Image ID. */
					'ajax_error_msg'      => __( '[%1$d/%2$d] AJAX error processing #%3$d', 'pluximo-image-optimizer' ),
					'error'               => __( 'An error occurred during conversion.', 'pluximo-image-optimizer' ),
					'unsaved_changes_msg' => __( 'You have unsaved changes.', 'pluximo-image-optimizer' ),
					'saved_success_msg'   => __( 'Settings saved successfully.', 'pluximo-image-optimizer' ),
				),
			)
		);
	}

	/**
	 * Render settings page template.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		$data = array(
			'auto_convert'         => (string) get_option( 'png2webp_auto_convert', '1' ),
			'conversion_timing'    => (string) get_option( 'png2webp_conversion_timing', 'immediate' ),
			'output_format'        => (string) get_option( 'png2webp_output_format', 'auto' ),
			'quality'              => (int) get_option( 'png2webp_quality', 82 ),
			'keep_backup'          => (string) get_option( 'png2webp_keep_backup', '1' ),
			'backup_delete_timing' => (string) get_option( 'png2webp_backup_delete_timing', 'immediate' ),
			'total_saved'          => (int) get_option( 'png2webp_total_saved_bytes', 0 ),
			'total_count'          => (int) get_option( 'png2webp_total_converted_count', 0 ),
			'is_supported'         => ConverterEngine::is_webp_supported(),
			'engine_name'          => ConverterEngine::get_engine_name( 'webp' ),
			'is_avif_supported'    => ConverterEngine::is_avif_supported(),
			'avif_engine_name'     => ConverterEngine::get_engine_name( 'avif' ),
			'is_writable'          => wp_is_writable( $upload_dir['basedir'] ),
		);

		$this->view->output( 'admin/settings-page', $data );
	}
}
