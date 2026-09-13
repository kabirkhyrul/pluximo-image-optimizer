<?php
/**
 * REST API controller for plugin settings.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Rest;

use Pluximo\ImageOptimizer\Settings\SettingsRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes CRUD endpoints over the single settings option.
 */
final class SettingsController {

	/**
	 * REST namespace.
	 */
	public const REST_NAMESPACE = 'pluximo-image-optimizer/v1';

	/**
	 * REST route.
	 */
	public const ROUTE = '/settings';

	/**
	 * Settings store.
	 *
	 * @var SettingsRepository
	 */
	private SettingsRepository $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings store.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register the settings CRUD routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_endpoint_args(),
				),
				array(
					'methods'             => 'PUT, PATCH',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_endpoint_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Require the capability that manages plugin settings.
	 *
	 * @return bool
	 */
	public function permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /settings — read all settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response(
			array( 'settings' => $this->settings->all() ),
			200
		);
	}

	/**
	 * POST /settings — replace every setting.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 *
	 * @return WP_REST_Response
	 */
	public function create_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = $this->settings->replace( $this->extract_payload( $request ) );

		return new WP_REST_Response( array( 'settings' => $settings ), 201 );
	}

	/**
	 * PUT|PATCH /settings — merge a partial settings payload.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		$payload = $this->extract_payload( $request );

		if ( array() === $payload ) {
			return new WP_Error(
				'rest_empty_settings',
				__( 'No settings were provided.', 'pluximo-image-optimizer' ),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response(
			array( 'settings' => $this->settings->update( $payload ) ),
			200
		);
	}

	/**
	 * DELETE /settings — reset to defaults.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_settings(): WP_REST_Response {
		return new WP_REST_Response(
			array( 'settings' => $this->settings->reset() ),
			200
		);
	}

	/**
	 * Extract only known setting keys from the request body.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 *
	 * @return array<string, mixed>
	 */
	private function extract_payload( WP_REST_Request $request ): array {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		if ( ! is_array( $params ) ) {
			return array();
		}

		return array_intersect_key( $params, array_flip( array_keys( SettingsRepository::defaults() ) ) );
	}

	/**
	 * Request argument schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_endpoint_args(): array {
		return array(
			'auto_convert'         => array(
				'description' => __( 'Automatically convert new PNG uploads.', 'pluximo-image-optimizer' ),
				'type'        => 'integer',
				'enum'        => array( 0, 1 ),
			),
			'conversion_timing'    => array(
				'description' => __( 'When upload conversions run.', 'pluximo-image-optimizer' ),
				'type'        => 'string',
				'enum'        => array( 'immediate', 'cron' ),
			),
			'output_format'        => array(
				'description' => __( 'Target output format.', 'pluximo-image-optimizer' ),
				'type'        => 'string',
				'enum'        => array( 'auto', 'webp', 'avif' ),
			),
			'quality'              => array(
				'description' => __( 'Compression quality (1-100).', 'pluximo-image-optimizer' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 100,
			),
			'keep_backup'          => array(
				'description' => __( 'Preserve original PNG files.', 'pluximo-image-optimizer' ),
				'type'        => 'integer',
				'enum'        => array( 0, 1 ),
			),
			'backup_delete_timing' => array(
				'description' => __( 'When backup files are removed.', 'pluximo-image-optimizer' ),
				'type'        => 'string',
				'enum'        => array( 'immediate', 'cron' ),
			),
		);
	}
}
