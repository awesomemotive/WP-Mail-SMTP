<?php

namespace WPMailSMTP;

/**
 * Class Processor modifies the behaviour of wp_mail() function.
 *
 * @since 1.0.0
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
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ), 11 );
	}

	/**
	 * Redefine certain PHPMailer options with our custom ones.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHPMailer $phpmailer It's passed by reference, so no need to return anything.
	 */
	public function phpmailer_init( $phpmailer ) {

		$options = new Options();
		$mailer  = $options->get( 'mail', 'mailer' );

		// Check that mailer is not blank, and if mailer=smtp, host is not blank.
		if (
			! $mailer ||
			( 'smtp' === $mailer && ! $options->get( 'smtp', 'host' ) )
		) {
			return;
		}

		// If the mailer is pepipost, make sure we have a username and password.
		if (
			'pepipost' === $mailer &&
			( ! $options->get( 'pepipost', 'user' ) && ! $options->get( 'pepipost', 'pass' ) )
		) {
			return;
		}

		// Set the mailer type as per config above, this overrides the already called isMail method.
		// It's basically always 'smtp'.
		$phpmailer->Mailer = $mailer;

		// Set the Sender (return-path) if required.
		if ( $options->get( 'mail', 'return_path' ) ) {
			$phpmailer->Sender = $phpmailer->From;
		}

		// Set the SMTPSecure value, if set to none, leave this blank. Possible values: 'ssl', 'tls', ''.
		if ( 'none' === $options->get( $mailer, 'encryption' ) ) {
			$phpmailer->SMTPSecure = '';
		} else {
			$phpmailer->SMTPSecure = $options->get( $mailer, 'encryption' );
		}

		// Check if user has disabled SMTPAutoTLS.
		if ( $options->get( $mailer, 'encryption' ) !== 'tls' && ! $options->get( $mailer, 'autotls' ) ) {
			$phpmailer->SMTPAutoTLS = false;
		}

		// If we're sending via SMTP, set the host.
		if ( 'smtp' === $mailer ) {
			// Set the other options.
			$phpmailer->Host = $options->get( $mailer, 'host' );
			$phpmailer->Port = $options->get( $mailer, 'port' );

			// If we're using smtp auth, set the username & password.
			if ( $options->get( $mailer, 'auth' ) ) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->Username = $options->get( $mailer, 'user' );
				$phpmailer->Password = $options->get( $mailer, 'pass' );
			}
		} elseif ( 'pepipost' === $mailer ) {
			// Set the Pepipost settings for BC.
			$phpmailer->Mailer     = 'smtp';
			$phpmailer->Host       = 'smtp.pepipost.com';
			$phpmailer->Port       = $options->get( $mailer, 'port' );
			$phpmailer->SMTPSecure = $options->get( $mailer, 'encryption' ) === 'none' ? '' : $options->get( $mailer, 'encryption' );
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = $options->get( $mailer, 'user' );
			$phpmailer->Password   = $options->get( $mailer, 'pass' );
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
