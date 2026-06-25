<?php

namespace WPMailSMTP\Admin\Recommendations;

use WPMailSMTP\MigrationAbstract;

/**
 * Recommended-plugins migration class.
 *
 * @since 4.9.0
 */
class Migration extends MigrationAbstract {

	/**
	 * Version of the latest migration for recommended plugins.
	 *
	 * @since 4.9.0
	 */
	const DB_VERSION = 1;

	/**
	 * Option key where we save the current recommended-plugins migration version.
	 *
	 * @since 4.9.0
	 */
	const OPTION_NAME = 'wp_mail_smtp_recommendations_migration_version';

	/**
	 * Option key where we save any errors during recommended-plugins migration.
	 *
	 * @since 4.9.0
	 */
	const ERROR_OPTION_NAME = 'wp_mail_smtp_recommendations_migration_error';

	/**
	 * Backfill first-activation timestamps for already-installed partner plugins.
	 *
	 * Reads each partner's own activation option so existing installs are not
	 * re-promoted a product the user already uses.
	 *
	 * @since 4.9.0
	 */
	protected function migrate_to_1() {

		$seeded = [];

		$this->check_wpconsent_activation( $seeded );
		$this->check_duplicator_activation( $seeded );
		$this->check_wpcode_activation( $seeded );
		$this->seed_active_plugins_as_adopted( $seeded );

		// Only write the option when there is something to seed.
		// Use add_option (not update_option) so we never clobber a value the
		// live activated_plugin hook may already have written.
		if ( ! empty( $seeded ) ) {
			add_option( RecommendedPlugins::ACTIVATED_OPTION, $seeded );
		}

		$this->update_db_ver( 1 );
	}

	/**
	 * Seed WPConsent activation timestamp from the partner's own option.
	 *
	 * @since 4.9.0
	 *
	 * @param array $seeded Array of slug => timestamp being built. Passed by reference.
	 */
	private function check_wpconsent_activation( &$seeded ) {

		$value = get_option( 'wpconsent_activated', [] );

		if ( empty( $value ) || ! is_array( $value ) ) {
			return;
		}

		// Lite stores its timestamp under 'wpconsent'; Pro under 'wpconsent_pro'.
		$timestamp = isset( $value['wpconsent'] ) ? $value['wpconsent'] : '';

		if ( empty( $timestamp ) ) {
			$timestamp = isset( $value['wpconsent_pro'] ) ? $value['wpconsent_pro'] : '';
		}

		if ( ! empty( $timestamp ) ) {
			$seeded['wpconsent'] = (int) $timestamp;
		}
	}

	/**
	 * Seed Duplicator activation timestamp from the partner's own option.
	 *
	 * @since 4.9.0
	 *
	 * @param array $seeded Array of slug => timestamp being built. Passed by reference.
	 */
	private function check_duplicator_activation( &$seeded ) {

		$install_info = get_option( 'duplicator_install_info' );
		$timestamp    = ( is_array( $install_info ) && ! empty( $install_info['time'] ) ) ? $install_info['time'] : null;

		// Fall back to Duplicator Pro install info when the Lite timestamp is absent.
		if ( empty( $timestamp ) ) {
			$pro_install_info = get_option( 'duplicator_pro_install_info' );
			$timestamp        = ( is_array( $pro_install_info ) && ! empty( $pro_install_info['time'] ) ) ? $pro_install_info['time'] : null;
		}

		if ( ! empty( $timestamp ) ) {
			$seeded['duplicator'] = (int) $timestamp;
		}
	}

	/**
	 * Seed WPCode activation timestamp from the partner's own option.
	 *
	 * @since 4.9.0
	 *
	 * @param array $seeded Array of slug => timestamp being built. Passed by reference.
	 */
	private function check_wpcode_activation( &$seeded ) {

		$value = get_option( 'ihaf_activated', [] );

		if ( empty( $value ) || ! is_array( $value ) ) {
			return;
		}

		$timestamp = isset( $value['wpcode'] ) ? $value['wpcode'] : '';

		if ( ! empty( $timestamp ) ) {
			$seeded['wpcode'] = (int) $timestamp;
		}
	}

	/**
	 * Mark any currently-active catalog plugin not already recorded as adopted.
	 *
	 * Pre-existing installs should not be promoted after upgrade. Products that
	 * expose no activation-time option can't be backdated from their own data,
	 * so detect an active install directly and stamp it past the rotation
	 * window, making it count as already adopted.
	 *
	 * @since 4.9.0
	 *
	 * @param array $seeded Array of slug => timestamp being built. Passed by reference.
	 */
	private function seed_active_plugins_as_adopted( &$seeded ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$adopted_time = time() - RecommendedPlugins::ADOPTED_AFTER - DAY_IN_SECONDS;

		foreach ( ( new RecommendedPlugins() )->get_products() as $product ) {
			if ( isset( $seeded[ $product['slug'] ] ) ) {
				continue;
			}

			$files = array_filter(
				[
					isset( $product['plugin'] ) ? $product['plugin'] : '',
					isset( $product['plugin_pro'] ) ? $product['plugin_pro'] : '',
				]
			);

			foreach ( $files as $file ) {
				if ( is_plugin_active( $file ) ) {
					$seeded[ $product['slug'] ] = $adopted_time;

					break;
				}
			}
		}
	}
}
