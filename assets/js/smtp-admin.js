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

		var target = $( '#' + $( this ).data( 'source_id' ) ).get( 0 );

		target.select();

		document.execCommand( 'Copy' );
	} );

	$( '#wp-mail-smtp-setting-smtp-auth' ).change( function () {
		$( '#wp-mail-smtp-setting-row-smtp-user, #wp-mail-smtp-setting-row-smtp-pass' ).toggleClass( 'inactive' );
	} );

	$( '#wp-mail-smtp-setting-row-smtp-encryption input' ).change( function () {

		var $this     = $( this ),
			$smtpPort = $( '#' + 'wp-mail-smtp-setting-smtp-port' );

		if ( 'tls' === $this.val() ) {
			$smtpPort.val( '587' );
			$( '#wp-mail-smtp-setting-row-smtp-autotls' ).addClass( 'inactive' );
		} else if ( 'ssl' === $this.val() ) {
			$smtpPort.val( '465' );
			$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
		} else {
			$smtpPort.val( '25' );
			$( '#wp-mail-smtp-setting-row-smtp-autotls' ).removeClass( 'inactive' );
		}
	} );

	$( '#wp-mail-smtp-wpforms-dismiss' ).on( 'click', function () {
		$.ajax( {
			 url: ajaxurl,
			 dataType: 'json',
			 type: 'POST',
			 data: {
				 action: 'wp_mail_smtp_ajax',
				 task: 'wpforms_dismiss'
			 }
		 } )
		 .always( function () {
			 $( '#wp-mail-smtp-wpforms' ).fadeOut( 'fast' );
		 } );
	} );

	$( '#wp-mail-smtp-debug .error-log-toggle' ).on( 'click', function ( e ) {
		e.preventDefault();

		$( '#wp-mail-smtp-debug .error-log-toggle' ).find( '.dashicons' ).toggleClass( 'dashicons-arrow-right-alt2 dashicons-arrow-down-alt2' );
		$( '#wp-mail-smtp-debug .error-log' ).slideToggle();
		$( '#wp-mail-smtp-debug .error-log-note' ).toggle();
	} );

	$( '#wp-mail-smtp-gmail-remove' ).on( 'click', function () {
		return confirm( window.wp_mail_smtp.text_gmail_remove );
	} );
} );
