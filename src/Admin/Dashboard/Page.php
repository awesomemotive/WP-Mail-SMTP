<?php

namespace WPMailSMTP\Admin\Dashboard;

use WPMailSMTP\WP;

/**
 * Dashboard admin page controller.
 *
 * @since 4.10.0
 */
class Page {

	/**
	 * Page slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'wp-mail-smtp-dashboard';

	/**
	 * Access resolver.
	 *
	 * @since 4.10.0
	 *
	 * @var AccessResolver
	 */
	private $access_resolver;

	/**
	 * Widget pipeline.
	 *
	 * @since 4.10.0
	 *
	 * @var WidgetPipeline
	 */
	private $widget_pipeline;

	/**
	 * Dashboard statistics.
	 *
	 * @since 4.10.0
	 *
	 * @var Stats
	 */
	private $stats;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessResolver $access_resolver Access resolver.
	 * @param WidgetPipeline $widget_pipeline Widget pipeline.
	 * @param Stats          $stats           Dashboard statistics.
	 */
	public function __construct( AccessResolver $access_resolver, WidgetPipeline $widget_pipeline, Stats $stats ) {

		$this->access_resolver = $access_resolver;
		$this->widget_pipeline = $widget_pipeline;
		$this->stats           = $stats;
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks(): void {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Get the Dashboard page URL.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public static function get_url(): string {

		return wp_mail_smtp()->get_admin()->get_admin_page_url( self::SLUG );
	}

	/**
	 * Enqueue Dashboard page assets.
	 *
	 * @since 4.10.0
	 */
	public function enqueue_assets(): void {

		if ( ! wp_mail_smtp()->get_admin()->is_admin_page( 'dashboard' ) ) {
			return;
		}

		$min = WP::asset_min();

		wp_enqueue_style(
			'wp-mail-smtp-dashboard',
			wp_mail_smtp()->assets_url . '/css/smtp-dashboard.min.css',
			[ 'wp-mail-smtp-admin' ],
			WPMS_PLUGIN_VER
		);

		wp_enqueue_script(
			'wp-mail-smtp-chart',
			wp_mail_smtp()->assets_url . '/js/vendor/chart.min.js',
			[ 'moment' ],
			'4.4.9',
			true
		);

		wp_enqueue_script(
			'wp-mail-smtp-dashboard',
			wp_mail_smtp()->assets_url . "/js/smtp-dashboard-page{$min}.js",
			$this->get_script_dependencies(),
			WPMS_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'wp-mail-smtp-dashboard',
			'wp_mail_smtp_dashboard',
			$this->get_localized_data()
		);

		// The Growth Tools widget's install CTA reuses the Setup Checklist's own install transport.
		wp_enqueue_script(
			'wp-mail-smtp-setup-checklist',
			wp_mail_smtp()->assets_url . "/js/smtp-setup-checklist{$min}.js",
			[ 'jquery', 'wp-mail-smtp-admin' ],
			WPMS_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'wp-mail-smtp-setup-checklist',
			'wp_mail_smtp_setup_checklist',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'plugins'  => [
					'installing' => esc_html__( 'Installing…', 'wp-mail-smtp' ),
					'activating' => esc_html__( 'Activating…', 'wp-mail-smtp' ),
					'activate'   => esc_html__( 'Activate', 'wp-mail-smtp' ),
					'installed'  => esc_html__( 'Installed', 'wp-mail-smtp' ),
					'error'      => esc_html__( 'Something went wrong. Please install the plugin from the Plugins page.', 'wp-mail-smtp' ),
				],
			]
		);
	}

	/**
	 * Get the Dashboard script dependencies. The Pro subclass adds flatpickr.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_script_dependencies(): array {

		return [ 'jquery', 'wp-mail-smtp-chart' ];
	}

	/**
	 * Get the data localized for the Dashboard page script.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_localized_data(): array {

		return [
			'modules' => [
				[
					'name' => 'widgetSettings',
					'path' => $this->get_module_url( 'smtp-dashboard-widget-settings' ),
				],
				[
					'name' => 'statCards',
					'path' => $this->get_module_url( 'smtp-dashboard-widget-stat-cards' ),
				],
				[
					'name' => 'emailsOverview',
					'path' => $this->get_module_url( 'smtp-dashboard-widget-emails-overview' ),
				],
				[
					'name' => 'emailSources',
					'path' => $this->get_module_url( 'smtp-dashboard-widget-email-sources' ),
				],
				[
					'name' => 'emailLog',
					'path' => $this->get_module_url( 'smtp-dashboard-widget-email-log' ),
				],
			],
			'i18n'    => [
				'sent'   => esc_html__( 'Sent', 'wp-mail-smtp' ),
				'failed' => esc_html__( 'Failed', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * URL for one dynamically imported dashboard module, version-stamped: nothing else
	 * appends the plugin version to a file reached by `import()`.
	 *
	 * @since 4.10.0
	 *
	 * @param string $handle Module file name, without the `.min` suffix or extension.
	 *
	 * @return string
	 */
	private function get_module_url( string $handle ): string {

		return add_query_arg(
			'ver',
			WPMS_PLUGIN_VER,
			wp_mail_smtp()->assets_url . '/js/' . $handle . WP::asset_min() . '.js'
		);
	}

	/**
	 * Get the date-range datepicker HTML. Empty on Lite; the Pro subclass overrides this.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	protected function get_datepicker_html(): string {

		return '';
	}

	/**
	 * The date range the first paint is scoped to, or null for the whole trailing series.
	 * An overriding tier must return the same range its AJAX re-render sends.
	 *
	 * @since 4.10.0
	 *
	 * @return array|null `[ from, to ]`, both `Y-m-d`.
	 */
	protected function get_initial_date_range(): ?array {

		return null;
	}

	/**
	 * Render the Dashboard page.
	 *
	 * @since 4.10.0
	 */
	public function output(): void {

		$access = $this->access_resolver->get_context();
		$range  = $this->get_initial_date_range();

		$data = [
			'stat_totals'           => $this->stats->get_stat_totals( $range ),
			'stat_deltas'           => $this->stats->get_stat_deltas( $range ),
			'chart_series'          => $this->stats->get_series( $range ),
			'has_high_failure_rate' => $this->stats->has_high_failure_rate(),
			'recent_failed_count'   => $this->stats->get_recent_failed_count(),
			'date_range'            => $range,
		];

		$this->widget_pipeline->init( $access );

		$view = [
			'datepicker'      => $this->get_datepicker_html(),
			'top_widgets'     => $this->widget_pipeline->render_column( 'top', $data ),
			'main_widgets'    => $this->widget_pipeline->render_column( 'main', $data ),
			'sidebar_widgets' => $this->widget_pipeline->render_column( 'sidebar', $data ),
		];

		echo '<div class="wrap wp-mail-smtp-page" id="wp-mail-smtp">';
		echo wp_mail_smtp_render( 'dashboard/page', $view, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
		echo '</div>';
	}
}
