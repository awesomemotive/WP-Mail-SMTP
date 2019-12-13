<?php

namespace WPMailSMTP;

/**
 * Class Options to handle all options management.
 * WordPress does all the heavy work for caching get_option() data,
 * so we don't have to do that. But we want to minimize cyclomatic complexity
 * of calling a bunch of WP functions, thus we will cache them in a class as well.
 *
 * @since 1.0.0
 */
class Options {

	/**
	 * All the options keys.
	 *
	 * @since 1.3.0
	 * @since 1.4.0 Added Mailgun:region.
	 * @since 1.5.0 Added Outlook/AmazonSES.
	 * @since 1.8.0 Added Pepipost API.
	 *
	 * @since
	 *
	 * @var array Map of all the default options of the plugin.
	 */
	private static $map = array(
		'mail'        => array(
			'from_name',
			'from_email',
			'mailer',
			'return_path',
			'from_name_force',
			'from_email_force',
		),
		'smtp'        => array(
			'host',
			'port',
			'encryption',
			'autotls',
			'auth',
			'user',
			'pass',
		),
		'gmail'       => array(
			'client_id',
			'client_secret',
		),
		'outlook'     => array(
			'client_id',
			'client_secret',
		),
		'amazonses'   => array(
			'client_id',
			'client_secret',
			'region',
			'emails_pending',
		),
		'mailgun'     => array(
			'api_key',
			'domain',
			'region',
		),
		'sendgrid'    => array(
			'api_key',
		),
		'sendinblue'  => array(
			'api_key',
		),
		'pepipostapi' => array(
			'api_key',
		),
		'pepipost'    => array(
			'host',
			'port',
			'encryption',
			'auth',
			'user',
			'pass',
		),
		'license'     => array(
			'key',
		),
	);

	/**
	 * That's where plugin options are saved in wp_options table.
	 *
	 * @var string
	 */
	const META_KEY = 'wp_mail_smtp';

	/**
	 * All the plugin options.
	 *
	 * @var array
	 */
	private $_options = array();

	/**
	 * Init the Options class.
	 * TODO: add a flag to process without retrieving const values.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->populate_options();
	}

	/**
	 * Initialize all the options, used for chaining.
	 *
	 * One-liner:
	 *      Options::init()->get('smtp', 'host');
	 *      Options::init()->is_pepipost_active();
	 *
	 * Or multiple-usage:
	 *      $options = new Options();
	 *      $options->get('smtp', 'host');
	 *
	 * @since 1.0.0
	 *
	 * @return Options
	 */
	public static function init() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Default options that are saved on plugin activation.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public static function get_defaults() {

		return array(
			'mail' => array(
				'from_email'       => get_option( 'admin_email' ),
				'from_name'        => get_bloginfo( 'name' ),
				'mailer'           => 'mail',
				'return_path'      => false,
				'from_email_force' => false,
				'from_name_force'  => false,
			),
			'smtp' => array(
				'autotls' => true,
				'auth'    => true,
			),
		);
	}

	/**
	 * Retrieve all options of the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function populate_options() {
		$this->_options = get_option( self::META_KEY, array() );
	}

	/**
	 * Get all the options.
	 *
	 * Options::init()->get_all();
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_all() {

		$options = $this->_options;

		foreach ( $options as $group => $g_value ) {
			foreach ( $g_value as $key => $value ) {
				$options[ $group ][ $key ] = $this->get( $group, $key );
			}
		}

		return apply_filters( 'wp_mail_smtp_options_get_all', $options );
	}

	/**
	 * Get all the options for a group.
	 *
	 * Options::init()->get_group('smtp') - will return the array of options for the group, including defaults and constants.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Process values through the get() method which is aware of constants.
	 *
	 * @param string $group
	 *
	 * @return array
	 */
	public function get_group( $group ) {

		// Just to feel safe.
		$group = sanitize_key( $group );

		/*
		 * Get the values saved in DB.
		 * If plugin is configured with constants right from the start - this will not have all the values.
		 */
		$options = isset( $this->_options[ $group ] ) ? $this->_options[ $group ] : array();

		// We need to process certain constants-aware options through actual constants.
		if ( isset( self::$map[ $group ] ) ) {
			foreach ( self::$map[ $group ] as $key ) {
				$options[ $key ] = $this->get( $group, $key );
			}
		}

		return apply_filters( 'wp_mail_smtp_options_get_group', $options, $group );
	}

	/**
	 * Get options by a group and a key.
	 *
	 * Options::init()->get( 'smtp', 'host' ) - will return only SMTP 'host' option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return mixed|null Null if value doesn't exist anywhere: in constants, in DB, in a map. So it's completely custom or a typo.
	 */
	public function get( $group, $key ) {

		// Just to feel safe.
		$group = sanitize_key( $group );
		$key   = sanitize_key( $key );
		$value = null;

		// Get the const value if we have one.
		$value = $this->get_const_value( $group, $key, $value );

		// We don't have a const value.
		if ( $value === null ) {
			// Ordinary database or default values.
			if ( isset( $this->_options[ $group ] ) ) {
				// Get the options key of a group.
				if ( isset( $this->_options[ $group ][ $key ] ) ) {
					$value = $this->_options[ $group ][ $key ];
				} else {
					$value = $this->postprocess_key_defaults( $group, $key );
				}
			} else {
				/*
				 * Fallback to default if it doesn't exist in a map.
				 * Allow to retrieve only values from a map.
				 */
				if (
					isset( self::$map[ $group ] ) &&
					in_array( $key, self::$map[ $group ], true )
				) {
					$value = $this->postprocess_key_defaults( $group, $key );
				}
			}
		}

		// Strip slashes only from values saved in DB. Constants should be processed as is.
		if ( is_string( $value ) && ! $this->is_const_defined( $group, $key ) ) {
			$value = stripslashes( $value );
		}

		return apply_filters( 'wp_mail_smtp_options_get', $value, $group, $key );
	}

	/**
	 * Some options may be non-empty by default,
	 * so we need to postprocess them to convert.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Added Mailgun:region.
	 * @since 1.5.0 Added Outlook/AmazonSES, license key support.
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function postprocess_key_defaults( $group, $key ) {

		$value = '';

		switch ( $key ) {
			case 'from_email_force':
			case 'from_name_force':
			case 'return_path':
				$value = $group === 'mail' ? false : true;
				break;

			case 'mailer':
				$value = 'mail';
				break;

			case 'encryption':
				$value = in_array( $group, array( 'smtp', 'pepipost' ), true ) ? 'none' : $value;
				break;

			case 'region':
				$value = $group === 'mailgun' ? 'US' : $value;
				break;

			case 'emails_pending':
				$value = array();
				break;

			case 'auth':
			case 'autotls':
				$value = in_array( $group, array( 'smtp', 'pepipost' ), true ) ? false : true;
				break;

			case 'pass':
				$value = $this->get_const_value( $group, $key, $value );
				break;

			case 'type':
				$value = $group === 'license' ? 'lite' : '';
				break;
		}

		return apply_filters( 'wp_mail_smtp_options_postprocess_key_defaults', $value, $group, $key );
	}

	/**
	 * Process the options values through the constants check.
	 * If we have defined associated constant - use it instead of a DB value.
	 * Backward compatibility is hard.
	 * General section of options won't have constants, so we are omitting those checks and just return default value.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Added WPMS_MAILGUN_REGION.
	 * @since 1.5.0 Added Outlook/AmazonSES, license key support.
	 * @since 1.6.0 Added Sendinblue.
	 * @since 1.7.0 Added Do Not Send.
	 * @since 1.8.0 Added Pepipost API.
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function get_const_value( $group, $key, $value ) {

		if ( ! $this->is_const_enabled() ) {
			return $value;
		}

		$return = null;

		switch ( $group ) {
			case 'mail':
				switch ( $key ) {
					case 'from_name':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAIL_FROM_NAME : $value;
						break;
					case 'from_email':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAIL_FROM : $value;
						break;
					case 'mailer':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAILER : $value;
						break;
					case 'return_path':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SET_RETURN_PATH : $value;
						break;
					case 'from_name_force':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAIL_FROM_NAME_FORCE : $value;
						break;
					case 'from_email_force':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAIL_FROM_FORCE : $value;
						break;
				}

				break;

			case 'smtp':
				switch ( $key ) {
					case 'host':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SMTP_HOST : $value;
						break;
					case 'port':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SMTP_PORT : $value;
						break;
					case 'encryption':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? ( WPMS_SSL === '' ? 'none' : WPMS_SSL ) : $value;
						break;
					case 'auth':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SMTP_AUTH : $value;
						break;
					case 'autotls':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SMTP_AUTOTLS : $value;
						break;
					case 'user':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SMTP_USER : $value;
						break;
					case 'pass':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SMTP_PASS : $value;
						break;
				}

				break;

			case 'gmail':
				switch ( $key ) {
					case 'client_id':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_GMAIL_CLIENT_ID : $value;
						break;
					case 'client_secret':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_GMAIL_CLIENT_SECRET : $value;
						break;
				}

				break;

			case 'outlook':
				switch ( $key ) {
					case 'client_id':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_OUTLOOK_CLIENT_ID : $value;
						break;
					case 'client_secret':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_OUTLOOK_CLIENT_SECRET : $value;
						break;
				}

				break;

			case 'amazonses':
				switch ( $key ) {
					case 'client_id':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_AMAZONSES_CLIENT_ID : $value;
						break;
					case 'client_secret':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_AMAZONSES_CLIENT_SECRET : $value;
						break;
					case 'region':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_AMAZONSES_REGION : $value;
						break;
				}

				break;

			case 'mailgun':
				switch ( $key ) {
					case 'api_key':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAILGUN_API_KEY : $value;
						break;
					case 'domain':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAILGUN_DOMAIN : $value;
						break;
					case 'region':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_MAILGUN_REGION : $value;
						break;
				}

				break;

			case 'sendgrid':
				switch ( $key ) {
					case 'api_key':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SENDGRID_API_KEY : $value;
						break;
				}

				break;

			case 'sendinblue':
				switch ( $key ) {
					case 'api_key':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_SENDINBLUE_API_KEY : $value;
						break;
				}

				break;

			case 'pepipostapi':
				switch ( $key ) {
					case 'api_key':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_PEPIPOST_API_KEY : $value;
						break;
				}

				break;

			case 'license':
				switch ( $key ) {
					case 'key':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_LICENSE_KEY : $value;
						break;
				}

				break;

			case 'general':
				switch ( $key ) {
					case 'do_not_send':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = $this->is_const_defined( $group, $key ) ? WPMS_DO_NOT_SEND : $value;
						break;
				}

				break;

			default:
				// Always return the default value if nothing from above matches the request.
				$return = $value;
		}

		return apply_filters( 'wp_mail_smtp_options_get_const_value', $return, $group, $key, $value );
	}

	/**
	 * Whether constants redefinition is enabled or not.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added filter to redefine the value.
	 *
	 * @return bool
	 */
	public function is_const_enabled() {

		$return = defined( 'WPMS_ON' ) && WPMS_ON === true;

		return apply_filters( 'wp_mail_smtp_options_is_const_enabled', $return );
	}

	/**
	 * We need this check to reuse later in admin area,
	 * to distinguish settings fields that were redefined,
	 * and display them differently.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added a filter, Outlook/AmazonSES, license key support.
	 * @since 1.6.0 Added Sendinblue.
	 * @since 1.7.0 Added Do Not Send.
	 * @since 1.8.0 Added Pepipost API.
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return bool
	 */
	public function is_const_defined( $group, $key ) {

		if ( ! $this->is_const_enabled() ) {
			return false;
		}

		// Just to feel safe.
		$group  = sanitize_key( $group );
		$key    = sanitize_key( $key );
		$return = false;

		switch ( $group ) {
			case 'mail':
				switch ( $key ) {
					case 'from_name':
						$return = defined( 'WPMS_MAIL_FROM_NAME' ) && WPMS_MAIL_FROM_NAME;
						break;
					case 'from_email':
						$return = defined( 'WPMS_MAIL_FROM' ) && WPMS_MAIL_FROM;
						break;
					case 'mailer':
						$return = defined( 'WPMS_MAILER' ) && WPMS_MAILER;
						break;
					case 'return_path':
						$return = defined( 'WPMS_SET_RETURN_PATH' ) && ( WPMS_SET_RETURN_PATH === 'true' || WPMS_SET_RETURN_PATH === true );
						break;
					case 'from_name_force':
						$return = defined( 'WPMS_MAIL_FROM_NAME_FORCE' ) && ( WPMS_MAIL_FROM_NAME_FORCE === 'true' || WPMS_MAIL_FROM_NAME_FORCE === true );
						break;
					case 'from_email_force':
						$return = defined( 'WPMS_MAIL_FROM_FORCE' ) && ( WPMS_MAIL_FROM_FORCE === 'true' || WPMS_MAIL_FROM_FORCE === true );
						break;
				}

				break;

			case 'smtp':
				switch ( $key ) {
					case 'host':
						$return = defined( 'WPMS_SMTP_HOST' ) && WPMS_SMTP_HOST;
						break;
					case 'port':
						$return = defined( 'WPMS_SMTP_PORT' ) && WPMS_SMTP_PORT;
						break;
					case 'encryption':
						$return = defined( 'WPMS_SSL' );
						break;
					case 'auth':
						$return = defined( 'WPMS_SMTP_AUTH' ) && WPMS_SMTP_AUTH;
						break;
					case 'autotls':
						$return = defined( 'WPMS_SMTP_AUTOTLS' ) && ( WPMS_SMTP_AUTOTLS === 'true' || WPMS_SMTP_AUTOTLS === true );
						break;
					case 'user':
						$return = defined( 'WPMS_SMTP_USER' ) && WPMS_SMTP_USER;
						break;
					case 'pass':
						$return = defined( 'WPMS_SMTP_PASS' ) && WPMS_SMTP_PASS;
						break;
				}

				break;

			case 'gmail':
				switch ( $key ) {
					case 'client_id':
						$return = defined( 'WPMS_GMAIL_CLIENT_ID' ) && WPMS_GMAIL_CLIENT_ID;
						break;
					case 'client_secret':
						$return = defined( 'WPMS_GMAIL_CLIENT_SECRET' ) && WPMS_GMAIL_CLIENT_SECRET;
						break;
				}

				break;

			case 'outlook':
				switch ( $key ) {
					case 'client_id':
						$return = defined( 'WPMS_OUTLOOK_CLIENT_ID' ) && WPMS_OUTLOOK_CLIENT_ID;
						break;
					case 'client_secret':
						$return = defined( 'WPMS_OUTLOOK_CLIENT_SECRET' ) && WPMS_OUTLOOK_CLIENT_SECRET;
						break;
				}

				break;

			case 'amazonses':
				switch ( $key ) {
					case 'client_id':
						$return = defined( 'WPMS_AMAZONSES_CLIENT_ID' ) && WPMS_AMAZONSES_CLIENT_ID;
						break;
					case 'client_secret':
						$return = defined( 'WPMS_AMAZONSES_CLIENT_SECRET' ) && WPMS_AMAZONSES_CLIENT_SECRET;
						break;
					case 'region':
						$return = defined( 'WPMS_AMAZONSES_REGION' ) && WPMS_AMAZONSES_REGION;
						break;
				}

				break;

			case 'mailgun':
				switch ( $key ) {
					case 'api_key':
						$return = defined( 'WPMS_MAILGUN_API_KEY' ) && WPMS_MAILGUN_API_KEY;
						break;
					case 'domain':
						$return = defined( 'WPMS_MAILGUN_DOMAIN' ) && WPMS_MAILGUN_DOMAIN;
						break;
					case 'region':
						$return = defined( 'WPMS_MAILGUN_REGION' ) && WPMS_MAILGUN_REGION;
						break;
				}

				break;

			case 'sendgrid':
				switch ( $key ) {
					case 'api_key':
						$return = defined( 'WPMS_SENDGRID_API_KEY' ) && WPMS_SENDGRID_API_KEY;
						break;
				}

				break;

			case 'sendinblue':
				switch ( $key ) {
					case 'api_key':
						$return = defined( 'WPMS_SENDINBLUE_API_KEY' ) && WPMS_SENDINBLUE_API_KEY;
						break;
				}

				break;

			case 'pepipostapi':
				switch ( $key ) {
					case 'api_key':
						$return = defined( 'WPMS_PEPIPOST_API_KEY' ) && WPMS_PEPIPOST_API_KEY;
						break;
				}

				break;

			case 'license':
				switch ( $key ) {
					case 'key':
						$return = defined( 'WPMS_LICENSE_KEY' ) && WPMS_LICENSE_KEY;
						break;
				}

				break;

			case 'general':
				switch ( $key ) {
					case 'do_not_send':
						/** @noinspection PhpUndefinedConstantInspection */
						$return = defined( 'WPMS_DO_NOT_SEND' ) && WPMS_DO_NOT_SEND;
						break;
				}

				break;
		}

		return apply_filters( 'wp_mail_smtp_options_is_const_defined', $return, $group, $key );
	}

	/**
	 * Set plugin options, all at once.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Added $once argument to save options only if they don't exist already.
	 * @since 1.4.0 Added Mailgun:region.
	 * @since 1.5.0 Added Outlook/AmazonSES, Email Log. Stop saving const values into DB.
	 *
	 * @param array $options Plugin options to save.
	 * @param bool $once Whether to update existing options or to add these options only once.
	 */
	public function set( $options, $once = false ) {
		/*
		 * Process generic options.
		 */
		foreach ( (array) $options as $group => $keys ) {
			foreach ( $keys as $option_name => $option_value ) {
				switch ( $group ) {
					case 'mail':
						switch ( $option_name ) {
							case 'from_name':
							case 'mailer':
								$options[ $group ][ $option_name ] = sanitize_text_field( $option_value );
								break;
							case 'from_email':
								if ( filter_var( $option_value, FILTER_VALIDATE_EMAIL ) ) {
									$options[ $group ][ $option_name ] = sanitize_email( $option_value );
								}
								break;
							case 'return_path':
							case 'from_name_force':
							case 'from_email_force':
								$options[ $group ][ $option_name ] = (bool) $option_value;
								break;
						}
						break;

					case 'general':
						switch ( $option_name ) {
							case 'do_not_send':
							case 'am_notifications_hidden':
							case 'email_delivery_errors_hidden':
							case 'uninstall':
								$options[ $group ][ $option_name ] = (bool) $option_value;
								break;
						}
				}
			}
		}

		/*
		 * Process mailers-specific options.
		 */
		if (
			! empty( $options['mail']['mailer'] ) &&
			isset( $options[ $options['mail']['mailer'] ] ) &&
			in_array( $options['mail']['mailer'], array( 'pepipost', 'pepipostapi', 'smtp', 'sendgrid', 'sendinblue', 'mailgun', 'gmail', 'outlook' ), true )
		) {

			$mailer = $options['mail']['mailer'];

			foreach ( $options[ $mailer ] as $option_name => $option_value ) {
				switch ( $option_name ) {
					case 'host': // smtp.
					case 'user': // smtp.
					case 'encryption': // smtp.
					case 'region': // mailgun/amazonses.
						$options[ $mailer ][ $option_name ] = $this->is_const_defined( $mailer, $option_name ) ? '' : sanitize_text_field( $option_value );
						break; // smtp.
					case 'port':
						$options[ $mailer ][ $option_name ] = $this->is_const_defined( $mailer, $option_name ) ? 25 : (int) $option_value;
						break;
					case 'auth': // smtp.
					case 'autotls': // smtp.
						$option_value = (bool) $option_value;

						$options[ $mailer ][ $option_name ] = $this->is_const_defined( $mailer, $option_name ) ? false : $option_value;
						break;

					case 'pass': // smtp.
						// Do not process as they may contain certain special characters, but allow to be overwritten using constants.
						$options[ $mailer ][ $option_name ] = $this->is_const_defined( $mailer, $option_name ) ? '' : trim( (string) $option_value );
						break;

					case 'api_key': // mailgun/sendgrid/sendinblue/pepipostapi.
					case 'domain': // mailgun.
					case 'client_id': // gmail/outlook/amazonses.
					case 'client_secret': // gmail/outlook/amazonses.
					case 'auth_code': // gmail/outlook.
						$options[ $mailer ][ $option_name ] = $this->is_const_defined( $mailer, $option_name ) ? '' : sanitize_text_field( $option_value );
						break;

					case 'access_token': // gmail/outlook, array().
					case 'user_details': // outlook, array().
					case 'emails_pending': // amazonses, array().
						// These options don't support constants.
						$options[ $mailer ][ $option_name ] = $option_value;
						break;
				}
			}
		}

		$options = apply_filters( 'wp_mail_smtp_options_set', $options );

		// Whether to update existing options or to add these options only once if they don't exist yet.
		if ( $once ) {
			add_option( self::META_KEY, $options, '', 'no' ); // Do not autoload these options.
		} else {
			update_option( self::META_KEY, $options, 'no' );
		}

		// Now we need to re-cache values.
		$this->populate_options();
	}

	/**
	 * Merge recursively, including a proper substitution of values in sub-arrays when keys are the same.
	 * It's more like array_merge() and array_merge_recursive() combined.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function array_merge_recursive() {

		$arrays = func_get_args();

		if ( count( $arrays ) < 2 ) {
			return isset( $arrays[0] ) ? $arrays[0] : array();
		}

		$merged = array();

		while ( $arrays ) {
			$array = array_shift( $arrays );

			if ( ! is_array( $array ) ) {
				return array();
			}

			if ( empty( $array ) ) {
				continue;
			}

			foreach ( $array as $key => $value ) {
				if ( is_string( $key ) ) {
					if (
						is_array( $value ) &&
						array_key_exists( $key, $merged ) &&
						is_array( $merged[ $key ] )
					) {
						$merged[ $key ] = call_user_func( __METHOD__, $merged[ $key ], $value );
					} else {
						$merged[ $key ] = $value;
					}
				} else {
					$merged[] = $value;
				}
			}
		}

		return $merged;
	}

	/**
	 * Check whether the site is using Pepipost SMTP or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_pepipost_active() {
		return apply_filters( 'wp_mail_smtp_options_is_pepipost_active', $this->get( 'mail', 'mailer' ) === 'pepipost' );
	}

	/**
	 * Check whether the site is using Pepipost/SMTP as a mailer or not.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function is_mailer_smtp() {
		return apply_filters( 'wp_mail_smtp_options_is_mailer_smtp', in_array( $this->get( 'mail', 'mailer' ), array( 'pepipost', 'smtp' ), true ) );
	}
}
