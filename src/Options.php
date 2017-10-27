<?php

namespace WPMailSMTP;

/**
 * Class Options to handle all options management.
 * WordPress does all the heavy work for caching get_option() data,
 * so we don't have to do that. But we want to minimize cyclomatic complexity
 * of calling a bunch of WP functions, thus we will cache them in a class as well.
 */
class Options {

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
		return apply_filters( 'wp_mail_smtp_options_get_all', $this->_options );
	}


	/**
	 * Get all the options for a group.
	 *
	 * Options::init()->get_group('smtp') - will return only array of options (or empty array if no key doesn't exist).
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
			return apply_filters( 'wp_mail_smtp_options_get_all', $this->_options[ $group ] );
		}

		return array();
	}

	/**
	 * Get options by a group and a key.
	 *
	 * Options::init()->get('smtp', 'host') - will return only SMTP 'host' option (string).
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
				$value = $this->postprocess_key_defaults( $group, $key, '' );
			}
		} else {
			$value = $this->postprocess_key_defaults( $group, $key, '' );
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
	 * @param mixed $value Default value, it's '' (empty string).
	 *
	 * @return mixed
	 */
	protected function postprocess_key_defaults( $group, $key, $value ) {

		switch ( $key ) {
			case 'return_path':
				$value = empty( $value ) ? false : $value;
				break;

			case 'encryption':
				$value = empty( $value ) ? 'none' : $value;
				break;

			case 'auth':
				$value = empty( $value ) ? false : $value;
				break;
		}

		return $value;
	}

	/**
	 * Process the options values through the constants check.
	 * If we have defined associated constant - use it instead of a DB value.
	 * Backward compatibility is hard.
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
					case 'user':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_USER : $value;
					case 'pass':
						/** @noinspection PhpUndefinedConstantInspection */
						return $this->is_const_defined( $group, $key ) ? WPMS_SMTP_PASS : $value;
				}

				break;
		}

		// Always return the default value if nothing form above matches the request.
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
					case 'user':
						return defined( 'WPMS_SMTP_USER' ) && WPMS_SMTP_USER;
					case 'pass':
						return defined( 'WPMS_SMTP_PASS' ) && WPMS_SMTP_PASS;
				}

				break;
		}

		return false;
	}

	/**
	 * Set plugin options, all at once.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Data to save.
	 */
	public function set( $options ) {

		foreach ( (array) $options as $group => $keys ) {
			foreach ( $keys as $key_name => $key_value ) {
				switch ( $group ) {
					case 'mail':
						switch ( $key_name ) {
							case 'from_name':
							case 'mailer':
								$options[ $group ][ $key_name ] = $this->get_const_value( $group, $key_name, sanitize_text_field( $options[ $group ][ $key_name ] ) );
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
				}
			}
		}

		if (
			isset( $options[ $options['mail']['mailer'] ] ) &&
			in_array( $options['mail']['mailer'], array( 'pepipost', 'smtp' ), true )
		) {

			$mailer = $options['mail']['mailer'];

			foreach ( $options[ $mailer ] as $key_name => $key_value ) {
				switch ( $key_name ) {
					case 'host':
					case 'user':
					case 'pass':
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, sanitize_text_field( $options[ $mailer ][ $key_name ] ) );
						break;
					case 'port':
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, intval( $options[ $mailer ][ $key_name ] ) );
						break;
					case 'encryption':
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, sanitize_text_field( $options[ $mailer ][ $key_name ] ) );
						break;
					case 'auth':
						$value                           = $options[ $mailer ][ $key_name ] === 'yes' ? true : false;
						$options[ $mailer ][ $key_name ] = $this->get_const_value( $mailer, $key_name, $value );
						break;
				}
			}
		}

		$options = apply_filters( 'wp_mail_smtp_options_set', $options );

		update_option( self::META_KEY, $options );

		// Now we need to re-cache values.
		$this->populate_options();
	}

	/**
	 * Check whether the site is using Pepipost or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_pepipost_active() {
		return apply_filters( 'wp_mail_smtp_options_is_pepipost_active', 'pepipost' === $this->get( 'mail', 'mailer' ) );
	}
}
