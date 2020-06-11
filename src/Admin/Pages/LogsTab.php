<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;

/**
 * Class LogsTab is a placeholder for Lite users and redirects them to Email Log page.
 *
 * @since 1.6.0
 */
class LogsTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	protected $slug = 'logs';

	/**
	 * @inheritdoc
	 *
	 * @since 1.6.0
	 */
	public function get_label() {

		return esc_html__( 'Email Log', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.6.0
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Display the upsell content for the Email Log feature.
	 *
	 * @since 1.6.0
	 * @since 2.1.0 Moved the display content from the email log page (WP admin menu "Email Log" page).
	 */
	public function display() {
		?>

		<div class="wp-mail-smtp-page-upsell">
			<h2><?php esc_html_e( 'Unlock Email Logging', 'wp-mail-smtp' ); ?></h2>

			<h3>
				<?php esc_html_e( 'Keep track of every email sent from your WordPress site with email logging. ', 'wp-mail-smtp' ); ?><br>
				<?php esc_html_e( 'Troubleshoot sending issues, recover lost emails, and more!', 'wp-mail-smtp' ); ?>
			</h3>

			<div class="wp-mail-smtp-page-upsell-images">
				<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/logs/archive.png' ); ?>" alt="<?php esc_attr_e( 'Logs Archive Page Screenshot', 'wp-mail-smtp' ); ?>">
				<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/logs/single.png' ); ?>" alt="<?php esc_attr_e( 'Logs Single Page Screenshot', 'wp-mail-smtp' ); ?>">
			</div>

			<div class="wp-mail-smtp-page-upsell-button">
				<a href="https://wpmailsmtp.com/lite-upgrade/?discount=LITEUPGRADE&amp;utm_source=WordPress&amp;utm_medium=logs&amp;utm_campaign=liteplugin" class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-upgrade-modal" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Upgrade to WP Mail SMTP Pro', 'wp-mail-smtp' ); ?>
				</a>
			</div>

		</div>

		<?php
	}

	/**
	 * Not used as we are simply redirecting users.
	 *
	 * @since 1.6.0
	 *
	 * @param array $data
	 */
	public function process_post( $data ) {
	}
}
