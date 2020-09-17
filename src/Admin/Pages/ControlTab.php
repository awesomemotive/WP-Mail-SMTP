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
	 * {@inheritdoc}
	 *
	 * @since 2.1.0 Replaced images with SVGs.
	 */
	public function display() {

		$features = [
			[
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 64 64"><path class="st0" d="M39.1,35.5H18l-9.2,6.9c-0.5,0.4-1.2,0.3-1.5-0.2c-0.1-0.2-0.2-0.4-0.2-0.6v-6c-3.9,0-7.1-3.2-7.1-7.1V10.7c0-3.9,3.2-7.1,7.1-7.1h32c3.9,0,7.1,3.2,7.1,7.1v17.8C46.2,32.4,43,35.5,39.1,35.5C39.1,35.5,39.1,35.5,39.1,35.5z"/><path class="st1" d="M64,28.4v17.8c0,3.9-3.2,7.1-7.1,7.1h-3.6v6c0,0.6-0.5,1.1-1.1,1.1c-0.2,0-0.5-0.1-0.6-0.2l-9.2-6.9h-14c-3.9,0-7.1-3.2-7.1-7.1v-7.1h17.8c5.9,0,10.7-4.8,10.7-10.7v-7.1h7.1C60.8,21.3,64,24.5,64,28.4z"/></svg>',
				'title' => esc_html__( 'Comment Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Manage emails sent when comments are published or awaiting moderation.', 'wp-mail-smtp' ),
			],
			[
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 64 64"><path class="st0" d="M63.6 45.2l-2.6-1.5c0.3-1.4 0.3-2.9 0-4.3l2.6-1.5c0.3-0.2 0.4-0.5 0.3-0.9 -0.7-2.1-1.8-4.1-3.3-5.7 -0.2-0.3-0.6-0.3-0.9-0.1l-2.6 1.5c-1.1-0.9-2.3-1.7-3.7-2.1v-3c0-0.3-0.2-0.6-0.6-0.7 -2.2-0.5-4.4-0.5-6.6 0 -0.3 0.1-0.6 0.4-0.6 0.7v3c-1.4 0.5-2.6 1.2-3.7 2.1l-2.6-1.5c-0.3-0.2-0.7-0.1-0.9 0.1C37 33 35.9 35 35.2 37.1c-0.1 0.3 0 0.7 0.3 0.9l2.6 1.5c-0.3 1.4-0.3 2.9 0 4.3l-2.6 1.5c-0.3 0.2-0.4 0.5-0.3 0.9 0.7 2.1 1.8 4.1 3.3 5.7 0.2 0.3 0.6 0.3 0.9 0.1l2.6-1.5c1.1 0.9 2.3 1.7 3.7 2.1v3c0 0.3 0.2 0.6 0.6 0.7 2.2 0.5 4.4 0.5 6.6 0 0.3-0.1 0.6-0.4 0.6-0.7v-3c1.4-0.5 2.6-1.2 3.7-2.1l2.6 1.5c0.3 0.2 0.7 0.1 0.9-0.1 1.5-1.6 2.7-3.6 3.3-5.7C64.1 45.7 63.9 45.4 63.6 45.2zM49.6 46.5c-2.7 0-4.9-2.2-4.9-4.9s2.2-4.9 4.9-4.9c2.7 0 4.9 2.2 4.9 4.9S52.3 46.5 49.6 46.5z"/><path class="st1" d="M42.5 55.6v-0.9c-0.2-0.1-0.5-0.3-0.7-0.4l-0.8 0.5c-1.6 0.9-3.6 0.6-4.9-0.7 -1.8-2-3.2-4.4-4-7 -0.6-1.8 0.2-3.7 1.8-4.6l0.8-0.5c0-0.3 0-0.5 0-0.8L34 40.8c-1.6-0.9-2.3-2.8-1.8-4.6 0.1-0.3 0.2-0.6 0.3-0.9 -0.4 0-0.8-0.1-1.1-0.1h-1.7c-4.6 2.1-10 2.1-14.6 0h-1.7C6 35.2 0 41.2 0 48.6v4.2c0 2.7 2.2 4.8 4.8 4.8l0 0H40c1 0 1.9-0.3 2.7-0.9C42.6 56.4 42.5 56 42.5 55.6zM22.4 32c7.1 0 12.8-5.7 12.8-12.8S29.5 6.4 22.4 6.4 9.6 12.1 9.6 19.2 15.3 32 22.4 32z"/></svg>',
				'title' => esc_html__( 'Site Admin Email Change Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Manage emails sent when site admin\'s account has been changed.', 'wp-mail-smtp' ),
			],
			[
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 64 64"><path class="st0" d="M9.6 28.8c3.5 0 6.4-2.9 6.4-6.4S13.1 16 9.6 16s-6.4 2.9-6.4 6.4S6.1 28.8 9.6 28.8zM57.6 32h-6.4c-1.7 0-3.3 0.7-4.5 1.9 4.1 2.2 6.9 6.3 7.5 10.9h6.6c1.8 0 3.2-1.4 3.2-3.2v-3.2C64 34.9 61.1 32 57.6 32zM6.4 32C2.9 32 0 34.9 0 38.4v3.2c0 1.8 1.4 3.2 3.2 3.2h6.6c0.6-4.6 3.4-8.7 7.5-10.9 -1.2-1.2-2.8-1.9-4.5-1.9H6.4zM54.4 28.8c3.5 0 6.4-2.9 6.4-6.4S57.9 16 54.4 16 48 18.9 48 22.4 50.9 28.8 54.4 28.8z"/><path class="st1" d="M39.7 35.2h-0.8c-2.1 1-4.5 1.6-6.8 1.6 -2.5 0-4.8-0.6-6.9-1.6h-0.8c-6.4 0-11.5 5.2-11.5 11.5v2.9c0 2.7 2.1 4.8 4.8 4.8h28.8c2.6 0 4.8-2.2 4.8-4.8v-2.9C51.2 40.4 46 35.2 39.7 35.2zM32 32c6.2 0 11.2-5 11.2-11.2S38.2 9.6 32 9.6s-11.2 5-11.2 11.2C20.8 27 25.8 32 32 32L32 32z"/></svg>',
				'title' => esc_html__( 'User Change Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Limit emails triggered by password changed/reset, email changed, and more.', 'wp-mail-smtp' ),
			],
			[
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 64 64"><path class="st0" d="M35.9 52.6L32 60l-3.9-7.4L30 44l-2.2-4.5c2.8 0.6 5.7 0.6 8.5 0L34 44 35.9 52.6zM32 35.9c8.8 0 16-7.1 16-15.9 -0.9 0.2-1.9 0.4-3 0.5v0.8c0 0-0.8 0.4-0.9 0.8 -0.5 1.6-1 3.3-2.2 4.5 -1.4 1.3-6.5 3-8.7-3.4 -0.4-1.1-2.1-1.1-2.5 0 -2.3 6.8-7.6 4.4-8.7 3.4 -1.3-1.2-1.7-2.9-2.2-4.5 -0.1-0.3-0.9-0.8-0.9-0.8v-0.8c-1.1-0.2-2.1-0.3-3-0.5C16 28.8 23.2 35.9 32 35.9z"/><path class="st1" d="M19 20.5v0.8c0 0 0.8 0.5 0.9 0.8 0.5 1.6 1 3.3 2.2 4.5 1.1 1 6.4 3.4 8.7-3.4 0.4-1.1 2.1-1.1 2.5 0 2.2 6.4 7.3 4.7 8.7 3.4 1.3-1.2 1.7-2.9 2.2-4.5 0.1-0.4 0.9-0.8 0.9-0.8v-0.8c6.6-1 11-2.7 11-4.6 0-1.7-3.4-3.3-8.8-4.3 -1.2-4-3.3-8-5-10.1C41 0 39-0.4 37.2 0.4l-3.5 1.7c-1.1 0.6-2.5 0.6-3.6 0l-3.5-1.7C25-0.4 23 0 21.8 1.5c-1.7 2.1-3.8 6.1-5 10.1 -5.4 1-8.8 2.5-8.8 4.3C8 17.8 12.3 19.6 19 20.5zM52 38.5l3-7.8c0.4-1-0.1-2.2-1.2-2.6 -0.2-0.1-0.5-0.1-0.7-0.1h-4L32 60 14.9 27.9H11c-1.1 0-2 0.9-2 2 0 0.3 0.1 0.5 0.2 0.8l3.2 7.5c-5.2 3-8.4 8.5-8.4 14.5V58c0 3.3 2.7 6 6 6l0 0H54c3.3 0 6-2.7 6-6l0 0v-5.2C60.1 46.9 57 41.5 52 38.5L52 38.5z"/></svg>',
				'title' => esc_html__( 'Personal Data Requests Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Control emails for data requests and data removal actions.', 'wp-mail-smtp' ),
			],
			[
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 64 64"><path class="st0" d="M0 57.6V40.3c0-1.7 1.4-3.1 3.1-3.1h17.3c2.8 0 4.1 3.3 2.2 5.3l-5.4 5.4c4 3.8 9.3 5.9 14.8 5.8 10 0 18.6-6.9 21-16.4 0.2-0.7 0.8-1.2 1.5-1.2h7.4c0.9 0 1.5 0.7 1.5 1.5 0 0.1 0 0.2 0 0.3C60.7 52.8 47.6 64 32 64c-8.2 0-16.2-3.2-22.1-8.9l-4.6 4.6C3.3 61.7 0 60.3 0 57.6z"/><path class="st1" d="M0.6 26C3.3 11.2 16.4 0 32 0c8.2 0 16.2 3.2 22.1 8.9l4.6-4.6C60.7 2.3 64 3.7 64 6.5v17.3c0 1.7-1.4 3.1-3.1 3.1H43.6c-2.8 0-4.1-3.3-2.2-5.3l5.4-5.4c-4-3.8-9.3-5.9-14.8-5.8 -10 0-18.6 6.9-21 16.4 -0.2 0.7-0.8 1.2-1.5 1.2H2.1c-0.9 0-1.5-0.7-1.5-1.5C0.5 26.2 0.5 26.1 0.6 26z"/></svg>',
				'title' => esc_html__( 'Automatic Update Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Manage emails sent by the core automatic update process.', 'wp-mail-smtp' ),
			],
			[
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 64 64"><path class="st0" d="M64 28.8V32c0 0.9-0.7 1.6-1.6 1.6H56V40c0 0.9-0.7 1.6-1.6 1.6h-3.2c-0.9 0-1.6-0.7-1.6-1.6v-6.4h-6.4c-0.9 0-1.6-0.7-1.6-1.6v-3.2c0-0.9 0.7-1.6 1.6-1.6h6.4v-6.4c0-0.9 0.7-1.6 1.6-1.6h3.2c0.9 0 1.6 0.7 1.6 1.6v6.4h6.4C63.3 27.2 64 27.9 64 28.8z"/><path class="st1" d="M22.4 32c7.1 0 12.8-5.7 12.8-12.8S29.5 6.4 22.4 6.4 9.6 12.1 9.6 19.2 15.3 32 22.4 32zM31.4 35.2h-1.7c-4.6 2.1-9.9 2.1-14.6 0h-1.7C6 35.2 0 41.2 0 48.6v4.2c0 2.7 2.2 4.8 4.8 4.8l0 0H40c2.7 0 4.8-2.1 4.8-4.8l0 0v-4.2C44.8 41.2 38.8 35.2 31.4 35.2z"/></svg>',
				'title' => esc_html__( 'New User Notifications', 'wp-mail-smtp' ),
				'desc'  => esc_html__( 'Toggle emails sent to both user and site administrator about new user accounts.', 'wp-mail-smtp' ),
			],
		];

		$allowed_svg_html = [
			'svg'  => [
				'xmlns'     => [],
				'focusable' => [],
				'viewbox'   => [],
			],
			'path' => [
				'class' => [],
				'd'     => [],
			],
		];
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
								<?php echo wp_kses( $feature['svg'], $allowed_svg_html ); ?>
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
				<a href="<?php echo esc_url( add_query_arg( 'discount', 'LITEUPGRADE', wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'logs', 'content' => '' ] ) ) ); // phpcs:ignore ?>"
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
