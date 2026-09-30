<?php
/**
 * Dashboard shared doc-link row: a documentation link that opens in a new tab.
 *
 * @since 4.10.0
 *
 * @var string $label Link text.
 * @var string $url   Link URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<a class="wpms-muted-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
	<i class="wpms:icon-[fa6-regular--file-lines] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
	<span class="wpms-muted-link__label"><?php echo esc_html( $label ); ?></span>
</a>
