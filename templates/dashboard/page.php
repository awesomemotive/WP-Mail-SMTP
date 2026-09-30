<?php
/**
 * Dashboard page shell template.
 *
 * @since 4.10.0
 *
 * @var string $datepicker      Date-range datepicker rendered HTML. Empty on Lite.
 * @var string $top_widgets     Full-width widgets above both columns: the stat cards row.
 * @var string $main_widgets    Main column widgets rendered HTML.
 * @var string $sidebar_widgets Sidebar widgets rendered HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="wpms-dashboard" class="wpms-dashboard-page">
	<div class="wp-mail-smtp-page-title">
		<span class="page-title">
			<?php esc_html_e( 'Dashboard', 'wp-mail-smtp' ); ?>
		</span>

		<div class="wpms-dashboard-page__title-actions">
			<?php
			/**
			 * Fires at the start of the Dashboard title bar's actions area.
			 *
			 * @since 4.10.0
			 */
			do_action( 'wp_mail_smtp_admin_dashboard_page_title_actions' );
			?>

			<?php echo $datepicker; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>

	<div class="wp-mail-smtp-page-content">
		<h1 class="screen-reader-text">
			<?php esc_html_e( 'Dashboard', 'wp-mail-smtp' ); ?>
		</h1>

		<?php
		/**
		 * Fires at the top of the Dashboard page content, above the stat cards.
		 *
		 * @since 4.10.0
		 */
		do_action( 'wp_mail_smtp_admin_dashboard_page_before_widgets' );
		?>

		<div id="wpms-dashboard-attention"></div>

		<?php echo $top_widgets; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="wpms-dashboard-columns">
			<div id="wpms-dashboard-column-main" class="wpms-dashboard-column-main">
				<?php echo $main_widgets; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div id="wpms-dashboard-column-sidebar" class="wpms-dashboard-column-sidebar">
				<?php echo $sidebar_widgets; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
</div>
