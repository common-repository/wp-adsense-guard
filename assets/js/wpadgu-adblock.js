function adBlockDetected() {
	
	jQuery.ajax({
		type: 'POST',
		url: wpadgubadblock.ajaxurl,
		data: {
			"action": "wpadgu_adblock_detected", 
			"_nonce": wpadgubadblock.nonce,
			"ip": wpadgubadblock.ip,
			"adblockaction": wpadgubadblock.adblockaction,
			"adblockstatus": wpadgubadblock.adblockstatus,
			"country": wpadgubadblock.country,
			"browser": wpadgubadblock.browser,
			"dateline": wpadgubadblock.dateline,
			"current_url": wpadgubadblock.current_url,
		},
		success: function( data ){
			//console.log( "IP Blocked!" );
		}
	});
	
	if(typeof wpadgubadblock !== 'undefined' )
	{
		if( wpadgubadblock.adblockstatus=='enabled' )
		{
			if( wpadgubadblock.redirect_url!='')
			{
				
				setTimeout(function( $ )
					{
					window.location.href= wpadgubadblock.redirect_url;
					},	(wpadgubadblock.delay*1000)
				);
				//wpadgu_adblockModa
			} else if ( wpadgubadblock.adblockaction=='message' )
				{
					setTimeout(function( $ )
						{
						wpadgu_modal.style.display = 'block';
						},	(wpadgubadblock.delay*1000)
					);
				}
		}
	}
	//console.log('Adblock (Detected!)');
}
function adBlockNotDetected() {
	jQuery('.wpadgu_ad').show();
	//console.log('Adblock NOT Detected!');
}

if(typeof blockAdBlock === 'undefined') {
	adBlockDetected();
} else {
	blockAdBlock.setOption({ debug: false });
	blockAdBlock.onDetected(adBlockDetected).onNotDetected(adBlockNotDetected);
}
