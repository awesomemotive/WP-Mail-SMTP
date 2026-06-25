<?php

namespace WPMailSMTP\Admin;

use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Options;

/**
 * WP Mail SMTP admin bar menu.
 *
 * @since 2.3.0
 */
class AdminBarMenu {

	/**
	 * Initialize class.
	 *
	 * @since 2.3.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.3.0
	 */
	public function hooks() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueues' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );
		add_action( 'admin_bar_menu', [ $this, 'register' ], 999 );
	}

	/**
	 * Check if current user has access to see admin bar menu.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	public function has_access() {

		$access = false;

		if (
			is_user_logged_in() &&
			current_user_can( wp_mail_smtp()->get_capability_manage_options() )
		) {
			$access = true;
		}

		return apply_filters( 'wp_mail_smtp_admin_adminbarmenu_has_access', $access );
	}

	/**
	 * Check if new notifications are available.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	public function has_notifications() {

		return wp_mail_smtp()->get_notifications()->get_count();
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 2.3.0
	 */
	public function enqueues() {

		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! $this->has_access() ) {
			return;
		}

		wp_enqueue_style(
			'wp-mail-smtp-admin-bar',
			wp_mail_smtp()->assets_url . '/css/admin-bar.min.css',
			[],
			WPMS_PLUGIN_VER
		);
	}

	/**
	 * Register and render admin menu bar.
	 *
	 * @since 2.3.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function register( \WP_Admin_Bar $wp_admin_bar ) {

		if (
			! $this->has_access() ||
			(
				(
					! $this->has_email_sending_errors() ||
					(bool) Options::init()->get( 'general', 'email_delivery_errors_hidden' )
				) &&
				empty( $this->has_notifications() )
			)
		) {
			return;
		}

		$items = apply_filters(
			'wp_mail_smtp_admin_adminbarmenu_register',
			[
				'main_menu',
			],
			$wp_admin_bar
		);

		foreach ( $items as $item ) {
			$this->{ $item }( $wp_admin_bar );

			do_action( "wp_mail_smtp_admin_adminbarmenu_register_{$item}_after", $wp_admin_bar );
		}
	}

	/**
	 * Render primary top-level admin menu bar item.
	 *
	 * @since 2.3.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function main_menu( \WP_Admin_Bar $wp_admin_bar ) {

		if (
			$this->has_email_sending_errors() &&
			! (bool) Options::init()->get( 'general', 'email_delivery_errors_hidden' )
		) {
			$indicator = ' <span class="wp-mail-smtp-admin-bar-menu-error">!</span>';
		} elseif ( ! empty( $this->has_notifications() ) ) {
			$count     = $this->has_notifications() < 10 ? $this->has_notifications() : '!';
			$indicator = ' <div class="wp-mail-smtp-admin-bar-menu-notification-counter"><span>' . $count . '</span></div>';
		}

		if ( ! isset( $indicator ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			[
				'id'    => 'wp-mail-smtp-menu',
				'title' => 'WP Mail SMTP' . $indicator,
				'href'  => apply_filters(
					'wp_mail_smtp_admin_adminbarmenu_main_menu_href',
					wp_mail_smtp()->get_admin()->get_admin_page_url()
				),
			]
		);
	}

	/**
	 * Whether any active connection has a live (failed, regular-context) email
	 * sending error recorded.
	 *
	 * Matches the filter used by EmailSendingErrors so the admin bar indicator
	 * fires for the same set of failures shown by the inline error banner.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function has_email_sending_errors() {

		$all = EmailSendingDebug::get();

		if ( empty( $all ) ) {
			return false;
		}

		$live = array_filter(
			$all,
			static function ( $record ) {

				return isset( $record['status'], $record['context'] )
							 && $record['status'] === 'failed'
							 && $record['context'] === 'regular';
			}
		);

		return ! empty( $live );
	}
}
