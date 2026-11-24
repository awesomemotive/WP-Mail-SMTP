<?php
/**
 * Email Log widget body.
 *
 * @since 4.10.0
 *
 * @var string $variant        Template variant: 'education', 'connect' or 'data'.
 * @var string $connect_reason Why the 'connect' variant is showing: 'primary_connection' or 'test_email'.
 * @var string $education_url  Upgrade link for the education variant.
 * @var string $connect_url    Settings URL, connections tab.
 * @var string $test_email_url Email test tool URL.
 * @var array  $rows           Log rows (Pro, data variant): subject, subject_attr, recipient,
 *                             mailer, date_sent, status ('sent'|'waiting'|'failed'),
 *                             status_label, edit_url, preview_url. `subject_attr` is the
 *                             subject with its entities double-encoded, because Thickbox
 *                             reads the title attribute back into the DOM as HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( $variant === 'education' ) : ?>
	<?php
	wp_mail_smtp_render(
		'dashboard/widget-overlay',
		[
			'title'  => esc_html__( 'View Email Logs', 'wp-mail-smtp' ),
			'text'   => esc_html__( 'Upgrade to Pro to unlock email logging and see every email your site sends right from your dashboard.', 'wp-mail-smtp' ),
			'cta'    => [
				'label'        => esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' ),
				'url'          => $education_url,
				'target_blank' => true,
			],
			'teaser' => 'email-log',
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

	wp_mail_smtp_render(
		'dashboard/widget-overlay',
		[
			'title'  => esc_html__( 'No Emails Logged Yet', 'wp-mail-smtp' ),
			'text'   => esc_html__( 'Once your site starts sending emails, your recent emails will show up here. Send a test email to make sure everything works.', 'wp-mail-smtp' ),
			'cta'    => $cta,
			'teaser' => 'email-log',
		],
		true
	);
	?>
<?php else : ?>
	<div class="wpms-dashboard-widget-table-scroll">
		<table class="wpms-dashboard-widget-table wpms-dashboard-widget-email-log-table">
			<thead>
				<tr>
					<th class="wpms-dashboard-widget-email-log-col-subject"><?php echo esc_html__( 'Subject', 'wp-mail-smtp' ); ?></th>
					<th class="wpms-dashboard-widget-email-log-col-recipient"><?php echo esc_html__( 'To', 'wp-mail-smtp' ); ?></th>
					<th class="wpms-dashboard-widget-email-log-col-mailer"><?php echo esc_html__( 'Mailer', 'wp-mail-smtp' ); ?></th>
					<th class="wpms-dashboard-widget-email-log-col-date"><?php echo esc_html__( 'Date Sent', 'wp-mail-smtp' ); ?></th>
					<th class="wpms-dashboard-widget-email-log-col-view"><?php echo esc_html__( 'View', 'wp-mail-smtp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td class="wpms-dashboard-widget-email-log-col-subject">
							<span class="wpms-dashboard-widget-email-log-subject">
								<span class="wpms-dashboard-widget-email-log-status wpms-dashboard-widget-email-log-status--<?php echo esc_attr( $row['status'] ); ?>"
									title="<?php echo esc_attr( $row['status_label'] ); ?>">
									<?php if ( $row['status'] === 'sent' ) : ?>
										<i class="wpms:icon-[fa6-solid--circle-check] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
									<?php else : ?>
										<i class="wpms:icon-[fa6-solid--circle-exclamation] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
									<?php endif; ?>
									<span class="screen-reader-text"><?php echo esc_html( $row['status_label'] ); ?></span>
								</span>

								<a href="<?php echo esc_url( $row['edit_url'] ); ?>" title="<?php echo esc_attr( $row['subject'] ); ?>">
									<?php echo esc_html( $row['subject'] ); ?>
								</a>
							</span>
						</td>
						<td class="wpms-dashboard-widget-email-log-col-recipient" title="<?php echo esc_attr( $row['recipient'] ); ?>">
							<?php echo esc_html( $row['recipient'] ); ?>
						</td>
						<td class="wpms-dashboard-widget-email-log-col-mailer"><?php echo esc_html( $row['mailer'] ); ?></td>
						<td class="wpms-dashboard-widget-email-log-col-date"><?php echo esc_html( $row['date_sent'] ); ?></td>
						<td class="wpms-dashboard-widget-email-log-col-view">
							<?php if ( ! empty( $row['preview_url'] ) ) : ?>
								<a href="<?php echo esc_url( $row['preview_url'] ); ?>"
									class="thickbox wpms-dashboard-widget-email-log-view"
									title="<?php echo esc_attr( $row['subject_attr'] ); ?>"
									aria-label="<?php echo esc_attr__( 'View email', 'wp-mail-smtp' ); ?>">
									<i class="wpms:icon-[fa6-solid--eye] wpms:text-[14px]" aria-hidden="true"></i>
								</a>
							<?php else : ?>
								<?php // No stored content to preview, so the eye opens the log entry, as the Email Log page's own row actions do. ?>
								<a href="<?php echo esc_url( $row['edit_url'] ); ?>"
									class="wpms-dashboard-widget-email-log-view"
									aria-label="<?php echo esc_attr__( 'View log entry', 'wp-mail-smtp' ); ?>">
									<i class="wpms:icon-[fa6-solid--eye] wpms:text-[14px]" aria-hidden="true"></i>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
