<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;

/**
 * Get Pro tab — a product-education page shown to Lite users only.
 *
 * @since 4.9.0
 */
class GetProTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $slug = 'get-pro';

	/**
	 * Tab priority.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	protected $priority = 100;

	/**
	 * Link label of a tab.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Get Pro', 'wp-mail-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Tab content.
	 *
	 * @since 4.9.0
	 */
	public function display() {

		?>
		<div class="wpms-get-pro-tab wpms:max-w-[1200px] wpms:mx-auto wpms:mt-[30px] wpms:bg-white wpms:rounded-[4px] wpms:border wpms:border-[#dadadf] wpms:shadow-soft">

			<?php $this->display_hero(); ?>
			<?php $this->display_features(); ?>
			<?php $this->display_comparison(); ?>

		</div>
		<?php
	}

	/**
	 * Hero: headline, supporting copy, primary CTA and the comparison anchor link.
	 *
	 * @since 4.9.0
	 */
	private function display_hero() {

		?>
		<div class="wpms:flex wpms:flex-wrap wpms:items-center wpms:justify-between wpms:gap-[40px] wpms:px-[40px] wpms:py-[40px]">

			<div class="wpms:flex wpms:flex-col wpms:gap-[20px] wpms:basis-[554px] wpms:grow-0 wpms:min-w-[300px]">
				<h1>
					<?php esc_html_e( 'Take complete control of your WordPress emails', 'wp-mail-smtp' ); ?>
				</h1>

				<p>
					<?php esc_html_e( 'Get email logs, delivery tracking, backup connections, and instant failure alerts so you catch issues before your customers do.', 'wp-mail-smtp' ); ?>
				</p>

				<div class="wpms:flex wpms:flex-col wpms:gap-[20px]">
					<div class="wpms:flex wpms:flex-col wpms:gap-[10px] wpms:items-start">
						<?php $this->display_upgrade_button( 'Hero' ); ?>
						<?php $this->display_discount_badge(); ?>
					</div>

					<a href="#wp-mail-smtp-get-pro-comparison" class="wpms:text-[13px] wpms:text-link! wpms:underline">
						<?php esc_html_e( 'Compare Lite vs Pro features', 'wp-mail-smtp' ); ?>
					</a>
				</div>
			</div>

			<div class="wpms:shrink-0">
				<img
					src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/education/get-pro-tab/hero.svg' ); ?>"
					alt=""
					width="400"
					height="214"
					class="wpms:w-[400px] wpms:max-w-full wpms:h-auto"
				>
			</div>

		</div>
		<?php
	}

	/**
	 * Feature grid: six product highlights on a pale section, closed by a CTA.
	 *
	 * @since 4.9.0
	 */
	private function display_features() {

		?>
		<div class="wpms:bg-[#fcf9e8] wpms:rounded-[4px] wpms:px-[40px] wpms:py-[40px]">
			<div class="wpms:flex wpms:flex-col wpms:gap-[30px]">

				<h2 class="wpms:text-center">
					<?php esc_html_e( 'Features you\'ll love with Pro', 'wp-mail-smtp' ); ?>
				</h2>

				<div class="wpms:grid wpms:grid-cols-3 wpms:gap-x-[20px] wpms:gap-y-[30px] wpms:max-tablet:grid-cols-1">
					<?php foreach ( $this->get_feature_cards() as $card ) : ?>
						<div class="wpms:flex wpms:flex-col wpms:gap-[20px] wpms:bg-white wpms:rounded-[8px] wpms:border wpms:border-[#dcdcde] wpms:shadow-subtle wpms:overflow-hidden">
							<img
								src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/education/get-pro-tab/' . $card['image'] . '.svg' ); ?>"
								alt=""
								class="wpms:w-full wpms:h-auto"
							>
							<div class="wpms:flex wpms:flex-col wpms:gap-[8px] wpms:px-[36px] wpms:pb-[32px]">
								<h3>
									<?php echo esc_html( $card['title'] ); ?>
								</h3>
								<p class="wpms:text-[14px]!">
									<?php echo esc_html( $card['description'] ); ?>
								</p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="wpms:flex wpms:flex-col wpms:gap-[8px] wpms:items-center">
					<?php $this->display_upgrade_button( 'Features Grid' ); ?>
					<?php $this->display_discount_badge(); ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Lite vs Pro comparison: heading, twelve-row table and the closing CTA.
	 *
	 * @since 4.9.0
	 */
	private function display_comparison() {

		?>
		<div class="wpms:px-[40px] wpms:py-[40px]">
			<div class="wpms:flex wpms:flex-col wpms:gap-[30px] wpms:items-center">

				<div class="wpms:flex wpms:flex-col wpms:gap-[20px] wpms:items-center wpms:text-center">
					<h2>
						<?php esc_html_e( 'Lite vs Pro: What\'s the Difference?', 'wp-mail-smtp' ); ?>
					</h2>
					<p class="wpms:max-w-[467px]">
						<?php esc_html_e( 'Get the most out of WP Mail SMTP by upgrading to Pro and unlocking all of the powerful features.', 'wp-mail-smtp' ); ?>
					</p>
				</div>

				<div id="wp-mail-smtp-get-pro-comparison" class="wpms:w-full wpms:overflow-x-auto">
					<div class="wpms:min-w-[1010px] wpms:rounded-[8px] wpms:border wpms:border-[#dcdcde] wpms:overflow-hidden">
						<?php $this->display_comparison_header(); ?>
						<?php foreach ( $this->get_comparison_rows() as $row ) : ?>
							<?php $this->display_comparison_row( $row ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wpms:flex wpms:flex-col wpms:gap-[8px] wpms:items-center">
					<?php $this->display_upgrade_button( 'Bottom' ); ?>
					<?php $this->display_discount_badge(); ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Comparison table header row.
	 *
	 * @since 4.9.0
	 */
	private function display_comparison_header() {

		$cell  = 'wpms:flex wpms:items-start wpms:p-[20px] wpms:bg-[#f3f4f6] wpms:border-b wpms:border-l wpms:border-[#f3f4f6]';
		$label = 'wpms:m-0! wpms:text-[20px]! wpms:font-bold! wpms:leading-[28px]! wpms:tracking-[0.25px] wpms:text-gray-900!';
		?>
		<div class="wpms:flex wpms:items-stretch">
			<div class="<?php echo esc_attr( $cell ); ?> wpms:w-[310px] wpms:shrink-0">
				<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Feature', 'wp-mail-smtp' ); ?></p>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> wpms:w-[400px] wpms:shrink-0">
				<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Lite', 'wp-mail-smtp' ); ?></p>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> wpms:border-r wpms:flex-1 wpms:min-w-[300px]">
				<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Pro', 'wp-mail-smtp' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * A single comparison table row (feature label + Lite cell + Pro cell).
	 *
	 * @since 4.9.0
	 *
	 * @param array $row Row data: feature label plus `lite` and `pro` cell arrays.
	 */
	private function display_comparison_row( $row ) {

		$cell = 'wpms:flex wpms:items-start wpms:p-[20px] wpms:bg-white wpms:border-b wpms:border-l wpms:border-[#f3f4f6]';
		?>
		<div class="wpms:flex wpms:items-stretch">
			<div class="<?php echo esc_attr( $cell ); ?> wpms:w-[310px] wpms:shrink-0">
				<p class="wpms:m-0! wpms:text-[16px]! wpms:font-medium! wpms:leading-[24px]! wpms:tracking-[0.25px] wpms:text-gray-700">
					<?php echo esc_html( $row['feature'] ); ?>
				</p>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> wpms:w-[400px] wpms:shrink-0">
				<?php $this->display_comparison_cell( $row['lite'] ); ?>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> wpms:border-r wpms:flex-1 wpms:min-w-[300px]">
				<?php $this->display_comparison_cell( $row['pro'] ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Inner content of a comparison cell: a status icon plus its copy. The
	 * lead sentence is emphasized; any follow-up detail renders below it.
	 *
	 * @since 4.9.0
	 *
	 * @param array $cell Cell data: `status`, `lead` and optional `rest`.
	 */
	private function display_comparison_cell( $cell ) {

		?>
		<div class="wpms:flex wpms:gap-[8px] wpms:items-start">
			<?php $this->display_status_icon( $cell['status'] ); ?>
			<p class="wpms:m-0! wpms:text-[16px]! wpms:leading-[24px]! wpms:tracking-[0.25px] wpms:text-gray-700">
				<strong class="wpms:font-bold!"><?php echo esc_html( $cell['lead'] ); ?></strong><?php if ( ! empty( $cell['rest'] ) ) : ?><br><?php echo esc_html( $cell['rest'] ); ?><?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render a comparison status icon for the given availability state.
	 *
	 * @since 4.9.0
	 *
	 * @param string $status One of `full`, `partial`, `none`.
	 */
	private function display_status_icon( $status ) {

		$icons = [
			'full'    => [ 'file' => 'status-full', 'label' => esc_attr__( 'Available', 'wp-mail-smtp' ) ],
			'partial' => [ 'file' => 'status-partial', 'label' => esc_attr__( 'Partially available', 'wp-mail-smtp' ) ],
			'none'    => [ 'file' => 'status-none', 'label' => esc_attr__( 'Not available', 'wp-mail-smtp' ) ],
		];

		if ( ! isset( $icons[ $status ] ) ) {
			return;
		}

		$icon = $icons[ $status ];
		?>
		<img
			src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/education/get-pro-tab/' . $icon['file'] . '.svg' ); ?>"
			alt="<?php echo esc_attr( $icon['label'] ); ?>"
			width="24"
			height="24"
			class="wpms:w-[24px] wpms:h-[24px] wpms:shrink-0"
		>
		<?php
	}

	/**
	 * Primary "Upgrade to Pro" button, carrying the Lite upgrade discount.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content UTM content tag identifying the button placement.
	 */
	private function display_upgrade_button( $content ) {

		$url = add_query_arg(
			'discount',
			'SMTPLITEUPGRADE',
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'get-pro-tab', 'content' => $content ] )
		);
		?>
		<a
			href="<?php echo esc_url( $url ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			class="wp-mail-smtp-btn wp-mail-smtp-btn-cta-large wp-mail-smtp-btn-orange wpms:w-[300px] wpms:rounded-[4px]"
		>
			<?php esc_html_e( 'Upgrade to Pro', 'wp-mail-smtp' ); ?>
		</a>
		<?php
	}

	/**
	 * Discount badge accompanying every upgrade button.
	 *
	 * @since 4.9.0
	 */
	private function display_discount_badge() {

		?>
		<div class="wpms:flex wpms:items-center wpms:gap-[5px]">
			<span aria-hidden="true" class="wpms:icon-[custom--badge-percent] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
			<span class="wpms:text-[14px]! wpms:leading-[20px]!">
				<span class="wpms:font-semibold wpms:text-success"><?php esc_html_e( '$50 OFF', 'wp-mail-smtp' ); ?></span>
				<span class="wpms:text-tertiary"><?php esc_html_e( 'for WP Mail SMTP Lite users', 'wp-mail-smtp' ); ?></span>
			</span>
		</div>
		<?php
	}

	/**
	 * The six feature cards shown above the comparison table.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function get_feature_cards() {

		return [
			[
				'image'       => 'card-email-logs',
				'title'       => esc_html__( 'Email Logs', 'wp-mail-smtp' ),
				'description' => esc_html__( 'Save details about every email sent from your WordPress site.', 'wp-mail-smtp' ),
			],
			[
				'image'       => 'card-email-failure-alerts',
				'title'       => esc_html__( 'Email Failure Alerts', 'wp-mail-smtp' ),
				'description' => esc_html__( 'Receive immediate alerts for email failures to quickly address issues.', 'wp-mail-smtp' ),
			],
			[
				'image'       => 'card-backup-connections',
				'title'       => esc_html__( 'Backup Connections', 'wp-mail-smtp' ),
				'description' => esc_html__( 'Set up a secondary email provider in case your primary provider fails.', 'wp-mail-smtp' ),
			],
			[
				'image'       => 'card-one-click-mailer-setups',
				'title'       => esc_html__( 'One-Click Mailer Setups', 'wp-mail-smtp' ),
				'description' => esc_html__( 'An easy and secure way to configure your Gmail and Outlook mailers.', 'wp-mail-smtp' ),
			],
			[
				'image'       => 'card-smart-routing',
				'title'       => esc_html__( 'Smart Routing', 'wp-mail-smtp' ),
				'description' => esc_html__( 'Use conditional logic to send emails through different providers.', 'wp-mail-smtp' ),
			],
			[
				'image'       => 'card-advanced-mailers',
				'title'       => esc_html__( 'Advanced mailers', 'wp-mail-smtp' ),
				'description' => esc_html__( 'Connect with top providers like Amazon SES, Microsoft 365 / Outlook, and Zoho Mail.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Twelve-row Lite vs Pro feature comparison.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function get_comparison_rows() {

		$not_available = [
			'status' => 'none',
			'lead'   => esc_html__( 'Not available', 'wp-mail-smtp' ),
			'rest'   => '',
		];

		return [
			[
				'feature' => esc_html__( 'Improved Email Deliverability', 'wp-mail-smtp' ),
				'lite'    => [
					'status' => 'partial',
					'lead'   => esc_html__( 'Connect your WordPress site to any of the following SMTP providers:', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'SendLayer, SMTP.com, Brevo, Google Workspace / Gmail, Mailgun, Postmark, SendGrid, SMTP2GO, Sparkpost, and any other SMTP provider using your SMTP credentials.', 'wp-mail-smtp' ),
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Connect your WordPress site to any of our Lite SMTP providers, as well as:', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'Amazon SES, Microsoft 365 / Outlook, and Zoho Mail.', 'wp-mail-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Weekly Email Summaries', 'wp-mail-smtp' ),
				'lite'    => [
					'status' => 'partial',
					'lead'   => esc_html__( 'Receive a basic report of your total emails sent including a summary of the previous week\'s sent emails.', 'wp-mail-smtp' ),
					'rest'   => '',
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Receive a detailed report of your total and previous week\'s metrics for emails sent, failed, and delivered, plus statistics for your top 5 popular emails.', 'wp-mail-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Email Error Tracking', 'wp-mail-smtp' ),
				'lite'    => [
					'status' => 'full',
					'lead'   => esc_html__( 'View email sending errors in your WordPress dashboard.', 'wp-mail-smtp' ),
					'rest'   => '',
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'View email sending errors in your WordPress dashboard.', 'wp-mail-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Google Workspace', 'wp-mail-smtp' ),
				'lite'    => [
					'status' => 'partial',
					'lead'   => esc_html__( 'Manually connect to your account with Google Workspace/ Gmail mailer', 'wp-mail-smtp' ),
					'rest'   => '',
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Access your Google Workspace immediately with Gmail One Click Setup', 'wp-mail-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Outlook', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Access Outlook immediately with Outlook One Click Setup', 'wp-mail-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Email Logging', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'View detailed email logs in your WordPress dashboard.', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'Includes delivery status, email content, attachments, source, and technical details, plus the option to resend the email.', 'wp-mail-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Instant Email Alerts', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Receive an alert via your preferred channel whenever an email fails to send.', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'Email, Slack, SMS via Twilio, Microsoft Teams, Discord, or custom webhooks.', 'wp-mail-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Backup Connections', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Connect to multiple SMTP providers.', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'Choose one primary connection, then select another connection as a backup. If an email fails to send via your primary connection, it will automatically be resent using your backup connection.', 'wp-mail-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Manage Email Notifications', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Choose which default WordPress email notifications your site sends.', 'wp-mail-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Open & Click Tracking', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Monitor email open and click-through rates.', 'wp-mail-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Email Reports', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'View advanced email reports in your WordPress dashboard.', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'Includes total number of emails sent, emails delivered, failed emails, opened emails, links clicked.', 'wp-mail-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Smart Routing', 'wp-mail-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Send emails through more than one SMTP provider.', 'wp-mail-smtp' ),
					'rest'   => esc_html__( 'Connect WP Mail SMTP to multiple providers, then route emails through your preferred provider based on custom conditional logic rules.', 'wp-mail-smtp' ),
				],
			],
		];
	}
}
