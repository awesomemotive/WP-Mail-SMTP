<?php

namespace WPMailSMTP\Providers;

/**
 * Class Loader
 *
 * @package WPMailSMTP\Providers
 */
class Loader {

	/**
	 * Key is the mailer option, value is the path to its classes.
	 *
	 * @var array
	 */
	protected $providers = array(
		'mail'     => '\WPMailSMTP\Providers\Mail\\',
		'gmail'    => '\WPMailSMTP\Providers\Gmail\\',
		'mailgun'  => '\WPMailSMTP\Providers\Mailgun\\',
		'sendgrid' => '\WPMailSMTP\Providers\Sendgrid\\',
		'pepipost' => '\WPMailSMTP\Providers\Pepipost\\',
		'smtp'     => '\WPMailSMTP\Providers\SMTP\\',
	);

	/**
	 * @var \WPMailSMTP\MailCatcher
	 */
	private $phpmailer;

	/**
	 * Loader constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public function get_providers() {
		return apply_filters( 'wp_mail_smtp_providers_loader_get_providers', $this->providers );
	}

	/**
	 * @param string $provider
	 *
	 * @return array
	 */
	public function get_provider_path( $provider ) {
		$provider = sanitize_key( $provider );

		return apply_filters(
			'wp_mail_smtp_providers_loader_get_provider_path',
			isset( $this->providers[ $provider ] ) ? $this->providers[ $provider ] : null,
			$provider
		);
	}

	/**
	 * Get the provider options, if exists.
	 *
	 * @param string $provider
	 *
	 * @return \WPMailSMTP\Providers\OptionAbstract|null
	 */
	public function get_options( $provider ) {
		return $this->get_entity( $provider, 'Options' );
	}

	/**
	 * Get all options of all providers.
	 *
	 * @return \WPMailSMTP\Providers\OptionAbstract[]
	 */
	public function get_options_all() {
		$options = array();

		foreach ( $this->get_providers() as $provider => $path ) {

			$option = $this->get_options( $provider );

			if ( ! $option instanceof OptionAbstract ) {
				continue;
			}

			$slug  = $option->get_slug();
			$title = $option->get_title();

			if ( empty( $title ) || empty( $slug ) ) {
				continue;
			}

			$options[] = $option;
		}

		return apply_filters( 'wp_mail_smtp_providers_loader_get_providers_all', $options );
	}

	/**
	 * Get the provider mailer, if exists.
	 *
	 * @param string $provider
	 * @param \WPMailSMTP\MailCatcher $phpmailer
	 *
	 * @return \WPMailSMTP\Providers\MailerAbstract|null
	 */
	public function get_mailer( $provider, $phpmailer ) {

		$this->phpmailer = $phpmailer;

		return $this->get_entity( $provider, 'Mailer' );
	}

	/**
	 * Get the provider auth, if exists.
	 *
	 * @param string $provider
	 *
	 * @return \WPMailSMTP\Providers\AuthAbstract|null
	 */
	public function get_auth( $provider ) {
		return $this->get_entity( $provider, 'Auth' );
	}

	/**
	 * @param string $provider
	 * @param string $request
	 *
	 * @return null
	 */
	protected function get_entity( $provider, $request ) {

		$provider = sanitize_key( $provider );
		$request  = sanitize_text_field( $request );
		$path     = $this->get_provider_path( $provider );
		$entity   = null;

		if ( empty( $path ) ) {
			return $entity;
		}

		try {
			$reflection = new \ReflectionClass( $path . $request );

			if ( file_exists( $reflection->getFileName() ) ) {
				$class = $path . $request;

				$entity = new $class( $this->phpmailer );
			}
		} catch ( \Exception $e ) {
			$entity = null;
		}

		return apply_filters( 'wp_mail_smtp_providers_loader_get_entity', $entity, $provider, $request );
	}
}
