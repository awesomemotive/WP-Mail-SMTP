/* global wp_mail_smtp_dashboard_widget, ajaxurl, moment, WPMailSMTPChart */
/**
 * WP Mail SMTP Dashboard Widget function.
 *
 * @since 2.9.0
 */

'use strict';

var WPMailSMTPDashboardWidget = window.WPMailSMTPDashboardWidget || ( function( document, window, $ ) {

	/**
	 * Elements reference.
	 *
	 * @since 2.9.0
	 *
	 * @type {object}
	 */
	var el = {
		$canvas                         : $( '#wp-mail-smtp-dash-widget-chart' ),
		$settingsBtn                    : $( '#wp-mail-smtp-dash-widget-settings-button' ),
		$dismissBtn                     : $( '.wp-mail-smtp-dash-widget-dismiss-chart-upgrade' ),
		$summaryReportEmailBlock        : $( '.wp-mail-smtp-dash-widget-summary-report-email-block' ),
		$summaryReportEmailDismissBtn   : $( '.wp-mail-smtp-dash-widget-summary-report-email-dismiss' ),
		$summaryReportEmailEnableInput  : $( '#wp-mail-smtp-dash-widget-summary-report-email-enable' ),
	};

	/**
	 * Chart.js functions and properties.
	 *
	 * @since 2.9.0
	 *
	 * @type {object}
	 */
	var chart = {

		/**
		 * Chart.js instance.
		 *
		 * @since 2.9.0
		 */
		instance: null,

		/**
		 * Chart.js settings.
		 *
		 * @since 2.9.0
		 */
		settings: {
			type: 'line',
			data: {
				labels: [],
				datasets: [
					{
						label: '',
						data: [],
						backgroundColor: 'rgba(34, 113, 177, 0.15)',
						borderColor: 'rgba(34, 113, 177, 1)',
						borderWidth: 2,
						pointRadius: 4,
						pointBorderWidth: 1,
						pointBackgroundColor: 'rgba(255, 255, 255, 1)',
					}
				],
			},
			options: {
				maintainAspectRatio: false,
				scales: {
					xAxes: [ {
						type: 'time',
						time: {
							unit: 'day',
							tooltipFormat: 'MMM D',
						},
						distribution: 'series',
						ticks: {
							beginAtZero: true,
							source: 'labels',
							padding: 10,
							minRotation: 25,
							maxRotation: 25,
							callback: function( value, index, values ) {

								// Distribute the ticks equally starting from a right side of xAxis.
								var gap = Math.floor( values.length / 7 );

								if ( gap < 1 ) {
									return value;
								}
								if ( ( values.length - index - 1 ) % gap === 0 ) {
									return value;
								}
							},
						},
					} ],
					yAxes: [ {
						ticks: {
							beginAtZero: true,
							maxTicksLimit: 6,
							padding: 20,
							callback: function( value ) {

								// Make sure the tick value has no decimals.
								if ( Math.floor( value ) === value ) {
									return value;
								}
							},
						},
					} ],
				},
				elements: {
					line: {
						tension: 0,
					},
				},
				animation: {
					duration: 0,
				},
				hover: {
					animationDuration: 0,
				},
				legend: {
					display: false,
				},
				tooltips: {
					displayColors: false,
				},
				responsiveAnimationDuration: 0,
			},
		},

		/**
		 * Init Chart.js.
		 *
		 * @since 2.9.0
		 */
		init: function() {

			var ctx;

			if ( ! el.$canvas.length ) {
				return;
			}

			ctx = el.$canvas[ 0 ].getContext( '2d' );

			chart.instance = new WPMailSMTPChart( ctx, chart.settings );

			chart.updateWithDummyData();

			chart.instance.update();
		},

		/**
		 * Update Chart.js settings with dummy data.
		 *
		 * @since 2.9.0
		 */
		updateWithDummyData: function() {

			var end = moment().startOf( 'day' ),
				days = 7,
				data = [ 55, 45, 34, 45, 32, 55, 65 ],
				date,
				i;

			for ( i = 1; i <= days; i++ ) {

				date = end.clone().subtract( i, 'days' );

				chart.settings.data.labels.push( date );
				chart.settings.data.datasets[ 0 ].data.push( {
					t: date,
					y: data[ i - 1 ],
				} );
			}
		},
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 2.9.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Publicly accessible Chart.js functions and properties.
		 *
		 * @since 2.9.0
		 */
		chart: chart,

		/**
		 * Start the engine.
		 *
		 * @since 2.9.0
		 */
		init: function() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.9.0
		 */
		ready: function() {

			el.$settingsBtn.on( 'click', function() {
				$( this ).siblings( '.wp-mail-smtp-dash-widget-settings-menu' ).toggle();
			} );

			el.$dismissBtn.on( 'click', function( event ) {
				event.preventDefault();

				app.saveWidgetMeta( 'hide_graph', 1 );
				$( this ).closest( '.wp-mail-smtp-dash-widget-chart-block-container' ).remove();
				$( '#wp-mail-smtp-dash-widget-upgrade-footer' ).show();
			} );

			// Hide summary report email block on dismiss icon click.
			el.$summaryReportEmailDismissBtn.on( 'click', function( event ) {
				event.preventDefault();

				app.saveWidgetMeta( 'hide_summary_report_email_block', 1 );
				el.$summaryReportEmailBlock.slideUp();
			} );

			// Enable summary report email on checkbox enable.
			el.$summaryReportEmailEnableInput.on( 'change', function( event ) {
				event.preventDefault();

				var $self = $( this ),
					$loader = $self.next( 'i' );

				$self.hide();
				$loader.show();

				var data = {
					_wpnonce: wp_mail_smtp_dashboard_widget.nonce,
					action  : 'wp_mail_smtp_' + wp_mail_smtp_dashboard_widget.slug + '_enable_summary_report_email'
				};

				$.post( ajaxurl, data )
					.done( function() {
						el.$summaryReportEmailBlock.find( '.wp-mail-smtp-dash-widget-summary-report-email-block-setting' )
							.addClass( 'hidden' );
						el.$summaryReportEmailBlock.find( '.wp-mail-smtp-dash-widget-summary-report-email-block-applied' )
							.removeClass( 'hidden' );
					} )
					.fail( function() {
						$self.show();
						$loader.hide();
					} );
			} );

			chart.init();
			app.removeOverlay( el.$canvas );
		},

		/**
		 * Save dashboard widget meta in backend.
		 *
		 * @since 2.9.0
		 *
		 * @param {string} meta Meta name to save.
		 * @param {number} value Value to save.
		 */
		saveWidgetMeta: function( meta, value ) {

			var data = {
				_wpnonce: wp_mail_smtp_dashboard_widget.nonce,
				action  : 'wp_mail_smtp_' + wp_mail_smtp_dashboard_widget.slug + '_save_widget_meta',
				meta    : meta,
				value   : value,
			};

			$.post( ajaxurl, data );
		},

		/**
		 * Remove an overlay from a widget block containing $el.
		 *
		 * @since 2.9.0
		 *
		 * @param {object} $el jQuery element inside a widget block.
		 */
		removeOverlay: function( $el ) {
			$el.siblings( '.wp-mail-smtp-dash-widget-overlay' ).remove();
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPMailSMTPDashboardWidget.init();
