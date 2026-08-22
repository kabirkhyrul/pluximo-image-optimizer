<?php
/**
 * Namespace map for the plugin and its modular packages.
 *
 * @package PluximoSupportTickets
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( Pluximo\Foundation\Autoloader::class, false ) ) {
	require_once __DIR__ . '/modules/foundation/src/Autoloader.php';
}

Pluximo\Foundation\Autoloader::register(
	array(
		'Pluximo\\SupportTickets\\' => __DIR__ . '/src/',
		'Pluximo\\Foundation\\'     => __DIR__ . '/modules/foundation/src/',
		'Pluximo\\AdminShell\\'     => __DIR__ . '/modules/admin-shell/src/',
		'Pluximo\\Ecosystem\\'      => __DIR__ . '/modules/ecosystem/src/',
		'Pluximo\\Support\\'        => __DIR__ . '/modules/support/src/',
	)
);
