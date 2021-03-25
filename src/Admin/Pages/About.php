<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Admin\PluginsInstallSkin;
use WPMailSMTP\Admin\PluginsInstallUpgrader;
use WPMailSMTP\WP;

/**
 * Class About to display a page with About Us and Versus content.
 *
 * @since 1.5.0
 */
class About extends PageAbstract {

	/**
	 * @since 1.5.0
	 *
	 * @var string Slug of a page.
	 */
	protected $slug = 'about';

	/**
	 * @since 1.5.0
	 *
	 * @var array List of supported tabs.
	 */
	protected $tabs = array( 'about', 'versus' );

	/**
	 * Get the page/tab link.
	 *
	 * @since 1.5.0
	 *
	 * @param string $tab Tab to generate a link to.
	 *
	 * @return string
	 */
	public function get_link( $tab = '' ) {

		return add_query_arg(
			'tab',
			$this->get_defined_tab( $tab ),
			WP::admin_url( 'admin.php?page=' . Area::SLUG . '-' . $this->slug )
		);
	}

	/**
	 * Get the current tab.
	 *
	 * @since 1.5.0
	 *
	 * @return string Current tab.
	 */
	public function get_current_tab() {

		if ( empty( $_GET['tab'] ) ) { // phpcs:ignore
			return $this->slug;
		}

		return $this->get_defined_tab( $_GET['tab'] ); // phpcs:ignore
	}

	/**
	 * Get the defined or default tab.
	 *
	 * @since 1.5.0
	 *
	 * @param string $tab Tab to check.
	 *
	 * @return string Defined tab. Fallback to default one if it doesn't exist.
	 */
	protected function get_defined_tab( $tab ) {

		$tab = \sanitize_key( $tab );

		return \in_array( $tab, $this->tabs, true ) ? $tab : $this->slug;
	}

	/**
	 * Get label for a tab.
	 * Process only those that exists.
	 * Defaults to "About Us".
	 *
	 * @since 1.5.0
	 *
	 * @param string $tab Tab to get label for.
	 *
	 * @return string
	 */
	public function get_label( $tab = '' ) {

		switch ( $this->get_defined_tab( $tab ) ) {
			case 'versus':
				$label = \sprintf(
					/* translators: %s - plugin current license type. */
					\esc_html__( '%s vs Pro', 'wp-mail-smtp' ),
					\ucfirst( \wp_mail_smtp()->get_license_type() )
				);
				break;

			case 'about':
			default:
				$label = \esc_html__( 'About Us', 'wp-mail-smtp' );
				break;
		}

		return $label;
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return $this->get_label( $this->get_current_tab() );
	}

	/**
	 * Display About page content based on the current tab.
	 *
	 * @since 1.5.0
	 */
	public function display() {
		?>

		<div class="wp-mail-smtp-page-title">
			<a href="<?php echo \esc_url( $this->get_link() ); ?>" class="tab <?php echo $this->get_current_tab() === 'about' ? 'active' : ''; ?>">
				<?php echo \esc_html( $this->get_label( 'about' ) ); ?>
			</a>

			<?php if ( \wp_mail_smtp()->get_license_type() === 'lite' ) : ?>
				<a href="<?php echo \esc_url( $this->get_link( 'versus' ) ); ?>" class="tab <?php echo $this->get_current_tab() === 'versus' ? 'active' : ''; ?>">
					<?php echo \esc_html( $this->get_label( 'versus' ) ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="wp-mail-smtp-page-content">
			<h1 class="screen-reader-text">
				<?php echo \esc_html( $this->get_label( $this->get_current_tab() ) ); ?>
			</h1>

			<?php do_action( 'wp_mail_smtp_admin_pages_before_content' ); ?>

			<?php
			$callback = 'display_' . $this->get_current_tab();

			if ( \method_exists( $this, $callback ) ) {
				$this->{$callback}();
			} else {
				$this->display_about();
			}
			?>
		</div>

		<?php
	}

	/**
	 * Display an "About Us" tab content.
	 *
	 * @since 1.5.0
	 */
	protected function display_about() {
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
							array(
								'a' => array(
									'href'   => array(),
									'rel'    => array(),
									'target' => array(),
								),
							)
						),
						'https://wpforms.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://www.wpbeginner.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://optinmonster.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://www.monsterinsights.com/?utm_source=wpmailsmtpplugin&utm_medium=pluginaboutpage&utm_campaign=aboutwpmailsmtp',
						'https://awesomemotive.com/'
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

		// Do not display the plugin section if the user can't install or activate them.
		if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$this->display_plugins();
	}

	/**
	 * Display the plugins section.
	 *
	 * @since 2.2.0
	 */
	protected function display_plugins() {
		?>

		<div class="wp-mail-smtp-admin-about-plugins">
			<div class="plugins-container">
				<?php
				foreach ( $this->get_am_plugins() as $key => $plugin ) :
					$is_url_external = false;

					$data = $this->get_about_plugins_data( $plugin );

					if ( isset( $plugin['pro'] ) && \array_key_exists( $plugin['pro']['path'], \get_plugins() ) ) {
						$is_url_external = true;
						$plugin          = $plugin['pro'];

						$data = array_merge( $data, $this->get_about_plugins_data( $plugin, true ) );
					}

					// Do not display a plugin which has to be installed and the user can't install it.
					if ( ! current_user_can( 'install_plugins' ) && $data['status_class'] === 'status-download' ) {
						continue;
					}

					?>
					<div class="plugin-container">
						<div class="plugin-item">
							<div class="details wp-mail-smtp-clear">
								<img src="<?php echo \esc_url( $plugin['icon'] ); ?>" alt="<?php esc_attr_e( 'Plugin icon', 'wp-mail-smtp' ); ?>">
								<h5 class="plugin-name">
									<?php echo $plugin['name']; ?>
								</h5>
								<p class="plugin-desc">
									<?php echo $plugin['desc']; ?>
								</p>
							</div>
							<div class="actions wp-mail-smtp-clear">
								<div class="status">
									<strong>
										<?php
										\printf(
											/* translators: %s - status HTML text. */
											\esc_html__( 'Status: %s', 'wp-mail-smtp' ),
											'<span class="status-label ' . $data['status_class'] . '">' . $data['status_text'] . '</span>'
										);
										?>
									</strong>
								</div>
								<div class="action-button">
									<?php
									$go_to_class = '';
									if ( $is_url_external && $data['status_class'] === 'status-download' ) {
										$go_to_class = 'go_to';
									}
									?>
									<a href="<?php echo \esc_url( $plugin['url'] ); ?>"
										class="<?php echo \esc_attr( $data['action_class'] ); ?> <?php echo $go_to_class; ?>"
										data-plugin="<?php echo $data['plugin_src']; ?>">
										<?php echo $data['action_text']; ?>
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
	 * @since 1.5.0
	 *
	 * @param array $plugin
	 * @param bool  $is_pro
	 *
	 * @return mixed
	 */
	protected function get_about_plugins_data( $plugin, $is_pro = false ) {

		$data = array();

		if ( \array_key_exists( $plugin['path'], \get_plugins() ) ) {
			if ( \is_plugin_active( $plugin['path'] ) ) {
				// Status text/status.
				$data['status_class'] = 'status-active';
				$data['status_text']  = \esc_html__( 'Active', 'wp-mail-smtp' );
				// Button text/status.
				$data['action_class'] = $data['status_class'] . ' button button-secondary disabled';
				$data['action_text']  = \esc_html__( 'Activated', 'wp-mail-smtp' );
				$data['plugin_src']   = \esc_attr( $plugin['path'] );
			} else {
				// Status text/status.
				$data['status_class'] = 'status-inactive';
				$data['status_text']  = \esc_html__( 'Inactive', 'wp-mail-smtp' );
				// Button text/status.
				$data['action_class'] = $data['status_class'] . ' button button-secondary';
				$data['action_text']  = \esc_html__( 'Activate', 'wp-mail-smtp' );
				$data['plugin_src']   = \esc_attr( $plugin['path'] );
			}
		} else {
			if ( ! $is_pro ) {
				// Doesn't exist, install.
				// Status text/status.
				$data['status_class'] = 'status-download';
				$data['status_text']  = \esc_html__( 'Not Installed', 'wp-mail-smtp' );
				// Button text/status.
				$data['action_class'] = $data['status_class'] . ' button button-primary';
				$data['action_text']  = \esc_html__( 'Install Plugin', 'wp-mail-smtp' );
				$data['plugin_src']   = \esc_url( $plugin['url'] );
			}
		}

		return $data;
	}

	/**
	 * List of AM plugins that we propose to install.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	private function get_am_plugins() {

		$data = array(
			'om'                            => array(
				'path' => 'optinmonster/optin-monster-wp-api.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-om.png',
				'name' => \esc_html__( 'OptinMonster', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
			),
			'wpforms'                       => array(
				'path' => 'wpforms-lite/wpforms.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-wpf.png',
				'name' => \esc_html__( 'WPForms', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 100+ form templates. Trusted by over 4 million websites as the best forms plugin.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/wpforms-lite.zip',
				'pro'  => array(
					'path' => 'wpforms/wpforms.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-wpf.png',
					'name' => \esc_html__( 'WPForms Pro', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 100+ form templates. Trusted by over 4 million websites as the best forms plugin.', 'wp-mail-smtp' ),
					'url'  => 'https://wpforms.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'mi'                            => array(
				'path' => 'google-analytics-for-wordpress/googleanalytics.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-mi.png',
				'name' => \esc_html__( 'MonsterInsights', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
				'pro'  => array(
					'path' => 'google-analytics-premium/googleanalytics-premium.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-mi.png',
					'name' => \esc_html__( 'MonsterInsights Pro', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'wp-mail-smtp' ),
					'url'  => 'https://www.monsterinsights.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'aioseo'                        => array(
				'path' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-aioseo.png',
				'name' => \esc_html__( 'AIOSEO', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'The original WordPress SEO plugin and toolkit that improves your website’s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip',
				'pro'  => array(
					'path' => 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-aioseo.png',
					'name' => \esc_html__( 'AIOSEO', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'The original WordPress SEO plugin and toolkit that improves your website’s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.', 'wp-mail-smtp' ),
					'url'  => 'https://aioseo.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'seedprod'                      => array(
				'path' => 'coming-soon/coming-soon.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-seedprod.png',
				'name' => \esc_html__( 'SeedProd', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/coming-soon.zip',
				'pro'  => array(
					'path' => 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-seedprod.png',
					'name' => \esc_html__( 'SeedProd', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'wp-mail-smtp' ),
					'url'  => 'https://www.seedprod.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'rafflepress'                   => array(
				'path' => 'rafflepress/rafflepress.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-rp.png',
				'name' => \esc_html__( 'RafflePress', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
				'pro'  => array(
					'path' => 'rafflepress-pro/rafflepress-pro.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-rp.png',
					'name' => \esc_html__( 'RafflePress Pro', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'wp-mail-smtp' ),
					'url'  => 'https://rafflepress.com/pricing/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'pushengage'                    => array(
				'path' => 'pushengage/main.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-pushengage.png',
				'name' => \esc_html__( 'PushEngage', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Connect with your visitors after they leave your website with the leading web push notification software. Over 10,000+ businesses worldwide use PushEngage to send 9 billion notifications each month.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/pushengage.zip',
			),
			'smash-balloon-instagram-feeds' => array(
				'path' => 'instagram-feed/instagram-feed.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-smash-balloon-instagram-feeds.png',
				'name' => \esc_html__( 'Smash Balloon Instagram Feeds', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
				'pro'  => array(
					'path' => 'instagram-feed-pro/instagram-feed.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/',
					'name' => \esc_html__( 'Smash Balloon Instagram Feeds', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'wp-mail-smtp' ),
					'url'  => 'https://smashballoon.com/instagram-feed/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'smash-balloon-facebook-feeds'  => array(
				'path' => 'custom-facebook-feed/custom-facebook-feed.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-smash-balloon-facebook-feeds.png',
				'name' => \esc_html__( 'Smash Balloon Facebook Feeds', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
				'pro'  => array(
					'path' => 'custom-facebook-feed-pro/custom-facebook-feed.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/',
					'name' => \esc_html__( 'Smash Balloon Facebook Feeds', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'wp-mail-smtp' ),
					'url'  => 'https://smashballoon.com/custom-facebook-feed/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'smash-balloon-youtube-feeds'   => array(
				'path' => 'feeds-for-youtube/youtube-feed.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-smash-balloon-youtube-feeds.png',
				'name' => \esc_html__( 'Smash Balloon YouTube Feeds', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
				'pro'  => array(
					'path' => 'youtube-feed-pro/youtube-feed.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/',
					'name' => \esc_html__( 'Smash Balloon YouTube Feeds', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'wp-mail-smtp' ),
					'url'  => 'https://smashballoon.com/youtube-feed/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'smash-balloon-twitter-feeds'   => array(
				'path' => 'custom-twitter-feeds/custom-twitter-feed.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-smash-balloon-twitter-feeds.png',
				'name' => \esc_html__( 'Smash Balloon Twitter Feeds', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
				'pro'  => array(
					'path' => 'custom-twitter-feeds-pro/custom-twitter-feed.php',
					'icon' => \wp_mail_smtp()->assets_url . '/images/about/',
					'name' => \esc_html__( 'Smash Balloon Twitter Feeds', 'wp-mail-smtp' ),
					'desc' => \esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'wp-mail-smtp' ),
					'url'  => 'https://smashballoon.com/custom-twitter-feeds/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp',
				),
			),
			'trustpulse'                    => array(
				'path' => 'trustpulse-api/trustpulse.php',
				'icon' => \wp_mail_smtp()->assets_url . '/images/about/plugin-trustpulse.png',
				'name' => \esc_html__( 'TrustPulse', 'wp-mail-smtp' ),
				'desc' => \esc_html__( 'Boost your sales and conversions by up to 15% with real-time social proof notifications. TrustPulse helps you show live user activity and purchases to help convince other users to purchase.', 'wp-mail-smtp' ),
				'url'  => 'https://downloads.wordpress.org/plugin/trustpulse-api.zip',
			),
		);

		return $data;
	}

	/**
	 * Active the given plugin.
	 *
	 * @since 1.5.0
	 */
	public static function ajax_plugin_activate() {

		// Run a security check.
		\check_ajax_referer( 'wp-mail-smtp-about', 'nonce' );

		$error = \esc_html__( 'Could not activate the plugin. Please activate it from the Plugins page.', 'wp-mail-smtp' );

		// Check for permissions.
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			\wp_send_json_error( $error );
		}

		if ( isset( $_POST['plugin'] ) ) {

			$activate = \activate_plugins( $_POST['plugin'] ); // phpcs:ignore

			if ( ! \is_wp_error( $activate ) ) {
				\wp_send_json_success( esc_html__( 'Plugin activated.', 'wp-mail-smtp' ) );
			}
		}

		\wp_send_json_error( $error );
	}

	/**
	 * Install & activate the given plugin.
	 *
	 * @since 1.5.0
	 */
	public static function ajax_plugin_install() {

		// Run a security check.
		\check_ajax_referer( 'wp-mail-smtp-about', 'nonce' );

		$error = \esc_html__( 'Could not install the plugin.', 'wp-mail-smtp' );

		// Check for permissions.
		if ( ! \current_user_can( 'install_plugins' ) ) {
			\wp_send_json_error( $error );
		}

		if ( empty( $_POST['plugin'] ) ) {
			\wp_send_json_error();
		}

		// Set the current screen to avoid undefined notices.
		\set_current_screen( 'wp-mail-smtp_page_wp-mail-smtp-about' );

		// Prepare variables.
		$url = \esc_url_raw(
			\add_query_arg(
				array(
					'page' => 'wp-mail-smtp-about',
				),
				\admin_url( 'admin.php' )
			)
		);

		$creds = \request_filesystem_credentials( $url, '', false, false, null );

		// Check for file system permissions.
		if ( false === $creds ) {
			\wp_send_json_error( $error );
		}

		if ( ! \WP_Filesystem( $creds ) ) {
			\wp_send_json_error( $error );
		}

		// Do not allow WordPress to search/download translations, as this will break JS output.
		\remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		// Create the plugin upgrader with our custom skin.
		$installer = new PluginsInstallUpgrader( new PluginsInstallSkin() );

		// Error check.
		if ( ! \method_exists( $installer, 'install' ) || empty( $_POST['plugin'] ) ) {
			\wp_send_json_error( $error );
		}

		$installer->install( $_POST['plugin'] ); // phpcs:ignore

		// Flush the cache and return the newly installed plugin basename.
		\wp_cache_flush();

		if ( $installer->plugin_info() ) {

			$plugin_basename = $installer->plugin_info();

			// Activate the plugin silently.
			$activated = \activate_plugin( $plugin_basename );

			if ( ! \is_wp_error( $activated ) ) {
				\wp_send_json_success(
					array(
						'msg'          => \esc_html__( 'Plugin installed & activated.', 'wp-mail-smtp' ),
						'is_activated' => true,
						'basename'     => $plugin_basename,
					)
				);
			} else {
				\wp_send_json_success(
					array(
						'msg'          => esc_html__( 'Plugin installed.', 'wp-mail-smtp' ),
						'is_activated' => false,
						'basename'     => $plugin_basename,
					)
				);
			}
		}

		\wp_send_json_error( $error );
	}

	/**
	 * Display a "Lite vs Pro" tab content.
	 *
	 * @since 1.5.0
	 */
	protected function display_versus() {

		$license = \wp_mail_smtp()->get_license_type();
		?>

		<div class="wp-mail-smtp-admin-about-section wp-mail-smtp-admin-about-section-squashed">
			<h1 class="centered">
				<strong>
					<?php
					\printf(
						/* translators: %s - plugin current license type. */
						\esc_html__( '%s vs Pro', 'wp-mail-smtp' ),
						\esc_html( \ucfirst( $license ) )
					);
					?>
				</strong>
			</h1>

			<p class="centered <?php echo ( $license === 'pro' ? 'hidden' : '' ); ?>">
				<?php esc_html_e( 'Get the most out of WP Mail SMTP by upgrading to Pro and unlocking all of the powerful features.', 'wp-mail-smtp' ); ?>
			</p>
		</div>

		<div class="wp-mail-smtp-admin-about-section wp-mail-smtp-admin-about-section-squashed wp-mail-smtp-admin-about-section-hero wp-mail-smtp-admin-about-section-table">

			<div class="wp-mail-smtp-admin-about-section-hero-main wp-mail-smtp-admin-columns">
				<div class="wp-mail-smtp-admin-column-33">
					<h3 class="no-margin">
						<?php esc_html_e( 'Feature', 'wp-mail-smtp' ); ?>
					</h3>
				</div>
				<div class="wp-mail-smtp-admin-column-33">
					<h3 class="no-margin">
						<?php echo esc_html( ucfirst( $license ) ); ?>
					</h3>
				</div>
				<div class="wp-mail-smtp-admin-column-33">
					<h3 class="no-margin">
						<?php esc_html_e( 'Pro', 'wp-mail-smtp' ); ?>
					</h3>
				</div>
			</div>
			<div class="wp-mail-smtp-admin-about-section-hero-extra no-padding wp-mail-smtp-admin-columns">

				<table>
					<?php
					foreach ( $this->get_license_features() as $slug => $name ) {
						$current = $this->get_license_data( $slug, $license );
						$pro     = $this->get_license_data( $slug, 'pro' );
						?>
						<tr class="wp-mail-smtp-admin-columns">
							<td class="wp-mail-smtp-admin-column-33">
								<p><?php echo $name; ?></p>
							</td>
							<td class="wp-mail-smtp-admin-column-33">
								<p class="features-<?php echo esc_attr( $current['status'] ); ?>">
									<?php echo \implode( '<br>', $current['text'] ); ?>
								</p>
							</td>
							<td class="wp-mail-smtp-admin-column-33">
								<p class="features-full">
									<?php echo \implode( '<br>', $pro['text'] ); ?>
								</p>
							</td>
						</tr>
						<?php
					}
					?>
				</table>

			</div>

		</div>

		<?php if ( 'lite' === $license ) : ?>
			<div class="wp-mail-smtp-admin-about-section wp-mail-smtp-admin-about-section-hero">
				<div class="wp-mail-smtp-admin-about-section-hero-main no-border">
					<h3 class="call-to-action centered">
						<a href="<?php echo esc_url( wp_mail_smtp()->get_upgrade_link( 'lite-vs-pro' ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php \esc_html_e( 'Get WP Mail SMTP Pro Today and Unlock all of these Powerful Features', 'wp-mail-smtp' ); ?>
						</a>
					</h3>

					<p class="centered">
						<?php
						printf(
							wp_kses( /* Translators: %s - discount value $50. */
								__( 'Bonus: WP Mail SMTP Lite users get <span class="price-off">%s off regular price</span>, automatically applied at checkout.', 'wp-mail-smtp' ),
								array(
									'span' => array(
										'class' => array(),
									),
								)
							),
							'$50'
						);
						?>
					</p>
				</div>
			</div>
		<?php endif; ?>

		<?php
	}

	/**
	 * Get the list of features for all licenses.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	private function get_license_features() {

		return [
			'log'       => \esc_html__( 'Email Log', 'wp-mail-smtp' ),
			'control'   => \esc_html__( 'Email Controls', 'wp-mail-smtp' ),
			'mailers'   => \esc_html__( 'Mailer Options', 'wp-mail-smtp' ),
			'multisite' => \esc_html__( 'WordPress Multisite', 'wp-mail-smtp' ),
			'support'   => \esc_html__( 'Customer Support', 'wp-mail-smtp' ),
		];
	}

	/**
	 * Get the array of data that compared the license data.
	 *
	 * @since 1.5.0
	 *
	 * @param string $feature Feature name.
	 * @param string $license License type to get data for.
	 *
	 * @return array|false
	 */
	private function get_license_data( $feature, $license ) {

		$data = [
			'log'       => [
				'lite' => [
					'status' => 'none',
					'text'   => [
						'<strong>' . esc_html__( 'Emails are not logged', 'wp-mail-smtp' ) . '</strong>',
					],
				],
				'pro'  => [
					'status' => 'full',
					'text'   => [
						'<strong>' . esc_html__( 'Access to all Email Logging options right inside WordPress', 'wp-mail-smtp' ) . '</strong>',
					],
				],
			],
			'control'   => [
				'lite' => [
					'status' => 'none',
					'text'   => [
						'<strong>' . esc_html__( 'No controls over whether default WordPress emails are sent', 'wp-mail-smtp' ) . '</strong>',
					],
				],
				'pro'  => [
					'status' => 'full',
					'text'   => [
						'<strong>' . esc_html__( 'Complete Email Controls management for most default WordPress emails', 'wp-mail-smtp' ) . '</strong>',
					],
				],
			],
			'mailers'   => [
				'lite' => [
					'status' => 'none',
					'text'   => [
						'<strong>' . esc_html__( 'Limited Mailers', 'wp-mail-smtp' ) . '</strong><br>' . esc_html__( 'Access is limited to standard mailer options only', 'wp-mail-smtp' ),
					],
				],
				'pro'  => [
					'status' => 'full',
					'text'   => [
						'<strong>' . esc_html__( 'Additional Mailer Options', 'wp-mail-smtp' ) . '</strong><br>' . esc_html__( 'Microsoft Outlook (with Office365 support), Amazon SES and Zoho Mail', 'wp-mail-smtp' ),
					],
				],
			],
			'multisite' => [
				'lite' => [
					'status' => 'none',
					'text'   => [
						'<strong>' . esc_html__( 'No Global Network Settings', 'wp-mail-smtp' ) . '</strong>',
					],
				],
				'pro'  => [
					'status' => 'full',
					'text'   => [
						'<strong>' . esc_html__( 'All Global Network Settings', 'wp-mail-smtp' ) . '</strong><br>' . esc_html__( 'Optionally configure settings at the network level or manage separately for each subsite', 'wp-mail-smtp' ),
					],
				],
			],
			'support'   => [
				'lite' => [
					'status' => 'none',
					'text'   => [
						'<strong>' . esc_html__( 'Limited Support', 'wp-mail-smtp' ) . '</strong>',
					],
				],
				'pro'  => [
					'status' => 'full',
					'text'   => [
						'<strong>' . esc_html__( 'Priority Support', 'wp-mail-smtp' ) . '</strong>',
					],
				],
			],
		];

		// Wrong feature?
		if ( ! isset( $data[ $feature ] ) ) {
			return false;
		}

		// Wrong license type?
		if ( ! isset( $data[ $feature ][ $license ] ) ) {
			return false;
		}

		return $data[ $feature ][ $license ];
	}
}
