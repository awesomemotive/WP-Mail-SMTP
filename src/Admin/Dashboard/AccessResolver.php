<?php

namespace WPMailSMTP\Admin\Dashboard;

use WPMailSMTP\Admin\Dashboard\Widgets\AbstractWidget;

/**
 * Builds the immutable access context for the Dashboard page.
 *
 * @since 4.10.0
 */
class AccessResolver {

	/**
	 * Cached context.
	 *
	 * @since 4.10.0
	 *
	 * @var AccessContext|null
	 */
	private $context;

	/**
	 * Get the access context, building it on first call.
	 *
	 * @since 4.10.0
	 *
	 * @return AccessContext
	 */
	public function get_context(): AccessContext {

		if ( $this->context === null ) {
			$this->context = new AccessContext( $this->get_data() );
		}

		return $this->context;
	}

	/**
	 * Get the raw context data.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_data(): array {

		return [
			'is_pro'          => wp_mail_smtp()->is_pro(),
			// Mirrors the capability the Dashboard submenu is registered with, so a site
			// filtering it does not reach a page whose AJAX actions all reject it.
			'can_manage'      => current_user_can( wp_mail_smtp()->get_capability_manage_options() ),
			'user_id'         => get_current_user_id(),
			'dismissals'      => Helpers::get_user_meta_array( AccessContext::DISMISSED_META_KEY ),
			'widget_settings' => Helpers::get_user_meta_array( AbstractWidget::SETTINGS_META_KEY ),
		];
	}
}
