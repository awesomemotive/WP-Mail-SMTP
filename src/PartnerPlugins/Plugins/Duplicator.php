<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Duplicator.
 *
 * @since 4.10.0
 */
class Duplicator extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'duplicator';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Duplicator';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'Duplicator Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'duplicator/duplicator.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'duplicator-pro/duplicator-pro.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/duplicator.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://duplicator.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Main files of plugins that solve the same problem this one does.
	 *
	 * @since 4.10.0
	 */
	const COMPETITORS = [
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
	];

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return class_exists( 'Duplicator\Plugin' ) ||
			defined( 'DUPLICATOR_VERSION' ) ||
			class_exists( 'DUP_PRO_Plugin' ) ||
			defined( 'DUPLICATOR_PRO_VERSION' );
	}

	/**
	 * Whether the premium tier is unlocked. Duplicator Pro 4.x shares
	 * DUPLICATOR_VERSION and the Duplicator\ namespace with the free version.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_pro_active(): bool {

		return parent::is_pro_active() ||
			class_exists( 'DUP_PRO_Plugin' ) ||
			defined( 'DUPLICATOR_PRO_VERSION' );
	}

	/**
	 * Whether the user has taken at least one backup.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_configured(): bool {

		$is_booted = defined( 'DUPLICATOR_VERSION' ) ||
			class_exists( 'Duplicator\Plugin' ) ||
			class_exists( 'Duplicator\Pro\Requirements' );

		return $is_booted && $this->is_active() && $this->get_backup_count() > 0;
	}

	/**
	 * The plugin's own admin screen.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return admin_url( 'admin.php?page=duplicator' );
	}

	/**
	 * How many backups the user has taken.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	public function get_backup_count(): int {

		if ( ! $this->is_loaded() ) {
			return 0;
		}

		global $wpdb;

		$table = $this->is_pro_active() ? $wpdb->prefix . 'duplicator_backups' : $wpdb->prefix . 'duplicator_packages';

		$blog_id         = get_current_blog_id();
		$table_cache_key = "wpms_dup_table_exists_{$blog_id}";
		$count_cache_key = "wpms_dup_package_count_{$blog_id}";
		$table_exists    = wp_cache_get( $table_cache_key, 'wp-mail-smtp' );

		if ( $table_exists === false ) {
			// Direct query required: no WP API exists for another plugin's custom tables.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

			wp_cache_set( $table_cache_key, $table_exists, 'wp-mail-smtp', 60 );
		}

		if ( $table_exists !== $table ) {
			return 0;
		}

		$count = wp_cache_get( $count_cache_key, 'wp-mail-smtp' );

		if ( $count === false ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

			wp_cache_set( $count_cache_key, $count, 'wp-mail-smtp', 60 );
		}

		return (int) $count;
	}

	/**
	 * How many backup schedules are configured.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	public function get_schedule_count(): int {

		$classes = [
			'\Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity',
			'\Duplicator\Models\ScheduleEntity',
		];

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) && method_exists( $class, 'count' ) ) {
				return (int) $class::count();
			}
		}

		return 0;
	}

	/**
	 * Drop the welcome-screen redirect Duplicator arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_option( 'duplicator_redirect_to_welcome' );
	}
}
