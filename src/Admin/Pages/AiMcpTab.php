<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\WP;

/**
 * AI MCP tab: 1-click installs the Vibe AI MCP plugin and surfaces the read-only Abilities API.
 *
 * @since 4.9.0
 */
class AiMcpTab extends PageAbstract {

	/**
	 * WPVibe plugin basename on wp.org.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const WPVIBE_BASENAME = 'vibe-ai/vibe-ai.php';

	/**
	 * WPVibe wp.org download URL.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const WPVIBE_DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/vibe-ai.zip';

	/**
	 * WPVibe top-level admin page slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const WPVIBE_PAGE_SLUG = 'vibe-ai';

	/**
	 * Tab slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $slug = 'ai-mcp';

	/**
	 * Tab display priority.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	protected $priority = 60;

	/**
	 * Link label of a tab.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'AI MCP', 'wp-mail-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Register tab hooks. Runs only when this tab is the current one.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		add_action( 'wp_mail_smtp_admin_area_enqueue_assets', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue the tab's install/activate script and localized strings.
	 *
	 * @since 4.9.0
	 */
	public function enqueue_assets() {

		wp_enqueue_script(
			'wp-mail-smtp-ai-mcp',
			wp_mail_smtp()->assets_url . '/js/smtp-ai-mcp' . WP::asset_min() . '.js',
			[ 'jquery', 'wp-mail-smtp-admin' ],
			WPMS_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'wp-mail-smtp-ai-mcp',
			'wp_mail_smtp_ai_mcp',
			[
				'error_text' => esc_html__( 'Something went wrong. Please try again.', 'wp-mail-smtp' ),
			]
		);
	}

	/**
	 * Resolve the WPVibe install state: not installed, installed but inactive, or active.
	 *
	 * @since 4.9.0
	 *
	 * @return string One of 'not_installed', 'installed_inactive', 'active'.
	 */
	private function get_wpvibe_state() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		if ( ! array_key_exists( self::WPVIBE_BASENAME, $plugins ) ) {
			return 'not_installed';
		}

		if ( ! is_plugin_active( self::WPVIBE_BASENAME ) ) {
			return 'installed_inactive';
		}

		return 'active';
	}

	/**
	 * Output the state-dependent WPVibe CTA button.
	 *
	 * @since 4.9.0
	 *
	 * @param string $state        WPVibe state.
	 * @param bool   $can_install  Whether the user can install plugins.
	 * @param bool   $can_activate Whether the user can activate plugins.
	 * @param string $setup_url    WPVibe admin page URL for the active state.
	 */
	private function render_cta_button( $state, $can_install, $can_activate, $setup_url ) {

		if ( $state === 'active' ) {
			?>
			<a
				class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-blueish wp-mail-smtp-ai-mcp-wpvibe-button"
				href="<?php echo esc_url( $setup_url ); ?>"
			><?php esc_html_e( 'Set Up WPVibe', 'wp-mail-smtp' ); ?></a>
			<?php

			return;
		}

		if ( $state === 'installed_inactive' && $can_activate ) {
			?>
			<button
				type="button"
				class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-ai-mcp-wpvibe-button"
				data-action="activate"
				data-plugin="<?php echo esc_attr( self::WPVIBE_BASENAME ); ?>"
			><?php esc_html_e( 'Activate WPVibe', 'wp-mail-smtp' ); ?></button>
			<?php

			return;
		}

		if ( $state === 'not_installed' && $can_install ) {
			?>
			<button
				type="button"
				class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-ai-mcp-wpvibe-button"
				data-action="install"
				data-plugin="<?php echo esc_attr( self::WPVIBE_DOWNLOAD_URL ); ?>"
			><?php esc_html_e( 'Install & Activate WPVibe', 'wp-mail-smtp' ); ?></button>
			<?php

			return;
		}

		if ( $state === 'not_installed' ) {
			?>
			<a
				href="https://wordpress.org/plugins/vibe-ai/"
				class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-ai-mcp-wpvibe-button"
				target="_blank"
				rel="noopener noreferrer"
			><?php esc_html_e( 'Install from WordPress.org', 'wp-mail-smtp' ); ?></a>
			<?php
		}
	}

	/**
	 * Output HTML of the tab.
	 *
	 * @since 4.9.0
	 */
	public function display() {

		$state         = $this->get_wpvibe_state();
		$is_pro        = wp_mail_smtp()->is_pro();
		$can_install   = current_user_can( 'install_plugins' );
		$can_activate  = current_user_can( 'activate_plugins' );
		$wpvibe_setup  = admin_url( 'admin.php?page=' . self::WPVIBE_PAGE_SLUG );
		$pro_badge_url = wp_mail_smtp()->assets_url . '/images/pro-badge-small.svg';

		$docs_url = wp_mail_smtp()->get_utm_url(
			'https://wpmailsmtp.com/docs/using-wpmailsmtp-with-ai-assistants/',
			[
				'medium'  => 'ai-mcp',
				'content' => 'View Abilities API Documentation',
			]
		);

		// Icon classes are literal so the Tailwind scanner can generate the Iconify utilities.
		$cards = [
			[
				'icon'    => 'wpms:icon-[fa6-solid--rectangle-list]',
				'title'   => esc_html__( 'Email Logs', 'wp-mail-smtp' ),
				'bullets' => [
					esc_html__( 'Browse and filter logged emails by status, mailer, date, and recipient', 'wp-mail-smtp' ),
					esc_html__( "Get a single email's full details and content", 'wp-mail-smtp' ),
				],
				'pro'     => true,
			],
			[
				'icon'    => 'wpms:icon-[fa6-solid--chart-column]',
				'title'   => esc_html__( 'Email Stats', 'wp-mail-smtp' ),
				'bullets' => [
					esc_html__( 'See aggregate sending stats for any period or date range', 'wp-mail-smtp' ),
					esc_html__( 'Scope stats to a single mailer and check the success rate', 'wp-mail-smtp' ),
				],
				'pro'     => true,
			],
			[
				'icon'    => 'wpms:icon-[fa6-solid--bug]',
				'title'   => esc_html__( 'Debug Events', 'wp-mail-smtp' ),
				'bullets' => [
					esc_html__( 'List recorded send errors and debug entries', 'wp-mail-smtp' ),
				],
				'pro'     => false,
			],
		];

		?>
		<div class="wp-mail-smtp-ai-mcp">

			<section class="wp-mail-smtp-ai-mcp-hero">

				<div class="wp-mail-smtp-ai-mcp-hero-copy">

					<p class="wp-mail-smtp-ai-mcp-eyebrow"><?php esc_html_e( 'WordPress Abilities API + WP Mail SMTP', 'wp-mail-smtp' ); ?></p>
					<h2 class="wp-mail-smtp-ai-mcp-title"><?php esc_html_e( 'Use WP Mail SMTP With Your Favorite AI', 'wp-mail-smtp' ); ?></h2>
					<p class="wp-mail-smtp-ai-mcp-lede">
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s - WPVibe.ai inline link. */
								__( 'Connect your WordPress site and WP Mail SMTP to AI assistants like Claude, ChatGPT, Cursor, and more. Ask them to review your email logs, sending stats, and debug events in plain English. No copy-pasting, no exports. Connect them with the free %s plugin.', 'wp-mail-smtp' ),
								sprintf(
									'<a href="%s" target="_blank" rel="noopener noreferrer"><strong>WPVibe.ai</strong></a>',
									esc_url( 'https://wpvibe.ai/?utm_source=wpmailsmtpplugin&utm_medium=link&utm_campaign=ai-mcp-page' )
								)
							),
							[
								'a'      => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
								'strong' => [],
							]
						);
						?>
					</p>

					<div class="wp-mail-smtp-ai-mcp-cta-row">
						<?php $this->render_cta_button( $state, $can_install, $can_activate, $wpvibe_setup ); ?>
					</div>

					<?php if ( $state === 'not_installed' && ! $can_install ) : ?>
						<p class="wp-mail-smtp-ai-mcp-install-note">
							<?php esc_html_e( 'Your site is configured to disallow plugin installation from the dashboard.', 'wp-mail-smtp' ); ?>
						</p>
					<?php endif; ?>

				</div>

				<img
					class="wp-mail-smtp-ai-mcp-hero-illustration"
					src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/ai-mcp/hero-illustration.svg' ); ?>"
					alt=""
					role="presentation"
				>

			</section>

			<section class="wp-mail-smtp-ai-mcp-capabilities">

				<header class="wp-mail-smtp-ai-mcp-capabilities-head">
					<h3 class="wp-mail-smtp-ai-mcp-capabilities-title"><?php esc_html_e( 'Everything WP Mail SMTP Can Do With AI', 'wp-mail-smtp' ); ?></h3>
					<a
						class="wp-mail-smtp-ai-mcp-docs-link"
						href="<?php echo esc_url( $docs_url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<span class="wp-mail-smtp-ai-mcp-docs-text"><?php esc_html_e( 'View Abilities API Documentation', 'wp-mail-smtp' ); ?></span>
						<span class="wp-mail-smtp-ai-mcp-docs-arrow wpms:icon-[fa6-solid--arrow-right]" aria-hidden="true"></span>
					</a>
				</header>

				<div class="wp-mail-smtp-ai-mcp-cards">
					<?php foreach ( $cards as $card ) : ?>
						<article class="wp-mail-smtp-ai-mcp-card">

							<header class="wp-mail-smtp-ai-mcp-card-head">
								<span class="wp-mail-smtp-ai-mcp-card-icon" aria-hidden="true">
									<span class="<?php echo esc_attr( $card['icon'] ); ?>"></span>
								</span>
								<h4 class="wp-mail-smtp-ai-mcp-card-title"><?php echo esc_html( $card['title'] ); ?></h4>
							</header>

							<ul class="wp-mail-smtp-ai-mcp-card-bullets">
								<?php foreach ( $card['bullets'] as $bullet ) : ?>
									<li>
										<span class="wp-mail-smtp-ai-mcp-card-dot" aria-hidden="true"></span>
										<span class="wp-mail-smtp-ai-mcp-card-bullet-text"><?php echo esc_html( $bullet ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>

							<?php if ( $card['pro'] && ! $is_pro ) : ?>
								<img
									class="wp-mail-smtp-ai-mcp-pro-badge"
									src="<?php echo esc_url( $pro_badge_url ); ?>"
									alt="<?php esc_attr_e( 'Pro feature', 'wp-mail-smtp' ); ?>"
								>
							<?php endif; ?>

						</article>
					<?php endforeach; ?>
				</div>

			</section>

		</div>
		<?php
	}
}
