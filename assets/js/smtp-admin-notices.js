/* global wp_mail_smtp_admin_notices, ajaxurl */

/**
 * WP Mail SMTP Admin Notices.
 *
 * @since 4.4.0
 */

'use strict';

var WPMailSMTPAdminNotices = window.WPMailSMTPAdminNotices || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 4.4.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 4.4.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 4.4.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 4.4.0
		 */
		events: function() {

			$( '.wp-mail-smtp-notice.is-dismissible' )
				.on( 'click', '.notice-dismiss', app.dismiss );
		},

		/**
		 * Click on the dismiss notice button.
		 *
		 * @since 4.4.0
		 *
		 * @param {object} event Event object.
		 */
		dismiss: function( event ) {

			var $notice = $( this ).closest( '.wp-mail-smtp-notice' );

			// If notice key is not defined, we can't dismiss it permanently.
			if ( $notice.data( 'notice' ) === undefined ) {
				return;
			}

			var $button = $( this );

			$.ajax( {
				url: ajaxurl,
				dataType: 'json',
				type: 'POST',
				data: {
					action: 'wp_mail_smtp_ajax',
					nonce: wp_mail_smtp_admin_notices.nonce,
					task: 'notice_dismiss',
					notice: $notice.data( 'notice' ),
				},
				beforeSend: function() {
					$button.prop( 'disabled', true );
				},
			} );
		},
	};

	return app;

}( document, window, jQuery ) );

// Initialize.
WPMailSMTPAdminNotices.init();
