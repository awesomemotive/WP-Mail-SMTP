<?php

namespace WPMailSMTP\WPCLI\Commands;

use stdClass;
use WP_CLI;
use WP_CLI\Utils;
use WPMailSMTP\Helpers\Data;
use WPMailSMTP\Options;
use WPMailSMTP\WPCLI\Options\Help;
use WPMailSMTP\WPCLI\Options\Registry;
use WPMailSMTP\WPCLI\Options\Writer;

/**
 * Read and write individual WP Mail SMTP settings.
 *
 * @since 4.9.0
 */
class Option {

	/**
	 * Mask shown in place of a sensitive value's content.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const MASK = '********';

	/**
	 * One-line summary passed to WP_CLI::add_command() as `shortdesc`.
	 *
	 * WP-CLI ignores the class docblock once a longdesc is passed and falls back
	 * to boilerplate, so the summary is supplied explicitly.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function shortdesc() {

		return __( 'Read and write individual WP Mail SMTP settings.', 'wp-mail-smtp' );
	}

	/**
	 * Build the longdesc passed to WP_CLI::add_command().
	 *
	 * @since 4.9.0
	 *
	 * @param Registry $registry Provides the configuration-flags enumeration.
	 *
	 * @return string
	 */
	public static function help( Registry $registry ) {

		$flags = Help::configuration_flags( $registry );

		$action_desc         = __( 'One of: get, set, list.', 'wp-mail-smtp' );
		$flag_desc           = __( 'Dotted flag (e.g. smtp.host). Required for get and set.', 'wp-mail-smtp' );
		$value_desc          = __( 'Value (required for set; can also be supplied via --value-file or env var).', 'wp-mail-smtp' );
		$value_file_desc     = __( 'Read a sensitive value from a file.', 'wp-mail-smtp' );
		$show_sensitive_desc = __( 'Show sensitive values instead of masking them.', 'wp-mail-smtp' );
		$format_desc         = __( 'Output format for `list`. table | json | yaml. Default: table.', 'wp-mail-smtp' );

		return <<<HELP
## OPTIONS

<action>
: {$action_desc}

[<flag>]
: {$flag_desc}

[<value>]
: {$value_desc}

[--value-file=<path>]
: {$value_file_desc}

[--show-sensitive]
: {$show_sensitive_desc}

[--format=<format>]
: {$format_desc}

## EXAMPLES

    wp wp-mail-smtp option get mail.from_email
    wp wp-mail-smtp option set smtp.host mail.example.com
    wp wp-mail-smtp option set sendgrid.api_key --value-file=/run/secret/sg
    wp wp-mail-smtp option list --format=json

{$flags}
HELP;
	}

	/**
	 * Execute the `wp wp-mail-smtp option` command.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) {

		$action = $args[0] ?? null;

		switch ( $action ) {
			case 'get':
				$this->cmd_get( $args, $assoc_args );
				break;

			case 'set':
				$this->cmd_set( $args, $assoc_args );
				break;

			case 'list':
				$this->cmd_list( $assoc_args );
				break;

			default:
				WP_CLI::error( __( 'Action must be one of: get, set, list.', 'wp-mail-smtp' ) );
		}
	}

	/**
	 * Print a single key's value.
	 *
	 * `smtp.pass` is decrypted via Options::get() before display. Keys absent
	 * from storage report "(not set)" via WP_CLI::log so empty-stdout scripts
	 * still work.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	private function cmd_get( array $args, array $assoc_args ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$flag = $args[1] ?? null;

		if ( $flag === null ) {
			WP_CLI::error( __( 'Usage: wp wp-mail-smtp option get <group>.<key>', 'wp-mail-smtp' ) );
		}

		$registry = new Registry();
		$arg      = $registry->find( $flag );

		if ( $arg === null ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the unknown dotted CLI flag (e.g. smtp.host). The flag itself is not translated. */
					__( 'Unknown flag: %s', 'wp-mail-smtp' ),
					$flag
				)
			);
		}

		$storage_path = $this->storage_path( $arg );
		$raw_options  = get_option( Options::META_KEY, [] );

		$sentinel = new stdClass();
		$value    = Data::get( $raw_options, $storage_path, $sentinel );

		if ( $value === $sentinel ) {
			WP_CLI::log( __( '(not set)', 'wp-mail-smtp' ) );

			return;
		}

		// smtp.pass is the only encrypted key; route it through Options::get()
		// so the stored ciphertext is decrypted before display.
		if ( $storage_path === 'smtp.pass' ) {
			$value = Options::init()->get( 'smtp', 'pass' );
		}

		$value   = $this->reverse_enum_storage( $arg, $value );
		$display = $this->scalarize( $value );
		$mask    = ! empty( $arg['sensitive'] ) && ! isset( $assoc_args['show-sensitive'] ) && $display !== '';

		WP_CLI::log( $mask ? self::MASK : $display );
	}

	/**
	 * Write a single key's value to the stored options.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	private function cmd_set( array $args, array $assoc_args ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$flag = $args[1] ?? null;

		if ( $flag === null ) {
			WP_CLI::error( __( 'Usage: wp wp-mail-smtp option set <group>.<key> <value>', 'wp-mail-smtp' ) );
		}

		$registry = new Registry();
		$arg      = $registry->find( $flag );

		if ( $arg === null ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the unknown dotted CLI flag (e.g. smtp.host). The flag itself is not translated. */
					__( 'Unknown flag: %s', 'wp-mail-smtp' ),
					$flag
				)
			);
		}

		// Source the value: positional > --value-file > env var.
		$assoc_args_for_writer = [];

		if ( isset( $args[2] ) ) {
			$assoc_args_for_writer[ $flag ] = $args[2];
		} elseif ( isset( $assoc_args['value-file'] ) ) {
			$assoc_args_for_writer[ $flag . '-file' ] = $assoc_args['value-file'];
		}

		$writer = new Writer( $registry );
		$value  = $writer->resolve_single( $flag, $assoc_args_for_writer );

		if ( $value === null ) {
			WP_CLI::error(
				sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. smtp.host). %2$s is the environment variable name the operator can use (e.g. WPMS_SMTP_HOST). Flag and env var name are not translated. */
					__( 'No value provided for --%1$s (positional, --value-file, or env var %2$s).', 'wp-mail-smtp' ),
					$flag,
					$arg['env_var'] ?? ( 'WPMS_' . strtoupper( str_replace( '.', '_', $flag ) ) )
				)
			);
		}

		$writer->validate_single( $flag, $value );
		$result = $writer->write_single( $flag, $value );

		// If the single flag we were asked to set is shadowed by a
		// wp-config constant, write_single() already warned and stored
		// nothing. Surface that as an error so shell pipelines like
		// `wp ... option set X Y && echo ok` don't falsely succeed.
		if ( empty( $result['written'] ) ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the dotted CLI flag whose underlying option is overridden by a wp-config constant (e.g. smtp.host). The flag itself is not translated. */
					__( 'Could not store %s — value is shadowed by a wp-config constant.', 'wp-mail-smtp' ),
					$flag
				)
			);
		}

		WP_CLI::success(
			sprintf(
				/* translators: %s is the dotted CLI flag that was updated (e.g. smtp.host). The flag itself is not translated. */
				__( 'Updated %s.', 'wp-mail-smtp' ),
				$flag
			)
		);
	}

	/**
	 * List stored option values under their operator-facing flag names.
	 *
	 * Only flags physically present in storage are surfaced, so output reflects
	 * what the operator has actually set. `smtp.pass` is decrypted via
	 * Options::get(). Sensitive values are masked unless --show-sensitive.
	 *
	 * @since 4.9.0
	 *
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	private function cmd_list( array $assoc_args ) {

		$show_sensitive = isset( $assoc_args['show-sensitive'] );
		$format         = $assoc_args['format'] ?? 'table';
		$registry       = new Registry();

		$rows        = [];
		$raw_options = get_option( Options::META_KEY, [] );
		$sentinel    = new stdClass();

		foreach ( $registry->get_args() as $arg ) {
			$storage_path = $this->storage_path( $arg );
			$value        = Data::get( $raw_options, $storage_path, $sentinel );

			if ( $value === $sentinel ) {
				continue;
			}

			if ( $storage_path === 'smtp.pass' ) {
				$value = Options::init()->get( 'smtp', 'pass' );
			}

			$value   = $this->reverse_enum_storage( $arg, $value );
			$display = $this->scalarize( $value );

			if ( ! empty( $arg['sensitive'] ) && ! $show_sensitive && $display !== '' ) {
				$display = self::MASK;
			}

			$rows[] = [
				'flag'  => $arg['flag'],
				'value' => $display,
			];
		}

		Utils\format_items( $format, $rows, [ 'flag', 'value' ] );
	}

	/**
	 * Flatten a value to its string display form.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	private function scalarize( $value ) {

		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_array( $value ) ) {
			return wp_json_encode( $value );
		}

		return (string) $value;
	}

	/**
	 * Resolve the storage path for an arg. Mirrors Writer::storage_path() —
	 * uses the arg's explicit `storage_path` if set, otherwise falls back to
	 * the flag itself.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg Registry arg.
	 *
	 * @return string
	 */
	private function storage_path( array $arg ) {

		return ! empty( $arg['storage_path'] ) ? $arg['storage_path'] : $arg['flag'];
	}

	/**
	 * Reverse the `enum_storage_map` translation for reads: when the stored
	 * value matches one of the map's storage values, return the corresponding
	 * operator-facing value. Values not in the map (and non-enum args) pass
	 * through unchanged, keeping operator-facing values consistent across
	 * writes and reads.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg   Registry arg.
	 * @param mixed $value Stored value.
	 *
	 * @return mixed
	 */
	private function reverse_enum_storage( array $arg, $value ) {

		if ( empty( $arg['enum_storage_map'] ) || ! is_array( $arg['enum_storage_map'] ) ) {
			return $value;
		}

		$reverse = array_flip( $arg['enum_storage_map'] );

		if ( is_string( $value ) && array_key_exists( $value, $reverse ) ) {
			return $reverse[ $value ];
		}

		return $value;
	}
}
