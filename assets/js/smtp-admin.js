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
		 * State attribute showing if one of the plugin settings
		 * changed and was not yet saved.
		 *
		 * @since {VERSION}
		 *
		 * @type {boolean}
		 */
		pluginSettingsChanged: false,

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

			// If there are screen options we have to move them.
			$( '#screen-meta-links, #screen-meta' ).prependTo( '#wp-mail-smtp-header-temp' ).show();

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
				$( this ).parents( '.wp-mail-smtp-mailer' ).find( 'input' ).trigger( 'click' );
			} );

			$( '.wp-mail-smtp-mailer input', app.pageHolder ).click( function () {
				var $input = $( this );

				if ( $input.prop( 'disabled' ) ) {
					// Educational Popup.
					if ( $input.hasClass( 'educate' ) ) {
						app.education.upgradeMailer( $input );
					}

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
						 task: 'pro_banner_dismiss'
					 }
				 } )
				 .always( function () {
					 $( '#wp-mail-smtp-pro-banner', app.pageHolder ).fadeOut( 'fast' );
				 } );
			} );

			// Dismis educational notices for certain mailers.
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

			app.triggerExitNotice();
		},

		education: {
			upgradeMailer: function ( $input ) {

				$.alert( {
					backgroundDismiss: true,
					escapeKey: true,
					animationBounce: 1,
					theme: 'modern',
					animateFromElement: false,
					draggable: false,
					closeIcon: true,
					useBootstrap: false,
					title: wp_mail_smtp.education.upgrade_title.replace( /%name%/g, $input.siblings( 'label' ).text().trim() ),
					icon: '"></i>' + wp_mail_smtp.education.upgrade_icon_lock + '<i class="',
					content: $( '.wp-mail-smtp-mailer-options .wp-mail-smtp-mailer-option-' + $input.val() + ' .wp-mail-smtp-setting-field' ).html(),
					boxWidth: '550px',
					onOpenBefore: function () {
						this.$btnc.after( '<div class="discount-note">' + wp_mail_smtp.education.upgrade_bonus + wp_mail_smtp.education.upgrade_doc + '</div>' );
					},
					buttons: {
						confirm: {
							text: wp_mail_smtp.education.upgrade_button,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
							action: function () {
								window.open( wp_mail_smtp.education.upgrade_url + '&utm_content=' + encodeURI( $input.val() ), '_blank' );
							}
						}
					}
				} );
			}
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
		},

		/**
		 * Exit notice JS code when plugin settings are not saved.
		 *
		 * @since {VERSION}
		 */
		triggerExitNotice: function () {

			var $settingPages = $( '.wp-mail-smtp-page-general:not( .wp-mail-smtp-tab-test )' );

			// Display an exit notice, if settings are not saved.
			$( window ).on( 'beforeunload', function () {
				if ( app.pluginSettingsChanged ) {
					return wp_mail_smtp.text_settings_not_saved;
				}
			} );

			// Set settings changed attribute, if any input was changed.
			$( ':input:not( #wp-mail-smtp-setting-license-key )', $settingPages ).on( 'change', function () {
				app.pluginSettingsChanged = true;
			} );

			// Clear the settings changed attribute, if the settings are about to be saved.
			$( 'form', $settingPages ).on( 'submit', function () {
				app.pluginSettingsChanged = false;
			} );
		}
	};

	// Provide access to public functions/properties.
	return app;
})( document, window, jQuery );

// Initialize.
WPMailSMTP.Admin.Settings.init();
