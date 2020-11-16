var canRefresh = null;
var ShowEditorsCounting = false;
var wgAjaxShowEditors = {};

// The loader. Look at bottom for the hook registration
wgAjaxShowEditors.onLoad = function () {
	$( '#ajax-se' ).on( 'click keypress', wgAjaxShowEditors.refresh );

	wgAjaxShowEditors.allowRefresh();
};

// Ask for new data & update UI
wgAjaxShowEditors.refresh = function () {
	if ( !canRefresh ) {
		return;
	}

	// Disable new requests for 5 seconds
	canRefresh = false;
	setTimeout( 'wgAjaxShowEditors.allowRefresh()', 5000 );

	// Do the call to the server
	( new mw.Api() ).get( {
		action: 'ajaxshoweditors',
		format: 'json',
		pageid: mw.config.get( 'wgArticleId' ),
		username: ( mw.config.get( 'wgUserName' ) || '' )
	} ).done( function ( data ) {
		$( '#ajax-se-editors' ).html( data.ajaxshoweditors.result );
	} );

	if ( !ShowEditorsCounting ) {
		wgAjaxShowEditors.countup();
	}
};

wgAjaxShowEditors.countup = function () {
	ShowEditorsCounting = true;

	var elEditorsList = document.getElementById( 'ajax-se-editors' );
	for ( var i = 0; i < elEditorsList.childNodes.length; i++ ) {
		var item = elEditorsList.childNodes[ i ];
		if ( item.nodeName === 'SPAN' ) {
			var value = parseInt( item.innerHTML );
			value++;
			item.innerHTML = value;
		}
	}

	setTimeout( 'wgAjaxShowEditors.countup()', 1000 );
};

// callback to allow refresh
wgAjaxShowEditors.allowRefresh = function () {
	canRefresh = true;
};

// Lazy hack, FIXME. --ashley, 9 November 2020
window.wgAjaxShowEditors = wgAjaxShowEditors;

// Register our initialization function.
$( wgAjaxShowEditors.onLoad );
