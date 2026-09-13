<?php
/**
 * REST API controller for bulk conversion.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Rest;

use Pluximo\ImageOptimizer\Bulk\BulkConverter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes Media Library scanning and conversion over REST.
 */
final class BulkController {

	/**
	 * Scan route.
	 */
	public const ROUTE_SCAN = '/bulk/scan';

	/**
	 * Single-attachment conversion route.
	 */
	public const ROUTE_PROCESS = '/bulk/process';

	/**
	 * Bulk conversion service.
	 *
	 * @var BulkConverter
	 */
	private BulkConverter $bulk;

	/**
	 * Constructor.
	 *
	 * @param BulkConverter $bulk Bulk conversion service.
	 */
	public function __construct( BulkConverter $bulk ) {
		$this->bulk = $bulk;
	}

	/**
	 * Register the bulk conversion routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			SettingsController::REST_NAMESPACE,
			self::ROUTE_SCAN,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'scan' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			SettingsController::REST_NAMESPACE,
			self::ROUTE_PROCESS,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'process' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'attachment_id' => array(
						'description'       => __( 'Media Library attachment ID to convert.', 'pluximo-image-optimizer' ),
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Require the capability that manages plugin conversions.
	 *
	 * @return bool
	 */
	public function permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * POST /bulk/scan — scan the Media Library.
	 *
	 * @return WP_REST_Response
	 */
	public function scan(): WP_REST_Response {
		return new WP_REST_Response( $this->bulk->scan(), 200 );
	}

	/**
	 * POST /bulk/process — convert one attachment.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function process( WP_REST_Request $request ) {
		return $this->to_response( $this->bulk->convert( (int) $request->get_param( 'attachment_id' ) ) );
	}

	/**
	 * Normalize a service result into a REST response.
	 *
	 * @param array<string, mixed>|WP_Error $result Service result.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	private function to_response( $result ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}
}
