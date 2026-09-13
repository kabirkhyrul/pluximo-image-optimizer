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
use Pluximo\ImageOptimizer\Settings\SettingsRepository;

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
	 * Single-option settings store.
	 *
	 * @var SettingsRepository
	 */
	private SettingsRepository $settings;

	/**
	 * Constructor.
	 *
	 * @param View               $view       Template renderer.
	 * @param AdminMenu          $admin_menu Core Admin Menu service.
	 * @param SettingsRepository $settings   Settings store.
	 */
	public function __construct( View $view, AdminMenu $admin_menu, SettingsRepository $settings ) {
		$this->view       = $view;
		$this->admin_menu = $admin_menu;
		$this->settings   = $settings;
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
			'pluximo-image-optimizer',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue the React admin application.
	 *
	 * @param string $hook Page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'toplevel_page_pluximo', 'toplevel_page_pluximo-network', 'settings_page_pluximo-image-optimizer', 'pluximo_page_pluximo-image-optimizer' ), true ) ) {
			return;
		}

		$dev_server = $this->get_dev_server_url();
		if ( '' !== $dev_server ) {
			$this->enqueue_dev_assets( $dev_server );
			return;
		}

		$plugin_file = dirname( __DIR__, 2 ) . '/pluximo-image-optimizer.php';
		$plugin_url  = plugin_dir_url( $plugin_file );
		$assets_dir  = dirname( __DIR__, 2 ) . '/assets/admin';

		$handle  = 'pluximo-image-optimizer-admin-app';
		$js_file = $assets_dir . '/index.js';

		if ( ! file_exists( $js_file ) ) {
			return;
		}

		wp_enqueue_script(
			$handle,
			$plugin_url . 'assets/admin/index.js',
			array(),
			(string) filemtime( $js_file ),
			true
		);

		$css_file = $assets_dir . '/style.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				$handle,
				$plugin_url . 'assets/admin/style.css',
				array(),
				(string) filemtime( $css_file )
			);
		}

		wp_localize_script( $handle, 'pluximoImageOptimizer', $this->get_admin_data() );
	}

	/**
	 * Resolve the Vite dev server URL when one is configured.
	 *
	 * Define PLUXIMO_IMAGE_OPTIMIZER_VITE_DEV in wp-config.php (for example
	 * 'https://localhost:5173') to load the admin app from the Vite dev server.
	 *
	 * @return string Dev server URL, or an empty string when disabled.
	 */
	private function get_dev_server_url(): string {
		if ( ! defined( 'PLUXIMO_IMAGE_OPTIMIZER_VITE_DEV' ) ) {
			return '';
		}

		$url = (string) apply_filters( 'pluximo_image_optimizer_vite_dev_url', (string) PLUXIMO_IMAGE_OPTIMIZER_VITE_DEV );

		return '' === $url ? '' : untrailingslashit( $url );
	}

	/**
	 * Enqueue the Vite dev server client and entry as ES modules.
	 *
	 * @param string $dev_server Dev server base URL.
	 *
	 * @return void
	 */
	private function enqueue_dev_assets( string $dev_server ): void {
		$client_handle = 'pluximo-image-optimizer-admin-app';
		$entry_handle  = 'pluximo-image-optimizer-admin-app-entry';

		wp_enqueue_script( $client_handle, $dev_server . '/@vite/client', array(), null, true );
		wp_enqueue_script( $entry_handle, $dev_server . '/src/image-admin/main.jsx', array(), null, true );

		wp_localize_script( $entry_handle, 'pluximoImageOptimizer', $this->get_admin_data() );

		add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 2 );
	}

	/**
	 * Add type="module" to the Vite dev server scripts.
	 *
	 * @param string $tag    Script tag markup.
	 * @param string $handle Script handle.
	 *
	 * @return string
	 */
	public function add_module_type( string $tag, string $handle ): string {
		if ( ! in_array( $handle, array( 'pluximo-image-optimizer-admin-app', 'pluximo-image-optimizer-admin-app-entry' ), true ) ) {
			return $tag;
		}

		return str_replace( '<script ', '<script type="module" ', $tag );
	}

	/**
	 * Build the localized payload consumed by the React application.
	 *
	 * @return array<string, mixed>
	 */
	private function get_admin_data(): array {
		$upload_dir  = wp_upload_dir();
		$total_count = (int) get_option( SettingsRepository::TOTAL_COUNT_OPTION, 0 );
		$total_saved = (int) get_option( SettingsRepository::TOTAL_SAVED_OPTION, 0 );

		return array(
			'restUrl'   => esc_url_raw( rest_url( 'pluximo-image-optimizer/v1/' ) ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'settings'  => $this->settings->all(),
			'stats'    => array(
				'totalCount'          => $total_count,
				'totalCountFormatted' => number_format_i18n( $total_count ),
				'totalSaved'          => $total_saved,
				'totalSavedFormatted' => size_format( $total_saved, 2 ),
			),
			'server'   => array(
				'isSupported'     => ConverterEngine::is_webp_supported(),
				'isWritable'      => wp_is_writable( $upload_dir['basedir'] ),
				'isAvifSupported' => ConverterEngine::is_avif_supported(),
				'engineName'      => ConverterEngine::get_engine_name( 'webp' ),
				'avifEngineName'  => ConverterEngine::get_engine_name( 'avif' ),
				'phpVersion'      => PHP_VERSION,
			),
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
				'error'               => __( 'An error occurred during conversion.', 'pluximo-image-optimizer' ),
				'unsaved_changes_msg' => __( 'You have unsaved changes.', 'pluximo-image-optimizer' ),
				'saved_success_msg'   => __( 'Settings saved successfully.', 'pluximo-image-optimizer' ),
			),
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

		$this->view->output( 'admin/settings-page' );
	}
}
