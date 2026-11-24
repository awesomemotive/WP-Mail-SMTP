<?php
/**
 * Dashboard page shell template.
 *
 * @since 4.10.0
 *
 * @var callable $datepicker      Prints the date-range datepicker. Nothing on Lite.
 * @var callable $top_widgets     Prints the full-width widgets above both columns: the stat cards row.
 * @var callable $main_widgets    Prints the main column widgets.
 * @var callable $sidebar_widgets Prints the sidebar widgets.
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

			<?php
			if ( is_callable( $datepicker ?? null ) ) {
				$datepicker();
			}
			?>
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

		<?php
		if ( is_callable( $top_widgets ?? null ) ) {
			$top_widgets();
		}
		?>

		<div class="wpms-dashboard-columns">
			<div id="wpms-dashboard-column-main" class="wpms-dashboard-column-main">
				<?php
				if ( is_callable( $main_widgets ?? null ) ) {
					$main_widgets();
				}
				?>
			</div>
			<div id="wpms-dashboard-column-sidebar" class="wpms-dashboard-column-sidebar">
				<?php
				if ( is_callable( $sidebar_widgets ?? null ) ) {
					$sidebar_widgets();
				}
				?>
			</div>
		</div>
	</div>
</div>
