'use strict';

var WPMailSMTP = window.WPMailSMTP || {};
WPMailSMTP.Admin = WPMailSMTP.Admin || {};

/**
 * WP Mail SMTP Admin area AI MCP module.
 *
 * @since 4.9.0
 */
WPMailSMTP.Admin.AiMcp = WPMailSMTP.Admin.AiMcp || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 4.9.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine. DOM is not ready yet, use only to init something.
		 *
		 * @since 4.9.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 4.9.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Bind events.
		 *
		 * @since 4.9.0
		 */
		events: function() {

			$( document )
				.on( 'click', '.wp-mail-smtp-ai-mcp-wpvibe-button[data-action="install"]', app.onInstallClick )
				.on( 'click', '.wp-mail-smtp-ai-mcp-wpvibe-button[data-action="activate"]', app.onActivateClick );
		},

		/**
		 * POST to the WP Mail SMTP admin-ajax dispatcher with the nonce attached.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} extra Task-specific fields merged into the request.
		 *
		 * @returns {object} The jQuery jqXHR promise.
		 */
		post: function( extra ) {

			var data = $.extend( {
				action: 'wp_mail_smtp_ajax',
				nonce: window.wp_mail_smtp.nonce,
			}, extra || {} );

			return $.post( window.wp_mail_smtp.ajax_url, data );
		},

		/**
		 * Install button: install & activate WPVibe, then reload.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} event The click event.
		 */
		onInstallClick: function( event ) {

			event.preventDefault();

			app.runInstallerTask( $( event.currentTarget ), 'about_plugin_install', 'ai_mcp' );
		},

		/**
		 * Activate button: activate WPVibe, then reload.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} event The click event.
		 */
		onActivateClick: function( event ) {

			event.preventDefault();

			app.runInstallerTask( $( event.currentTarget ), 'about_plugin_activate', 'ai_mcp' );
		},

		/**
		 * Run an install/activate task, then reload on success or surface an
		 * inline error on failure. A reload lets PHP re-render the next state's
		 * CTA. WP returns logical failures with HTTP 200 + success:false, so a
		 * failed task lands in .done and must be checked explicitly.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} $button Button element that was clicked.
		 * @param {string} task    AJAX task — install or activate.
		 * @param {string} source  Originating screen sent with the request.
		 */
		runInstallerTask: function( $button, task, source ) {

			if ( WPMailSMTP.Admin.Settings.pluginInstall.takeManualRoute( $button ) ) {
				return;
			}

			app.setLoading( $button );

			app.post( { task: task, plugin: $button.data( 'plugin' ), source: source } )
				.done( function( response ) {

					if ( response && response.success ) {
						window.location.reload();
						return;
					}

					// Install partially succeeded (installed but activation failed):
					// the plugin state changed, so reload to render the activate CTA.
					if ( task === 'about_plugin_install' && response && response.data && response.data.basename ) {
						window.location.reload();
						return;
					}

					app.showError( $button, response, task );
				} )
				.fail( function( xhr ) {

					app.showError( $button, xhr ? xhr.responseJSON : null, task );
				} );
		},

		/**
		 * Show the loading spinner on the button.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} $button Button element.
		 */
		setLoading: function( $button ) {

			$button
				.addClass( 'wp-mail-smtp-btn-loading' )
				.prop( 'disabled', true );
		},

		/**
		 * Restore the button to its initial state and explain what went wrong.
		 *
		 * @since 4.9.0
		 *
		 * @param {object}      $button  Button element.
		 * @param {object|null} response AJAX error payload, when available.
		 * @param {string}      task     AJAX task that failed.
		 */
		showError: function( $button, response, task ) {

			$button
				.removeClass( 'wp-mail-smtp-btn-loading' )
				.prop( 'disabled', false );

			var settings  = WPMailSMTP.Admin.Settings;
			var manualUrl = settings.extractAjaxManualUrl( response );

			settings.pluginInstall.offerManualRoute( $button, manualUrl );

			settings.pluginInstall.showErrorModal(
				settings.extractAjaxError( response, settings.pluginInstall.fallbackError( task === 'about_plugin_activate' ) ),
				manualUrl
			);
		},
	};

	return app;
}( document, window, jQuery ) );

WPMailSMTP.Admin.AiMcp.init();
