<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Options;
use WPMailSMTP\TestEmail\TestEmail;
use WPMailSMTP\WP;
use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Helpers\UI;

/**
 * Class TestTab is part of Area, displays email testing page of the plugin.
 *
 * @since 1.0.0
 */
class TestTab extends PageAbstract {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'test';

	/**
	 * Tab priority.
	 *
	 * @since 2.8.0
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Domain Checker API object.
	 *
	 * @since 2.6.0
	 *
	 * @var DomainChecker|null
	 */
	private $domain_checker;

	/**
	 * Option key where the test email form values are persisted between visits,
	 * so the next page load prefills with what the user last entered.
	 *
	 * @since 4.9.0
	 *
	 * @const string
	 */
	const TEST_EMAIL_OPTION_KEY = 'wp_mail_smtp_test_email';

	/**
	 * Test email sending failed.
	 *
	 * @since 3.0.0
	 *
	 * @const int
	 */
	const FAILED = 0;

	/**
	 * Test email sent successfully.
	 *
	 * @since 3.0.0
	 *
	 * @const int
	 */
	const SUCCESS = 1;

	/**
	 * Test email domain check failed.
	 *
	 * @since 3.0.0
	 *
	 * @const int
	 */
	const FAILED_DOMAIN_CHECK = 2;

	/**
	 * Test email result.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private $result = null;

	/**
	 * Test email connection.
	 *
	 * @since 3.7.0
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

	/**
	 * @inheritdoc
	 */
	public function get_label() {

		return esc_html__( 'Email Test', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Display the content of the tab.
	 *
	 * @since 1.0.0
	 */
	public function display() {

		if ( $this->result === self::FAILED_DOMAIN_CHECK ) {
			$this->display_domain_check_details();

			return;
		}

		if ( $this->result === self::SUCCESS ) {
			$this->display_success_banner();
		}

		$this->display_form();
	}

	/**
	 * Process the email-test form submission.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Submitted POST data.
	 *
	 * @inheritdoc
	 */
	public function process_post( $data ) {

		$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();

		/**
		 * Filters test email connection object.
		 *
		 * @since 3.7.0
		 *
		 * @param ConnectionInterface $connection The Connection object.
		 * @param array               $data       Post data.
		 */
		$this->connection = apply_filters( 'wp_mail_smtp_admin_pages_test_tab_process_post_connection', $connection, $data );

		if ( ! empty( $data['test']['email'] ) ) {
			$data['test']['email'] = wp_unslash( $data['test']['email'] );
			$data['test']['email'] = filter_var( $data['test']['email'], FILTER_VALIDATE_EMAIL );
		}

		if ( empty( $data['test']['email'] ) ) {
			WP::add_admin_notice(
				esc_html__( 'Test failed. Please use a valid email address and try to resend the test email.', 'wp-mail-smtp' ),
				WP::ADMIN_NOTICE_WARNING
			);

			return;
		}

		$is_html = ! empty( $data['test']['html'] );

		$test_email = ( new TestEmail() )
			->with_connection( $this->connection )
			->with_context( TestEmail::CONTEXT_ADMIN_TEST )
			->as_html( $is_html )
			->with_domain_check( true );

		$test_email->send( $data['test']['email'] );

		$this->result         = $test_email->get_result();
		$this->domain_checker = $test_email->get_domain_checker();

		// Persist the form values so the next visit prefills with what the
		// user last entered (matches ESMTP's TestTab behavior).
		$test_email_options         = get_option( self::TEST_EMAIL_OPTION_KEY, [] );
		$test_email_options['to']   = $data['test']['email'];
		$test_email_options['html'] = $is_html;

		update_option( self::TEST_EMAIL_OPTION_KEY, $test_email_options, false );
	}

	/**
	 * Display test email title section.
	 *
	 * @since 3.0.0
	 */
	private function display_title_section() {

		?>
		<!-- Test Email Section Title -->
		<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc wp-mail-smtp-section-heading--has-divider">
			<div class="wp-mail-smtp-setting-field">
				<h2><?php esc_html_e( 'Send a Test Email', 'wp-mail-smtp' ); ?></h2>
			</div>
		</div>
		<?php
	}

	/**
	 * Display test email form.
	 *
	 * @since 3.0.0
	 */
	private function display_form() {

		$test_email_options = array_merge(
			[
				'to'   => '',
				'html' => true,
			],
			get_option( self::TEST_EMAIL_OPTION_KEY, [] )
		);

		if ( empty( $test_email_options['to'] ) ) {
			$test_email_options['to'] = wp_get_current_user()->user_email;
		}

		?>
		<form id="email-test-form" method="POST" action="<?php echo esc_url( $this->get_link() ); ?>">
			<?php $this->wp_nonce_field(); ?>

			<?php $this->display_title_section(); ?>

			<!-- Test Email -->
			<div id="wp-mail-smtp-setting-row-test_email" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-email wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-test_email"><?php esc_html_e( 'Send To', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[test][email]" value="<?php echo esc_attr( $test_email_options['to'] ); ?>"
							type="email" id="wp-mail-smtp-setting-test_email" spellcheck="false" required>
					<p class="desc">
						<?php esc_html_e( 'Enter email address where test email will be sent.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<?php
			/**
			 * Fires after "Send To" section on the test email page.
			 *
			 * @since 3.7.0
			 */
			do_action( 'wp_mail_smtp_admin_pages_test_tab_display_form_send_to_after' );
			?>

			<!-- HTML/Plain -->
			<div id="wp-mail-smtp-setting-row-test_email_html" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-test_email_html"><?php esc_html_e( 'HTML', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php
					UI::toggle(
						[
							'name'    => 'wp-mail-smtp[test][html]',
							'id'      => 'wp-mail-smtp-setting-test_email_html',
							'checked' => (bool) $test_email_options['html'],
						]
					);
					?>
					<p class="desc">
						<?php esc_html_e( 'Send this email in HTML or in plain text format.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<p class="wp-mail-smtp-submit">
				<?php
				$btn       = 'wp-mail-smtp-btn-orange';
				$disabled  = '';
				$help_text = '';

				$mailer = wp_mail_smtp()->get_providers()->get_mailer(
					Options::init()->get( 'mail', 'mailer' ),
					wp_mail_smtp()->get_processor()->get_phpmailer()
				);

				if ( ! $mailer || ! $mailer->is_mailer_complete() ) {
					$btn      = 'wp-mail-smtp-btn-red';
					$disabled = 'disabled';

					$help_text = '<span class="help-text"><strong>' . esc_html__( 'You cannot send an email. Mailer is not properly configured. Please check your settings.', 'wp-mail-smtp' ) . '</strong></span>';
				}
				?>
				<button type="submit" class="wp-mail-smtp-btn wp-mail-smtp-btn-md <?php echo esc_attr( $btn ); ?>" <?php echo esc_attr( $disabled ); ?>>
					<span><?php esc_html_e( 'Send Email', 'wp-mail-smtp' ); ?></span>
					<?php echo wp_mail_smtp()->prepare_loader( 'white', 'sm' ); // phpcs:ignore ?>
				</button>
				<?php echo $help_text; ?>
			</p>
			<?php $this->post_form_hidden_field(); ?>
		</form>

		<?php if ( ! empty( $mailer ) && $mailer->is_mailer_complete() && isset( $_GET['auto-start'] ) ) : // phpcs:ignore ?>
			<script>
				(function( $ ) {
					var $button = $( '.wp-mail-smtp-tab-tools-test #email-test-form .wp-mail-smtp-btn' );

					$button.attr( 'disabled', true );
					$button.find( 'span' ).hide();
					$button.find( '.wp-mail-smtp-loading' ).show();

					$( '#email-test-form' ).submit();
				}( jQuery ));
			</script>
		<?php
		endif;
	}

	/**
	 * Display the domain check details.
	 *
	 * @since 2.6.0
	 */
	protected function display_domain_check_details() {

		if ( empty( $this->domain_checker ) || $this->domain_checker->no_issues() ) {
			return;
		}

		$this->display_title_section();
		?>

		<?php if ( $this->domain_checker->is_supported_mailer() ) : ?>
			<div class="notice-warning notice-inline">
				<p><?php esc_html_e( 'The test email might have sent, but its deliverability should be improved.', 'wp-mail-smtp' ); ?></p>
			</div>
		<?php endif; ?>

		<?php echo $this->domain_checker->get_results_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="wp-mail-smtp-test-email-resend">
			<a href="<?php echo esc_url( $this->get_link() ); ?>">
				<?php esc_html_e( 'Send Another Test Email', 'wp-mail-smtp' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render the success banner shown after a test email succeeds. This is the
	 * Lite implementation: success headline, four Pro feature bullets, an
	 * "Upgrade to Pro" CTA with a $50 OFF badge, the hero illustration, and
	 * the Pro Tip strip. The Pro subclass overrides this method to render its
	 * own variants.
	 *
	 * @since 4.9.0
	 */
	protected function display_success_banner() {

		$assets_url   = wp_mail_smtp()->assets_url;
		$illustration = $assets_url . '/images/test-success/illustration-lite.svg';

		$upgrade_url = wp_mail_smtp()->get_upgrade_link(
			[
				'medium'  => 'lite-test-email-success',
				'content' => 'Upgrade Button',
			]
		);

		$bullets = [
			esc_html__( 'Log, track, and resend any email your site sends', 'wp-mail-smtp' ),
			esc_html__( 'Get instant failure alerts via Email, Slack, SMS, Discord, etc', 'wp-mail-smtp' ),
			esc_html__( 'Never miss an email with an automatic backup mailer', 'wp-mail-smtp' ),
		];
		?>
		<div class="wpms-test-email-success-banner wp-mail-smtp-test-success-banner wp-mail-smtp-test-success-banner--lite">
			<?php $this->display_success_banner_dismiss(); ?>

			<div class="wpms:flex wpms:items-center wpms:gap-md wpms:max-tablet:flex-col wpms:max-tablet:items-stretch">
				<div class="wpms:flex wpms:flex-col wpms:flex-1 wpms:min-w-[0] wpms:gap-md wpms:p-md">
					<div class="wpms:flex wpms:flex-col wpms:gap-sm">
						<div class="wpms-test-email-success-banner__heading">
							<span aria-hidden="true" class="wpms:icon-[fa6-solid--circle-check] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
							<h2>
								<?php esc_html_e( 'Test email sent successfully! Check your inbox to confirm delivery.', 'wp-mail-smtp' ); ?>
							</h2>
						</div>
						<p class="wpms:m-[0]! wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s - "Pro" wrapped in a bold tag highlighting the WP Mail SMTP Pro tier. */
									__( 'Level Up Your Email Game! Unlock these %s features and get even more from WP Mail SMTP.', 'wp-mail-smtp' ),
									'<strong class="wpms:font-medium! wpms:text-primary">' . esc_html__( 'Pro', 'wp-mail-smtp' ) . '</strong>'
								),
								[ 'strong' => [ 'class' => [] ] ]
							);
							?>
						</p>
					</div>

					<ul class="wpms:m-[0]! wpms:p-[0]! wpms:list-none wpms:flex wpms:flex-col wpms:gap-sm">
						<?php foreach ( $bullets as $bullet ) : ?>
							<li class="wpms:m-[0]! wpms:p-[0]! wpms:flex wpms:items-center wpms:gap-sm">
								<span aria-hidden="true" class="wpms:icon-[fa6-solid--check] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
								<span class="wpms:text-sm! wpms:leading-5! wpms:font-medium! wpms:text-primary"><?php echo esc_html( $bullet ); ?></span>
							</li>
						<?php endforeach; ?>
						<li class="wpms:m-[0]! wpms:p-[0]! wpms:flex wpms:items-center wpms:gap-sm">
							<span aria-hidden="true" class="wpms:icon-[fa6-solid--check] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
							<span class="wpms:text-sm! wpms:leading-5! wpms:font-medium! wpms:text-primary">
								<?php esc_html_e( 'Priority support from our email deliverability experts', 'wp-mail-smtp' ); ?>
							</span>
							<span class="wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
								<?php esc_html_e( '...and much more!', 'wp-mail-smtp' ); ?>
							</span>
						</li>
					</ul>

					<div class="wpms:flex wpms:flex-col wpms:gap-[8px] wpms:items-start">
						<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-cta-large wp-mail-smtp-btn-secondary">
							<?php esc_html_e( 'Upgrade to WP Mail SMTP Pro', 'wp-mail-smtp' ); ?>
						</a>

						<p class="wpms:m-[0]! wpms:flex wpms:items-center wpms:gap-[7px]">
							<span aria-hidden="true" class="wpms:icon-[custom--badge-percent] wpms:text-success wpms:w-[16px] wpms:h-[16px] wpms:shrink-0"></span>
							<span class="wpms:text-sm! wpms:leading-5! wpms:text-tertiary">
								<strong class="wpms:font-medium! wpms:text-success"><?php esc_html_e( '$50 OFF', 'wp-mail-smtp' ); ?></strong>
								<?php esc_html_e( 'for WP Mail SMTP users, applied at checkout.', 'wp-mail-smtp' ); ?>
							</span>
						</p>
					</div>
				</div>

				<div class="wpms:w-[400px] wpms:self-stretch wpms:shrink-0 wpms:max-tablet:w-full wpms:max-tablet:h-[316px]">
					<img src="<?php echo esc_url( $illustration ); ?>" alt="<?php esc_attr_e( 'A person celebrating after sending an email.', 'wp-mail-smtp' ); ?>" class="wpms:block wpms:w-full wpms:h-full wpms:object-cover">
				</div>
			</div>

			<?php $this->display_success_pro_tip_strip(); ?>
		</div>
		<?php
	}

	/**
	 * Render the dismiss button shared by every success-banner variant.
	 * Uses the WP-native `.notice-dismiss` class so the visual treatment
	 * matches WP admin notices; JS handles the per-session hide.
	 *
	 * @since 4.9.0
	 */
	protected function display_success_banner_dismiss() {

		?>
		<button type="button" class="notice-dismiss wpms-test-email-success-banner__dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'wp-mail-smtp' ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'wp-mail-smtp' ); ?></span>
		</button>
		<?php
	}

	/**
	 * Render the Pro Tip strip shown below the Lite and Pro-Setup-Pending
	 * banners. Picks the first not-installed cross-sell candidate as the
	 * featured plugin; if no candidates remain, renders nothing.
	 *
	 * @since 4.9.0
	 */
	protected function display_success_pro_tip_strip() {

		// Capability gating lives in get_cross_sell_recommendations() — it returns
		// an empty pool for users without install_plugins, which short-circuits below.
		$recommendations = $this->get_cross_sell_recommendations( 1 );

		if ( empty( $recommendations ) ) {
			return;
		}

		$product     = $recommendations[0];
		$install_url = $this->get_install_plugin_url( $product['install'] );
		?>
		<div class="wpms-test-email-pro-tip-strip wpms:flex wpms:items-center wpms:gap-sm wpms:px-md wpms:py-sm wpms:bg-surface-background-white wpms:border-t wpms:border-surface-divider">
			<span aria-hidden="true" class="wpms:icon-[fa6-solid--lightbulb] wpms:text-utility-yellow-50 wpms:w-[14px] wpms:h-[14px] wpms:shrink-0"></span>
			<span class="wpms-test-email-pro-tip-strip__initial wpms:text-sm wpms:leading-5 wpms:text-tertiary">
				<strong class="wpms:font-medium! wpms:text-primary"><?php esc_html_e( 'Pro Tip:', 'wp-mail-smtp' ); ?></strong>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s - product name wrapped in <strong>, e.g. WPConsent. */
						__( '%1$s with our sister plugin %2$s', 'wp-mail-smtp' ),
						esc_html( $product['pro_tip'] ),
						'<strong class="wpms:font-medium! wpms:text-primary">' . esc_html( $product['name'] ) . '</strong>'
					),
					[ 'strong' => [ 'class' => [] ] ]
				);
				?>
				-
				<a href="<?php echo esc_url( $install_url ); ?>"
					class="js-wp-mail-smtp-plugin-install-link status-download wpms:font-medium! wpms:text-link wpms:underline wpms:focus:outline-none! wpms:focus:shadow-none!"
					data-plugin="<?php echo esc_attr( $product['install_url'] ); ?>"
					data-plugin-name="<?php echo esc_attr( $product['name'] ); ?>"
					>
                    <?php
					/* translators: %s - product name (e.g. WPConsent). */
					printf( esc_html__( 'Install %s (Free)', 'wp-mail-smtp' ), esc_html( $product['name'] ) );
					?>
                    </a>
			</span>
			<span class="wpms-test-email-pro-tip-strip__success wpms:text-sm wpms:leading-5 wpms:text-tertiary" role="status" hidden>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %1$s - plugin name (bold). %2$s - settings-page hyperlink. */
						__( '%1$s was installed and activated, please visit their %2$s to configure it.', 'wp-mail-smtp' ),
						'<strong class="wpms:font-medium! wpms:text-primary">' . esc_html( $product['name'] ) . '</strong>',
						'<a href="' . esc_url( $product['settings_page_url'] ) . '" class="wpms:font-medium! wpms:text-link wpms:underline">' . esc_html__( 'settings page', 'wp-mail-smtp' ) . '</a>'
					),
					[
						'strong' => [ 'class' => [] ],
						'a'      => [
							'href'  => [],
							'class' => [],
						],
					]
				);
				?>
			</span>
		</div>
		<?php
	}

	/**
	 * Build the cross-sell pool filtered to plugins not already installed
	 * on this site. The catalog is inlined here — the success banner uses
	 * its own small, hand-curated set, separate from the About-tab list at
	 * {@see AboutTab::get_all_plugins()}.
	 *
	 * @since 4.9.0
	 *
	 * @param int $limit Max number of products to return.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function get_cross_sell_recommendations( $limit = 3 ) { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded -- Inner foreach over per-product competitor list is intentional; flattening would obscure the catalog structure.

		if ( ! current_user_can( 'install_plugins' ) ) {
			return [];
		}

		$assets_url = wp_mail_smtp()->assets_url;

		$catalog = [
			[
				'name'              => 'ActiveLayer',
				'title'             => esc_html__( 'Smarter Spam Protection for WordPress', 'wp-mail-smtp' ),
				'desc'              => esc_html__( 'Catch spam in milliseconds with AI, invisible to your real visitors.', 'wp-mail-smtp' ),
				'pro_tip'           => esc_html__( 'Stop spam at the door', 'wp-mail-smtp' ),
				'icon'              => $assets_url . '/images/about/icon-activelayer.svg',
				'plugin'            => 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php',
				'install'           => 'activelayer-anti-spam-spam-protection-for-forms-comments',
				'install_url'       => 'https://downloads.wordpress.org/plugin/activelayer-anti-spam-spam-protection-for-forms-comments.zip',
				'settings_page_url' => admin_url( 'admin.php?page=activelayer-settings' ),
				'framed_icon'       => true,
				'competitors'       => [
					'akismet/akismet.php',
					'antispam-bee/antispam_bee.php',
					'honeypot/wp-armour.php',
					'wp-armour-extended/wp-armour-extended.php',
					'cleantalk-spam-protect/cleantalk.php',
					'wp-cerber/wp-cerber.php',
					'anti-spam/anti-spam.php',
				],
			],
			[
				'name'              => 'WPConsent',
				'title'             => esc_html__( 'Stay GDPR & Privacy Compliant', 'wp-mail-smtp' ),
				'desc'              => esc_html__( 'Add a cookie consent banner to your site and meet privacy laws in minutes.', 'wp-mail-smtp' ),
				'pro_tip'           => esc_html__( 'Stay GDPR & Privacy compliant', 'wp-mail-smtp' ),
				'icon'              => $assets_url . '/images/about/icon-wpconsent.svg',
				'plugin'            => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
				'install'           => 'wpconsent-cookies-banner-privacy-suite',
				'install_url'       => 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip',
				'settings_page_url' => admin_url( 'admin.php?page=wpconsent-cookies' ),
				'framed_icon'       => true,
				'competitors'       => [
					'cookie-law-info/cookie-law-info.php',
					'complianz-gdpr/complianz-gpdr.php',
					'complianz-gdpr-premium/complianz-gpdr-premium.php',
					'cookie-notice/cookie-notice.php',
					'gdpr-cookie-compliance/moove-gdpr.php',
					'iubenda-cookie-law-solution/iubenda_cookie_solution.php',
					'real-cookie-banner/index.php',
					'cookiebot/cookiebot.php',
					'uk-cookie-consent/uk-cookie-consent.php',
					'borlabs-cookie/borlabs-cookie.php',
				],
			],
			[
				'name'              => 'Duplicator',
				'title'             => esc_html__( 'Add Secure WordPress Backups', 'wp-mail-smtp' ),
				'desc'              => esc_html__( 'Automated, encrypted backups with 1-click restore to keep your site safe.', 'wp-mail-smtp' ),
				'pro_tip'           => esc_html__( 'Protect your site with automated backups', 'wp-mail-smtp' ),
				'icon'              => $assets_url . '/images/about/icon-duplicator.svg',
				'plugin'            => 'duplicator/duplicator.php',
				'plugin_pro'        => 'duplicator-pro/duplicator-pro.php',
				'install'           => 'duplicator',
				'install_url'       => 'https://downloads.wordpress.org/plugin/duplicator.zip',
				'settings_page_url' => admin_url( 'admin.php?page=duplicator-settings' ),
				'framed_icon'       => false,
				'competitors'       => [
					'all-in-one-wp-migration/all-in-one-wp-migration.php',
					'all-in-one-wp-migration-unlimited-extension/all-in-one-wp-migration-unlimited-extension.php',
					'updraftplus/updraftplus.php',
					'wpvivid-backuprestore/wpvivid-backuprestore.php',
					'wpvivid-backup-pro/wpvivid-backup-pro.php',
					'backwpup/backwpup.php',
					'backwpup-pro/backwpup.php',
					'migrate-guru/migrateguru.php',
					'wp-migrate-db/wp-migrate-db.php',
					'wp-migrate-db-pro/wp-migrate-db-pro.php',
					'wp-staging/wp-staging.php',
					'wp-staging-pro/wp-staging-pro.php',
					'backupbuddy/backupbuddy.php',
				],
			],
		];

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();

		$candidates = array_filter(
			$catalog,
			function ( $product ) use ( $installed ) {
				if ( array_key_exists( $product['plugin'], $installed ) ) {
					return false;
				}

				// Pro variant present too — treat as "already provided" so we
				// don't push the Lite when the Pro version is installed.
				if ( ! empty( $product['plugin_pro'] ) && array_key_exists( $product['plugin_pro'], $installed ) ) {
					return false;
				}

				// Drop the entry when a competitor that covers the same need
				// is installed (e.g., recommending Duplicator to an
				// UpdraftPlus user is wasted screen space).
				if ( ! empty( $product['competitors'] ) ) {
					foreach ( $product['competitors'] as $competitor ) {
						if ( array_key_exists( $competitor, $installed ) ) {
							return false;
						}
					}
				}

				return true;
			}
		);

		return array_slice( array_values( $candidates ), 0, $limit );
	}

	/**
	 * Build a nonce-protected install-plugin URL for the WP-Admin updater.
	 * Users without the install_plugins capability get the public wp.org
	 * page so the link still makes sense.
	 *
	 * @since 4.9.0
	 *
	 * @param string $slug WP.org plugin slug.
	 *
	 * @return string
	 */
	private function get_install_plugin_url( $slug ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return 'https://wordpress.org/plugins/' . $slug . '/';
		}

		return wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ),
			'install-plugin_' . $slug
		);
	}

	/**
	 * Get the plain text prepared message for test email.
	 *
	 * Use {@see TestEmail::get_email_message_text()} instead.
	 *
	 * @since      1.4.0
	 * @since      1.5.0 Display an upsell to WP Mail SMTP Pro if free version installed.
	 * @since      2.6.0 Change visibility, so it can be used elsewhere.
	 * @deprecated {VERSION}
	 *
	 * @return string
	 */
	public static function get_email_message_text() {

		return TestEmail::get_email_message_text();
	}

	/**
	 * Returns debug information for detection, processing, and display.
	 *
	 * @since      1.3.0
	 * @deprecated {VERSION}
	 *
	 * @return array
	 */
	protected function get_debug_details() {

		_deprecated_function( __METHOD__, '4.9.0', '\WPMailSMTP\Admin\EmailSendingErrors\EmailSendingErrors::get_local_failure_info' );

		return [];
	}

	/**
	 * Displays all the various error and debug details.
	 *
	 * @since      1.3.0
	 * @deprecated {VERSION}
	 */
	protected function display_debug_details() {

		_deprecated_function( __METHOD__, '4.9.0', '\WPMailSMTP\Admin\EmailSendingErrors\EmailSendingErrors::print_banner_body' );
	}
}
