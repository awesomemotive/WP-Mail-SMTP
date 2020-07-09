<?php

namespace WPMailSMTP;

/**
 * Interface MailCatcherInterface.
 *
 * @since 2.2.0
 */
interface MailCatcherInterface {

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 2.2.0
	 *
	 * @throws \phpmailerException|\PHPMailer\PHPMailer\Exception When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send();

	/**
	 * Get the PHPMailer line ending.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_line_ending();
}
