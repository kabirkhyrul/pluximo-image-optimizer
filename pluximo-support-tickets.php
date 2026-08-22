<?php
/**
 * Plugin Name: Pluximo Support Tickets
 * Description: Receives Pluximo support API requests and stores them as support tickets.
 * Version: 1.0.0
 * Author: Pluximo
 * Text Domain: pluximo-support-tickets
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package PluximoSupportTickets
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/autoload.php';

use Pluximo\SupportTickets\Plugin;

add_action(
	'plugins_loaded',
	static function (): void {
		$plugin = new Plugin();
		$plugin->boot();
	}
);

