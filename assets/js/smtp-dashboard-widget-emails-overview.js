/* global WPMailSMTPChart */

/**
 * WP Mail SMTP Dashboard: Emails Overview widget graph module.
 *
 * Draws a filled area chart, one dataset per entry in the canvas's `data-series-meta`
 * attribute, plotted against the rows in `data-series`: Lite's completed weeks, or
 * Pro's per-day series for the selected date range. Binds no events itself; Pro's
 * date-range control redraws it via `draw()`.
 *
 * Points sit on a category axis: the server formats each row's axis label and its
 * tooltip, so this module never parses a date and the chart needs no time adapter.
 *
 * @since 4.10.0
 *
 * @param {object} document Document object.
 * @param {object} window   Window object.
 * @param {jQuery} $        jQuery object.
 * @param {object} app      Shared dashboard page app.
 *
 * @returns {object} Public module API.
 */
export default function( document, window, $, app ) { // eslint-disable-line no-unused-vars, max-lines-per-function
	const emailsOverview = {

		/**
		 * CSS selector for the chart canvas.
		 *
		 * @since 4.10.0
		 *
		 * @type {string}
		 */
		selector: '.wpms-dashboard-widget-emails-overview-chart',

		/**
		 * The current chart instance, if drawn. Kept so a redraw (Pro's date-range
		 * control) can destroy it first instead of stacking a second chart on the
		 * same canvas.
		 *
		 * @since 4.10.0
		 *
		 * @type {?object}
		 */
		chart: null,

		/**
		 * How many axis labels the x axis aims to draw, however many points it holds.
		 * The Email Reports chart thins to the same count.
		 *
		 * @since 4.10.0
		 *
		 * @type {number}
		 */
		maxTicks: 7,

		/**
		 * Draw the chart, if the canvas is present on this render.
		 *
		 * @since 4.10.0
		 */
		init() {
			const canvas = document.querySelector( emailsOverview.selector );

			if ( ! canvas || typeof WPMailSMTPChart === 'undefined' ) {
				return;
			}

			emailsOverview.draw( canvas, emailsOverview.readSeries( canvas ) );
		},

		/**
		 * Parse the server-rendered `data-series` payload. An invalid or missing
		 * payload normalizes to an empty series, plotting an empty chart rather
		 * than throwing.
		 *
		 * @since 4.10.0
		 *
		 * @param {Element} canvas Chart canvas carrying the `data-series` payload.
		 *
		 * @returns {Array} Rows, one per point, keyed by each series id plus `label`.
		 */
		readSeries( canvas ) {
			try {
				const series = JSON.parse( canvas.getAttribute( 'data-series' ) || '[]' );

				return Array.isArray( series ) ? series : [];
			} catch ( error ) {
				window.console?.error?.( 'WP Mail SMTP Dashboard: invalid Emails Overview series.', error );

				return [];
			}
		},

		/**
		 * Parse the server-rendered `data-series-meta` payload: the chart's series,
		 * in legend order. An invalid or missing payload normalizes to an empty list,
		 * plotting no datasets rather than throwing.
		 *
		 * @since 4.10.0
		 *
		 * @param {Element} canvas Chart canvas carrying the `data-series-meta` payload.
		 *
		 * @returns {Array} Series as `{ id, label, color, fill }`.
		 */
		readSeriesMeta( canvas ) {
			try {
				const meta = JSON.parse( canvas.getAttribute( 'data-series-meta' ) || '[]' );

				return Array.isArray( meta ) ? meta : [];
			} catch ( error ) {
				window.console?.error?.( 'WP Mail SMTP Dashboard: invalid Emails Overview series metadata.', error );

				return [];
			}
		},

		/**
		 * Derive the area fill colour from a series' line colour. Chart.js needs a
		 * literal fill colour, so the tint isn't declared alongside the series.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} hex Series colour, e.g. `#056aab`.
		 *
		 * @returns {string} `rgba()` colour at low opacity.
		 */
		fadeColor( hex ) {
			const value = hex.replace( '#', '' );
			const r = parseInt( value.substring( 0, 2 ), 16 );
			const g = parseInt( value.substring( 2, 4 ), 16 );
			const b = parseInt( value.substring( 4, 6 ), 16 );

			return `rgba(${ r }, ${ g }, ${ b }, 0.12)`;
		},

		/**
		 * Build one Chart.js dataset per series in `data-series-meta`, plotted
		 * against `rows`.
		 *
		 * @since 4.10.0
		 *
		 * @param {Array} rows Rows as `{ label, ...seriesValues }`.
		 * @param {Array} meta Series as `{ id, label, color, fill }`.
		 *
		 * @returns {Array} Chart.js dataset configs.
		 */
		buildDatasets( rows, meta ) {
			return meta.map( ( series ) => ( {
				label: series.label,
				data: rows.map( ( row ) => row[ series.id ] ?? 0 ),
				borderColor: series.color,
				backgroundColor: series.fill ? emailsOverview.fadeColor( series.color ) : 'transparent',
				fill: !! series.fill,
				tension: 0.4,
				borderWidth: 2,
				pointRadius: 0,
				pointHoverRadius: 4,
				pointHoverBackgroundColor: series.color,
			} ) );
		},

		/**
		 * Which axis labels to draw: all of them while they fit, then every nth, so a
		 * 30- or 90-day range reads as about seven dates instead of a solid band.
		 *
		 * @since 4.10.0
		 *
		 * @param {Array} rows Rows as `{ label, ...seriesValues }`.
		 *
		 * @returns {Function} Chart.js x-axis tick callback.
		 */
		tickCallback( rows ) {
			const gap = Math.floor( rows.length / emailsOverview.maxTicks );

			// Counted from the end, so the latest point always keeps its label.
			return ( value, index ) => {
				if ( gap < 1 || ( rows.length - index - 1 ) % gap === 0 ) {
					return rows[ index ]?.label ?? '';
				}

				return '';
			};
		},

		/**
		 * A design token's value, for the axis colours Chart.js paints onto the canvas
		 * and CSS therefore cannot reach.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} name     Custom property name.
		 * @param {string} fallback Value to use when the property is not set.
		 *
		 * @returns {string} Colour value.
		 */
		token( name, fallback ) {
			const value = window.getComputedStyle( document.documentElement ).getPropertyValue( name ).trim();

			return value || fallback;
		},

		/**
		 * Draw the filled area chart: one dataset per series in `data-series-meta`,
		 * plotted against `rows`.
		 *
		 * @since 4.10.0
		 *
		 * @param {Element} canvas Chart canvas.
		 * @param {Array}   rows   Rows as `{ label, ...seriesValues }`.
		 */
		draw( canvas, rows ) {
			const meta = emailsOverview.readSeriesMeta( canvas );

			if ( ! meta.length ) {
				return;
			}

			emailsOverview.chart?.destroy();

			const gridColor = emailsOverview.token( '--wpms-color-surface-divider', '#dcdcde' );
			const tickColor = emailsOverview.token( '--wpms-text-secondary', '#50575e' );
			const tickFont = { size: 13 };

			emailsOverview.chart = new WPMailSMTPChart( canvas.getContext( '2d' ), {
				type: 'line',
				data: {
					labels: rows.map( ( row ) => row.label ),
					datasets: emailsOverview.buildDatasets( rows, meta ),
				},
				options: {
					maintainAspectRatio: false,
					responsive: true,
					interaction: { mode: 'index', intersect: false },
					scales: {
						x: {
							grid: { display: true, color: gridColor },

							// The design slants the labels; thinning them is left to the callback
							// rather than to Chart.js, which would drop them by available room and
							// so vary with the viewport.
							ticks: {
								maxRotation: 25,
								minRotation: 25,
								autoSkip: false,
								color: tickColor,
								font: tickFont,
								callback: emailsOverview.tickCallback( rows ),
							},
						},
						y: {
							beginAtZero: true,
							grid: { color: gridColor },
							ticks: { precision: 0, color: tickColor, font: tickFont },
							border: { display: false },
						},
					},
					plugins: {

						// The legend lives in the widget head, server-rendered from the
						// same series metadata this chart is built from.
						legend: { display: false },
						tooltip: {
							displayColors: true,

							// Chart.js' default 'average' positioner averages every item in the
							// tooltip, which on a shared index lands between the two series.
							position: 'nearest',
							callbacks: {

								// A weekly point is labelled with the week's first day only, so
								// the tooltip spells out the whole span where there is room.
								title: ( items ) => rows[ items[ 0 ].dataIndex ]?.tooltip || items[ 0 ].label,
							},
						},
					},
				},
			} );
		},
	};

	return emailsOverview;
}
