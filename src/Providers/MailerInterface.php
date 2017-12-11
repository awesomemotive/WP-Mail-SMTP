<?php

namespace WPMailSMTP\Providers;

/**
 * Interface MailerInterface.
 *
 * @since 1.0.0
 */
interface MailerInterface {

	/**
	 * Send the email.
	 *
	 * @since 1.0.0
	 */
	public function send();

	/**
	 * Whether the email is sent or not.
	 * We basically check the response code from a request to provider.
	 * Might not be 100% correct, not guarantees that email is delivered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_email_sent();

	/**
	 * Whether the mailer supports the current PHP version or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_php_compatible();

	/**
	 * Set the email headers in bulk.
	 *
	 * @since 1.0.0
	 *
	 * @param array $headers
	 */
	public function set_headers( $headers );

	/**
	 * Set the email single header.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set_header( $name, $value );

	/**
	 * Set email FROM.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function set_from( $email, $name );

	/**
	 * Set a bunch of email recipients: to, cc, bcc.
	 *
	 * @since 1.0.0
	 *
	 * @param array $recipients
	 */
	public function set_recipients( $recipients );

	/**
	 * Set the email subject.
	 *
	 * @since 1.0.0
	 *
	 * @param string $subject
	 */
	public function set_subject( $subject );

	/**
	 * Set the email content.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $content
	 */
	public function set_content( $content );

	/**
	 * Set the email attachments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attachments
	 */
	public function set_attachments( $attachments );

	/**
	 * Set the email reply_to option.
	 *
	 * @since 1.0.0
	 *
	 * @param array $reply_to
	 */
	public function set_reply_to( $reply_to );

	/**
	 * Set the email return_path (when supported).
	 *
	 * @since 1.0.0
	 *
	 * @param string $email
	 */
	public function set_return_path( $email );

	/**
	 * Get the email body.
	 *
	 * @since 1.0.0
	 *
	 * @return string|array
	 */
	public function get_body();

	/**
	 * Get the email headers.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_headers();
}
