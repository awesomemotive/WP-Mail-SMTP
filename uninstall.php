<?php
/**
 * Uninstall all WP Mail SMTP data.
 *
 * @since 1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load plugin file.
require_once 'wp_mail_smtp.php';

// Confirm user has decided to remove all data, otherwise stop.
$settings = get_option( 'wp_mail_smtp', array() );
if ( empty( $settings['general']['uninstall'] ) ) {
	return;
}

/*
 * Remove Legacy options.
 */
$options = array(
	'_amn_smtp_last_checked',
	'pepipost_ssl',
	'pepipost_port',
	'pepipost_pass',
	'pepipost_user',
	'smtp_pass',
	'smtp_user',
	'smtp_auth',
	'smtp_ssl',
	'smtp_port',
	'smtp_host',
	'mail_set_return_path',
	'mailer',
	'mail_from_name',
	'mail_from',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

global $wpdb;

// Delete plugin settings.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wp\_mail\_smtp%'" );

// Delete plugin user meta.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wp\_mail\_smtp\_%'" );

// Remove any transients we've left behind.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_wp\_mail\_smtp\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_site\_transient\_wp\_mail\_smtp\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_wp\_mail\_smtp\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_site\_transient\_timeout\_wp\_mail\_smtp\_%'" );

/*
 * Remove product announcements.
 */
$announcements = get_posts(
	array(
		'post_type'   => array( 'amn_smtp' ),
		'post_status' => 'any',
		'numberposts' => - 1,
		'fields'      => 'ids',
	)
);
if ( ! empty( $announcements ) ) {
	foreach ( $announcements as $announcement ) {
		wp_delete_post( $announcement, true );
	}
}

/*
 * Logs for Pro plugin only.
 */
if (
	function_exists( 'wp_mail_smtp' ) &&
	is_readable( wp_mail_smtp()->plugin_path . '/src/Pro/Pro.php' )
) {
	// DB table.
	$logs_table = \WPMailSMTP\Pro\Emails\Logs\Logs::get_table_name();
	$wpdb->query( "DROP TABLE IF EXISTS $logs_table;" ); // phpcs:ignore WordPress.DB
}

/*
 * Drop all Action Scheduler data.
 */
require_once dirname( __FILE__ ) . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

// Unschedule all plugin ActionScheduler actions.
( new \WPMailSMTP\Tasks\Tasks() )->cancel_all();

$meta_table = \WPMailSMTP\Tasks\Meta::get_table_name();
$wpdb->query( "DROP TABLE IF EXISTS $meta_table;" ); // phpcs:ignore WordPress.DB
