<?php
/**
 * Support Tickets product services.
 *
 * @package PluximoSupportTickets
 */

declare(strict_types=1);

namespace Pluximo\SupportTickets;

use Pluximo\Foundation\Container;
use Pluximo\Foundation\HookManager;
use Pluximo\Foundation\PluginServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers services and hooks owned by this product.
 */
final class TicketServiceProvider extends PluginServiceProvider {

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

		$this->singleton(
			TicketPostType::class,
			static fn(): TicketPostType => new TicketPostType()
		);

		$this->action(
			'init',
			static function () use ( $container ): void {
				/** @var TicketPostType $post_type */
				$post_type = $container->get( TicketPostType::class );
				$post_type->register();
			}
		);
	}
}
