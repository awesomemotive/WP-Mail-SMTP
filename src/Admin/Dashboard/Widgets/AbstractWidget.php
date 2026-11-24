<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\AccessContext;
use WPMailSMTP\Admin\Dashboard\Helpers;
use WPMailSMTP\Admin\Dashboard\WidgetState;

/**
 * Defines the widget contract and renders the shared card shell.
 *
 * @since 4.10.0
 */
abstract class AbstractWidget {

	/**
	 * Column placement: 'main' or 'sidebar'.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'main';

	/**
	 * Default sort position within the column.
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 10;

	/**
	 * User meta key holding per-widget settings.
	 *
	 * @since 4.10.0
	 */
	public const SETTINGS_META_KEY = 'wp_mail_smtp_dashboard_settings';

	/**
	 * Access context, the same instance for every widget in the request.
	 *
	 * @since 4.10.0
	 *
	 * @var AccessContext
	 */
	protected $access;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessContext $access Access context.
	 */
	public function __construct( AccessContext $access ) {

		$this->access = $access;
	}

	/**
	 * Get the widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	abstract public function get_id(): string;

	/**
	 * Get the widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	abstract public function get_title(): string;

	/**
	 * Get the widget state for the given access context.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	abstract public function get_state(): WidgetState;

	/**
	 * Print the widget body.
	 *
	 * @since 4.10.0
	 * @since 4.10.5 Prints instead of returning the markup.
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 */
	abstract protected function render_body( string $variant, array $data );

	/**
	 * Print the widget footer. Printing nothing hides the footer.
	 *
	 * @since 4.10.0
	 * @since 4.10.5 Prints instead of returning the markup.
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 */
	protected function render_footer( string $variant, array $data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Overridable default; the signature is the widget contract.
	}

	/**
	 * Print the widget head content: the title by default.
	 *
	 * The title row is only printed when the widget has a title or a settings menu,
	 * so a widget with a head of its own keeps a title.
	 *
	 * @since 4.10.0
	 * @since 4.10.5 Prints instead of returning the markup, the title by default.
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 */
	protected function render_head( string $variant, array $data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Overridable default; the signature is the widget contract.

		?>
		<h2 class="wpms-card__title wpms-card__title--widget"><?php echo esc_html( $this->get_title() ); ?></h2>
		<?php
	}

	/**
	 * Print the trailing edge of the widget head, beside the title. Nothing by default.
	 *
	 * @since 4.10.0
	 * @since 4.10.5 Prints instead of returning the markup.
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 */
	protected function render_head_aside( string $variant, array $data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Overridable default; the signature is the widget contract.
	}

	/**
	 * Extra state classes for the card root, beyond the id/variant ones the shell builds.
	 * `wpms-dashboard-widget-attention` opts the card into the hoist above the stat cards.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return array
	 */
	protected function get_extra_classes( string $variant, array $data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Overridable default; the signature is the widget contract.

		return [];
	}

	/**
	 * Print the widget into the shared card shell.
	 *
	 * The shell template calls back into the widget for each part, so every part
	 * prints its own escaped markup.
	 *
	 * @since 4.10.0
	 * @since 4.10.5 Prints the widget instead of returning its markup.
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string Always empty. Kept a string so callers written when it returned the markup do not fail.
	 */
	public function render( string $variant, array $data ): string {

		$settings_schema = $this->get_settings_schema( $variant, $data );
		$has_settings    = ! empty( $settings_schema );

		wp_mail_smtp_render(
			'dashboard/widget',
			[
				'id'             => $this->get_id(),
				'variant'        => $variant,
				'extra_classes'  => $this->get_extra_classes( $variant, $data ),
				'has_head'       => $this->get_title() !== '' || $has_settings,
				'has_settings'   => $has_settings,
				'head'           => fn() => $this->render_head( $variant, $data ),
				'head_aside'     => fn() => $this->render_head_aside( $variant, $data ),
				'settings'       => fn() => $this->render_settings( $settings_schema ),
				'body'           => fn() => $this->render_body( $variant, $data ),
				'footer'         => fn() => $this->render_footer( $variant, $data ),
				'is_dismissible' => $this->is_dismissible(),
			],
			true
		);

		return '';
	}

	/**
	 * Get the widget column placement.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_column(): string {

		return static::COLUMN;
	}

	/**
	 * Declare the gear-menu settings for this widget as a field schema; empty means no gear
	 * menu. The framework renders the popover and persists the fields.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return array
	 */
	protected function get_settings_schema( string $variant, array $data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Overridable default; the signature is the widget contract.

		return [];
	}

	/**
	 * Print the settings popover from a field schema via the shared template.
	 *
	 * @since 4.10.0
	 * @since 4.10.5 Prints instead of returning the markup.
	 *
	 * @param array $fields Settings field schema.
	 */
	private function render_settings( array $fields ): void {

		if ( empty( $fields ) ) {
			return;
		}

		wp_mail_smtp_render(
			'dashboard/widget-settings',
			[
				'fields'    => $fields,
				'widget_id' => $this->get_id(),
				'can_reset' => $this->has_custom_settings(),
			],
			true
		);
	}

	/**
	 * Whether the user has moved the widget off its default settings, which is what the
	 * gear's reset control offers to undo. Only the declared default keys are compared.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function has_custom_settings(): bool {

		$stored = $this->get_settings();

		foreach ( $this->get_default_settings() as $key => $default ) {
			if ( ! array_key_exists( $key, $stored ) ) {
				continue;
			}

			if ( is_array( $default ) ? $this->normalize_list( $stored[ $key ] ) !== $this->normalize_list( $default ) : (string) $stored[ $key ] !== (string) $default ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reduce a stored checklist value to a comparable set: the popover submits a hidden
	 * empty sentinel with every list, and checkbox order follows the markup.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed $value Stored or default list value.
	 *
	 * @return array
	 */
	private function normalize_list( $value ): array {

		$list = array_filter( array_map( 'strval', (array) $value ), 'strlen' );

		sort( $list );

		return $list;
	}

	/**
	 * The widget's default gear settings, shaped the way its popover submits them, so a
	 * reset can hand them straight to the client that renders the widget.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_default_settings(): array {

		return [];
	}

	/**
	 * Whether the primary connection has a real mailer, set up completely.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_mailer_configured() {

		$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();

		if ( in_array( $connection->get_mailer_slug(), [ '', 'mail' ], true ) ) {
			return false;
		}

		$mailer = $connection->get_mailer();

		return ! empty( $mailer ) && $mailer->is_mailer_complete();
	}

	/**
	 * Whether the widget can be dismissed by the user, which adds
	 * `wpms-dashboard-dismiss-container` to the card root.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_dismissible(): bool {

		return false;
	}

	/**
	 * Get the per-widget settings from user meta.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_settings(): array {

		$all = Helpers::get_user_meta_array( self::SETTINGS_META_KEY );

		return (array) ( $all[ $this->get_id() ] ?? [] );
	}

	/**
	 * Persist the per-widget settings into user meta.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Widget settings.
	 */
	public function save_settings( array $data ): void {

		$all = Helpers::get_user_meta_array( self::SETTINGS_META_KEY );

		$all[ $this->get_id() ] = $data;

		Helpers::update_user_meta_array( self::SETTINGS_META_KEY, $all );
	}
}
