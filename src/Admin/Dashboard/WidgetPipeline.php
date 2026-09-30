<?php

namespace WPMailSMTP\Admin\Dashboard;

/**
 * Evaluates widget states once per request and renders per-column widget HTML.
 *
 * @since 4.10.0
 */
class WidgetPipeline {

	/**
	 * Prepared widgets per column: `[ 'main' => [ [ 'widget' => AbstractWidget, 'variant' => string ], ... ], 'sidebar' => [ ... ] ]`.
	 *
	 * @since 4.10.0
	 *
	 * @var array
	 */
	private $widgets = [];

	/**
	 * Evaluate widget states against the given access context and group
	 * visible widgets per column, sorted.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessContext $access Access context.
	 */
	public function init( AccessContext $access ): void {

		$items = [];

		foreach ( $this->get_widgets( $access ) as $widget ) {
			$state = $widget->get_state();

			if ( ! $state->is_visible() ) {
				continue;
			}

			$items[] = [
				'widget'  => $widget,
				'variant' => $state->get_variant(),
				'order'   => $state->get_order_override() ?? $widget::ORDER,
				'column'  => $widget->get_column(),
			];
		}

		// A tied order otherwise falls back to usort()'s sort stability, silently: the
		// id is an explicit, visible tiebreaker instead.
		usort(
			$items,
			static function ( $a, $b ) {

				$by_order = $a['order'] <=> $b['order'];

				if ( $by_order !== 0 ) {
					return $by_order;
				}

				return strcmp( $a['widget']->get_id(), $b['widget']->get_id() );
			}
		);

		foreach ( $items as $item ) {
			$this->widgets[ $item['column'] ][] = [
				'widget'  => $item['widget'],
				'variant' => $item['variant'],
			];
		}
	}

	/**
	 * Get the widget instances to evaluate.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessContext $access Access context.
	 *
	 * @return array
	 */
	private function get_widgets( AccessContext $access ): array {

		$widgets = [
			new Widgets\Version( $access ),
			new Widgets\StatCards( $access ),
			new Widgets\MailerNotice( $access ),
			new Widgets\EmailsOverview( $access ),
			new Widgets\Connections( $access ),
			new Widgets\EmailLog( $access ),
			new Widgets\EmailSources( $access ),
			new Widgets\SetupChecklistOverview( $access ),
			new Widgets\GettingStarted( $access ),
			new Widgets\FeaturesUpsell( $access ),
			new Widgets\GrowthTools( $access ),
		];

		/**
		 * Filter the Dashboard widgets to render.
		 *
		 * @since 4.10.0
		 *
		 * @param array         $widgets Widget instances.
		 * @param AccessContext $access  Access context, for constructing a widget of one's own.
		 */
		return (array) apply_filters( 'wp_mail_smtp_admin_dashboard_widget_pipeline_get_widgets', $widgets, $access );
	}

	/**
	 * Render all visible widgets of a column into HTML.
	 *
	 * @since 4.10.0
	 *
	 * @param string $column Column: 'main' or 'sidebar'.
	 * @param array  $data   Aggregated data.
	 *
	 * @return string
	 */
	public function render_column( string $column, array $data ): string {

		$html = '';

		foreach ( $this->widgets[ $column ] ?? [] as $item ) {
			$html .= $item['widget']->render( $item['variant'], $data );
		}

		return $html;
	}
}
