<?php
/**
 * Pluximo Image Optimizer plugin constants.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PLUXIMO_IMAGE_OPTIMIZER_VERSION' ) ) {
	define( 'PLUXIMO_IMAGE_OPTIMIZER_VERSION', '1.0.0' );
}

if ( ! defined( 'PLUXIMO_IMAGE_OPTIMIZER_FILE' ) ) {
	define( 'PLUXIMO_IMAGE_OPTIMIZER_FILE', __DIR__ . '/pluximo-image-optimizer.php' );
}

if ( ! defined( 'PLUXIMO_IMAGE_OPTIMIZER_DIR' ) ) {
	define( 'PLUXIMO_IMAGE_OPTIMIZER_DIR', __DIR__ );
}

if ( ! defined( 'PLUXIMO_IMAGE_OPTIMIZER_URL' ) ) {
	define( 'PLUXIMO_IMAGE_OPTIMIZER_URL', plugin_dir_url( __FILE__ ) );
}

// Backward compatibility constants.
if ( ! defined( 'PLUXIMO_PNG2WEBP_VERSION' ) ) {
	define( 'PLUXIMO_PNG2WEBP_VERSION', PLUXIMO_IMAGE_OPTIMIZER_VERSION );
}

if ( ! defined( 'PLUXIMO_PNG2WEBP_FILE' ) ) {
	define( 'PLUXIMO_PNG2WEBP_FILE', PLUXIMO_IMAGE_OPTIMIZER_FILE );
}

if ( ! defined( 'PLUXIMO_PNG2WEBP_DIR' ) ) {
	define( 'PLUXIMO_PNG2WEBP_DIR', PLUXIMO_IMAGE_OPTIMIZER_DIR );
}

if ( ! defined( 'PLUXIMO_PNG2WEBP_URL' ) ) {
	define( 'PLUXIMO_PNG2WEBP_URL', PLUXIMO_IMAGE_OPTIMIZER_URL );
}
