<?php

namespace WPMailSMTP;

use WPMailSMTP\Tasks\Meta;

/**
 * Class Migration helps migrate plugin options, DB tables and more.
 *
 * @since 1.0.0 Migrate all plugin options saved from separate WP options into one.
 * @since 2.1.0 Major overhaul of this class to use DB migrations (or any other migrations per version).
 */
class Migration {

	/**
	 * Version of the latest migration.
	 *
	 * @since 2.1.0
	 */
	const VERSION = 2;

	/**
	 * Option key where we save the current migration version.
	 *
	 * @since 2.1.0
	 */
	const OPTION_NAME = 'wp_mail_smtp_migration_version';

	/**
	 * Current migration version, received from self::OPTION_NAME WP option
	 *
	 * @since 2.1.0
	 *
	 * @var int
	 */
	protected $cur_ver;

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
	 * @since 2.1.0 Redefined constructor - major overhaul.
	 */
	public function __construct() {

		$this->cur_ver = self::get_cur_version();

		$this->maybe_migrate();
	}

	/**
	 * Static on purpose, to get current migration version without __construct() and validation.
	 *
	 * @since 2.1.0
	 *
	 * @return int
	 */
	public static function get_cur_version() {

		return (int) get_option( self::OPTION_NAME, 0 );
	}

	/**
	 * Run the migration if needed.
	 *
	 * @since 2.1.0
	 */
	protected function maybe_migrate() {

		if ( ! is_admin() ) {
			return;
		}

		if ( version_compare( $this->cur_ver, self::VERSION, '<' ) ) {
			$this->run( self::VERSION );
		}
	}

	/**
	 * Actual migration launcher.
	 *
	 * @since 2.1.0
	 *
	 * @param int $version The version of migration to run.
	 */
	protected function run( $version ) {

		$function_version = (int) $version;

		if ( method_exists( $this, 'migrate_to_' . $function_version ) ) {
			$this->{'migrate_to_' . $function_version}();
		} else {
			$message = sprintf( /* translators: %1$s - WP Mail SMTP, %2$s - error message. */
				esc_html__( 'There was an error while upgrading the database. Please contact %1$s support with this information: %2$s.', 'wp-mail-smtp' ),
				'<strong>WP Mail SMTP</strong>',
				'<code>migration from v' . self::get_cur_version() . ' to v' . self::VERSION . ' failed. Plugin version: v' . WPMS_PLUGIN_VER . '</code>'
			);

			WP::add_admin_notice( $message, WP::ADMIN_NOTICE_ERROR );
		}
	}

	/**
	 * Update migration version in options table.
	 *
	 * @since 2.1.0
	 *
	 * @param int $version Migration version.
	 */
	protected function update_db_ver( $version ) {

		if ( empty( $version ) ) {
			$version = self::VERSION;
		}

		// Autoload it, because this value is checked all the time
		// and no need to request it separately from all autoloaded options.
		update_option( self::OPTION_NAME, $version, true );
	}

	/**
	 * Prevent running the same migration twice.
	 * Run migration only when required.
	 *
	 * @since 2.1.0
	 *
	 * @param string $version The version of migration to check for potential execution.
	 */
	protected function maybe_required_older_migrations( $version ) {

		if ( version_compare( $this->cur_ver, $version, '<' ) ) {
			$this->run( $version );
		}
	}

	/**
	 * Migration from 0.x to 1.0.0.
	 * Move separate plugin WP options to one main plugin WP option setting.
	 *
	 * @since 2.1.0
	 */
	private function migrate_to_1() {

		if ( $this->is_migrated() ) {
			return;
		}

		$this->old_values = $this->get_old_values();
		$this->new_values = $this->get_converted_options();

		Options::init()->set( $this->new_values, true );

		$this->update_db_ver( 1 );
	}

	/**
	 * Migration from 1.x to 2.1.0.
	 * Create Tasks\Meta table, if it does not exist.
	 *
	 * @since 2.1.0
	 */
	private function migrate_to_2() {

		$this->maybe_required_older_migrations( 1 );

		$meta = new Meta();

		// Create the table if it doesn't exist.
		if ( $meta && ! $meta->table_exists() ) {
			$meta->create_table();
		}

		$this->update_db_ver( 2 );
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
