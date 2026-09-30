<?php
/**
 * Centered overlay card above an inert teaser.
 *
 * @since 4.10.0
 *
 * @var string $title  Overlay heading.
 * @var string $text   Overlay body copy.
 * @var array  $cta    Button: label, url, style, target_blank, arrow.
 * @var string $teaser Teaser HTML from `templates/dashboard/teasers/`, echoed raw
 *                     (the chart teaser is an inline SVG that wp_kses_post() would strip),
 *                     so never caller-composed markup and never user data. Empty renders
 *                     the overlay on its own.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$btn_color    = ( isset( $cta['style'] ) && $cta['style'] === 'secondary' ) ? 'wp-mail-smtp-btn-grey' : 'wp-mail-smtp-btn-orange';
$show_arrow   = ! empty( $cta['arrow'] );
$target_attrs = empty( $cta['target_blank'] ) ? '' : ' target="_blank" rel="noopener noreferrer"';
?>
<div class="wpms-dashboard-overlay-container">
	<?php if ( $teaser !== '' ) : ?>
		<div class="wpms-dashboard-teaser" aria-hidden="true" inert>
			<?php echo $teaser; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered by a sibling template. ?>
		</div>
	<?php endif; ?>

	<div class="wpms-dashboard-overlay">
		<h3 class="wpms-dashboard-overlay__title"><?php echo esc_html( $title ); ?></h3>
		<p class="wpms-dashboard-overlay__text"><?php echo esc_html( $text ); ?></p>

		<a href="<?php echo esc_url( $cta['url'] ); ?>"
			class="wp-mail-smtp-btn wp-mail-smtp-btn-md <?php echo esc_attr( $btn_color ); ?>"
			<?php echo $target_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string built above. ?>>
			<?php echo esc_html( $cta['label'] ); ?>
			<?php if ( $show_arrow ) : ?>
				<i class="wpms:icon-[fa6-solid--arrow-right] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
			<?php endif; ?>
		</a>

		<?php if ( $teaser !== '' ) : ?>
			<p class="wpms-dashboard-overlay__disclaimer">
				<?php echo esc_html__( 'The data shown behind this popup is for example purposes only. No email data has been recorded.', 'wp-mail-smtp' ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>
