;( function( $ ){

	/**
	 * f(x) Settings Page Tabs UI Script
	 **/

	/* Load tabs functions if exist */
	if ( $('.fx-sptabs-nav .nav-tab').length > 0 ) {
		fx_sp_tabs();
	}

	/**
	 * Settings Tabs
	 * based on options framework tabs
	 * @link https://github.com/devinsays/options-framework-theme/
	 */
	function fx_sp_tabs(){
		var tabs_id = $( 'fx-settings-page-tabs-ui' ).attr( 'id' );
			panels  = $( '.fx-sptabs-panel' ),
			navtabs = $('.nav-tab-wrapper a'),
			active_tab = '';

		/* Hide panel */
		panels.hide();

		/* Get Local Storage */
		if ( typeof( localStorage ) != 'undefined' ) {
			active_tab = localStorage.getItem( tabs_id + '_active_tab' );
		}
		if ( active_tab != '' && $( active_tab ).length ) {
			$( active_tab ).fadeIn();
			$( '.nav-tab[href="' + active_tab + '"]' ).addClass('nav-tab-active');
		} else {
			$('.fx-sptabs-panel:first').fadeIn();
			$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
		}

		/* Click tab: add local storage and show/hide */
		navtabs.click(function(e) {
			e.preventDefault();
			navtabs.removeClass('nav-tab-active');
			$( this ).addClass('nav-tab-active').blur();
			if ( typeof( localStorage ) != 'undefined' ) {
				localStorage.setItem( tabs_id + '_active_tab', $( this ).attr( 'href' ) );
			}
			var selected = $( this ).attr( 'href' );
			panels.hide();
			$( selected ).fadeIn();
		});
	}

	/* Show Spinner on Submit */
	$('#fx-sptabs-form').submit( function(){
		$('#publishing-action .spinner').addClass( 'is-active' );
	});

	/* Reset Confirmation */
	$('#delete-action .submitdelete').on( 'click', function() {
		return confirm( $( this ).data( 'confirm-text' ) );
	});

})( jQuery );