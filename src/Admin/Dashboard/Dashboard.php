<?php

namespace WPMailSMTP\Admin\Dashboard;

use WPMailSMTP\Admin\Area;

/**
 * Dashboard orchestrator.
 *
 * @since 4.10.0
 */
class Dashboard {

	/**
	 * Admin page controller.
	 *
	 * @since 4.10.0
	 *
	 * @var Page
	 */
	private $page;

	/**
	 * AJAX endpoints.
	 *
	 * @since 4.10.0
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Build the object graph and register hooks. Area constructs this only under
	 * `is_admin()`, so it needs no admin guard of its own.
	 *
	 * @since 4.10.0
	 */
	public function hooks() {

		$access_resolver = new AccessResolver();
		$pipeline        = new WidgetPipeline();
		$stats           = $this->get_stats();

		$this->page = $this->get_page( $access_resolver, $pipeline, $stats );
		$this->ajax = $this->get_ajax( $access_resolver );

		$this->page->hooks();
		$this->ajax->hooks();
	}

	/**
	 * Get the Dashboard statistics instance. Overridable seam: Pro sources its series
	 * from the email log instead of the option counters.
	 *
	 * @since 4.10.0
	 *
	 * @return Stats
	 */
	protected function get_stats() {

		return new Stats();
	}

	/**
	 * Get the Dashboard page controller. Overridable seam: Pro adds the date-range control.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessResolver $access_resolver Access resolver.
	 * @param WidgetPipeline $pipeline        Widget pipeline.
	 * @param Stats          $stats           Dashboard statistics.
	 *
	 * @return Page
	 */
	protected function get_page( AccessResolver $access_resolver, WidgetPipeline $pipeline, Stats $stats ) {

		return new Page( $access_resolver, $pipeline, $stats );
	}

	/**
	 * Get the Dashboard AJAX endpoints. Overridable seam: Pro adds the date-range endpoint.
	 *
	 * @since 4.10.0
	 *
	 * @param AccessResolver $access_resolver Access resolver.
	 *
	 * @return Ajax
	 */
	protected function get_ajax( AccessResolver $access_resolver ) {

		return new Ajax( $access_resolver );
	}

	/**
	 * Whether the Dashboard page belongs to the admin being built: each site's own one.
	 *
	 * @since 4.10.5
	 *
	 * @return bool
	 */
	protected function is_submenu_item_allowed() {

		return ! is_network_admin();
	}

	/**
	 * Register the Dashboard submenu entry, ahead of the plugin's other pages. The Setup
	 * Checklist splices itself above this while it is active, and WordPress takes the
	 * top-level menu link from whichever submenu item ends up first.
	 *
	 * @since 4.10.0
	 *
	 * @param string $access_capability Capability required to see the page.
	 */
	public function add_submenu_item( $access_capability ) {

		if ( ! $this->is_submenu_item_allowed() ) {
			return;
		}

		global $submenu;

		add_submenu_page(
			Area::SLUG,
			esc_html__( 'Dashboard', 'wp-mail-smtp' ),
			esc_html__( 'Dashboard', 'wp-mail-smtp' ),
			$access_capability,
			Page::SLUG,
			[ $this->page, 'output' ]
		);

		// WordPress auto-inserts the parent page as the first submenu item; remove that
		// duplicate so the Dashboard is first and becomes the top-level target.
		if ( isset( $submenu[ Area::SLUG ][0][2] ) && $submenu[ Area::SLUG ][0][2] === Area::SLUG ) {
			unset( $submenu[ Area::SLUG ][0] );
		}
	}
}
