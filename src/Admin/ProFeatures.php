<?php

namespace WPMailSMTP\Admin;

/**
 * The Pro feature list shared by the admin surfaces that promote it.
 *
 * @since 4.10.0
 */
class ProFeatures {

	/**
	 * Render-ready tiles for the Pro feature grid. Each tile's `slug` names the feature
	 * for a caller that decorates them further.
	 *
	 * @since 4.10.0
	 *
	 * @return array<int, array>
	 */
	public function get_tiles(): array {

		return [
			[
				'slug'        => 'email-logging',
				'icon'        => 'wpms:icon-[fa6-solid--list-check] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Email Logging', 'wp-mail-smtp' ),
				'description' => __( 'Log, track, and resend any email your site sends.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'email-alerts',
				'icon'        => 'wpms:icon-[fa6-solid--comments] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Email Failure Alerts', 'wp-mail-smtp' ),
				'description' => __( 'Instant failure alerts via email, Slack, SMS, and more.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'backup-connections',
				'icon'        => 'wpms:icon-[fa6-solid--paper-plane] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Backup Connections', 'wp-mail-smtp' ),
				'description' => __( 'Never miss an email with an automatic backup mailer.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'one-click-setup',
				'icon'        => 'wpms:icon-[fa6-solid--hand-pointer] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'One-Click Mailer Setups', 'wp-mail-smtp' ),
				'description' => __( 'Easily configure your Gmail and Outlook mailers.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'advanced-mailers',
				'icon'        => 'wpms:icon-[fa6-solid--medal] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Advanced Mailers', 'wp-mail-smtp' ),
				'description' => __( 'Unlock Amazon SES, Microsoft 365, and Zoho.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'email-reports',
				'icon'        => 'wpms:icon-[fa6-solid--chart-bar] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Email Reports', 'wp-mail-smtp' ),
				'description' => __( 'Track opens, clicks, and weekly deliverability stats.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'open-click-tracking',
				'icon'        => 'wpms:icon-[fa6-solid--envelope-open-text] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Open & Click Tracking', 'wp-mail-smtp' ),
				'description' => __( 'See when emails are opened and links are clicked.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'wp-notifications',
				'icon'        => 'wpms:icon-[fa6-solid--bell-slash] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Manage WordPress Notifications', 'wp-mail-smtp' ),
				'description' => __( 'Silence default WordPress emails you don\'t need.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'smart-routing',
				'icon'        => 'wpms:icon-[fa6-solid--sitemap] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Smart Routing', 'wp-mail-smtp' ),
				'description' => __( 'Send each email type through the mailer you choose.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'rate-limiting',
				'icon'        => 'wpms:icon-[fa6-solid--gauge-high] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Rate Limiting', 'wp-mail-smtp' ),
				'description' => __( 'Control how many emails your site sends per interval.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'log-export',
				'icon'        => 'wpms:icon-[fa6-solid--file-export] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Export Email Logs', 'wp-mail-smtp' ),
				'description' => __( 'Download your email logs in CSV, Excel, or EML.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'priority-support',
				'icon'        => 'wpms:icon-[fa6-solid--headset] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Priority Support', 'wp-mail-smtp' ),
				'description' => __( 'Get help from our email deliverability experts.', 'wp-mail-smtp' ),
			],
			[
				'slug'        => 'multisite',
				'icon'        => 'wpms:icon-[fa6-solid--globe] wpms:w-[20px] wpms:h-[20px]',
				'title'       => __( 'Multisite Support', 'wp-mail-smtp' ),
				'description' => __( 'Manage email settings across your network.', 'wp-mail-smtp' ),
			],
		];
	}
}
