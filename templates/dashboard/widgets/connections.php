<?php
/**
 * Connections widget body.
 *
 * @since 4.10.0
 *
 * @var array  $groups    Row groups: label, rows (each: icon_html, title, subtitle, status, action, locked).
 * @var string $cta_label Trailing action label.
 * @var string $cta_url   Trailing action URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $groups as $group ) {
	echo wp_mail_smtp_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
		'dashboard/widget-row-group',
		[
			'label' => $group['label'],
			'rows'  => $group['rows'],
		],
		true
	);
}
?>
<p class="wpms-dashboard-connections__action">
	<a href="<?php echo esc_url( $cta_url ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-grey">
		<?php echo esc_html( $cta_label ); ?>
	</a>
</p>
