/* global List */
'use strict';

var WPMailSMTP = window.WPMailSMTP || {};
WPMailSMTP.Admin = WPMailSMTP.Admin || {};

/**
 * WP Mail SMTP Admin area Code Snippets module.
 *
 * @since 4.9.0
 */
WPMailSMTP.Admin.CodeSnippets = WPMailSMTP.Admin.CodeSnippets || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 4.9.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * List.js instance for the snippet grid.
		 *
		 * @since 4.9.0
		 *
		 * @type {object}
		 */
		snippetSearch: null,

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

			app.snippetSearch = new List(
				'wp-mail-smtp-wpcode-snippets-list',
				{
					valueNames: [ 'wp-mail-smtp-wpcode-snippet-title' ],
				}
			);

			app.events();
		},

		/**
		 * Bind events.
		 *
		 * @since 4.9.0
		 */
		events: function() {

			// Fallback overlay button installs WPCode directly.
			$( '.wp-mail-smtp-wpcode-fallback-install-plugin' ).on( 'click', app.onFallbackInstallClick );

			// Card "Install Snippet" (WPCode absent) opens the install-plugin popup.
			$( '.wp-mail-smtp-wpcode-install-plugin' ).on( 'click', app.openInstallPluginPopup );

			// Card "Install Snippet" (WPCode installed but inactive) activates WPCode, then installs.
			$( '.wp-mail-smtp-wpcode-activate-plugin' ).on( 'click', app.onActivatePluginClick );

			// "View" opens the code-preview modal.
			$( '.wp-mail-smtp-wpcode-view-snippet' ).on( 'click', app.openViewSnippetModal );

			// Active-state "Install Snippet" shows an installing state before navigating to WPCode.
			$( '.wp-mail-smtp-wpcode-install-snippet' ).on( 'click', app.installSnippet );

			$( '#wp-mail-smtp-wpcode-snippet-search' ).on( 'keyup search', function() {

				app.searchSnippet( this );
			} );
		},

		/**
		 * POST to an admin-ajax action with the nonce attached.
		 *
		 * @since 4.9.0
		 *
		 * @param {string} action admin-ajax action name.
		 * @param {object} extra  Action-specific fields merged into the request.
		 *
		 * @returns {object} The jQuery jqXHR promise.
		 */
		post: function( action, extra ) {

			var data = $.extend( {
				action: action,
				nonce: window.wp_mail_smtp.nonce,
			}, extra || {} );

			return $.post( window.wp_mail_smtp.ajax_url, data );
		},

		/**
		 * Enter the installing state: hide the badge/label and overlay a spinner
		 * on the button (CSS keeps the button width).
		 *
		 * @since 4.9.0
		 *
		 * @param {object} $label  Badge or "installing" label element.
		 * @param {object} $button Action button element.
		 */
		setInstalling: function( $label, $button ) {

			$label.addClass( 'wp-mail-smtp-wpcode-installing-in-progress' ).text( window.wp_mail_smtp_code_snippets.installing_text );
			$button.addClass( 'wp-mail-smtp-btn-loading' );
		},

		/**
		 * Leave the installing state (used when a flow fails and the UI stays put).
		 *
		 * @since 4.9.0
		 *
		 * @param {object} $label  Badge or "installing" label element.
		 * @param {object} $button Action button element.
		 */
		clearInstalling: function( $label, $button ) {

			$label.removeClass( 'wp-mail-smtp-wpcode-installing-in-progress' ).text( '' );
			$button.removeClass( 'wp-mail-smtp-btn-loading' );
		},

		/**
		 * Filter the snippet grid against the search field value.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} searchField The search field HTML element.
		 */
		searchSnippet: function( searchField ) {

			var searchTerm = $( searchField ).val();
			var searchResults = app.snippetSearch.search( searchTerm );
			var $noResults = $( '#wp-mail-smtp-wpcode-no-results' );

			if ( searchResults.length === 0 ) {
				$noResults.show();
			} else {
				$noResults.hide();
			}
		},

		/**
		 * Active-state "Install Snippet": show an installing state, then let the
		 * link navigate to WPCode's install screen (perceived-load only, no AJAX).
		 *
		 * @since 4.9.0
		 */
		installSnippet: function() {

			var $button = $( this );

			// Edit is a plain navigation to the WPCode edit screen; no JS needed.
			if ( $button.data( 'action' ) === 'edit' ) {
				return;
			}

			app.setInstalling(
				$button.closest( '.wp-mail-smtp-wpcode-snippet' ).find( '.wp-mail-smtp-wpcode-snippet-badge' ),
				$button
			);
		},

		/**
		 * Card "Install Snippet" when WPCode is installed but inactive: activate
		 * WPCode, then navigate to the snippet's install URL. On activation
		 * failure, show the activation popup.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} event The click event.
		 */
		onActivatePluginClick: function( event ) {

			event.preventDefault();

			var $button = $( this );
			var $badge = $button.closest( '.wp-mail-smtp-wpcode-snippet' ).find( '.wp-mail-smtp-wpcode-snippet-badge' );

			app.setInstalling( $badge, $button );

			app.activatePluginThenInstallSnippet( $button.data( 'library-id' ), $button.data( 'plugin' ), function() {

				app.clearInstalling( $badge, $button );
				app.showManualActivationPluginPopup();
			} );
		},

		/**
		 * Activate WPCode via AJAX, then resolve the snippet's install URL via a
		 * second AJAX request and navigate there. If activation fails, run the
		 * provided callback; if only the URL lookup fails, reload so the tab
		 * re-renders in its active state.
		 *
		 * @since 4.9.0
		 *
		 * @param {number}   libraryId      The snippet library id.
		 * @param {string}   plugin         WPCode plugin path.
		 * @param {Function} onActivateFail Called when activation fails.
		 */
		activatePluginThenInstallSnippet: function( libraryId, plugin, onActivateFail ) {

			app.post( 'wp_mail_smtp_ajax', { task: 'about_plugin_activate', plugin: plugin } )
				.done( function( response ) {

					if ( ! response || ! response.success ) {
						onActivateFail();
						return;
					}

					app.navigateToSnippetInstall( libraryId );
				} )
				.fail( function() {

					onActivateFail();
				} );
		},

		/**
		 * Resolve a snippet's WPCode install URL (now that WPCode is active) via
		 * AJAX and navigate to it. Falls back to a reload if the URL can't be
		 * resolved, so the tab still re-renders in its active state.
		 *
		 * @since 4.9.0
		 *
		 * @param {number} libraryId The snippet library id.
		 */
		navigateToSnippetInstall: function( libraryId ) {

			var extra = {};
			extra['library_id'] = libraryId; // eslint-disable-line dot-notation

			app.post( 'wp_mail_smtp_get_wpcode_snippet_install_url', extra )
				.done( function( response ) {

					if ( response && response.success && response.data && response.data.install_url ) {
						window.location.href = response.data.install_url;
						return;
					}

					window.location.reload();
				} )
				.fail( function() {

					window.location.reload();
				} );
		},

		/**
		 * Install and activate WPCode via AJAX. Runs onDone on full success, or
		 * onError('install') if the install failed / errored, or onError('activate')
		 * if WPCode installed but silent activation failed.
		 *
		 * WP returns logical failures with HTTP 200 + success:false, so a failed
		 * install lands in .done (not .fail) and must be checked explicitly.
		 *
		 * @since 4.9.0
		 *
		 * @param {string}   plugin  WPCode download URL.
		 * @param {Function} onDone  Called after a successful install + activation.
		 * @param {Function} onError Called with 'install' or 'activate' on failure.
		 */
		installAndActivatePlugin: function( plugin, onDone, onError ) {

			app.post( 'wp_mail_smtp_ajax', { task: 'about_plugin_install', plugin: plugin } )
				.done( function( response ) {

					if ( ! response || ! response.success ) {
						onError( 'install' );
						return;
					}

					if ( response.data && response.data.is_activated === false ) {
						onError( 'activate' );
						return;
					}

					onDone();
				} )
				.fail( function() {

					onError( 'install' );
				} );
		},

		/**
		 * Show the activate-WPCode popup. Its button forwards to the Plugins page
		 * activation action for the installed WPCode plugin.
		 *
		 * @since 4.9.0
		 */
		showManualActivationPluginPopup: function() {

			var l10n = window.wp_mail_smtp_code_snippets;

			$.confirm( {
				title: l10n.activate_popup_title,
				container: '#wp-mail-smtp',
				boxWidth: '600px',
				useBootstrap: false,
				closeIcon: true,
				draggable: false,
				content: '<p class="wpms:text-secondary">' + l10n.activate_popup_desc + '</p>',
				buttons: {
					activate: {
						text: l10n.activate_popup_btn,
						btnClass: 'btn btn-confirm',
						action: function( button ) {

							button.addClass( 'wp-mail-smtp-btn-loading' );
							window.location.href = l10n.activate_url;

							// Keep the popup open so the loader stays visible during navigation.
							return false;
						},
					},
				},
			} );
		},

		/**
		 * Map a snippet code_type to a CodeMirror mode.
		 *
		 * @since 4.9.0
		 *
		 * @param {string} codeType Snippet code type.
		 *
		 * @returns {string} CodeMirror mode.
		 */
		mapMode: function( codeType ) {

			switch ( codeType ) {
				case 'js':
				case 'javascript':
					return 'javascript';
				case 'css':
					return 'css';
				case 'html':
					return 'htmlmixed';
				default:
					return 'text/x-php';
			}
		},

		/**
		 * Open the read-only code-preview modal for a snippet.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} event The click event.
		 */
		openViewSnippetModal: function( event ) {

			event.preventDefault();

			var $btn       = $( this );
			var libraryId  = $btn.data( 'library-id' );
			var installUrl = $btn.data( 'install-url' );
			var plugin     = $btn.data( 'plugin' );
			var l10n       = window.wp_mail_smtp_code_snippets;

			// $.dialog has no jconfirm button row (whose styles can't be overridden
			// cleanly) — the action button is rendered inside our own content.
			$.dialog( {
				title: l10n.loading_text,
				container: '#wp-mail-smtp',
				boxWidth: '720px',
				useBootstrap: false,
				closeIcon: true,
				draggable: false,
				content: '<div class="wpms:p-md wpms:text-secondary">' + l10n.loading_text + '</div>',
				onContentReady: function() {

					this.$el.addClass( 'wp-mail-smtp-wpcode-dialog' );
					app.loadSnippetCode( this, libraryId, installUrl, plugin );
				},
			} );
		},

		/**
		 * Fetch a snippet's code and render it into an open dialog.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} dialog     The jconfirm dialog instance.
		 * @param {number} libraryId  The snippet library id.
		 * @param {string} installUrl WPCode install URL (active state).
		 * @param {string} plugin     WPCode download URL (inactive state).
		 */
		loadSnippetCode: function( dialog, libraryId, installUrl, plugin ) {

			var l10n  = window.wp_mail_smtp_code_snippets;
			var error = '<div class="wpms:p-md wpms:text-error">' + l10n.error_text + '</div>';
			var extra = {};

			extra['library_id'] = libraryId; // eslint-disable-line dot-notation

			app.post( 'wp_mail_smtp_get_wpcode_snippet_code', extra )
				.done( function( response ) {

					if ( ! response || ! response.success || ! response.data ) {
						dialog.setContent( error );
						return;
					}

					dialog.setTitle( response.data.title );

					var $content   = $( '<div class="wp-mail-smtp-wpcode-modal"></div>' );
					var $codeWrap  = $( '<div class="wp-mail-smtp-wpcode-modal-code"></div>' );
					var $textarea  = $( '<textarea></textarea>' ).val( response.data.code );
					var $footer    = $( '<div class="wp-mail-smtp-wpcode-modal-footer"></div>' );
					var $installing = $( '<span class="wp-mail-smtp-wpcode-modal-installing"></span>' );
					var $action    = $( '<a href="#" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-secondary"></a>' ).text( l10n.install_text );

					$codeWrap.append( $textarea );
					$footer.append( $installing ).append( $action );
					$content.append( $codeWrap ).append( $footer );

					if ( response.data.note ) {
						$content.prepend( $( '<div class="wp-mail-smtp-wpcode-modal-note"></div>' ).text( response.data.note ) );
					}

					dialog.setContent( $content );
					app.initEditor( $textarea[ 0 ], response.data.code_type );
					app.bindModalInstall( dialog, libraryId, installUrl, plugin, $action, $installing );
				} )
				.fail( function() {

					dialog.setContent( error );
				} );
		},

		/**
		 * Wire the preview modal's "Install Snippet" button to the right action for
		 * the current WPCode state (active, installed-inactive, or absent).
		 *
		 * @since 4.9.0
		 *
		 * @param {object} dialog      The jconfirm dialog instance.
		 * @param {number} libraryId   The snippet library id.
		 * @param {string} installUrl  WPCode install URL (active state).
		 * @param {string} plugin      WPCode download URL (WPCode-absent branch).
		 * @param {object} $action     The modal action button.
		 * @param {object} $installing The modal "installing" text element.
		 */
		bindModalInstall: function( dialog, libraryId, installUrl, plugin, $action, $installing ) {

			var l10n = window.wp_mail_smtp_code_snippets;

			$action.on( 'click', function( clickEvent ) {

				clickEvent.preventDefault();

				if ( l10n.wpcode_active ) {
					if ( installUrl ) {
						app.setInstalling( $installing, $action );
						window.location.href = installUrl;
					}
					return;
				}

				// WPCode installed but inactive: activate, then install (mirrors the card button).
				if ( l10n.wpcode_path ) {
					app.setInstalling( $installing, $action );
					app.activatePluginThenInstallSnippet( libraryId, l10n.wpcode_path, function() {

						app.clearInstalling( $installing, $action );
						dialog.close();
						app.showManualActivationPluginPopup();
					} );
					return;
				}

				dialog.close();
				app.showInstallPluginPopup( plugin, libraryId );
			} );
		},

		/**
		 * Initialize CodeMirror on a textarea, with a plain-text fallback.
		 *
		 * @since 4.9.0
		 *
		 * @param {HTMLElement} textarea The textarea element.
		 * @param {string}      codeType Snippet code type.
		 */
		initEditor: function( textarea, codeType ) {

			var l10n = window.wp_mail_smtp_code_snippets;

			if ( ! window.wp || ! window.wp.codeEditor || ! l10n.code_editor ) {

				// Syntax highlighting unavailable (user profile setting): leave the
				// textarea as a plain read-only code box.
				$( textarea ).prop( 'readonly', true );
				return;
			}

			var settings = $.extend( true, {}, l10n.code_editor );

			settings.codemirror = $.extend( {}, settings.codemirror, {
				mode: app.mapMode( codeType ),
				readOnly: true,
				lineNumbers: true,
			} );

			window.wp.codeEditor.initialize( textarea, settings );
		},

		/**
		 * Card "Install Snippet" (WPCode absent): open the install-plugin popup.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} event The click event.
		 */
		openInstallPluginPopup: function( event ) {

			event.preventDefault();
			app.showInstallPluginPopup( $( this ).data( 'plugin' ), $( this ).data( 'library-id' ) );
		},

		/**
		 * Show the install-plugin popup. Its button installs + activates WPCode,
		 * then navigates to the chosen snippet's install URL.
		 *
		 * @since 4.9.0
		 *
		 * @param {string} plugin    WPCode download URL.
		 * @param {number} libraryId The snippet library id.
		 */
		showInstallPluginPopup: function( plugin, libraryId ) {

			var l10n = window.wp_mail_smtp_code_snippets;

			// $.dialog (no jconfirm button row) so the icon row, title, description,
			// CTA, and link render as one centered content block per the design.
			$.dialog( {
				title: '',
				container: '#wp-mail-smtp',
				boxWidth: '550px',
				useBootstrap: false,
				closeIcon: true,
				draggable: false,
				content: app.installPopupHtml( l10n ),

				// The scoped modal styles key off these classes. jQuery-Confirm v3.3.4
				// has no containerClass option, so add them in onOpenBefore: it is the
				// earliest hook where $el exists and it fires before the open animation
				// reveals the box, so the modal paints styled on first render.
				onOpenBefore: function() {

					this.$el.addClass( 'wp-mail-smtp-wpcode-dialog wp-mail-smtp-wpcode-install-dialog' );
				},
				onContentReady: function() {

					var dialog = this;

					dialog.$content.find( '.wp-mail-smtp-wpcode-install-modal-btn' ).on( 'click', function( event ) {

						event.preventDefault();

						var $btn = $( this );

						if ( $btn.hasClass( 'wp-mail-smtp-btn-loading' ) ) {
							return;
						}

						$btn.addClass( 'wp-mail-smtp-btn-loading' );
						dialog.$content.find( '.wp-mail-smtp-wpcode-modal-error' ).remove();

						app.installAndActivatePlugin(
							plugin,
							function() {

								app.navigateToSnippetInstall( libraryId );
							},
							function( reason ) {

								$btn.removeClass( 'wp-mail-smtp-btn-loading' );
								dialog.$content.append( app.modalErrorHtml( reason ) );
							}
						);
					} );
				},
			} );
		},

		/**
		 * Build the install-WPCode popup body: an icon row, a two-line title and
		 * description, the install CTA, and a "learn more" link.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} l10n Localized strings and URLs.
		 *
		 * @returns {string} The popup body markup.
		 */
		installPopupHtml: function( l10n ) {

			return '<div class="wp-mail-smtp-wpcode-install-modal">' +
				'<img class="wp-mail-smtp-wpcode-install-modal-icons" src="' + l10n.install_popup_icon + '" alt="">' +
				'<div class="wp-mail-smtp-wpcode-install-modal-title">' + l10n.install_popup_title + '</div>' +
				'<div class="wp-mail-smtp-wpcode-install-modal-description">' + l10n.install_popup_desc + '</div>' +
				'<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange wp-mail-smtp-wpcode-install-modal-btn">' + l10n.install_popup_btn + '</button>' +
				'<a href="' + l10n.learn_more_url + '" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-wpcode-install-modal-link">' + l10n.learn_more_text + '</a>' +
			'</div>';
		},

		/**
		 * Build the inline error shown in the install/activate confirm dialog on
		 * failure: a message plus a manual-fallback link (activation URL for
		 * activation failures).
		 *
		 * @since 4.9.0
		 *
		 * @param {string} reason 'install' or 'activate'.
		 *
		 * @returns {object} jQuery element for the error paragraph.
		 */
		modalErrorHtml: function( reason ) {

			var l10n = window.wp_mail_smtp_code_snippets;
			var $error = $( '<p class="wp-mail-smtp-wpcode-modal-error"></p>' );

			// Install failure: message only. A plugin-directory search link is
			// fragile (it can surface the wrong plugin), so we don't offer one.
			if ( reason !== 'activate' ) {
				return $error.text( l10n.install_error_text );
			}

			// Activation failure: WPCode is installed, so link to its exact
			// activation URL.
			return $error
				.text( l10n.activate_error_text + ' ' )
				.append(
					$( '<a></a>' )
						.attr( 'href', l10n.lite_activate_url )
						.text( l10n.activate_manual_link )
				);
		},

		/**
		 * Fallback overlay button: install (or activate) WPCode directly, then
		 * reload so the tab re-renders with live snippet data.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} event The click event.
		 */
		onFallbackInstallClick: function( event ) {

			if ( event && typeof event.preventDefault === 'function' ) {
				event.preventDefault();
			}

			var $btn = $( this );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			// Lock the width, hide the label, and disable while the request runs.
			$btn.width( $btn.width() ).text( '' ).addClass( 'disabled' );

			var task = $btn.data( 'action' ) === 'activate' ? 'about_plugin_activate' : 'about_plugin_install';

			app.post( 'wp_mail_smtp_ajax', { task: task, plugin: $btn.data( 'plugin' ) } )
				.done( function() {

					window.location.reload();
				} );
		},
	};

	return app;
}( document, window, jQuery ) );

WPMailSMTP.Admin.CodeSnippets.init();
