<?php
/**
 * The no-mailer prompt.
 *
 * @since 4.10.0
 *
 * @var string $wizard_url Setup wizard entry point.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpms-dashboard-mailer-notice">
	<h3 class="wpms-dashboard-mailer-notice__title">
		<i class="wpms-dashboard-mailer-notice__icon wpms:icon-[fa6-solid--circle-exclamation] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
		<?php echo esc_html__( 'Setup Your Mailer', 'wp-mail-smtp' ); ?>
	</h3>

	<p class="wpms-dashboard-mailer-notice__text">
		<?php echo esc_html__( 'Connect WordPress to mailers like SendLayer, Gmail, Outlook, Amazon SES, and more to start sending emails', 'wp-mail-smtp' ); ?>
	</p>

	<a href="<?php echo esc_url( $wizard_url ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange">
		<?php echo esc_html__( 'Launch Setup Wizard', 'wp-mail-smtp' ); ?>
		<i class="wpms:icon-[fa6-solid--arrow-right] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
	</a>
</div>
