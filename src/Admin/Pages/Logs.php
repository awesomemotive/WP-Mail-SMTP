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
}
