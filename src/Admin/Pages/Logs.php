<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\PageAbstract;

/**
 * Class Logs
 */
class Logs extends PageAbstract {

	/**
	 * @since 1.5.0
	 *
	 * @var string Slug of a page.
	 */
	protected $slug = 'logs';

	/**
	 * Get the page/tab link.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_link() {

		return add_query_arg(
			'page',
			Area::SLUG . '-' . $this->slug,
			admin_url( 'admin.php' )
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return esc_html__( 'Email Log', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return $this->get_label();
	}

	/**
	 * @inheritdoc
	 */
	public function display() {
		?>

		<div class="wp-mail-smtp-page-title">
			<h1 class="page-title">
				<?php echo esc_html( $this->get_label() ); ?>
			</h1>
		</div>

		<div class="wp-mail-smtp-page wp-mail-smtp-page-<?php echo esc_attr( $this->slug ); ?>">

			<h1 class="screen-reader-text">
				<?php echo esc_html( $this->get_label() ); ?>
			</h1>

			<div class="wp-mail-smtp-logs-upsell">
				<div class="wp-mail-smtp-logs-upsell-content">
					<h2>
						<?php esc_html_e( 'View and Manage All Sent Emails inside WordPress', 'wp-mail-smtp' ); ?>
					</h2>

					<p>
						<strong><?php esc_html_e( 'Sent emails are not stored in WP Mail SMTP Lite.', 'wp-mail-smtp' ); ?></strong><br>
						<?php esc_html_e( 'Once you upgrade to WP Mail SMTP Pro, all future sent emails will be stored in your WordPress database and displayed on this Logs screen.', 'wp-mail-smtp' ); ?>
					</p>

					<div class="wp-mail-smtp-clear">
						<ul class="left">
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'View Sent Emails in Dashboard', 'wp-mail-smtp' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'View Emails Sent Status', 'wp-mail-smtp' ); ?></li>
						</ul>
						<ul class="right">
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Filter All Emails', 'wp-mail-smtp' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Search for Specific Emails', 'wp-mail-smtp' ); ?></li>
						</ul>
						<div class="clear"></div>
					</div>
				</div>

				<div class="wp-mail-smtp-logs-upsell-button">
					<a href="https://wpmailsmtp.com/lite-upgrade/?discount=LITEUPGRADE&amp;utm_source=WordPress&amp;utm_medium=logs&amp;utm_campaign=liteplugin" class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-upgrade-modal" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Upgrade to WP Mail SMTP Pro Now', 'wp-mail-smtp' ); ?>
					</a>
					<br>
					<p style="margin: 10px 0 0;font-style:italic;font-size: 13px;">
						<?php esc_html_e( 'and start logging all emails!', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

		</div>

		<?php
	}
}
