<?php
/**
 * Plugin Name: Pluximo Image Optimizer
 * Description: Automatically convert PNG images to WebP or AVIF, preserve transparency, optimize quality, keep optional backups, and bulk-convert existing Media Library images.
 * Version: 1.0.0
 * Author: Pluximo
 * Text Domain: pluximo-image-optimizer
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/constants.php';

use Pluximo\ImageOptimizer\Plugin;

register_activation_hook(
	__FILE__,
	static function (): void {
		if ( false === get_option( 'png2webp_auto_convert' ) ) {
			update_option( 'png2webp_auto_convert', '1' );
		}
		if ( false === get_option( 'png2webp_conversion_timing' ) ) {
			update_option( 'png2webp_conversion_timing', 'immediate' );
		}
		if ( false === get_option( 'png2webp_output_format' ) ) {
			update_option( 'png2webp_output_format', 'auto' );
		}
		if ( false === get_option( 'png2webp_quality' ) ) {
			update_option( 'png2webp_quality', 82 );
		}
		if ( false === get_option( 'png2webp_keep_backup' ) ) {
			update_option( 'png2webp_keep_backup', '1' );
		}
		if ( false === get_option( 'png2webp_backup_delete_timing' ) ) {
			update_option( 'png2webp_backup_delete_timing', 'immediate' );
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'pluximo-image-optimizer',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
		$plugin = new Plugin();
		$plugin->boot();
	}
);
