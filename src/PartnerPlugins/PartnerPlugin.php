<?php

namespace WPMailSMTP\PartnerPlugins;

use WPMailSMTP\WP;

/**
 * A partner plugin we recommend, install, or detect.
 *
 * @since 4.10.0
 */
abstract class PartnerPlugin {

	/**
	 * Catalog slug, unique across the catalog.
	 *
	 * @since 4.10.0
	 */
	const SLUG = '';

	/**
	 * Product name, as the partner brands it. Not translated.
	 *
	 * @since 4.10.0
	 */
	const NAME = '';

	/**
	 * Product name of the premium version. Defaults to NAME.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = '';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * Empty when there is no free version.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = '';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = '';

	/**
	 * Where the free version is downloaded from. A `.zip` is installable in
	 * place; any other URL can only be linked to.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = '';

	/**
	 * Where the premium version is bought. A landing page, never a download.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = '';

	/**
	 * Main files of plugins that solve the same problem this one does.
	 *
	 * @since 4.10.0
	 */
	const COMPETITORS = [];

	/**
	 * Install state: the plugin's files are not present.
	 *
	 * @since 4.10.0
	 */
	const STATE_NOT_INSTALLED = 'not_installed';

	/**
	 * Install state: the files are present but the plugin is switched off.
	 *
	 * @since 4.10.0
	 */
	const STATE_INACTIVE = 'installed_inactive';

	/**
	 * Install state: the plugin is switched on.
	 *
	 * @since 4.10.0
	 */
	const STATE_ACTIVE = 'active';

	/**
	 * The site this instance describes.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	protected $site_id;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param int $site_id Site this instance describes, or 0 for the one the current
	 *                     admin screen administers.
	 */
	public function __construct( int $site_id = 0 ) {

		if ( $site_id === 0 ) {
			// The network admin administers the main site, whichever site its statistics show.
			$site_id = WP::is_network_admin_scope() ? get_main_site_id() : get_current_blog_id();
		}

		$this->site_id = $site_id;
	}

	/**
	 * The site this instance describes.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	public function get_site_id(): int {

		return $this->site_id;
	}

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_slug(): string {

		return static::SLUG;
	}

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_name(): string {

		return static::NAME;
	}

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_name_pro(): string {

		return static::NAME_PRO !== '' ? static::NAME_PRO : static::NAME;
	}

	/**
	 * Free version main file.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_basename(): string {

		return static::BASENAME;
	}

	/**
	 * Premium version main file.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_basename_pro(): string {

		return static::BASENAME_PRO;
	}

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_download_url(): string {

		return static::DOWNLOAD_URL;
	}

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_upgrade_url(): string {

		return static::UPGRADE_URL;
	}

	/**
	 * WordPress.org directory slug, or an empty string when the free version
	 * is not hosted there.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_wporg_slug(): string {

		if ( ! $this->is_installable() ) {
			return '';
		}

		return basename( wp_parse_url( static::DOWNLOAD_URL, PHP_URL_PATH ), '.zip' );
	}

	/**
	 * WordPress.org plugin page for the free version, or an empty string when
	 * the free version is not hosted there.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_wporg_url(): string {

		$slug = $this->get_wporg_slug();

		return $slug === '' ? '' : 'https://wordpress.org/plugins/' . $slug . '/';
	}

	/**
	 * Main files of plugins that solve the same problem this one does.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_competitors(): array {

		return static::COMPETITORS;
	}

	/**
	 * Whether this plugin can be installed in place, as opposed to only
	 * linked to.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_installable(): bool {

		return (bool) preg_match( '#\.zip$#i', static::DOWNLOAD_URL );
	}

	/**
	 * Whether the free version's files are present.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_installed(): bool {

		return static::BASENAME !== '' && array_key_exists( static::BASENAME, self::get_installed_plugins() );
	}

	/**
	 * Whether the premium version's files are present.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_pro_installed(): bool {

		return static::BASENAME_PRO !== '' && array_key_exists( static::BASENAME_PRO, self::get_installed_plugins() );
	}

	/**
	 * Whether either version is active.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_active(): bool {

		return $this->is_basename_active( static::BASENAME ) ||
			$this->is_basename_active( static::BASENAME_PRO );
	}

	/**
	 * Whether the plugin's premium features are available.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_pro_active(): bool {

		return $this->is_basename_active( static::BASENAME_PRO );
	}

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return $this->is_active();
	}

	/**
	 * Whether the plugin is active and set up enough to be doing its job.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_configured(): bool {

		return $this->is_loaded();
	}

	/**
	 * Main file of whichever version is installed, premium preferred.
	 *
	 * Empty when neither version is present, which is the state the
	 * code-snippets tab distinguishes.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_installed_basename(): string {

		if ( $this->is_pro_installed() ) {
			return static::BASENAME_PRO;
		}

		return $this->is_installed() ? static::BASENAME : '';
	}

	/**
	 * How far along the install ladder this plugin is.
	 *
	 * @since 4.10.0
	 *
	 * @return string One of the STATE_* constants.
	 */
	public function get_install_state(): string {

		if ( ! $this->is_installed() && ! $this->is_pro_installed() ) {
			return self::STATE_NOT_INSTALLED;
		}

		return $this->is_active() ? self::STATE_ACTIVE : self::STATE_INACTIVE;
	}

	/**
	 * Whether the current user may carry out the step this plugin's state calls for.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function current_user_can_install_or_activate(): bool {

		if ( $this->get_install_state() === self::STATE_NOT_INSTALLED ) {
			return current_user_can( 'install_plugins' );
		}

		return current_user_can( 'activate_plugins' );
	}

	/**
	 * The one-click install CTA for this plugin, by its state.
	 *
	 * @since 4.10.0
	 *
	 * @return array Link parts: `text`, `url`, `action`, `plugin`.
	 */
	public function get_install_cta(): array {

		$state = $this->get_install_state();

		if ( $state === self::STATE_ACTIVE ) {
			return [
				'text'   => __( 'Installed', 'wp-mail-smtp' ),
				'url'    => '#',
				'action' => 'active',
				'plugin' => '',
			];
		}

		$is_installed = $state === self::STATE_INACTIVE;

		return [
			'text'   => $is_installed ? __( 'Activate', 'wp-mail-smtp' ) : __( 'Install', 'wp-mail-smtp' ),
			'url'    => '#',
			'action' => $is_installed ? 'activate-plugin' : 'install-plugin',
			'plugin' => $this->get_basename(),
		];
	}

	/**
	 * The plugin's own onboarding or settings screen, or an empty string when
	 * it has none worth linking to.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return '';
	}

	/**
	 * Run just before this plugin is activated, where a plugin that redirects
	 * to its own welcome screen pre-empts it.
	 *
	 * @since 4.10.0
	 */
	public function before_activation(): void {}

	/**
	 * Run just after this plugin is activated.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {}

	/**
	 * Whether a plugin covering the same need as this one is installed.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_competitor_installed(): bool {

		$installed = self::get_installed_plugins();

		foreach ( static::COMPETITORS as $competitor ) {
			if ( array_key_exists( $competitor, $installed ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a plugin main file is active, guarding against the empty
	 * basename of a plugin with no such version.
	 *
	 * @since 4.10.0
	 *
	 * @param string $basename Plugin main file, relative to the plugins directory.
	 *
	 * @return bool
	 */
	private function is_basename_active( string $basename ): bool {

		if ( $basename === '' ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( $this->is_this_site() ) {
			return is_plugin_active( $basename );
		}

		return in_array( $basename, (array) get_blog_option( $this->site_id, 'active_plugins', [] ), true ) ||
			is_plugin_active_for_network( $basename );
	}

	/**
	 * Whether this instance describes the site serving the request.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	private function is_this_site(): bool {

		return ! is_multisite() || $this->site_id === get_current_blog_id();
	}

	/**
	 * An option of the site this instance describes.
	 *
	 * @since 4.10.0
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Value when the option is not set.
	 *
	 * @return mixed
	 */
	protected function get_site_option( string $name, $default = false ) {

		if ( $this->is_this_site() ) {
			return get_option( $name, $default );
		}

		return get_blog_option( $this->site_id, $name, $default );
	}

	/**
	 * Every plugin installed on this site, keyed by main file.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private static function get_installed_plugins(): array {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins();
	}
}
