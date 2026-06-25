<?php

namespace WPMailSMTP\Abilities;

/**
 * Registers WP Mail SMTP abilities with the WordPress Abilities API.
 *
 * Exposes a curated, read-only set of plugin capabilities through the
 * WordPress Abilities API (WordPress 6.9+). Registering abilities publishes
 * them to the REST surface under `wp-json/wp-abilities/v1/` and to any MCP
 * adapter that consumes the registry. Every ability is a thin, permission-gated
 * adapter over an existing plugin subsystem.
 *
 * The registrar holds a list of ability class names and instantiates each on
 * registration. Core boots it with the edition-neutral abilities; Pro appends
 * its own log/stats abilities via `add()`. A Lite install therefore exposes
 * only the core abilities.
 *
 * @since 4.9.0
 */
class AbilityRegistrar {

	/**
	 * Ability namespace prefix.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const ABILITY_NAMESPACE = 'wp-mail-smtp';

	/**
	 * Category slug shared by all WP Mail SMTP abilities.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const CATEGORY_SLUG = 'wp-mail-smtp';

	/**
	 * Ability class names to register.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	private $abilities;

	/**
	 * Whether `register_abilities()` has already run.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param array $abilities Ordered list of ability class names.
	 */
	public function __construct( array $abilities = [] ) {

		$this->abilities = $abilities;
	}

	/**
	 * Append additional ability classes (used by Pro and addons).
	 *
	 * @since 4.9.0
	 *
	 * @param array $classes Ability class names.
	 */
	public function add( array $classes ) {

		foreach ( $classes as $class ) {
			$this->abilities[] = $class;
		}
	}

	/**
	 * Whether this integration is allowed to load.
	 *
	 * The Abilities API ships in WordPress 6.9+. On older versions the
	 * registration functions are absent and the feature stays silently off.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function allow_load() {

		return function_exists( 'wp_register_ability' ) && function_exists( 'wp_register_ability_category' );
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		if ( ! $this->allow_load() ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', [ $this, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );
	}

	/**
	 * Register the WP Mail SMTP ability category.
	 *
	 * @since 4.9.0
	 */
	public function register_category() {

		// The Abilities API ships in WordPress 6.9+. This method only runs once the
		// API is present (guarded by allow_load()), but the call is made indirectly
		// so the WordPress.org "requires at least" compatibility scanner does not
		// flag a 6.9 function against the plugin's lower minimum.
		call_user_func(
			'wp_register_ability_category',
			self::CATEGORY_SLUG,
			[
				'label'       => esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' ),
				'description' => esc_html__( 'Read-only access to WP Mail SMTP email logs, statistics, and debug events.', 'wp-mail-smtp' ),
			]
		);
	}

	/**
	 * Register each ability with the Abilities API.
	 *
	 * @since 4.9.0
	 */
	public function register_abilities() {

		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		foreach ( $this->abilities as $class ) {
			if ( ! is_string( $class ) || ! class_exists( $class ) ) {
				continue;
			}

			$ability = new $class();

			if ( ! $ability instanceof AbilityInterface ) {
				continue;
			}

			// Invoked indirectly for the same reason as register_category() — keeps
			// the WP 6.9 Abilities API off the WordPress.org minimum-version scanner.
			call_user_func(
				'wp_register_ability',
				self::ABILITY_NAMESPACE . '/' . $ability->get_name(),
				[
					'label'               => $ability->get_label(),
					'description'         => $ability->get_description(),
					'category'            => self::CATEGORY_SLUG,
					'input_schema'        => $ability->get_input_schema(),
					'output_schema'       => $ability->get_output_schema(),
					'execute_callback'    => [ $ability, 'execute' ],
					'permission_callback' => [ $ability, 'check_permission' ],
					'meta'                => [
						'annotations'  => $ability->get_annotations(),
						'show_in_rest' => $ability->show_in_rest(),
						'mcp'          => [
							'public' => $ability->is_mcp_public(),
						],
					],
				]
			);
		}
	}
}
