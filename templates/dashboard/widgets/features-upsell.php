<?php
/**
 * Features widget body: a grid of the shared feature tiles.
 *
 * @since 4.10.0
 *
 * @var array $tiles Feature tiles: icon, title, description, and (Pro only) link_text/link_url.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpms-dashboard-features-upsell-grid">
	<?php foreach ( $tiles as $tile ) : ?>
		<?php echo wp_mail_smtp_render( 'feature-tile', [ 'tile' => $tile ], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML. ?>
	<?php endforeach; ?>
</div>
