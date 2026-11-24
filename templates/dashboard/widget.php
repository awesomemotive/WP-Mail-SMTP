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
 * @var bool     $has_head     Whether the widget has a title row.
 * @var bool     $has_settings Whether the widget has a settings (cog) menu.
 * @var callable $head         Prints the title row content: the widget title by default.
 * @var callable $head_aside   Prints the trailing edge of the title row, before the cog.
 * @var callable $settings     Prints the settings popover (framework-built from the widget schema).
 * @var callable $body         Prints the widget body.
 * @var callable $footer       Prints the widget footer; printing nothing hides it.
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
	<?php // The version strip and the no-mailer prompt are bare cards in the design: no title, no controls, and so no title row. ?>
	<?php if ( $has_head ) : ?>
		<div class="wpms-card__head wpms-dashboard-widget-head">
			<?php
			if ( is_callable( $head ?? null ) ) {
				$head();
			}
			?>

			<?php
			if ( is_callable( $head_aside ?? null ) ) {
				$head_aside();
			}
			?>

			<?php if ( ! empty( $has_settings ) ) : ?>
				<button type="button" class="wpms-icon-btn wpms-dashboard-widget-cog" aria-label="<?php esc_attr_e( 'Widget settings', 'wp-mail-smtp' ); ?>">
					<i class="wpms:icon-[fa6-solid--gear] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php
	if ( is_callable( $settings ?? null ) ) {
		$settings();
	}
	?>

	<div class="wpms-dashboard-widget-body">
		<?php
		if ( is_callable( $body ?? null ) ) {
			$body();
		}
		?>
	</div>

	<?php
	if ( is_callable( $footer ?? null ) ) {
		$footer();
	}
	?>
</div>
