<?php

namespace WPMailSMTP;

/**
 * Class Conflicts
 *
 * @since 1.5.0
 */
class Conflicts {

	/**
	 * @since 1.5.0
	 *
	 * @var array List of plugins WP Mail SMTP may be conflicting with.
	 */
	public static $plugins = array(
		'swpsmtp_init_smtp'    => array(
			'name' => 'Easy WP SMTP',
		),
		'postman_start'        => array(
			'name' => 'Postman SMTP',
		),
		'post_start'           => array(
			'name' => 'Post SMTP Mailer/Email Log',
		),
		'mail_bank'            => array(
			'name' => 'WP Mail Bank',
		),
		'SMTP_MAILER'          => array(
			'name'  => 'SMTP Mailer',
			'class' => true,
		),
		'GMAIL_SMTP'           => array(
			'name'  => 'Gmail SMTP',
			'class' => true,
		),
		'WP_Email_Smtp'        => array(
			'name'  => 'WP Email SMTP',
			'class' => true,
		),
		'smtpmail_include'     => array(
			'name' => 'SMTP Mail',
		),
		'bwssmtp_init'         => array(
			'name' => 'SMTP by BestWebSoft',
		),
		'WPSendGrid_SMTP'      => array(
			'name'  => 'WP SendGrid SMTP',
			'class' => true,
		),
		'sar_friendly_smtp'    => array(
			'name' => 'SAR Friendly SMTP',
		),
		'WPGmail_SMTP'         => array(
			'name'  => 'WP Gmail SMTP',
			'class' => true,
		),
		'st_smtp_check_config' => array(
			'name' => 'Cimy Swift SMTP',
		),
		'WP_Easy_SMTP'         => array(
			'name'  => 'WP Easy SMTP',
			'class' => true,
		),
		'WPMailgun_SMTP'       => array(
			'name'  => 'WP Mailgun SMTP',
			'class' => true,
		),
		'my_smtp_wp'           => array(
			'name' => 'MY SMTP WP',
		),
		'mail_booster'         => array(
			'name' => 'WP Mail Booster',
		),
		'Sendgrid_Settings'    => array(
			'name'  => 'SendGrid',
			'class' => true,
		),
		'WPMS_php_mailer'      => array(
			'name' => 'WP Mail Smtp Mailer',
		),
		'WPAmazonSES_SMTP'     => array(
			'name'  => 'WP Amazon SES SMTP',
			'class' => true,
		),
		'Postmark_Mail'        => array(
			'name'  => 'Postmark for WordPress',
			'class' => true,
		),
		'Mailgun'              => array(
			'name'  => 'Mailgun',
			'class' => true,
		),
		'SparkPost'            => array(
			'name'  => 'SparkPost',
			'class' => true,
		),
		'WPYahoo_SMTP'         => array(
			'name'  => 'WP Yahoo SMTP',
			'class' => true,
		),
		'wpses_init'           => array(
			'name'  => 'WP SES',
			'class' => true,
		),
		'TSPHPMailer'          => array(
			'name' => 'turboSMTP',
		),
		'WP_SMTP'              => array(
			'name'  => 'WP SMTP',
			'class' => true,
		),
	);

	/**
	 * @var array Conflict information.
	 */
	protected $conflict = array();

	/**
	 * Whether we have a conflict with predefined list of plugins.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_detected() {

		foreach ( self::$plugins as $callback => $plugin ) {
			if ( ! empty( $plugin['class'] ) ) {
				$detected = \class_exists( $callback, false );
			} else {
				$detected = \function_exists( $callback );
			}

			if ( $detected ) {
				$this->conflict = $plugin;
				break;
			}
		}

		return ! empty( $this->conflict );
	}

	/**
	 * Add a warning admin message to a user about the conflicting plugin.
	 *
	 * @since 1.5.0
	 */
	public function notify() {

		if ( empty( $this->conflict ) ) {
			return;
		}

		WP::add_admin_notice(
			\sprintf(
				/* translators: %1$s - Plugin name causing conflict; %2$s - Plugin name causing conflict. */
				\esc_html__( 'Heads up! WP Mail SMTP has detected %1$s is activated. Please deactivate %2$s to prevent conflicts.', 'wp-mail-smtp' ),
				$this->get_conflict_name(),
				$this->get_conflict_name()
			),
			WP::ADMIN_NOTICE_WARNING
		);
	}

	/**
	 * Get the conflicting plugin name is any.
	 *
	 * @since 1.5.0
	 *
	 * @return null|string
	 */
	public function get_conflict_name() {

		$name = null;

		if ( ! empty( $this->conflict['name'] ) ) {
			$name = $this->conflict['name'];
		}

		return $name;
	}
}
