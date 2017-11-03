<?php

namespace WPMailSMTP;

// Load PHPMailer class, so we can subclass it.
if ( ! class_exists( 'PHPMailer', false ) ) {
	require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

/**
 * Class MailCatcher replaces the \PHPMailer and modifies the email sending logic.
 * Thus, we can use other mailers API to do what we need, or stop emails completely.
 *
 * @package WPMailSMTP
 */
class MailCatcher extends \PHPMailer {

	/**
	 * Replacement send() method that does not send.
	 *
	 * Unlike the PHPMailer send method,
	 * this method never calls the method postSend(),
	 * which is where the email is actually sent
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function send() {
		// TODO: attachments - $this->getAttachments()
		// TODO: Content type.
		// TODO: CharSet.
		// TODO: FromName.
		// TODO: Text or HTML version separately.
		// TODO: iterate over headers: iterate through $this->getCustomHeaders() and $this->addCustomHeader().
		$options = new Options();

		switch ( $options->get( 'mail', 'mailer' ) ) {
			case 'mailgun':
				try {
					if ( ! $this->preSend() ) {
						return false;
					}

					/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
					$mailgun = \Mailgun\Mailgun::create( $options->get( 'mailgun', 'api_key' ) );

					$params = array(
						'from'       => $this->FromName . '<' . $this->From . '>',
						'to'         => implode( ',', reset( $this->to ) ),
						'subject'    => $this->Subject,
						'text'       => $this->AltBody,
						'html'       => $this->Body,
						'attachment' => $this->getAttachments(),
					);

					if ( $options->get( 'mail', 'return_path' ) ) {
						$params['h:Reply-To'] = $this->From;
					}

					// Someone might want to add additional params, like tracking.
					$params = apply_filters( 'wp_mail_smtp_mailcatcher_send_mailgun_params', $params );

					$mailgun->messages()->send( $options->get( 'mailgun', 'domain' ), $params );

					return true;
				} catch ( \phpmailerException $e ) {
					return false;
				}

				break;

			case 'smtp':
			case 'pepipost':
			case 'mail':
			default:
				return parent::send();
		}
	}
}
