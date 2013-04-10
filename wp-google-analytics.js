(function($){ // Open closure and map jQuery to $.

	var custom = function(obj) {
		// It's a custom event if the link is inside a span.wgaevent container
		return ( $(obj).closest('span.wgaevent').length >= 1 );
	}

	var external = function(obj) {
		return !obj.href.match(/^mailto\:/) && !obj.href.match(/^javascript\:/) && (obj.hostname != location.hostname);
	}

	// Adds :external for grabbing external links (that don't belong tu custom events!)
	$.expr[':'].external = function(obj) {
		return ( wga_settings.external == 'true' ) && !custom(obj) && external(obj);
	};

	// Adds :custom for custom events (they don't have to be external links!)
	$.expr[':'].custom = function(obj) {
		return ( wga_settings.custom == 'true' ) && custom(obj);
	};

	var send_event = function(e, href, params) {
		try {
			_gaq.push( [ '_trackEvent' ].concat( params ) );
			/**
			 * If this link is not opened in a new tab or window, we need to add
			 * a small delay so the event can fully fire.  See:
			 * http://support.google.com/analytics/bin/answer.py?hl=en&answer=1136920
			 *
			 * We're actually checking for modifier keys or middle-click
			 */
			if ( ! ( e.metaKey || e.ctrlKey || 1 == e.button ) ) {
				e.preventDefault();
				setTimeout('document.location = "' + href + '"', 100)
			}
		} catch(err) {}
	}

	// Document ready.
	$( function() {
		// Add 'external' class and _blank target to all external links
		$('a:external').on( 'click.wp-google-analytics', function(e){
			send_event( e, $(this).attr('href'), [ 'Outbound Links', e.currentTarget.host, $(this).attr('href') ] );
		});

		$('a:custom').on( 'click.wp-google-analytics', function(e){
 			send_event( e, $(this).attr('href'), $(this).closest('span.wgaevent').data().wgaevent );
		});			
	});

})( jQuery ); // Close closure.
