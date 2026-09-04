jQuery( function( $ ) {
	'use strict';

	function openModal( $modal ) {
		$modal.addClass( 'sozlesme-wce-modal--acik' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'sozlesme-wce-kilit' );
	}

	function closeModal( $modal ) {
		$modal.removeClass( 'sozlesme-wce-modal--acik' ).attr( 'aria-hidden', 'true' );
		if ( ! $( '.sozlesme-wce-modal--acik' ).length ) {
			$( 'body' ).removeClass( 'sozlesme-wce-kilit' );
		}
	}

	$( document ).on( 'click', '.sozlesme-wce-link', function( e ) {
		e.preventDefault();
		openModal( $( '#' + $( this ).data( 'target' ) ) );
	} );

	$( document ).on( 'click', '.sozlesme-wce-modal-kapat, .sozlesme-wce-modal-vazgec', function() {
		closeModal( $( this ).closest( '.sozlesme-wce-modal' ) );
	} );

	// Onay içeriğinin dışına (overlay'e) tıklanınca kapat.
	$( document ).on( 'click', '.sozlesme-wce-modal', function( e ) {
		if ( e.target === this ) {
			closeModal( $( this ) );
		}
	} );

	// "Okudum, Onaylıyorum": ilgili checkbox'ı işaretler ve popup'ı kapatır.
	$( document ).on( 'click', '.sozlesme-wce-modal-onayla', function() {
		var $modal = $( this ).closest( '.sozlesme-wce-modal' );
		var checkboxId = $modal.data( 'checkbox' );
		if ( checkboxId ) {
			$( '#' + checkboxId ).prop( 'checked', true ).trigger( 'change' );
		}
		closeModal( $modal );
	} );

	$( document ).on( 'keyup', function( e ) {
		if ( 'Escape' === e.key ) {
			closeModal( $( '.sozlesme-wce-modal--acik' ) );
		}
	} );
} );
