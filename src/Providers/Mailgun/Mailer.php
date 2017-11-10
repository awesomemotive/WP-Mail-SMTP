<?php

namespace WPMailSMTP\Providers\Mailgun;

use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class Mailer
 *
 * @package WPMailSMTP\Providers\Mailgun
 */
class Mailer extends MailerAbstract {

	/**
	 * Which response code from HTTP provider is considered to be successful?
	 *
	 * @var int
	 */
	protected $email_sent_code = 200;

	/**
	 * URL to make an API request to.
	 *
	 * @var string
	 */
	protected $url = 'https://api.mailgun.net/v3';

	/**
	 * @inheritdoc
	 */
	public function __construct( $phpmailer ) {

		// We want to prefill everything from \PHPMailer class.
		parent::__construct( $phpmailer );

		/*
		 * Append the url with a domain,
		 * to avoid passing the domain name as a query parameter with all requests.
		 */
		$this->url .= esc_url_raw( '/' . $this->options->get( $this->mailer, 'domain' ) . '/messages' );

		$this->set_header( 'Authorization', 'Basic ' . base64_encode( 'api:' . $this->options->get( $this->mailer, 'api_key' ) ) );
	}

	/**
	 * @inheritdoc
	 */
	public function set_from( $email, $name = '' ) {

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		if ( ! empty( $name ) ) {
			$this->set_body_param(
				array(
					'from' => $name . ' <' . $email . '>',
				)
			);
		} else {
			$this->set_body_param(
				array(
					'from' => $email,
				)
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_recipients( $recipients ) {

		if ( empty( $recipients ) ) {
			return;
		}

		$default = array( 'to', 'cc', 'bcc' );

		foreach ( $recipients as $kind => $emails ) {
			if (
				! in_array( $kind, $default, true ) ||
				empty( $emails ) ||
				! is_array( $emails )
			) {
				continue;
			}

			$data = array();

			foreach ( $emails as $email ) {
				$addr = isset( $email[0] ) ? $email[0] : false;
				$name = isset( $email[1] ) ? $email[1] : false;

				if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
					continue;
				}

				if ( ! empty( $name ) ) {
					$data[] = $name . ' <' . $addr . '>';
				} else {
					$data[] = $addr;
				}
			}

			if ( ! empty( $data ) ) {
				$this->set_body_param(
					array(
						$kind => implode( ', ', $data ),
					)
				);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_subject( $subject ) {

		$this->set_body_param(
			array(
				'subject' => $subject,
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function set_content( $content ) {

		if ( is_array( $content ) ) {

			$default = array( 'text', 'html' );

			foreach ( $content as $type => $mail ) {
				if (
					! in_array( $type, $default, true ) ||
					empty( $mail )
				) {
					continue;
				}

				$this->set_body_param(
					array(
						$type => $mail,
					)
				);
			}
		} else {
			$type = 'text';

			if ( $this->phpmailer->ContentType === 'text/html' ) {
				$type = 'html';
			}

			if ( ! empty( $content ) ) {
				$this->set_body_param(
					array(
						$type => $content,
					)
				);
			}
		}
	}

	/**
	 * TODO: this doesn't work, we need to actually upload files and pass their temp paths.
	 * TODO: in the end it should be an array of paths to temp files.
	 *
	 * @param array $attachments
	 */
	public function set_attachments( $attachments ) {

		if ( ! empty( $attachments ) ) {
			$this->set_body_param(
				array(
					'attachment' => $attachments,
				)
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_reply_to( $reply_to ) {

		if ( empty( $reply_to ) ) {
			return;
		}

		$data = array();

		foreach ( $reply_to as $key => $emails ) {
			if (
				empty( $emails ) ||
				! is_array( $emails )
			) {
				continue;
			}

			$addr = isset( $emails[0] ) ? $emails[0] : false;
			$name = isset( $emails[1] ) ? $emails[1] : false;

			if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
				continue;
			}

			if ( ! empty( $name ) ) {
				$data[] = $name . ' <' . $addr . '>';
			} else {
				$data[] = $addr;
			}
		}

		if ( ! empty( $data ) ) {
			$this->set_body_param(
				array(
					'h:Reply-To' => implode( ',', $data ),
				)
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_return_path( $email ) {

		if (
			$this->options->get( 'mail', 'return_path' ) !== true ||
			! filter_var( $email, FILTER_VALIDATE_EMAIL )
		) {
			return;
		}

		$this->set_body_param(
			array(
				'sender' => $email,
			)
		);
	}
}
