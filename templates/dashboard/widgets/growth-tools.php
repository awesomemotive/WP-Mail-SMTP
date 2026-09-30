<?php
/**
 * Growth Tools widget body: a two-column grid of bordered plugin tiles, each with a
 * brand icon, a name, a description and an in-place Install / Activate / Installed CTA.
 *
 * @since 4.10.0
 *
 * @var array $tiles Growth-tool tiles: each with `image`, `title`, `description`,
 *                   `link_text`, `link_url`, `link_action`, `link_plugin`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpms-dashboard-growth-tools">
	<?php foreach ( $tiles as $tile ) : ?>
		<div class="wpms-dashboard-growth-tool">
			<div class="wpms-dashboard-growth-tool__head">
				<span class="wpms-icon-tile wpms-icon-tile--sm wpms-dashboard-growth-tool__icon">
					<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/' . $tile['image'] ); ?>" alt="" width="24" height="24">
				</span>
				<span class="wpms-dashboard-growth-tool__name"><?php echo esc_html( $tile['title'] ); ?></span>
			</div>

			<p class="wpms-dashboard-growth-tool__desc"><?php echo esc_html( $tile['description'] ); ?></p>

			<?php
			echo wp_mail_smtp_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
				'promo-link',
				[
					'base_class' => 'wpms-dashboard-growth-tool__cta wp-mail-smtp-btn wp-mail-smtp-btn-sm wp-mail-smtp-btn-grey',
					'text'       => $tile['link_text'] ?? '',
					'url'        => $tile['link_url'] ?? '#',
					'action'     => $tile['link_action'] ?? '',
					'plugin'     => $tile['link_plugin'] ?? '',
				],
				true
			);
			?>
		</div>
	<?php endforeach; ?>
</div>
