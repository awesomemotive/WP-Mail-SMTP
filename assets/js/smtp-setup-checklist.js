/* global wp_mail_smtp, WPMailSMTP */

/**
 * WP Mail SMTP Setup Checklist.
 *
 * @since 4.10.0
 */

'use strict';

const WPMailSMTPSetupChecklist = window.WPMailSMTPSetupChecklist || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 4.10.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * Collapsible block selector (checklist sections and promo cards).
		 *
		 * @since 4.10.0
		 *
		 * @type {string}
		 */
		blockSelector: '.wpms-setup-checklist-section, .wpms-setup-checklist-promo',

		/**
		 * Block header selector.
		 *
		 * @since 4.10.0
		 *
		 * @type {string}
		 */
		headerSelector: '.wpms-setup-checklist-section__header, .wpms-setup-checklist-promo__header',

		/**
		 * Collapse toggle selector.
		 *
		 * @since 4.10.0
		 *
		 * @type {string}
		 */
		toggleSelector: '.wpms-setup-checklist-section__toggle, .wpms-setup-checklist-promo__toggle',

		/**
		 * Initialize.
		 *
		 * @since 4.10.0
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 4.10.0
		 */
		ready() {
			app.events();
		},

		/**
		 * Bind events.
		 *
		 * @since 4.10.0
		 */
		events() {
			$( document )
				.on( 'click', app.headerSelector, app.toggleBlock )
				.on( 'click', '.wpms-setup-checklist-dismiss', app.confirmDismiss )
				.on( 'click', '.js-wpms-plugin-install, .wpms-setup-checklist-item__button[data-action="install-plugin"], .wpms-setup-checklist-item__button[data-action="activate-plugin"]', app.installPlugin )
				.on( 'click', '.wpms-setup-checklist-item__button[data-action="import-settings"]', app.importSettings )
				.on( 'click', '.wpms-setup-checklist-item__button[data-action="usage-tracking-optin"]', app.usageTrackingOptin )
				.on( 'click', '.wpms-setup-checklist-item__button[data-action="subscribe"]', app.subscribeNewsletter );
		},

		/**
		 * Collapse or expand a block when its header is clicked.
		 *
		 * @since 4.10.0
		 */
		toggleBlock() {
			const $header = $( this );
			const $block = $header.closest( app.blockSelector );

			if ( ! $block.length ) {
				return;
			}

			const isCollapsed = $block.toggleClass( 'is-collapsed' ).hasClass( 'is-collapsed' );

			$header
				.find( app.toggleSelector )
				.attr( 'aria-expanded', isCollapsed ? 'false' : 'true' );
		},

		/**
		 * Confirm dismissing the checklist, then dismiss it.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} event Click event.
		 */
		confirmDismiss( event ) {
			event.preventDefault();

			const strings = ( window.wp_mail_smtp_setup_checklist || {} ).dismiss || {};

			$.confirm( {
				title: strings.title,
				content: strings.content,
				icon: 'wpms:icon-[fa6-solid--circle-exclamation] wpms:w-[40px] wpms:h-[40px] wpms:inline-block',
				type: 'orange',
				buttons: {
					confirm: {
						text: strings.confirm,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action() {
							app.dismiss();
						},
					},
					cancel: {
						text: strings.cancel,
						keys: [ 'esc' ],
					},
				},
			} );
		},

		/**
		 * Send the dismiss request and redirect on success.
		 *
		 * @since 4.10.0
		 */
		dismiss() {
			const l10n = window.wp_mail_smtp_setup_checklist || {};

			$.post( l10n.ajax_url, {
				action: 'wp_mail_smtp_setup_checklist_dismiss',
				nonce: wp_mail_smtp.nonce,
			} )
				.done( ( response ) => {
					if ( response && response.success && response.data && response.data.redirect_url ) {
						window.location = response.data.redirect_url;

						return;
					}

					app.dismissFailed();
				} )
				.fail( app.dismissFailed );
		},

		/**
		 * Warn the user when the dismiss request fails (nonce, capability, or network).
		 *
		 * @since 4.10.0
		 */
		dismissFailed() {
			const strings = ( window.wp_mail_smtp_setup_checklist || {} ).dismiss || {};

			app.showError( strings.error );
		},

		/**
		 * Install or activate a plugin in place via the checklist's single install endpoint.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} event Click event.
		 */
		installPlugin( event ) {
			event.preventDefault();

			const $link = $( this );

			if ( WPMailSMTP.Admin.Settings.pluginInstall.takeManualRoute( $link ) ) {
				return;
			}

			const action = $link.attr( 'data-action' );

			if ( ( action !== 'install-plugin' && action !== 'activate-plugin' ) || $link.hasClass( 'is-loading' ) ) {
				return;
			}

			const l10n = window.wp_mail_smtp_setup_checklist || {};
			const strings = app.getStrings();
			const originalText = $link.text();

			$link
				.addClass( 'is-loading' )
				.text( action === 'activate-plugin' ? strings.activating : strings.installing );

			$.post( l10n.ajax_url, {
				action: 'wp_mail_smtp_setup_checklist_install_plugin',
				nonce: wp_mail_smtp.nonce,
				plugin: $link.attr( 'data-plugin' ) || '',
				source: 'setup_checklist',
			} )
				.done( ( response ) => {
					if ( ! response || ! response.success ) {
						app.installPluginFailed( $link, response, originalText );

						return;
					}

					if ( $link.attr( 'data-reload' ) ) {
						window.location.reload();

						return;
					}

					$link.removeClass( 'is-loading' );

					if ( response.data && ! response.data.activated ) {
						app.markInstalledInactive( $link );

						return;
					}

					app.markInstalled( $link );
				} )
				.fail( ( jqXHR ) => app.installPluginFailed( $link, jqXHR && jqXHR.responseJSON, originalText ) );
		},

		/**
		 * Revert the link to its original label and surface the error when a request fails.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $link        The clicked link.
		 * @param {object} response     AJAX error payload, when available.
		 * @param {string} originalText The link label to restore.
		 */
		installPluginFailed( $link, response, originalText ) {
			const settings  = WPMailSMTP.Admin.Settings;
			const manualUrl = settings.extractAjaxManualUrl( response );

			$link
				.removeClass( 'is-loading' )
				.text( originalText );

			settings.pluginInstall.offerManualRoute( $link, manualUrl );

			app.showError( settings.extractAjaxError( response, app.getStrings().error ), manualUrl );
		},

		/**
		 * Swap a promo link into its installed-but-inactive state, which is where an
		 * install by a user who may not activate leaves the plugin.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $link The link to update.
		 */
		markInstalledInactive( $link ) {
			$link
				.attr( 'data-action', 'activate-plugin' )
				.text( app.getStrings().activate || '' );
		},

		/**
		 * Swap a promo link into its installed state.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $link The link to update.
		 */
		markInstalled( $link ) {
			const strings = app.getStrings();

			$link
				.addClass( 'is-active' )
				.attr( 'data-action', 'active' )
				.removeAttr( 'data-plugin' )
				.html( ( strings.installed || '' ) + '<i class="wpms:icon-[fa6-regular--circle-check] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>' );
		},

		/**
		 * Import settings from the plugin selected in this item's select.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} event Click event.
		 */
		importSettings( event ) {
			event.preventDefault();

			const $button = $( this );

			if ( $button.hasClass( 'wp-mail-smtp-btn-loading' ) ) {
				return;
			}

			const plugin = $button.closest( '.wpms-setup-checklist-item' ).find( '.js-wpms-setup-checklist-import-select' ).val() || '';

			$button.addClass( 'wp-mail-smtp-btn-loading' );

			$.post( wp_mail_smtp.ajax_url, {
				action: 'wp_mail_smtp_setup_checklist_import_settings',
				nonce: wp_mail_smtp.nonce,
				plugin,
			} )
				.done( ( response ) => {
					if ( ! response || ! response.success ) {
						app.itemActionFailed( $button, response );

						return;
					}

					window.location.reload();
				} )
				.fail( ( jqXHR ) => app.itemActionFailed( $button, jqXHR && jqXHR.responseJSON ) );
		},

		/**
		 * Opt into usage tracking from the checklist.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} event Click event.
		 */
		usageTrackingOptin( event ) {
			event.preventDefault();

			const $button = $( this );

			if ( $button.hasClass( 'wp-mail-smtp-btn-loading' ) ) {
				return;
			}

			$button.addClass( 'wp-mail-smtp-btn-loading' );

			$.post( wp_mail_smtp.ajax_url, {
				action: 'wp_mail_smtp_setup_checklist_usage_tracking_optin',
				nonce: wp_mail_smtp.nonce,
			} )
				.done( ( response ) => {
					if ( ! response || ! response.success ) {
						app.itemActionFailed( $button, response );

						return;
					}

					window.location.reload();
				} )
				.fail( ( jqXHR ) => app.itemActionFailed( $button, jqXHR && jqXHR.responseJSON ) );
		},

		/**
		 * Subscribe to Smart Recommendations from the checklist.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} event Click event.
		 */
		subscribeNewsletter( event ) {
			event.preventDefault();

			const $button = $( this );

			if ( $button.hasClass( 'wp-mail-smtp-btn-loading' ) ) {
				return;
			}

			const email = $button.closest( '.wpms-setup-checklist-item' ).find( '.js-wpms-setup-checklist-newsletter-email' ).val() || '';

			$button.addClass( 'wp-mail-smtp-btn-loading' );

			$.post( wp_mail_smtp.ajax_url, {
				action: 'wp_mail_smtp_setup_checklist_subscribe',
				nonce: wp_mail_smtp.nonce,
				email,
			} )
				.done( ( response ) => {
					if ( ! response || ! response.success ) {
						app.itemActionFailed( $button, response );

						return;
					}

					window.location.reload();
				} )
				.fail( ( jqXHR ) => app.itemActionFailed( $button, jqXHR && jqXHR.responseJSON ) );
		},

		/**
		 * Revert a busy button and surface the error when an item action request fails.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $button  The clicked button.
		 * @param {object} response AJAX error payload, when available.
		 */
		itemActionFailed( $button, response ) {
			const settings  = WPMailSMTP.Admin.Settings;
			const manualUrl = settings.extractAjaxManualUrl( response );

			$button.removeClass( 'wp-mail-smtp-btn-loading' );

			settings.pluginInstall.offerManualRoute( $button, manualUrl );

			app.showError( settings.extractAjaxError( response, app.getStrings().error ), manualUrl );
		},

		/**
		 * The localized plugin action strings.
		 *
		 * @since 4.10.0
		 *
		 * @returns {object} Plugin action strings, or an empty object when unavailable.
		 */
		getStrings() {
			return ( window.wp_mail_smtp_setup_checklist || {} ).plugins || {};
		},

		/**
		 * Show the error modal, reusing the main admin module's shared one.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} message   Message to display.
		 * @param {string} manualUrl Optional plugin page to offer as the manual route.
		 */
		showError( message, manualUrl ) {
			WPMailSMTP.Admin.Settings.pluginInstall.showErrorModal( message, manualUrl );
		},
	};

	return app;
}( document, window, jQuery ) );

WPMailSMTPSetupChecklist.init();
