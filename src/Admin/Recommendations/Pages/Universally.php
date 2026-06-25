<?php

namespace WPMailSMTP\Admin\Recommendations\Pages;

use WPMailSMTP\Admin\Recommendations\PageAbstract;

/**
 * Universally recommended-plugin landing page.
 *
 * @since 4.9.0
 */
class Universally extends PageAbstract {

	/**
	 * Admin menu page slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	public const SLUG = 'wp-mail-smtp-recommended-universally';

	/**
	 * Configuration.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	protected $config = [
		'lite_plugin'            => 'universally-language-translation-multilingual-tool/universally.php',
		'lite_wporg_url'         => 'https://wordpress.org/plugins/universally-language-translation-multilingual-tool/',
		'lite_download_url'      => 'https://downloads.wordpress.org/plugin/universally-language-translation-multilingual-tool.zip',
		'pro_plugin'             => '',
		'universally_addon'      => '',
		'universally_addon_page' => 'https://universally.com/?utm_source=wpmailsmtpplugin&utm_medium=link&utm_campaign=translation-page',
		'universally_onboarding' => 'admin.php?page=universally_settings',
	];

	/**
	 * Get the plugin name for use in IDs, CSS classes, and config keys.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin name.
	 */
	protected static function get_plugin_name(): string {

		return 'universally'; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading title text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading title.
	 */
	protected function get_heading_title(): string {

		return esc_html__( 'Translate Your Website and Go Global in Minutes', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading alt text for logo.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading alt text.
	 */
	protected function get_heading_alt_text(): string {

		return esc_attr__( 'WP Mail SMTP ♥ Universally', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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
			esc_html__( 'Universally uses AI to translate your entire WordPress site into 110+ languages in minutes, understanding context and keeping your brand voice intact. No coding required.', 'wp-mail-smtp' ),
			esc_html__( 'Built for global reach. Designed for everyone.', 'wp-mail-smtp' ),
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
			esc_html__( 'AI translation into 110+ languages.', 'wp-mail-smtp' ),
			esc_html__( 'Multilingual SEO with translated URLs and meta.', 'wp-mail-smtp' ),
			esc_html__( 'Auto-translates new content when you publish.', 'wp-mail-smtp' ),
			esc_html__( 'Exclude specific pages, classes, or IDs from translation.', 'wp-mail-smtp' ),
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

		return esc_attr__( 'Universally screenshot', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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
			esc_html__( 'Reach Even More of the World', 'wp-mail-smtp' ),
			esc_html__( 'Upgrade your plan for more languages and higher translation limits.', 'wp-mail-smtp' ),
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
		$step['button_class']  = $this->output_data['plugin_setup'] ? 'button-primary' : 'grey disabled';
		$step['button_url']    = $this->config['universally_addon_page'];

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

		return $this->is_plugin_configured(); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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

		return ! empty( get_option( 'universally_api_key' ) );
	}

	/**
	 * Whether a plugin is active or not.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if plugin is active.
	 */
	protected function is_plugin_activated(): bool {

		return is_plugin_active( $this->config['lite_plugin'] ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether a plugin is available (class/function exists).
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if plugin is available.
	 */
	protected function is_plugin_available(): bool {

		return class_exists( 'Universally\AdminBar', false ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether pro version is active.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if pro version is active.
	 */
	protected function is_pro_active(): bool {

		return false; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step heading.
	 */
	protected function get_install_heading(): string {

		return esc_html__( 'Install & Activate Universally', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step description.
	 */
	protected function get_install_description(): string {

		return esc_html__( 'Install Universally from the WordPress.org plugin repository.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the plugin title.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin title.
	 */
	protected function get_plugin_title(): string {

		return esc_html__( 'Universally', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the install button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install button text.
	 */
	protected function get_install_button_text(): string {

		return esc_html__( 'Install Universally', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a plugin is installed and activated.
	 *
	 * @since 4.9.0
	 *
	 * @return string Installed & activated text.
	 */
	protected function get_installed_activated_text(): string {

		return esc_html__( 'Universally Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the activate button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Activate button text.
	 */
	protected function get_activate_text(): string {

		return esc_html__( 'Activate Universally', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step heading.
	 */
	protected function get_setup_heading(): string {

		return esc_html__( 'Connect Universally', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step description.
	 */
	protected function get_setup_description(): string {

		return esc_html__( 'Add your API key on the settings page and choose the languages for your site.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the setup button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup button text.
	 */
	protected function get_setup_button_text(): string {

		return esc_html__( 'Open Settings', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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

		return esc_html__( 'Universally Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}
}
