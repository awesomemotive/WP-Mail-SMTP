<?php

namespace WPMailSMTP;

/**
 * Class Processor modifies the behaviour of wp_mail() function.
 *
 * @since 1.0.0
 */
class Processor {

	/**
	 * This attribute will hold the "original" WP from email address passed to the wp_mail_from filter,
	 * that is not equal to the default email address.
	 *
	 * It should hold an email address set via the wp_mail_from filter, before we might overwrite it.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	protected $wp_mail_from;

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

		// High priority number tries to ensure our plugin code executes last and respects previous hooks, if not forced.
		add_filter( 'wp_mail_from', array( $this, 'filter_mail_from_email' ), PHP_INT_MAX );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ), PHP_INT_MAX );
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

		// Check if original WP from email can be set as the reply_to attribute.
		if ( $this->allow_setting_original_from_email_to_reply_to( $phpmailer->getReplyToAddresses(), $mailer ) ) {
			$phpmailer->addReplyTo( $this->wp_mail_from );
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

		// Maybe set default reply-to header.
		$this->set_default_reply_to( $phpmailer );

		// You can add your own options here.
		// See the phpmailer documentation for more info: https://github.com/PHPMailer/PHPMailer/tree/5.2-stable.
		/** @noinspection PhpUnusedLocalVariableInspection It's passed by reference. */
		$phpmailer = apply_filters( 'wp_mail_smtp_custom_options', $phpmailer );
	}

	/**
	 * Check if it's allowed to set the original WP from email to the reply_to field.
	 *
	 * @since 2.1.0
	 *
	 * @param array  $reply_to Array of currently set reply to emails.
	 * @param string $mailer   The slug of current mailer.
	 *
	 * @return bool
	 */
	protected function allow_setting_original_from_email_to_reply_to( $reply_to, $mailer ) {

		$options    = new Options();
		$forced     = $options->get( 'mail', 'from_email_force' );
		$from_email = $options->get( 'mail', 'from_email' );

		if ( ! empty( $reply_to ) || empty( $this->wp_mail_from ) ) {
			return false;
		}

		if ( $mailer === 'gmail' ) {
			$forced = true;
		} elseif ( $mailer === 'outlook' ) {
			$sender     = $options->get( 'outlook', 'user_details' );
			$from_email = ! empty( $sender['email'] ) ? $sender['email'] : '';
			$forced     = true;
		}

		if (
			$from_email === $this->wp_mail_from ||
			! $forced
		) {
			return false;
		}

		return true;
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
	 * @param string $wp_email The email address passed by the filter.
	 *
	 * @return string
	 */
	public function filter_mail_from_email( $wp_email ) {

		$options    = new Options();
		$forced     = $options->get( 'mail', 'from_email_force' );
		$from_email = $options->get( 'mail', 'from_email' );
		$def_email  = WP::get_default_email();

		// Save the "original" set WP email from address for later use.
		if ( $wp_email !== $def_email ) {
			$this->wp_mail_from = filter_var( $wp_email, FILTER_VALIDATE_EMAIL );
		}

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
	 * @since 1.9.0
	 *
	 * @return MailCatcherInterface
	 */
	public function get_phpmailer() {

		global $phpmailer;

		// Make sure the PHPMailer class has been instantiated.
		if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
			$phpmailer = wp_mail_smtp()->generate_mail_catcher( true ); // phpcs:ignore
		}

		return $phpmailer;
	}

	/**
	 * Set the default reply_to header, if:
	 * - no other reply_to headers are already set and,
	 * - the default reply_to address filter `wp_mail_smtp_processor_default_reply_to_addresses` is configured.
	 *
	 * @since 2.1.1
	 *
	 * @param MailCatcherInterface $phpmailer The PHPMailer object.
	 */
	private function set_default_reply_to( $phpmailer ) {

		if ( ! empty( $phpmailer->getReplyToAddresses() ) ) {
			return;
		}

		$default_reply_to_emails = apply_filters( 'wp_mail_smtp_processor_set_default_reply_to', '' );

		if ( empty( $default_reply_to_emails ) ) {
			return;
		}

		foreach ( explode( ',', $default_reply_to_emails ) as $email ) {
			$email = trim( $email );

			if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$phpmailer->addReplyTo( $email );
			}
		}
	}
}
