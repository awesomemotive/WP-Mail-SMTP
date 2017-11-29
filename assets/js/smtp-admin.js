/* globals jQuery */
jQuery( document ).ready( function ( $ ) {

	$( '.js-wp-mail-smtp-code-helper' ).click( function () {

		$( this ).siblings( '.wp-mail-smtp-code-helper-text' ).slideToggle( 'fast', function () {

			if ( $( this ).is( ':visible' ) ) {
				$( this )
					.siblings( '.wp-mail-smtp-code-helper' )
					.find( '.dashicons' )
					.removeClass( 'dashicons-arrow-down-alt2' )
					.addClass( 'dashicons-arrow-up-alt2' );
			}
			else {
				$( this )
					.siblings( '.wp-mail-smtp-code-helper' )
					.find( '.dashicons' )
					.removeClass( 'dashicons-arrow-up-alt2' )
					.addClass( 'dashicons-arrow-down-alt2' );
			}
		} );

	} );

	$( '.wp-mail-smtp-mailer input' ).click( function () {
		if ( $( this ).prop( 'disabled' ) ) {
			return false;
		}

		// Deselect the current mailer.
		$( '.wp-mail-smtp-mailer' ).removeClass( 'active' );
		// Select the correct one.
		$( this ).parents( '.wp-mail-smtp-mailer' ).addClass( 'active' );

		$( '.wp-mail-smtp-mailer-option' ).addClass( 'hidden' ).removeClass( 'active' );
		$( '.wp-mail-smtp-mailer-option-' + $( this ).val() ).addClass( 'active' ).removeClass( 'hidden' );
	} );

	$( '.wp-mail-smtp-mailer-image' ).click( function () {
		$( this ).parents( '.wp-mail-smtp-mailer' ).find( 'input' ).trigger( 'click' );
	} );

	$( '.wp-mail-smtp-setting-copy' ).click( function ( e ) {
		e.preventDefault();

		var target = $( '#' + $( this ).data( 'source_id' ) ).get(0);

		target.select();

		document.execCommand( 'Copy' );
	} );

} );
