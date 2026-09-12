<?php
/**
 * Main Pluximo Image Optimizer Plugin Bootstrap.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer;

use Pluximo\Foundation\AbstractPluginDefinition;
use Pluximo\Foundation\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates plugin services and hook registration.
 */
final class Plugin extends AbstractPluginDefinition {

	/**
	 * Get this product's runtime metadata.
	 *
	 * @return Product
	 */
	public function get_product(): Product {
		return new Product(
			array(
				'slug'                    => 'pluximo-image-optimizer',
				'name'                    => 'Image Optimizer',
				'version'                 => '1.0.0',
				'plugin_file'             => dirname( __DIR__ ) . '/pluximo-image-optimizer.php',
				'admin_page_slug'         => 'pluximo-image-optimizer',
				'support_context'         => array( 'channel' => 'wordpress' ),
				'enabled_shared_features' => array( 'admin-shell', 'ecosystem', 'support' ),
			)
		);
	}

	/**
	 * Get product-specific service providers.
	 *
	 * @return array
	 */
	public function get_service_providers(): array {
		return array( new ImageOptimizerServiceProvider() );
	}
}
