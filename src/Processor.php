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

		add_filter( 'wp_mail_from', array( $this, 'filter_mail_from_email' ), 1000 );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ), 1000 );
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
	 * This method will be called every time 'smtp' and 'mail' mailers will be used to send emails.
	 *
	 * @since 1.3.0
	 * @since 1.5.0 Added a do_action() to be able to hook into.
	 *
	 * @param bool $is_sent
	 * @param array $to
	 * @param array $cc
	 * @param array $bcc
	 * @param string $subject
	 * @param string $body
	 * @param string $from
	 */
	public static function send_callback( $is_sent, $to, $cc, $bcc, $subject, $body, $from ) {

		if ( ! $is_sent ) {
			// Add mailer to the beginning and save to display later.
			Debug::set(
				'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( Options::init()->get( 'mail', 'mailer' ) )->get_title() ) . "\r\n" .
				'PHPMailer was able to connect to SMTP server but failed while trying to send an email.'
			);
		} else {
			Debug::clear();
		}

		do_action( 'wp_mail_smtp_mailcatcher_smtp_send_after', $is_sent, $to, $cc, $bcc, $subject, $body, $from );
	}

	/**
	 * Modify the email address that is used for sending emails.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Forcing email rewrite if option is selected.
	 * @since 1.7.0 Default email may be empty, so pay attention to that as well.
	 *
	 * @param string $wp_email
	 *
	 * @return string
	 */
	public function filter_mail_from_email( $wp_email ) {

		$options    = new Options();
		$forced     = $options->get( 'mail', 'from_email_force' );
		$from_email = $options->get( 'mail', 'from_email' );
		$def_email  = $this->get_default_email();

		// Return FROM EMAIL if forced in settings.
		if ( $forced & ! empty( $from_email ) ) {
			return $from_email;
		}

		// If the FROM EMAIL is not the default, return it unchanged.
		if ( ! empty( $def_email ) && $wp_email !== $def_email ) {
			return $wp_email;
		}

		return ! empty( $from_email ) ? $from_email : $wp_email;
	}

	/**
	 * Modify the sender name that is used for sending emails.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Forcing name rewrite if option is selected.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function filter_mail_from_name( $name ) {

		$options = new Options();
		$force   = $options->get( 'mail', 'from_name_force' );

		// If the FROM NAME is not the default and not forced, return it unchanged.
		if ( ! $force && $name !== $this->get_default_name() ) {
			return $name;
		}

		$name = $options->get( 'mail', 'from_name' );

		return $name;
	}

	/**
	 * Get the default email address based on domain name.
	 *
	 * @since 1.0.0
	 * @since 1.7.0 May return an empty string.
	 *
	 * @return string Empty string when we aren't able to get the site domain (CLI, misconfigured server etc).
	 */
	public function get_default_email() {

		$server_name = Geo::get_site_domain();

		if ( empty( $server_name ) ) {
			return '';
		}

		// Get rid of www.
		$sitename = strtolower( $server_name );
		if ( substr( $sitename, 0, 4 ) === 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		return 'wordpress@' . $sitename;
	}

	/**
	 * Get the default email FROM NAME generated by WordPress.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function get_default_name() {
		return 'WordPress';
	}

	/**
	 * Get or create the phpmailer.
	 *
	 * @since {VERSION}
	 *
	 * @return \WPMailSMTP\MailCatcher
	 */
	public function get_phpmailer() {

		global $phpmailer;

		// Make sure the PHPMailer class has been instantiated.
		if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$phpmailer = new MailCatcher( true ); // phpcs:ignore
		}

		return $phpmailer;
	}
}
