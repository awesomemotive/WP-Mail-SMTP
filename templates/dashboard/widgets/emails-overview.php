<?php
/**
 * Emails Overview widget body.
 *
 * @since 4.10.0
 *
 * @var string $variant        Template variant: 'data' or 'connect'.
 * @var string $connect_reason Why the 'connect' variant is showing: 'primary_connection' or 'test_email'.
 * @var array  $series         Chart rows: a label, an optional tooltip span, and sent/failed
 * (Lite) or confirmed/unconfirmed/failed/opened (Pro), per row.
 * @var array  $series_meta    Chart series metadata: id, label, color, fill.
 * @var string $connect_url    Settings URL, scrolled to the primary connection's mailer row.
 * @var string $test_email_url Email test tool URL.
 * @var string $inline_cards   Rendered dismissible notice cards, shown under the chart.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( $variant === 'connect' ) : ?>
	<?php
	if ( $connect_reason === 'test_email' ) {
		$cta = [
			'label' => esc_html__( 'Send a Test Email', 'wp-mail-smtp' ),
			'url'   => $test_email_url,
		];
	} else {
		$cta = [
			'label' => esc_html__( 'Setup Primary Connection', 'wp-mail-smtp' ),
			'url'   => $connect_url,
		];
	}

	echo wp_mail_smtp_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
		'dashboard/widget-overlay',
		[
			'title'  => esc_html__( 'No Emails Sent Yet', 'wp-mail-smtp' ),
			'text'   => esc_html__( 'Once your site starts sending emails, your stats will show up here. Send a test email to make sure everything works.', 'wp-mail-smtp' ),
			'cta'    => $cta,
			'teaser' => wp_mail_smtp_render( 'dashboard/teasers/chart', [], true ),
		],
		true
	);
	?>
<?php else : ?>
	<div class="wpms-dashboard-widget__chart-holder">
		<canvas class="wpms-dashboard-widget-emails-overview-chart"
			data-series="<?php echo esc_attr( wp_json_encode( $series ) ); ?>"
			data-series-meta="<?php echo esc_attr( wp_json_encode( $series_meta ) ); ?>"></canvas>
	</div>

	<?php echo $inline_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered and escaped by inline-card.php. ?>
<?php endif; ?>
