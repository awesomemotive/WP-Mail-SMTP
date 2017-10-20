/* globals jQuery */
jQuery( document ).ready( function ( $ ) {

	$( '.wp-mail-smtp-mailer input' ).click( function () {
		// Deselect the current mailer.
		$( '.wp-mail-smtp-mailers .wp-mail-smtp-mailer-image' ).removeClass( 'active' );
		// Select the correct one.
		$( this ).parents( '.wp-mail-smtp-mailer' ).find( '.wp-mail-smtp-mailer-image' ).addClass( 'active' );

		$( '.wp-mail-smtp-mailer-option' ).addClass( 'hidden' ).removeClass( 'active' );
		$( '.wp-mail-smtp-mailer-option-' + $( this ).val() ).addClass( 'active' ).removeClass( 'hidden' );
	} );

	$( '.wp-mail-smtp-mailer-image' ).click( function () {
		$( this ).parents( '.wp-mail-smtp-mailer' ).find( 'input' ).trigger( 'click' );
	} );

} );
