<?php
/**
 * Main Pluximo Support Tickets Plugin Bootstrap.
 *
 * @package PluximoSupportTickets
 */

declare(strict_types=1);

namespace Pluximo\SupportTickets;

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
				'slug'                    => 'pluximo-support-tickets',
				'name'                    => 'Pluximo Support Tickets',
				'version'                 => '1.0.0',
				'plugin_file'             => dirname( __DIR__ ) . '/pluximo-support-tickets.php',
				'admin_page_slug'         => 'pluximo-support-tickets',
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
		return array( new TicketServiceProvider() );
	}
}
