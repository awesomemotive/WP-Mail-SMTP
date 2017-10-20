<?php

namespace WPMailSMTP;

/**
 * Class Options to handle all options management.
 * WordPress does all the heavy work for caching get_option() data,
 * so we don't have to do that.
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
	 */
	public function __construct() {
		$this->populate_options();
	}

	/**
	 * Initialize all the options, used for chaining.
	 *
	 * Options::init()->get('smtp', 'host');
	 * Options::init()->is_pepipost_active();
	 * OR
	 * $options = new Options();
	 * $options->get('smtp', 'host');
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
	 */
	protected function populate_options() {
		$this->_options = get_option( self::META_KEY, array() );
	}

	/**
	 * Get options by a group and a key or by group only:
	 *
	 * Options::init()->get()               - will return all options.
	 * Options::init()->get('smtp')         - will return only SMTP options (array).
	 * Options::init()->get('smtp', 'host') - will return only SMTP 'host' option (string).
	 *
	 * @since 1.0.0
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return array|string
	 */
	public function get( $group = '', $key = '' ) {

		// Just to feel safe.
		$group = sanitize_key( $group );
		$key   = sanitize_key( $key );

		// Get the options group.
		if ( array_key_exists( $group, $this->_options ) ) {

			// Get the options key of a group.
			if ( array_key_exists( $key, $this->_options[ $group ] ) ) {
				return $this->_options[ $group ][ $key ];
			}

			return $this->_options[ $group ];
		}

		return $this->_options;
	}

	/**
	 * Set plugin options, all at once.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Data to save, already processed.
	 */
	public function set( $options ) {

		update_option( self::META_KEY, $options );

		// Now we need to re-cache values.
		$this->populate_options();
	}

	/**
	 * Check whether the site is using Pepipost or not.
	 *
	 * @return bool
	 */
	public function is_pepipost_active() {
		return apply_filters( 'wp_mail_smtp_is_pepipost_active', 'pepipost' === $this->get( 'mail', 'mailer' ) );
	}
}
