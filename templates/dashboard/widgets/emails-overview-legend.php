<?php
/**
 * Emails Overview chart legend, rendered in the widget head beside the title.
 *
 * @since 4.10.0
 *
 * @var array $series_meta Chart series, in legend order: id, label, color, fill.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpms-dashboard-widget-emails-overview-legend">
	<?php foreach ( $series_meta as $series ) : ?>
		<span class="wpms-dashboard-widget-emails-overview-legend-item">
			<span class="wpms-dashboard-widget-emails-overview-legend-dot" style="background-color: <?php echo esc_attr( $series['color'] ); ?>;"></span>
			<?php echo esc_html( $series['label'] ); ?>
		</span>
	<?php endforeach; ?>
</div>
