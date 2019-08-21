<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;

/**
 * Class ControlTab is a placeholder for Pro Email Control tab settings.
 * Displays an upsell.
 *
 * @since 1.6.0
 */
class ControlTab extends PageAbstract {

	/**
	 * @since 1.6.0
	 *
	 * @var string Slug of a tab.
	 */
	protected $slug = 'control';

	/**
	 * @inheritdoc
	 */
	public function get_label() {

		return esc_html__( 'Email Controls', 'wp-mail-smtp' );
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

		$features = array(
			array(
				'image' => 'comments.png',
				'title' => esc_html__( 'Comment Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Manage emails sent when comments are published or awaiting moderation.', 'wp-mail-smtp' ),
			),
			array(
				'image' => 'admin.png',
				'title' => esc_html__( 'Site Admin Email Change Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Manage emails sent when site admin\'s account has been changed.', 'wp-mail-smtp' ),
			),
			array(
				'image' => 'users.png',
				'title' => esc_html__( 'User Change Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Limit emails triggered by password changed/reset, email changed, and more.', 'wp-mail-smtp' ),
			),
			array(
				'image' => 'personal.png',
				'title' => esc_html__( 'Personal Data Requests Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Control emails for data requests and data removal actions.', 'wp-mail-smtp' ),
			),
			array(
				'image' => 'update.png',
				'title' => esc_html__( 'Automatic Update Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Manage emails sent by the core automatic update process.', 'wp-mail-smtp' ),
			),
			array(
				'image' => 'user_new.png',
				'title' => esc_html__( 'New User Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Toggle emails sent to both user and site administrator about new user accounts.', 'wp-mail-smtp' ),
			),

		)
		?>

		<div class="wp-mail-smtp-page-upsell">
			<h2><?php esc_html_e( 'Unlock Email Controls', 'wp-mail-smtp' ); ?></h2>

			<h3>
				<?php esc_html_e( 'Email Controls allows you to granularly manage emails sent by WordPress. ', 'wp-mail-smtp' ); ?>
			</h3>

			<div class="wp-mail-smtp-page-upsell-content">

				<div class="wp-mail-smtp-page-upsell-features">
					<?php foreach ( $features as $feature ) : ?>
						<div class="wp-mail-smtp-page-upsell-feature">
							<div class="wp-mail-smtp-page-upsell-feature-image">
								<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/control/' . $feature['image'] ); ?>" alt="">
							</div>
							<div class="wp-mail-smtp-page-upsell-feature-content">
								<h4><?php echo esc_html( $feature['title'] ); ?></h4>
								<p><?php echo esc_html( $feature['desc'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			</div>

			<div class="wp-mail-smtp-page-upsell-button">
				<a href="https://wpmailsmtp.com/lite-upgrade/?discount=LITEUPGRADE&amp;utm_source=WordPress&amp;utm_medium=logs&amp;utm_campaign=liteplugin"
					class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Upgrade to WP Mail SMTP Pro', 'wp-mail-smtp' ); ?>
				</a>
			</div>

		</div>

		<?php
	}

	/**
	 * Not used as we display an upsell.
	 *
	 * @since 1.6.0
	 *
	 * @param array $data
	 */
	public function process_post( $data ) {
	}
}
