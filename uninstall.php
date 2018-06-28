<?php
/**
 * Uninstalls WP Mail SMTP.
 *
 * @since 1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Confirm user has decided to remove all data, otherwise stop.
$settings = get_option( 'wp_mail_smtp', array() );
if ( empty( $settings['general']['uninstall'] ) ) {
	return;
}

// Remove options.
$options = array(
	'wp_mail_smtp_initial_version',
	'wp_mail_smtp_version',
	'wp_mail_smtp',
	'_amn_smtp_last_checked',
	// Legacy options.
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
	'wp_mail_smtp_am_notifications_hidden',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove product annoucements.
$annoucements = get_posts( array(
	'post_type'   => array( 'amn_smtp' ),
	'post_status' => 'any',
	'numberposts' => -1,
	'fields'      => 'ids',
) );
if ( ! empty( $annoucements ) ) {
	foreach ( $annoucements as $annoucement ) {
		wp_delete_post( $annoucement, true );
	}
}
