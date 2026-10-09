<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\PartnerPlugins\Catalog;
use WPMailSMTP\PartnerPlugins\InstallPermission;
use WPMailSMTP\PartnerPlugins\Installer;

/**
 * About tab.
 *
 * @since 2.9.0
 */
class AboutTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	protected $slug = 'about';

	/**
	 * Tab priority.
	 *
	 * @since 2.9.0
	 *
	 * @var int
	 */
	protected $priority = 20;

	/**
	 * Link label of a tab.
	 *
	 * @since 2.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'About Us', 'wp-mail-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 2.9.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Tab content.
	 *
	 * @since 2.9.0
	 */
	public function display() {

		?>
		<div class="wp-mail-smtp-admin-about-section wp-mail-smtp-admin-columns">

			<div class="wp-mail-smtp-admin-column-60">
				<h3>
					<?php esc_html_e( 'Hello and welcome to WP Mail SMTP, the easiest and most popular WordPress SMTP plugin. We build software that helps your site reliably deliver emails every time.', 'wp-mail-smtp' ); ?>
				</h3>

				<p>
					<?php esc_html_e( 'Email deliverability has been a well-documented problem for all WordPress websites. However as WPForms grew, we became more aware of this painful issue that affects our users and the larger WordPress community. So we decided to solve this problem and make a solution that\'s beginner friendly.', 'wp-mail-smtp' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Our goal is to make reliable email deliverability easy for WordPress.', 'wp-mail-smtp' ); ?>
				</p>
				<p>
					<?php
					printf(
						wp_kses(
						/* translators: %1$s - WPForms URL, %2$s - WPBeginner URL, %3$s - OptinMonster URL, %4$s - MonsterInsights URL, %5$s - Awesome Motive URL */
							__( 'WP Mail SMTP is brought to you by the same team that\'s behind the most user friendly WordPress forms, <a href="%1$s" target="_blank" rel="noopener noreferrer">WPForms</a>, the largest WordPress resource site, <a href="%2$s" target="_blank" rel="noopener noreferrer">WPBeginner</a>, the most popular lead-generation software, <a href="%3$s" target="_blank" rel="noopener noreferrer">OptinMonster</a>, the best WordPress analytics plugin, <a href="%4$s" target="_blank" rel="noopener noreferrer">MonsterInsights</a>, and <a href="%5$s" target="_blank" rel="noopener noreferrer">more</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://wpforms.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://www.wpbeginner.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://optinmonster.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://www.monsterinsights.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
						esc_url( wp_mail_smtp()->get_utm_url( 'https://awesomemotive.com/', [ 'medium' => 'pluginaboutpage', 'content' => 'aboutwpmailsmtp' ] ) )
					);
					?>
				</p>
				<p>
					<?php esc_html_e( 'Yup, we know a thing or two about building awesome products that customers love.', 'wp-mail-smtp' ); ?>
				</p>
			</div>

			<div class="wp-mail-smtp-admin-column-40 wp-mail-smtp-admin-column-last">
				<figure>
					<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/about/team.jpg' ); ?>" alt="<?php esc_attr_e( 'The WPForms Team photo', 'wp-mail-smtp' ); ?>">
					<figcaption>
						<?php esc_html_e( 'The WPForms Team', 'wp-mail-smtp' ); ?>
					</figcaption>
				</figure>
			</div>

		</div>

		<?php

		$this->display_plugins();
	}

	/**
	 * Display the plugins section.
	 *
	 * @since 2.9.0
	 */
	protected function display_plugins() {

		?>
		<div class="wp-mail-smtp-admin-about-plugins">
			<div class="plugins-container">
				<?php
				foreach ( self::get_am_plugins() as $key => $plugin ) :
					$is_url_external = false;

					$data = $this->get_about_plugins_data( $plugin );

					if ( isset( $plugin['pro'] ) && array_key_exists( $plugin['pro']['path'], get_plugins() ) ) {
						$is_url_external = true;
						$plugin          = $plugin['pro'];

						$data = array_merge( $data, $this->get_about_plugins_data( $plugin, true ) );
					}

					?>
					<div class="plugin-container">
						<div class="plugin-item">
							<div class="details wp-mail-smtp-clear">
								<img src="<?php echo esc_url( $plugin['icon'] ); ?>" alt="<?php esc_attr_e( 'Plugin icon', 'wp-mail-smtp' ); ?>">
								<h5 class="plugin-name">
									<?php echo esc_html( $plugin['name'] ); ?>
								</h5>
								<p class="plugin-desc">
									<?php echo esc_html( $plugin['desc'] ); ?>
								</p>
							</div>
							<div class="actions wp-mail-smtp-clear">
								<div class="status">
									<strong>
										<?php
										printf(
										/* translators: %s - status HTML text. */
											esc_html__( 'Status: %s', 'wp-mail-smtp' ),
											'<span class="status-label ' . esc_attr( $data['status_class'] ) . '">' . esc_html( $data['status_text'] ) . '</span>'
										);
										?>
									</strong>
								</div>
								<div class="action-button">
									<?php
									$go_to_class = '';
									if ( $is_url_external && $data['status_class'] === 'status-download' ) {
										$go_to_class = ' go_to';
									}
									?>
									<a href="<?php echo esc_url( $plugin['url'] ); ?>"
										class="<?php echo esc_attr( $data['action_class'] . $go_to_class ); ?>"
										data-plugin="<?php echo esc_attr( $data['plugin_src'] ); ?>">
										<?php echo esc_html( $data['action_text'] ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Generate all the required CSS classed and labels to be used in rendering.
	 *
	 * @since 2.9.0
	 *
	 * @param array $plugin Plugin slug.
	 * @param bool  $is_pro License type.
	 *
	 * @return mixed
	 */
	protected function get_about_plugins_data( $plugin, $is_pro = false ) {

		$data = [];

		if ( array_key_exists( $plugin['path'], get_plugins() ) ) {
			if ( is_plugin_active( $plugin['path'] ) ) {
				// Status text/status.
				$data['status_class'] = 'status-active';
				$data['status_text']  = esc_html__( 'Active', 'wp-mail-smtp' );
				// Button text/status.
				$data['action_class'] = $data['status_class'] . ' button button-secondary disabled';
				$data['action_text']  = esc_html__( 'Activated', 'wp-mail-smtp' );
				$data['plugin_src']   = esc_attr( $plugin['path'] );
			} else {
				// Status text/status.
				$data['status_class'] = 'status-inactive';
				$data['status_text']  = esc_html__( 'Inactive', 'wp-mail-smtp' );
				// Button text/status.
				$data['action_class'] = $data['status_class'] . ' button button-secondary';
				$data['action_text']  = esc_html__( 'Activate', 'wp-mail-smtp' );
				$data['plugin_src']   = esc_attr( $plugin['path'] );
			}
		} elseif ( ! $is_pro ) {
				// Doesn't exist, install.
				// Status text/status.
				$data['status_class'] = 'status-download';
				$data['status_text']  = esc_html__( 'Not Installed', 'wp-mail-smtp' );
				// Button text/status.
				$data['action_class'] = $data['status_class'] . ' button button-primary';
				$data['action_text']  = esc_html__( 'Install Plugin', 'wp-mail-smtp' );
				$data['plugin_src']   = esc_url( $plugin['url'] );

				// If plugin URL is not a zip file, open a new tab with site URL.
				if ( preg_match( '/.*\.zip$/', $plugin['url'] ) === 0 ) {
				$data['status_class'] = 'status-open';
				$data['action_class'] = $data['status_class'] . ' button button-primary';
				$data['action_text']  = esc_html__( 'Visit Site', 'wp-mail-smtp' );
				}
		}

		return $data;
	}

	/**
	 * This page's own copy and imagery for each plugin, in display order.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private static function get_am_plugins_copy() {

		return [
			'optinmonster'                  => [
				'icon' => 'plugin-om.png',
				'desc' => esc_html__( 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'wp-mail-smtp' ),
			],
			'wpforms'                       => [
				'icon' => 'plugin-wpf.png',
				'desc' => esc_html__( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 600+ form templates. Trusted by over 5 million websites as the best forms plugin.', 'wp-mail-smtp' ),
			],
			'monsterinsights'               => [
				'icon' => 'plugin-mi.png',
				'desc' => esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'wp-mail-smtp' ),
			],
			'aioseo'                        => [
				'icon' => 'plugin-aioseo.png',
				'desc' => esc_html__( 'The original WordPress SEO plugin and toolkit that improves your website’s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.', 'wp-mail-smtp' ),
			],
			'seedprod'                      => [
				'icon' => 'plugin-seedprod.png',
				'desc' => esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'wp-mail-smtp' ),
			],
			'rafflepress'                   => [
				'icon' => 'plugin-rp.png',
				'desc' => esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'wp-mail-smtp' ),
			],
			'pushengage'                    => [
				'icon' => 'plugin-pushengage.png',
				'desc' => esc_html__( 'Connect with your visitors after they leave your website with the leading web push notification software. Over 10,000+ businesses worldwide use PushEngage to send 15 billion notifications each month.', 'wp-mail-smtp' ),
			],
			'smash-balloon-instagram-feeds' => [
				'icon' => 'plugin-smash-balloon-instagram-feeds.png',
				'desc' => esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'wp-mail-smtp' ),
			],
			'smash-balloon-facebook-feeds'  => [
				'icon' => 'plugin-smash-balloon-facebook-feeds.png',
				'desc' => esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'wp-mail-smtp' ),
			],
			'smash-balloon-youtube-feeds'   => [
				'icon' => 'plugin-smash-balloon-youtube-feeds.png',
				'desc' => esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'wp-mail-smtp' ),
			],
			'smash-balloon-twitter-feeds'   => [
				'icon' => 'plugin-smash-balloon-twitter-feeds.png',
				'desc' => esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'wp-mail-smtp' ),
			],
			'trustpulse'                    => [
				'icon' => 'plugin-trustpulse.png',
				'desc' => esc_html__( 'Boost your sales and conversions by up to 15% with real-time social proof notifications. TrustPulse helps you show live user activity and purchases to help convince other users to purchase.', 'wp-mail-smtp' ),
			],
			'searchwp'                      => [
				'icon' => 'searchwp.png',
				'desc' => esc_html__( 'The most advanced WordPress search plugin. Customize your WordPress search algorithm, reorder search results, track search metrics, and everything you need to leverage search to grow your business.', 'wp-mail-smtp' ),
			],
			'affiliatewp'                   => [
				'icon' => 'affiliatewp.png',
				'desc' => esc_html__( 'The #1 affiliate management plugin for WordPress. Easily create an affiliate program for your eCommerce store or membership site within minutes and start growing your sales with the power of referral marketing.', 'wp-mail-smtp' ),
			],
			'wp-simple-pay'                 => [
				'icon' => 'wp-simple-pay.png',
				'desc' => esc_html__( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'wp-mail-smtp' ),
			],
			'easy-digital-downloads'        => [
				'icon' => 'edd.png',
				'desc' => esc_html__( 'The best WordPress eCommerce plugin for selling digital downloads. Start selling eBooks, software, music, digital art, and more within minutes. Accept payments, manage subscriptions, advanced access control, and more.', 'wp-mail-smtp' ),
			],
			'sugar-calendar'                => [
				'icon' => 'sugar-calendar.png',
				'desc' => esc_html__( 'A simple & powerful event calendar plugin for WordPress that comes with all the event management features including payments, scheduling, timezones, ticketing, recurring events, and more.', 'wp-mail-smtp' ),
			],
			'wp-charitable'                 => [
				'icon' => 'plugin-charitable.png',
				'desc' => esc_html__( 'Top-rated WordPress donation and fundraising plugin. Over 10,000+ non-profit organizations and website owners use Charitable to create fundraising campaigns and raise more money online.', 'wp-mail-smtp' ),
			],
			'wpcode'                        => [
				'icon' => 'plugin-wpcode.png',
				'name' => esc_html__( 'WPCode Lite', 'wp-mail-smtp' ),
				'desc' => esc_html__( 'Future proof your WordPress customizations with the most popular code snippet management plugin for WordPress. Trusted by over 1,500,000+ websites for easily adding code to WordPress right from the admin area.', 'wp-mail-smtp' ),
			],
			'duplicator'                    => [
				'icon' => 'duplicator-icon-large.png',
				'desc' => esc_html__( 'Leading WordPress backup & site migration plugin. Over 1,500,000+ smart website owners use Duplicator to make reliable and secure WordPress backups to protect their websites. It also makes website migration really easy.', 'wp-mail-smtp' ),
			],
			'activelayer'                   => [
				'icon' => 'icon-activelayer.svg',
				'desc' => esc_html__( 'Smarter spam protection for WordPress. Catch spam in milliseconds with AI, invisible to your real visitors.', 'wp-mail-smtp' ),
			],
			'wpconsent'                     => [
				'icon' => 'icon-wpconsent.svg',
				'desc' => esc_html__( 'Stay GDPR & privacy compliant. Add a cookie consent banner to your site and meet privacy laws in minutes.', 'wp-mail-smtp' ),
			],
			'wpvibe'                        => [
				'icon' => 'plugin-vibe-ai.png',
				'name' => esc_html__( 'Vibe AI', 'wp-mail-smtp' ),
				'desc' => esc_html__( 'AI-powered tools for WordPress. Work faster and smarter with automation built for your WordPress workflow.', 'wp-mail-smtp' ),
			],
			'universally'                   => [
				'icon' => 'icon-universally.svg',
				'desc' => esc_html__( 'Make your WordPress site accessible to everyone. Automatic accessibility fixes and a visitor widget, no coding required.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * The AM plugins we propose to install, in display order.
	 *
	 * @since 2.9.0
	 *
	 * @return array
	 */
	private static function get_am_plugins() {

		$catalog = new Catalog();
		$plugins = [];

		foreach ( self::get_am_plugins_copy() as $slug => $copy ) {
			$plugin = $catalog->get( $slug );

			if ( $plugin === null ) {
				continue;
			}

			$icon = wp_mail_smtp()->assets_url . '/images/about/' . $copy['icon'];

			$plugins[ $slug ] = [
				'path' => $plugin->get_basename(),
				'icon' => $icon,
				'name' => $copy['name'] ?? $plugin->get_name(),
				'desc' => $copy['desc'],
				'url'  => $plugin->get_download_url() !== '' ? $plugin->get_download_url() : $plugin->get_upgrade_url(),
			];

			if ( $plugin->get_upgrade_url() === '' ) {
				continue;
			}

			$plugins[ $slug ]['pro'] = [
				'path' => $plugin->get_basename_pro(),
				'icon' => isset( $copy['icon_pro'] ) ? wp_mail_smtp()->assets_url . '/images/about/' . $copy['icon_pro'] : $icon,
				'name' => $copy['name_pro'] ?? $plugin->get_name_pro(),
				'desc' => $copy['desc_pro'] ?? $copy['desc'],
				'url'  => $plugin->get_upgrade_url(),
			];
		}

		return $plugins;
	}

	/**
	 * Active the given plugin.
	 *
	 * @since 2.9.0
	 */
	public static function ajax_plugin_activate() {

		if ( empty( $_POST['plugin'] ) ) {
			wp_send_json_error( esc_html__( 'No plugin was specified.', 'wp-mail-smtp' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Area::process_ajax() verifies the nonce before dispatching here.
		$basename = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
		$plugin   = ( new Catalog() )->get_by_basename( $basename );

		if ( $plugin === null ) {
			wp_send_json_error( esc_html__( 'This plugin is not allowed.', 'wp-mail-smtp' ) );
		}

		$permission = new InstallPermission();
		$permitted  = $permission->check( $plugin );

		if ( is_wp_error( $permitted ) ) {
			wp_send_json_error( $permission->get_refusal_payload( $permitted ) );
		}

		$activated = ( new Installer() )->activate( $plugin );

		if ( is_wp_error( $activated ) ) {
			wp_send_json_error( $activated->get_error_message() );
		}

		wp_send_json_success( esc_html__( 'Plugin activated.', 'wp-mail-smtp' ) );
	}

	/**
	 * Install & activate the given plugin.
	 *
	 * @since 2.9.0
	 */
	public static function ajax_plugin_install() {

		if ( empty( $_POST['plugin'] ) ) {
			wp_send_json_error( esc_html__( 'No plugin was specified.', 'wp-mail-smtp' ) );
		}

		$plugin_url = esc_url_raw( wp_unslash( $_POST['plugin'] ) );
		$plugin     = ( new Catalog() )->get_by_download_url( $plugin_url );

		if ( $plugin === null ) {
			wp_send_json_error( esc_html__( 'This plugin is not allowed.', 'wp-mail-smtp' ) );
		}

		$permission = new InstallPermission();
		$permitted  = $permission->check( $plugin );

		if ( is_wp_error( $permitted ) ) {
			wp_send_json_error( $permission->get_refusal_payload( $permitted ) );
		}

		// Avoids undefined notices from the file operations the installer runs.
		set_current_screen( 'wp-mail-smtp_page_wp-mail-smtp-about' );

		$redirect_url = esc_url_raw( add_query_arg( [ 'page' => 'wp-mail-smtp-about' ], admin_url( 'admin.php' ) ) );

		$result = ( new Installer() )->install( $plugin, $redirect_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		if ( $result['basename'] === 'wpforms-lite/wpforms.php' ) {
			add_option( 'wpforms_installation_source', 'wp-mail-smtp-about-us' );
		}

		wp_send_json_success(
			[
				'msg'          => $result['activated']
					? esc_html__( 'Plugin installed & activated.', 'wp-mail-smtp' )
					: esc_html__( 'Plugin installed.', 'wp-mail-smtp' ),
				'is_activated' => $result['activated'],
				'basename'     => $result['basename'],
			]
		);
	}
}
