<?php

namespace WPMailSMTP;

/**
 * Class SiteHealth adds the plugin status and information to the WP Site Health admin page.
 *
 * @since {VERSION}
 */
class SiteHealth {

	/**
	 * String of a badge color.
	 * Options: blue, green, red, orange, purple and gray.
	 *
	 * @see https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
	 *
	 * @since {VERSION}
	 */
	const BADGE_COLOR = 'blue';

	/**
	 * Debug info plugin slug.
	 * This should be a plugin unique string, which will be used in the WP Site Health page,
	 * for the "info" tab and will present the plugin info section.
	 *
	 * @since {VERSION}
	 */
	const DEBUG_INFO_SLUG = 'wp_mail_smtp';

	/**
	 * Translatable string for the plugin label.
	 *
	 * @since {VERSION}
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' );
	}

	/**
	 * Initialize the site heath functionality.
	 *
	 * @since {VERSION}
	 */
	public function init() {

		add_filter( 'site_status_tests', array( $this, 'register_site_status_tests' ) );
		add_filter( 'debug_information', array( $this, 'register_debug_information' ) );
	}

	/**
	 * Register plugin WP site health tests.
	 * This will be displayed in the "Status" tab of the WP Site Health page.
	 *
	 * @since {VERSION}
	 *
	 * @param array $tests The array with all WP site health tests.
	 *
	 * @return array
	 */
	public function register_site_status_tests( $tests ) {

		$tests['direct']['wp_mail_smtp_mailer_setup_complete'] = array(
			'label' => esc_html__( 'Is WP Mail SMTP mailer setup complete?', 'wp-mail-smtp' ),
			'test'  => array( $this, 'mailer_setup_complete_test' ),
		);

		return $tests;
	}

	/**
	 * Register plugin WP Site Health debug information.
	 * This will be displayed in the "Info" tab of the WP Site Health page.
	 *
	 * @since {VERSION}
	 *
	 * @param array $debug_info Array of existing debug information.
	 *
	 * @return array
	 */
	public function register_debug_information( $debug_info ) {

		$debug_notices = Debug::get();

		$debug_info[ self::DEBUG_INFO_SLUG ] = array(
			'label'  => $this->get_label(),
			'fields' => array(
				'version'          => array(
					'label' => esc_html__( 'Version', 'wp-mail-smtp' ),
					'value' => WPMS_PLUGIN_VER,
				),
				'license_key_type' => array(
					'label' => esc_html__( 'License key type', 'wp-mail-smtp' ),
					'value' => wp_mail_smtp()->get_license_type(),
				),
				'debug'            => array(
					'label' => esc_html__( 'Debug', 'wp-mail-smtp' ),
					'value' => ! empty( $debug_notices ) ? implode( '. ', $debug_notices ) : esc_html__( 'No debug notices found.', 'wp-mail-smtp' ),
				),
			),
		);

		return $debug_info;
	}

	/**
	 * Perform the WP site health test for checking, if the mailer setup is complete.
	 *
	 * @since {VERSION}
	 */
	public function mailer_setup_complete_test() {

		$mailer          = Options::init()->get( 'mail', 'mailer' );
		$mailer_complete = wp_mail_smtp()
			->get_providers()
			->get_mailer(
				$mailer,
				wp_mail_smtp()->get_processor()->get_phpmailer()
			)->is_mailer_complete();

		// The default mailer should be considered as a non-complete mailer.
		if ( $mailer === 'mail' ) {
			$mailer_complete = false;
		}

		$mailer_text = sprintf(
			'%s: <strong>%s</strong>',
			esc_html__( 'Current mailer', 'wp-mail-smtp' ),
			esc_html( wp_mail_smtp()->get_providers()->get_options( $mailer )->get_title() )
		);

		$result = array(
			'label'       => esc_html__( 'WP Mail SMTP mailer setup is complete', 'wp-mail-smtp' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => $this->get_label(),
				'color' => self::BADGE_COLOR,
			),
			'description' => sprintf(
				'<p>%s</p><p>%s</p>',
				$mailer_text,
				esc_html__( 'The WP Mail SMTP plugin mailer setup is complete. You can send a test email, to make sure it\'s working properly.', 'wp-mail-smtp' )
			),
			'actions'     => sprintf(
				'<p><a href="%s">%s</a></p>',
				esc_url( add_query_arg( 'tab', 'test', wp_mail_smtp()->get_admin()->get_admin_page_url() ) ),
				esc_html__( 'Test email sending', 'wp-mail-smtp' )
			),
			'test'        => 'wp_mail_smtp_mailer_setup_complete',
		);

		if ( $mailer === 'mail' ) {
			$mailer_text .= sprintf( /* translators: %s - explanation why default mailer is not a valid mailer option. */
				'<p>%s</p>',
				esc_html__( 'You currently have the default mailer selected, which means that you haven’t set up SMTP yet.', 'wp-mail-smtp' )
			);
		}

		if ( $mailer_complete === false ) {
			$result['label']          = esc_html__( 'WP Mail SMTP mailer setup is incomplete', 'wp-mail-smtp' );
			$result['status']         = 'recommended';
			$result['badge']['color'] = 'orange';
			$result['description']    = sprintf(
				'<p>%s</p><p>%s</p>',
				$mailer_text,
				esc_html__( 'The WP Mail SMTP plugin mailer setup is incomplete. Please click on the link below to access plugin settings and configure the mailer.', 'wp-mail-smtp' )
			);
			$result['actions']        = sprintf(
				'<p><a href="%s">%s</a></p>',
				esc_url( wp_mail_smtp()->get_admin()->get_admin_page_url() ),
				esc_html__( 'Configure mailer', 'wp-mail-smtp' )
			);
		}

		return $result;
	}
}
