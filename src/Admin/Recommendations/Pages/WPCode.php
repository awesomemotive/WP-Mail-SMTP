<?php

namespace WPMailSMTP\Admin\Recommendations\Pages;

use WPMailSMTP\Admin\Recommendations\PageAbstract;

/**
 * WPCode recommended-plugin landing page (Code Snippets).
 *
 * @since 4.9.0
 */
class WPCode extends PageAbstract {

	/**
	 * Admin menu page slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	public const SLUG = 'wp-mail-smtp-recommended-wpcode';

	/**
	 * Configuration.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	protected $config = [
		'lite_plugin'       => 'insert-headers-and-footers/ihaf.php',
		'lite_wporg_url'    => 'https://wordpress.org/plugins/insert-headers-and-footers/',
		'lite_download_url' => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
		'pro_plugin'        => 'wpcode-premium/wpcode.php',
		'wpcode_addon'      => 'wpcode-premium/wpcode.php',
		'wpcode_addon_page' => 'https://wpcode.com/lite/?utm_source=wpmailsmtpplugin&utm_medium=link&utm_campaign=code-snippets-page',
		'wpcode_onboarding' => 'admin.php?page=wpcode',
	];

	/**
	 * Get the plugin name for use in IDs, CSS classes, and config keys.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin name.
	 */
	protected static function get_plugin_name(): string {

		return 'wpcode'; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading title text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading title.
	 */
	protected function get_heading_title(): string {

		return esc_html__( 'Add Custom Code to WordPress Without Breaking Your Site', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading alt text for logo.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading alt text.
	 */
	protected function get_heading_alt_text(): string {

		return esc_attr__( 'WP Mail SMTP ♥ WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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
			esc_html__( 'WPCode lets you safely add PHP, JavaScript, CSS, and HTML snippets with no theme edits and no risk of losing changes when your theme updates. Built-in error handling keeps your site from breaking.', 'wp-mail-smtp' ),
			esc_html__( 'Trusted by over 3 million websites.', 'wp-mail-smtp' ),
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
			esc_html__( 'Add snippets without editing theme files.', 'wp-mail-smtp' ),
			esc_html__( 'PHP, JavaScript, CSS, and HTML all supported.', 'wp-mail-smtp' ),
			esc_html__( '100+ ready-made snippets for common tasks.', 'wp-mail-smtp' ),
			esc_html__( 'Auto-insert and one-click toggle for every snippet.', 'wp-mail-smtp' ),
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

		return esc_attr__( 'WPCode screenshot', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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
			esc_html__( 'Get Advanced Code Snippet Features', 'wp-mail-smtp' ),
			esc_html__( 'WPCode Pro adds conditional logic, a cloud snippet library, revisions, and conversion pixels to your workflow.', 'wp-mail-smtp' ),
			esc_attr( $step['button_class'] ),
			esc_url( $step['button_url'] ),
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

		$step = [];

		$step['icon']          = 'plugin-page/step-3.svg';
		$step['section_class'] = $this->output_data['plugin_setup'] ? '' : 'grey';
		$step['button_text']   = esc_html__( 'Learn More', 'wp-mail-smtp' );
		$step['button_class']  = 'grey disabled';
		$step['button_url']    = '';

		if ( ! $this->is_pro_active() ) {
			$step['button_url']   = $this->config['wpcode_addon_page'];
			$step['button_class'] = $this->output_data['plugin_setup'] ? 'button-primary' : 'grey disabled';
		} else {
			$addon_installed      = array_key_exists( $this->config['wpcode_addon'], $this->output_data['all_plugins'] );
			$step['button_text']  = $addon_installed
				? esc_html__( 'WPCode Pro Installed & Activated', 'wp-mail-smtp' )
				: esc_html__( 'Install Now', 'wp-mail-smtp' );
			$step['button_class'] = $this->output_data['plugin_setup'] ? 'grey disabled' : 'button-primary';
			$step['icon']         = $addon_installed ? 'plugin-page/complete.svg' : 'plugin-page/step-3.svg';
		}

		return $step;
	}

	/**
	 * Whether the plugin is finished setup or not.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if the plugin is finished setup.
	 */
	protected function is_plugin_finished_setup(): bool {

		if ( ! $this->is_plugin_configured() ) {
			return false;
		}

		return $this->is_pro_active();
	}

	/**
	 * Whether a plugin is configured or not.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if plugin is configured properly.
	 */
	protected function is_plugin_configured(): bool {

		if ( ! $this->is_plugin_activated() ) {
			return false;
		}

		$activated = get_option( 'ihaf_activated' );

		return is_array( $activated ) && ! empty( $activated['wpcode'] );
	}

	/**
	 * Whether a plugin is active or not.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if plugin is active.
	 */
	protected function is_plugin_activated(): bool {

		return ( // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
			function_exists( 'WPCode' ) &&
			(
				is_plugin_active( $this->config['lite_plugin'] ) ||
				is_plugin_active( $this->config['pro_plugin'] )
			)
		);
	}

	/**
	 * Whether a plugin is available (class/function exists).
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if plugin is available.
	 */
	protected function is_plugin_available(): bool {

		return function_exists( 'WPCode' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether pro version is active.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if pro version is active.
	 */
	protected function is_pro_active(): bool {

		return class_exists( 'WPCode_License' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step heading.
	 */
	protected function get_install_heading(): string {

		return esc_html__( 'Install & Activate WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step description.
	 */
	protected function get_install_description(): string {

		return esc_html__( 'Install WPCode from the WordPress.org plugin repository.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the plugin title.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin title.
	 */
	protected function get_plugin_title(): string {

		return esc_html__( 'WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the install button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install button text.
	 */
	protected function get_install_button_text(): string {

		return esc_html__( 'Install WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a plugin is installed and activated.
	 *
	 * @since 4.9.0
	 *
	 * @return string Installed & activated text.
	 */
	protected function get_installed_activated_text(): string {

		return esc_html__( 'WPCode Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the activate button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Activate button text.
	 */
	protected function get_activate_text(): string {

		return esc_html__( 'Activate WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step heading.
	 */
	protected function get_setup_heading(): string {

		return esc_html__( 'Set Up WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step description.
	 */
	protected function get_setup_description(): string {

		return esc_html__( 'Open WPCode to create your first snippet and start customizing your site safely.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the setup button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup button text.
	 */
	protected function get_setup_button_text(): string {

		return esc_html__( 'Open WPCode', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when setup is completed.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup completed text.
	 */
	protected function get_setup_completed_text(): string {

		return esc_html__( 'Setup Complete', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a pro-version is installed and activated.
	 *
	 * @since 4.9.0
	 *
	 * @return string Pro installed and activated text.
	 */
	protected function get_pro_installed_activated_text(): string {

		return esc_html__( 'WPCode Pro Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}
}
