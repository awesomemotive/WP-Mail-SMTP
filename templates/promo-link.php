<?php
/**
 * A promo CTA link: Upgrade, Install, Activate or Installed.
 *
 * @since 4.10.0
 *
 * @var string $base_class Block-specific link class.
 * @var string $text       Link text.
 * @var string $url        Link URL.
 * @var string $action     Link action: 'install-plugin', 'activate-plugin', 'active', or ''.
 * @var string $plugin     Main file of the plugin the install action works on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = [ $base_class ];

// Only a link that installs or activates something gets the JS hook: the handler
// swallows the click, which would strand a link that just navigates somewhere.
if ( in_array( $action, [ 'install-plugin', 'activate-plugin' ], true ) ) {
	$classes[] = 'js-wpms-plugin-install';
}

if ( $action === 'active' ) {
	$classes[] = 'is-active';
}

$attributes = '';

if ( $action !== '' ) {
	$attributes .= sprintf( ' data-action="%s"', esc_attr( $action ) );
}

if ( $plugin !== '' ) {
	$attributes .= sprintf( ' data-plugin="%s"', esc_attr( $plugin ) );
}

// Assembled rather than echoed in place: the link is underlined, so whitespace between
// the text and the glyph would render as an underlined gap.
$label = esc_html( $text );

if ( $action === 'active' ) {
	$label .= '<i class="wpms:icon-[fa6-regular--circle-check] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>';
}
?>
<a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each attribute value is escaped above. ?>><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped text plus a literal glyph, assembled above. ?></a>
