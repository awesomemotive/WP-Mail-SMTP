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

	$( '.wp-mail-smtp-setting-copy' ).click( function ( e ) {
		e.preventDefault();

		var target = $( '#' + $( this ).data( 'source_id' ) ).get(0);

		target.select();

		document.execCommand( 'Copy' );
	} );

	$( '#wp-mail-smtp-setting-smtp-auth' ).change( function() {
		$( '#wp-mail-smtp-setting-row-smtp-user, #wp-mail-smtp-setting-row-smtp-pass' ).toggleClass( 'inactive' );
	});

	$( '#wp-mail-smtp-setting-row-smtp-encryption input').change( function() {
		if ( 'tls' === $(this).val() ) {
			$(' #wp-mail-smtp-setting-row-smtp-autotls' ).addClass( 'inactive' );
		} else {
			$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
		}
	} );
} );
