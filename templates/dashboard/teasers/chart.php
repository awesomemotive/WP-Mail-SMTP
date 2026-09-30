<?php
/**
 * Inert filled-area-chart teaser, blurred behind the Emails Overview empty-state
 * overlay. Purely decorative: no text, no focusable node.
 *
 * @since 4.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/dashboard-chart-teaser.svg' ); ?>"
	alt="" width="781" height="303" aria-hidden="true">
