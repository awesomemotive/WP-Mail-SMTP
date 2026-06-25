<?php

namespace WPMailSMTP\Admin\EmailSendingErrors;

use WPMailSMTP\Helpers\Helpers;

/**
 * Plugin-side mirror of the product-api error-doc registry. Fetches a single
 * mailer's structured-error-code → doc-url map on demand and caches it in a
 * per-mailer transient.
 *
 * @since 4.9.0
 */
class Registry {

	/**
	 * Fallback product-api base URL when WPMS_PRODUCT_API_BASE_URL is undefined.
	 *
	 * @since 4.9.0
	 */
	const BASE_URL = 'https://wpmailsmtpapi.com';

	/**
	 * Product-api registry endpoint path, relative to the base URL.
	 *
	 * @since 4.9.0
	 */
	const ENDPOINT_PATH = 'troubleshooting/v1/errors';

	/**
	 * Transient key for the per-mailer-keyed map. Each entry carries its own
	 * `populated_at` so mailers refresh independently.
	 *
	 * @since 4.9.0
	 */
	const TRANSIENT_KEY = 'wp_mail_smtp_email_sending_errors_registry';

	/**
	 * Per-mailer cache TTL, in seconds (1 week).
	 *
	 * @since 4.9.0
	 */
	const TTL = WEEK_IN_SECONDS;

	/**
	 * Transient key prefix for the per-mailer fetch-failure backoff. When the
	 * remote API returns a non-200 response (or transport error), the next
	 * fetch for that mailer is skipped until this transient expires — prevents
	 * a thundering herd against our API and outbound HTTP spam from the host.
	 *
	 * @since 4.9.0
	 */
	const FETCH_BACKOFF_TRANSIENT_PREFIX = 'wp_mail_smtp_email_sending_errors_registry_fetch_backoff_';

	/**
	 * Backoff window applied after a failed fetch, in seconds.
	 *
	 * @since 4.9.0
	 */
	const FETCH_BACKOFF_TTL = HOUR_IN_SECONDS;

	/**
	 * Look up the doc-url for a (mailer, code) pair. Returns null when the
	 * mailer's entry does not list the code.
	 *
	 * A cached null under `codes[ $code ]` is a per-code negative marker:
	 * the API has been asked for this code and didn't return it, so further
	 * lookups short-circuit until the next full mailer refresh.
	 *
	 * @since 4.9.0
	 *
	 * @param string $mailer Mailer slug ('gmail', 'brevo', 'smtp', etc.).
	 * @param string $code   Structured error code.
	 *
	 * @return string|null Doc URL, or null when not published for this mailer.
	 */
	public function doc_url_for( $mailer, $code ) {

		if ( empty( $mailer ) || empty( $code ) ) {
			return null;
		}

		$cache = get_transient( self::TRANSIENT_KEY );
		$cache = is_array( $cache ) ? $cache : [];

		if ( $this->cache_is_stale( $cache, $mailer ) ) {
			$cache = $this->refresh( $cache, $mailer, $code );
		} elseif ( ! isset( $cache[ $mailer ]['codes'] ) || ! array_key_exists( $code, $cache[ $mailer ]['codes'] ) ) {
			// Per-code miss: this code has never been asked of the API for this mailer.
			// Fetch fresh; if the API still doesn't return it, refresh() will plant an
			// explicit null marker so we don't keep refetching until the next full refresh.
			$cache = $this->refresh( $cache, $mailer, $code );
		}

		if ( empty( $cache[ $mailer ]['codes'][ $code ] ) ) {
			return null;
		}

		return (string) $cache[ $mailer ]['codes'][ $code ];
	}

	/**
	 * Fetch the mailer's error map and merge it into the cache. When `$code` is
	 * given and the freshly-fetched map doesn't contain it, stores an explicit
	 * `null` under that code so subsequent lookups skip the network until the
	 * next full refresh.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $cache  Current transient payload (passed by value, returned mutated).
	 * @param string $mailer Mailer slug to refresh.
	 * @param string $code   Specific code that triggered the refresh, if any.
	 *
	 * @return array Updated cache payload.
	 */
	private function refresh( $cache, $mailer, $code = '' ) {

		$fresh = $this->fetch( $mailer );

		// Transport failures return null; leave the existing cache untouched so the
		// caller will retry on the next render.
		if ( ! is_array( $fresh ) ) {
			return $cache;
		}

		// Negative-cache the requested code when the API came back without it. This
		// is what lets unknown codes stop refetching after one round-trip — they sit
		// as null until the whole mailer entry ages out of cache.
		if ( $code !== '' && ! array_key_exists( $code, $fresh['codes'] ) ) {
			$fresh['codes'][ $code ] = null;
		}

		$cache[ $mailer ] = $fresh;

		set_transient( self::TRANSIENT_KEY, $cache, self::TTL );

		return $cache;
	}

	/**
	 * Whether the cached entry for the given mailer is missing or older than the TTL.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $cache  Full transient payload.
	 * @param string $mailer Mailer slug.
	 *
	 * @return bool
	 */
	private function cache_is_stale( $cache, $mailer ) {

		if ( ! isset( $cache[ $mailer ] ) || ! isset( $cache[ $mailer ]['populated_at'] ) ) {
			return true;
		}

		return ( time() - (int) $cache[ $mailer ]['populated_at'] ) >= self::TTL;
	}

	/**
	 * Fetch a single mailer's error map from the registry endpoint.
	 *
	 * @since 4.9.0
	 *
	 * @param string $mailer Mailer slug.
	 *
	 * @return array|null Array (`populated_at` + `codes` map) on a successful response,
	 *                   including an empty codes map when the API has no entry for this
	 *                   mailer (negative cache). Null on transport / server failure so
	 *                   the caller can retry on the next render.
	 */
	private function fetch( $mailer ) {

		$backoff_key = self::FETCH_BACKOFF_TRANSIENT_PREFIX . $mailer;

		// A prior fetch failed within the backoff window; skip the HTTP request so a
		// degraded API or flaky network doesn't translate into a stream of outbound
		// requests on every banner render.
		if ( get_transient( $backoff_key ) ) {
			return null;
		}

		$base_url = defined( 'WPMS_PRODUCT_API_BASE_URL' ) ? WPMS_PRODUCT_API_BASE_URL : self::BASE_URL;
		$endpoint = trailingslashit( $base_url ) . self::ENDPOINT_PATH;

		$response = wp_remote_get(
			add_query_arg( 'mailer', $mailer, $endpoint ),
			[
				'timeout'    => 5,
				'user-agent' => Helpers::get_default_user_agent(),
			]
		);

		if ( is_wp_error( $response ) ) {
			set_transient( $backoff_key, time(), self::FETCH_BACKOFF_TTL );

			return null;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			set_transient( $backoff_key, time(), self::FETCH_BACKOFF_TTL );

			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			set_transient( $backoff_key, time(), self::FETCH_BACKOFF_TTL );

			return null;
		}

		$decoded = json_decode( $body, true );

		if ( ! is_array( $decoded ) ) {
			set_transient( $backoff_key, time(), self::FETCH_BACKOFF_TTL );

			return null;
		}

		// Successful response — clear any stale backoff marker so future failures
		// start a fresh window instead of inheriting a partially-elapsed one.
		delete_transient( $backoff_key );

		if ( ! isset( $decoded['errors'] ) ) {
			return [
				'populated_at' => time(),
				'codes'        => [],
			];
		}

		$errors = is_array( $decoded['errors'] ) ? $decoded['errors'] : (array) $decoded['errors'];

		return [
			'populated_at' => time(),
			'codes'        => array_map( 'strval', $errors ),
		];
	}
}
