<?php
/**
 * Email Sources widget body.
 *
 * @since 4.10.0
 *
 * @var string     $variant        Template variant: 'education', 'connect' or 'data'.
 * @var string     $connect_reason Why the 'connect' variant is showing: 'primary_connection' or 'test_email'.
 * @var string     $education_url  Upgrade link for the education variant.
 * @var string     $connect_url    Settings URL, connections tab.
 * @var string     $test_email_url Email test tool URL.
 * @var array|null $config         Donut/table config (Pro, data variant): all (name, total, share), palette, donutTotal.
 * @var string     $inline_cards   Rendered sending-source suggestion cards (Pro, data variant), shown below the chart.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( $variant === 'education' ) : ?>
	<?php
	echo wp_mail_smtp_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
		'dashboard/widget-overlay',
		[
			'title'  => esc_html__( 'See Which Plugins Send Emails', 'wp-mail-smtp' ),
			'text'   => esc_html__( 'Upgrade to Pro to unlock the email log and see exactly where every email comes from.', 'wp-mail-smtp' ),
			'cta'    => [
				'label'        => esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' ),
				'url'          => $education_url,
				'target_blank' => true,
			],
			'teaser' => wp_mail_smtp_render( 'dashboard/teasers/email-sources', [], true ),
		],
		true
	);
	?>
<?php elseif ( $variant === 'connect' ) : ?>
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
			'title'  => esc_html__( 'No Email Source Data Yet', 'wp-mail-smtp' ),
			'text'   => esc_html__( 'Sorry, there\'s not enough email data from this date range to show sources. Once your site sends more emails, sources will show up here.', 'wp-mail-smtp' ),
			'cta'    => $cta,
			'teaser' => wp_mail_smtp_render( 'dashboard/teasers/email-sources', [], true ),
		],
		true
	);
	?>
<?php else : ?>
	<?php $palette_size = count( $config['palette'] ); ?>
	<?php // JSON_HEX_* so an entity in a source name survives `esc_attr()`'s single encoding as literal text. ?>
	<div class="wpms-dashboard-email-sources" data-config="<?php echo esc_attr( wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ); ?>">
		<div class="wpms-dashboard-widget-email-sources-body">
			<table class="wpms-dashboard-widget-table wpms-dashboard-widget-email-sources-table">
				<thead>
					<tr>
						<th class="wpms-dashboard-widget-email-sources-col-source"><?php echo esc_html__( 'Source', 'wp-mail-smtp' ); ?></th>
						<th class="wpms-dashboard-widget-email-sources-col-share"><?php echo esc_html__( 'Share', 'wp-mail-smtp' ); ?></th>
						<th class="wpms-dashboard-widget-email-sources-col-total"><?php echo esc_html__( 'Emails', 'wp-mail-smtp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $config['all'] as $index => $source ) : ?>
						<?php $color = $config['palette'][ $index % $palette_size ]; ?>
						<tr>
							<td class="wpms-dashboard-widget-email-sources-col-source">
								<span class="wpms-dashboard-widget-email-sources-dot" style="background-color: <?php echo esc_attr( $color ); ?>;"></span>
								<?php echo esc_html( $source['name'] ); ?>
							</td>
							<td class="wpms-dashboard-widget-email-sources-col-share">
								<?php echo esc_html( round( $source['share'] ) . '%' ); ?>
							</td>
							<td class="wpms-dashboard-widget-email-sources-col-total">
								<?php echo esc_html( number_format_i18n( $source['total'] ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="wpms-dashboard-widget-email-sources-chart">
				<div class="wpms-dashboard-widget-email-sources-chart-canvas">
					<canvas class="wpms-dashboard-widget-email-sources-donut"></canvas>
					<div class="wpms-dashboard-widget-email-sources-chart-center">
						<span class="wpms-dashboard-widget-email-sources-chart-total"></span>
						<span class="wpms-dashboard-widget-email-sources-chart-caption">
							<?php echo esc_html__( 'Emails', 'wp-mail-smtp' ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php echo $inline_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered and escaped by inline-card.php. ?>
<?php endif; ?>
