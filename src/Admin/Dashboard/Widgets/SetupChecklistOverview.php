<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\WidgetState;
use WPMailSMTP\SetupChecklist\Page as SetupChecklistPage;

/**
 * Setup Checklist Overview widget.
 *
 * @since 4.10.0
 */
class SetupChecklistOverview extends AbstractWidget {

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'sidebar';

	/**
	 * Default sort position within the column.
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 30;

	/**
	 * Maximum number of next steps shown per checklist section.
	 *
	 * @since 4.10.0
	 */
	private const ITEMS_PER_SECTION = 3;

	/**
	 * Widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'setup_checklist_overview';
	}

	/**
	 * Widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Setup Checklist Overview', 'wp-mail-smtp' );
	}

	/**
	 * Widget state for the given access context.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		if ( $this->is_checklist_dismissed() ) {
			return new WidgetState( false );
		}

		foreach ( $this->get_sections() as $section ) {
			if ( empty( $section['is_complete'] ) ) {
				return new WidgetState( true, 'data' );
			}
		}

		return new WidgetState( false );
	}

	/**
	 * Widget body.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	protected function render_body( string $variant, array $data ): string {

		return (string) wp_mail_smtp_render(
			'dashboard/widgets/setup-checklist-overview',
			[
				'sections'      => $this->get_sections(),
				'checklist_url' => SetupChecklistPage::get_url(),
			],
			true
		);
	}

	/**
	 * A step's own destination, empty when it has none to offer from here.
	 *
	 * @since 4.10.0
	 *
	 * @param array $item Checklist item.
	 *
	 * @return string
	 */
	private function get_item_url( array $item ) {

		// A finished step has nothing left to do, and one whose CTA runs a checklist-page
		// action instead of navigating has nowhere to send the user.
		if ( ! empty( $item['complete'] ) ) {
			return '';
		}

		return $item['cta']['url'] ?? '';
	}

	/**
	 * Checklist sections with their steps, each carrying its own completion state and
	 * truncated to the first few steps.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = [];

		foreach ( $this->get_checklist_sections() as $id => $section ) {
			$items = $section['items'] ?? [];

			if ( empty( $items ) ) {
				// A section can end up with no items once every item is filtered out
				// upstream as inapplicable; skip it rather than showing an empty group.
				continue;
			}

			foreach ( $items as $item_id => $item ) {
				$items[ $item_id ]['is_complete'] = ! empty( $item['complete'] );
				$items[ $item_id ]['url']         = $this->get_item_url( $item );
			}

			$sliced = array_slice( $items, 0, self::ITEMS_PER_SECTION, true );

			$section['is_complete']  = ! empty( $section['complete'] );
			$section['items']        = $sliced;
			$section['hidden_count'] = count( $items ) - count( $sliced );

			$sections[ $id ] = $section;
		}

		return $sections;
	}

	/**
	 * The live checklist sections, or empty when the Setup Checklist has not initialized
	 * its admin-only object graph (e.g. a non-admin context).
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_checklist_sections() {

		$checklist = wp_mail_smtp()->get_setup_checklist()->get_checklist();

		return $checklist !== null ? $checklist->get_sections() : [];
	}

	/**
	 * Whether the user has dismissed the Setup Checklist.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_checklist_dismissed() {

		return wp_mail_smtp()->get_setup_checklist()->get_state()->is_dismissed();
	}
}
