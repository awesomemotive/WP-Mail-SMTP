/* globals wp_mail_smtp, jconfirm, ajaxurl */
'use strict';

var WPMailSMTP = window.WPMailSMTP || {};
WPMailSMTP.Admin = WPMailSMTP.Admin || {};

/**
 * WP Mail SMTP Admin area module.
 *
 * @since 1.6.0
 */
WPMailSMTP.Admin.Settings = WPMailSMTP.Admin.Settings || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.6.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * State attribute showing if one of the plugin settings
		 * changed and was not yet saved.
		 *
		 * @since 1.9.0
		 *
		 * @type {boolean}
		 */
		pluginSettingsChanged: false,

		/**
		 * Start the engine. DOM is not ready yet, use only to init something.
		 *
		 * @since 1.6.0
		 */
		init: function() {

			// Do that when DOM is ready.
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.6.0
		 */
		ready: function() {

			app.pageHolder = $( '.wp-mail-smtp-tab-settings' );

			app.settingsForm = $( '.wp-mail-smtp-connection-settings-form' );

			// If there are screen options we have to move them.
			$( '#screen-meta-links, #screen-meta' ).prependTo( '#wp-mail-smtp-header-temp' ).show();

			app.bindActions();
			var removableQueryParams = [ 'sendlayer_quick_connect_result', 'sendlayer_quick_connect_disconnect_result' ];

			if ( ! $( '.wp-mail-smtp-tab-tools-debug-events' ).length ) {
				removableQueryParams.push( 'debug_event_id' );
			}

			app.cleanQueryParams( removableQueryParams );

			app.setJQueryConfirmDefaults();

			// Flyout Menu.
			app.initFlyoutMenu();
		},

		/**
		 * Process all generic actions/events, mostly custom that were fired by our API.
		 *
		 * @since 1.6.0
		 */
		bindActions: function() {

			// Mailer selection.
			$( '.wp-mail-smtp-mailer-image', app.settingsForm ).on( 'click', function() {
				$( this ).parents( '.wp-mail-smtp-mailer' ).find( 'input' ).trigger( 'click' );
			} );

			$( '.wp-mail-smtp-mailer input', app.settingsForm ).on( 'click', function() {
				var $input = $( this );

				if ( $input.prop( 'disabled' ) ) {

					// Educational Popup.
					if ( $input.hasClass( 'educate' ) ) {
						app.education.upgradeMailer( $input );
					}

					return false;
				}

				// Deselect the current mailer.
				$( '.wp-mail-smtp-mailer', app.settingsForm ).removeClass( 'active' );

				// Select the correct one.
				$( this ).parents( '.wp-mail-smtp-mailer' ).addClass( 'active' );

				// Hide all mailers options and display for a currently clicked one.
				$( '.wp-mail-smtp-mailer-option', app.settingsForm ).addClass( 'hidden' ).removeClass( 'active' );
				$( '.wp-mail-smtp-mailer-option-' + $( this ).val(), app.settingsForm ).addClass( 'active' ).removeClass( 'hidden' );
			} );

			app.mailers.sendlayer.bindActions();
			app.mailers.smtp.bindActions();

			// Dismiss Pro banner at the bottom of the page.
			$( '#wp-mail-smtp-pro-banner-dismiss', app.pageHolder ).on( 'click', function() {
				$.ajax( {
					url: ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: {
						action: 'wp_mail_smtp_ajax',
						task: 'pro_banner_dismiss',
						nonce: wp_mail_smtp.nonce
					}
				} )
					.always( function() {
						$( '#wp-mail-smtp-pro-banner', app.pageHolder ).fadeOut( 'fast' );
					} );
			} );

			// Dissmis educational notices for certain mailers.
			$( '.js-wp-mail-smtp-mailer-notice-dismiss', app.settingsForm ).on( 'click', function( e ) {
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
						nonce: wp_mail_smtp.nonce,
						task: 'notice_dismiss',
						notice: $notice.data( 'notice' ),
						mailer: $notice.data( 'mailer' )
					},
					beforeSend: function() {
						$btn.addClass( 'disabled' );
					}
				} )
					.always( function() {
						$notice.fadeOut( 'fast', function() {
							$btn.removeClass( 'disabled' );
						} );
					} );
			} );

			// Show/hide debug output.
			$( '#wp-mail-smtp-debug .error-log-toggle' ).on( 'click', function( e ) {
				e.preventDefault();

				$( '#wp-mail-smtp-debug .error-log' ).slideToggle();
			} );

			// Copy debug output to clipboard.
			$( '#wp-mail-smtp-debug .error-log-copy' ).on( 'click', function( e ) {
				e.preventDefault();

				var $self = $( this );

				// Get error log.
				var $content = $( '#wp-mail-smtp-debug .error-log' );

				// Copy to clipboard.
				if ( ! $content.is( ':visible' ) ) {
					$content.addClass( 'error-log-selection' );
				}
				var range = document.createRange();
				range.selectNode( $content[0] );
				window.getSelection().removeAllRanges();
				window.getSelection().addRange( range );
				document.execCommand( 'Copy' );
				window.getSelection().removeAllRanges();
				$content.removeClass( 'error-log-selection' );

				$self.addClass( 'error-log-copy-copied' );

				setTimeout(
					function() {
						$self.removeClass( 'error-log-copy-copied' );
					},
					1500
				);
			} );

			// Remove mailer connection.
			$( '.js-wp-mail-smtp-provider-remove', app.settingsForm ).on( 'click', function() {
				return confirm( wp_mail_smtp.text_provider_remove );
			} );

			// Copy input text to clipboard.
			$( '.wp-mail-smtp-setting-copy', app.settingsForm ).on( 'click', function( e ) {
				e.preventDefault();

				var target = $( '#' + $( this ).data( 'source_id' ) ).get( 0 );

				target.select();
				document.execCommand( 'Copy' );

				var $buttonIcon = $( this ).find( '.dashicons' );

				$buttonIcon
					.removeClass( 'dashicons-admin-page' )
					.addClass( 'wp-mail-smtp-dashicons-yes-alt-green wp-mail-smtp-success wp-mail-smtp-animate' );

				setTimeout(
					function() {
						$buttonIcon
							.removeClass( 'wp-mail-smtp-dashicons-yes-alt-green wp-mail-smtp-success wp-mail-smtp-animate' )
							.addClass( 'dashicons-admin-page' );
					},
					1000
				);
			} );

			// Notice bar: click on the dissmiss button.
			$( '#wp-mail-smtp-notice-bar' ).on( 'click', '.dismiss', function() {
				var $notice = $( this ).closest( '#wp-mail-smtp-notice-bar' );

				$notice.addClass( 'out' );
				setTimeout(
					function() {
						$notice.remove();
					},
					300
				);

				$.post(
					ajaxurl,
					{
						action: 'wp_mail_smtp_notice_bar_dismiss',
						nonce: wp_mail_smtp.nonce,
					}
				);
			} );

			app.triggerExitNotice();
			app.beforeSaveChecks();

			// Register change event to show/hide plugin supported settings for currently selected mailer.
			$( '.js-wp-mail-smtp-setting-mailer-radio-input', app.settingsForm ).on( 'change', this.processMailerSettingsOnChange );

			// Disable multiple click on the Email Test tab submit button and display a loader icon.
			$( '.wp-mail-smtp-tab-tools-test #email-test-form' ).on( 'submit', function() {
				var $button = $( this ).find( '.wp-mail-smtp-btn' );

				$button.attr( 'disabled', true );
				$button.find( 'span' ).hide();
				$button.find( '.wp-mail-smtp-loading' ).show();
			} );

			$( '#wp-mail-smtp-setting-gmail-one_click_setup_enabled-lite' ).on( 'click', function( e ) {
				e.preventDefault();

				app.education.gmailOneClickSetupUpgrade();
			} );

			$( '#wp-mail-smtp-setting-misc-rate_limit-lite' ).on( 'click', function( e ) {
				e.preventDefault();

				app.education.rateLimitUpgrade();
			} );

			// Confirm before enabling the "Hide Email Delivery Errors" option.
			$( '#wp-mail-smtp-setting-email_delivery_errors_hidden' ).on( 'change', app.confirmHideDeliveryErrors );

			// Obfuscated fields
			$( '.wp-mail-smtp-btn[data-clear-field]' ).on( 'click', function( e ) {
				var $button = $( this );
				var fieldId = $button.attr( 'data-clear-field' );
				var $field = $( `#${fieldId}` );

				$field.prop( 'disabled', false );
				$field.attr( 'name', $field.attr( 'data-name' ) );
				$field.removeAttr( 'value' );
				$field.focus();
				$button.remove();
			} );

			$( '.email_test_tab_removal_notice' ).on( 'click', '.notice-dismiss', function() {
				var $button = $( this );

				$.ajax( {
					url: ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: {
						action: 'wp_mail_smtp_ajax',
						nonce: wp_mail_smtp.nonce,
						task: 'email_test_tab_removal_notice_dismiss',
					},
					beforeSend: function() {
						$button.prop( 'disabled', true );
					},
				} );
			} );

			// Banner: toggle the inline error-log panel.
			$( document ).on(
				'click',
				'.wpms-email-sending-errors-banner__error-log-toggle',
				function( e ) {
					e.preventDefault();

					var $btn   = $( this );
					var $panel = $btn.closest( '.wpms-email-sending-errors-banner' )
						.find( '.wpms-email-sending-errors-banner__error-log' );

					if ( ! $panel.length ) {
						return;
					}

					var showLabel = $btn.data( 'show-label' );
					var hideLabel = $btn.data( 'hide-label' );
					var isHidden  = $panel.prop( 'hidden' ) || ! $panel.is( ':visible' );

					if ( isHidden ) {
						$panel.prop( 'hidden', false ).hide().slideDown( 200 );
						$btn.text( hideLabel );
					} else {
						$panel.slideUp( 200, function() {
							$panel.prop( 'hidden', true );
						} );
						$btn.text( showLabel );
					}
				}
			);

			// Banner: copy the inline error-log panel contents to clipboard.
			$( document ).on(
				'click',
				'.wpms-email-sending-errors-error-log__copy-icon',
				function( e ) {
					e.preventDefault();

					var $btn     = $( this );
					var $panel   = $btn.closest( '.wpms-email-sending-errors-banner__error-log' );
					var $default = $btn.find( '.wpms-email-sending-errors-error-log__copy-icon-default' );
					var $done    = $btn.find( '.wpms-email-sending-errors-error-log__copy-icon-done' );
					var $tooltip = $panel.find( '.wpms-email-sending-errors-error-log__copy-tooltip' );

					if ( ! $panel.length ) {
						return;
					}

					var $content = $panel.clone();
					$content.find( '.wpms-email-sending-errors-error-log__copy-icon, .wpms-email-sending-errors-error-log__copy-tooltip' ).remove();
					$content.find( 'br' ).replaceWith( '\n' );
					var text = $content.text().trim();

					var afterCopy = function() {
						$default.prop( 'hidden', true );
						$done.prop( 'hidden', false );
						$tooltip.prop( 'hidden', false );

						setTimeout(
							function() {
								$default.prop( 'hidden', false );
								$done.prop( 'hidden', true );
								$tooltip.prop( 'hidden', true );
							},
							2000
						);
					};

					var copyViaExecCommand = function() {
						var $tmp = $( '<textarea>' )
							.val( text )
							.css( { position: 'fixed', left: '-9999px', top: 0 } )
							.appendTo( 'body' );
						$tmp[ 0 ].select();
						try {
							document.execCommand( 'copy' );
						} catch ( err ) {

							// Best-effort fallback; failure leaves the clipboard untouched.
						}
						$tmp.remove();
					};

					if ( navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( text ).then( afterCopy, function() {
							copyViaExecCommand();
							afterCopy();
						} );
					} else {
						copyViaExecCommand();
						afterCopy();
					}
				}
			);

			// Dismiss email-sending-errors banner.
			$( document ).on(
				'click',
				'.wpms-email-sending-errors-banner__dismiss',
				function( e ) {
					e.preventDefault();

					var $el          = $( this ).closest( '[data-connection-id]' );
					var connectionId = $el.data( 'connection-id' );

					if ( ! connectionId ) {
						return;
					}

					$.post( wp_mail_smtp.ajax_url, {
						action:        'wp_mail_smtp_email_sending_errors_dismiss',
						nonce:         wp_mail_smtp.nonce,
						connection_id: connectionId
					} ).done( function( res ) {
						if ( res && res.success ) {
							$el.slideUp( 200, function() {
								$el.remove();
							} );
							return;
						}
						app.pluginInstall.showErrorModal( app.extractAjaxError( res, wp_mail_smtp.dismiss_error ) );
					} ).fail( function( xhr ) {
						var res = xhr && xhr.responseJSON ? xhr.responseJSON : null;
						app.pluginInstall.showErrorModal( app.extractAjaxError( res, wp_mail_smtp.dismiss_error ) );
					} );
				}
			);

			// Dismiss one-liner: WP's is-dismissible handles slideUp; we fire the AJAX clear.
			$( document ).on(
				'click',
				'.wpms-email-sending-errors-one-liner .notice-dismiss',
				function() {
					var $el          = $( this ).closest( '[data-connection-id]' );
					var connectionId = $el.data( 'connection-id' );

					if ( ! connectionId ) {
						return;
					}

					$.post( wp_mail_smtp.ajax_url, {
						action:        'wp_mail_smtp_email_sending_errors_dismiss',
						nonce:         wp_mail_smtp.nonce,
						connection_id: connectionId
					} ).done( function( res ) {
						if ( res && res.success ) {
							return;
						}
						app.pluginInstall.showErrorModal( app.extractAjaxError( res, wp_mail_smtp.dismiss_error ) );
					} ).fail( function( xhr ) {
						var res = xhr && xhr.responseJSON ? xhr.responseJSON : null;
						app.pluginInstall.showErrorModal( app.extractAjaxError( res, wp_mail_smtp.dismiss_error ) );
					} );
				}
			);

			// Per-session dismiss for the test-success banner. DOM removal
			// only, no AJAX; banner is regenerated on each successful test send.
			$( document ).on(
				'click',
				'.wpms-test-email-success-banner__dismiss',
				function( e ) {
					e.preventDefault();

					$( this ).closest( '.wpms-test-email-success-banner' ).slideUp( 200, function() {
						$( this ).remove();
					} );
				}
			);

			// Generic plugin install/activate for any opt-in button. Mirrors
			// the About Us page's AJAX flow but works on any markup that
			// carries .js-wp-mail-smtp-plugin-install-btn + status-* class +
			// data-plugin (zip URL initially, swapped to basename after
			// install so a follow-up activate click reuses the same attribute).
			$( document ).on( 'click', '.js-wp-mail-smtp-plugin-install-btn', app.handlePluginInstallBtnClick );

			// Text-link variant of the install/activate flow used by the Pro Tip
			// strip. Same backend as the button handler; different DOM mutations
			// (inline loader + full strip-content swap on success).
			$( document ).on( 'click', '.js-wp-mail-smtp-plugin-install-link', app.handlePluginInstallLinkClick );
		},

		/**
		 * Handle a click on a generic plugin install/activate button.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} e jQuery click event.
		 */
		handlePluginInstallBtnClick: function( e ) {

			e.preventDefault();

			var $btn = $( this );

			// After install+activate the same button doubles as a Setup Now CTA:
			// clicking navigates to the plugin's settings page (status-active +
			// data-settings-url). Keeps a single element and one event hook.
			if ( $btn.hasClass( 'status-active' ) ) {
				var settingsUrl = $btn.attr( 'data-settings-url' );

				if ( settingsUrl ) {
					window.location.href = settingsUrl;
				}

				return;
			}

			if ( $btn.hasClass( 'wp-mail-smtp-btn-loading' ) || $btn.prop( 'disabled' ) ) {
				return;
			}

			var task;
			if ( $btn.hasClass( 'status-download' ) ) {
				task = 'about_plugin_install';
			} else if ( $btn.hasClass( 'status-inactive' ) ) {
				task = 'about_plugin_activate';
			} else {
				return;
			}

			var originalLabel = $btn.html();
			var strings       = wp_mail_smtp.plugin_install;

			$btn.addClass( 'wp-mail-smtp-btn-loading' );
			$btn.prop( 'disabled', true );
			$btn.text( strings.processing );

			app.pluginInstall.installPlugin( {
				plugin: $btn.attr( 'data-plugin' ),
				task:   task,
				onSuccess: function( res ) {

					if ( task === 'about_plugin_install' && res.data && res.data.basename ) {
						$btn.attr( 'data-plugin', res.data.basename );
					}

					// Flash a transient confirmation beside the button (About Us
					// pattern). Backend returns a localized msg for both
					// install-only and install+activate outcomes. .html()
					// (matches About Us) so HTML entities from esc_html__ —
					// `&amp;` for the `&` in "installed & activated" — decode
					// instead of rendering as literal `&amp;`.
					if ( res.data && res.data.msg ) {
						var $actions = $btn.closest( '.wpms-test-email-success-banner__card-actions' );
						var $msg     = $( '<p class="wpms-test-email-success-banner__card-msg" />' ).html( res.data.msg );

						$actions.find( '.wpms-test-email-success-banner__card-msg' ).remove();
						$actions.append( $msg );

						setTimeout( function() {
							$msg.fadeOut( 200, function() {
								$( this ).remove();
							} );
						}, 3000 );
					}

					// Install succeeded but silent activation failed (host may
					// allow install_plugins without activate_plugins).
					if ( task === 'about_plugin_install' && res.data && res.data.is_activated === false ) {
						$btn
							.removeClass( 'wp-mail-smtp-btn-loading status-download' )
							.addClass( 'status-inactive' )
							.prop( 'disabled', false )
							.text( strings.activate );

						return;
					}

					// Activate task lands here too — install-with-activation and
					// standalone activate transition the same way: the button
					// becomes a Setup Now CTA. status-active here means
					// "installed + click navigates to settings" — the click
					// handler reads data-settings-url and changes location.
					var settingsUrl = $btn.attr( 'data-settings-url' );

					$btn
						.removeClass( 'wp-mail-smtp-btn-loading status-download status-inactive' )
						.addClass( 'status-active' );

					if ( settingsUrl ) {
						$btn
							.prop( 'disabled', false )
							.text( strings.setup_now );

						return;
					}

					// Fallback: catalog entry without a settings page URL — leave
					// the button in a disabled "Installed" state.
					$btn
						.prop( 'disabled', true )
						.text( strings.installed );
				},
				onError: function( message ) {
					$btn.removeClass( 'wp-mail-smtp-btn-loading' );
					$btn.prop( 'disabled', false );
					$btn.html( originalLabel );
					app.pluginInstall.showErrorModal( message );
				},
			} );
		},

		/**
		 * Handle a click on a Pro-Tip-strip install/activate text-link. Same
		 * backend as the button handler; different DOM mutations — inline
		 * loader beside the link during install, and on success the initial
		 * strip content is swapped for the pre-rendered success span.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} e jQuery click event.
		 */
		handlePluginInstallLinkClick: function( e ) {

			e.preventDefault();

			var $link = $( this );

			if ( $link.hasClass( 'wp-mail-smtp-link-loading' ) || $link.hasClass( 'status-active' ) ) {
				return;
			}

			var task;
			if ( $link.hasClass( 'status-download' ) ) {
				task = 'about_plugin_install';
			} else if ( $link.hasClass( 'status-inactive' ) ) {
				task = 'about_plugin_activate';
			} else {
				return;
			}

			var $strip        = $link.closest( '.wpms-test-email-pro-tip-strip' );
			var originalLabel = $link.html();
			var strings       = wp_mail_smtp.plugin_install;
			var $loader       = $( '<span class="wpms-test-email-pro-tip-strip__loader wp-mail-smtp-loading-spin wp-mail-smtp-loading-sm" aria-hidden="true"></span>' );

			$link.addClass( 'wp-mail-smtp-link-loading' );
			$link.css( 'pointer-events', 'none' );
			$link.after( $loader );

			app.pluginInstall.installPlugin( {
				plugin: $link.attr( 'data-plugin' ),
				task:   task,
				onSuccess: function( res ) {

					if ( task === 'about_plugin_install' && res.data && res.data.basename ) {
						$link.attr( 'data-plugin', res.data.basename );
					}

					// Install succeeded but activation didn't — transition the
					// link to status-inactive so the user can retry activate.
					if ( task === 'about_plugin_install' && res.data && res.data.is_activated === false ) {
						$loader.remove();

						var activateLabel = strings.activate_with_name.replace( '%s', $link.data( 'plugin-name' ) || '' );

						$link
							.removeClass( 'wp-mail-smtp-link-loading status-download' )
							.addClass( 'status-inactive' )
							.css( 'pointer-events', '' )
							.text( activateLabel );

						return;
					}

					// Activate task lands here too — caller's onSuccess treats
					// install-with-activation and standalone activate the same:
					// hide the initial span, reveal the pre-rendered success span.
					$loader.remove();
					$link.removeClass( 'wp-mail-smtp-link-loading' );
					$link.css( 'pointer-events', '' );

					$strip.find( '.wpms-test-email-pro-tip-strip__initial' ).attr( 'hidden', true );
					$strip.find( '.wpms-test-email-pro-tip-strip__success' ).removeAttr( 'hidden' );
				},
				onError: function( message ) {
					$loader.remove();
					$link.removeClass( 'wp-mail-smtp-link-loading' );
					$link.css( 'pointer-events', '' );
					$link.html( originalLabel );
					app.pluginInstall.showErrorModal( message );
				},
			} );
		},

		education: {
			upgradeMailer: function( $input ) {

				var mailerName = $input.data( 'title' ).trim();

				app.education.upgradeModal(
					wp_mail_smtp.education.upgrade_title.replace( /%name%/g, mailerName ),
					wp_mail_smtp.education.upgrade_content.replace( /%name%/g, mailerName ),
					$input.val()
				);
			},

			gmailOneClickSetupUpgrade: function() {

				app.education.upgradeModal(
					wp_mail_smtp.education.gmail.one_click_setup_upgrade_title,
					wp_mail_smtp.education.gmail.one_click_setup_upgrade_content,
					'gmail-one-click-setup'
				);
			},

			rateLimitUpgrade: function() {

				app.education.upgradeModal(
					wp_mail_smtp.education.rate_limit.upgrade_title,
					wp_mail_smtp.education.rate_limit.upgrade_content,
					'rate-limit-setting'
				);
			},

			upgradeModal: function( title, content, upgradeUrlUtmContent ) {

				$.alert( {
					backgroundDismiss: true,
					escapeKey: true,
					animationBounce: 1,
					type: 'blue',
					closeIcon: true,
					title: title,
					icon: '"></i>' + wp_mail_smtp.education.upgrade_icon_lock + '<i class="',
					content: content,
					boxWidth: '550px',
					onOpenBefore: function() {
						this.$btnc.after( '<div class="discount-note">' + wp_mail_smtp.education.upgrade_bonus + wp_mail_smtp.education.upgrade_doc + '</div>' );
						this.$body.addClass( 'wp-mail-smtp-upgrade-mailer-education-modal' );
					},
					buttons: {
						confirm: {
							text: wp_mail_smtp.education.upgrade_button,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
							action: function() {
								var appendChar = /(\?)/.test( wp_mail_smtp.education.upgrade_url ) ? '&' : '?',
									upgradeURL = wp_mail_smtp.education.upgrade_url + appendChar + 'utm_content=' + encodeURIComponent( upgradeUrlUtmContent );

								window.open( upgradeURL, '_blank' );
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
			sendlayer: {

				/**
				 * Show a SendLayer connect error modal with message and optional error code.
				 *
				 * @since 4.8.0
				 *
				 * @param {string} message   The error message to display.
				 * @param {string} errorCode The dot-notation error code (optional).
				 */
				showConnectError: function( message, errorCode ) {

					var content = '<p>' + $( '<span>' ).text( message ).html() + '</p>';

					if ( errorCode ) {
						content += '<div class="wp-mail-smtp-error-code-box">' +
							'<code>' + $( '<span>' ).text( errorCode ).html() + '</code>' +
							'<button type="button" class="wp-mail-smtp-error-code-box__copy" title="Copy">' +
								'<svg class="wp-mail-smtp-error-code-box__icon-copy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M433.941 65.941l-51.882-51.882A48 48 0 0 0 348.118 0H176c-26.51 0-48 21.49-48 48v48H48c-26.51 0-48 21.49-48 48v320c0 26.51 21.49 48 48 48h224c26.51 0 48-21.49 48-48v-48h80c26.51 0 48-21.49 48-48V99.882a48 48 0 0 0-14.059-33.941zM266 464H54a6 6 0 0 1-6-6V150a6 6 0 0 1 6-6h74v224c0 26.51 21.49 48 48 48h96v42a6 6 0 0 1-6 6zm128-96H182a6 6 0 0 1-6-6V54a6 6 0 0 1 6-6h106v88c0 13.255 10.745 24 24 24h88v202a6 6 0 0 1-6 6zm6-256h-64V48h9.632c1.591 0 3.117.632 4.243 1.757l48.368 48.368a6 6 0 0 1 1.757 4.243V112z"/></svg>' +
								'<svg class="wp-mail-smtp-error-code-box__icon-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="display:none;"><path fill="#0f8a56" d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>' +
							'</button>' +
						'</div>';
					}

					$.alert( {
						backgroundDismiss: true,
						escapeKey: true,
						animationBounce: 1,
						type: 'red',
						closeIcon: true,
						icon: app.getModalIcon( 'times-circle-red' ),
						title: wp_mail_smtp.sendlayer.error_title,
						content: content,
						boxWidth: '450px',
						buttons: {
							confirm: {
								text: wp_mail_smtp.ok_text,
								btnClass: 'wp-mail-smtp-btn wp-mail-smtp-btn-md',
								keys: [ 'enter' ]
							}
						},
						onOpenBefore: function() {
							this.$body.on( 'click', '.wp-mail-smtp-error-code-box__copy', function() {
								var $btn   = $( this );
								var code   = $btn.siblings( 'code' ).text();

								if ( navigator.clipboard ) {
									navigator.clipboard.writeText( code );
								}

								$btn.find( '.wp-mail-smtp-error-code-box__icon-copy' ).hide();
								$btn.find( '.wp-mail-smtp-error-code-box__icon-check' ).show();

								setTimeout( function() {
									$btn.find( '.wp-mail-smtp-error-code-box__icon-check' ).hide();
									$btn.find( '.wp-mail-smtp-error-code-box__icon-copy' ).show();
								}, 2000 );
							} );
						}
					} );
				},

				/**
				 * Start the connect flow via AJAX and handle errors with the modal.
				 *
				 * @since 4.8.0
				 *
				 * @param {object}   connectArgs Extra arguments to pass to the connect endpoint (e.g. { utm_content: '...' }).
				 * @param {Function} onDone      Callback when the request completes (success or error).
				 */
				doConnect: function( connectArgs, onDone ) {

					var self = this;
					var returnUrl    = $( '#wp-mail-smtp-sendlayer-quick-connect-return-url' ).val() || wp_mail_smtp.sendlayer.return_url;
					var connectionId = $( '#wp-mail-smtp-sendlayer-quick-connect-connection-id' ).val() || '';

					$.post( ajaxurl, { // eslint-disable-line camelcase
						action: 'wp_mail_smtp_sendlayer_connect',
						nonce: wp_mail_smtp.sendlayer.connect_nonce,
						return_url: returnUrl, // eslint-disable-line camelcase
						connection_id: connectionId, // eslint-disable-line camelcase
						connect_args: connectArgs || {}, // eslint-disable-line camelcase
					}, function( response ) { // eslint-disable-line complexity
						if ( response.success && response.data.redirect_url ) {
							window.location.href = response.data.redirect_url;
						} else {
							var message   = response.data && response.data.message ? response.data.message : wp_mail_smtp.sendlayer.error_text;
							var errorCode = response.data && response.data.error_code ? response.data.error_code : '';
							self.showConnectError( message, errorCode );
							if ( onDone ) {
								onDone();
							}
						}
					} ).fail( function() {
						self.showConnectError( wp_mail_smtp.sendlayer.server_error, 'plugin.init_connect.ajax_failed' );
						if ( onDone ) {
							onDone();
						}
					} );
				},

				/**
				 * Bind SendLayer-specific UI actions.
				 *
				 * @since 4.8.0
				 */
				bindActions: function() {

					var self = this;

					// Quick Connect button.
					$( '#wp-mail-smtp-sendlayer-connect-btn' ).on( 'click', function( e ) {
						e.preventDefault();

						var $btn = $( this );
						$btn.addClass( 'wp-mail-smtp-btn-loading' );

						self.doConnect( { utm_content: 'Plugin Settings - Quick Connect' }, function() { // eslint-disable-line camelcase
							$btn.removeClass( 'wp-mail-smtp-btn-loading' );
						} );
					} );

					// Change domain link (same flow as Quick Connect).
					$( '#wp-mail-smtp-sendlayer-change-domain' ).on( 'click', function( e ) {
						e.preventDefault();

						var $link = $( this );
						var originalText = $link.text();
						$link.text( wp_mail_smtp.sendlayer.connecting_text );

						self.doConnect( { utm_content: 'Plugin Settings - Quick Connect Change Domain' }, function() { // eslint-disable-line camelcase
							$link.text( originalText );
						} );
					} );

					// Show Quick Connect when API key is removed.
					$( '.wp-mail-smtp-btn[data-clear-field="wp-mail-smtp-setting-sendlayer-api_key"]' ).on( 'click', function() {
						$( '#wp-mail-smtp-setting-row-sendlayer-connect' ).show();
					} );

					// Show API key field and remove the toggle link.
					$( '#wp-mail-smtp-sendlayer-show-api-key' ).on( 'click', function( e ) {
						e.preventDefault();
						$( this ).closest( '.wp-mail-smtp-setting-row' ).remove();
						$( '#wp-mail-smtp-setting-row-sendlayer-api_key' ).show();
					} );

					// SendLayer education banner: Setup button (same flow as Quick Connect).
					$( '#wp-mail-smtp-sendlayer-education-connect-btn' ).on( 'click', function( e ) {
						e.preventDefault();

						var $btn = $( this );
						$btn.addClass( 'wp-mail-smtp-btn-loading' );

						self.doConnect( { utm_content: 'Plugin Settings - Quick Connect Education' }, function() { // eslint-disable-line camelcase
							$btn.removeClass( 'wp-mail-smtp-btn-loading' );
						} );
					} );

					// SendLayer Quick Connect button — supports per-button data-mode
					// (e.g. backup-mailer mode from the test email success banner).
					$( document ).on( 'click', '.js-wp-mail-smtp-sendlayer-quick-connect-btn', function( e ) {
						e.preventDefault();

						var $btn = $( this );

						if ( $btn.hasClass( 'wp-mail-smtp-btn-loading' ) ) {
							return;
						}

						$btn.addClass( 'wp-mail-smtp-btn-loading' );

						var connectArgs = {
							utm_content: $btn.data( 'utm-content' ), // eslint-disable-line camelcase
						};

						var btnMode = $btn.data( 'mode' );

						if ( btnMode ) {
							connectArgs.mode = btnMode;
						}

						self.doConnect( connectArgs, function() {
							$btn.removeClass( 'wp-mail-smtp-btn-loading' );
						} );
					} );

					// Inline SendLayer Quick Connect link (same flow as Quick Connect).
					$( document ).on( 'click', '.js-wp-mail-smtp-sendlayer-quick-connect-link', function( e ) {
						e.preventDefault();

						var $link = $( this );

						if ( $link.next( '.wp-mail-smtp-inline-loader' ).length ) {
							return;
						}

						var $loader = $( '<span class="wp-mail-smtp-inline-loader" aria-hidden="true"></span>' );
						$link.after( $loader );

						self.doConnect( { utm_content: 'Email Sending Errors Banner - Quick Connect' }, function() { // eslint-disable-line camelcase
							$loader.remove();
						} );
					} );

					// SendLayer education banner: Dismiss.
					$( '.js-wp-mail-smtp-sendlayer-education-dismiss' ).on( 'click', function( e ) {
						e.preventDefault();

						var $banner = $( this ).closest( '.wp-mail-smtp-sendlayer-education' );

						$banner.fadeOut( 200 );

						$.post( ajaxurl, {
							action: 'wp_mail_smtp_ajax',
							task: 'notice_dismiss',
							notice: 'sendlayer_education',
							nonce: wp_mail_smtp.nonce,
						} );
					} );
				}
			},
			smtp: {
				bindActions: function() {

					// Hide SMTP-specific user/pass when Auth disabled.
					$( '#wp-mail-smtp-setting-smtp-auth' ).on( 'change', function() {
						$( '#wp-mail-smtp-setting-row-smtp-user, #wp-mail-smtp-setting-row-smtp-pass' ).toggleClass( 'inactive' );
					} );

					// Port default values based on encryption type.
					$( '#wp-mail-smtp-setting-row-smtp-encryption input' ).on( 'change', function() {

						var $input = $( this ),
							$smtpPort = $( '#wp-mail-smtp-setting-smtp-port', app.settingsForm );

						if ( 'tls' === $input.val() ) {
							$smtpPort.val( '587' );
							$( '#wp-mail-smtp-setting-row-smtp-autotls' ).addClass( 'inactive' );
						} else if ( 'ssl' === $input.val() ) {
							$smtpPort.val( '465' );
							$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
						} else {
							$smtpPort.val( '25' );
							$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
						}
					} );
				}
			}
		},

		/**
		 * Plugin install/activate utilities. Shared by the tile-button and
		 * Pro-Tip-strip handlers; the handlers own DOM mutation, these
		 * helpers only normalize the AJAX roundtrip and error UI.
		 *
		 * @since 4.9.0
		 */
		pluginInstall: {

			/**
			 * POST to the shared About-tab plugin install/activate AJAX
			 * endpoint and dispatch to caller-supplied success/error
			 * callbacks.
			 *
			 * @since 4.9.0
			 *
			 * @param {object}   options          Options.
			 * @param {string}   options.plugin   data-plugin value to send.
			 * @param {string}   options.task     'about_plugin_install' or 'about_plugin_activate'.
			 * @param {Function} options.onSuccess Receives the parsed response.
			 * @param {Function} options.onError   Receives (message, rawResponse|null).
			 */
			installPlugin: function( options ) {

				$.post( wp_mail_smtp.ajax_url, {
					action: 'wp_mail_smtp_ajax',
					task:   options.task,
					nonce:  wp_mail_smtp.nonce,
					plugin: options.plugin,
				} ).done( function( res ) {
					if ( res && res.success ) {
						options.onSuccess( res );
						return;
					}
					options.onError( app.extractAjaxError( res, wp_mail_smtp.plugin_install.error ), res );
				} ).fail( function( xhr ) {
					var res = xhr && xhr.responseJSON ? xhr.responseJSON : null;
					options.onError( app.extractAjaxError( res, wp_mail_smtp.plugin_install.error ), res );
				} );
			},

			/**
			 * Show a jconfirm error modal for a plugin install/activate
			 * failure.
			 *
			 * @since 4.9.0
			 *
			 * @param {string} message The localized error message body.
			 */
			showErrorModal: function( message ) {

				var strings = wp_mail_smtp.plugin_install;

				$.confirm( {
					backgroundDismiss: false,
					escapeKey:         true,
					animationBounce:   1,
					closeIcon:         true,
					type:              'red',
					title:             strings.error_title,
					content:           message,
					buttons: {
						confirm: {
							text:     strings.btn_ok,
							btnClass: 'btn-confirm',
							keys:     [ 'enter' ],
						},
					},
				} );
			},
		},

		/**
		 * Extract a user-facing message from a wp_send_json_error() response.
		 *
		 * Covers the standard shape `{ success: false, data: 'message' }`
		 * (plain string from `wp_send_json_error( $message )`). Empty or
		 * non-string payloads (network failures, WP_Error arrays whose codes
		 * are usually cryptic) fall back to the caller-supplied default.
		 *
		 * @since 4.9.0
		 *
		 * @param {object|null} response       Parsed AJAX response, or null on network failure.
		 * @param {string}      defaultMessage Fallback when the response has no usable message.
		 *
		 * @returns {string} The extracted message, or the default when none is usable.
		 */
		extractAjaxError: function( response, defaultMessage ) {

			if ( response && typeof response.data === 'string' && response.data.length ) {
				return response.data;
			}

			return defaultMessage;
		},

		/**
		 * Returns prepared modal icon HTML for a jQuery Confirm dialog.
		 *
		 * @since 4.9.0
		 *
		 * @param {string} icon The icon name from /assets/images/font-awesome/ to use in the modal.
		 *
		 * @returns {string} Modal icon HTML.
		 */
		getModalIcon: function( icon ) {

			return '"></i><img src="' + wp_mail_smtp.plugin_url + '/assets/images/font-awesome/' + icon + '.svg" style="width: 40px; height: 40px;" alt=""><i class="';
		},

		/**
		 * Confirm before enabling the "Hide Email Delivery Errors" option.
		 *
		 * Enabling this suppresses the warnings that surface failed email
		 * delivery, so it requires explicit confirmation. Cancelling reverts
		 * the toggle to its previous (unchecked) state. Turning the option
		 * off is never prompted.
		 *
		 * @since 4.9.0
		 *
		 * @param {Event} e The change event.
		 */
		confirmHideDeliveryErrors: function( e ) {

			var $toggle = $( e.target );

			// Only confirm when the option is being enabled.
			if ( ! $toggle.prop( 'checked' ) ) {
				return;
			}

			var strings = wp_mail_smtp.hide_delivery_errors;

			$.confirm( {
				backgroundDismiss: false,
				escapeKey:         true,
				animationBounce:   1,
				type:              'orange',
				icon:              app.getModalIcon( 'exclamation-circle-solid-orange' ),
				title:             strings.title,
				content:           strings.content,
				boxWidth:          '550px',
				buttons: {
					confirm: {
						text:     strings.confirm_button,
						btnClass: 'btn-confirm',
						keys:     [ 'enter' ],
					},
					cancel: {
						text:     strings.cancel_button,
						btnClass: 'btn-cancel',
						action: function() {
							$toggle.prop( 'checked', false );
						},
					},
				},
			} );
		},

		/**
		 * Exit notice JS code when plugin settings are not saved.
		 *
		 * @since 1.9.0
		 */
		triggerExitNotice: function() {

			var $settingPages = $( '.wp-mail-smtp-page-general' );

			// Display an exit notice, if settings are not saved.
			$( window ).on( 'beforeunload', function() {
				if ( app.pluginSettingsChanged ) {
					return wp_mail_smtp.text_settings_not_saved;
				}
			} );

			// Set settings changed attribute, if any input was changed.
			$( ':input:not( #wp-mail-smtp-setting-license-key, .wp-mail-smtp-not-form-input, #wp-mail-smtp-setting-gmail-one_click_setup_enabled, #wp-mail-smtp-setting-outlook-one_click_setup_enabled )', $settingPages ).on( 'change', function() {
				app.pluginSettingsChanged = true;
			} );

			// Clear the settings changed attribute, if the settings are about to be saved.
			$( 'form', $settingPages ).on( 'submit', function() {
				app.pluginSettingsChanged = false;
			} );
		},

		/**
		 * Perform any checks before the settings are saved.
		 *
		 * Checks:
		 * - warn users if they try to save the settings with the default (PHP) mailer selected.
		 *
		 * @since 2.1.0
		 */
		beforeSaveChecks: function() {

			app.settingsForm.on( 'submit', function() {
				if ( $( '.wp-mail-smtp-mailer input:checked', app.settingsForm ).val() === 'mail' ) {
					var $thisForm = $( this );

					$.alert( {
						backgroundDismiss: false,
						escapeKey: false,
						animationBounce: 1,
						type: 'orange',
						icon: '"></i><img src="' + wp_mail_smtp.plugin_url + '/assets/images/font-awesome/exclamation-circle-solid-orange.svg" style="width: 40px; height: 40px;" alt="' + wp_mail_smtp.default_mailer_notice.icon_alt + '"><i class="',
						title: wp_mail_smtp.default_mailer_notice.title,
						content: wp_mail_smtp.default_mailer_notice.content,
						boxWidth: '550px',
						buttons: {
							confirm: {
								text: wp_mail_smtp.default_mailer_notice.save_button,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
								action: function() {
									$thisForm.off( 'submit' ).trigger( 'submit' );
								}
							},
							cancel: {
								text: wp_mail_smtp.default_mailer_notice.cancel_button,
								btnClass: 'btn-cancel',
							},
						}
					} );

					return false;
				}
			} );
		},

		/**
		 * On change callback for showing/hiding plugin supported settings for currently selected mailer.
		 *
		 * @since 2.3.0
		 */
		processMailerSettingsOnChange: function() {

			var selectedMailer = $( this ).val();
			var mailerSupportedSettings = wp_mail_smtp.all_mailers_supports[ selectedMailer ];

			for ( var setting in mailerSupportedSettings ) {
				// eslint-disable-next-line no-prototype-builtins
				if ( mailerSupportedSettings.hasOwnProperty( setting ) ) {
					$( '.js-wp-mail-smtp-setting-' + setting, app.settingsForm ).toggle( mailerSupportedSettings[ setting ] );
				}
			}

			// Special case: "from email" (group settings).
			var $mainSettingInGroup = $( '.js-wp-mail-smtp-setting-from_email' );
			var $quickConnectFromEmail = $( '#wp-mail-smtp-setting-row-sendlayer-quick-connect-from_email' );
			var isQuickConnectActive = selectedMailer === 'sendlayer' && $quickConnectFromEmail.length > 0;

			$mainSettingInGroup.toggle(
				! isQuickConnectActive && ( mailerSupportedSettings[ 'from_email' ] || mailerSupportedSettings[ 'from_email_force' ] )
			);

			// Toggle quick connect From Email field and disable inputs when hidden
			// to prevent split fields from being submitted for other mailers.
			$quickConnectFromEmail.toggle( isQuickConnectActive );
			$quickConnectFromEmail.find( 'input' ).prop( 'disabled', ! isQuickConnectActive );

			// Special case: "from name" (group settings).
			$mainSettingInGroup = $( '.js-wp-mail-smtp-setting-from_name' );

			$mainSettingInGroup.toggle(
				mailerSupportedSettings['from_name'] || mailerSupportedSettings['from_name_force']
			);
		},

		/**
		 * Remove transient query params from the URL without a page reload.
		 *
		 * Useful for cleaning up one-time result params after they have been
		 * read and rendered on the current page load.
		 *
		 * @since 4.8.0
		 *
		 * @param {string[]} params List of query parameter names to remove.
		 */
		cleanQueryParams: function( params ) {

			try {
				var url   = new URL( window.location.href );
				var dirty = false;

				params.forEach( function( param ) {
					if ( url.searchParams.has( param ) ) {
						url.searchParams.delete( param );
						dirty = true;
					}
				} );

				if ( dirty ) {
					window.history.replaceState( {}, document.title, url.toString() );
				}
			} catch ( e ) {} // eslint-disable-line no-empty
		},

		/**
		 * Set jQuery-Confirm default options.
		 *
		 * @since 2.9.0
		 */
		setJQueryConfirmDefaults: function() {

			jconfirm.defaults = {
				typeAnimated: false,
				draggable: false,
				animateFromElement: false,
				theme: 'modern',
				boxWidth: '400px',
				useBootstrap: false
			};
		},

		/**
		 * Flyout Menu (quick links).
		 *
		 * @since 3.0.0
		 */
		initFlyoutMenu: function() {

			// Flyout Menu Elements.
			var $flyoutMenu = $( '#wp-mail-smtp-flyout' );

			if ( $flyoutMenu.length === 0 ) {
				return;
			}

			var $head = $flyoutMenu.find( '.wp-mail-smtp-flyout-head' );

			// Click on the menu head icon.
			$head.on( 'click', function( e ) {
				e.preventDefault();
				$flyoutMenu.toggleClass( 'opened' );
			} );

			// Page elements and other values.
			var $wpfooter = $( '#wpfooter' );

			if ( $wpfooter.length === 0 ) {
				return;
			}

			var $overlap = $(
				'.wp-mail-smtp-page-logs-archive, ' +
				'.wp-mail-smtp-tab-tools-action-scheduler, ' +
				'.wp-mail-smtp-page-reports, ' +
				'.wp-mail-smtp-tab-tools-debug-events, ' +
				'.wp-mail-smtp-tab-connections'
			);

			// Hide menu if scrolled down to the bottom of the page or overlap some critical controls.
			$( window ).on( 'resize scroll', _.debounce( function() {

				var wpfooterTop = $wpfooter.offset().top,
					wpfooterBottom = wpfooterTop + $wpfooter.height(),
					overlapBottom = $overlap.length > 0 ? $overlap.offset().top + $overlap.height() + 85 : 0,
					viewTop = $( window ).scrollTop(),
					viewBottom = viewTop + $( window ).height();

				if ( wpfooterBottom <= viewBottom && wpfooterTop >= viewTop && overlapBottom > viewBottom ) {
					$flyoutMenu.addClass( 'out' );
				} else {
					$flyoutMenu.removeClass( 'out' );
				}
			}, 50 ) );

			$( window ).trigger( 'scroll' );
		}
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPMailSMTP.Admin.Settings.init();
