<?php

namespace WPMailSMTP\Admin\Dashboard;

use WPMailSMTP\Admin\Dashboard\Widgets\AbstractWidget;

/**
 * Dashboard AJAX endpoints.
 *
 * @since 4.10.0
 */
class Ajax {

	/**
	 * Access resolver.
	 *
	 * @since 4.10.0
	 *
	 * @var AccessResolver
	 */
	private $access_resolver;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessResolver $access_resolver Access resolver.
	 */
	public function __construct( AccessResolver $access_resolver ) {

		$this->access_resolver = $access_resolver;
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks(): void {

		add_action( 'wp_ajax_wp_mail_smtp_dashboard_save_widget_settings', [ $this, 'save_widget_settings' ] );
		add_action( 'wp_ajax_wp_mail_smtp_dashboard_reset_widget_settings', [ $this, 'reset_widget_settings' ] );
		add_action( 'wp_ajax_wp_mail_smtp_dashboard_dismiss', [ $this, 'dismiss' ] );
	}

	/**
	 * Verify the nonce and capability shared by every dashboard AJAX action.
	 *
	 * @since 4.10.0
	 *
	 * @return AccessContext
	 */
	protected function validate_request(): AccessContext {

		if ( ! check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) ) {
			$this->send_error( 'nonce', esc_html__( 'Your session has expired. Please reload the page.', 'wp-mail-smtp' ) );
		}

		$access = $this->access_resolver->get_context();

		if ( ! $access->can_manage() ) {
			$this->send_error( 'cap', esc_html__( 'You do not have permission to do this.', 'wp-mail-smtp' ) );
		}

		return $access;
	}

	/**
	 * Persist a per-widget settings map to user meta.
	 *
	 * @since 4.10.0
	 */
	public function save_widget_settings(): void {

		$access = $this->validate_request();

		// Nonce verified via validate_request() before this runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$widget_id = sanitize_key( $_POST['widget'] ?? '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via validate_request() before this runs.
		$settings = ! empty( $_POST['settings'] ) ? map_deep( (array) wp_unslash( $_POST['settings'] ), 'sanitize_text_field' ) : [];

		if ( $widget_id === '' ) {
			$this->send_error( 'invalid', esc_html__( 'Invalid widget.', 'wp-mail-smtp' ) );
		}

		$all      = Helpers::get_user_meta_array( AbstractWidget::SETTINGS_META_KEY );
		$existing = (array) ( $all[ $widget_id ] ?? [] );

		// The gear form posts only the fields it rendered, so a setting it did not carry
		// keeps its stored value instead of being wiped.
		$all[ $widget_id ] = $settings + $existing;

		Helpers::update_user_meta_array( AbstractWidget::SETTINGS_META_KEY, $all );

		$widget = $this->get_gear_widget( $widget_id, $access );

		// The reset control follows the saved state, and the widgets that re-render
		// client-side never rebuild the popover markup that would carry it.
		wp_send_json_success(
			[
				'has_custom_settings' => $widget && $widget->has_custom_settings(),
			]
		);
	}

	/**
	 * Drop a widget's stored settings, which puts it back on its defaults.
	 *
	 * @since 4.10.0
	 */
	public function reset_widget_settings(): void {

		$access = $this->validate_request();

		// Nonce verified via validate_request() before this runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$widget_id = sanitize_key( $_POST['widget'] ?? '' );

		if ( $widget_id === '' ) {
			$this->send_error( 'invalid', esc_html__( 'Invalid widget.', 'wp-mail-smtp' ) );
		}

		$widget   = $this->get_gear_widget( $widget_id, $access );
		$defaults = $widget ? $widget->get_default_settings() : [];
		$all      = Helpers::get_user_meta_array( AbstractWidget::SETTINGS_META_KEY );

		// Only the keys the gear owns are dropped. The same bucket can carry state the
		// popover never shows, so a settings reset has no business forgetting it.
		$all[ $widget_id ] = array_diff_key( (array) ( $all[ $widget_id ] ?? [] ), $defaults );

		Helpers::update_user_meta_array( AbstractWidget::SETTINGS_META_KEY, $all );

		// The defaults travel back so the client can put the popover and the widget on
		// them through the same path a save takes.
		wp_send_json_success(
			[
				'settings' => $defaults,
			]
		);
	}

	/**
	 * Dismiss a single alert card for the current user, keyed by card id.
	 *
	 * @since 4.10.0
	 */
	public function dismiss(): void {

		$this->validate_request();

		// Nonce verified via validate_request() before this runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$card_id = sanitize_key( $_POST['card'] ?? '' );

		if ( $card_id === '' ) {
			$this->send_error( 'invalid', esc_html__( 'Invalid card.', 'wp-mail-smtp' ) );
		}

		$dismissed = Helpers::get_user_meta_array( AccessContext::DISMISSED_META_KEY );

		$dismissed[ $card_id ] = time();

		Helpers::update_user_meta_array( AccessContext::DISMISSED_META_KEY, $dismissed );

		wp_send_json_success();
	}

	/**
	 * Resolve a widget that owns a gear popover by its identifier.
	 *
	 * @since 4.10.0
	 *
	 * @param string        $widget_id Widget identifier.
	 * @param AccessContext $access    Access context.
	 *
	 * @return AbstractWidget|null
	 */
	protected function get_gear_widget( string $widget_id, AccessContext $access ): ?AbstractWidget {

		$classes = [
			'stat_cards'               => Widgets\StatCards::class,
			'emails_overview'          => Widgets\EmailsOverview::class,
			'connections'              => Widgets\Connections::class,
			'email_log'                => Widgets\EmailLog::class,
			'email_sources'            => Widgets\EmailSources::class,
			'setup_checklist_overview' => Widgets\SetupChecklistOverview::class,
			'getting_started'          => Widgets\GettingStarted::class,
			'features_upsell'          => Widgets\FeaturesUpsell::class,
			'growth_tools'             => Widgets\GrowthTools::class,
		];

		if ( ! isset( $classes[ $widget_id ] ) ) {
			return null;
		}

		return new $classes[ $widget_id ]( $access );
	}

	/**
	 * Send a JSON error envelope and stop execution.
	 *
	 * @since 4.10.0
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message Human-readable error message.
	 */
	protected function send_error( string $code, string $message ): void {

		wp_send_json_error(
			[
				'code'    => $code,
				'message' => $message,
			]
		);
	}
}
