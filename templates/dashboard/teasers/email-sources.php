<?php
/**
 * Inert Email Sources table and donut teaser, blurred behind the widget's overlay.
 * Decorative sample literals, never real data, so they are never translated.
 *
 * @since 4.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpms-dashboard-widget-email-sources-body" aria-hidden="true">
	<table class="wpms-dashboard-widget-table wpms-dashboard-widget-email-sources-table">
		<thead>
			<tr>
				<th class="wpms-dashboard-widget-email-sources-col-source">Source</th>
				<th class="wpms-dashboard-widget-email-sources-col-share">Share</th>
				<th class="wpms-dashboard-widget-email-sources-col-total">Emails</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="wpms-dashboard-widget-email-sources-col-source">
					<span class="wpms-dashboard-widget-email-sources-dot" style="background-color: #056aab;"></span>
					WooCommerce
				</td>
				<td class="wpms-dashboard-widget-email-sources-col-share">42%</td>
				<td class="wpms-dashboard-widget-email-sources-col-total">540</td>
			</tr>
			<tr>
				<td class="wpms-dashboard-widget-email-sources-col-source">
					<span class="wpms-dashboard-widget-email-sources-dot" style="background-color: #00a0d2;"></span>
					WPForms
				</td>
				<td class="wpms-dashboard-widget-email-sources-col-share">31%</td>
				<td class="wpms-dashboard-widget-email-sources-col-total">398</td>
			</tr>
			<tr>
				<td class="wpms-dashboard-widget-email-sources-col-source">
					<span class="wpms-dashboard-widget-email-sources-dot" style="background-color: #72aee6;"></span>
					WordPress Core
				</td>
				<td class="wpms-dashboard-widget-email-sources-col-share">8%</td>
				<td class="wpms-dashboard-widget-email-sources-col-total">180</td>
			</tr>
			<tr>
				<td class="wpms-dashboard-widget-email-sources-col-source">
					<span class="wpms-dashboard-widget-email-sources-dot" style="background-color: #c5e1f9;"></span>
					Sugar Calendar
				</td>
				<td class="wpms-dashboard-widget-email-sources-col-share">8%</td>
				<td class="wpms-dashboard-widget-email-sources-col-total">102</td>
			</tr>
			<tr>
				<td class="wpms-dashboard-widget-email-sources-col-source">
					<span class="wpms-dashboard-widget-email-sources-dot" style="background-color: #d4d4d4;"></span>
					Others
				</td>
				<td class="wpms-dashboard-widget-email-sources-col-share">5%</td>
				<td class="wpms-dashboard-widget-email-sources-col-total">64</td>
			</tr>
		</tbody>
	</table>

	<div class="wpms-dashboard-widget-email-sources-chart">
		<div class="wpms-dashboard-widget-email-sources-chart-canvas">
			<div class="wpms-dashboard-widget-email-sources-donut-teaser"></div>
			<div class="wpms-dashboard-widget-email-sources-chart-center">
				<span class="wpms-dashboard-widget-email-sources-chart-total">1284</span>
				<span class="wpms-dashboard-widget-email-sources-chart-caption">Emails</span>
			</div>
		</div>
	</div>
</div>
