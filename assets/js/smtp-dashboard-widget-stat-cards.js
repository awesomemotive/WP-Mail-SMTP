/**
 * WP Mail SMTP Dashboard: stat cards module.
 *
 * The cards are server-rendered; this module only swaps the row in on a
 * `wpMailSmtpDashboardDataReceived` broadcast carrying its markup for the new range.
 *
 * @since 4.10.0
 *
 * @param {object} document Document object.
 * @param {object} window   Window object.
 * @param {jQuery} $        jQuery object.
 * @param {object} app      Shared dashboard page app (el, selectors).
 *
 * @returns {object} Public module API.
 */
export default function( document, window, $, app ) { // eslint-disable-line no-unused-vars
	const { el } = app;

	const statCards = {

		/**
		 * Bind the date-range swap.
		 *
		 * @since 4.10.0
		 */
		init() {
			el.$document.on( 'wpMailSmtpDashboardDataReceived', statCards.onDataReceived );
		},

		/**
		 * Swap in the card row the server re-rendered for the new range.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Custom event.
		 * @param {object}       data  Dashboard `get_stats` response data.
		 */
		onDataReceived( event, data ) {
			if ( ! data?.stat_cards_html ) {
				return;
			}

			const $row = el.$page.find( app.selectors.statCards ).first();

			if ( $row.length ) {
				$row.replaceWith( data.stat_cards_html );
			}
		},
	};

	return statCards;
}
