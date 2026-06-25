<?php

namespace WPMailSMTP\Abilities;

use DateTime;
use DateTimeZone;
use WP_Error;

/**
 * Default implementation that every concrete ability extends.
 *
 * Provides:
 * - A `manage_options` permission check (Pro log/stats abilities override it).
 * - Read-only annotation defaults.
 * - REST + MCP exposure defaults.
 * - Reusable input-schema fragments (limit, offset, status, date).
 * - Input normalization and pagination clamping.
 *
 * @since 4.9.0
 */
abstract class AbstractAbility implements AbilityInterface {

	/**
	 * Read-only annotations applied to every ability.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {

		return [
			'readonly'    => true,
			'destructive' => false,
			'idempotent'  => true,
		];
	}

	/**
	 * Expose abilities via the REST API by default.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function show_in_rest() {

		return true;
	}

	/**
	 * Mark abilities as MCP-public by default.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_mcp_public() {

		return true;
	}

	/**
	 * Permission gate: viewer must be able to manage plugin options.
	 *
	 * Pro log/stats abilities override this with the email-log view capability.
	 *
	 * @since 4.9.0
	 *
	 * @return true|WP_Error
	 */
	public function check_permission() {

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			return $this->forbidden();
		}

		return true;
	}

	/**
	 * Normalize raw ability input to an array.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $input Raw input (array, object, or null).
	 *
	 * @return array
	 */
	protected function normalize_input( $input ) {

		if ( is_array( $input ) ) {
			return $input;
		}

		if ( is_object( $input ) ) {
			return (array) $input;
		}

		return [];
	}

	/**
	 * Clamp a requested page size to the supported 1-100 range.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $limit Requested limit.
	 *
	 * @return int
	 */
	protected function clamp_limit( $limit ) {

		$limit = absint( $limit );

		if ( $limit < 1 ) {
			$limit = 1;
		}

		if ( $limit > 100 ) {
			$limit = 100;
		}

		return $limit;
	}

	/**
	 * Resolve a clamped `limit` and a non-negative `offset` from input.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args Normalized input.
	 *
	 * @return array{limit: int, offset: int}
	 */
	protected function get_pagination( array $args ) {

		return [
			'limit'  => $this->clamp_limit( $args['limit'] ?? 20 ),
			'offset' => max( 0, absint( $args['offset'] ?? 0 ) ),
		];
	}

	/**
	 * Convert a DateTime to an ISO 8601 UTC string.
	 *
	 * @since 4.9.0
	 *
	 * @param DateTime $datetime Date to format.
	 *
	 * @return string
	 */
	protected function to_iso8601( DateTime $datetime ) {

		// Clone before retiming so the caller's DateTime keeps its own timezone;
		// formatting on a UTC copy guarantees the `+00:00` offset the docblock promises.
		$datetime = clone $datetime;

		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		return $datetime->format( 'c' );
	}

	/**
	 * Build the shared 403 error returned by permission callbacks.
	 *
	 * @since 4.9.0
	 *
	 * @return WP_Error
	 */
	protected function forbidden() {

		return new WP_Error(
			'wp_mail_smtp_forbidden',
			esc_html__( 'You do not have permission to access this data.', 'wp-mail-smtp' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Shared `limit` input-schema fragment.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function limit_schema() {

		return [
			'description' => esc_html__( 'Maximum number of records to return.', 'wp-mail-smtp' ),
			'type'        => 'integer',
			'minimum'     => 1,
			'maximum'     => 100,
			'default'     => 20,
		];
	}

	/**
	 * Shared `offset` input-schema fragment.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function offset_schema() {

		return [
			'description' => esc_html__( 'Number of records to skip.', 'wp-mail-smtp' ),
			'type'        => 'integer',
			'minimum'     => 0,
			'default'     => 0,
		];
	}

	/**
	 * Shared date input-schema fragment.
	 *
	 * @since 4.9.0
	 *
	 * @param string $description Field description.
	 *
	 * @return array
	 */
	protected function date_schema( $description ) {

		return [
			'description' => $description,
			'type'        => 'string',
			'format'      => 'date',
		];
	}
}
