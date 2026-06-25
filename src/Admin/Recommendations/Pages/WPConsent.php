<?php

namespace WPMailSMTP\Admin\Recommendations\Pages;

use WPMailSMTP\Admin\Recommendations\PageAbstract;

/**
 * WPConsent recommended-plugin landing page (Privacy Compliance).
 *
 * @since 4.9.0
 */
class WPConsent extends PageAbstract {

	/**
	 * Admin menu page slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	public const SLUG = 'wp-mail-smtp-recommended-wpconsent';

	/**
	 * Configuration.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	protected $config = [
		'lite_plugin'          => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
		'lite_wporg_url'       => 'https://wordpress.org/plugins/wpconsent-cookies-banner-privacy-suite/',
		'lite_download_url'    => 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip',
		'pro_plugin'           => 'wpconsent-premium/wpconsent-premium.php',
		'wpconsent_addon'      => 'wpconsent-premium/wpconsent-premium.php',
		'wpconsent_addon_page' => 'https://wpconsent.com/?utm_source=wpmailsmtpplugin&utm_medium=link&utm_campaign=privacy-compliance-page',
		'wpconsent_onboarding' => 'admin.php?page=wpconsent-onboarding',
	];

	/**
	 * Hooks.
	 *
	 * Removes WPConsent's onboarding redirect during AJAX to prevent it from
	 * hijacking the status-check JSON response.
	 *
	 * @since 4.9.0
	 */
	public function hooks(): void {

		if ( wp_doing_ajax() ) {
			remove_action( 'admin_init', 'wpconsent_maybe_redirect_onboarding', 9999 );
		}

		parent::hooks();
	}

	/**
	 * Get the plugin name for use in IDs, CSS classes, and config keys.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin name.
	 */
	protected static function get_plugin_name(): string {

		return 'wpconsent'; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading title text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading title.
	 */
	protected function get_heading_title(): string {

		return esc_html__( 'Make Your Website Privacy-Compliant in Minutes', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading alt text for logo.
	 *
	 * @since 4.9.0
	 *
	 * @return string Heading alt text.
	 */
	protected function get_heading_alt_text(): string {

		return esc_attr__( 'WP Mail SMTP ♥ WPConsent', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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
			esc_html__( 'Build trust with clear, compliant privacy practices. WPConsent adds clean, professional banners and handles the technical side for you.', 'wp-mail-smtp' ),
			esc_html__( 'Built for transparency. Designed for ease.', 'wp-mail-smtp' ),
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
			esc_html__( 'A professional banner that fits your site.', 'wp-mail-smtp' ),
			esc_html__( 'Tools like Google Analytics and Facebook Pixel paused until consent.', 'wp-mail-smtp' ),
			esc_html__( 'Peace of mind knowing you’re aligned with global laws.', 'wp-mail-smtp' ),
			esc_html__( 'Self-hosted. Your data remains on your site.', 'wp-mail-smtp' ),
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

		return esc_attr__( 'WPConsent screenshot', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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
			esc_html__( 'Get Advanced Cookie Consent Features', 'wp-mail-smtp' ),
			esc_html__( 'With WPConsent Pro you can access advanced features like geolocation, popup layout, records of consent, multilanguage support, and more.', 'wp-mail-smtp' ),
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
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	protected function get_data_step_result(): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$step = [];

		$step['icon']          = 'plugin-page/step-3.svg';
		$step['section_class'] = $this->output_data['plugin_setup'] ? '' : 'grey';
		$step['button_text']   = esc_html__( 'Learn More', 'wp-mail-smtp' );
		$step['button_class']  = 'grey disabled';
		$step['button_url']    = '';

		$plugin_license_level = $this->get_license_level();

		switch ( $plugin_license_level ) {
			case 'lite':
				$step['button_url']   = $this->config['wpconsent_addon_page'];
				$step['button_class'] = $this->output_data['plugin_setup'] ? 'button-primary' : 'grey disabled';
				break;

			case 'pro':
				$addon_installed      = array_key_exists( $this->config['wpconsent_addon'], $this->output_data['all_plugins'] );
				$step['button_text']  =
					$addon_installed
						? esc_html__( 'WPConsent Pro Installed & Activated', 'wp-mail-smtp' )
						: esc_html__( 'Install Now', 'wp-mail-smtp' );
				$step['button_class'] = $this->output_data['plugin_setup'] ? 'grey disabled' : 'button-primary';
				$step['icon']         = $addon_installed ? 'plugin-page/complete.svg' : 'plugin-page/step-3.svg';
				break;
		}

		return $step;
	}

	/**
	 * Retrieve the license level of the plugin.
	 *
	 * @since 4.9.0
	 *
	 * @return string The plugin license level ('lite' or 'pro').
	 */
	protected function get_license_level(): string {

		$plugin_license_level = 'lite';

		// Check if premium features are available.
		if ( function_exists( 'wpconsent' ) ) {
			$wpconsent = wpconsent();

			if ( isset( $wpconsent->license ) && method_exists( $wpconsent->license, 'is_active' ) ) {
				$plugin_license_level = $wpconsent->license->is_active() ? 'pro' : 'lite';
			}
		}

		return $plugin_license_level;
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

		return $this->get_license_level() === 'pro';
	}

	/**
	 * Whether a plugin is configured or not.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if plugin is configured properly.
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	protected function is_plugin_configured(): bool {

		if ( ! $this->is_plugin_activated() ) {
			return false;
		}

		// The plugin is considered configured if the consent banner is enabled.
		if ( function_exists( 'wpconsent' ) ) {
			$wpconsent = wpconsent();

			if ( isset( $wpconsent->settings ) ) {
				$enable_consent_banner = $wpconsent->settings->get_option( 'enable_consent_banner', 0 );

				return ! empty( $enable_consent_banner );
			}
		}

		return false;
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
			function_exists( 'wpconsent' ) &&
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

		return function_exists( 'wpconsent' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether pro version is active.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True if pro version is active.
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	protected function is_pro_active(): bool {

		if ( ! function_exists( 'wpconsent' ) ) {
			return false;
		}

		$wpconsent = wpconsent();

		return isset( $wpconsent->license ) && method_exists( $wpconsent->license, 'is_active' ) && $wpconsent->license->is_active();
	}

	/**
	 * Get the heading for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step heading.
	 */
	protected function get_install_heading(): string {

		return esc_html__( 'Install & Activate WPConsent', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the install step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install step description.
	 */
	protected function get_install_description(): string {

		return esc_html__( 'Install WPConsent from the WordPress.org plugin repository.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the plugin title.
	 *
	 * @since 4.9.0
	 *
	 * @return string Plugin title.
	 */
	protected function get_plugin_title(): string {

		return esc_html__( 'WPConsent', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the install button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Install button text.
	 */
	protected function get_install_button_text(): string {

		return esc_html__( 'Install WPConsent', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a plugin is installed and activated.
	 *
	 * @since 4.9.0
	 *
	 * @return string Installed & activated text.
	 */
	protected function get_installed_activated_text(): string {

		return esc_html__( 'WPConsent Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the activate button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Activate button text.
	 */
	protected function get_activate_text(): string {

		return esc_html__( 'Activate WPConsent', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step heading.
	 */
	protected function get_setup_heading(): string {

		return esc_html__( 'Set Up WPConsent', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the setup step.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup step description.
	 */
	protected function get_setup_description(): string {

		return esc_html__( 'WPConsent has an intuitive setup wizard to guide you through the cookie consent configuration process.', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the setup button text.
	 *
	 * @since 4.9.0
	 *
	 * @return string Setup button text.
	 */
	protected function get_setup_button_text(): string {

		return esc_html__( 'Run Setup Wizard', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
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

		return esc_html__( 'WPConsent Pro Installed & Activated', 'wp-mail-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}
}
