<?php

namespace WPMailSMTP\PartnerPlugins;

use Plugin_Upgrader;
use WP_Error;
use WPMailSMTP\Admin\PluginsInstallSkin;
use WPMailSMTP\Helpers\Helpers;

/**
 * Installs and activates partner plugins.
 *
 * Callers resolve a PartnerPlugin first, so a request naming an arbitrary
 * plugin has nowhere to go.
 *
 * @since 4.10.0
 */
class Installer {

	/**
	 * Install the free version, then activate it.
	 *
	 * @since 4.10.0
	 *
	 * @param PartnerPlugin $plugin       Partner to install.
	 * @param string        $redirect_url Where the filesystem-credentials form would post back to.
	 *
	 * @return array|WP_Error `basename` and `activated` on success.
	 */
	public function install( PartnerPlugin $plugin, string $redirect_url ) {

		if ( ! $plugin->is_installable() ) {
			return new WP_Error( 'wp_mail_smtp_partner_not_installable', esc_html__( 'This plugin cannot be installed automatically.', 'wp-mail-smtp' ) );
		}

		if ( ! $this->prepare_filesystem( $redirect_url ) ) {
			return new WP_Error( 'wp_mail_smtp_partner_filesystem', esc_html__( 'Could not install the plugin. Missing file system permission.', 'wp-mail-smtp' ) );
		}

		Helpers::include_plugin_upgrader();

		$skin      = new PluginsInstallSkin();
		$installer = new Plugin_Upgrader( $skin );

		$installer->install( $plugin->get_download_url() );

		// The installed basename is only readable once the cache is flushed.
		wp_cache_flush();

		$basename = $installer->plugin_info();

		if ( $skin->has_failure() || ! $basename ) {
			return new WP_Error( 'wp_mail_smtp_partner_install_failed', esc_html__( 'Could not install the plugin.', 'wp-mail-smtp' ) );
		}

		/**
		 * After a partner plugin was installed.
		 *
		 * @since 4.10.0
		 *
		 * @param string $slug Partner plugin slug.
		 */
		do_action( 'wp_mail_smtp_partners_installer_installed', $plugin->get_slug() );

		$activated = false;

		// Core's installer leaves a plugin inactive for a user who may not activate it.
		if ( current_user_can( 'activate_plugins' ) ) {
			$activated = ! is_wp_error( $this->run_activation( $plugin, $basename ) );
		}

		if ( $activated ) {
			/**
			 * After a partner plugin was activated.
			 *
			 * @since 4.10.0
			 *
			 * @param string $slug Partner plugin slug.
			 */
			do_action( 'wp_mail_smtp_partners_installer_activated', $plugin->get_slug() );
		}

		return [
			'basename'  => $basename,
			'activated' => $activated,
		];
	}

	/**
	 * Activate an already installed plugin, preferring the premium version
	 * when both are present.
	 *
	 * @since 4.10.0
	 *
	 * @param PartnerPlugin $plugin Partner to activate.
	 *
	 * @return string|WP_Error Basename of the activated plugin.
	 */
	public function activate( PartnerPlugin $plugin ) {

		if ( ! $plugin->is_installed() && ! $plugin->is_pro_installed() ) {
			return new WP_Error( 'wp_mail_smtp_partner_not_installed', esc_html__( 'Could not activate the plugin. It is not installed.', 'wp-mail-smtp' ) );
		}

		$basename = $plugin->get_installed_basename();

		if ( is_wp_error( $this->run_activation( $plugin, $basename ) ) ) {
			return new WP_Error( 'wp_mail_smtp_partner_activate_failed', esc_html__( 'Could not activate the plugin. Please activate it from the Plugins page.', 'wp-mail-smtp' ) );
		}

		/**
		 * After a partner plugin was activated.
		 *
		 * @since 4.10.0
		 *
		 * @param string $slug Partner plugin slug.
		 */
		do_action( 'wp_mail_smtp_partners_installer_activated', $plugin->get_slug() );

		return $basename;
	}

	/**
	 * Install the plugin when it is missing, activate it when it is not.
	 *
	 * @since 4.10.0
	 *
	 * @param PartnerPlugin $plugin       Partner to make active.
	 * @param string        $redirect_url Where the filesystem-credentials form would post back to.
	 *
	 * @return true|WP_Error
	 */
	public function install_or_activate( PartnerPlugin $plugin, string $redirect_url ) {

		if ( $plugin->is_active() ) {
			return true;
		}

		$result = ( $plugin->is_installed() || $plugin->is_pro_installed() )
			? $this->activate( $plugin )
			: $this->install( $plugin, $redirect_url );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// An install the user may not follow with an activation did all it could.
		if ( is_array( $result ) && ! $result['activated'] && current_user_can( 'activate_plugins' ) ) {
			return new WP_Error( 'wp_mail_smtp_partner_activate_failed', esc_html__( 'The plugin was installed but could not be activated. Please activate it from the Plugins page.', 'wp-mail-smtp' ) );
		}

		return true;
	}

	/**
	 * Activate a main file, on the site the plugin describes.
	 *
	 * @since 4.10.0
	 *
	 * @param PartnerPlugin $plugin   Partner being activated.
	 * @param string        $basename Main file to activate.
	 *
	 * @return null|WP_Error
	 */
	private function run_activation( PartnerPlugin $plugin, string $basename ) {

		$switched = is_multisite() && $plugin->get_site_id() !== get_current_blog_id();

		if ( $switched ) {
			switch_to_blog( $plugin->get_site_id() );
		}

		$plugin->before_activation();

		$activated = activate_plugin( $basename );

		$plugin->after_activation();

		if ( $switched ) {
			restore_current_blog();
		}

		return $activated;
	}

	/**
	 * Bring up the filesystem abstraction the upgrader writes through.
	 *
	 * @since 4.10.0
	 *
	 * @param string $redirect_url Where the credentials form would post back to.
	 *
	 * @return bool
	 */
	private function prepare_filesystem( string $redirect_url ): bool { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks -- the remove_action() below is scoped to this one upgrader run; hooks() would remove it unconditionally.

		// REST requests do not bootstrap the admin, so the file backing
		// request_filesystem_credentials and WP_Filesystem may not be loaded.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		/*
		 * On failure request_filesystem_credentials() prints a credentials
		 * form, which would corrupt the JSON response, so its output is
		 * swallowed and the failure read from the return value instead.
		 */
		ob_start();
		// phpcs:ignore WPForms.Formatting.EmptyLineAfterAssigmentVariables.AddEmptyLine
		$creds = request_filesystem_credentials( $redirect_url, '', false, false, null );
		ob_end_clean();

		if ( $creds === false ) {
			return false;
		}

		// Translation downloads emit output that would corrupt the response.
		remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

		return (bool) WP_Filesystem( $creds );
	}
}
