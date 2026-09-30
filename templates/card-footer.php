<?php
/**
 * Card footer: helper text at one edge, the actions at the other.
 *
 * @since 4.10.0
 *
 * @var string $text Helper text. Empty renders the actions alone, at the trailing edge.
 * @var array  $cta  Button: label, url, style ('primary'|'secondary'), target_blank, arrow.
 * @var string $chip Optional chip markup shown before the button, with its own leading glyph.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$btn_color    = ( isset( $cta['style'] ) && $cta['style'] === 'secondary' ) ? 'wp-mail-smtp-btn-grey' : 'wp-mail-smtp-btn-orange';
$show_arrow   = ! isset( $cta['arrow'] ) || (bool) $cta['arrow'];
$target_attrs = empty( $cta['target_blank'] ) ? '' : ' target="_blank" rel="noopener noreferrer"';
?>
<div class="wpms-card__footer">
	<?php if ( $text !== '' ) : ?>
		<p class="wpms-card__footer-text"><?php echo esc_html( $text ); ?></p>
	<?php endif; ?>

	<div class="wpms-card__footer-actions">
		<?php if ( ! empty( $chip ) ) : ?>
			<span class="wpms-card__footer-chip">
				<i class="wpms-card__footer-chip-icon wpms:icon-[custom--badge-percent] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
				<?php echo wp_kses_post( $chip ); ?>
			</span>
		<?php endif; ?>

		<a href="<?php echo esc_url( $cta['url'] ); ?>"
			class="wp-mail-smtp-btn wp-mail-smtp-btn-md <?php echo esc_attr( $btn_color ); ?> wpms-card__footer-cta"
			<?php echo $target_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string built above. ?>>
			<?php echo esc_html( $cta['label'] ); ?>
			<?php if ( $show_arrow ) : ?>
				<i class="wpms:icon-[fa6-solid--arrow-right] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
			<?php endif; ?>
		</a>
	</div>
</div>
