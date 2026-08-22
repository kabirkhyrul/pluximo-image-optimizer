<?php
/**
 * Image Optimizer Service Provider.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer;

use Pluximo\AdminShell\AdminMenu;
use Pluximo\AdminShell\View;
use Pluximo\Foundation\Container;
use Pluximo\Foundation\HookManager;
use Pluximo\Foundation\PluginServiceProvider;
use Pluximo\ImageOptimizer\Admin\AdminSettings;
use Pluximo\ImageOptimizer\Admin\BulkConverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers services and hooks owned by Pluximo Image Optimizer.
 */
final class ImageOptimizerServiceProvider extends PluginServiceProvider {

	/**
	 * Register product services and hooks.
	 *
	 * @param Container   $container Shared dependency container.
	 * @param HookManager $hooks     Shared hook manager.
	 *
	 * @return void
	 */
	public function register( Container $container, HookManager $hooks ): void {
		$this->initialize( $container, $hooks );

		$view = new View( dirname( __DIR__ ) . '/templates' );

		$this->singleton(
			AdminSettings::class,
			static fn( Container $c ): AdminSettings => new AdminSettings(
				$view,
				$c->get( AdminMenu::class )
			)
		);

		$this->singleton(
			BulkConverter::class,
			static fn(): BulkConverter => new BulkConverter()
		);

		$this->singleton(
			UploadHandler::class,
			static fn(): UploadHandler => new UploadHandler()
		);

		$this->action(
			'admin_menu',
			static function () use ( $container ): void {
				/** @var AdminSettings $settings */
				$settings = $container->get( AdminSettings::class );
				$settings->add_admin_menu();
			}
		);

		$this->action(
			'admin_enqueue_scripts',
			static function ( string $hook ) use ( $container ): void {
				/** @var AdminSettings $settings */
				$settings = $container->get( AdminSettings::class );
				$settings->enqueue_admin_assets( $hook );
			}
		);

		$this->action(
			'admin_init',
			static function () use ( $container ): void {
				/** @var AdminSettings $settings */
				$settings = $container->get( AdminSettings::class );
				$settings->register_settings();
			}
		);

		$this->action(
			'wp_ajax_png2webp_bulk_scan',
			static function () use ( $container ): void {
				/** @var BulkConverter $bulk */
				$bulk = $container->get( BulkConverter::class );
				$bulk->ajax_bulk_scan();
			}
		);

		$this->action(
			'wp_ajax_png2webp_bulk_process_item',
			static function () use ( $container ): void {
				/** @var BulkConverter $bulk */
				$bulk = $container->get( BulkConverter::class );
				$bulk->ajax_bulk_process_item();
			}
		);

		$this->filter(
			'wp_handle_upload',
			static function ( array $upload, string $context = 'upload' ) use ( $container ): array {
				/** @var UploadHandler $uploader */
				$uploader = $container->get( UploadHandler::class );
				return $uploader->handle_upload( $upload, $context );
			},
			10,
			2
		);

		$this->filter(
			'wp_handle_sideload',
			static function ( array $upload, string $context = 'upload' ) use ( $container ): array {
				/** @var UploadHandler $uploader */
				$uploader = $container->get( UploadHandler::class );
				return $uploader->handle_upload( $upload, $context );
			},
			10,
			2
		);

		$this->filter(
			'image_editor_output_format',
			static function ( array $formats ) use ( $container ): array {
				/** @var UploadHandler $uploader */
				$uploader = $container->get( UploadHandler::class );
				return $uploader->filter_image_editor_output_format( $formats );
			}
		);

		$this->action(
			'add_attachment',
			static function ( int $attachment_id ) use ( $container ): void {
				/** @var UploadHandler $uploader */
				$uploader = $container->get( UploadHandler::class );
				$uploader->schedule_attachment_conversion( $attachment_id );
			}
		);

		$this->action(
			'png2webp_convert_attachment',
			static function ( int $attachment_id ) use ( $container ): void {
				/** @var UploadHandler $uploader */
				$uploader = $container->get( UploadHandler::class );
				$uploader->convert_scheduled_attachment( $attachment_id );
			}
		);

		$this->action(
			'delete_attachment',
			array( MediaHelper::class, 'delete_backup_on_attachment_delete' )
		);

		$this->action(
			'png2webp_delete_backup_files',
			array( MediaHelper::class, 'delete_backup_files' )
		);
	}
}
