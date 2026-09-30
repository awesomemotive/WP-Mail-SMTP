<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Integrations\EmailDetective\Client;
use WPMailSMTP\Integrations\EmailDetective\TestState;
use WPMailSMTP\TestEmail\TestEmail;
use WPMailSMTP\WP;
use WP_Error;

/**
 * The Email Detective tab: sends a deliverability test through the site's
 * primary connection and hands the user their report by email.
 *
 * @since 4.10.0
 */
class EmailDetectiveTab extends PageAbstract {

	/**
	 * Tab slug.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	protected $slug = 'email-detective';

	/**
	 * Tab priority.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	protected $priority = 60;

	/**
	 * Link label of a tab.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Email Detective', 'wp-mail-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Register tab hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks() {

		add_action( 'wp_mail_smtp_admin_area_enqueue_assets', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue the tab's styles, script and localized config.
	 *
	 * @since 4.10.0
	 */
	public function enqueue_assets() {

		$min = WP::asset_min();

		wp_enqueue_style(
			'wp-mail-smtp-email-detective',
			wp_mail_smtp()->assets_url . '/css/smtp-email-detective.min.css',
			[],
			WPMS_PLUGIN_VER
		);

		wp_enqueue_script(
			'wp-mail-smtp-email-detective',
			wp_mail_smtp()->assets_url . "/js/smtp-email-detective{$min}.js",
			[ 'jquery' ],
			WPMS_PLUGIN_VER,
			true
		);

		$state = TestState::get();

		wp_localize_script(
			'wp-mail-smtp-email-detective',
			'wp_mail_smtp_email_detective',
			[
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'wp-mail-smtp-admin' ),
				'started_at' => $state !== null ? (int) $state['started_at'] : 0,
				'email'      => $state !== null && isset( $state['email'] ) ? (string) $state['email'] : '',
				'strings'    => [
					/* translators: %s - a formatted time, e.g. "2:45 pm". */
					'come_back_at'      => __( 'You are sending requests too quickly. Please come back at %s and this page will pick up automatically.', 'wp-mail-smtp' ),
					'unavailable'       => __( 'Email Detective is temporarily unavailable. Please try again in a moment.', 'wp-mail-smtp' ),
					'generic_error'     => __( 'Something went wrong. Please try again.', 'wp-mail-smtp' ),
					'invalid_email'     => __( 'Enter a valid email address.', 'wp-mail-smtp' ),
					'test_not_received' => __( 'We have not received your test email yet. Please wait for it to arrive before unlocking the report.', 'wp-mail-smtp' ),
					'invalid_phase'     => __( 'This test is not ready for that step. Please refresh the page and try again.', 'wp-mail-smtp' ),
					'sending'           => __( 'Sending...', 'wp-mail-smtp' ),
					'email_sent'        => __( 'Email Sent', 'wp-mail-smtp' ),
					'lead_sent_body'    => self::lead_sent_template(),
					'unlocked_body'     => self::unlocked_template(),
				],
			]
		);
	}

	/**
	 * Register the tab's AJAX handlers.
	 *
	 * @since 4.10.0
	 */
	public function ajax() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks -- ajax() is the framework's AJAX-registration method.

		add_action( 'wp_ajax_wp_mail_smtp_email_detective_start', [ $this, 'ajax_start' ] );
		add_action( 'wp_ajax_wp_mail_smtp_email_detective_status', [ $this, 'ajax_status' ] );
		add_action( 'wp_ajax_wp_mail_smtp_email_detective_lead', [ $this, 'ajax_lead' ] );
		add_action( 'wp_ajax_wp_mail_smtp_email_detective_reset', [ $this, 'ajax_reset' ] );
	}

	/**
	 * Output the tab content.
	 *
	 * @since 4.10.0
	 */
	public function display() {

		$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		$mailer     = $connection ? $connection->get_mailer() : null;

		echo '<div class="wp-mail-smtp-email-detective" id="wp-mail-smtp-email-detective">';

		$this->display_hero();

		if ( ! $mailer || ! $mailer->is_mailer_complete() ) {
			$this->display_empty_state();
		} else {
			$state = TestState::get();
			$phase = $state !== null ? (string) $state['phase'] : 'ready';
			?>

			<div class="wpms-email-detective-panel">
				<?php // Every state below renders unconditionally; data-phase selects the visible one, in CSS and in the JS alike. ?>
				<div class="wpms-email-detective-body" id="wpms-email-detective-body" data-phase="<?php echo esc_attr( $phase ); ?>">

					<div class="wpms-email-detective-state" data-state="ready">
						<?php $this->display_ready_state(); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="loading">
						<?php $this->display_loading_state(); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="send_failed">
						<?php $this->display_send_failed_state( $state ); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="not_delivered">
						<?php $this->display_not_delivered_state( $state ); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="lead_sent">
						<?php $this->display_lead_sent_state( $state ); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="unlocked">
						<?php $this->display_unlocked_state( $state ); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="rate_limited">
						<?php $this->display_rate_limited_state(); ?>
					</div>

					<div class="wpms-email-detective-state" data-state="unavailable">
						<?php $this->display_unavailable_state(); ?>
					</div>

				</div>

				<?php $this->display_footer(); ?>
			</div>

			<?php
		}

		echo '</div>';
	}

	/**
	 * The hero above the card: brand lockup, title and intro copy.
	 *
	 * @since 4.10.0
	 */
	private function display_hero() {

		$landing_url = self::landing_page_url();
		?>
		<div class="wpms-email-detective-hero">
			<a class="wpms-email-detective-brand-lockup" href="<?php echo esc_url( $landing_url ); ?>" target="_blank" rel="noopener noreferrer">
				<img
					src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/email-detective/ed-logo.svg' ); ?>"
					alt="<?php esc_attr_e( 'Email Detective', 'wp-mail-smtp' ); ?>"
					width="295"
					height="43"
					class="wpms-email-detective-brand-lockup__logo wpms-email-detective-brand-lockup__logo--ed"
				>
				<span class="wpms-email-detective-brand-lockup__by"><?php esc_html_e( 'by', 'wp-mail-smtp' ); ?></span>
				<img
					src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/email-detective/sl-logo.svg' ); ?>"
					alt="<?php esc_attr_e( 'SendLayer', 'wp-mail-smtp' ); ?>"
					width="78"
					height="17"
					class="wpms-email-detective-brand-lockup__logo wpms-email-detective-brand-lockup__logo--sl"
				>
			</a>

			<h2 class="wpms-email-detective-hero__title">
				<?php esc_html_e( 'Find Out If Your Emails Actually Reach the Inbox', 'wp-mail-smtp' ); ?>
			</h2>

			<p class="wpms-email-detective-hero__desc">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s - "Email Detective" link to the landing page. */
						__( 'Your site sends a test email through your primary connection, and %s checks everything that decides where it lands: authentication, blacklists, and spam filters. You get a free report showing exactly what to fix before real emails get lost in spam or get rejected.', 'wp-mail-smtp' ),
						sprintf(
							'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
							esc_url( $landing_url ),
							esc_html__( 'Email Detective', 'wp-mail-smtp' )
						)
					),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * The Email Detective landing page URL.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private static function landing_page_url() {

		return wp_mail_smtp()->get_utm_url(
			'https://sendlayer.com/email-detective/',
			[
				'source'   => 'wp-mail-smtp',
				'medium'   => 'plugin',
				'campaign' => 'email-detective',
			]
		);
	}

	/**
	 * The empty state: no fully configured primary connection to send through.
	 *
	 * @since 4.10.0
	 */
	private function display_empty_state() {

		$settings_url = wp_mail_smtp()->get_admin()->get_admin_page_url();
		?>
		<div class="wpms-email-detective-panel">
			<div class="wpms-email-detective-empty">
				<div class="wpms-email-detective-notice wpms-email-detective-notice--warning">
					<p>
						<?php esc_html_e( 'Email Detective needs a fully configured primary connection before it can send a deliverability test. Finish setting up your primary connection first.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange">
					<?php esc_html_e( 'Finish Primary Connection Setup', 'wp-mail-smtp' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * The ready state: the entry form, the benefits band and the example report.
	 *
	 * @since 4.10.0
	 */
	private function display_ready_state() {

		$current_user = wp_get_current_user();
		$email        = $current_user ? $current_user->user_email : '';
		?>
		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'Send a Test Email', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body"><?php esc_html_e( 'We will send the report to your email address below.', 'wp-mail-smtp' ); ?></p>

		<?php // novalidate: the browser's own bubble would intercept submit before the JS validation runs. ?>
		<form id="wpms-email-detective-start-form" class="wpms-email-detective-form" novalidate>
			<div class="wpms-email-detective-form-row">
				<input
					type="email"
					class="wpms-email-detective-input"
					id="wpms-email-detective-email"
					name="email"
					value="<?php echo esc_attr( $email ); ?>"
					aria-label="<?php esc_attr_e( 'Send the report to', 'wp-mail-smtp' ); ?>"
					required
				>
				<button type="submit" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange" id="wpms-email-detective-start">
					<?php esc_html_e( 'Run a Deliverability Test', 'wp-mail-smtp' ); ?>
				</button>
			</div>
			<p class="wpms-email-detective-form-message" id="wpms-email-detective-start-message" role="alert" hidden></p>
			<p class="wpms-email-detective-consent-notice">
				<?php echo esc_html( self::consent_wording() ); ?>
			</p>
		</form>

		<?php
		$this->display_benefits();
		$this->display_example_report();
	}

	/**
	 * The ready state's benefits band.
	 *
	 * @since 4.10.0
	 */
	private function display_benefits() {

		// The icon utility is spelled out in full: Tailwind scans this file as text, so a
		// class assembled at runtime would never be generated.
		$benefits = [
			[
				'icon'  => 'wpms:icon-[fa6-solid--shield-halved]',
				'label' => __( 'SPF, DKIM, & DMARC checks', 'wp-mail-smtp' ),
			],
			[
				'icon'  => 'wpms:icon-[fa6-solid--ban]',
				'label' => __( 'Domain and IP checked on blocklists', 'wp-mail-smtp' ),
			],
			[
				'icon'  => 'wpms:icon-[fa6-solid--filter]',
				'label' => __( 'Your message scored by a real spam filter', 'wp-mail-smtp' ),
			],
			[
				'icon'  => 'wpms:icon-[fa6-solid--clipboard-list]',
				'label' => __( 'Free report with clear, prioritized fixes', 'wp-mail-smtp' ),
			],
		];
		?>
		<div class="wpms-email-detective-benefits">
			<?php foreach ( $benefits as $benefit ) : ?>
				<div class="wpms-email-detective-benefits__item">
					<span
						class="wpms-email-detective-benefits__icon <?php echo esc_attr( $benefit['icon'] ); ?>"
						aria-hidden="true"
					></span>
					<p class="wpms-email-detective-benefits__label"><?php echo esc_html( $benefit['label'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * The ready state's example report figure.
	 *
	 * @since 4.10.0
	 */
	private function display_example_report() {

		?>
		<figure class="wpms-email-detective-example">
			<div class="wpms-email-detective-example__frame">
				<span class="wpms-email-detective-example__badge"><?php esc_html_e( 'Example Report', 'wp-mail-smtp' ); ?></span>
				<img
					src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/email-detective/report-example.webp' ); ?>"
					alt="<?php esc_attr_e( 'Example Email Detective report', 'wp-mail-smtp' ); ?>"
					width="1272"
					height="890"
					loading="lazy"
					class="wpms-email-detective-example__image"
				>
			</div>
		</figure>
		<?php
	}

	/**
	 * The loading state, shown for both the `waiting` and `received` phases.
	 *
	 * @since 4.10.0
	 */
	private function display_loading_state() {

		?>
		<div class="wpms-email-detective-spinner" aria-hidden="true">
			<span class="wpms-email-detective-spinner__glyph wpms:icon-[fa6-solid--hourglass]"></span>
		</div>

		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'Email Detective Is on the Case...', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body">
			<?php esc_html_e( 'Your test email is on its way. We\'re watching how the receiving server treats it and putting your report together.', 'wp-mail-smtp' ); ?>
		</p>
		<p class="wpms-email-detective-state__note">
			<?php esc_html_e( 'This usually takes less than a minute.', 'wp-mail-smtp' ); ?>
		</p>
		<button type="button" class="wpms-email-detective-reset-link" id="wpms-email-detective-cancel">
			<?php esc_html_e( 'Cancel', 'wp-mail-smtp' ); ?>
		</button>
		<?php
	}

	/**
	 * The circular status-icon badge shown atop the terminal states.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Either 'success' or 'error'.
	 */
	private function display_icon_badge( $variant ) {

		$icon = $variant === 'success' ? 'wpms:icon-[fa6-solid--check]' : 'wpms:icon-[fa6-solid--exclamation]';
		?>
		<div class="wpms-email-detective-icon-badge wpms-email-detective-icon-badge--<?php echo esc_attr( $variant ); ?>" aria-hidden="true">
			<span class="wpms-email-detective-icon-badge__glyph <?php echo esc_attr( $icon ); ?>"></span>
		</div>
		<?php
	}

	/**
	 * The send-failed state: the captured local send error, plus a retry.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $state The in-flight test state, if any.
	 */
	private function display_send_failed_state( $state ) {

		$send_error = is_array( $state ) && isset( $state['send_error'] ) ? (string) $state['send_error'] : '';
		$docs_url   = wp_mail_smtp()->get_utm_url(
			'https://wpmailsmtp.com/docs/a-complete-guide-to-wp-mail-smtp-mailers/',
			[
				'medium'  => 'email-detective',
				'content' => 'Send failed - mailer docs',
			]
		);

		$this->display_icon_badge( 'error' );
		?>
		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'The Test Email Couldn\'t Be Sent', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body">
			<?php esc_html_e( 'Your primary connection returned an error before Email Detective could run the check. Here\'s what your site reported:', 'wp-mail-smtp' ); ?>
		</p>
		<div class="notice notice-error inline wpms-email-detective-notice">
			<p>
				<span class="wpms-email-detective-send-error" id="wpms-email-detective-send-error"><?php echo esc_html( $send_error ); ?></span>
				<a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Read the mailer setup guide', 'wp-mail-smtp' ); ?>
				</a>
			</p>
		</div>
		<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-light-grey" id="wpms-email-detective-retry-send">
			<?php esc_html_e( 'Retry Test Email', 'wp-mail-smtp' ); ?>
		</button>
		<?php
	}

	/**
	 * The not-delivered state: the send succeeded locally but never arrived.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $state The in-flight test state, if any.
	 */
	private function display_not_delivered_state( $state ) {

		$report_url = is_array( $state ) && isset( $state['report_url'] ) ? (string) $state['report_url'] : '';

		$this->display_icon_badge( 'error' );
		?>
		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'We Couldn\'t Confirm Delivery', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body">
			<?php esc_html_e( 'Your site sent the test email, but Email Detective never received it. That usually points to a deliverability problem worth fixing. The report page has the same verdict and what to try next.', 'wp-mail-smtp' ); ?>
		</p>
		<a href="<?php echo esc_url( $report_url ); ?>" id="wpms-email-detective-report-link-not-delivered" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-light-grey" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'View the Report Page', 'wp-mail-smtp' ); ?>
		</a>
		<?php
	}

	/**
	 * The lead-sent state: names the inbox the report was emailed to.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $state The in-flight test state, if any.
	 */
	private function display_lead_sent_state( $state ) {

		$email = is_array( $state ) && isset( $state['email'] ) ? (string) $state['email'] : '';

		$this->display_icon_badge( 'success' );
		?>
		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'Check Your Inbox!', 'wp-mail-smtp' ); ?></h3>
		<p id="wpms-email-detective-lead-sent-message" class="wpms-email-detective-state__body">
			<?php
			echo wp_kses(
				sprintf(
					self::lead_sent_template(),
					'<strong>' . esc_html( $email ) . '</strong>'
				),
				[
					'br'     => [],
					'strong' => [],
				]
			);
			?>
		</p>
		<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-secondary" id="wpms-email-detective-resend">
			<?php esc_html_e( 'Resend the Report Email', 'wp-mail-smtp' ); ?>
		</button>
		<div class="wpms-email-detective-resend-feedback" id="wpms-email-detective-resend-feedback" role="status" aria-live="polite" hidden></div>
		<p class="wpms-email-detective-state__note">
			<?php esc_html_e( 'Didn\'t get it? Check your spam or junk folder before resending.', 'wp-mail-smtp' ); ?>
		</p>
		<?php
	}

	/**
	 * The unlocked state: the report link, shown directly.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $state The in-flight test state, if any.
	 */
	private function display_unlocked_state( $state ) {

		$report_url = is_array( $state ) && isset( $state['report_url'] ) ? (string) $state['report_url'] : '';
		$email      = is_array( $state ) && isset( $state['email'] ) ? (string) $state['email'] : '';

		$this->display_icon_badge( 'success' );
		?>
		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'Your Report Is Ready!', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body">
			<?php
			echo wp_kses(
				sprintf(
					self::unlocked_template(),
					'<strong>' . esc_html( $email ) . '</strong>'
				),
				[
					'br'     => [],
					'strong' => [],
				]
			);
			?>
		</p>
		<a href="<?php echo esc_url( $report_url ); ?>" id="wpms-email-detective-report-link-unlocked" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'View Your Deliverability Report', 'wp-mail-smtp' ); ?>
		</a>
		<?php
	}

	/**
	 * The rate-limited state. Client-only phase; the JS fills in the message.
	 *
	 * @since 4.10.0
	 */
	private function display_rate_limited_state() {

		$this->display_icon_badge( 'error' );
		?>
		<h3 class="wpms-email-detective-state__title"><?php esc_html_e( 'Too Many Requests Right Now', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body" id="wpms-email-detective-rate-limited-message"></p>
		<?php
	}

	/**
	 * The unavailable state. Client-only phase; the JS fills in the message.
	 *
	 * @since 4.10.0
	 */
	private function display_unavailable_state() {

		$this->display_icon_badge( 'error' );
		?>
		<h3 class="wpms-email-detective-state__title" id="wpms-email-detective-unavailable-title"><?php esc_html_e( 'Email Detective Is Temporarily Unavailable', 'wp-mail-smtp' ); ?></h3>
		<p class="wpms-email-detective-state__body" id="wpms-email-detective-unavailable-message"></p>
		<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-light-grey" id="wpms-email-detective-retry">
			<?php esc_html_e( 'Try Again', 'wp-mail-smtp' ); ?>
		</button>
		<?php
	}

	/**
	 * The shared card footer.
	 *
	 * @since 4.10.0
	 */
	private function display_footer() {

		// Must stay a following sibling of the state body: the CSS reveals it on
		// the terminal phases through the sibling combinator.
		?>
		<div class="wpms-email-detective-footer">
			<p class="wpms-email-detective-footer__text">
				<?php esc_html_e( 'Do you want a new report?', 'wp-mail-smtp' ); ?>
				<button type="button" id="wpms-email-detective-reset" class="wpms-email-detective-reset-link">
					<span><?php esc_html_e( 'Start a New Report', 'wp-mail-smtp' ); ?></span>
					<span class="wpms-email-detective-reset-link__arrow wpms:icon-[fa6-solid--arrow-right]" aria-hidden="true"></span>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Start a deliverability test: mint, send, report the outcome, persist.
	 *
	 * @since 4.10.0
	 */
	public function ajax_start() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh -- sequential guards, each a distinct early return.

		$this->verify_request();

		if ( TestState::get() !== null ) {
			wp_send_json_error(
				[
					'code'    => 'test_in_flight',
					'message' => esc_html__( 'A test is already running. Please wait for it to finish.', 'wp-mail-smtp' ),
				]
			);
		}

		$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		$mailer     = $connection ? $connection->get_mailer() : null;

		if ( ! $mailer || ! $mailer->is_mailer_complete() ) {
			wp_send_json_error(
				[
					'code'    => 'mailer_not_configured',
					'title'   => esc_html__( 'Finish Your Connection Setup', 'wp-mail-smtp' ),
					'message' => esc_html__( 'Finish setting up your primary connection before running a test.', 'wp-mail-smtp' ),
				]
			);
		}

		$email = sanitize_email( wp_unslash( isset( $_POST['email'] ) ? $_POST['email'] : '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above via check_ajax_referer().

		// sanitize_email() cleans but does not reject, so is_email() is the gate.
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_email',
					'message' => esc_html__( 'Enter a valid email address.', 'wp-mail-smtp' ),
				]
			);
		}

		$client = new Client();
		$test   = $client->create_test();

		if ( is_wp_error( $test ) ) {
			wp_send_json_error( $this->error_payload( $test ) );
		}

		// Stored so ajax_lead reads the address back from state, never from a POST field.
		$test['email'] = $email;

		// The minted address is used verbatim: the receiving server rejects a
		// trimmed, cased or otherwise reshaped localpart with a 550 before DATA.
		$test_email = ( new TestEmail() )
			->with_connection( $connection )
			->with_context( TestEmail::CONTEXT_ADMIN_TEST )
			->as_html( true )
			->with_domain_check( false );

		$test_email->send( $test['email_address'] );

		$succeeded  = $test_email->get_result() === TestEmail::SUCCESS;
		$send_error = $succeeded ? '' : $this->last_send_error( $connection );

		$client->report_send_result( $test['test_id'], $succeeded, $send_error );

		if ( ! $succeeded ) {
			TestState::start( $test );
			TestState::update(
				[
					'phase'      => TestState::PHASE_SEND_FAILED,
					'send_error' => $send_error,
				]
			);

			wp_send_json_success(
				[
					'phase'      => TestState::PHASE_SEND_FAILED,
					'send_error' => $send_error,
				]
			);
		}

		TestState::start( $test );

		wp_send_json_success(
			[
				'phase'      => TestState::PHASE_WAITING,
				'report_url' => esc_url_raw( $test['report_url'] ),
			]
		);
	}

	/**
	 * Poll the current status of the in-flight test and persist any phase change.
	 *
	 * @since 4.10.0
	 */
	public function ajax_status() {

		$this->verify_request();

		$state = TestState::get();

		if ( $state === null ) {
			wp_send_json_error(
				[
					'code'    => 'no_test',
					'message' => esc_html__( 'There is no test running. Please start a new one.', 'wp-mail-smtp' ),
				]
			);
		}

		$status = ( new Client() )->get_status( $state['test_id'] );

		if ( is_wp_error( $status ) ) {
			wp_send_json_error( $this->error_payload( $status ) );
		}

		$phase = self::phase_for_status( (string) $status['status'], (int) $state['started_at'], (string) $state['phase'] );

		if ( $phase !== $state['phase'] ) {
			TestState::update( [ 'phase' => $phase ] );
		}

		wp_send_json_success(
			[
				'phase'      => $phase,
				'report_url' => esc_url_raw( $state['report_url'] ),
			]
		);
	}

	/**
	 * Map the server's status vocabulary onto tab phases.
	 *
	 * @since 4.10.0
	 *
	 * @param string $status        The server's status value.
	 * @param int    $started_at    The test's start timestamp (TestState's started_at).
	 * @param string $current_phase The stored phase, when there is one.
	 *
	 * @return string One of the TestState::PHASE_* constants.
	 */
	public static function phase_for_status( string $status, int $started_at, string $current_phase = '' ): string {

		// The server's vocabulary tops out at `complete`, so nothing it reports
		// can walk back a report that is already sent or unlocked.
		if ( in_array( $current_phase, [ TestState::PHASE_LEAD_SENT, TestState::PHASE_UNLOCKED ], true ) ) {
			return $current_phase;
		}

		// An unknown status degrades to waiting, so a new server-side value fails soft.
		$map = [
			'awaiting_email' => TestState::PHASE_WAITING,
			'waiting'        => TestState::PHASE_WAITING,
			'send_failed'    => TestState::PHASE_SEND_FAILED,
			'not_delivered'  => TestState::PHASE_NOT_DELIVERED,
			'processing'     => TestState::PHASE_RECEIVED,
			'complete'       => TestState::PHASE_RECEIVED,
		];

		$phase = isset( $map[ $status ] ) ? $map[ $status ] : TestState::PHASE_WAITING;

		// Local failsafe: when the send-result report never landed, the server-side
		// clock never started, so a still-waiting test would wait forever.
		if ( $phase === TestState::PHASE_WAITING && ( time() - $started_at ) > 16 * MINUTE_IN_SECONDS ) {
			$phase = TestState::PHASE_NOT_DELIVERED;
		}

		return $phase;
	}

	/**
	 * Whether a lead submission is allowed from the given phase.
	 *
	 * @since 4.10.0
	 *
	 * @param string $phase The current TestState phase.
	 *
	 * @return bool
	 */
	public static function lead_allowed_in_phase( string $phase ): bool {

		// `received` is the unlock, `lead_sent` the resend. Everything else,
		// `unlocked` included, fails closed.
		return in_array( $phase, [ TestState::PHASE_RECEIVED, TestState::PHASE_LEAD_SENT ], true );
	}

	/**
	 * Submit the lead-capture email address to unlock the full report.
	 *
	 * @since 4.10.0
	 */
	public function ajax_lead() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh -- sequential guards, each a distinct early return.

		$this->verify_request();

		$state = TestState::get();

		if ( $state === null ) {
			wp_send_json_error(
				[
					'code'    => 'no_test',
					'message' => esc_html__( 'There is no test running. Please start a new one.', 'wp-mail-smtp' ),
				]
			);
		}

		if ( ! self::lead_allowed_in_phase( (string) $state['phase'] ) ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_phase',
					'message' => esc_html__( 'This test is not ready for that step. Please refresh the page and try again.', 'wp-mail-smtp' ),
				]
			);
		}

		// From state, never the request body: this must be the address the consent was given for.
		$email = isset( $state['email'] ) ? (string) $state['email'] : '';

		if ( empty( $email ) ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_email',
					'message' => esc_html__( 'Enter a valid email address.', 'wp-mail-smtp' ),
				]
			);
		}

		$result = ( new Client() )->submit_lead( $state['test_id'], $email, self::consent_wording() );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $this->error_payload( $result ) );
		}

		$phase = ! empty( $result['unlocked'] ) ? TestState::PHASE_UNLOCKED : TestState::PHASE_LEAD_SENT;

		TestState::update( [ 'phase' => $phase ] );

		wp_send_json_success(
			[
				'phase'      => $phase,
				'report_url' => esc_url_raw( $state['report_url'] ),
				'email'      => $email,
			]
		);
	}

	/**
	 * Clear the in-flight test.
	 *
	 * @since 4.10.0
	 */
	public function ajax_reset() {

		$this->verify_request();

		TestState::clear();

		wp_send_json_success();
	}

	/**
	 * The consent notice shown under the email field on the ready state.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public static function consent_wording() {

		return __( 'We\'ll email your report to this address and may follow up with occasional deliverability tips. Unsubscribe anytime.', 'wp-mail-smtp' );
	}

	/**
	 * The lead-sent state's "we've emailed it to {email}" sentence template.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public static function lead_sent_template() {

		/* translators: %s - the email address the report was sent to, wrapped in <strong>. The <br> keeps the first sentence on its own line. */
		return __( 'Your deliverability report is ready!<br>We\'ve emailed it to %s. Open the link inside to see your full results and what to fix first.', 'wp-mail-smtp' );
	}

	/**
	 * The unlocked state's "Hey {email}, welcome back" sentence template.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public static function unlocked_template() {

		/* translators: %s - the email address that unlocked the report, wrapped in <strong>. The <br> keeps the greeting on its own line. */
		return __( 'Hey %s, welcome back.<br>Your full deliverability report is ready to view right away.', 'wp-mail-smtp' );
	}

	/**
	 * Verify the request's nonce and capability, or send an error and stop.
	 *
	 * @since 4.10.0
	 */
	private function verify_request() {

		if ( ! check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_nonce',
					'title'   => esc_html__( 'Your Session Expired', 'wp-mail-smtp' ),
					'message' => esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ),
				]
			);
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error(
				[
					'code'    => 'forbidden',
					'title'   => esc_html__( 'You Cannot Run This Test', 'wp-mail-smtp' ),
					'message' => esc_html__( 'You do not have permission to run a deliverability test.', 'wp-mail-smtp' ),
				]
			);
		}
	}

	/**
	 * The captured local send-failure message for a connection.
	 *
	 * @since 4.10.0
	 *
	 * @param ConnectionInterface $connection The connection the test email was sent through.
	 *
	 * @return string
	 */
	private function last_send_error( ConnectionInterface $connection ) {

		$record  = EmailSendingDebug::get( $connection->get_id() );
		$message = isset( $record['error_message'] ) ? (string) $record['error_message'] : '';

		// 2000 is the API's own validation cap on this field.
		return function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 2000 ) : substr( $message, 0, 2000 );
	}

	/**
	 * Flatten a WP_Error into the JSON error payload every handler sends.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_Error $error The error to flatten.
	 *
	 * @return array
	 */
	private function error_payload( WP_Error $error ) {

		return [
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
			'data'    => $error->get_error_data(),
		];
	}
}
