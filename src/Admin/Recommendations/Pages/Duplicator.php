<?php

namespace WPMailSMTP\Admin\Recommendations\Pages;

use WPMailSMTP\Admin\Recommendations\PageAbstract;
use WPMailSMTP\PartnerPlugins\PartnerPlugin;
use WPMailSMTP\PartnerPlugins\Plugins\Duplicator as DuplicatorPartner;

/**
 * Duplicator recommended-plugin landing page (Backup & Migration).
 *
 * @since 4.9.0
 */
class Duplicator extends PageAbstract {

	/**
	 * Admin menu page slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	public const SLUG = 'wp-mail-smtp-recommended-duplicator';

	/**
	 * Configuration.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	protected $config = [
		'duplicator_addon_page' => 'https://duplicator.com/?utm_source=wpmailsmtpplugin&utm_medium=link&utm_campaign=duplicator-page',
	];

	/**
	 * Get the plugin name for use in IDs, CSS classes, and config keys.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin name.
	 */
	protected static function get_plugin_name(): string {

		return 'duplicator'; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * The plugin this page promotes.
	 *
	 * @since 4.10.0
	 *
	 * @return PartnerPlugin
	 */
	protected function create_plugin(): PartnerPlugin {

		return new DuplicatorPartner();
	}

	/**
	 * Get heading title text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading title.
	 */
	protected function get_heading_title(): string {

		return esc_html__( 'WP Mail SMTP Delivers It. Duplicator Protects It.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading alt text for logo.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading alt text.
	 */
	protected function get_heading_alt_text(): string {

		return esc_attr__( 'WP Mail SMTP ♥ Duplicator', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading description strings.
	 *
	 * @since 4.9.0
	 *
	 * @return array Array of description strings.
	 */
	protected function get_heading_strings(): array {

		return [ // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
			esc_html__( 'Your email settings and sender reputation live in your database. One bad update, one crash, and everything is gone. Duplicator backs up your entire WordPress site automatically so you can restore with one click.', 'wp-mail-smtp' ),
			esc_html__( 'Trusted by over 1.5 million websites.', 'wp-mail-smtp' ),
		];
	}

	/**
	 * Get screenshot features list.
	 *
	 * @since 4.9.0
	 *
	 * @return array Array of feature strings.
	 */
	protected function get_screenshot_features(): array {

		return [ // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
			esc_html__( 'Back up your entire site automatically.', 'wp-mail-smtp' ),
			esc_html__( 'Restore your site with one click if anything goes wrong.', 'wp-mail-smtp' ),
			esc_html__( 'Store backups safely in Google Drive, Dropbox, or Amazon S3.', 'wp-mail-smtp' ),
			esc_html__( 'Schedule daily backups so you never have to think about it.', 'wp-mail-smtp' ),
		];
	}

	/**
	 * Get screenshot alt text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Alt text for screenshot image.
	 */
	protected function get_screenshot_alt_text(): string {

		return esc_attr__( 'Duplicator screenshot', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether the plugin has nothing left for the user to set up.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	protected function is_plugin_finished_setup(): bool {

		if ( ! $this->is_plugin_configured() ) {
			return false;
		}

		$plugin         = $this->get_plugin();
		$count          = $plugin->get_backup_count();
		$schedule_count = ( $count && $this->is_pro_active() ) ? $plugin->get_schedule_count() : 0;

		return $count && $schedule_count;
	}

	/**
	 * Generate and output step 'Result' section HTML.
	 *
	 * @since 4.9.0
	 *
	 * @noinspection HtmlUnknownTarget
	 */
	protected function output_section_step_result(): void {

		$step = $this->get_data_step_result();

		if ( empty( $step ) ) {
			return;
		}

		printf(
			'<section class="step step-result %1$s">
				<aside class="num">
					<img src="%2$s" alt="%3$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%4$s</h2>
					<p>%5$s</p>
					<button class="button %6$s" data-url="%7$s">%8$s</button>
				</div>
			</section>',
			esc_attr( $step['section_class'] ),
			esc_url( wp_mail_smtp()->assets_url . '/images/recommendations/' . $step['icon'] ),
			esc_attr__( 'Step 3', 'wp-mail-smtp' ),
			esc_html__( 'Set Up Scheduled Cloud Backups', 'wp-mail-smtp' ),
			esc_html__( 'Keep your data safe forever with automatic daily backups to Google Drive, Dropbox, or Amazon S3.', 'wp-mail-smtp' ),
			esc_attr( $step['button_class'] ),
			esc_url( admin_url( 'admin.php?page=duplicator-schedules' ) ),
			esc_html( $step['button_text'] )
		);
	}

	/**
	 * Step 'Result' data.
	 *
	 * @since 4.9.0
	 *
	 * @return array Step data.
	 */
	protected function get_data_step_result(): array {

		$plugin = $this->get_plugin();
		$count  = $plugin->get_backup_count();

		$data = [
			'section_class' => $count ? '' : 'grey',
			'button_class'  => ! $count ? 'grey disabled' : 'button-primary',
			'icon'          => 'plugin-page/step-3.svg',
			'button_text'   => esc_html__( 'Set Up Cloud Backups', 'wp-mail-smtp' ),
		];

		if ( $count && $this->is_pro_active() ) {
			$data['section_class'] = '';
			$data['button_class']  = 'button-primary';

			if ( $plugin->get_schedule_count() > 0 ) {
				$data['icon']         = 'plugin-page/complete.svg';
				$data['button_class'] = 'grey disabled';
				$data['button_text']  = esc_html__( 'Cloud Backups Set Up', 'wp-mail-smtp' );
			}
		}

		return $data;
	}

	/**
	 * Get the heading for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step heading.
	 */
	protected function get_install_heading(): string {

		return esc_html__( 'Install & Activate Duplicator', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step description.
	 */
	protected function get_install_description(): string {

		return esc_html__( 'Your first step toward bulletproof backups.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the plugin title.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin title.
	 */
	protected function get_plugin_title(): string {

		return esc_html__( 'Duplicator', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the install button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install button text.
	 */
	protected function get_install_button_text(): string {

		return esc_html__( 'Install Duplicator', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a plugin is installed and activated.
	 *
	 * @since 4.9.0
	 *
	 * @return string Installed & activated text.
	 */
	protected function get_installed_activated_text(): string {

		return esc_html__( 'Duplicator Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the activate button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Activate button text.
	 */
	protected function get_activate_text(): string {

		return esc_html__( 'Activate Duplicator', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step heading.
	 */
	protected function get_setup_heading(): string {

		return esc_html__( 'Create Your First Backup', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step description.
	 */
	protected function get_setup_description(): string {

		return esc_html__( 'Back up your site, in just one click.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the setup button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup button text.
	 */
	protected function get_setup_button_text(): string {

		return esc_html__( 'Create First Backup', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when setup is completed.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup completed text.
	 */
	protected function get_setup_completed_text(): string {

		return esc_html__( 'Backup Created', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a pro-version is installed and activated.
	 *
	 * @since 4.9.0
	 *
	 * @return string Pro installed and activated text.
	 */
	protected function get_pro_installed_activated_text(): string {

		return esc_html__( 'Duplicator Pro Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}
}
