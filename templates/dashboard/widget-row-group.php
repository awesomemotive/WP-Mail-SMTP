<?php
/**
 * A labelled group of rows: leading icon, title, status subtitle, trailing action.
 *
 * @since 4.10.0
 *
 * @var string $label Group heading. Empty renders the rows with no heading.
 * @var array  $rows  Rows: icon_html, title, subtitle, status, action (label, url), locked.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpms-dashboard-row-group">
	<?php if ( $label !== '' ) : ?>
		<h4 class="wpms-dashboard-row-group__label"><?php echo esc_html( $label ); ?></h4>
	<?php endif; ?>

	<?php foreach ( $rows as $row ) : ?>
		<?php
		$modifiers = '';

		if ( ! empty( $row['status'] ) ) {
			$modifiers .= ' wpms-dashboard-row-group__row--' . str_replace( '_', '-', $row['status'] );
		}

		if ( ! empty( $row['locked'] ) ) {
			$modifiers .= ' wpms-dashboard-row-group__row--locked';
		}
		?>
		<div class="wpms-dashboard-row-group__row<?php echo esc_attr( $modifiers ); ?>">
			<span class="wpms-icon-tile wpms-icon-tile--md wpms-dashboard-row-group__icon">
				<?php if ( ! empty( $row['locked'] ) ) : ?>
					<i class="wpms:icon-[fa6-solid--lock] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
				<?php elseif ( ! empty( $row['icon_html'] ) ) : ?>
					<?php echo wp_kses_post( $row['icon_html'] ); ?>
				<?php else : ?>
					<i class="wpms:icon-[fa6-solid--plus] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
				<?php endif; ?>
			</span>

			<span class="wpms-dashboard-row-group__info">
				<span class="wpms-dashboard-row-group__title"><?php echo esc_html( $row['title'] ); ?></span>

				<?php if ( $row['subtitle'] !== '' ) : ?>
					<span class="wpms-dashboard-row-group__subtitle">
						<?php if ( ! empty( $row['status'] ) ) : ?>
							<?php if ( $row['status'] === 'connected' ) : ?>
								<i class="wpms-dashboard-row-group__status wpms:icon-[fa6-solid--circle-check] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
							<?php else : ?>
								<i class="wpms-dashboard-row-group__status wpms:icon-[fa6-solid--circle-exclamation] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
							<?php endif; ?>
						<?php endif; ?>

						<?php echo esc_html( $row['subtitle'] ); ?>
					</span>
				<?php endif; ?>
			</span>

			<?php if ( ! empty( $row['action']['label'] ) ) : ?>
				<a href="<?php echo esc_url( $row['action']['url'] ); ?>" class="wpms-text-link wpms-dashboard-row-group__action">
					<?php echo esc_html( $row['action']['label'] ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
