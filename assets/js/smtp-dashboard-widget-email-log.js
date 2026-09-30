/**
 * WP Mail SMTP Dashboard: Email Log widget module.
 *
 * The widget's rows are server-rendered; this module only swaps the whole card in
 * on a `wpMailSmtpDashboardDataReceived` broadcast that carries the widget's
 * re-rendered markup for the newly selected date range, mirroring the Email
 * Sources widget module.
 *
 * @since 4.10.0
 *
 * @param {object} document Document object.
 * @param {object} window   Window object.
 * @param {jQuery} $        jQuery object.
 * @param {object} app      Shared dashboard page app (el).
 *
 * @returns {object} Public module API.
 */
export default function( document, window, $, app ) { // eslint-disable-line no-unused-vars
	const { el } = app;

	const emailLog = {

		/**
		 * CSS selectors.
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		selectors: {
			widget: '[data-widget="email_log"]',
		},

		/**
		 * Bind the date-range swap.
		 *
		 * @since 4.10.0
		 */
		init() {
			el.$document.on( 'wpMailSmtpDashboardDataReceived', emailLog.onDataReceived );
		},

		/**
		 * Swap in the widget markup the server re-rendered for the new range.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Custom event.
		 * @param {object}       data  Dashboard `get_stats` response data.
		 */
		onDataReceived( event, data ) {
			if ( ! data?.email_log_html ) {
				return;
			}

			const $widget = el.$main.find( emailLog.selectors.widget ).first();

			if ( $widget.length ) {
				$widget.replaceWith( data.email_log_html );
			}
		},
	};

	return emailLog;
}
