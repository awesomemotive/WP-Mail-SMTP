<?php

namespace WPMailSMTP\Providers\Sendlayer;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\ConnectionsManager;
use WPMailSMTP\Helpers\Data;
use WPMailSMTP\Providers\MailerAbstract;
use WPMailSMTP\WP;

/**
 * Tracks SendLayer Quick Connect usage.
 *
 * @since 4.9.0
 */
class QuickConnectUsage {

	/**
	 * Option key holding all site-level usage-notice state as a structured array:
	 * usage (limit/used), plan, cc, limit_reached. Non-autoloaded.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const STATE_OPTION = 'wp_mail_smtp_sendlayer_quick_connect';

	/**
	 * Response header carrying the QC account quota snapshot on the plugin's own sends.
	 * Format: `limit=100000;used=437;plan=free;cc=0`. Read case-insensitively.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const QUOTA_HEADER = 'x-sendlayer-quota';

	/**
	 * Header `used` floor that gates the custom-domain notice. Below it a quiet install
	 * shows no nudges.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	const CUSTOM_DOMAIN_NOTICE_LIMIT = 10;

	/**
	 * SendLayer API error code returned when the account's email quota is reached
	 * (HTTP 429, `Errors[0].Code` == 17, message "Email quota reached"). The
	 * limit-reached notice keys off this exact code from the plugin's own send responses.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const QUOTA_REACHED_ERROR_CODE = '17';

	/**
	 * Fallback upgrade URL when the connect-time free_upgrade_url is absent.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const PRICING_URL = 'https://sendlayer.com/pricing/';

	/**
	 * Connections manager.
	 *
	 * @since 4.9.0
	 *
	 * @var ConnectionsManager
	 */
	private $connections_manager;

	/**
	 * QuickConnectUsage constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param ConnectionsManager|null $connections_manager Connections manager. Defaults to the global one.
	 */
	public function __construct( $connections_manager = null ) {

		$this->connections_manager = $connections_manager === null
			? wp_mail_smtp()->get_connections_manager()
			: $connections_manager;
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		// Nothing here is relevant unless the primary connection is a QC connection.
		if ( ! $this->is_quick_connect() ) {
			return;
		}

		add_action( 'wp_mail_smtp_mailcatcher_send_after', [ $this, 'capture_usage_from_response' ], 10, 1 );
		add_action( 'wp_mail_smtp_mailcatcher_send_after', [ $this, 'clear_limit_reached_on_success' ], 10, 1 );
		add_action( 'wp_mail_smtp_mailcatcher_send_failed', [ $this, 'maybe_flag_limit_reached' ], 10, 6 );
		add_action( 'admin_init', [ $this, 'maybe_add_notice' ] );
	}

	/**
	 * Whether the primary connection is a SendLayer Quick Connect connection.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_quick_connect() {

		$connection = $this->connections_manager->get_primary_connection();

		return $connection->get_mailer_slug() === 'sendlayer'
			&& (bool) $connection->get_options()->get( 'sendlayer', 'quick_connect' );
	}

	/**
	 * Whether the primary connection is a Quick Connect connection on a shared
	 * (SendLayer-provided) domain.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_shared_domain() {

		if ( ! $this->is_quick_connect() ) {
			return false;
		}

		$options = $this->connections_manager->get_primary_connection()->get_options();

		return (bool) $options->get( 'sendlayer', 'is_shared_domain' );
	}

	/**
	 * Store the quota snapshot from the QC send response header.
	 *
	 * Hooked to the after-send action; the header is advisory, so a send that lacks it
	 * leaves stored usage unchanged. Runs only for a primary Quick Connect send. Capture
	 * is never gated on is_bailed() - it is the classifier that arms the suppress gate.
	 *
	 * @since 4.9.0
	 *
	 * @param MailerAbstract $mailer The mailer that performed the send.
	 */
	public function capture_usage_from_response( MailerAbstract $mailer ) {

		if ( ! $this->is_quick_connect() || ! $mailer->get_connection()->is_primary() ) {
			return;
		}

		$parsed = $this->parse_quota_header( $mailer->get_response_header( self::QUOTA_HEADER ) );

		if ( $parsed === null ) {
			return;
		}

		$this->save_state(
			[
				'usage' => [
					'limit' => $parsed['limit'],
					'used'  => $parsed['used'],
				],
				'plan'  => $parsed['plan'],
				'cc'    => $parsed['cc'],
			]
		);
	}

	/**
	 * Parse the X-SendLayer-Quota header value into the usage sub-array.
	 *
	 * @since 4.9.0
	 *
	 * @param string $header Raw header value, e.g. `limit=100000;used=437;plan=free;cc=0`.
	 *
	 * @return array|null { limit, used, plan, cc } when limit+used are present, else null.
	 */
	private function parse_quota_header( $header ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( ! is_string( $header ) || $header === '' ) {
			return null;
		}

		$pairs = [];

		foreach ( explode( ';', $header ) as $segment ) {
			if ( strpos( $segment, '=' ) === false ) {
				continue;
			}

			list( $key, $value ) = explode( '=', $segment, 2 );

			$pairs[ trim( $key ) ] = trim( $value );
		}

		if ( ! isset( $pairs['limit'], $pairs['used'] ) ) {
			return null;
		}

		return [
			'limit' => (int) $pairs['limit'],
			'used'  => (int) $pairs['used'],
			'plan'  => isset( $pairs['plan'] ) ? sanitize_text_field( $pairs['plan'] ) : '',
			'cc'    => isset( $pairs['cc'] ) ? (int) $pairs['cc'] : 0,
		];
	}

	/**
	 * Reset all QC usage-notice state.
	 *
	 * Called on QC connect/disconnect and when the SendLayer API key changes, since each
	 * starts a clean slate against a different (or absent) account. Clears usage + the
	 * reached flag and re-arms every notice so the next account starts un-dismissed.
	 *
	 * @since 4.9.0
	 */
	public function reset() {

		$this->save_state(
			[
				'usage'         => $this->default_usage(),
				'plan'          => '',
				'cc'            => 0,
				'limit_reached' => false,
			]
		);

		$this->rearm_notice( $this->custom_domain_key() );
		$this->rearm_notice( $this->limit_warning_key() );
		$this->rearm_notice( $this->limit_reached_key() );
	}

	/**
	 * Clear the per-user dismissal of a notice for all users, so it shows again.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key Notice key.
	 */
	private function rearm_notice( $key ) {

		delete_metadata( 'user', 0, "wp_mail_smtp_notice_{$key}_dismissed", '', true );
	}

	/**
	 * Queue the single top-precedence due usage notice (limit_reached > limit_warning >
	 * custom_domain) for WP::display_admin_notices() to render dashboard-wide.
	 *
	 * All three require a QC primary connection, the global-options capability (the QC
	 * connection state is main-site-scoped in network-wide mode), and a not-bailed feature.
	 *
	 * @since 4.9.0
	 */
	public function maybe_add_notice() {

		if (
			$this->is_bailed() ||
			! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() )
		) {
			return;
		}

		if ( $this->should_show_limit_reached() ) {
			WP::add_admin_notice( $this->limit_reached_message(), WP::ADMIN_NOTICE_ERROR, true, $this->limit_reached_key() );

			return;
		}

		if ( $this->should_show_limit_warning() ) {
			WP::add_admin_notice( $this->limit_warning_message(), WP::ADMIN_NOTICE_WARNING, true, $this->limit_warning_key() );

			return;
		}

		if ( $this->should_show_custom_domain() ) {
			WP::add_admin_notice( $this->custom_domain_message(), WP::ADMIN_NOTICE_INFO, true, $this->custom_domain_key() );
		}
	}

	/**
	 * Notice key for the custom-domain notice. Stable; dismissal is re-armed on reset.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function custom_domain_key() {

		return 'sendlayer_qc_custom_domain';
	}

	/**
	 * Notice key for the limit-warning notice. Stable; dismissal is re-armed on reset.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function limit_warning_key() {

		return 'sendlayer_qc_limit_warning';
	}

	/**
	 * Notice key for the limit-reached notice. Stable; dismissal is re-armed on reset.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function limit_reached_key() {

		return 'sendlayer_qc_limit_reached';
	}

	/**
	 * Whether the custom-domain notice is due.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function should_show_custom_domain() {

		$usage = $this->get_usage();
		$used  = isset( $usage['used'] ) ? (int) $usage['used'] : 0;

		return $this->is_shared_domain() &&
			$used >= self::CUSTOM_DOMAIN_NOTICE_LIMIT;
	}

	/**
	 * Whether the limit-warning notice is due.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function should_show_limit_warning() {

		$usage = $this->get_usage();

		if ( empty( $usage ) ) {
			return false;
		}

		$limit = isset( $usage['limit'] ) ? (int) $usage['limit'] : 0;
		$used  = isset( $usage['used'] ) ? (int) $usage['used'] : 0;

		// No data or a nonsensical limit: never render (a wrong notice is worse
		// than none). At-or-over the limit is the limit-reached notice's territory.
		if ( $limit <= 0 || $used >= $limit ) {
			return false;
		}

		return ( $used / $limit ) >= 0.5;
	}

	/**
	 * Whether the limit-reached notice is due.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function should_show_limit_reached() {

		return (bool) $this->get_state( 'limit_reached' );
	}

	/**
	 * Flag the limit-reached state when a primary-QC send fails with the
	 * SendLayer email-quota-reached error.
	 *
	 * @since 4.9.0
	 *
	 * @param string                   $error_message Error message.
	 * @param mixed                    $mailcatcher   The MailCatcher object.
	 * @param string                   $mailer_slug   Current mailer slug.
	 * @param string                   $error_code    Error code/slug from the mailer response.
	 * @param int                      $response_code HTTP/SMTP response code.
	 * @param ConnectionInterface|null $connection    The connection the send used.
	 */
	public function maybe_flag_limit_reached( $error_message, $mailcatcher, $mailer_slug, $error_code, $response_code = 0, ConnectionInterface $connection = null ) {

		if (
			$this->is_bailed() ||
			! $connection instanceof ConnectionInterface ||
			! $connection->is_primary()
		) {
			return;
		}

		// The SendLayer mailer surfaces Errors[0].Code, which is a numeric quota
		// code on a 429; compare as a string so an int or string code both match.
		if ( (string) $error_code !== self::QUOTA_REACHED_ERROR_CODE ) {
			return;
		}

		$this->rearm_notice( $this->limit_reached_key() );

		if ( ! $this->get_state( 'limit_reached' ) ) {
			$this->save_state( [ 'limit_reached' => true ] );
		}
	}

	/**
	 * Clear the limit-reached state on the next successful primary-QC send.
	 *
	 * @since 4.9.0
	 *
	 * @param MailerAbstract $mailer The mailer that performed the send.
	 */
	public function clear_limit_reached_on_success( MailerAbstract $mailer ) {

		if ( $this->is_bailed() || ! $mailer->get_connection()->is_primary() ) {
			return;
		}

		if ( $this->get_state( 'limit_reached' ) ) {
			$this->save_state( [ 'limit_reached' => false ] );
		}
	}

	/**
	 * Resolve the upgrade CTA URL: the connect-time free_upgrade_url stored on the
	 * primary connection when present, otherwise the UTM-tagged pricing fallback.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_free_upgrade_url() {

		$stored = $this->connections_manager->get_primary_connection()->get_options()->get( 'sendlayer', 'free_upgrade_url' );

		$target = ! empty( $stored ) ? $stored : self::PRICING_URL;

		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		return wp_mail_smtp()->get_utm_url( $target, [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'SendLayer QC Usage Notice - Upgrade' ] );
	}

	/**
	 * Whether the feature is suppressed: notices run only for a primary QC connection the
	 * quota header has classified as free with no card on file. An unclassified account
	 * (empty plan) bails until the header confirms free+no-card; capture still runs, so
	 * classification still happens.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_bailed() {

		return ! $this->is_quick_connect()
			|| ! ( $this->get_state( 'plan' ) === 'free' && (int) $this->get_state( 'cc' ) === 0 );
	}

	/**
	 * Whether the account has a card on file (per the latest quota header).
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function has_card_on_file() {

		return (int) $this->get_state( 'cc' ) === 1;
	}

	/**
	 * Get the stored usage data ({ limit, used }), or an empty array when there is no
	 * usable limit yet.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_usage() {

		$usage = $this->get_state( 'usage' );

		if ( $usage['limit'] === null ) {
			return [];
		}

		return [
			'limit' => (int) $usage['limit'],
			'used'  => (int) $usage['used'],
		];
	}

	/**
	 * Custom-domain suggestion notice copy.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function custom_domain_message() {

		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		$app_url = wp_mail_smtp()->get_utm_url( 'https://app.sendlayer.com/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'QC Usage Notice - Custom Domain App' ] );
		$doc_url = wp_mail_smtp()->get_utm_url( 'https://sendlayer.com/docs/authorizing-your-domain/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'QC Usage Notice - Custom Domain Docs' ] );
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

		$message = sprintf(
			wp_kses(
				/* translators: %1$s - SendLayer dashboard URL; %2$s - custom domain documentation URL. */
				__( 'You\'re sending through a shared SendLayer domain. For better deliverability, add your own domain on the <a href="%1$s" target="_blank" rel="noopener noreferrer">SendLayer dashboard</a> and follow our <a href="%2$s" target="_blank" rel="noopener noreferrer">custom domain guide</a>.', 'wp-mail-smtp' ),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			esc_url( $app_url ),
			esc_url( $doc_url )
		);

		return $message;
	}

	/**
	 * Limit-warning notice copy.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function limit_warning_message() {

		$usage = $this->get_usage();

		$message = sprintf(
			wp_kses(
				/* translators: %1$d - emails used; %2$d - free sending limit; %3$s - upgrade URL. */
				__( 'You\'ve used %1$d of your %2$d free SendLayer emails. <a href="%3$s" target="_blank" rel="noopener noreferrer">Upgrade your plan</a> to keep your emails flowing without interruption.', 'wp-mail-smtp' ),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			isset( $usage['used'] ) ? (int) $usage['used'] : 0,
			isset( $usage['limit'] ) ? (int) $usage['limit'] : 0,
			esc_url( $this->get_free_upgrade_url() )
		);

		return $message;
	}

	/**
	 * Limit-reached notice copy.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function limit_reached_message() {

		$message = sprintf(
			wp_kses(
				/* translators: %s - upgrade URL. */
				__( 'You\'ve reached your free SendLayer limit. Emails are failing and won\'t be delivered! <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade your plan</a> to start sending again.', 'wp-mail-smtp' ),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			esc_url( $this->get_free_upgrade_url() )
		);

		return $message;
	}

	/*
	 * --- Consolidated state ---
	 *
	 * All site-level usage-notice state lives in one non-autoloaded option. The
	 * accessors below merge the stored value over defaults on read and persist the
	 * whole array on write.
	 */

	/**
	 * Default state shape, used both as the read-time merge base and the reset target.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function default_state() {

		return [
			'usage'         => $this->default_usage(),
			'plan'          => '',
			'cc'            => 0,
			'limit_reached' => false,
		];
	}

	/**
	 * Default usage sub-array. A null limit marks "no usable data yet" (no quota header
	 * received).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function default_usage() {

		return [
			'limit' => null,
			'used'  => null,
		];
	}

	/**
	 * Read the consolidated state, merged over defaults so callers always get the full
	 * shape (including the usage sub-array). Network-aware.
	 *
	 * @since 4.9.0
	 *
	 * @param string|null $key           Dot-notation key into the state, or null for the full array.
	 * @param mixed       $default_value Value to return when a keyed read misses.
	 *
	 * @return mixed
	 */
	private function get_state( $key = null, $default_value = null ) {

		$stored = $this->is_network_scoped()
			? get_blog_option( get_main_site_id(), self::STATE_OPTION, [] )
			: get_option( self::STATE_OPTION, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$state          = array_merge( $this->default_state(), $stored );
		$state['usage'] = array_merge( $this->default_usage(), is_array( $state['usage'] ) ? $state['usage'] : [] );

		if ( $key === null ) {
			return $state;
		}

		return Data::get( $state, $key, $default_value );
	}

	/**
	 * Merge the changed keys over the current state and persist. Callers pass only the
	 * keys they are changing. Non-autoloaded, network-aware.
	 *
	 * @since 4.9.0
	 *
	 * @param array $changes Top-level state keys to overwrite.
	 */
	private function save_state( array $changes ) {

		$state = array_merge( $this->get_state(), $changes );

		if ( $this->is_network_scoped() ) {
			update_blog_option( get_main_site_id(), self::STATE_OPTION, $state );

			return;
		}

		update_option( self::STATE_OPTION, $state, false );
	}

	/**
	 * Whether plugin state should be scoped to the network's main site.
	 *
	 * In network-wide mode the SendLayer connection lives on the main site, so every
	 * sub-site must agree on one per-account view of the usage-notice state.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_network_scoped() {

		// Short-circuit on single-site before consulting the network setting, so the
		// common path never touches main-site option reads.
		if ( ! is_multisite() ) {
			return false;
		}

		return WP::use_global_plugin_settings();
	}
}
