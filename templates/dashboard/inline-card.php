<?php
/**
 * A dismissible card rendered inside a host widget's body, under its content. See
 * InlineCards::render_inline_cards().
 *
 * @since 4.10.0
 *
 * @var array $card Resolved card: id, icon, title, text, cta (label, url, target_blank).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$target_attrs = empty( $card['cta']['target_blank'] ) ? '' : ' target="_blank" rel="noopener noreferrer"';
?>
<div class="wpms-dashboard-notice" data-card="<?php echo esc_attr( $card['id'] ); ?>">
	<div class="wpms-icon-tile wpms-icon-tile--framed wpms-icon-tile--accent wpms-dashboard-notice__icon">
		<i class="wpms:icon-[<?php echo esc_attr( $card['icon'] ); ?>] wpms:text-[20px]" aria-hidden="true"></i>
	</div>

	<div class="wpms-dashboard-notice__body">
		<p class="wpms-dashboard-notice__title"><?php echo esc_html( $card['title'] ); ?></p>

		<span class="wpms-dashboard-notice__message">
			<span class="wpms-dashboard-notice__text"><?php echo esc_html( $card['text'] ); ?></span>

			<a class="wpms-text-link wpms-text-link--arrow wpms-text-link--xs"
				href="<?php echo esc_url( $card['cta']['url'] ); ?>"
				<?php echo $target_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string built above. ?>>
				<span class="wpms-text-link__label"><?php echo esc_html( $card['cta']['label'] ); ?></span>
				<i class="wpms-text-link__icon wpms:icon-[fa6-solid--arrow-right] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
			</a>
		</span>
	</div>

	<button type="button" class="wpms-icon-btn wpms-dashboard-notice__dismiss js-wpms-dashboard-notice-dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'wp-mail-smtp' ); ?>">
		<i class="wpms:icon-[fa6-solid--xmark] wpms:text-[14px]" aria-hidden="true"></i>
	</button>
</div>
