/* global wp_mail_smtp_email_detective */
/**
 * WP Mail SMTP Email Detective tab: starts a deliverability test, polls its
 * status on a backoff schedule, and submits the lead as soon as it arrives.
 *
 * @since 4.10.0
 */

'use strict';

var WPMailSmtpEmailDetective = window.WPMailSmtpEmailDetective || ( function( document, window, $ ) {

	/**
	 * Poll schedule: 10s for the first minute, 30s to five minutes, 60s
	 * beyond that so an abandoned tab still reaches the not-delivered failsafe.
	 *
	 * @since 4.10.0
	 *
	 * @type {Array}
	 */
	var schedule = [
		{ until: 60, every: 10 },
		{ until: 300, every: 30 },
		{ until: 900, every: 60 }
	];

	/**
	 * Elements, looked up once on ready.
	 *
	 * @since 4.10.0
	 *
	 * @type {object}
	 */
	var el = {};

	/**
	 * Runtime state that doesn't belong in the DOM.
	 *
	 * @since 4.10.0
	 *
	 * @type {object}
	 */
	var state = {
		pollTimer: null,
		startedAt: 0, // Unix seconds, server-provided, never the client clock.
		leadSubmitted: false, // Guards the auto lead submit against firing twice.
		retryAction: null, // 'start', 'status', or 'lead': which loop a rate limit or outage paused.
		startLabel: '', // The start button's shipped label, so the phase swap can restore it translated.
		unavailableTitle: '', // The unavailable state's shipped heading, restored when an error sends no title.
		lastEmail: '', // The address the in-session start was submitted with; the localized config only carries one on a reload.
		resendHideTimer: null
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 4.10.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 4.10.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 4.10.0
		 */
		ready: function() {

			el.$wrap = $( '#wp-mail-smtp-email-detective' );

			if ( el.$wrap.length === 0 ) {
				return;
			}

			el.$body = el.$wrap.find( '#wpms-email-detective-body' );

			// The mailer-not-configured empty state has no body to poll or wire up.
			if ( el.$body.length === 0 ) {
				return;
			}

			state.startedAt = parseInt( wp_mail_smtp_email_detective.started_at, 10 ) || 0;

			el.$start = el.$wrap.find( '#wpms-email-detective-start' );
			state.startLabel = el.$start.text();

			// Every state block is a permanent sibling toggled only by data-phase,
			// so nothing re-creates what a previous run disabled or overwrote.
			el.$retrySend = el.$wrap.find( '#wpms-email-detective-retry-send' );
			state.unavailableTitle = el.$wrap.find( '#wpms-email-detective-unavailable-title' ).text();

			app.events();

			// PHP already rendered the current phase, so only these two resume anything.
			var phase = el.$body.attr( 'data-phase' );

			if ( phase === 'waiting' ) {
				app.schedulePoll( 0 );
			} else if ( phase === 'received' ) {
				app.autoSubmitLead();
			}
		},

		/**
		 * Register JS events.
		 *
		 * @since 4.10.0
		 */
		events: function() {

			el.$wrap.on( 'submit', '#wpms-email-detective-start-form', app.startTest );
			el.$wrap.on( 'input', '#wpms-email-detective-email', app.onEmailInput );
			el.$wrap.on( 'click', '#wpms-email-detective-resend', app.resendLead );
			el.$wrap.on( 'click', '#wpms-email-detective-reset', app.resetTest );
			el.$wrap.on( 'click', '#wpms-email-detective-cancel', app.resetTest );
			el.$wrap.on( 'click', '#wpms-email-detective-retry', app.retry );
			el.$wrap.on( 'click', '#wpms-email-detective-retry-send', app.retrySend );
		},

		/**
		 * POST one of the tab's four AJAX actions.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} action 'start', 'status', 'lead', or 'reset'.
		 * @param {object} data   Extra fields to send (e.g. email).
		 *
		 * @returns {jQuery.jqXHR} The AJAX promise.
		 */
		request: function( action, data ) {

			var payload = $.extend(
				{
					action: 'wp_mail_smtp_email_detective_' + action,
					nonce: wp_mail_smtp_email_detective.nonce
				},
				data || {}
			);

			return $.post( wp_mail_smtp_email_detective.ajax_url, payload );
		},

		/**
		 * Switch the visible state block by setting data-phase on the wrapper.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} phase One of the TestState::PHASE_* values, plus
		 * the client-only 'ready', 'rate_limited', and
		 * 'unavailable'.
		 */
		// eslint-disable-next-line complexity
		setPhase: function( phase ) {

			// Re-enable on every entry, since nothing re-creates either button.
			if ( phase === 'ready' && el.$start && el.$start.length ) {
				el.$start.css( 'width', '' ).prop( 'disabled', false ).text( state.startLabel );
			}

			if ( phase === 'send_failed' && el.$retrySend && el.$retrySend.length ) {
				el.$retrySend.prop( 'disabled', false );
			}

			el.$body.attr( 'data-phase', phase );
		},

		/**
		 * Fill in every report_url link on the page.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} url The report URL, or empty/undefined to no-op.
		 */
		applyReportUrl: function( url ) {

			if ( ! url ) {
				return;
			}

			// .attr('href') does not filter dangerous schemes the way esc_url does, so
			// a hostile or MITM'd API response could otherwise land a javascript: URL.
			var probe = document.createElement( 'a' );
			probe.href = url;

			if ( probe.protocol !== 'http:' && probe.protocol !== 'https:' ) {
				return;
			}

			$( '#wpms-email-detective-report-link-not-delivered, #wpms-email-detective-report-link-unlocked' ).attr( 'href', url );
		},

		/**
		 * Build a templated sentence as HTML, with the email bolded in place of '%s'.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} template A string containing one '%s' placeholder.
		 * @param {string} email    The address to bold in place of '%s'.
		 *
		 * @returns {string} The resulting HTML.
		 */
		buildBoldedEmailHtml: function( template, email ) {

			// .text() on a detached element: only escaped markup is spliced in.
			var strongHtml = $( '<strong></strong>' ).text( email ).prop( 'outerHTML' );
			var parts = template.split( '%s' );

			return parts[ 0 ] + strongHtml + parts.slice( 1 ).join( '%s' );
		},

		/**
		 * Fill in the lead_sent state's message, for the phases reached without
		 * a page load, where PHP has not already rendered it.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} email The address the report was sent to, or
		 * empty/undefined to no-op.
		 */
		applyLeadSentEmail: function( email ) {

			if ( ! email ) {
				return;
			}

			$( '#wpms-email-detective-lead-sent-message' ).html(
				app.buildBoldedEmailHtml( wp_mail_smtp_email_detective.strings.lead_sent_body, email )
			);
		},

		/**
		 * Fill in the unlocked state's message. See applyLeadSentEmail().
		 *
		 * @since 4.10.0
		 *
		 * @param {string} email The address that unlocked the report, or
		 * empty/undefined to no-op.
		 */
		applyUnlockedEmail: function( email ) {

			if ( ! email ) {
				return;
			}

			el.$wrap.find( '[data-state="unlocked"] .wpms-email-detective-state__body' ).html(
				app.buildBoldedEmailHtml( wp_mail_smtp_email_detective.strings.unlocked_body, email )
			);
		},

		/**
		 * Validate the ready-state email field before it reaches ajax_start.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $email   The email input.
		 * @param {jQuery} $message The message element to show the error in.
		 *
		 * @returns {boolean} True if the field is valid. False otherwise, in
		 * which case the error state has already been applied.
		 */
		validateEmail: function( $email, $message ) {

			if ( $email[ 0 ].checkValidity() ) {
				app.clearEmailError( $email, $message );
				return true;
			}

			$email.attr( 'aria-invalid', 'true' );
			$message.text( wp_mail_smtp_email_detective.strings.invalid_email ).prop( 'hidden', false );

			return false;
		},

		/**
		 * Clear the ready-state form's client-side email error, if one is showing.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $email   The email input.
		 * @param {jQuery} $message The message element the error was shown in.
		 */
		clearEmailError: function( $email, $message ) {

			$email.removeAttr( 'aria-invalid' );
			$message.prop( 'hidden', true ).text( '' );
		},

		/**
		 * The email field's `input` event (ready state).
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} e Input event.
		 */
		onEmailInput: function( e ) {

			app.clearEmailError( $( e.target ), $( '#wpms-email-detective-start-message' ) );
		},

		/**
		 * The "Run a Deliverability Test" form submit (ready state).
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} e Submit event.
		 */
		startTest: function( e ) {

			e.preventDefault();

			var $form = $( this );
			var $email = $form.find( '#wpms-email-detective-email' );
			var $message = $( '#wpms-email-detective-start-message' );

			if ( ! app.validateEmail( $email, $message ) ) {
				return;
			}

			var $btn = $form.find( '#wpms-email-detective-start' ).prop( 'disabled', true );
			var originalLabel = $btn.text();
			var email = $.trim( $email.val() );

			// Remembered so send_failed's retry can re-send without a form.
			state.lastEmail = email;

			// Lock the width before swapping the label, or the shorter label shrinks
			// the button and the email input jumps to fill the row.
			$btn.css( 'width', $btn[0].getBoundingClientRect().width + 'px' );
			$btn.text( wp_mail_smtp_email_detective.strings.sending );

			app.request( 'start', { email: email } ).done( function( response ) {

				if ( ! response || ! response.success ) {
					$btn.css( 'width', '' ).prop( 'disabled', false ).text( originalLabel );
					app.showStartError( $message, response );
					return;
				}

				if ( ! app.applyStartSuccess( response.data ) ) {
					$btn.css( 'width', '' ).prop( 'disabled', false ).text( originalLabel );
				}
			} ).fail( function() {

				$btn.css( 'width', '' ).prop( 'disabled', false ).text( originalLabel );
				app.showStartError( $message, { data: { code: 'http_error', message: '' } } );
			} );
		},

		/**
		 * Apply a successful 'start' response, shared by the form submit and
		 * the send_failed retry so both land on an identical phase.
		 *
		 * @since 4.10.0
		 *
		 * @param {object} data The 'start' action's response.data.
		 *
		 * @returns {boolean} True if the phase is 'waiting' (the caller
		 * should leave its button disabled - the loading card is now
		 * showing); false otherwise (the caller should restore its
		 * button).
		 */
		applyStartSuccess: function( data ) {

			if ( data.phase === 'send_failed' ) {
				$( '#wpms-email-detective-send-error' ).text( data.send_error || '' );
			}

			app.applyReportUrl( data.report_url );
			app.setPhase( data.phase );

			if ( data.phase !== 'waiting' ) {
				return false;
			}

			state.startedAt = Math.floor( Date.now() / 1000 );
			state.leadSubmitted = false;
			app.schedulePoll( 0 );

			return true;
		},

		/**
		 * Map a 'start' error onto an inline message on the ready form, or the
		 * shared phase-swap error handling for everything else.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $message The message element to fill in.
		 * @param {object} response The AJAX response, or a synthetic one for
		 * a network-level failure.
		 */
		showStartError: function( $message, response ) {

			var error = ( response && response.data ) ? response.data : { code: 'http_error', message: '' };

			if ( error.code === 'invalid_email' ) {
				$message.text( wp_mail_smtp_email_detective.strings.invalid_email ).prop( 'hidden', false );
				return;
			}

			app.handleError( 'start', response );
		},

		/**
		 * How long to wait before the next status poll.
		 *
		 * @since 4.10.0
		 *
		 * @param {number} elapsedSeconds Seconds since started_at.
		 *
		 * @returns {number} The delay, in seconds, before the next poll.
		 */
		intervalForElapsed: function( elapsedSeconds ) {

			for ( var i = 0; i < schedule.length; i++ ) {
				if ( elapsedSeconds < schedule[ i ].until ) {
					return schedule[ i ].every;
				}
			}

			return schedule[ schedule.length - 1 ].every;
		},

		/**
		 * Schedule the next status poll.
		 *
		 * @since 4.10.0
		 *
		 * @param {number} delaySeconds Delay before the next poll fires.
		 */
		schedulePoll: function( delaySeconds ) {

			app.clearPollTimer();

			state.pollTimer = window.setTimeout( app.pollStatus, delaySeconds * 1000 );
		},

		/**
		 * Clear the pending poll timer, if any.
		 *
		 * @since 4.10.0
		 */
		clearPollTimer: function() {

			if ( state.pollTimer ) {
				window.clearTimeout( state.pollTimer );
				state.pollTimer = null;
			}
		},

		/**
		 * Stop polling.
		 *
		 * @since 4.10.0
		 */
		stopPolling: function() {

			app.clearPollTimer();
		},

		/**
		 * One status poll tick.
		 *
		 * @since 4.10.0
		 */
		pollStatus: function() {

			app.request( 'status', {} ).done( function( response ) {

				if ( ! response || ! response.success ) {
					app.handleError( 'status', response );
					return;
				}

				var data = response.data;

				app.applyReportUrl( data.report_url );

				if ( data.phase === 'received' ) {
					app.stopPolling();
					app.setPhase( 'received' );
					app.autoSubmitLead();
					return;
				}

				app.setPhase( data.phase );

				// Anything other than waiting is the end of the loop, the two lead
				// phases included: a status poll can report those back on a resync.
				if ( data.phase !== 'waiting' ) {
					app.stopPolling();
					return;
				}

				var elapsed = Math.floor( Date.now() / 1000 ) - state.startedAt;

				app.schedulePoll( app.intervalForElapsed( elapsed ) );
			} ).fail( function() {

				app.handleError( 'status', { data: { code: 'http_error', message: '' } } );
			} );
		},

		/**
		 * Submit the lead as soon as the test arrives. The email and consent
		 * were captured on the ready state, so there is nothing to ask for.
		 *
		 * @since 4.10.0
		 */
		autoSubmitLead: function() {

			if ( state.leadSubmitted ) {
				return;
			}

			state.leadSubmitted = true;

			app.request( 'lead', {} ).done( function( response ) {

				if ( ! response || ! response.success ) {
					state.leadSubmitted = false;
					app.handleError( 'lead', response );
					return;
				}

				var data = response.data;

				app.applyReportUrl( data.report_url );
				app.applyLeadSentEmail( data.email );
				app.applyUnlockedEmail( data.email );
				app.setPhase( data.phase );
			} ).fail( function() {

				state.leadSubmitted = false;
				app.handleError( 'lead', { data: { code: 'http_error', message: '' } } );
			} );
		},

		/**
		 * Shared error handling for the 'start', 'status', and 'lead' actions.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} action   'start', 'status', or 'lead'.
		 * @param {object} response The AJAX response (or a synthetic one for
		 * a network-level failure).
		 */
		// eslint-disable-next-line complexity
		handleError: function( action, response ) {

			var error = ( response && response.data ) ? response.data : { code: 'api_error', message: '' };

			switch ( error.code ) {

				case 'rate_limited':
					app.showRateLimited( action, error );
					break;

				case 'no_test':

					// The stored test is gone, e.g. reset from another tab.
					app.stopPolling();
					app.setPhase( 'ready' );
					break;

				case 'test_in_flight':
				case 'invalid_phase':

					// Out of sync with the server: resync rather than guess.
					app.pollStatus();
					break;

				default:

					// Everything else, named or not, lands on one manual-retry state.
					app.showUnavailable( action, error );
			}
		},

		/**
		 * Pause on a 429 and resume automatically once retry_after elapses.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} action 'start', 'status', or 'lead'.
		 * @param {object} error  The error payload ({@see error_payload()}).
		 */
		showRateLimited: function( action, error ) {

			app.stopPolling();

			var retryAfter = parseInt( error.data && error.data.retry_after, 10 ) || 30;
			var comeBackAt = new Date( Date.now() + retryAfter * 1000 );

			$( '#wpms-email-detective-rate-limited-message' ).text(
				wp_mail_smtp_email_detective.strings.come_back_at.replace( '%s', app.formatTime( comeBackAt ) )
			);

			app.setPhase( 'rate_limited' );

			window.setTimeout( function() {

				if ( action === 'start' ) {

					// Nothing was created server-side, so let the person choose.
					app.setPhase( 'ready' );
					return;
				}

				if ( action === 'lead' ) {
					state.leadSubmitted = false;
					app.setPhase( 'received' );
					app.autoSubmitLead();
					return;
				}

				state.startedAt = Math.floor( Date.now() / 1000 );
				app.setPhase( 'waiting' );
				app.pollStatus();
			}, retryAfter * 1000 );
		},

		/**
		 * Show the temporarily-unavailable state with a manual retry.
		 *
		 * @since 4.10.0
		 *
		 * @param {string} action 'start', 'status', or 'lead': which action
		 * to retry.
		 * @param {object} error  The error payload.
		 */
		showUnavailable: function( action, error ) {

			app.stopPolling();

			$( '#wpms-email-detective-unavailable-message' ).text(
				( error && error.message ) ? error.message : wp_mail_smtp_email_detective.strings.unavailable
			);

			// A failure that is not an outage sends its own heading, so the
			// generic "temporarily unavailable" title does not contradict it.
			var $title = $( '#wpms-email-detective-unavailable-title' );

			$title.text( ( error && error.title ) ? error.title : state.unavailableTitle );

			app.setPhase( 'unavailable' );

			state.retryAction = action;
		},

		/**
		 * The "Try Again" click from the unavailable state.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} e Click event.
		 */
		retry: function( e ) {

			e.preventDefault();

			var $btn = $( this ).prop( 'disabled', true );

			if ( state.retryAction === 'start' ) {
				app.setPhase( 'ready' );
				$btn.prop( 'disabled', false );
				return;
			}

			if ( state.retryAction === 'lead' ) {
				state.leadSubmitted = false;
				app.setPhase( 'received' );
				app.autoSubmitLead();
				$btn.prop( 'disabled', false );
				return;
			}

			// A single manual status refresh, which re-enters the poll loop on success.
			app.setPhase( 'waiting' );
			app.pollStatus();
			$btn.prop( 'disabled', false );
		},

		/**
		 * The "Resend the Report Email" click (lead_sent state). The email and
		 * consent come from TestState, so there is nothing to pass.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} e Click event.
		 */
		resendLead: function( e ) {

			e.preventDefault();

			var $btn = $( '#wpms-email-detective-resend' ).prop( 'disabled', true );
			var $fb = $( '#wpms-email-detective-resend-feedback' );
			var strings = wp_mail_smtp_email_detective.strings;

			// A pending "Email Sent" from a previous resend may still be on screen.
			window.clearTimeout( state.resendHideTimer );

			$fb.attr( 'class', 'wpms-email-detective-resend-feedback wpms-email-detective-resend-feedback--loading' )
				.html( '<span class="wpms-email-detective-resend-spinner" aria-hidden="true"></span>' )
				.prop( 'hidden', false );

			// Minimum spinner time, so a near-instant resend still reads as an action.
			var started = Date.now();
			var afterMinSpin = function( fn ) {
				window.setTimeout( fn, Math.max( 0, 3000 - ( Date.now() - started ) ) );
			};

			app.request( 'lead', {} ).done( function( response ) {

				afterMinSpin( function() {

					$btn.prop( 'disabled', false );

					if ( ! response || ! response.success ) {
						app.showResendError( $fb, response );
						return;
					}

					app.applyReportUrl( response.data.report_url );

					$fb.attr( 'class', 'wpms-email-detective-resend-feedback wpms-email-detective-resend-feedback--success' )
						.text( strings.email_sent )
						.prop( 'hidden', false );

					state.resendHideTimer = window.setTimeout( function() {
						$fb.prop( 'hidden', true ).empty();
					}, 5000 );
				} );
			} ).fail( function() {

				afterMinSpin( function() {
					$btn.prop( 'disabled', false );
					app.showResendError( $fb, null );
				} );
			} );
		},

		/**
		 * Render a resend error below the button rather than swapping the whole
		 * panel, since the resend button has to stay on screen for another try.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $fb      The resend-feedback element to render into.
		 * @param {object} response The AJAX response, or null on a
		 * network-level failure.
		 */
		// eslint-disable-next-line complexity
		showResendError: function( $fb, response ) {

			var error = ( response && response.data ) ? response.data : { code: 'http_error', message: '' };
			var strings = wp_mail_smtp_email_detective.strings;
			var text = error.message || strings.generic_error;

			if ( error.code === 'rate_limited' ) {

				var retryAfter = parseInt( error.data && error.data.retry_after, 10 ) || 30;
				var comeBackAt = new Date( Date.now() + retryAfter * 1000 );

				text = strings.come_back_at.replace( '%s', app.formatTime( comeBackAt ) );

			} else if ( strings[ error.code ] ) {
				text = strings[ error.code ];
			}

			// .text() escapes it: server and error strings are not markup-safe.
			$fb.attr( 'class', 'wpms-email-detective-resend-feedback wpms-email-detective-resend-feedback--error' )
				.text( text )
				.prop( 'hidden', false );
		},

		/**
		 * The "Retry Test Email" click (send_failed state): reset, then
		 * re-submit 'start' with the email the original run used.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} e Click event.
		 */
		retrySend: function( e ) {

			e.preventDefault();

			var $btn = $( this ).prop( 'disabled', true );

			var email = state.lastEmail || wp_mail_smtp_email_detective.email;

			app.request( 'reset', {} ).done( function() {

				if ( ! email ) {
					$btn.prop( 'disabled', false );
					app.setPhase( 'ready' );
					return;
				}

				app.request( 'start', { email: email } ).done( function( response ) {

					if ( ! response || ! response.success ) {
						$btn.prop( 'disabled', false );
						app.handleError( 'start', response );
						return;
					}

					if ( ! app.applyStartSuccess( response.data ) ) {
						$btn.prop( 'disabled', false );
					}
				} ).fail( function() {

					$btn.prop( 'disabled', false );
					app.handleError( 'start', { data: { code: 'http_error', message: '' } } );
				} );
			} ).fail( function() {

				$btn.prop( 'disabled', false );
				app.handleError( 'start', { data: { code: 'http_error', message: '' } } );
			} );
		},

		/**
		 * The "Start a New Report" click.
		 *
		 * @since 4.10.0
		 *
		 * @param {Event} e Click event.
		 */
		resetTest: function( e ) {

			e.preventDefault();

			var $btn = $( this ).prop( 'disabled', true );

			app.stopPolling();
			state.leadSubmitted = false;

			app.request( 'reset', {} ).always( function() {

				$btn.prop( 'disabled', false );
				app.setPhase( 'ready' );
			} );
		},

		/**
		 * Format a Date as a short local time, e.g. "2:45 pm".
		 *
		 * @since 4.10.0
		 *
		 * @param {Date} date The time to format.
		 *
		 * @returns {string} The formatted time.
		 */
		formatTime: function( date ) {

			var hours = date.getHours();
			var minutes = date.getMinutes();
			var period = hours >= 12 ? 'pm' : 'am';
			var displayHours = hours % 12;

			if ( displayHours === 0 ) {
				displayHours = 12;
			}

			return displayHours + ':' + ( minutes < 10 ? '0' : '' ) + minutes + ' ' + period;
		}
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPMailSmtpEmailDetective.init();
