/* global wp_mail_smtp, ajaxurl */

/**
 * WP Mail SMTP Dashboard: shared widget gear-menu module.
 *
 * Generic cog to popover to save cycle for any widget that renders a
 * `.wpms-dashboard-widget-cog` button and a `.wpms-dashboard-widget-settings`
 * popover. Persists the popover fields via `wp_mail_smtp_dashboard_save_widget_settings`
 * and broadcasts `wpMailSmtpDashboardWidgetSettingsSaved` so the owning widget can
 * re-render. Bound by class, not widget id, so later gear widgets reuse it.
 *
 * @since 4.10.0
 *
 * @param {object} document Document object.
 * @param {object} window   Window object.
 * @param {jQuery} $        jQuery object.
 * @param {object} app      Shared dashboard page app (el, vars, classNames).
 *
 * @returns {object} Public module API.
 */
export default function( document, window, $, app ) { // eslint-disable-line max-lines-per-function
	const { el } = app;

	const settings = {

		/**
		 * CSS selectors.
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		selectors: {
			widget: '.wpms-dashboard-widget',
			cog: '.wpms-dashboard-widget-cog',
			popover: '.wpms-dashboard-widget-settings',
			save:    '.wpms-dashboard-widget-settings-save',
			reset:   '.wpms-dashboard-widget-settings-reset',
			capped:  '.wpms-dashboard-widget-settings-list[data-min-unchecked], .wpms-dashboard-widget-settings-list[data-max-checked]',
			locked:  '.wpms-dashboard-widget-settings-field[data-locked-by]',
		},

		/**
		 * CSS class names toggled by this module.
		 *
		 * @since 4.10.0
		 *
		 * @type {object}
		 */
		classNames: {
			open: 'wpms-dashboard-widget-settings-open',
			hidden: 'wp-mail-smtp-hide',
		},

		/**
		 * Initialize the module.
		 *
		 * @since 4.10.0
		 */
		init() {
			settings.bindEvents();
		},

		/**
		 * Bind the delegated gear events.
		 *
		 * @since 4.10.0
		 */
		bindEvents() {
			el.$page
				.on( 'click', settings.selectors.cog, settings.onToggle )
				.on( 'click', settings.selectors.save, settings.onSave )
				.on( 'click', settings.selectors.reset, settings.onReset )
				.on( 'change', `${ settings.selectors.popover } input[type="checkbox"]`, settings.onCheckboxChange );

			el.$document.on( 'click', settings.onClickOutside );
		},

		/**
		 * Toggle the popover belonging to the clicked cog.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Click event.
		 */
		onToggle( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $popover = $( event.currentTarget ).closest( settings.selectors.widget ).find( settings.selectors.popover ).first();
			const isOpen = $popover.hasClass( settings.classNames.open );

			settings.closeAll();

			if ( ! isOpen ) {
				$popover.addClass( settings.classNames.open ).prop( 'hidden', false );

				$popover.find( settings.selectors.capped ).each( ( index, list ) => settings.applyListCap( $( list ) ) );
				settings.applyFieldLocks( $popover );
			}
		},

		/**
		 * Re-apply a capped list's floor and the popover's field locks after one of its
		 * boxes changed.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Change event.
		 */
		onCheckboxChange( event ) {
			const $box = $( event.currentTarget );

			settings.applyListCap( $box.closest( settings.selectors.capped ) );
			settings.applyFieldLocks( $box.closest( settings.selectors.popover ) );
		},

		/**
		 * Disable a field whose value another setting overrides, named by `data-locked-by`:
		 * the Entries row count is locked while forms are hand-picked, since the selection
		 * decides how many rows the table has. A disabled field submits nothing, and the
		 * server keeps a setting the popover did not carry, so the locked value survives
		 * and comes back once the checklist is cleared.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $popover The settings popover.
		 */
		applyFieldLocks( $popover ) {
			$popover.find( settings.selectors.locked ).each( ( index, field ) => {
				const $field = $( field );
				const name = $field.data( 'locked-by' );

				$field.prop( 'disabled', $popover.find( `input[type="checkbox"][name="${ name }[]"]:checked` ).length > 0 );
			} );
		},

		/**
		 * Enforce a list's caps in the UI instead of silently on save. Both caps are
		 * reached by checking more, so both disable the options still unchecked.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $list Checklist element.
		 */
		applyListCap( $list ) {
			const $boxes = $list.find( 'input[type="checkbox"]' );
			const $unchecked = $boxes.filter( ':not(:checked)' );

			$boxes.prop( 'disabled', false );
			$unchecked.prop( 'disabled', settings.isListCapped( $list, $boxes.length - $unchecked.length, $unchecked.length ) );
		},

		/**
		 * Whether a list is at either declared cap: how many options must stay unchecked
		 * (`data-min-unchecked`), e.g. so a table keeps a row, and how many may be checked
		 * at once (`data-max-checked`).
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $list     Checklist element.
		 * @param {number} checked   Checked option count.
		 * @param {number} unchecked Unchecked option count.
		 *
		 * @returns {boolean} True when no further option may be checked.
		 */
		isListCapped( $list, checked, unchecked ) {
			const minUnchecked = Number( $list.data( 'min-unchecked' ) ) || 0;
			const maxChecked = Number( $list.data( 'max-checked' ) ) || 0;

			if ( minUnchecked && unchecked <= minUnchecked ) {
				return true;
			}

			return maxChecked > 0 && checked >= maxChecked;
		},

		/**
		 * Close any open popover when a click lands outside it or its cog.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Document click event.
		 */
		onClickOutside( event ) {
			if ( $( event.target ).closest( `${ settings.selectors.popover }, ${ settings.selectors.cog }` ).length ) {
				return;
			}

			settings.closeAll();
		},

		/**
		 * Close every open settings popover.
		 *
		 * @since 4.10.0
		 */
		closeAll() {
			el.$page.find( `${ settings.selectors.popover }.${ settings.classNames.open }` ).removeClass( settings.classNames.open ).prop( 'hidden', true );
		},

		/**
		 * Persist the popover fields, then broadcast the saved settings.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Click event.
		 */
		onSave( event ) {
			event.preventDefault();

			const $popover = $( event.currentTarget ).closest( settings.selectors.popover );
			const values = settings.serialize( $popover );

			settings.request( event, 'save_widget_settings', { settings: values }, ( $reset, data ) => {
				$reset.toggleClass( settings.classNames.hidden, ! data?.has_custom_settings );

				return values;
			} );
		},

		/**
		 * Drop the widget's stored settings and put it back on its defaults. The control is
		 * only offered while the widget is off them, so there is nothing to confirm and
		 * nothing to send but the widget id.
		 *
		 * The server answers with the defaults, which go into the popover's own controls
		 * and then travel on the saved-settings event, so the widget refreshes through
		 * exactly the path a save takes.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event Click event.
		 */
		onReset( event ) {
			event.preventDefault();

			settings.request( event, 'reset_widget_settings', {}, ( $reset, data, $popover ) => {
				const values = data?.settings || {};

				settings.applyValues( $popover, values );
				$reset.addClass( settings.classNames.hidden );

				return values;
			} );
		},

		/**
		 * Post one of the popover's actions, then broadcast the resulting settings so the
		 * owning widget refreshes. Shared by the footer's two buttons: they differ only in
		 * the action, what they send, and how they leave the popover.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery.Event} event   Click event on the button that owns the request.
		 * @param {string}       action  Dashboard AJAX action, without its `wp_mail_smtp_dashboard_` prefix.
		 * @param {object}       payload Extra POST fields.
		 * @param {Function}     resolve Called with ( $reset, responseData, $popover ); returns the settings to broadcast.
		 */
		request( event, action, payload, resolve ) {
			const $button = $( event.currentTarget );
			const $popover = $button.closest( settings.selectors.popover );
			const widget = $button.closest( settings.selectors.widget ).data( 'widget' );

			$button.prop( 'disabled', true ).addClass( 'inactive' );

			// Busy cursor across the whole dashboard while the request is in flight,
			// matching the date-range update (see the Pro dashboard updater).
			el.$page.addClass( app.classNames.loading );

			$.post( ajaxurl, {
				action: `wp_mail_smtp_dashboard_${ action }`,
				nonce: wp_mail_smtp.nonce,
				widget,
				...payload,
			} )
				.done( ( response ) => {
					if ( ! response?.success ) {
						return;
					}

					const values = resolve( $popover.find( settings.selectors.reset ), response.data, $popover );

					el.$document.trigger( 'wpMailSmtpDashboardWidgetSettingsSaved', [ { widget, settings: values } ] );
					settings.closeAll();
				} )
				.fail( ( jqXHR, textStatus ) => {

					// A plain variable, not a template literal passed directly to an
					// optional-chained call, dodges an ESLint 6.x indent-rule crash.
					const message = `WP Mail SMTP Dashboard: ${ action } failed.`;

					window.console?.error?.( message, textStatus );
				} )
				.always( () => {
					$button.prop( 'disabled', false ).removeClass( 'inactive' );
					el.$page.removeClass( app.classNames.loading );
				} );
		},

		/**
		 * Put a settings map back into the popover's controls, the reverse of `serialize()`:
		 * a checkbox is checked when the map carries its value, and anything else takes the
		 * value as it comes. The caps and locks then follow the new values.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $popover The settings popover.
		 * @param {object} values   Settings map.
		 */
		applyValues( $popover, values ) {
			Object.entries( values ).forEach( ( [ name, value ] ) => {
				const $boxes = $popover.find( `input[type="checkbox"][name="${ name }"], input[type="checkbox"][name="${ name }[]"]` );

				if ( $boxes.length ) {
					const checked = Array.isArray( value ) ? value : [ value ];

					$boxes.each( ( index, box ) => {
						box.checked = checked.includes( box.value );
					} );

					return;
				}

				$popover.find( `select[name="${ name }"], input[name="${ name }"]` ).val( value );
			} );

			$popover.find( settings.selectors.capped ).each( ( index, list ) => settings.applyListCap( $( list ) ) );
			settings.applyFieldLocks( $popover );
		},

		/**
		 * Collect the popover's named fields into a settings map. Names ending in
		 * `[]` become arrays of the checked/selected values.
		 *
		 * @since 4.10.0
		 *
		 * @param {jQuery} $popover The settings popover.
		 *
		 * @returns {object} Settings map.
		 */
		serialize( $popover ) {
			const values = {};

			$popover.find( ':input[name]' ).serializeArray().forEach( ( field ) => {
				if ( field.name.endsWith( '[]' ) ) {
					const key = field.name.slice( 0, -2 );

					values[ key ] = values[ key ] || [];
					values[ key ].push( field.value );

					return;
				}

				values[ field.name ] = field.value;
			} );

			return values;
		},
	};

	return settings;
}
