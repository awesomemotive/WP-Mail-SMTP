<?php

namespace WPMailSMTP\Admin\Dashboard;

use WPMailSMTP\Reports\Reports;

/**
 * Dashboard statistics.
 *
 * @since 4.10.0
 */
class Stats {

	/**
	 * Number of weeks the series covers.
	 *
	 * @since 4.10.0
	 */
	const WEEKS = 12;

	/**
	 * Share of failed emails, in percent, that triggers the failed-emails prompts.
	 *
	 * @since 4.10.0
	 */
	const FAILED_THRESHOLD_PERCENT = 20;

	/**
	 * Number of trailing weekly rows standing in for the 30 days the failed-email prompts
	 * name.
	 *
	 * @since 4.10.0
	 */
	private const RECENT_WEEKS = 5;

	/**
	 * Per-week sent and failed counts, oldest first.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_weekly_series() {

		$sent   = $this->get_sent_counters();
		$failed = $this->get_failed_counters();
		$series = [];

		for ( $offset = self::WEEKS - 1; $offset >= 0; $offset-- ) {
			$week = $this->get_week_number( $offset );

			$series[] = [
				'week'   => $week,
				'sent'   => (int) ( $sent[ $week ] ?? 0 ),
				'failed' => (int) ( $failed[ $week ] ?? 0 ),
			];
		}

		return $series;
	}

	/**
	 * Chart series for the graph, one point per week through the part-finished current one.
	 * Lite has no date range, so `$range` is ignored; the subclass scopes to it.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $range Date range to scope to, `[ from, to ]`. Ignored on Lite.
	 *
	 * @return array Points as `[ 'label' => string, 'tooltip' => string, 'sent' => int,
	 *               'failed' => int ]`.
	 */
	public function get_series( ?array $range = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Contract signature; the Pro subclass uses $range.

		$series = [];

		foreach ( $this->get_weekly_series() as $index => $point ) {
			$weeks_back = self::WEEKS - 1 - $index;

			$series[] = [
				'label'   => $this->get_week_label( $weeks_back ),
				'tooltip' => $this->get_week_range_label( $weeks_back ),
				'sent'    => $point['sent'],
				'failed'  => $point['failed'],
			];
		}

		return $series;
	}

	/**
	 * The short localized date label for the Monday of a week a given number of weeks
	 * back, e.g. "Jan 22".
	 *
	 * @since 4.10.0
	 *
	 * @param int $weeks_back How many weeks back from the current one.
	 *
	 * @return string
	 */
	protected function get_week_label( $weeks_back ) {

		return date_i18n( 'M j', $this->get_week_start( $weeks_back ) );
	}

	/**
	 * The week's full span, e.g. "Jan 22 - Jan 28", for the chart tooltip.
	 *
	 * @since 4.10.0
	 *
	 * @param int $weeks_back How many weeks back from the current one.
	 *
	 * @return string
	 */
	protected function get_week_range_label( $weeks_back ) {

		$start = $this->get_week_start( $weeks_back );

		return sprintf(
			/* translators: %1$s - the week's first day, %2$s - the week's last day, both as short dates. */
			esc_html__( '%1$s - %2$s', 'wp-mail-smtp' ),
			date_i18n( 'M j', $start ),
			date_i18n( 'M j', strtotime( '+6 days', $start ) )
		);
	}

	/**
	 * Timestamp of the Monday starting the ISO week a given number of weeks back, the
	 * week the counters are keyed by.
	 *
	 * @since 4.10.0
	 *
	 * @param int $weeks_back How many weeks back from the current one.
	 *
	 * @return int
	 */
	protected function get_week_start( $weeks_back ) {

		return strtotime( 'monday this week', strtotime( "-{$weeks_back} weeks" ) );
	}

	/**
	 * Last week's sent and failed totals: the previous complete week, so a percent change
	 * compares two complete weeks.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_last_week_totals() {

		return [
			'sent'   => $this->get_week_value( 'sent', 1 ),
			'failed' => $this->get_week_value( 'failed', 1 ),
		];
	}

	/**
	 * The stat cards' totals, keyed by metric. Lite has no date range, so `$range` is
	 * ignored; the subclass scopes the figures to it.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $range Date range to scope to, `[ from, to ]`. Ignored on Lite.
	 *
	 * @return array
	 */
	public function get_stat_totals( ?array $range = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Contract signature; the Pro subclass uses $range.

		return $this->get_last_week_totals();
	}

	/**
	 * The stat cards' percent changes, keyed by metric. Null where there is nothing
	 * earlier to compare against.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $range Date range to scope to, `[ from, to ]`. Ignored on Lite.
	 *
	 * @return array
	 */
	public function get_stat_deltas( ?array $range = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Contract signature; the Pro subclass uses $range.

		$deltas = [];

		foreach ( $this->get_stat_metrics() as $metric ) {
			$deltas[ $metric ] = $this->get_delta( $metric );
		}

		return $deltas;
	}

	/**
	 * The metrics the stat cards carry. Pro records two more on top of these.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_stat_metrics(): array {

		return [ 'sent', 'failed' ];
	}

	/**
	 * Percent change of last week against the week before it.
	 *
	 * @since 4.10.0
	 *
	 * @param string $metric Either 'sent' or 'failed'.
	 *
	 * @return float|null Null when the earlier week has no data to compare against.
	 */
	public function get_delta( $metric ) {

		$previous = $this->get_week_value( $metric, 2 );

		if ( $previous === 0 ) {
			return null;
		}

		$last = $this->get_week_value( $metric, 1 );

		return round( ( $last - $previous ) / $previous * 100, 1 );
	}

	/**
	 * Sent and failed totals over the 30 days the failed-email prompts name, rounded up to
	 * whole weeks because that is all these counters store: 29 to 35 days.
	 *
	 * @since 4.10.0
	 *
	 * @return array `[ 'sent' => int, 'failed' => int ]`.
	 */
	protected function get_recent_totals() {

		$totals = [
			'sent'   => 0,
			'failed' => 0,
		];

		foreach ( array_slice( $this->get_weekly_series(), -self::RECENT_WEEKS ) as $week ) {
			$totals['sent']   += $week['sent'];
			$totals['failed'] += $week['failed'];
		}

		return $totals;
	}

	/**
	 * Failed emails over that period.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	public function get_recent_failed_count() {

		return (int) $this->get_recent_totals()['failed'];
	}

	/**
	 * Whether failed emails are a large enough share of that period's sends to prompt
	 * the user: at least self::FAILED_THRESHOLD_PERCENT of them.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function has_high_failure_rate() {

		$totals = $this->get_recent_totals();
		$total  = $totals['sent'] + $totals['failed'];

		if ( $total === 0 ) {
			return false;
		}

		return $totals['failed'] / $total * 100 >= self::FAILED_THRESHOLD_PERCENT;
	}

	/**
	 * One metric's count for a week a given number of weeks back.
	 *
	 * @since 4.10.0
	 *
	 * @param string $metric Metric name.
	 * @param int    $back   How many weeks back from the current one.
	 *
	 * @return int
	 */
	private function get_week_value( $metric, $back ) {

		$counters = $this->get_counters( $metric );

		return (int) ( $counters[ $this->get_week_number( $back ) ] ?? 0 );
	}

	/**
	 * The stored counters for one metric, or an empty array when the metric has no source.
	 * Subclasses extend the map with their own metrics.
	 *
	 * @since 4.10.0
	 *
	 * @param string $metric Metric name.
	 *
	 * @return array
	 */
	protected function get_counters( $metric ) {

		if ( $metric === 'sent' ) {
			return $this->get_sent_counters();
		}

		if ( $metric === 'failed' ) {
			return $this->get_failed_counters();
		}

		return [];
	}

	/**
	 * The ISO week number a given number of weeks back from now, derived from a date so it
	 * resolves across a year boundary, where the prior year's week 52 or 53 follows week 1.
	 *
	 * @since 4.10.0
	 *
	 * @param int $weeks_back How many weeks back from the current one.
	 *
	 * @return int
	 */
	protected function get_week_number( $weeks_back = 0 ) {

		return (int) wp_date( 'W', strtotime( "-{$weeks_back} weeks" ) );
	}

	/**
	 * The current ISO week number.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	protected function get_current_week() {

		return $this->get_week_number( 0 );
	}

	/**
	 * Sent counts keyed by ISO week number.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_sent_counters() {

		return (array) ( new Reports() )->get_total_weekly_emails_sent();
	}

	/**
	 * Failed counts keyed by ISO week number.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_failed_counters() {

		return (array) ( new Reports() )->get_total_weekly_emails_failed();
	}
}
