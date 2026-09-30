<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\SetupWizard\Launcher;
use WPMailSMTP\PartnerPlugins\Catalog;
use WPMailSMTP\SettingsImport\SettingsImport;

/**
 * Setup Checklist configuration and data model.
 *
 * @since 4.10.0
 */
class Config {

	/**
	 * The settings-import domain: which plugins have importable settings.
	 *
	 * @since 4.10.0
	 *
	 * @var SettingsImport
	 */
	private $settings_import;

	/**
	 * Promo helper, source of each recommended plugin's install state.
	 *
	 * @since 4.10.0
	 *
	 * @var Promos
	 */
	private $promos;

	/**
	 * Partner plugin catalog.
	 *
	 * @since 4.10.0
	 *
	 * @var Catalog
	 */
	private $catalog;

	/**
	 * Completion detector, source of the state an item's CTA depends on.
	 *
	 * @since 4.10.0
	 *
	 * @var CompletionDetector
	 */
	private $detector;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param SettingsImport     $settings_import Settings-import domain.
	 * @param Promos             $promos          Promo helper.
	 * @param Catalog            $catalog         Partner plugin catalog.
	 * @param CompletionDetector $detector        Completion detector.
	 */
	public function __construct( SettingsImport $settings_import, Promos $promos, Catalog $catalog, CompletionDetector $detector ) {

		$this->settings_import = $settings_import;
		$this->promos          = $promos;
		$this->catalog         = $catalog;
		$this->detector        = $detector;
	}

	/**
	 * "Connect Your Mailer" section ID.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const SECTION_CONNECT_MAILER = 'connect_mailer';

	/**
	 * "Improve Your Email Sending Experience" section ID.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const SECTION_IMPROVE_SENDING = 'improve_sending';

	/**
	 * Ordered checklist sections, each with its ordered items.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = [
			self::SECTION_CONNECT_MAILER  => [
				'id'    => self::SECTION_CONNECT_MAILER,
				'title' => __( 'Connect Your Mailer', 'wp-mail-smtp' ),
				'order' => 10,
				'items' => $this->get_connect_mailer_items(),
			],
			self::SECTION_IMPROVE_SENDING => [
				'id'    => self::SECTION_IMPROVE_SENDING,
				'title' => __( 'Improve Your Email Sending Experience', 'wp-mail-smtp' ),
				'order' => 30,
				'items' => $this->get_improve_sending_items(),
			],
		];

		/**
		 * Filter the checklist sections before they are ordered.
		 *
		 * @since 4.10.0
		 *
		 * @param array $sections Sections keyed by section ID.
		 */
		$sections = (array) apply_filters( 'wp_mail_smtp_setup_checklist_config_get_sections', $sections );

		$sections = $this->sort_by_order( $sections );

		foreach ( $sections as $id => $section ) {
			if ( ! empty( $section['items'] ) && is_array( $section['items'] ) ) {
				$sections[ $id ]['items'] = $this->sort_by_order( $section['items'] );
			}
		}

		return $sections;
	}

	/**
	 * Items for the "Connect Your Mailer" section.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_connect_mailer_items() {

		return [
			'setup_mailer'    => [
				'id'          => 'setup_mailer',
				'section'     => self::SECTION_CONNECT_MAILER,
				'title'       => __( 'Setup Your Mailer', 'wp-mail-smtp' ),
				'description' => __( 'Connect WordPress to mailers like SendLayer, Gmail, Outlook, Amazon SES, and more to start sending emails.', 'wp-mail-smtp' ),
				'order'       => 10,
				'cta'         => [
					'label'      => __( 'Launch Setup Wizard', 'wp-mail-smtp' ),
					'url'        => Launcher::get_url(),
					'icon_after' => 'wpms:icon-[fa6-solid--arrow-right] wpms:w-[12px] wpms:h-[12px]',
				],
			],
			'import_settings' => [
				'id'          => 'import_settings',
				'section'     => self::SECTION_CONNECT_MAILER,
				'title'       => $this->get_import_title(),
				'description' => __( 'We have detected other SMTP plugins installed on your website. Select which plugin\'s data to import', 'wp-mail-smtp' ),
				'order'       => 20,
				'conditional' => true,
				'form'        => 'import',
				'cta'         => [
					'label'      => __( 'Import Settings', 'wp-mail-smtp' ),
					'modifier'   => 'secondary',
					'action'     => 'import-settings',
					'icon_after' => 'wpms:icon-[fa6-solid--arrow-right] wpms:w-[12px] wpms:h-[12px]',
				],
			],
			'test_email'      => [
				'id'          => 'test_email',
				'section'     => self::SECTION_CONNECT_MAILER,
				'title'       => __( 'Successfully Send a Test Email', 'wp-mail-smtp' ),
				'description' => __( 'Confirm your site can send emails by delivering a test message to your inbox.', 'wp-mail-smtp' ),
				'order'       => 30,
				'cta'         => $this->get_test_email_cta(),
			],
		];
	}

	/**
	 * CTA for the test-email item, inert until a mailer is set up.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_test_email_cta() {

		$cta = [
			'label'    => __( 'Test Email', 'wp-mail-smtp' ),
			'modifier' => 'grey',
		];

		if ( ! $this->detector->is_complete( 'setup_mailer' ) ) {
			$cta['disabled'] = true;
			$cta['tooltip']  = __( 'Set up your mailer first. Until then there is no connection to send a test email through.', 'wp-mail-smtp' );

			return $cta;
		}

		$cta['url'] = add_query_arg( 'tab', 'test', wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' ) );

		return $cta;
	}

	/**
	 * Items for the "Improve Your Email Sending Experience" section.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_improve_sending_items() {

		return [
			'domain_setup'          => [
				'id'          => 'domain_setup',
				'section'     => self::SECTION_IMPROVE_SENDING,
				'title'       => __( 'Fix Your Domain Setup', 'wp-mail-smtp' ),
				'description' => __( 'We found issues with your domain\'s email authentication. Fix SPF, DKIM, and DMARC to keep your emails out of spam.', 'wp-mail-smtp' ),
				'order'       => 10,
				'conditional' => true,
				'cta'         => [
					'label'    => __( 'View Domain Report', 'wp-mail-smtp' ),
					'url'      => add_query_arg(
						[
							'tab'        => 'test',
							'auto-start' => 1,
						],
						wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' )
					),
					'modifier' => 'grey',
				],
			],
			'wpvibe'                => [
				'id'          => 'wpvibe',
				'section'     => self::SECTION_IMPROVE_SENDING,
				'title'       => __( 'Connect Your AI Assistant', 'wp-mail-smtp' ),
				'description' => __( 'Review email logs and debug sending issues with Claude, ChatGPT, and more via the free WPVibe plugin.', 'wp-mail-smtp' ),
				'order'       => 20,
				'cta'         => $this->plugin_item_cta(
					'wpvibe',
					[
						'install'  => __( 'Install WPVibe', 'wp-mail-smtp' ),
						'activate' => __( 'Activate WPVibe', 'wp-mail-smtp' ),
						'setup'    => __( 'Set Up WPVibe', 'wp-mail-smtp' ),
					]
				),
			],
			'code_snippets'         => [
				'id'          => 'code_snippets',
				'section'     => self::SECTION_IMPROVE_SENDING,
				'title'       => __( 'Set Up Code Snippets', 'wp-mail-smtp' ),
				'description' => __( 'Customize how WP Mail SMTP works with one-click, pre-built snippets powered by WPCode.', 'wp-mail-smtp' ),
				'order'       => 30,
				'cta'         => [
					'label'    => __( 'Browse Snippets', 'wp-mail-smtp' ),
					'url'      => add_query_arg( 'tab', 'code-snippets', wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' ) ),
					'modifier' => 'grey',
				],
			],
			'wpconsent'             => [
				'id'          => 'wpconsent',
				'section'     => self::SECTION_IMPROVE_SENDING,
				'title'       => __( 'Set Up Privacy Compliance', 'wp-mail-smtp' ),
				'description' => __( 'Improve GDPR, CCPA, and privacy compliance with cookie banner and consent records.', 'wp-mail-smtp' ),
				'order'       => 40,
				'cta'         => $this->plugin_item_cta(
					'wpconsent',
					[
						'install'  => __( 'Install WPConsent', 'wp-mail-smtp' ),
						'activate' => __( 'Activate WPConsent', 'wp-mail-smtp' ),
						'setup'    => __( 'Set Up WPConsent', 'wp-mail-smtp' ),
					]
				),
			],
			'smart_recommendations' => [
				'id'          => 'smart_recommendations',
				'section'     => self::SECTION_IMPROVE_SENDING,
				'title'       => __( 'Smart Recommendations from WP Mail SMTP', 'wp-mail-smtp' ),
				'description' => __( 'Get helpful suggestions from WP Mail SMTP on how to optimize your email deliverability and grow your business.', 'wp-mail-smtp' ),
				'order'       => 50,
				'form'        => 'email',
				'cta'         => [
					'label'    => __( 'Sign Up for Free', 'wp-mail-smtp' ),
					'modifier' => 'grey',
					'action'   => 'subscribe',
				],
			],
			'usage_tracking'        => [
				'id'          => 'usage_tracking',
				'section'     => self::SECTION_IMPROVE_SENDING,
				'title'       => __( 'Help Improve WP Mail SMTP', 'wp-mail-smtp' ),
				'description' => __( 'Collect non-sensitive information from your website, such as the PHP version and features used, to help us fix bugs faster, make smarter decisions, and build features that actually matter to you.', 'wp-mail-smtp' ),
				'order'       => 60,
				'conditional' => true,
				'learn_more'  => [
					'label' => __( 'Learn More', 'wp-mail-smtp' ),
					'url'   => 'https://wpmailsmtp.com/legal/privacy-policy/',
				],
				'cta'         => [
					'label'    => __( 'Count Me In!', 'wp-mail-smtp' ),
					'modifier' => 'grey',
					'action'   => 'usage-tracking-optin',
				],
			],
		];
	}

	/**
	 * CTA for a recommended-plugin item, resolved from the plugin's current
	 * state.
	 *
	 * @since 4.10.0
	 *
	 * @param string $slug   Catalog slug.
	 * @param array  $labels Labels keyed `install`, `activate` and `setup`.
	 *
	 * @return array
	 */
	private function plugin_item_cta( $slug, array $labels ) {

		$plugin = $this->catalog->get( $slug );

		if ( $plugin === null ) {
			return [];
		}

		$state = $this->promos->recommended_plugin_state( $plugin );

		if ( $state['state'] === 'setup' ) {
			return [
				'label'    => $labels['setup'],
				'url'      => $state['setup_url'],
				'modifier' => 'grey',
			];
		}

		if ( $state['state'] === 'done' ) {
			return [];
		}

		$is_activate = $state['state'] === 'activate';

		return [
			'label'    => $is_activate ? $labels['activate'] : $labels['install'],
			'modifier' => 'grey',
			'action'   => $is_activate ? 'activate-plugin' : 'install-plugin',
			'plugin'   => $plugin->get_basename(),
			'reload'   => true,
		];
	}

	/**
	 * Title for the import item, naming the plugin when only one has settings to offer.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_import_title() {

		$detected = $this->settings_import->get_detected_plugins();

		if ( count( $detected ) !== 1 ) {
			return __( 'Import Settings From Existing SMTP Plugins', 'wp-mail-smtp' );
		}

		return sprintf(
			/* translators: %s: the name of the detected SMTP plugin. */
			__( 'Import Settings From %s', 'wp-mail-smtp' ),
			reset( $detected )
		);
	}

	/**
	 * Sort a keyed list of sections or items by their `order` weight, ascending.
	 *
	 * @since 4.10.0
	 *
	 * @param array $entries Keyed list of entries that each carry an `order`.
	 *
	 * @return array
	 */
	private function sort_by_order( array $entries ) {

		uasort(
			$entries,
			static function ( $a, $b ) {

				return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
			}
		);

		return $entries;
	}
}
