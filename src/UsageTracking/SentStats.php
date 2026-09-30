<?php
/**
 * Sent email statistics tracking.
 *
 * Handles tracking of successful email sends for usage stats.
 *
 * @since 4.10.0
 */

namespace WPMailSMTP\UsageTracking;

use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class SentStats.
 *
 * Counts successful email sends in memory during a request,
 * then flushes to the database on shutdown if any sends were recorded.
 *
 * Unlike ErrorStats, the stored option is never reset: the running total
 * and the last-sent timestamp survive every usage-tracking ping.
 *
 * @since 4.10.0
 */
class SentStats {

	/**
	 * Option name to store sent stats.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'wp_mail_smtp_email_sending_sent_stat';

	/**
	 * How many ISO-week buckets to keep, newest first.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const MAX_WEEK_BUCKETS = 3;

	/**
	 * Sends counted during the current request.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private $pending_count = 0;

	/**
	 * Timestamp of the last send counted during the current request.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private $last_sent = 0;

	/**
	 * Whether the shutdown flush hook has been registered.
	 *
	 * @since 4.10.0
	 *
	 * @var bool
	 */
	private $shutdown_registered = false;

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks() {

		// Track successful SMTP/PHP-mail sends. The action fires only on success.
		add_action( 'wp_mail_smtp_mailcatcher_smtp_send_after', [ $this, 'track_smtp_send' ] );
		// Track API mailer sends. The action also fires on failure, so the handler checks the result.
		add_action( 'wp_mail_smtp_mailcatcher_send_after', [ $this, 'track_api_send' ] );
		// Add data to usage tracking.
		add_filter( 'wp_mail_smtp_usage_tracking_get_data', [ $this, 'add_usage_stats' ] );
	}

	/**
	 * Track a successful SMTP/PHP-mail send.
	 *
	 * @since 4.10.0
	 */
	public function track_smtp_send() {

		$this->track_sent();
	}

	/**
	 * Track an API mailer send, counting it only when the mailer reports success.
	 *
	 * @since 4.10.0
	 *
	 * @param MailerAbstract $mailer The mailer object.
	 */
	public function track_api_send( $mailer ) {

		if ( ! $mailer instanceof MailerAbstract || ! $mailer->is_email_sent() ) {
			return;
		}

		$this->track_sent();
	}

	/**
	 * Count a successful send in memory. All accumulated counts are flushed
	 * to the database on shutdown.
	 *
	 * @since 4.10.0
	 */
	private function track_sent() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		++$this->pending_count;

		$this->last_sent = time();

		// Register shutdown flush lazily on first tracked send.
		if ( ! $this->shutdown_registered ) {
			add_action( 'shutdown', [ $this, 'flush' ] );

			$this->shutdown_registered = true;
		}
	}

	/**
	 * Flush accumulated counts to the database.
	 *
	 * Merges pending counts into the stored stats, trims week buckets to the
	 * newest MAX_WEEK_BUCKETS, and saves. Bails early if nothing was tracked
	 * during this request.
	 *
	 * @since 4.10.0
	 */
	public function flush() {

		if ( $this->pending_count === 0 ) {
			return;
		}

		$stats = $this->get_stats();
		$week  = gmdate( 'o-\WW' );

		$stats['total']    += $this->pending_count;
		$stats['last_sent'] = $this->last_sent;

		if ( ! isset( $stats['weeks'][ $week ] ) ) {
			$stats['weeks'][ $week ] = 0;
		}

		$stats['weeks'][ $week ] += $this->pending_count;

		// ISO week keys are zero-padded, so a plain key sort is chronological.
		ksort( $stats['weeks'] );

		$stats['weeks'] = array_slice( $stats['weeks'], - self::MAX_WEEK_BUCKETS, self::MAX_WEEK_BUCKETS, true );

		update_option( self::OPTION_NAME, $stats, false );

		$this->pending_count = 0;
	}

	/**
	 * Get the stored stats, normalized to the expected shape.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_stats() {

		$stored = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$stored = array_merge(
			[
				'total'     => 0,
				'weeks'     => [],
				'last_sent' => 0,
			],
			$stored
		);

		return [
			'total'     => (int) $stored['total'],
			'weeks'     => $this->normalize_weeks( $stored['weeks'] ),
			'last_sent' => (int) $stored['last_sent'],
		];
	}

	/**
	 * Normalize stored week buckets to valid ISO week keys with integer counts.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed $weeks Stored week buckets.
	 *
	 * @return array
	 */
	private function normalize_weeks( $weeks ) {

		$normalized = [];

		if ( ! is_array( $weeks ) ) {
			return $normalized;
		}

		foreach ( $weeks as $week => $count ) {
			if ( preg_match( '/^\d{4}-W\d{2}$/', (string) $week ) === 1 ) {
				$normalized[ $week ] = (int) $count;
			}
		}

		return $normalized;
	}

	/**
	 * Add sent stats to the usage tracking data.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Usage data.
	 *
	 * @return array
	 */
	public function add_usage_stats( $data ) {

		// Flush any pending counts before reading.
		$this->flush();

		$stats     = $this->get_stats();
		$last_week = gmdate( 'o-\WW', time() - WEEK_IN_SECONDS );

		$data['wp_mail_smtp_emails_sent_total']     = (int) $stats['total'];
		$data['wp_mail_smtp_emails_sent_last_week'] = isset( $stats['weeks'][ $last_week ] ) ? (int) $stats['weeks'][ $last_week ] : 0;
		$data['wp_mail_smtp_last_email_sent_time']  = (int) $stats['last_sent'];

		return $data;
	}
}
