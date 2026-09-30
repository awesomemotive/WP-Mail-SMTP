<?php
/**
 * Dashboard stat card.
 *
 * @since 4.10.0
 *
 * @var array $card Card data: id, label, value, delta, locked, icon, tone.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modifier     = $card['locked'] ? ' wpms-dashboard-stat-card--locked' : '';
$upgrade_link = wp_mail_smtp()->get_upgrade_link(
	[
		'medium'  => 'dashboard',
		'content' => 'stat-card-' . $card['id'],
	]
);
?>
<div class="wpms-dashboard-stat-card<?php echo esc_attr( $modifier ); ?>" data-card="<?php echo esc_attr( $card['id'] ); ?>">
	<span class="wpms-dashboard-stat-card__icon wpms-dashboard-stat-card__icon--<?php echo esc_attr( $card['tone'] ); ?>">
		<i class="wpms:icon-[<?php echo esc_attr( $card['icon'] ); ?>] wpms:w-[18px] wpms:h-[18px]" aria-hidden="true"></i>
	</span>

	<span class="wpms-dashboard-stat-card__body">
		<span class="wpms-dashboard-stat-card__label"><?php echo esc_html( $card['label'] ); ?></span>

		<?php if ( $card['locked'] ) : ?>
			<span class="wpms-dashboard-stat-card__value-row">
				<span class="wpms-dashboard-stat-card__value-wrap" aria-hidden="true">
					<span class="wpms-dashboard-stat-card__value wpms-dashboard-stat-card__value--locked" style="filter: blur( <?php echo esc_attr( $card['teaser_blur'] ); ?>px );">
						<?php echo esc_html( number_format_i18n( $card['teaser_value'] ) ); ?>
					</span>
					<i class="wpms-dashboard-stat-card__lock wpms:icon-[fa6-solid--lock] wpms:w-[14px] wpms:h-[14px]"></i>
				</span>

				<a href="<?php echo esc_url( $upgrade_link ); ?>" class="wpms-text-link wpms-text-link--xs wpms-dashboard-stat-card__unlock" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html__( 'Upgrade to Unlock', 'wp-mail-smtp' ); ?>
				</a>
			</span>
		<?php else : ?>
			<span class="wpms-dashboard-stat-card__value"><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></span>
		<?php endif; ?>
	</span>

	<?php if ( ! $card['locked'] && $card['delta'] !== null ) : ?>
		<?php $direction = $card['delta'] >= 0 ? 'up' : 'down'; ?>
		<span class="wpms-dashboard-stat-card__delta">
			<?php if ( $direction === 'up' ) : ?>
				<i class="wpms:icon-[fa6-solid--chevron-up] wpms:text-[11px]" aria-hidden="true"></i>
			<?php else : ?>
				<i class="wpms:icon-[fa6-solid--chevron-down] wpms:text-[11px]" aria-hidden="true"></i>
			<?php endif; ?>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: percent change against the previous week, a whole number. */
					__( '%s%%', 'wp-mail-smtp' ),
					number_format_i18n( round( abs( $card['delta'] ) ) )
				)
			);
			?>
		</span>
	<?php endif; ?>
</div>
