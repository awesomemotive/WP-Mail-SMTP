<?php
/**
 * A feature tile: leading icon, title, description, and an optional CTA link. Shared by
 * the Setup Checklist promo grids and the Dashboard's features and growth-tools widgets.
 *
 * @since 4.10.0
 *
 * @var array $tile Tile parts. `icon` is an Iconify class list for the glyph, `image` a
 *                  brand-mark filename under `assets/images/` for a partner plugin, and
 *                  the `link_*` keys the optional CTA: `link_text` (empty renders none),
 *                  `link_url`, `link_action`, `link_plugin`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A tile source fills in only the keys its own surface needs, so default the rest here
// rather than at each of them.
$tile = wp_parse_args(
	$tile,
	[
		'icon'          => '',
		'image'         => '',
		'title'         => '',
		'description'   => '',
		'link_text'     => '',
		'link_url'      => '#',
		'link_action'   => '',
		'link_plugin'   => '',
	]
);

$icon_classes = [ 'wpms-icon-tile' ];

// A brand mark gets the large framed tile; a glyph gets the tinted one.
if ( $tile['image'] !== '' ) {
	array_push( $icon_classes, 'wpms-icon-tile--lg', 'wpms-icon-tile--framed' );
} else {
	array_push( $icon_classes, 'wpms-icon-tile--tint', 'wpms-icon-tile--accent' );
}
?>
<div class="wpms-feature-tile">
	<span class="<?php echo esc_attr( implode( ' ', $icon_classes ) ); ?>">
		<?php if ( $tile['image'] !== '' ) : ?>
			<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/' . $tile['image'] ); ?>" alt="" width="40" height="40">
		<?php else : ?>
			<i class="<?php echo esc_attr( $tile['icon'] ); ?>" aria-hidden="true"></i>
		<?php endif; ?>
	</span>
	<div class="wpms-feature-tile__body">
		<h3 class="wpms-feature-tile__title"><?php echo esc_html( $tile['title'] ); ?></h3>
		<div class="wpms-feature-tile__content">
			<p class="wpms-feature-tile__desc"><?php echo esc_html( $tile['description'] ); ?></p>

			<?php if ( $tile['link_text'] !== '' ) : ?>
				<?php
				echo wp_mail_smtp_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
					'promo-link',
					[
						'base_class' => 'wpms-text-link wpms-feature-tile__link',
						'text'       => $tile['link_text'],
						'url'        => $tile['link_url'],
						'action'     => $tile['link_action'],
						'plugin'     => $tile['link_plugin'],
					],
					true
				);
				?>
			<?php endif; ?>
		</div>
	</div>
</div>
