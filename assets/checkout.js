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
		sozlesmeWceFaturaTipiGuncelle();
		openModal( $( '#' + $( this ).data( 'target' ) ) );
	} );

	// Fatura Tipi (Bireysel/Kurumsal) alanları checkout AJAX'ı ile sunucuya gitmediğinden
	// (bu sitede CheckoutWC saf JS ile gösterip gizliyor), sözleşme popup'larındaki
	// {musteri_adi}, {fatura_tipi}, {sirket_unvani}, {vergi_numarasi}, {vergi_dairesi}
	// değişkenlerini form alanları değiştikçe burada canlı olarak güncelliyoruz.
	function sozlesmeWceFaturaTipiGuncelle() {
		var $tipRadio = $( 'input[name="billing_customer_type"]:checked' );
		if ( ! $tipRadio.length ) {
			return;
		}

		var tip     = $tipRadio.val();
		var sirket  = $.trim( $( '#billing_company' ).val() || '' );
		var vergiNo = $.trim( $( '#billing_tax_number' ).val() || '' );
		var vergiD  = $.trim( $( '#billing_tax_office' ).val() || '' );
		var kurumsalMi = 'kurumsal' === tip;

		$( '.sozlesme-wce-fatura-tipi' ).text( kurumsalMi ? 'Kurumsal' : 'Bireysel' );
		$( '.sozlesme-wce-sirket-unvani' ).text( ( kurumsalMi && sirket ) ? sirket : '—' );
		$( '.sozlesme-wce-vergi-no' ).text( ( kurumsalMi && vergiNo ) ? vergiNo : '—' );
		$( '.sozlesme-wce-vergi-dairesi' ).text( ( kurumsalMi && vergiD ) ? vergiD : '—' );

		if ( kurumsalMi && sirket ) {
			$( '.sozlesme-wce-ad-bireysel' ).addClass( 'sozlesme-wce-gizli' );
			$( '.sozlesme-wce-ad-kurumsal' ).text( sirket ).addClass( 'sozlesme-wce-gorunur' );
		} else {
			$( '.sozlesme-wce-ad-bireysel' ).removeClass( 'sozlesme-wce-gizli' );
			$( '.sozlesme-wce-ad-kurumsal' ).removeClass( 'sozlesme-wce-gorunur' );
		}
	}

	$( document.body ).on( 'change input', 'input[name="billing_customer_type"], #billing_company, #billing_tax_number, #billing_tax_office', sozlesmeWceFaturaTipiGuncelle );
	$( document.body ).on( 'updated_checkout', sozlesmeWceFaturaTipiGuncelle );
	sozlesmeWceFaturaTipiGuncelle();

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
