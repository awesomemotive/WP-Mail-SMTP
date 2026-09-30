/* global WPMailSMTPChart */

/**
 * WP Mail SMTP Dashboard: Email Sources widget module.
 *
 * Draws the donut chart from the widget's `data-config` payload (labels, totals,
 * palette and center total; the table itself is server-rendered). On any
 * `wpMailSmtpDashboardDataReceived` broadcast that carries this widget's markup, this
 * module swaps it in and redraws.
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
export default function( document, window, $, app ) { // eslint-disable-line no-unused-vars, max-lines-per-function
	const { el } = app;

	const emailSources = {

		/**
		 * CSS selectors.
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		selectors: {
			widget: '[data-widget="email_sources"]',
			root: '.wpms-dashboard-email-sources',
			donut: '.wpms-dashboard-widget-email-sources-donut',
			chartTotal: '.wpms-dashboard-widget-email-sources-chart-total',
		},

		/**
		 * The current chart instance, if drawn.
		 *
		 * @since 4.10.0
		 *
		 * @type {?object}
		 */
		chart: null,

		/**
		 * Bind the date-range swap, then draw the chart present on load.
		 *
		 * @since 4.10.0
		 */
		init() {
			el.$document.on( 'wpMailSmtpDashboardDataReceived', emailSources.onDataReceived );
			emailSources.draw();
		},

		/**
		 * Draw the donut chart and its center total from the widget's current
		 * `data-config`, if present on this render.
		 *
		 * @since 4.10.0
		 */
		draw() {
			emailSources.chart?.destroy();
			emailSources.chart = null;

			const $root = el.$main.find( emailSources.selectors.root ).first();
			const canvas = $root.find( emailSources.selectors.donut ).get( 0 );

			if ( ! canvas || typeof WPMailSMTPChart === 'undefined' ) {
				return;
			}

			const config = emailSources.readConfig( $root );

			if ( ! config ) {
				return;
			}

			emailSources.chart = new WPMailSMTPChart( canvas.getContext( '2d' ), {
				type: 'doughnut',
				data: {
					labels: config.all.map( ( source ) => source.name ),
					datasets: [ {
						data: config.all.map( ( source ) => source.total ),
						backgroundColor: config.all.map( ( source, index ) => config.palette[ index % config.palette.length ] ),

						// Segment separator matches the chart container's background ($gray-0).
						borderColor: '#f6f6f6',
						borderWidth: config.all.length > 1 ? 2 : 0,
					} ],
				},
				options: {
					cutout: '50%',
					animation: false,
					maintainAspectRatio: false,
					responsive: true,
					plugins: { legend: { display: false } },
				},
			} );

			$root.find( emailSources.selectors.chartTotal ).text( new Intl.NumberFormat().format( config.donutTotal ) );
		},

		/**
		 * Parse the server-rendered config payload from the widget root.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $root Widget root, found by `selectors.root`.
		 *
		 * @returns {?object} Parsed config, or null when absent/invalid.
		 */
		readConfig( $root ) {
			if ( ! $root.length ) {
				return null;
			}

			try {
				return JSON.parse( $root.attr( 'data-config' ) );
			} catch ( error ) {
				window.console?.error?.( 'WP Mail SMTP Dashboard: invalid Email Sources config.', error );

				return null;
			}
		},

		/**
		 * Swap in the widget markup the server re-rendered for the new range, then redraw.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Custom event.
		 * @param {object}       data  Dashboard `get_stats` response data.
		 */
		onDataReceived( event, data ) {
			if ( ! data?.email_sources_html ) {
				return;
			}

			const $widget = el.$main.find( emailSources.selectors.widget ).first();

			if ( ! $widget.length ) {
				return;
			}

			$widget.replaceWith( data.email_sources_html );

			emailSources.draw();
		},
	};

	return emailSources;
}
