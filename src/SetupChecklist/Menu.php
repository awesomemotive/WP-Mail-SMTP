<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\Dashboard\Page as DashboardPage;
use WPMailSMTP\WP;

/**
 * Setup Checklist admin menu item.
 *
 * The progress bar's CSS prints inline on `admin_head` because the menu renders on
 * every admin screen, while the main admin stylesheet loads only on plugin pages.
 *
 * @since 4.10.0
 */
class Menu {

	/**
	 * CSS class added to the menu link so the inline styles target only this item.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ITEM_CLASS = 'wpms-setup-checklist-menu-item';

	/**
	 * CSS class marking the hidden submenu entry that supplies the top-level menu link.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const PARENT_LINK_CLASS = 'wpms-menu-parent-link';

	/**
	 * Parent menu slug the item is attached to.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const PARENT_SLUG = Area::SLUG;

	/**
	 * Checklist model (source of the progress percentage).
	 *
	 * @since 4.10.0
	 *
	 * @var Checklist
	 */
	private $checklist;

	/**
	 * Per-site state store (dismissal).
	 *
	 * @since 4.10.0
	 *
	 * @var State
	 */
	private $state;

	/**
	 * Admin page controller (the checklist page route).
	 *
	 * @since 4.10.0
	 *
	 * @var Page
	 */
	private $page;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Checklist $checklist Checklist model.
	 * @param State     $state     Per-site state store.
	 * @param Page      $page      Admin page controller.
	 */
	public function __construct( Checklist $checklist, State $state, Page $page ) {

		$this->checklist = $checklist;
		$this->state     = $state;
		$this->page      = $page;
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks(): void {

		$menu_hook = $this->get_menu_hook();

		add_action( $menu_hook, [ $this, 'register' ], 11 );
		add_action( $menu_hook, [ $this, 'reorder' ], PHP_INT_MAX );
		add_action( 'admin_page_access_denied', [ $this, 'maybe_redirect_dismissed' ] );
		add_action( 'admin_head', [ $this, 'print_styles' ] );
	}

	/**
	 * Send a dismissed checklist's page back to the plugin's main page.
	 *
	 * `reorder()` removes the submenu entry on dismissal, so core can no longer resolve
	 * this page's parent and fires `admin_page_access_denied` before its own `wp_die()`.
	 *
	 * @since 4.10.0
	 */
	public function maybe_redirect_dismissed(): void {

		if ( ! wp_mail_smtp()->get_admin()->is_admin_page( 'setup-checklist' ) || ! $this->state->is_dismissed() ) {
			return;
		}

		wp_safe_redirect( wp_mail_smtp()->get_admin()->get_admin_page_url( self::PARENT_SLUG ) );

		exit;
	}

	/**
	 * The menu hook the item is registered on: the admin the settings it walks the
	 * user through live in.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_menu_hook(): string {

		return WP::use_global_plugin_settings() ? 'network_admin_menu' : 'admin_menu';
	}

	/**
	 * Register the submenu item and the page route.
	 *
	 * @since 4.10.0
	 */
	public function register(): void {

		$capability = wp_mail_smtp()->get_capability_manage_options();

		if ( ! current_user_can( $capability ) ) {
			return;
		}

		$title = $this->state->is_dismissed()
			? esc_html__( 'Setup Checklist', 'wp-mail-smtp' )
			: $this->get_menu_title();

		add_submenu_page(
			self::PARENT_SLUG,
			esc_html__( 'WP Mail SMTP Setup Checklist', 'wp-mail-smtp' ),
			$title,
			$capability,
			Page::SLUG,
			[ $this->page, 'output' ]
		);
	}

	/**
	 * Position the item, or hide its link when the checklist is dismissed.
	 *
	 * @since 4.10.0
	 */
	public function reorder(): void {

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			return;
		}

		if ( $this->state->is_dismissed() ) {
			remove_submenu_page( self::PARENT_SLUG, Page::SLUG );

			return;
		}

		$this->move_before_dashboard();
	}

	/**
	 * Output the inline menu styles on every admin screen the menu shows on.
	 *
	 * @since 4.10.0
	 */
	public function print_styles(): void {

		if ( $this->state->is_dismissed() || ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			return;
		}

		$item = '#adminmenu .wp-submenu a.' . self::ITEM_CLASS;

		$styles =
			'#adminmenu .wp-submenu a.' . self::PARENT_LINK_CLASS . '{display:none;}'
			. $item . '{box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;gap:8px;padding:12px 12px 13px;border-bottom:1px solid #3C434A;}'
			. $item . ' .wpms-setup-checklist-menu-label{width:100%;font-weight:500;font-size:13px;line-height:16px;}'
			. $item . ' .wpms-setup-checklist-menu-progress{position:relative;width:100%;height:4px;background:#1D2327;border-radius:2px;overflow:hidden;}'
			. $item . ' .wpms-setup-checklist-menu-progress-bar{display:block;height:4px;min-width:4px;background:#00BA37;border-radius:2px;}';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<style id="wpms-setup-checklist-menu-styles">%s</style>', $styles );
	}

	/**
	 * Build the menu label markup: the title plus the progress bar.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_menu_title(): string {

		$percent = (int) $this->checklist->get_progress()['percent'];
		$percent = max( 0, min( 100, $percent ) );

		return sprintf(
			'<span class="wpms-setup-checklist-menu-label">%1$s</span>'
			. '<span class="wpms-setup-checklist-menu-progress">'
			. '<span class="wpms-setup-checklist-menu-progress-bar" style="width:%2$d%%;"></span>'
			. '<span class="screen-reader-text">%3$s</span>'
			. '</span>',
			esc_html__( 'Setup Checklist', 'wp-mail-smtp' ),
			$percent,
			esc_html(
				sprintf(
					/* translators: %d: setup checklist completion percentage. */
					__( '%d%% complete', 'wp-mail-smtp' ),
					$percent
				)
			)
		);
	}

	/**
	 * Place the checklist item right after the Dashboard in the submenu.
	 *
	 * @since 4.10.0
	 */
	private function move_before_dashboard(): void {

		global $submenu;

		if ( empty( $submenu[ self::PARENT_SLUG ] ) ) {
			return;
		}

		$items = $submenu[ self::PARENT_SLUG ];
		$ours  = null;

		foreach ( $items as $key => $item ) {
			if ( isset( $item[2] ) && $item[2] === Page::SLUG ) {
				$ours = $item;

				unset( $items[ $key ] );

				break;
			}
		}

		if ( $ours === null ) {
			return;
		}

		// The 5th element becomes a class on the menu link.
		$ours[4] = empty( $ours[4] ) ? self::ITEM_CLASS : $ours[4] . ' ' . self::ITEM_CLASS;

		$items     = array_values( $items );
		$position  = 0;
		$dashboard = null;

		foreach ( $items as $key => $item ) {
			if ( isset( $item[2] ) && $item[2] === DashboardPage::SLUG ) {
				$position  = $key;
				$dashboard = $item;

				break;
			}
		}

		array_splice( $items, $position, 0, [ $ours ] );

		// WordPress builds the top-level menu's link from the first submenu item and
		// offers no filter for it, so the Dashboard is repeated there and hidden. That
		// keeps the link on the Dashboard while the checklist is listed above it.
		if ( $dashboard !== null ) {
			$dashboard[4] = empty( $dashboard[4] ) ? self::PARENT_LINK_CLASS : $dashboard[4] . ' ' . self::PARENT_LINK_CLASS;

			array_unshift( $items, $dashboard );
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ self::PARENT_SLUG ] = $items;
	}
}
