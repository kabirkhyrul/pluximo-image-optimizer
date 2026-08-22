<?php
/**
 * Ticket Post Type registration.
 *
 * @package PluximoSupportTickets
 */

declare(strict_types=1);

namespace Pluximo\SupportTickets;

use Pluximo\AdminShell\AdminMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TicketPostType {

	/**
	 * Post type identifier.
	 */
	public const POST_TYPE = 'pluximo_ticket';

	/**
	 * Register the private ticket post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Support Tickets', 'pluximo-support-tickets' ),
					'singular_name' => __( 'Support Ticket', 'pluximo-support-tickets' ),
					'menu_name'     => __( 'Support Tickets', 'pluximo-support-tickets' ),
					'edit_item'     => __( 'View Support Ticket', 'pluximo-support-tickets' ),
					'search_items'  => __( 'Search Support Tickets', 'pluximo-support-tickets' ),
					'not_found'     => __( 'No support tickets found.', 'pluximo-support-tickets' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => AdminMenu::MENU_SLUG,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-sos',
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}
}
