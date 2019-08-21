/* globals jQuery, wp_mail_smtp */
var WPMailSMTP = window.WPMailSMTP || {};
WPMailSMTP.Admin = WPMailSMTP.Admin || {};

/**
 * WP Mail SMTP Admin area module.
 *
 * @since 1.6.0
 */
WPMailSMTP.Admin.Settings = WPMailSMTP.Admin.Settings || (function ( document, window, $ ) {

	'use strict';

	/**
	 * Private functions and properties.
	 *
	 * @since 1.6.0
	 *
	 * @type {Object}
	 */
	var __private = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.6.0
	 *
	 * @type {Object}
	 */
	var app = {
		/**
		 * Start the engine. DOM is not ready yet, use only to init something.
		 *
		 * @since 1.6.0
		 */
		init: function () {

			// Do that when DOM is ready.
			$( document ).ready( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.6.0
		 */
		ready: function () {

			app.pageHolder = $( '.wp-mail-smtp-tab-settings' );

			app.bindActions();
		},

		/**
		 * Process all generic actions/events, mostly custom that were fired by our API.
		 *
		 * @since 1.6.0
		 */
		bindActions: function () {

			// Mailer selection.
			$( '.wp-mail-smtp-mailer-image', app.pageHolder ).click( function () {
				$( this ).parents('.wp-mail-smtp-mailer').find( 'input' ).trigger( 'click' );
			} );

			$( '.wp-mail-smtp-mailer input', app.pageHolder ).click( function () {
				if ( $( this ).prop( 'disabled' ) ) {
					return false;
				}

				// Deselect the current mailer.
				$( '.wp-mail-smtp-mailer', app.pageHolder ).removeClass( 'active' );
				// Select the correct one.
				$( this ).parents( '.wp-mail-smtp-mailer' ).addClass( 'active' );

				// Hide all mailers options and display for a currently clicked one.
				$( '.wp-mail-smtp-mailer-option', app.pageHolder ).addClass( 'hidden' ).removeClass( 'active' );
				$( '.wp-mail-smtp-mailer-option-' + $( this ).val(), app.pageHolder ).addClass( 'active' ).removeClass( 'hidden' );
			} );

			app.mailers.smtp.bindActions();

			// Dismiss Pro banner at the bottom of the page.
			$( '#wp-mail-smtp-pro-banner-dismiss', app.pageHolder ).on( 'click', function () {
				$.ajax( {
					 url: ajaxurl,
					 dataType: 'json',
					 type: 'POST',
					 data: {
						 action: 'wp_mail_smtp_ajax',
						 task: 'pro_banner_dismiss',
					 }
				 } )
				 .always( function () {
					 $( '#wp-mail-smtp-pro-banner', app.pageHolder ).fadeOut( 'fast' );
				 } );
			} );

			// Dismis educational notices for certain users.
			$( '.js-wp-mail-smtp-mailer-notice-dismiss', app.pageHolder ).on( 'click', function ( e ) {
				e.preventDefault();

				var $btn = $( this ),
					$notice = $btn.parents( '.inline-notice' );

				if ( $btn.hasClass( 'disabled' ) ) {
					return false;
				}

				$.ajax( {
					 url: ajaxurl,
					 dataType: 'json',
					 type: 'POST',
					 data: {
						 action: 'wp_mail_smtp_ajax',
						 task: 'notice_dismiss',
						 notice: $notice.data( 'notice' ),
						 mailer: $notice.data( 'mailer' )
					 },
					 beforeSend: function () {
						 $btn.addClass( 'disabled' );
					 }
				 } )
				 .always( function () {
					 $notice.fadeOut( 'fast', function () {
						 $btn.removeClass( 'disabled' );
					 } );
				 } );
			} );

			// Show/hide debug output.
			$( '#wp-mail-smtp-debug .error-log-toggle' ).on( 'click', function ( e ) {
				e.preventDefault();

				$( '#wp-mail-smtp-debug .error-log-toggle' ).find( '.dashicons' ).toggleClass( 'dashicons-arrow-right-alt2 dashicons-arrow-down-alt2' );
				$( '#wp-mail-smtp-debug .error-log' ).slideToggle();
				$( '#wp-mail-smtp-debug .error-log-note' ).toggle();
			} );

			// Remove mailer connection.
			$( '.js-wp-mail-smtp-provider-remove', app.pageHolder ).on( 'click', function () {
				return confirm( wp_mail_smtp.text_provider_remove );
			} );

			// Copy input text to clipboard.
			$( '.wp-mail-smtp-setting-copy', app.pageHolder ).click( function ( e ) {
				e.preventDefault();

				var target = $( '#' + $( this ).data( 'source_id' ) ).get( 0 );

				target.select();

				document.execCommand( 'Copy' );
			} );
		},

		/**
		 * Individual mailers specific js code.
		 *
		 * @since 1.6.0
		 */
		mailers: {
			smtp: {
				bindActions: function () {

					// Hide SMTP-specific user/pass when Auth disabled.
					$( '#wp-mail-smtp-setting-smtp-auth' ).change( function () {
						$( '#wp-mail-smtp-setting-row-smtp-user, #wp-mail-smtp-setting-row-smtp-pass' ).toggleClass( 'inactive' );
					} );

					// Port default values based on encryption type.
					$( '#wp-mail-smtp-setting-row-smtp-encryption input' ).change( function () {

						var $input = $( this ),
							$smtpPort = $( '#wp-mail-smtp-setting-smtp-port', app.pageHolder );

						if ( 'tls' === $input.val() ) {
							$smtpPort.val( '587' );
							$( '#wp-mail-smtp-setting-row-smtp-autotls' ).addClass( 'inactive' );
						}
						else if ( 'ssl' === $input.val() ) {
							$smtpPort.val( '465' );
							$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
						}
						else {
							$smtpPort.val( '25' );
							$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
						}
					} );
				}
			}
		}
	};

	// Provide access to public functions/properties.
	return app;
})( document, window, jQuery );

// Initialize.
WPMailSMTP.Admin.Settings.init();
