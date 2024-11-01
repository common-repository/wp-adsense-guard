if( typeof(wpadgu) != 'undefined' )
{
	//ajax call 
	var data = {
	    action: 'wpadgu_calc_click',
	};
	var ajaxurl = wpadgu.ajaxurl;  //WHAT IS THIS?!?!
	jQuery('.wpadgu_ad').click(function( $ )
	{
		jQuery.post(ajaxurl, data, function(response) {
		    console.log(response);
		});
	}		
	);
	
}

