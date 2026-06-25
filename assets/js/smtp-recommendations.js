/* global wp_mail_smtp_recommendations, wp_mail_smtp */

/**
 * @param wp_mail_smtp_recommendations.plugin_page.activated
 * @param wp_mail_smtp_recommendations.plugin_page.activated_pro
 * @param wp_mail_smtp_recommendations.plugin_page.download_now
 * @param wp_mail_smtp_recommendations.plugin_page.error_could_not_activate
 * @param wp_mail_smtp_recommendations.plugin_page.error_could_not_install
 * @param wp_mail_smtp_recommendations.plugin_page.is_activated
 * @param wp_mail_smtp_recommendations.plugin_page.license_level
 * @param wp_mail_smtp_recommendations.plugin_page.result_status
 * @param wp_mail_smtp_recommendations.plugin_page.plugins_page
 * @param wp_mail_smtp_recommendations.plugin_page.setup_status
 * @param wp_mail_smtp_recommendations.plugin_page.step3_button_url
 * @param wp_mail_smtp_recommendations.plugin_page.manual_activate_url
 * @param wp_mail_smtp_recommendations.plugin_page.manual_install_url
 */

'use strict';

const WPMailSMTPRecommendations = {};

/**
 * Plugin recommendation page.
 *
 * @since 4.9.0
 */
WPMailSMTPRecommendations.plugin_page = ( function( document, window, $ ) {

	/**
	 * Elements.
	 *
	 * @since 4.9.0
	 *
	 * @type {object}
	 */
	let el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 4.9.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 4.9.0
		 */
		init: () => {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 4.9.0
		 */
		ready: () => {
			app.initVars();
			app.events();
		},

		/**
		 * Init variables.
		 *
		 * @since 4.9.0
		 */
		initVars: () => {
			el = {
				$stepInstall:    $( 'section.step-install' ),
				$stepInstallNum: $( 'section.step-install .num img' ),
				$stepSetup:      $( 'section.step-setup' ),
				$stepSetupNum:   $( 'section.step-setup .num img' ),
				$stepResult:      $( 'section.step-result' ),
				$stepResultNum:   $( 'section.step-result .num img' ),
			};
		},

		/**
		 * Register JS events.
		 *
		 * @since 4.9.0
		 */
		events: () => {

			// Step the 'Install' button click.
			el.$stepInstall.on( 'click', 'button', app.stepInstallClick );

			// Step 'Setup' button click.
			el.$stepSetup.on( 'click', 'button', app.gotoURL );

			// Step the 'Addon' button click.
			el.$stepResult.on( 'click', 'button', app.gotoURL );
		},

		/**
		 * Step the 'Install' button click.
		 *
		 * @since 4.9.0
		 *
		 * @param {Event} e Event object.
		 */
		stepInstallClick: ( e ) => {
			const $btn = $( e.currentTarget );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			const action = $btn.attr( 'data-action' );

			let task = '';

			switch ( action ) {
				case 'activate':
					task = 'about_plugin_activate';
					$btn.html( wp_mail_smtp_recommendations.plugin_page.activating );
					break;

				case 'install':
					task = 'about_plugin_install';
					$btn.html( wp_mail_smtp_recommendations.plugin_page.installing );
					break;

				case 'goto-url':
					window.location.href = $btn.attr( 'data-url' );
					return;

				default:
					return;
			}

			$btn.addClass( 'disabled' );
			app.showSpinner( el.$stepInstallNum );

			const plugin = $btn.attr( 'data-plugin' );

			const data = {
				action: 'wp_mail_smtp_ajax',
				task,
				nonce : wp_mail_smtp.nonce,
				plugin,
			};
			$.post( wp_mail_smtp.ajax_url, data )
				.done( function( res ) {
					app.stepInstallDone( res, $btn, action );
				} )
				.always( function() {
					app.hideSpinner( el.$stepInstallNum );
				} );
		},

		/**
		 * Done part of the step 'Install'.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} res    Result of $.post() query.
		 * @param {jQuery} $btn   Button.
		 * @param {string} action Action (for more info look at the app.stepInstallClick() function).
		 */
		stepInstallDone: ( res, $btn, action ) => { // eslint-disable-line complexity
			const success = 'install' === action ? res.success && res.data.is_activated : res.success,
				provider = $btn.data( 'provider' );

			if ( success ) {
				el.$stepInstallNum.attr( 'src', el.$stepInstallNum.attr( 'src' ).replace( 'step-1.', 'complete.' ) );
				$btn.addClass( 'grey' ).removeClass( 'button-primary' ).html( wp_mail_smtp_recommendations.plugin_page.activated );
				app.stepInstallPluginStatus( provider );
			} else {
				const activationFail = ( 'install' === action && res.success && ! res.data.is_activated ) || 'activate' === action,
					installUrl = wp_mail_smtp_recommendations.plugin_page[ provider + '_manual_install_url' ] || '',
					activateUrl = wp_mail_smtp_recommendations.plugin_page[ provider + '_manual_activate_url' ] || '',
					url = ! activationFail ? installUrl : activateUrl,
					msg = ! activationFail ? wp_mail_smtp_recommendations.plugin_page.error_could_not_install : wp_mail_smtp_recommendations.plugin_page.error_could_not_activate,
					btn = ! activationFail ? wp_mail_smtp_recommendations.plugin_page.download_now : wp_mail_smtp_recommendations.plugin_page.plugins_page;

				$btn.removeClass( 'grey disabled' ).html( btn ).attr( 'data-action', 'goto-url' ).attr( 'data-url', url );
				$btn.after( '<p class="error">' + msg + '</p>' );
			}
		},

		/**
		 * Callback for step 'Install' completion.
		 *
		 * @since 4.9.0
		 *
		 * @param {string} plugin Plugin name.
		 */
		stepInstallPluginStatus: ( plugin ) => {
			const data = {
				action: 'wp_mail_smtp_page_check_' + plugin + '_status',
				nonce: wp_mail_smtp.nonce,
				provider: plugin,
			};
			$.post( wp_mail_smtp.ajax_url, data ).done( app.stepInstallPluginStatusDone );
		},

		/**
		 * Done part of the callback for step 'Install' completion.
		 *
		 * @since 4.9.0
		 *
		 * @param {object} res Result of $.post() query.
		 */
		stepInstallPluginStatusDone: ( res ) => {
			if ( ! res.success ) {
				return;
			}

			el.$stepSetup.removeClass( 'grey' );
			el.$stepSetupBtn = el.$stepSetup.find( 'button' );

			if ( res.data.setup_status > 0 ) {
				el.$stepSetupNum.attr( 'src', el.$stepSetupNum.attr( 'src' ).replace( 'step-2.svg', 'complete.svg' ) );
				el.$stepResult.removeClass( 'grey' );
				el.$stepResultBtn = el.$stepResult.find( 'button' );

				if ( res.data.license_level === 'pro' && res.data.result_status === true ) {
					el.$stepResultBtn.html( wp_mail_smtp_recommendations.plugin_page.activated_pro );
					el.$stepResultNum.attr( 'src', el.$stepResultNum.attr( 'src' ).replace( 'step-3.svg', 'complete.svg' ) );
				} else {
					el.$stepResultBtn.attr( 'data-url', res.data.step3_button_url );
					el.$stepResultBtn.removeClass( 'grey disabled' ).addClass( 'button-primary' );
				}
			} else {
				el.$stepSetupBtn.removeClass( 'grey disabled' ).addClass( 'button-primary' );
			}
		},

		/**
		 * Go to URL.
		 *
		 * @since 4.9.0
		 *
		 * @param {Event} e Event object.
		 */
		gotoURL: ( e ) => { // eslint-disable-line no-unused-vars
			const $btn = $( e.currentTarget ),
				url = $btn.attr( 'data-url' );

			if ( $btn.hasClass( 'disabled' ) || ! url ) {
				return;
			}

			window.open( url, '_blank' );
		},

		/**
		 * Show spinner.
		 *
		 * @since 4.9.0
		 *
		 * @param {jQuery} $el Element.
		 */
		showSpinner: ( $el ) => {
			$el.siblings( 'i.loader' ).removeClass( 'hidden' );
		},

		/**
		 * Hide spinner.
		 *
		 * @since 4.9.0
		 *
		 * @param {jQuery} $el Element.
		 */
		hideSpinner: ( $el ) => {
			$el.show();
			$el.siblings( 'i.loader' ).addClass( 'hidden' );
		},
	};

	// Provide public access to functions and properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPMailSMTPRecommendations.plugin_page.init();
