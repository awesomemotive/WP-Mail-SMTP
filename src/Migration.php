<?php

namespace WPMailSMTP;

/**
 * Class Migration helps migrate all plugin options saved into DB to a new storage location.
 *
 * @since 1.0.0
 */
class Migration {

	/**
	 * All old values for pre 1.0 version of a plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $old_keys = array(
		'pepipost_ssl',
		'pepipost_port',
		'pepipost_pass',
		'pepipost_user',
		'smtp_pass',
		'smtp_user',
		'smtp_auth',
		'smtp_ssl',
		'smtp_port',
		'smtp_host',
		'mail_set_return_path',
		'mailer',
		'mail_from_name',
		'mail_from',
		'wp_mail_smtp_am_notifications_hidden',
	);

	/**
	 * Old values, taken from $old_keys options.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $old_values = array();

	/**
	 * Converted array of data from previous option values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $new_values = array();

	/**
	 * Migration constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( $this->is_migrated() ) {
			return;
		}

		$this->old_values = $this->get_old_values();
		$this->new_values = $this->get_converted_options();

		Options::init()->set( $this->new_values, true );

		// Removing all old options will be enabled some time in the future.
		// $this->clean_deprecated_data();
	}

	/**
	 * Whether we already migrated or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_migrated() {

		$is_migrated = false;
		$new_values  = get_option( Options::META_KEY, array() );

		if ( ! empty( $new_values ) ) {
			$is_migrated = true;
		}

		return $is_migrated;
	}

	/**
	 * Get all old values from DB.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_old_values() {

		$old_values = array();

		foreach ( $this->old_keys as $old_key ) {
			$value = get_option( $old_key, '' );

			if ( ! empty( $value ) ) {
				$old_values[ $old_key ] = $value;
			}
		}

		return $old_values;
	}

	/**
	 * Convert old values from key=>value to a multidimensional array of data.
	 *
	 * @since 1.0.0
	 */
	protected function get_converted_options() {

		$converted = array();

		foreach ( $this->old_keys as $old_key ) {

			$old_value = isset( $this->old_values[ $old_key ] ) ? $this->old_values[ $old_key ] : '';

			switch ( $old_key ) {
				case 'pepipost_user':
				case 'pepipost_pass':
				case 'pepipost_port':
				case 'pepipost_ssl':
					// Do not migrate pepipost options if it's not activated at the moment.
					if ( isset( $this->old_values['mailer'] ) && $this->old_values['mailer'] === 'pepipost' ) {
						$shortcut = explode( '_', $old_key );

						if ( $old_key === 'pepipost_ssl' ) {
							$converted[ $shortcut[0] ]['encryption'] = $old_value;
						} else {
							$converted[ $shortcut[0] ][ $shortcut[1] ] = $old_value;
						}
					}
					break;

				case 'smtp_host':
				case 'smtp_port':
				case 'smtp_ssl':
				case 'smtp_auth':
				case 'smtp_user':
				case 'smtp_pass':
					$shortcut = explode( '_', $old_key );

					if ( $old_key === 'smtp_ssl' ) {
						$converted[ $shortcut[0] ]['encryption'] = $old_value;
					} elseif ( $old_key === 'smtp_auth' ) {
						$converted[ $shortcut[0] ][ $shortcut[1] ] = ( $old_value === 'true' ? 'yes' : 'no' );
					} else {
						$converted[ $shortcut[0] ][ $shortcut[1] ] = $old_value;
					}

					break;

				case 'mail_from':
					$converted['mail']['from_email'] = $old_value;
					break;
				case 'mail_from_name':
					$converted['mail']['from_name'] = $old_value;
					break;
				case 'mail_set_return_path':
					$converted['mail']['return_path'] = ( $old_value === 'true' );
					break;
				case 'mailer':
					$converted['mail']['mailer'] = $old_value;
					break;
				case 'wp_mail_smtp_am_notifications_hidden':
					$converted['general']['am_notifications_hidden'] = ( isset( $old_value ) && $old_value === 'true' );
					break;
			}
		}

		$converted = $this->get_converted_constants_options( $converted );

		return $converted;
	}

	/**
	 * Some users use constants in wp-config.php to define values.
	 * We need to prioritize them and reapply data to options.
	 * Use only those that are actually defined.
	 *
	 * @since 1.0.0
	 *
	 * @param array $converted
	 *
	 * @return array
	 */
	protected function get_converted_constants_options( $converted ) {

		// Are we configured via constants?
		if ( ! defined( 'WPMS_ON' ) || ! WPMS_ON ) {
			return $converted;
		}

		/*
		 * Mail settings.
		 */
		if ( defined( 'WPMS_MAIL_FROM' ) ) {
			$converted['mail']['from_email'] = WPMS_MAIL_FROM;
		}
		if ( defined( 'WPMS_MAIL_FROM_NAME' ) ) {
			$converted['mail']['from_name'] = WPMS_MAIL_FROM_NAME;
		}
		if ( defined( 'WPMS_MAILER' ) ) {
			$converted['mail']['mailer'] = WPMS_MAILER;
		}
		if ( defined( 'WPMS_SET_RETURN_PATH' ) ) {
			$converted['mail']['return_path'] = WPMS_SET_RETURN_PATH;
		}

		/*
		 * SMTP settings.
		 */
		if ( defined( 'WPMS_SMTP_HOST' ) ) {
			$converted['smtp']['host'] = WPMS_SMTP_HOST;
		}
		if ( defined( 'WPMS_SMTP_PORT' ) ) {
			$converted['smtp']['port'] = WPMS_SMTP_PORT;
		}
		if ( defined( 'WPMS_SSL' ) ) {
			$converted['smtp']['ssl'] = WPMS_SSL;
		}
		if ( defined( 'WPMS_SMTP_AUTH' ) ) {
			$converted['smtp']['auth'] = WPMS_SMTP_AUTH;
		}
		if ( defined( 'WPMS_SMTP_USER' ) ) {
			$converted['smtp']['user'] = WPMS_SMTP_USER;
		}
		if ( defined( 'WPMS_SMTP_PASS' ) ) {
			$converted['smtp']['pass'] = WPMS_SMTP_PASS;
		}

		return $converted;
	}

	/**
	 * Delete all old values that are stored separately each.
	 *
	 * @since 1.0.0
	 */
	protected function clean_deprecated_data() {

		foreach ( $this->old_keys as $old_key ) {
			delete_option( $old_key );
		}
	}
}
