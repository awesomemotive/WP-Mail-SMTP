<?php

namespace WPMailSMTP\Providers\SMTP;

use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class Mailer inherits everything from parent abstract class.
 * This file is required for a proper work of Loader and \ReflectionClass.
 *
 * @package WPMailSMTP\Providers\SMTP
 */
class Mailer extends MailerAbstract {

	/**
	 * @inheritdoc
	 */
	public function is_mailer_complete() {

		// Host and Port are the only really required options.
    $host = $this->options->get($this->mailer, 'host');
    $port = $this->options->get($this->mailer, 'port');
		return ( !empty( $host ) && !empty( $port ) );

	}
}
