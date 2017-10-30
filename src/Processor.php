<?php

namespace WPMailSMTP;

/**
 * Class Processor modifies the behaviour of wp_mail() function.
 */
class Processor {

	/**
	 * Processor constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Assign all hooks to proper places.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_action( 'phpmailer_init', array( $this, 'phpmailer_init' ) );

		add_filter( 'wp_mail_from', array( $this, 'filter_mail_from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ) );
	}

	/**
	 * Redefine certain PHPMailer options with our custom ones.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHPMailer $phpmailer It's passed by reference, so no need to return anything.
	 */
	public function phpmailer_init( $phpmailer ) {

		$options = Options::init()->get_all();

		// Check that mailer is not blank, and if mailer=smtp, host is not blank.
		if (
			! $options['mail']['mailer'] ||
			( 'smtp' === $options['mail']['mailer'] && ! $options['smtp']['host'] )
		) {
			return;
		}

		// If the mailer is pepipost, make sure we have a username and password.
		if (
			'pepipost' === $options['mail']['mailer'] &&
			( ! $options['pepipost']['user'] && ! $options['pepipost']['pass'] )
		) {
			return;
		}

		// Set the mailer type as per config above, this overrides the already called isMail method.
		$phpmailer->Mailer = $options['mail']['mailer'];

		// Set the Sender (return-path) if required.
		if ( $options['mail']['return_path'] ) {
			$phpmailer->Sender = $phpmailer->From;
		}

		// Set the SMTPSecure value, if set to none, leave this blank.
		$phpmailer->SMTPSecure = $options['smtp']['encryption'];
		if ( 'none' === $options['smtp']['encryption'] ) {
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		}

		// If we're sending via SMTP, set the host.
		if ( 'smtp' === $options['mail']['mailer'] ) {
			// Set the other options.
			$phpmailer->Host = $options['smtp']['host'];
			$phpmailer->Port = $options['smtp']['port'];

			// If we're using smtp auth, set the username & password.
			if ( $options['smtp']['auth'] ) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->Username = $options['smtp']['user'];
				$phpmailer->Password = $options['smtp']['pass'];
			}
		} elseif ( 'pepipost' === $options['mail']['mailer'] ) {
			// Set the Pepipost settings for BC.
			$phpmailer->Mailer     = 'smtp';
			$phpmailer->Host       = 'smtp.pepipost.com';
			$phpmailer->Port       = $options['pepipost']['port'];
			$phpmailer->SMTPSecure = $options['pepipost']['encryption'] === 'none' ? '' : $options['pepipost']['encryption'];
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = $options['pepipost']['user'];
			$phpmailer->Password   = $options['pepipost']['pass'];
		}

		// You can add your own options here.
		// See the phpmailer documentation for more info: https://github.com/PHPMailer/PHPMailer/tree/5.2-stable.
		/** @noinspection PhpUnusedLocalVariableInspection It's passed by reference. */
		$phpmailer = apply_filters( 'wp_mail_smtp_custom_options', $phpmailer );
	}

	/**
	 * Modify the email address that is used for sending emails.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	public function filter_mail_from_email( $email ) {

		// If the from email is not the default, return it unchanged.
		if ( $email !== $this->get_default_email() ) {
			return $email;
		}

		$from_email = Options::init()->get( 'mail', 'from_email' );

		if ( ! empty( $from_email ) ) {
			return $from_email;
		}

		return $email;
	}

	/**
	 * Modify the sender name that is used for sending emails.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function filter_mail_from_name( $name ) {

		if ( 'WordPress' === $name ) {
			$name = Options::init()->get( 'mail', 'from_name' );
		}

		return $name;
	}

	/**
	 * Get the default email address based on domain name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_default_email() {

		// In case of CLI we don't have SERVER_NAME, so use host name instead, may be not a domain name.
		$server_name = ! empty( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : wp_parse_url( get_home_url( get_current_blog_id() ), PHP_URL_HOST );

		// Get the site domain and get rid of www.
		$sitename = strtolower( $server_name );
		if ( substr( $sitename, 0, 4 ) === 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		return 'wordpress@' . $sitename;
	}
}
