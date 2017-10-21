/* globals jQuery */
jQuery( document ).ready( function ( $ ) {

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

} );
