<?php

namespace WPMailSMTP\Admin\Dashboard;

/**
 * Dashboard access context value object.
 *
 * @since 4.10.0
 */
class AccessContext {

	/**
	 * User meta key holding per-card dismiss timestamps, keyed by card id.
	 *
	 * @since 4.10.0
	 */
	public const DISMISSED_META_KEY = 'wp_mail_smtp_dashboard_dismissed';

	/**
	 * Context data, merged over the Lite defaults in the constructor.
	 *
	 * @since 4.10.0
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Context data. Missing keys fall back to Lite defaults.
	 */
	public function __construct( array $data = [] ) {

		$this->data = array_merge(
			[
				'is_pro'          => false,
				'can_manage'      => false,
				'user_id'         => 0,
				'dismissals'      => [],
				'widget_settings' => [],
			],
			$data
		);
	}

	/**
	 * Whether the Pro version is active.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_pro(): bool {

		return (bool) $this->data['is_pro'];
	}

	/**
	 * Whether the current user can manage the Dashboard (AJAX capability gate).
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function can_manage(): bool {

		return (bool) $this->data['can_manage'];
	}

	/**
	 * Get the current user ID.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	public function get_user_id(): int {

		return (int) $this->data['user_id'];
	}

	/**
	 * Get the dismissed-card user meta (card id => dismiss timestamp), e.g. `email_alerts`
	 * or `smart_routing` from the Alerts widget.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_dismissals(): array {

		return (array) $this->data['dismissals'];
	}

	/**
	 * Get the per-widget settings user meta, keyed by widget ID.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_widget_settings(): array {

		return (array) $this->data['widget_settings'];
	}
}
