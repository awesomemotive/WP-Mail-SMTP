<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Integrations\WPCode\RegisterLibrary;
use WPMailSMTP\Integrations\WPCode\SnippetsProvider;
use WPMailSMTP\WP;

/**
 * Code Snippets tab: surfaces the plugin's WPCode library snippets.
 *
 * @since 4.9.0
 */
class CodeSnippetsTab extends PageAbstract {

	/**
	 * Tab slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $slug = 'code-snippets';

	/**
	 * Tab display priority.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	protected $priority = 50;

	/**
	 * Whether to display the placeholder fallback view (no live data, no cache).
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $display_fallback = false;

	/**
	 * Link label of a tab.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Code Snippets', 'wp-mail-smtp' );
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
	 * Register tab AJAX handlers. Runs on every admin AJAX request.
	 *
	 * @since 4.9.0
	 */
	public function ajax() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks -- ajax() is the framework's AJAX-registration method; hooks() never runs during an AJAX request.

		add_action( 'wp_ajax_wp_mail_smtp_get_wpcode_snippet_code', [ $this, 'ajax_snippet_code' ] );
		add_action( 'wp_ajax_wp_mail_smtp_get_wpcode_snippet_install_url', [ $this, 'ajax_snippet_install_url' ] );
	}

	/**
	 * Enqueue the tab's scripts and localized strings.
	 *
	 * @since 4.9.0
	 */
	public function enqueue_assets() {

		wp_enqueue_script(
			'wp-mail-smtp-listjs',
			wp_mail_smtp()->assets_url . '/js/vendor/list.min.js',
			[ 'jquery' ],
			'1.5.0',
			true
		);

		// WP core CodeMirror for the read-only syntax-highlighted snippet preview.
		$code_editor_settings = wp_enqueue_code_editor( [ 'type' => 'text/x-php' ] );

		wp_enqueue_script(
			'wp-mail-smtp-code-snippets',
			wp_mail_smtp()->assets_url . '/js/smtp-code-snippets' . WP::asset_min() . '.js',
			[ 'jquery', 'wp-mail-smtp-listjs', 'wp-mail-smtp-admin', 'wp-mail-smtp-admin-jconfirm', 'code-editor' ],
			WPMS_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'wp-mail-smtp-code-snippets',
			'wp_mail_smtp_code_snippets',
			[
				'wpcode_active'        => function_exists( 'wpcode_get_library_snippets_by_username' ),
				'wpcode_path'          => $this->get_wpcode_plugin_path(),
				'activate_url'         => $this->get_wpcode_activate_url(),
				'code_editor'          => $code_editor_settings,
				'install_text'         => esc_html__( 'Install Snippet', 'wp-mail-smtp' ),
				'installing_text'      => esc_html__( 'Installing…', 'wp-mail-smtp' ),
				'loading_text'         => esc_html__( 'Loading snippet…', 'wp-mail-smtp' ),
				'error_text'           => esc_html__( 'Could not load this snippet. Please try again.', 'wp-mail-smtp' ),
				'install_popup_title'  => esc_html__( 'Please Install WPCode to Use the Snippet Library', 'wp-mail-smtp' ),
				'install_popup_desc'   => esc_html__( 'Using WPCode, you can install WP Mail SMTP code snippets with 1 click right from this page.', 'wp-mail-smtp' ),
				'install_popup_btn'    => esc_html__( 'Install & Activate WPCode', 'wp-mail-smtp' ),
				'learn_more_text'      => esc_html__( 'Learn more about WPCode', 'wp-mail-smtp' ),
				'learn_more_url'       => 'https://wordpress.org/plugins/insert-headers-and-footers/',
				'install_popup_icon'   => esc_url( wp_mail_smtp()->assets_url . '/images/recommendations/plugins/wpcode/logo.svg' ),
				'activate_popup_title' => esc_html__( 'Please Activate WPCode to Use the Snippet Library', 'wp-mail-smtp' ),
				'activate_popup_desc'  => esc_html__( 'WPCode is installed but not active. Activate it to install WP Mail SMTP code snippets with 1 click right from this page.', 'wp-mail-smtp' ),
				'activate_popup_btn'   => esc_html__( 'Activate WPCode', 'wp-mail-smtp' ),
				'install_error_text'   => esc_html__( 'WPCode could not be installed. Please try again.', 'wp-mail-smtp' ),
				'activate_error_text'  => esc_html__( 'WPCode was installed but could not be activated.', 'wp-mail-smtp' ),
				'activate_manual_link' => esc_html__( 'Activate it manually.', 'wp-mail-smtp' ),
				'lite_activate_url'    => $this->get_lite_activate_url(),
			]
		);
	}

	/**
	 * Whether WPCode is active (its library helper is available).
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_wpcode_active() {

		return function_exists( 'wpcode_get_library_snippets_by_username' );
	}

	/**
	 * Path of the installed WPCode plugin (pro preferred), or empty if none is installed.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function get_wpcode_plugin_path() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();

		foreach ( [ 'wpcode-premium/wpcode.php', 'insert-headers-and-footers/ihaf.php' ] as $path ) {
			if ( isset( $installed[ $path ] ) ) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * WordPress activation URL for the installed WPCode plugin, or empty if none is installed.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function get_wpcode_activate_url() {

		$path = $this->get_wpcode_plugin_path();

		return $path === '' ? '' : $this->build_activate_url( $path );
	}

	/**
	 * WordPress activation URL for WPCode Lite by its known basename.
	 *
	 * Built regardless of install state (nonces don't require the plugin to
	 * exist), so the install popup can offer a manual-activation link when an
	 * install succeeds but silent activation fails.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function get_lite_activate_url() {

		return $this->build_activate_url( 'insert-headers-and-footers/ihaf.php' );
	}

	/**
	 * Build a WordPress plugin-activation URL for a plugin basename.
	 *
	 * Uses esc_url_raw (not wp_nonce_url, which HTML-encodes &) so the URL stays
	 * valid when assigned to window.location.href on the client.
	 *
	 * @since 4.9.0
	 *
	 * @param string $path Plugin basename.
	 *
	 * @return string
	 */
	private function build_activate_url( $path ) {

		// In the multisite network admin, plugins are activated from the network
		// plugins screen; the nonce action is the same on both.
		$plugins_url = is_network_admin()
			? network_admin_url( 'plugins.php' )
			: admin_url( 'plugins.php' );

		$url = add_query_arg(
			[
				'action'   => 'activate',
				'plugin'   => $path,
				'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $path ),
			],
			$plugins_url
		);

		return esc_url_raw( $url );
	}

	/**
	 * Resolve the snippets to display.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function get_snippets() {

		$provider               = new SnippetsProvider( ( new RegisterLibrary() )->get_username() );
		$snippets               = $provider->get_snippets();
		$this->display_fallback = $provider->is_fallback();

		return $snippets;
	}

	/**
	 * Output HTML of the tab.
	 *
	 * @since 4.9.0
	 */
	public function display() {

		$snippets          = $this->get_snippets();
		$wpcode_active     = $this->is_wpcode_active();
		$display_fallback  = $this->display_fallback;
		$wpcode_url        = 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip';
		$container_class   = $display_fallback ? 'wp-mail-smtp-wpcode-blur' : '';
		$wpcode_path       = $this->get_wpcode_plugin_path();
		$activate_required = ! $wpcode_active && $wpcode_path !== '';
		?>
		<div class="wp-mail-smtp-wpcode">
			<div class="wp-mail-smtp-wpcode-container <?php echo sanitize_html_class( $container_class ); ?>">
				<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content section-heading wp-mail-smtp-section-heading--has-divider wp-mail-smtp-wpcode-header">
					<div class="wp-mail-smtp-setting-field">
						<h2><?php esc_html_e( 'Code Snippets', 'wp-mail-smtp' ); ?></h2>
						<p>
							<?php
							printf(
								wp_kses( /* translators: %s - WPCode library website URL. */
									__( 'Using WPCode, you can install WP Mail SMTP code snippets with 1 click directly from this page or the <a href="%s" target="_blank" rel="noopener noreferrer">WPCode library</a>.', 'wp-mail-smtp' ),
									[
										'a' => [
											'href'   => [],
											'rel'    => [],
											'target' => [],
										],
									]
								),
								esc_url( admin_url( 'admin.php?page=wpcode-library' ) )
							);
							?>
						</p>
					</div>
					<div class="wp-mail-smtp-wpcode-header-search">
						<span aria-hidden="true" class="wp-mail-smtp-wpcode-search-icon wpms:icon-[fa6-solid--magnifying-glass]"></span>
						<input type="search" id="wp-mail-smtp-wpcode-snippet-search"
							placeholder="<?php esc_attr_e( 'Search Snippets', 'wp-mail-smtp' ); ?>">
					</div>
				</div>

				<div id="wp-mail-smtp-wpcode-snippets-list">
					<div class="list">
						<?php foreach ( $snippets as $snippet ) : ?>
							<?php $this->render_snippet_card( $snippet, $wpcode_active, $wpcode_url, $activate_required, $wpcode_path ); ?>
						<?php endforeach; ?>
					</div>
					<div id="wp-mail-smtp-wpcode-no-results">
						<?php esc_html_e( "Sorry, we didn't find any snippets that match your criteria.", 'wp-mail-smtp' ); ?>
					</div>
				</div>
			</div>

			<?php if ( $display_fallback ) : ?>
				<div class="wp-mail-smtp-wpcode-fallback">
					<img class="wp-mail-smtp-wpcode-fallback-icons" src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/recommendations/plugins/wpcode/logo.svg' ); ?>" alt="">
					<div class="wp-mail-smtp-wpcode-fallback-title">
						<?php esc_html_e( 'Please Install WPCode to Use the Snippet Library', 'wp-mail-smtp' ); ?>
					</div>
					<div class="wp-mail-smtp-wpcode-fallback-description">
						<?php esc_html_e( 'Using WPCode, you can install WP Mail SMTP code snippets with 1 click right from this page.', 'wp-mail-smtp' ); ?>
					</div>
					<button class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-wpcode-fallback-install-plugin"
						data-action="install" data-plugin="<?php echo esc_url( $wpcode_url ); ?>">
						<?php esc_html_e( 'Install & Activate WPCode', 'wp-mail-smtp' ); ?>
					</button>
					<a href="https://wordpress.org/plugins/insert-headers-and-footers/" class="wp-mail-smtp-wpcode-fallback-link"><?php esc_html_e( 'Learn more about WPCode', 'wp-mail-smtp' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Verify the AJAX nonce and capability; send a JSON error and exit on failure.
	 *
	 * @since 4.9.0
	 */
	private function verify_ajax() {

		if (
			! check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) ||
			! current_user_can( wp_mail_smtp()->get_capability_manage_options() )
		) {
			wp_send_json_error();
		}
	}

	/**
	 * AJAX: return a single snippet's code for the preview modal.
	 *
	 * Resolves by library_id from the active-WPCode helper when available,
	 * otherwise from the inactive-state fetcher. Code is returned raw (JSON
	 * transport); the client renders it in a text context (CodeMirror), never
	 * as HTML.
	 *
	 * @since 4.9.0
	 */
	public function ajax_snippet_code() {

		$this->verify_ajax();

		// Nonce verified in verify_ajax().
		$library_id = isset( $_POST['library_id'] ) ? absint( wp_unslash( $_POST['library_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $library_id ) ) {
			wp_send_json_error();
		}

		$snippet = ( new SnippetsProvider( ( new RegisterLibrary() )->get_username() ) )->get_snippet( $library_id );

		if ( empty( $snippet ) || empty( $snippet['code'] ) ) {
			wp_send_json_error();
		}

		// The provider already normalized + sanitized these fields.
		wp_send_json_success(
			[
				'title'     => $snippet['title'],
				'note'      => $snippet['note'],
				'code'      => $snippet['code'],
				'code_type' => $snippet['code_type'],
			]
		);
	}

	/**
	 * AJAX: return a snippet's WPCode install URL.
	 *
	 * Used after WPCode is activated for an installed-but-inactive plugin: with
	 * WPCode now loaded, the active-state resolver returns the snippet's install
	 * URL, which the client navigates to.
	 *
	 * @since 4.9.0
	 */
	public function ajax_snippet_install_url() {

		$this->verify_ajax();

		// Nonce verified in verify_ajax().
		$library_id = isset( $_POST['library_id'] ) ? absint( wp_unslash( $_POST['library_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $library_id ) ) {
			wp_send_json_error();
		}

		$snippet = ( new SnippetsProvider( ( new RegisterLibrary() )->get_username() ) )->get_snippet( $library_id );

		if ( empty( $snippet ) || empty( $snippet['install'] ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( [ 'install_url' => $snippet['install'] ] );
	}

	/**
	 * Output the markup for a single snippet card.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $snippet           Snippet view-model.
	 * @param bool   $wpcode_active     Whether WPCode is active.
	 * @param string $wpcode_url        WPCode plugin download URL.
	 * @param bool   $activate_required Whether WPCode is installed but inactive.
	 * @param string $wpcode_path       Installed WPCode plugin path (empty if not installed).
	 */
	private function render_snippet_card( array $snippet, $wpcode_active, $wpcode_url, $activate_required = false, $wpcode_path = '' ) {

		$installed = ! empty( $snippet['installed'] );
		?>
		<div class="wp-mail-smtp-wpcode-snippet">
			<div class="wp-mail-smtp-wpcode-snippet-header">
				<h3 class="wp-mail-smtp-wpcode-snippet-title"><?php echo esc_html( $snippet['title'] ); ?></h3>
				<div class="wp-mail-smtp-wpcode-snippet-note"><?php echo esc_html( $snippet['note'] ); ?></div>
			</div>
			<div class="wp-mail-smtp-wpcode-snippet-footer">
				<?php if ( $installed ) : ?>
					<a href="<?php echo esc_url( $snippet['install'] ); ?>"
						class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-grey wp-mail-smtp-wpcode-install-snippet"
						data-action="edit">
						<?php esc_html_e( 'Edit Snippet', 'wp-mail-smtp' ); ?>
					</a>
				<?php else : ?>
					<?php if ( $wpcode_active ) : ?>
						<a href="<?php echo esc_url( $snippet['install'] ); ?>"
							class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-secondary wp-mail-smtp-wpcode-install-snippet"
							data-action="install">
							<?php esc_html_e( 'Install Snippet', 'wp-mail-smtp' ); ?>
						</a>
					<?php elseif ( $activate_required ) : ?>
						<a href="#"
							class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-secondary wp-mail-smtp-wpcode-activate-plugin"
							data-library-id="<?php echo absint( $snippet['library_id'] ); ?>"
							data-plugin="<?php echo esc_attr( $wpcode_path ); ?>">
							<?php esc_html_e( 'Install Snippet', 'wp-mail-smtp' ); ?>
						</a>
					<?php else : ?>
						<a href="#"
							class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-secondary wp-mail-smtp-wpcode-install-plugin"
							data-library-id="<?php echo absint( $snippet['library_id'] ); ?>"
							data-plugin="<?php echo esc_url( $wpcode_url ); ?>">
							<?php esc_html_e( 'Install Snippet', 'wp-mail-smtp' ); ?>
						</a>
					<?php endif; ?>
					<a href="#"
						class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-grey wp-mail-smtp-wpcode-view-snippet"
						data-library-id="<?php echo absint( $snippet['library_id'] ); ?>"
						data-install-url="<?php echo $wpcode_active ? esc_url( $snippet['install'] ) : ''; ?>"
						data-plugin="<?php echo esc_url( $wpcode_url ); ?>">
						<?php esc_html_e( 'View', 'wp-mail-smtp' ); ?>
					</a>
				<?php endif; ?>
				<span class="wp-mail-smtp-wpcode-snippet-badge"><?php echo $installed ? esc_html__( 'Installed', 'wp-mail-smtp' ) : ''; ?></span>
			</div>
		</div>
		<?php
	}
}
