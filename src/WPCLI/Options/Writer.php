<?php

namespace WPMailSMTP\WPCLI\Options;

use WP_CLI;
use WPMailSMTP\Helpers\Data;
use WPMailSMTP\Options;

/**
 * Resolves passed CLI args to values, validates them against the
 * registry, and writes them through Options::set().
 *
 * @since 4.9.0
 */
class Writer {

	/**
	 * Registry of args this writer resolves against.
	 *
	 * @since 4.9.0
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param Registry|null $registry Optional injected registry; defaults to a fresh instance.
	 */
	public function __construct( Registry $registry = null ) {

		$this->registry = $registry !== null ? $registry : new Registry();
	}

	/**
	 * Resolve every registry arg against the assoc args passed on the CLI.
	 *
	 * Precedence per arg: literal flag value > --<flag>-file=<path> > env var.
	 * Returns a map of flag => value for flags that resolved to something.
	 *
	 * @since 4.9.0
	 *
	 * @param array $assoc_args WP-CLI assoc args.
	 *
	 * @return array
	 */
	public function resolve( array $assoc_args ) {

		$resolved = [];

		foreach ( $this->registry->get_args() as $arg ) {
			$value = $this->resolve_one_arg( $arg, $assoc_args );

			if ( $value !== null ) {
				$resolved[ $arg['flag'] ] = $this->coerce( $arg, $value );
			}
		}

		return $resolved;
	}

	/**
	 * Resolve a single flag by name.
	 *
	 * @since 4.9.0
	 *
	 * @param string $flag       Dotted flag (e.g. `smtp.host`).
	 * @param array  $assoc_args WP-CLI assoc args.
	 *
	 * @return mixed|null Coerced value, or null if not resolved.
	 */
	public function resolve_single( $flag, array $assoc_args ) {

		$arg = $this->registry->find( $flag );

		if ( $arg === null ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the unknown dotted CLI flag (e.g. smtp.host). The flag itself is not translated. */
					__( 'Unknown flag: %s', 'wp-mail-smtp' ),
					$flag
				)
			);
		}

		$value = $this->resolve_one_arg( $arg, $assoc_args );

		return $value === null ? null : $this->coerce( $arg, $value );
	}

	/**
	 * Validate the resolved map. Enforces required, required_if, type, enum.
	 * Accumulates ALL errors and reports them in one WP_CLI::error call.
	 *
	 * @since 4.9.0
	 *
	 * @param array $resolved Map of flag => coerced value.
	 *
	 * @return void
	 */
	public function validate( array $resolved ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded

		$errors         = [];
		$reported_gates = []; // De-dup partial-chain "missing gate" messages across all rules.

		foreach ( $this->registry->get_args() as $arg ) {
			$flag    = $arg['flag'];
			$present = array_key_exists( $flag, $resolved );

			// Required.
			if ( ! empty( $arg['required'] ) && ! $present ) {
				$errors[] = sprintf(
					/* translators: %s is the dotted CLI flag (e.g. mail.from_email). The flag itself is not translated. */
					__( 'Missing required flag: --%s', 'wp-mail-smtp' ),
					$flag
				);

				continue;
			}

			// Required-if.
			if ( ! empty( $arg['required_if'] ) ) {
				$rule_match = $this->required_if_evaluate( $arg['required_if'], $resolved );

				if ( $rule_match['all_match'] && ! $present ) {
					$errors[] = sprintf(
						/* translators: %1$s is the dotted CLI flag that is missing (e.g. smtp.host). %2$s is a human-readable condition (e.g. "--mail.mailer=smtp"). Flags and values are not translated. */
						__( 'Missing required flag: --%1$s (required when %2$s)', 'wp-mail-smtp' ),
						$flag,
						$this->required_if_human( $arg['required_if'] )
					);

					continue;
				}

				// Partial-chain match: at least one gating flag matched but others are missing; report each missing gate once.
				if ( $rule_match['any_match'] && ! empty( $rule_match['missing'] ) ) {
					foreach ( $rule_match['missing'] as $missing_gate ) {
						if ( isset( $reported_gates[ $missing_gate ] ) ) {
							continue;
						}

						$reported_gates[ $missing_gate ] = true;

						$errors[] = sprintf(
							/* translators: %s is the dotted CLI flag that is missing (e.g. smtp.auth). The flag itself is not translated. */
							__( 'Missing required flag: --%s (needed to fully specify the configuration when other gating flags are set)', 'wp-mail-smtp' ),
							$missing_gate
						);
					}
				}
			}

			if ( ! $present ) {
				continue;
			}

			// Enum.
			if ( ( $arg['type'] ?? null ) === 'enum' && ! in_array( $resolved[ $flag ], $arg['enum'], true ) ) {
				$errors[] = sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. mail.mailer). %2$s is the value the operator provided. %3$s is a comma-separated list of allowed values. Flags and values are not translated. */
					__( 'Invalid value for --%1$s: %2$s (allowed: %3$s)', 'wp-mail-smtp' ),
					$flag,
					$resolved[ $flag ],
					implode( ', ', $arg['enum'] )
				);
			}

			// Email type sanity check.
			if ( ( $arg['type'] ?? null ) === 'email' && ! is_email( $resolved[ $flag ] ) ) {
				$errors[] = sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. mail.from_email). %2$s is the value the operator provided. The flag is not translated. */
					__( 'Invalid email for --%1$s: %2$s', 'wp-mail-smtp' ),
					$flag,
					$resolved[ $flag ]
				);
			}

			// Int type sanity check. coerce() leaves non-numeric strings unmodified so they surface here instead of a silent 0.
			if ( ( $arg['type'] ?? null ) === 'int' && ! is_numeric( $resolved[ $flag ] ) ) {
				$errors[] = sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. smtp.port). %2$s is the value the operator provided. The flag is not translated. */
					__( 'Invalid integer for --%1$s: %2$s', 'wp-mail-smtp' ),
					$flag,
					$resolved[ $flag ]
				);
			}
		}

		if ( ! empty( $errors ) ) {
			WP_CLI::error( __( 'Configuration errors:', 'wp-mail-smtp' ) . "\n  - " . implode( "\n  - ", $errors ) );
		}
	}

	/**
	 * Validate a single resolved flag/value pair without applying required/required_if.
	 * Used by `option set` where the operator is updating one key in isolation.
	 *
	 * @since 4.9.0
	 *
	 * @param string $flag  Dotted flag.
	 * @param mixed  $value Coerced value.
	 *
	 * @return void
	 */
	public function validate_single( $flag, $value ) {

		$arg = $this->registry->find( $flag );

		if ( $arg === null ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the unknown dotted CLI flag (e.g. smtp.host). The flag itself is not translated. */
					__( 'Unknown flag: %s', 'wp-mail-smtp' ),
					$flag
				)
			);
		}

		if ( ( $arg['type'] ?? null ) === 'enum' && ! in_array( $value, $arg['enum'], true ) ) {
			WP_CLI::error(
				sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. mail.mailer). %2$s is the value the operator provided. %3$s is a comma-separated list of allowed values. Flags and values are not translated. */
					__( 'Invalid value for --%1$s: %2$s (allowed: %3$s)', 'wp-mail-smtp' ),
					$flag,
					$value,
					implode( ', ', $arg['enum'] )
				)
			);
		}

		if ( ( $arg['type'] ?? null ) === 'email' && ! is_email( $value ) ) {
			WP_CLI::error(
				sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. mail.from_email). %2$s is the value the operator provided. The flag is not translated. */
					__( 'Invalid email for --%1$s: %2$s', 'wp-mail-smtp' ),
					$flag,
					$value
				)
			);
		}

		if ( ( $arg['type'] ?? null ) === 'int' && ! is_numeric( $value ) ) {
			WP_CLI::error(
				sprintf(
					/* translators: %1$s is the dotted CLI flag (e.g. smtp.port). %2$s is the value the operator provided. The flag is not translated. */
					__( 'Invalid integer for --%1$s: %2$s', 'wp-mail-smtp' ),
					$flag,
					$value
				)
			);
		}
	}

	/**
	 * Write the resolved map through Options::set().
	 *
	 * Each flag is written to its `storage_path` (see ::storage_path()). Keys
	 * shadowed by a wp-config constant are skipped and warned about: Options::set()
	 * forces the stored value to '' whenever the constant is defined, which would
	 * silently wipe the value.
	 *
	 * @since 4.9.0
	 *
	 * @param array $resolved Map of flag => coerced value.
	 *
	 * @return array Map with two keys: 'written' (flags actually stored) and
	 *               'shadowed' (flags skipped because a wp-config constant is set).
	 */
	public function write( array $resolved ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$result = [
			'written'  => [],
			'shadowed' => [],
		];

		if ( empty( $resolved ) ) {
			return $result;
		}

		$options = Options::init();

		$writable = [];

		foreach ( $resolved as $flag => $value ) {
			$arg  = $this->registry->find( $flag );
			$path = $this->storage_path( $arg !== null ? $arg : [ 'flag' => $flag ] );

			// Translate operator-facing enum values to storage values before the shadow check, so it operates on storage values.
			if ( $arg !== null && ! empty( $arg['enum_storage_map'] ) && is_array( $arg['enum_storage_map'] ) && array_key_exists( $value, $arg['enum_storage_map'] ) ) {
				$value = $arg['enum_storage_map'][ $value ];
			}

			// Shadow check is limited to the top-level group/key pair, matching WPMS's shallow WPMS_<GROUP>_<KEY> constants.
			$segments = explode( Data::KEY_SEPARATOR, $path, 3 );

			if ( isset( $segments[0], $segments[1] ) && $options->is_const_defined( $segments[0], $segments[1] ) ) {
				$result['shadowed'][] = $flag;
			} else {
				$writable[ $flag ] = [
					'value' => $value,
					'path'  => $path,
				];
			}
		}

		foreach ( $result['shadowed'] as $flag ) {
			WP_CLI::warning(
				sprintf(
					/* translators: %s is the dotted CLI flag whose underlying option is overridden by a wp-config constant (e.g. smtp.host). The flag itself is not translated. */
					__( 'A wp-config constant is defined for %s. The value was not stored. Remove the constant first, then re-run this command.', 'wp-mail-smtp' ),
					$flag
				)
			);
		}

		if ( empty( $writable ) ) {
			return $result;
		}

		$new = $options->get_all_raw();

		foreach ( $writable as $entry ) {
			Data::set( $new, $entry['path'], $entry['value'] );
		}

		$options->set( $new );

		$result['written'] = array_keys( $writable );

		return $result;
	}

	/**
	 * Write a single key (used by `option set`).
	 *
	 * @since 4.9.0
	 *
	 * @param string $flag  Dotted flag.
	 * @param mixed  $value Coerced value.
	 *
	 * @return array See ::write().
	 */
	public function write_single( $flag, $value ) {

		return $this->write( [ $flag => $value ] );
	}

	/**
	 * Resolve the storage path for an arg.
	 *
	 * Uses `storage_path` if set on the arg, otherwise falls back to the flag itself.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg Registry arg shape.
	 *
	 * @return string Dotted storage path, e.g. `alert_slack_webhook.connections.0.webhook_url`.
	 */
	private function storage_path( array $arg ) {

		return ! empty( $arg['storage_path'] ) ? $arg['storage_path'] : $arg['flag'];
	}

	/**
	 * Resolve a single arg's value from assoc args using
	 * literal > --<flag>-file > env var precedence.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg        Registry arg.
	 * @param array $assoc_args WP-CLI assoc args.
	 *
	 * @return string|null Raw string value, or null if not resolved.
	 */
	private function resolve_one_arg( array $arg, array $assoc_args ) {

		$flag      = $arg['flag'];
		$file_flag = $flag . '-file';

		if ( array_key_exists( $flag, $assoc_args ) ) {
			return (string) $assoc_args[ $flag ];
		}

		if ( array_key_exists( $file_flag, $assoc_args ) ) {
			$path = $assoc_args[ $file_flag ];

			if ( ! is_readable( $path ) ) {
				WP_CLI::error(
					sprintf(
						/* translators: %1$s is the dotted CLI flag with a -file suffix (e.g. smtp.pass-file). %2$s is the absolute file path that could not be read. */
						__( 'Cannot read --%1$s file: %2$s', 'wp-mail-smtp' ),
						$file_flag,
						$path
					)
				);
			}

			return rtrim( (string) file_get_contents( $path ) );
		}

		$env_var = $arg['env_var'] ?? ( 'WPMS_' . strtoupper( str_replace( '.', '_', $flag ) ) );
		$env     = getenv( $env_var );

		if ( $env !== false && $env !== '' ) {
			return $env;
		}

		return null;
	}

	/**
	 * Coerce a raw string value to the type declared by the arg.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg   Registry arg.
	 * @param mixed $value Raw value.
	 *
	 * @return mixed Coerced value.
	 */
	private function coerce( array $arg, $value ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$type = $arg['type'] ?? 'string';

		if ( $type === 'bool' ) {
			return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true );
		}

		if ( $type === 'int' ) {
			// Non-numeric values returned as-is so validate() can report them instead of silently casting to 0.
			return is_numeric( $value ) ? (int) $value : (string) $value;
		}

		return (string) $value;
	}

	/**
	 * Evaluate a required_if rule set against the resolved map.
	 *
	 * Returns:
	 *   - all_match: every gating flag is present AND equals its expected value.
	 *   - any_match: at least one gating flag is present AND equals its expected value.
	 *   - missing:   gating flags that aren't present in the resolved map.
	 *
	 * Callers use any_match + missing to detect a partial-chain match (a likely forgotten gating flag).
	 *
	 * @since 4.9.0
	 *
	 * @param array $rules    Map of flag => expected value.
	 * @param array $resolved Map of flag => resolved value.
	 *
	 * @return array
	 */
	private function required_if_evaluate( array $rules, array $resolved ) {

		$any_match = false;
		$all_match = true;
		$missing   = [];

		foreach ( $rules as $flag => $expected ) {
			if ( ! array_key_exists( $flag, $resolved ) ) {
				$missing[] = $flag;
				$all_match = false;

				continue;
			}

			if ( $resolved[ $flag ] === $expected ) {
				$any_match = true;
			} else {
				$all_match = false;
			}
		}

		return [
			'all_match' => $all_match,
			'any_match' => $any_match,
			'missing'   => $missing,
		];
	}

	/**
	 * Render a required_if rule set as a human-readable "--flag=value and ..." string.
	 *
	 * @since 4.9.0
	 *
	 * @param array $rules Map of flag => expected value.
	 *
	 * @return string
	 */
	private function required_if_human( array $rules ) {

		$parts = [];

		foreach ( $rules as $flag => $expected ) {
			$rendered = is_bool( $expected ) ? ( $expected ? 'true' : 'false' ) : (string) $expected;
			$parts[]  = sprintf( '--%s=%s', $flag, $rendered );
		}

		return implode( ' and ', $parts );
	}
}
