<?php

namespace WPMailSMTP\Reports;

use WPMailSMTP\Options;
use WPMailSMTP\Providers\MailerAbstract;
use WPMailSMTP\Reports\Emails\Summary as SummaryReportEmail;
use WPMailSMTP\Tasks\Reports\SummaryEmailTask;
use WPMailSMTP\WP;

/**
 * Class Reports. Emails stats reports.
 *
 * @since 3.0.0
 */
class Reports {

	/**
	 * The WP option key for storing the total number of sent emails.
	 *
	 * @since 3.0.0
	 *
	 * @const string
	 */
	const SENT_EMAILS_COUNTER_OPTION_KEY = 'wp_mail_smtp_lite_sent_email_counter';

	/**
	 * The WP option key for storing the total number of sent emails by weeks.
	 *
	 * @since 3.0.0
	 *
	 * @const string
	 */
	const WEEKLY_SENT_EMAILS_COUNTER_OPTION_KEY = 'wp_mail_smtp_lite_weekly_sent_email_counter';

	/**
	 * Weekly failed emails counter option key.
	 *
	 * @since 4.10.0
	 *
	 * @const string
	 */
	const WEEKLY_FAILED_EMAILS_COUNTER_OPTION_KEY = 'wp_mail_smtp_lite_weekly_failed_email_counter';

	/**
	 * Stats by week retention period. Value in weeks count.
	 * Maximum value is 52 weeks (1 year).
	 *
	 * @since 3.0.0
	 *
	 * @const string
	 */
	const WEEKLY_COUNTER_RETENTION_PERIOD = 12;

	/**
	 * Init class.
	 *
	 * @since 3.0.0
	 */
	public function init() {

		$this->public_hooks();

		if ( WP::in_wp_admin() ) {
			$this->admin_hooks();
		}
	}

	/**
	 * Frontend hooks.
	 *
	 * @since 3.0.0
	 */
	private function public_hooks() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks -- Sent/failed counters are updated via dedicated hooks registered here by design.

		// Update sent email counter when SMTP mailer is used. That action fires only on success.
		add_action( 'wp_mail_smtp_mailcatcher_smtp_send_after', [ $this, 'update_sent_emails_stats' ] );

		// Update sent email counter when all other mailers are used. That action also fires
		// on failure, so the handler checks the result.
		add_action( 'wp_mail_smtp_mailcatcher_send_after', [ $this, 'update_api_sent_emails_stats' ] );

		// Update failed email counter on the plugin's own failure signal, the one the
		// email log records a failed email from, rather than on `wp_mail_failed`, which
		// the queue un-hooks while it sends.
		add_action( 'wp_mail_smtp_mailcatcher_send_failed', [ $this, 'update_failed_emails_stats' ] );
	}

	/**
	 * Admin hooks.
	 *
	 * @since 3.0.0
	 */
	private function admin_hooks() {

		add_action( 'load-toplevel_page_wp-mail-smtp', [ $this, 'summary_report_email_preview' ] );

		// Detect summary report email constant change.
		if ( Options::init()->is_const_defined( 'general', SummaryReportEmail::SETTINGS_SLUG ) ) {
			add_action( 'admin_init', [ $this, 'detect_summary_report_email_constant_change' ] );
		}
	}

	/**
	 * Update all stats after email sent.
	 *
	 * @since 3.0.0
	 */
	public function update_sent_emails_stats() {

		if ( wp_mail_smtp()->is_pro() ) {
			return;
		}

		$this->increment_sent_emails_counter();
		$this->increment_weekly_sent_emails_counter();
	}

	/**
	 * Update all stats after an API mailer send, counting it only when the mailer reports
	 * success.
	 *
	 * @since 4.10.0
	 *
	 * @param MailerAbstract $mailer The mailer object.
	 */
	public function update_api_sent_emails_stats( $mailer ) {

		if ( ! $mailer instanceof MailerAbstract || ! $mailer->is_email_sent() ) {
			return;
		}

		$this->update_sent_emails_stats();
	}

	/**
	 * Increment the number of total emails sent by 1.
	 *
	 * @since 3.0.0
	 */
	private function increment_sent_emails_counter() {

		$value = $this->get_total_emails_sent() + 1;

		update_option( self::SENT_EMAILS_COUNTER_OPTION_KEY, $value, true );
	}

	/**
	 * Get the number of total emails sent.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_total_emails_sent() {

		return get_option( self::SENT_EMAILS_COUNTER_OPTION_KEY, 0 );
	}

	/**
	 * Increment the number of total emails sent in this week by 1.
	 *
	 * @since 3.0.0
	 */
	private function increment_weekly_sent_emails_counter() {

		$stats = $this->get_total_weekly_emails_sent();

		$week = $this->get_current_week();

		if ( ! isset( $stats[ $week ] ) ) {
			$stats[ $week ] = 0;
		}

		$stats[ $week ] ++;

		// Cleanup old stats.
		$stats = array_slice( $stats, self::WEEKLY_COUNTER_RETENTION_PERIOD * - 1, null, true );

		update_option( self::WEEKLY_SENT_EMAILS_COUNTER_OPTION_KEY, $stats, true );
	}

	/**
	 * Get the number of total emails sent by week.
	 *
	 * When `$week` is `'previous'` or `'two_weeks_ago'` and the calculated ISO week drops
	 * below 1 (year-boundary case), the target week number is derived from the dated
	 * offset (7 or 14 days ago) so it correctly resolves to the prior year's last weeks
	 * (52 or 53) regardless of whether intervening weeks have entries in the stats array.
	 *
	 * @since 3.0.0
	 * @since 4.9.0 Added 'two_weeks_ago' identifier and year-boundary handling.
	 * @since 4.10.0 Counters are re-keyed by integer ISO week.
	 *
	 * @param int|string|null $week One of: null (full array), 'now', 'previous',
	 *                              'two_weeks_ago', or an integer ISO week number.
	 *
	 * @return array|int
	 */
	public function get_total_weekly_emails_sent( $week = null ) {

		$stats = $this->normalize_weekly_counters( get_option( self::WEEKLY_SENT_EMAILS_COUNTER_OPTION_KEY, [] ) );

		if ( is_null( $week ) ) {
			return $stats;
		}

		$current_week = $this->get_current_week();

		if ( $week === 'now' ) {
			return isset( $stats[ $current_week ] ) ? $stats[ $current_week ] : 0;
		}

		if ( $week === 'previous' ) {
			$target = $current_week - 1;

			if ( $target < 1 ) {
				$target = (int) wp_date( 'W', strtotime( '-7 days' ) );
			}

			return isset( $stats[ $target ] ) ? $stats[ $target ] : 0;
		}

		if ( $week === 'two_weeks_ago' ) {
			$target = $current_week - 2;

			if ( $target < 1 ) {
				$target = (int) wp_date( 'W', strtotime( '-14 days' ) );
			}

			return isset( $stats[ $target ] ) ? $stats[ $target ] : 0;
		}

		// Explicit integer ISO week number.
		return isset( $stats[ $week ] ) ? $stats[ $week ] : 0;
	}

	/**
	 * Record a failed email send.
	 *
	 * @since 4.10.0
	 */
	public function update_failed_emails_stats() {

		if ( wp_mail_smtp()->is_pro() ) {
			return;
		}

		$stats = $this->get_total_weekly_emails_failed();

		$week = $this->get_current_week();

		if ( ! isset( $stats[ $week ] ) ) {
			$stats[ $week ] = 0;
		}

		++$stats[ $week ];

		// Cleanup old stats.
		$stats = array_slice( $stats, self::WEEKLY_COUNTER_RETENTION_PERIOD * - 1, null, true );

		update_option( self::WEEKLY_FAILED_EMAILS_COUNTER_OPTION_KEY, $stats, true );
	}

	/**
	 * Get the number of total emails failed by week, with the same year-boundary handling
	 * as get_total_weekly_emails_sent().
	 *
	 * @since 4.10.0
	 *
	 * @param int|string|null $week One of: null (full array), 'now', 'previous',
	 *                              'two_weeks_ago', or an integer ISO week number.
	 *
	 * @return array|int
	 */
	public function get_total_weekly_emails_failed( $week = null ) {

		$stats = $this->normalize_weekly_counters( get_option( self::WEEKLY_FAILED_EMAILS_COUNTER_OPTION_KEY, [] ) );

		if ( is_null( $week ) ) {
			return $stats;
		}

		$current_week = $this->get_current_week();

		if ( $week === 'now' ) {
			$week = $current_week;
		} elseif ( $week === 'previous' ) {
			$week = $this->get_target_week( $current_week, 1, '-7 days' );
		} elseif ( $week === 'two_weeks_ago' ) {
			$week = $this->get_target_week( $current_week, 2, '-14 days' );
		}

		return isset( $stats[ $week ] ) ? $stats[ $week ] : 0;
	}

	/**
	 * Re-key weekly counters by integer ISO week, so a week can be looked up by its number.
	 *
	 * @since 4.10.0
	 *
	 * @param array $stats Counters keyed by ISO week.
	 *
	 * @return array
	 */
	private function normalize_weekly_counters( $stats ) {

		$normalized = [];

		foreach ( (array) $stats as $week => $count ) {
			// `current_time( 'W' )` zero-pads, and PHP keeps `'05'` a string key, so one
			// week can hold both spellings. Summed, since neither is the more current.
			$week = (int) $week;

			$normalized[ $week ] = ( $normalized[ $week ] ?? 0 ) + (int) $count;
		}

		return $normalized;
	}

	/**
	 * Resolve the target ISO week a given number of weeks back from the current one,
	 * derived from a dated offset below week 1 so it lands on the prior year's last week.
	 *
	 * @since 4.10.0
	 *
	 * @param int    $current_week Current ISO week number.
	 * @param int    $weeks_back   How many weeks back to look.
	 * @param string $fallback     A `strtotime()`-compatible offset used when the
	 *                             subtraction drops below week 1.
	 *
	 * @return int
	 */
	private function get_target_week( $current_week, $weeks_back, $fallback ) {

		$target = $current_week - $weeks_back;

		if ( $target < 1 ) {
			$target = (int) wp_date( 'W', strtotime( $fallback ) );
		}

		return $target;
	}

	/**
	 * Generate a summary report email preview and display it for users.
	 *
	 * @since 3.0.0
	 */
	public function summary_report_email_preview() {

		if ( ! current_user_can( wp_mail_smtp()->get_admin()->get_logs_access_capability() ) ) {
			return;
		}

		if ( ! isset( $_GET['mode'] ) || $_GET['mode'] !== 'summary_report_email_preview' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$email = $this->get_summary_report_email();

		echo $email->get_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Get emails stats weekly summary report email.
	 *
	 * @since 3.0.0
	 *
	 * @return SummaryReportEmail
	 */
	public function get_summary_report_email() {

		return new SummaryReportEmail();
	}

	/**
	 * Detect summary report email constant change.
	 *
	 * @since 3.0.0
	 */
	public function detect_summary_report_email_constant_change() {

		if ( ! WP::in_wp_admin() ) {
			return;
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			return;
		}

		if ( Options::init()->is_const_changed( 'general', SummaryReportEmail::SETTINGS_SLUG ) ) {
			( new SummaryEmailTask() )->cancel();
		}
	}

	/**
	 * Get current week number.
	 *
	 * @since 3.0.0
	 * @since 4.10.0 Cast to int, so a week below 10 keys the counters by its number.
	 *
	 * @return int
	 */
	public function get_current_week() {

		return (int) current_time( 'W' );
	}
}
