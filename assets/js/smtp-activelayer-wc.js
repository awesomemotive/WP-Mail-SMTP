/* global wp_mail_smtp_activelayer_wc */

'use strict';

( function( $ ) {

	const l10n = wp_mail_smtp_activelayer_wc;

	const WPMailSMTPActiveLayerWC = {

		/**
		 * Bind the section controls.
		 *
		 * @since 4.9.0
		 */
		init: function() {

			$( document )
				.on( 'click', '.wp-mail-smtp-activelayer-button', WPMailSMTPActiveLayerWC.onClick )
				.on( 'click', '.wp-mail-smtp-activelayer-wc-dismiss', WPMailSMTPActiveLayerWC.onDismiss );
		},

		/**
		 * Handle a click on the section button based on its data-action.
		 *
		 * @since 4.9.0
		 *
		 * @param {Event} e Click event.
		 */
		onClick: function( e ) {

			e.preventDefault();

			const $btn   = $( this ),
				action = $btn.attr( 'data-action' );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			if ( action === 'goto-url' ) {
				window.open( $btn.attr( 'data-url' ), '_blank', 'noopener' );
				return;
			}

			if ( action === 'goto-settings' ) {
				window.location.href = $btn.attr( 'data-url' );
				return;
			}

			if ( action === 'install' ) {
				WPMailSMTPActiveLayerWC.run( $btn, 'about_plugin_install', l10n.download_url, l10n.installing );
			} else if ( action === 'activate' ) {
				WPMailSMTPActiveLayerWC.run( $btn, 'about_plugin_activate', l10n.plugin, l10n.activating );
			}
		},

		/**
		 * Run an install or activate task through the shared plugin dispatcher.
		 *
		 * @since 4.9.0
		 *
		 * @param {jQuery} $btn           The section button.
		 * @param {string} task           The dispatcher task name.
		 * @param {string} plugin         The download URL (install) or basename (activate).
		 * @param {string} processingText Button text while the request runs.
		 */
		run: function( $btn, task, plugin, processingText ) {

			$btn.addClass( 'disabled' ).text( processingText );

			$.post(
				l10n.ajax_url,
				{
					action: 'wp_mail_smtp_ajax',
					task: task,
					plugin: plugin,
					nonce: l10n.nonce
				}
			)
				.done( function( res ) {

					if ( task === 'about_plugin_install' ) {

						if ( res && res.success && res.data && res.data.is_activated ) {
							WPMailSMTPActiveLayerWC.toSettings( $btn );
							return;
						}

						// Installed but not auto-activated: try activating.
						if ( res && res.success ) {
							WPMailSMTPActiveLayerWC.run( $btn, 'about_plugin_activate', l10n.plugin, l10n.activating );
							return;
						}

						WPMailSMTPActiveLayerWC.fail( $btn, task );
						return;
					}

					if ( res && res.success ) {
						WPMailSMTPActiveLayerWC.toSettings( $btn );
						return;
					}

					WPMailSMTPActiveLayerWC.fail( $btn, task );
				} )
				.fail( function() {

					WPMailSMTPActiveLayerWC.fail( $btn, task );
				} );
		},

		/**
		 * Turn the button into the "Connect Your Free Account" CTA.
		 *
		 * @since 4.9.0
		 *
		 * @param {jQuery} $btn The section button.
		 */
		toSettings: function( $btn ) {

			$btn
				.removeClass( 'disabled' )
				.attr( 'data-action', 'goto-settings' )
				.attr( 'data-url', l10n.settings_url )
				.text( l10n.goto_settings );
		},

		/**
		 * Show an inline error and fall back to the WordPress.org link.
		 *
		 * @since 4.9.0
		 *
		 * @param {jQuery} $btn The section button.
		 * @param {string} task The dispatcher task that failed.
		 */
		fail: function( $btn, task ) {

			const msg = task === 'about_plugin_install' ? l10n.error_install : l10n.error_activate;

			$btn
				.removeClass( 'disabled' )
				.attr( 'data-action', 'goto-url' )
				.attr( 'data-url', l10n.wporg_url )
				.text( l10n.get_activelayer );

			if ( ! $btn.siblings( '.wp-mail-smtp-activelayer-error' ).length ) {
				$btn.after( '<p class="wp-mail-smtp-activelayer-error">' + msg + '</p>' );
			}
		},

		/**
		 * Dismiss the section for the current user.
		 *
		 * @since 4.9.0
		 */
		onDismiss: function() {

			$.post(
				l10n.ajax_url,
				{
					action: 'wp_mail_smtp_activelayer_wc_dismiss',
					nonce: l10n.nonce
				}
			);

			// Remove optimistically; persistence failures only resurface the section on reload.
			$( this ).closest( '.wpms-activelayer-wc' ).remove();
		}
	};

	$( WPMailSMTPActiveLayerWC.init );

}( jQuery ) );
