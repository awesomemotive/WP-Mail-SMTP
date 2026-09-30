<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\WidgetState;
use WPMailSMTP\Admin\SetupWizard\Launcher as SetupWizardLauncher;

/**
 * Prompts a user with no mailer connected to run the setup wizard.
 *
 * @since 4.10.0
 */
class MailerNotice extends AbstractWidget {

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'main';

	/**
	 * Between the stat cards and the Emails Overview widget.
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 15;

	/**
	 * Get the widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'mailer_notice';
	}

	/**
	 * Get the widget title. Empty: the card carries its own heading in the body,
	 * not as a widget heading.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return '';
	}

	/**
	 * Get the widget state.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( ! $this->is_mailer_configured(), 'data' );
	}

	/**
	 * Render the widget body.
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
			'dashboard/widgets/mailer-notice',
			[
				'wizard_url' => SetupWizardLauncher::get_url(),
			],
			true
		);
	}
}
