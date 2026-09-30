<?php
/**
 * Dashboard widget shell template.
 *
 * @since 4.10.0
 *
 * @var string $id             Widget identifier (e.g. `entries`, `payments`).
 * @var string $variant        Widget variant; appended as the `wpms-dashboard-widget-{id}-{variant}` state class.
 * @var array  $extra_classes  Extra state classes for the card root, declared by the widget.
 * @var bool   $is_dismissible Whether to add the `wpms-dashboard-dismiss-container` class to the card root.
 * @var string $title          Widget title.
 * @var string $head           Optional rich head HTML (title row). When empty, $title is rendered instead.
 * @var string $head_aside     Optional HTML for the trailing edge of the title row, before the cog.
 * @var bool   $has_settings   Whether the widget has a settings (cog) menu.
 * @var string $settings       Settings popover rendered HTML (framework-built from the widget schema).
 * @var string $body           Widget body rendered HTML.
 * @var string $footer         Widget footer rendered HTML. Empty string hides the footer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Card classes: the shared card, the block, the widget itself, and (when set) its state.
// Styles hook these; data-widget stays the JS hook.
$widget_classes = [
	'wpms-card',
	'wpms-dashboard-widget',
	'wpms-dashboard-widget-' . $id,
];

if ( $variant !== '' ) {
	$widget_classes[] = 'wpms-dashboard-widget-' . $id . '-' . $variant;
}

if ( ! empty( $extra_classes ) ) {
	$widget_classes = array_merge( $widget_classes, array_map( 'sanitize_html_class', (array) $extra_classes ) );
}

if ( ! empty( $is_dismissible ) ) {
	$widget_classes[] = 'wpms-dashboard-dismiss-container';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $widget_classes ) ); ?>" data-widget="<?php echo esc_attr( $id ); ?>">
	<?php
	// The version strip and the no-mailer prompt are bare cards in the design: no title,
	// no controls, and so no title row above the body at all.
	$has_head = $head !== '' || $title !== '' || $head_aside !== '' || ! empty( $has_settings );
	?>
	<?php if ( $has_head ) : ?>
		<div class="wpms-card__head wpms-dashboard-widget-head">
			<?php if ( $head !== '' ) : ?>
				<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<h2 class="wpms-card__title wpms-card__title--widget"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( $head_aside !== '' ) : ?>
				<?php echo $head_aside; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered by the widget, escaped there. ?>
			<?php endif; ?>

			<?php if ( ! empty( $has_settings ) ) : ?>
				<button type="button" class="wpms-icon-btn wpms-dashboard-widget-cog" aria-label="<?php esc_attr_e( 'Widget settings', 'wp-mail-smtp' ); ?>">
					<i class="wpms:icon-[fa6-solid--gear] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $has_settings ) && ! empty( $settings ) ) : ?>
		<?php echo $settings; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by the framework settings template, escaped there. ?>
	<?php endif; ?>

	<?php if ( $body !== '' ) : ?>
		<div class="wpms-dashboard-widget-body">
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $footer ) ) : ?>
		<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</div>
