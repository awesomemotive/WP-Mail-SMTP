/* global wp_mail_smtp_dashboard, wp_mail_smtp, ajaxurl */
'use strict';

/**
 * WP Mail SMTP Dashboard admin page: orchestrator.
 *
 * Holds the shared page state (cached DOM, module vars) and loads feature
 * modules via dynamic import from the PHP-provided manifest.
 *
 * @since 4.10.0
 */

window.WPMailSMTPDashboard = window.WPMailSMTPDashboard || ( function( document, window, $ ) {
	const app = {

		/**
		 * Cached DOM lookups. Populated by cacheElements().
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		el: {},

		/**
		 * Shared module state.
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		vars: {},

		/**
		 * Generic CSS class names for applying visual changes.
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		classNames: {
			hide: 'wp-mail-smtp-hide',
			selected: 'is-selected',

			// Page-level busy cursor, shared by every module that runs a request.
			loading: 'wpms-dashboard-is-loading',
		},

		/**
		 * CSS selectors for the page-level containers cached in cacheElements().
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		selectors: {
			page: '#wpms-dashboard',
			attention: '#wpms-dashboard-attention',
			main: '#wpms-dashboard-column-main',
			sidebar: '#wpms-dashboard-column-sidebar',
			statCards: '[data-widget="stat_cards"]',
			attentionWidget: '.wpms-dashboard-widget-attention',
			noticeCard: '.wpms-dashboard-notice',
			noticeDismiss: '.js-wpms-dashboard-notice-dismiss',
		},

		/**
		 * Start the engine. Caches elements, then loads feature modules.
		 *
		 * @since 4.10.0
		 */
		init() {
			if ( ! $( app.selectors.page ).length ) {
				return;
			}

			app.cacheElements();
			app.setupAttentionHoist();
			app.setupInlineCardDismiss();
			app.loadModules();
		},

		/**
		 * Cache DOM lookups used across the modules.
		 *
		 * @since 4.10.0
		 */
		cacheElements() {
			app.el.$document = $( document );
			app.el.$page = $( app.selectors.page );
			app.el.$attention = $( app.selectors.attention );
			app.el.$main = $( app.selectors.main );
			app.el.$sidebar = $( app.selectors.sidebar );
		},

		/**
		 * Hoist an "attention" card above the stat cards on narrow screens
		 * and return it to the sidebar on wide ones. A widget opts in via
		 * AbstractWidget::get_extra_classes().
		 *
		 * @since 4.10.0
		 */
		setupAttentionHoist() {
			const $widget = app.el.$sidebar.find( app.selectors.attentionWidget );

			if ( ! $widget.length || ! app.el.$attention.length ) {
				return;
			}

			app.vars.$attentionWidget = $widget;

			// Must mirror the column-stack breakpoint in dashboard/_page.scss.
			app.vars.attentionMedia = window.matchMedia( '(max-width: 1024px)' );
			app.vars.attentionMedia.addEventListener( 'change', app.toggleAttentionHoist );

			if ( app.vars.attentionMedia.matches ) {
				app.toggleAttentionHoist();
			}
		},

		/**
		 * Move the attention card into the top slot or back home, following the media query.
		 *
		 * @since 4.10.0
		 */
		toggleAttentionHoist() {
			if ( app.vars.attentionMedia.matches ) {

				// Hidden anchor marking the card's home position in the sidebar.
				if ( ! app.vars.$attentionAnchor ) {
					app.vars.$attentionAnchor = $( '<div>', { class: app.classNames.hide } ).insertBefore( app.vars.$attentionWidget );
				}

				app.el.$attention.append( app.vars.$attentionWidget );

				return;
			}

			app.vars.$attentionAnchor?.before( app.vars.$attentionWidget );
		},

		/**
		 * Wire up dismissal for the inline notice cards a widget renders inside its
		 * own body (see InlineCards::render_inline_cards() on the PHP side). One
		 * delegated handler on the page root serves every host widget.
		 *
		 * @since 4.10.0
		 */
		setupInlineCardDismiss() {
			app.el.$page.on( 'click', app.selectors.noticeDismiss, app.onInlineCardDismiss );
		},

		/**
		 * Dismiss the clicked card, then remove it. The host widget stays put even
		 * once its last card is gone.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Click event.
		 */
		onInlineCardDismiss( event ) {
			event.preventDefault();

			const $button = $( event.currentTarget );
			const $card = $button.closest( app.selectors.noticeCard );
			const cardId = $card.data( 'card' );

			$button.prop( 'disabled', true );

			$.post( ajaxurl, {
				action: 'wp_mail_smtp_dashboard_dismiss',
				nonce: wp_mail_smtp.nonce,
				card: cardId,
			} )
				.done( ( response ) => {
					if ( ! response?.success ) {
						return;
					}

					$card.remove();
				} )
				.fail( ( jqXHR, textStatus ) => {
					window.console?.error?.( 'WP Mail SMTP Dashboard: dismiss failed.', textStatus );
				} )
				.always( () => {
					$button.prop( 'disabled', false );
				} );
		},

		/**
		 * Dynamically import the feature modules listed in the PHP manifest.
		 *
		 * @since 4.10.0
		 *
		 * @returns {Promise} Resolves once every module has been instantiated and wired.
		 */
		loadModules() {
			const modules = wp_mail_smtp_dashboard.modules || [];

			return Promise.all(
				modules.map( ( module ) => import( module.path ).then( ( mod ) => [ module.name, mod ] ) )
			)
				.then( app.initModules )
				.catch( ( error ) => {
					window.console?.error?.( 'WP Mail SMTP Dashboard: module load failed.', error );
				} );
		},

		/**
		 * Instantiate and wire the loaded modules.
		 *
		 * @since 4.10.0
		 *
		 * @param {Array} imported Resolved [ name, module ] pairs from loadModules().
		 */
		initModules( imported ) {

			// Instantiate every module first so an init() may rely on its siblings.
			imported.forEach( ( [ name, mod ] ) => {
				app[ name ] = mod.default( document, window, $, app );
			} );

			imported.forEach( ( [ name ] ) => {
				app[ name ].init?.();
			} );
		},
	};

	return app;
}( document, window, jQuery ) );

window.WPMailSMTPDashboard.init();
