( function() {
	'use strict';

	function insertAtCursor( textarea, text ) {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		var value = textarea.value;
		textarea.value = value.substring( 0, start ) + text + value.substring( end );
		textarea.selectionStart = textarea.selectionEnd = start + text.length;
		textarea.focus();
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		var buttons = document.querySelectorAll( '.sozlesme-wce-degisken-ekle' );

		buttons.forEach( function( button ) {
			button.addEventListener( 'click', function( e ) {
				e.preventDefault();
				var tag = button.getAttribute( 'data-tag' );

				var editor = ( typeof tinymce !== 'undefined' ) ? tinymce.get( 'content' ) : null;

				if ( editor && ! editor.isHidden() ) {
					editor.execCommand( 'mceInsertContent', false, tag );
				} else {
					var textarea = document.getElementById( 'content' );
					if ( textarea ) {
						insertAtCursor( textarea, tag );
					}
				}
			} );
		} );
	} );
} )();
