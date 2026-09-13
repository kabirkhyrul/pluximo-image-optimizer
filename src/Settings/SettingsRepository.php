<?php
/**
 * Single-option settings store.
 *
 * @package PluximoImageOptimizer
 */

declare( strict_types=1 );

namespace Pluximo\ImageOptimizer\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores every plugin setting inside one autoloaded option so reads resolve
 * from a single database row instead of one row per setting.
 */
final class SettingsRepository {

	/**
	 * Option name holding the settings array.
	 */
	public const OPTION_NAME = 'pluximo_image_optimizer_settings';

	/**
	 * Option name holding the cumulative saved-bytes counter.
	 */
	public const TOTAL_SAVED_OPTION = 'pluximo_image_optimizer_total_saved_bytes';

	/**
	 * Option name holding the cumulative converted-count counter.
	 */
	public const TOTAL_COUNT_OPTION = 'pluximo_image_optimizer_total_converted_count';

	/**
	 * Default settings.
	 *
	 * @var array<string, string|int>
	 */
	private const DEFAULTS = array(
		'auto_convert'         => 1,
		'conversion_timing'    => 'immediate',
		'output_format'        => 'auto',
		'quality'              => 82,
		'keep_backup'          => 1,
		'backup_delete_timing' => 'immediate',
	);

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Per-request cache of the merged settings.
	 *
	 * @var array<string, string|int>|null
	 */
	private ?array $cache = null;

	/**
	 * Singleton accessor.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, string|int>
	 */
	public static function defaults(): array {
		return self::DEFAULTS;
	}

	/**
	 * Get every setting merged with defaults.
	 *
	 * @return array<string, string|int>
	 */
	public function all(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$this->cache = $this->sanitize( array_merge( self::DEFAULTS, $this->resolve_stored_settings() ) );

		return $this->cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback when the key is unknown.
	 *
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$settings = $this->all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Merge values over the stored settings and persist.
	 *
	 * @param array<string, mixed> $values Settings to merge.
	 *
	 * @return array<string, string|int>
	 */
	public function update( array $values ): array {
		return $this->save( array_merge( $this->all(), $values ) );
	}

	/**
	 * Replace the stored settings entirely and persist.
	 *
	 * @param array<string, mixed> $values Settings to store.
	 *
	 * @return array<string, string|int>
	 */
	public function replace( array $values ): array {
		return $this->save( array_merge( self::DEFAULTS, $values ) );
	}

	/**
	 * Restore defaults and persist.
	 *
	 * @return array<string, string|int>
	 */
	public function reset(): array {
		return $this->save( self::DEFAULTS );
	}

	/**
	 * Seed the single option on activation.
	 *
	 * @return void
	 */
	public function install(): void {
		$this->cache = null;

		if ( ! is_array( get_option( self::OPTION_NAME, null ) ) ) {
			update_option( self::OPTION_NAME, self::DEFAULTS, true );
		}

		$this->cache = null;
	}

	/**
	 * Sanitize a raw settings payload.
	 *
	 * @param mixed $value Raw value from REST or the Settings API.
	 *
	 * @return array<string, string|int>
	 */
	public function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'auto_convert'         => $this->to_flag( $value['auto_convert'] ?? self::DEFAULTS['auto_convert'] ),
			'conversion_timing'    => $this->pick( $value['conversion_timing'] ?? '', array( 'immediate', 'cron' ), (string) self::DEFAULTS['conversion_timing'] ),
			'output_format'        => $this->pick( $value['output_format'] ?? '', array( 'auto', 'webp', 'avif' ), (string) self::DEFAULTS['output_format'] ),
			'quality'              => max( 1, min( 100, (int) ( $value['quality'] ?? self::DEFAULTS['quality'] ) ) ),
			'keep_backup'          => $this->to_flag( $value['keep_backup'] ?? self::DEFAULTS['keep_backup'] ),
			'backup_delete_timing' => $this->pick( $value['backup_delete_timing'] ?? '', array( 'immediate', 'cron' ), (string) self::DEFAULTS['backup_delete_timing'] ),
		);
	}

	/**
	 * Persist sanitized settings.
	 *
	 * @param array<string, mixed> $values Settings to persist.
	 *
	 * @return array<string, string|int>
	 */
	private function save( array $values ): array {
		$clean = $this->sanitize( $values );

		update_option( self::OPTION_NAME, $clean, true );
		$this->cache = $clean;

		return $clean;
	}

	/**
	 * Read the stored settings.
	 *
	 * @return array<string, mixed>
	 */
	private function resolve_stored_settings(): array {
		$stored = get_option( self::OPTION_NAME, null );

		return is_array( $stored ) ? $stored : self::DEFAULTS;
	}

	/**
	 * Normalize a boolean-ish value to the stored '1'/'0' flag.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	private function to_flag( $value ): string {
		if ( true === $value || 1 === $value || '1' === $value || 'true' === $value || 'on' === $value ) {
			return '1';
		}

		return '0';
	}

	/**
	 * Pick the first allowed value, falling back when invalid.
	 *
	 * @param mixed    $value    Candidate value.
	 * @param string[] $allowed  Allowed values.
	 * @param string   $fallback Fallback value.
	 *
	 * @return string
	 */
	private function pick( $value, array $allowed, string $fallback ): string {
		$value = is_scalar( $value ) ? strtolower( (string) $value ) : '';

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
