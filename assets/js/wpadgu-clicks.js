(function($){
	var wpadguCookies = Cookies.noConflict();
	if ( jQuery(".wpadgu_ad").length > 0 ) {
		if( typeof wpadguCookies.get('wpadgu_clicks_count') === 'undefined' ) {
			var count = 0;
		} else {
			var count = wpadguCookies.get('wpadgu_clicks_count');
		}

		if( count > wpadguclicks.max_clicks ) {
			jQuery(".wpadgu_ad").css({ display: "none" });
		} else {
			jQuery(".wpadgu_ad div").click(function($){
				
					++count; 
					wpadguCookies.set('wpadgu_clicks_count', count, { expires: ( wpadguclicks.cookie_expiration )/24 });
					if( count > wpadguclicks.max_clicks ) {
						jQuery(".wpadgu_ad").css({ display: "none" });
						jQuery.ajax({
							type: 'POST',
							url: wpadguclicks.ajaxurl,
							data: {
								"action": "wpadgu_click_banip", 
								"_nonce": wpadguclicks.nonce,
								"ip": wpadguclicks.ip,
								"country": wpadguclicks.country,
								"cookie_expiration": wpadguclicks.cookie_expiration,
								"ban_duration": wpadguclicks.ban_duration,
								"dateline": wpadguclicks.dateline,
								"wpadgu_clicks_count": (count - 1)
							},
							success: function( data ){
								//console.log( "IP Blocked!" );
							}
						});
					}
			    
			});
		}
	}
})(jQuery);

