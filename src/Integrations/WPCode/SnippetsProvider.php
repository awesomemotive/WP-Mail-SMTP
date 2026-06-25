<?php

namespace WPMailSMTP\Integrations\WPCode;

/**
 * Provides the plugin's WPCode library snippets as a single, normalized
 * view-model, whether or not the WPCode plugin is active.
 *
 * When WPCode is active its own library helper supplies the data (with real
 * installed flags and install URLs); otherwise the snippets are fetched from
 * the library API over HTTP, cached for a day, and fall back to placeholders.
 * Both sources are normalized through map_snippet() so consumers see one shape.
 *
 * @since 4.9.0
 */
class SnippetsProvider {

	/**
	 * Library API base.
	 *
	 * @since 4.9.0
	 */
	const API_BASE = 'https://library.wpcode.com/api/profile/';

	/**
	 * Transient key for the cached snippet list.
	 *
	 * @since 4.9.0
	 */
	const CACHE_KEY = 'wp_mail_smtp_wpcode_snippets';

	/**
	 * Library username this provider reads.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Whether the last get_snippets() resolved to the placeholder fallback.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $fallback = false;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param string $username Library username.
	 */
	public function __construct( $username ) {

		$this->username = $username;
	}

	/**
	 * All snippets for the current state, as normalized card view-models.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_snippets() {

		$this->fallback = false;

		$snippets = $this->is_wpcode_active()
			? $this->get_snippets_from_wpcode()
			: $this->get_snippets_from_api();

		// Surface installed snippets first, keeping each group's source order
		// (sort functions aren't stable on PHP 7.4, the plugin's minimum).
		$installed = [];
		$rest      = [];

		foreach ( $snippets as $snippet ) {
			if ( ! empty( $snippet['installed'] ) ) {
				$installed[] = $snippet;
			} else {
				$rest[] = $snippet;
			}
		}

		return array_merge( $installed, $rest );
	}

	/**
	 * A single normalized snippet by its library id, or null when not found.
	 *
	 * @since 4.9.0
	 *
	 * @param int $library_id Numeric library id.
	 *
	 * @return array|null
	 */
	public function get_snippet( $library_id ) {

		$library_id = absint( $library_id );

		foreach ( $this->get_snippets() as $snippet ) {
			if ( $snippet['library_id'] === $library_id ) {
				return $snippet;
			}
		}

		return null;
	}

	/**
	 * Whether the last get_snippets() call fell back to placeholders.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_fallback() {

		return $this->fallback;
	}

	/**
	 * Whether WPCode is active (its library helper is available).
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_wpcode_active() {

		return function_exists( 'wpcode_get_library_snippets_by_username' );
	}

	/**
	 * Snippets from WPCode's own library helper, normalized.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function get_snippets_from_wpcode() {

		$snippets = wpcode_get_library_snippets_by_username( $this->username );

		if ( ! is_array( $snippets ) ) {
			return [];
		}

		$mapped = [];

		foreach ( $snippets as $snippet ) {
			if ( empty( $snippet['library_id'] ) ) {
				continue;
			}

			$mapped[] = $this->map_snippet( $snippet, absint( $snippet['library_id'] ) );
		}

		return $mapped;
	}

	/**
	 * Snippets from the library API: HTTP, cached for a day,
	 * with a placeholder fallback when there's no cache and the fetch fails.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function get_snippets_from_api() {

		$cached = get_transient( self::CACHE_KEY );

		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get( self::API_BASE . sanitize_key( $this->username ) );

		if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
			$mapped = $this->map_response( json_decode( wp_remote_retrieve_body( $response ), true ) );

			if ( ! empty( $mapped ) ) {
				set_transient( self::CACHE_KEY, $mapped, DAY_IN_SECONDS );

				return $mapped;
			}
		}

		// No cache and no live data: fall back to placeholders, blurred behind the modal.
		$this->fallback = true;

		return $this->get_placeholder_snippets();
	}

	/**
	 * Map a raw API response to normalized card view-models.
	 *
	 * @since 4.9.0
	 *
	 * @param array $response Decoded API response.
	 *
	 * @return array List of snippet view-models (empty on error/empty).
	 */
	private function map_response( $response ) {

		if (
			empty( $response['status'] ) ||
			$response['status'] !== 'success' ||
			empty( $response['data']['snippets'] )
		) {
			return [];
		}

		$snippets = [];

		foreach ( $response['data']['snippets'] as $snippet ) {
			if ( empty( $snippet['library_id'] ) ) {
				continue;
			}

			$snippets[] = $this->map_snippet( $snippet, absint( $snippet['library_id'] ) );
		}

		return $snippets;
	}

	/**
	 * Normalize a raw snippet (from either source) to the card view-model.
	 *
	 * @since 4.9.0
	 *
	 * @param array $snippet    Raw snippet.
	 * @param int   $library_id Numeric library id.
	 *
	 * @return array
	 */
	private function map_snippet( $snippet, $library_id ) {

		return [
			'title'      => isset( $snippet['title'] ) ? sanitize_text_field( $snippet['title'] ) : '',
			'note'       => isset( $snippet['note'] ) ? sanitize_text_field( $snippet['note'] ) : '',
			'installed'  => ! empty( $snippet['installed'] ),
			'install'    => isset( $snippet['install'] ) ? esc_url_raw( $snippet['install'] ) : '',
			'library_id' => $library_id,
			'code'       => isset( $snippet['code'] ) ? (string) $snippet['code'] : '',
			'code_type'  => isset( $snippet['code_type'] ) ? sanitize_key( $snippet['code_type'] ) : 'php',
		];
	}

	/**
	 * Hardcoded placeholder snippets, shown only blurred behind the install modal
	 * as the fallback (ports WPForms' get_placeholder_snippets mechanism, populated
	 * with this plugin's real snippet titles).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function get_placeholder_snippets() {

		$titles = [
			'Adding a Custom Filter when using Other SMTP Setting',
			'Change the redirect URI when using Google App\'s OAuth',
			'Set a Custom Reply-To Email',
			'Using an Office 365 GCC or DoD email address',
			'Change the Email Summary Email Address',
			'Block URLs Inside Text Fields',
			'Set a Custom Email Header',
			'Defining Amazon SES Constants',
			'Use Single Tenant With the Outlook Mailer',
			'Defining Google Mailer Constants',
			'Define the Mailgun Signing Key Constant',
			'Defining Outlook Mailer Constants',
		];

		$snippets = [];

		foreach ( $titles as $title ) {
			$snippets[] = [
				'title'      => $title,
				'note'       => esc_html__( 'Install WPCode to use this snippet.', 'wp-mail-smtp' ),
				'installed'  => false,
				'install'    => 'https://library.wpcode.com/',
				'library_id' => 0,
				'code'       => '',
				'code_type'  => 'php',
			];
		}

		return $snippets;
	}
}
