<?php
/**
 * Dashboard "Setup Checklist" sidebar widget body: one group per checklist section,
 * its truncated steps, and a footer link to the full checklist.
 *
 * @since 4.10.0
 *
 * @var array  $sections      Checklist sections, each with `title`, `is_complete`, `hidden_count`
 *                             (steps hidden by truncation), and truncated `items` (item
 *                             view-models with `title`, `url`, `is_complete`).
 * @var string $checklist_url Full Setup Checklist page URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php foreach ( $sections as $section ) : ?>
	<div class="wpms-dashboard-checklist-overview-group">
		<div class="wpms-dashboard-checklist-overview-group-header">
			<h3 class="wpms-dashboard-checklist-overview-group-title<?php echo $section['is_complete'] ? ' wpms-dashboard-checklist-overview-group-title--complete' : ''; ?>">
				<?php echo esc_html( $section['title'] ); ?>
			</h3>
			<span class="wpms-badge wpms-badge--sm<?php echo $section['is_complete'] ? ' wpms-badge--complete' : ''; ?>">
				<?php echo $section['is_complete'] ? esc_html__( 'Complete', 'wp-mail-smtp' ) : esc_html__( 'Incomplete', 'wp-mail-smtp' ); ?>
			</span>
		</div>
		<?php if ( ! $section['is_complete'] ) : ?>
			<ul class="wpms-dashboard-checklist-overview-items">
				<?php foreach ( $section['items'] as $item ) : ?>
					<?php
					$item_complete = ! empty( $item['is_complete'] );
					$item_url      = $item['url'] ?? '';

					// A step with no URL is finished, or runs its action on the checklist
					// page itself, so here it is text: a link would have nowhere to go.
					$item_classes = 'wpms-dashboard-checklist-overview-item-link ' .
						( $item_url !== '' ? 'wpms-muted-link' : 'wpms-dashboard-checklist-overview-item-static' );

					if ( $item_complete ) {
						$item_classes .= ' wpms-dashboard-checklist-overview-item-link--complete';
					}

					$item_body = sprintf(
						'<i class="wpms-dashboard-checklist-overview-item-icon %1$s" aria-hidden="true"></i><span class="wpms-muted-link__label">%2$s</span>',
						$item_complete ? 'wpms:icon-[fa6-solid--circle-check]' : 'wpms:icon-[fa6-regular--circle]',
						esc_html( $item['title'] )
					);
					?>
					<li class="wpms-dashboard-checklist-overview-item">
						<?php if ( $item_url !== '' ) : ?>
							<a class="<?php echo esc_attr( $item_classes ); ?>" href="<?php echo esc_url( $item_url ); ?>"><?php echo $item_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled above from an escaped title and literal classes. ?></a>
						<?php else : ?>
							<span class="<?php echo esc_attr( $item_classes ); ?>"><?php echo $item_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled above from an escaped title and literal classes. ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $section['hidden_count'] > 0 ) : ?>
				<p class="wpms-dashboard-checklist-overview-more">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of further setup steps not listed. */
							_n( '+%s More Step..', '+%s More Steps..', $section['hidden_count'], 'wp-mail-smtp' ),
							number_format_i18n( $section['hidden_count'] )
						)
					);
					?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
<div class="wpms-dashboard-checklist-overview-footer">
	<a class="wpms-text-link wpms-text-link--arrow wpms-text-link--md" href="<?php echo esc_url( $checklist_url ); ?>">
		<span class="wpms-text-link__label"><?php esc_html_e( 'View Complete Setup Checklist', 'wp-mail-smtp' ); ?></span>
		<i class="wpms-text-link__icon wpms:icon-[fa6-solid--arrow-right] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
	</a>
</div>
