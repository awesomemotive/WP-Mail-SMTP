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
	 * @var array Map of all the default options of the plugin.
	 */
	private static $map = array(
		'mail'     => array(
			'from_name',
			'from_email',
			'mailer',
			'return_path',
		),
		'smtp'     => array(
			'host',
			'port',
			'encryption',
			'autotls',
			'auth',
			'user',
			'pass',
		),
		'gmail'    => array(
			'client_id',
			'client_secret',
		),
		'mailgun'  => array(
			'api_key',
			'domain',
		),
		'sendgrid' => array(
			'api_key',
		),
		'pepipost' => array(
			'host',
			'port',
			'encryption',
			'auth',
			'user',
			'pass',
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
	 * Options::init()->get_group('smtp') - will return only array of options (or empty array if a key doesn't exist).
	 *
	 * @since 1.0.0
	 *
	 * @param string $group
	 *
	 * @return mixed
	 */
	public function get_group( $group ) {

		// Just to feel safe.
		$group = sanitize_key( $group );

		if ( isset( $this->_options[ $group ] ) ) {

			foreach ( $this->_options[ $group ] as $g_key => $g_value ) {
				$options[ $group ][ $g_key ] = $this->get( $group, $g_key );
			}

			return apply_filters( 'wp_mail_smtp_options_get_group', $this->_options[ $group ], $group );
		}

		return array();
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
	 * @return mixed
	 */
	public function get( $group, $key ) {

		// Just to feel safe.
		$group = sanitize_key( $group );
		$key   = sanitize_key( $key );

		// Get the options group.
		if ( isset( $this->_options[ $group ] ) ) {

			// Get the options key of a group.
			if ( isset( $this->_options[ $group ][ $key ] ) ) {
				$value = $this->get_const_value( $group, $key, $this->_options[ $group ][ $key ] );
			} else {
				$value = $this->postprocess_key_defaults( $group, $key );
			}
		} else {
			$value = $this->postprocess_key_defaults( $group, $key );
		}

		if ( is_string( $value ) ) {
			$value = stripslashes( $value );
		}

		return apply_filters( 'wp_mail_smtp_options_get', $value, $group, $key );
	}

	/**
	 * Some options may be non-empty by default,
	 * so we need to postprocess them to convert.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function postprocess_key_defaults( $group, $key ) {

		$value = '';

		switch ( $key ) {
			case 'return_path':
				$value = $group === 'mail' ? false : true;
				break;

			case 'encryption':
				$value = in_array( $group, array( 'smtp', 'pepipost' ), true ) ? 'none' : $value;
				break;

			case 'auth':
			case 'autotls':
				$value = in_array( $group, array( 'smtp', 'pepipost' ), true ) ? false : true;
				break;

			case 'pass':
				$value = $this->get_const_value( $group, $key, $value );
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

		switch ( $group ) {
			case 'mail':
				switch ( $key ) {
					case 'from_name':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_MAIL_FROM_NAME : $value;
					case 'from_email':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_MAIL_FROM : $value;
					case 'mailer':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_MAILER : $value;
					case 'return_path':
						return $this->is_const_defined( $group, $key ) ? true : $value;
				}

				break;

			case 'smtp':
				switch ( $key ) {
					case 'host':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_HOST : $value;
					case 'port':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_PORT : $value;
					case 'encryption':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key )
							? ( WPMS_SSL === '' ? 'none' : WPMS_SSL )
							: $value;
					case 'auth':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_AUTH : $value;
					case 'autotls':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_AUTOTLS : $value;
					case 'user':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_USER : $value;
					case 'pass':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_PASS : $value;
				}

				break;

			case 'gmail':
				switch ( $key ) {
					case 'client_id':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_GMAIL_CLIENT_ID : $value;
					case 'client_secret':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_GMAIL_CLIENT_SECRET : $value;
				}

				break;

			case 'mailgun':
				switch ( $key ) {
					case 'api_key':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_MAILGUN_API_KEY : $value;
					case 'domain':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_MAILGUN_DOMAIN : $value;
				}

				break;

			case 'sendgrid':
				switch ( $key ) {
					case 'api_key':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SENDGRID_API_KEY : $value;
				}

				break;
		}

		// Always return the default value if nothing from above matches the request.
		return $value;
	}

	/**
	 * Whether constants redefinition is enabled or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_const_enabled() {
		return defined( 'WPMS_ON' ) && WPMS_ON === true;
	}

	/**
	 * We need this check to reuse later in admin area,
	 * to distinguish settings fields that were redefined,
	 * and display them differently.
	 *
	 * @since 1.0.0
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
		$group = sanitize_key( $group );
		$key   = sanitize_key( $key );

		switch ( $group ) {
			case 'mail':
				switch ( $key ) {
					case 'from_name':
						return defined( 'WPMS_MAIL_FROM_NAME' ) && WPMS_MAIL_FROM_NAME;
					case 'from_email':
						return defined( 'WPMS_MAIL_FROM' ) && WPMS_MAIL_FROM;
					case 'mailer':
						return defined( 'WPMS_MAILER' ) && WPMS_MAILER;
					case 'return_path':
						return defined( 'WPMS_SET_RETURN_PATH' ) && ( WPMS_SET_RETURN_PATH === 'true' || WPMS_SET_RETURN_PATH === true );
				}

				break;

			case 'smtp':
				switch ( $key ) {
					case 'host':
						return defined( 'WPMS_SMTP_HOST' ) && WPMS_SMTP_HOST;
					case 'port':
						return defined( 'WPMS_SMTP_PORT' ) && WPMS_SMTP_PORT;
					case 'encryption':
						return defined( 'WPMS_SSL' );
					case 'auth':
						return defined( 'WPMS_SMTP_AUTH' ) && WPMS_SMTP_AUTH;
					case 'autotls':
						return defined( 'WPMS_SMTP_AUTOTLS' ) && WPMS_SMTP_AUTOTLS;
					case 'user':
						return defined( 'WPMS_SMTP_USER' ) && WPMS_SMTP_USER;
					case 'pass':
						return defined( 'WPMS_SMTP_PASS' ) && WPMS_SMTP_PASS;
				}

				break;

			case 'gmail':
				switch ( $key ) {
					case 'client_id':
						return defined( 'WPMS_GMAIL_CLIENT_ID' ) && WPMS_GMAIL_CLIENT_ID;
					case 'client_secret':
						return defined( 'WPMS_GMAIL_CLIENT_SECRET' ) && WPMS_GMAIL_CLIENT_SECRET;
				}

				break;

			case 'mailgun':
				switch ( $key ) {
					case 'api_key':
						return defined( 'WPMS_MAILGUN_API_KEY' ) && WPMS_MAILGUN_API_KEY;
					case 'domain':
						return defined( 'WPMS_MAILGUN_DOMAIN' ) && WPMS_MAILGUN_DOMAIN;
				}

				break;

			case 'sendgrid':
				switch ( $key ) {
					case 'api_key':
						return defined( 'WPMS_SENDGRID_API_KEY' ) && WPMS_SENDGRID_API_KEY;
				}

				break;
		}

		return false;
	}

	/**
	 * Set plugin options, all at once.
	 *
	 * @since 1.0.0
	 * @since 1.3.0 Added $once argument to save option only if they don't exist already.
	 *
	 * @param array $options Plugin options to save.
	 * @param bool $once Whether to update existing options or to add these options only once.
	 */
	public function set( $options, $once = false ) {

		foreach ( (array) $options as $group => $keys ) {
			foreach ( $keys as $key_name => $key_value ) {
				switch ( $group ) {
					case 'mail':
						switch ( $key_name ) {
							case 'from_name':
							case 'mailer':
								$options[ $group ][ $key_name ] = $this->get_const_value( $group, $key_name, wp_strip_all_tags( $options[ $group ][ $key_name ], true ) );
								break;
							case 'from_email':
								if ( filter_var( $options[ $group ][ $key_name ], FILTER_VALIDATE_EMAIL ) ) {
									$options[ $group ][ $key_name ] = $this->get_const_value( $group, $key_name, sanitize_email( $options[ $group ][ $key_name ] ) );
								}
								break;
							case 'return_path':
								$options[ $group ][ $key_name ] = $this->get_const_value( $group, $key_name, (bool) $options[ $group ][ $key_name ] );
								break;
						}
						break;

					case 'general':
						switch ( $key_name ) {
							case 'am_notifications_hidden':
								$options[ $group ][ $key_name ] = (bool) $options[ $group ][ $key_name ];
								break;
						}
				}
			}
		}

		if (
			isset( $options[ $options['mail']['mailer'] ] ) &&
			in_array( $options['mail']['mailer'], array( 'pepipost', 'smtp', 'sendgrid', 'mailgun', 'gmail' ), true )
		) {

			$mailer = $options['mail']['mailer'];

			foreach ( $options[ $mailer ] as $key_name => $key_value ) {
				switch ( $key_name ) {
					case 'host':
					case 'user':
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, wp_strip_all_tags( $options[ $mailer ][ $key_name ], true ) );
						break;
					case 'port':
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, intval( $options[ $mailer ][ $key_name ] ) );
						break;
					case 'encryption':
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, wp_strip_all_tags( $options[ $mailer ][ $key_name ], true ) );
						break;
					case 'auth':
					case 'autotls':
						$value = $options[ $mailer ][ $key_name ] === 'yes' || $options[ $mailer ][ $key_name ] === true ? true : false;

						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, $value );
						break;

					case 'pass':
					case 'api_key':
					case 'domain':
					case 'client_id':
					case 'client_secret':
					case 'auth_code':
					case 'access_token':
						// Do not process as they may contain certain special characters, but allow to be overwritten using constants.
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, $options[ $mailer ][ $key_name ] );
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
	 * Check whether the site is using Pepipost or not.
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
