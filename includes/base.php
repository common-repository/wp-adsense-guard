<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//get blog info
if( !function_exists( 'wpadgu_blog_info' ) )
{
	function wpadgu_blog_info( $blog_id=false, $request=false )
	{
		//if we have a blog ID
		if( $blog_id && is_numeric( $blog_id ) )
		{
			return get_blog_details( array( 'blog_id' => $blog_id ) );
		}
		//multisite is on
		if( function_exists( 'is_multisite') && is_multisite() )
		{
			$current_blog_id = get_current_blog_id();
			return get_blog_details( array( 'blog_id' => $current_blog_id ), $request);
		}
		//default
		return get_bloginfo($request);
	}
}

//die when error found
if( !function_exists( 'wpadgu_die' ) )
{
	function wpadgu_die( $echo=true, $message=false, $include_header=false, $include_footer=false )
	{
		$html = new wpadgu_html();
		
		//call wp_die when needed
		if( !$message ){wp_die(); exit;}
		//print header
		if( $include_header )
		{
			$html->header(__("Error", wpadgu_TRANS), __("Something wrong happened!", wpadgu_TRANS), 'dialog-error.png');
		}
		
		$ajax_url = add_query_arg(array(
				'action' => 'wpadgu_contact_support',
				'_nonce' => wp_create_nonce('contact-support')
		), admin_url('admin-ajax.php'));
		
		$output[] = "<div class='wpadgu_block_blank' style='width:100%'>";
		$output[] = "<table border=0 width=100% cellspacing=0>";
		$output[] = "<tr class='wpadgu_errorTD'><td width=16><img src='".plugins_url('/assets/img/blocked.png', wpadgu_PATH)."'></td>";
		$output[] = "<td>".$message."</td></tr>";
		$output[] = "<tr class='wpadgu_headTD' id='contact_support_btn'><td colspan=2>".$html->button_small(__("Contact Support",wpadgu_TRANS), "button", "contact-support-btn")."</td></tr>";
		
		//contact support
		$domain = wpadgu_getDomainUrl(wpadgu_selfURL());
		$admin_email = get_option('admin_email');
		$report = addslashes($message)."<hr>";
		$title = 'BRAVO -Client Support Request';
		$p = '';
		if( defined('wpadgu_CURRENT_PAGE_TITLE') ) 
		{
			$report .= "<strong>Page Title:</strong> ".wpadgu_CURRENT_PAGE_TITLE."<br />";
		}
		if( defined('wpadgu_CURRENT_PAGE') )
		{
			$report .= "<strong>Page:</strong> ".wpadgu_CURRENT_PAGE."<br />";
		}
		if( defined('wpadgu_CURRENT_PAGE_P') )
		{
			$report .= "<strong>Sub Page:</strong> ".wpadgu_CURRENT_PAGE_P."<br />";
		}
		if( defined('wpadgu_CURRENT_PAGE_ACTION') )
		{
			$report .= "<strong>Action -Sub Page:</strong> ".wpadgu_CURRENT_PAGE_ACTION."<br />";
		}
		
		if( isset($_GET['page'])){$p .= "<input type='hidden' name='page' value='".esc_html( $_GET['page'] )."'>";}
		if( isset($_GET['p'])){$p .= "<input type='hidden' name='p' value='".esc_html( $_GET['p'] )."'>";}
		if( isset($_GET['action'])){$p .= "<input type='hidden' name='p' value='".esc_html( $_GET['action'] )."'>";}
		
		$report .= "<strong>Domain:</strong>".$domain."<br />";
		$report .= "<strong>Admin E-Mail:</strong>".$admin_email;
		$output[] = "<tr class='wpadgu_headTD' id='contact_support_form' style='display:none;'>";
		$output[] = "<td colspan=2>";
		$output[] = "<form action='".$ajax_url."' method=post id='contactsupportform'>";
		$output[] = "<u>".__("Domain", wpadgu_TRANS).":</u> <i>".$domain."</i><br />";
		$output[] = "<u>".__("Your E-Mail", wpadgu_TRANS).":</u> <i>".$admin_email."</i><hr><br />";
		$output[] = "<strong>".__("Your Custom Message", wpadgu_TRANS).":</strong><br />";
		$output[] = "<textarea style='width:350px; height:75px' name='message'></textarea><br />";
		$output[] = "<input type='checkbox' name='report' value='yes' checked id='report'>";
		$output[] = "<input type='hidden' name='title' value='".$title."'>";
		$output[] = "<input type='hidden' name='report' value='".$report."'>".$p;
		$output[] = "<label for='report'>".__("Include Report", wpadgu_TRANS)."</label><br />";
		$output[] = $html->button_small(__("Send", "submit", "contact-support-submit"));
		$output[] = "<span id='wpadgu_send_loading' style='display:none;'><br /><img src='".plugins_url('assets/img/loading.gif', wpadgu_PATH)."'></span>";
		$output[] = "</form>";
		$output[] = "<div id='res'></div></td></tr>";
		
		$output[] = "</table></div>";
		
		$js = "<script>".PHP_EOL;
		$js .= "jQuery('#contact-support-btn').click(function(){".PHP_EOL;
		$js .= "jQuery('#contact_support_form').show();".PHP_EOL;
		$js .= "jQuery('#contact-support-btn').hide();".PHP_EOL;
		$js .= "});".PHP_EOL;
		$js .= "jQuery( \"#contactsupportform\" ).submit(function( event ) {
	// Stop form from submitting normally
		jQuery(\".wpadgu_loading\").show(); // Show Loading Box and Darken Background
		var url = jQuery( \"#contactsupportform\" ).attr (\"action\");
jQuery('#wpadgu_send_loading').show();
		jQuery.ajax({
	        url: url,
	        type: 'post',
	        dataType: 'html',
	        data: jQuery('form#contactsupportform').serialize(),
	        success: function(data) {
			jQuery( \"#res\" ).html( data );
	                 }
	    });

		
		event.preventDefault(); 
	});";
		$js .= "</script>";
		
		if( $echo ){ echo implode("\n", $output); echo $js;} else {return implode("\n", $output).$js;}
		//print footer
		if( $include_footer )
		{
			$html->footer();
		}
		
		exit;
	}
}

//Notices
if( !function_exists( 'wpadgu_notices' ) )
{
    function wpadgu_notices( $echo=true, $message, $type="success" )
    {
        $class = "success";
        if( $type=="error" ){$class = "error";}
        
        $output[] = "<div class=\"notice notice-$class is-dismissible\">";
        $output[] = "<p>".esc_js( $message )."</p>";
        $output[] = "</div>";
        
        if( $echo != true )
        {
            return implode("\n", $output);
        }
        
        echo implode("\n", $output);
    }
}
//create donate URL
if( !function_exists( 'wpadgu_create_donate_url' ) )
{
	function wpadgu_create_donate_url( $position=false )
	{
		return add_query_arg(
				array(
				        'utm_source' => wpadgu_getDomainUrl(wpadgu_selfURL()),
						'utm_medium' => 'adsguard_trial',
						'utm_content' => $position,
				), WPADGU_DONATE_URL);
	}
}

//creat plugin action links
if( !function_exists( 'wpadgu_plugin_action_links' ) )
{
	function wpadgu_plugin_action_links( $links )
	{
		$wpadgu_version_type= 'free';
		if( defined('WPADGU_VERSIONTYPE')){$wpadgu_version_type= WPADGU_VERSIONTYPE;}
		
		if( $wpadgu_version_type!= 'pro')
		{
			$wpadgu_docs_url = "http://technoyer.com/adsguard";
		} else {
			$wpadgu_docs_url = "http://technoyer.com/adsguard";
		}
		
		$links[] = '<a href="' . esc_url( $wpadgu_docs_url) . '">' . __( 'Docs', WPADGU_TRANS ) . '</a>';
		if( $wpadgu_version_type != 'PRO' ){
		    $links[] = '<a target="_blank" href="'.wpadgu_create_donate_url('action_links').'"><strong style="color: #2296D8; display: inline;">' . __( 'Upgrade To Pro', WPADGU_TRANS ) . '<strong></a>';
		}
		
		return $links;
	}
}

//install plugin
if( !function_exists( 'wpadgu_install' ) )
{
    function wpadgu_install()
    {
        global $wpdb;
        
        if( defined( 'WPADGU_VERSION' ) )
        {
            if( !wpadgu_utility::is_option( WPADGU_PREFIX.'version') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'version' , WPADGU_VERSION);}
        }
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'status') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'status' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'default_margin') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'default_margin' , '14');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'desktop_status') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'desktop_status' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'phones_status') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'phones_status' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'adsense_checker') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'adsense_checker' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'unique_content_checker') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'unique_content_checker' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'google_apikey') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'google_apikey' , '');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'google_engine_id') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'google_engine_id' , '');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'slices') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'slices' , '12');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'guard_status') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'guard_status' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'max_clicks') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'max_clicks' , '5');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'cookie_expiration') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'cookie_expiration' , '1');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'ban_duration') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'ban_duration' , '3');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'ban_strategy') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'ban_strategy' , 'cookie_db');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'blacklist_countries') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'blacklist_countries' , '');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'blacklist_bots') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'blacklist_bots' , 'amazonwas.com,ahrefs.com');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'adblock_status') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'adblock_status' , 'enabled');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'adblock_action') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'adblock_action' , 'message');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'adblock_message') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'adblock_message' , __("Please disable Adblock for this site.", WPADGU_TRANS));}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'adblock_redirect_url') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'adblock_redirect_url' , '');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'security_hash') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'security_hash' , wpadgu_utility::create_hash(8));}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'reports') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'reports' , '');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'min_words') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'min_words' , '250');}
        if( !wpadgu_utility::is_option( WPADGU_PREFIX.'adblock_action_delay') ) {wpadgu_utility::add_option(WPADGU_PREFIX.'adblock_action_delay' , '5');}
        
        $charset_collate = $wpdb -> get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.WPADGU_PREFIX."ads (
        id mediumint(11) NOT NULL AUTO_INCREMENT,
        ad_title char(75) NOT NULL,
        ad_code mediumtext,
        ad_position char(25) NOT NULL,
        ad_alignment char(25) NOT NULL,
        ad_pages mediumtext,
        ad_margin char(25) NOT NULL,
        ad_frame char(25) NOT NULL,
        ad_frame_label_title varchar(255) NOT NULL,
        ad_border_color char(25) NOT NULL,
        lastupdate char(25) NOT NULL,
        admin_id int(11) NOT NULL,
        blog_id int(11) NOT NULL,
        unique_code char(25) NOT NULL,
        min_words int(11) NOT NULL,
        ad_turn int(11) NOT NULL,
        ad_status char(25) NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";
        
        $sql1 = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.WPADGU_PREFIX."blocked_ips (
        id mediumint(11) NOT NULL AUTO_INCREMENT,
        ipaddress char(75) NOT NULL,
        country_code char(25) NOT NULL,
        clicks char(25) NOT NULL,
        when_unblock char(25) NOT NULL,
        dateline char(25) NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";
        
        $sql2 = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.WPADGU_PREFIX."nots (
        id mediumint(11) NOT NULL AUTO_INCREMENT,
        note_title char(75) NOT NULL,
        note_category char(75) NOT NULL,
        note_level char(75) NOT NULL,
        note_desc mediumtext,
        post_id mediumint(11) NOT NULL,
        is_read mediumint(11) NOT NULL,
        is_emailed mediumint(11) NOT NULL,
        read_by mediumint(11) NOT NULL,
        read_time char(25) NOT NULL,
        dateline char(25) NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once ABSPATH .'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        dbDelta($sql1);
        dbDelta($sql2);
    }
}

//uninstall plugin
if( !function_exists( 'wpadgu_uninstall' ) )
{
    function wpadgu_uninstall()
    {
        
    }
}

//UTILITY CLASS
if( !class_exists('wpadgu_utility'))
{
	class wpadgu_utility
	{
		/**
		 * Get the master blog table prefix
		 * @return string
		 */
		public static function dbprefix()
		{
			global $wpdb;
			
			$blog_id = 1;
			if( defined( 'BLOG_ID_CURRENT_SITE' ) )
			{
				$blog_id = BLOG_ID_CURRENT_SITE;
			}
			$main_blog_prefix = $wpdb->get_blog_prefix( $blog_id );
			
			//return only main blog prefix
			return $main_blog_prefix.WPADGU_PREFIX;
		}
		public static function get_sites()
		{
			if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
				$sites = get_sites();
				if( is_array( $sites) ) 
				{
					return $sites;
				}
			}
		}
		/**
		 * get wordpress option
		 * @param string $option_name
		 * @param string $option_value
		 * @param string $default
		 * @return mixed|boolean
		 */
		public static function get_option( $option_name, $default=false)
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return get_site_option($option_name, $default);
			}
			
			return get_option($option_name, $default);
		}
		/**
		 * add new option to wordpress
		 * @param string $option_name
		 * @param string $option_value
		 * @return boolean
		 */
		public static function add_option( $option_name, $option_value='' )
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return add_site_option($option_name, $option_value);
			}
			
			return add_option($option_name, $option_value);
		}
		/**
		 * update an exisiting option
		 * @param string $option_name
		 * @param string $option_value
		 * @return boolean
		 */
		public static function update_option( $option_name, $option_value='')
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return update_site_option($option_name, $option_value);
			}
			
			return update_option($option_name, $option_value);
		}
		/**
		 * delete wordpress option
		 * @param string $option_name
		 * @return boolean
		 */
		public static function delete_option( $option_name )
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return delete_site_option($option_name);
			}
			
			return delete_option($option_name);
		}
		/**
		 * check if option exists
		 * @param string $option_name
		 * @return boolean
		 */
		public static function is_option( $option_name )
		{
			if( self::get_option( $option_name ) )
			{
				return true;
			}
			return false;
		}
		/**
		 * get wordpress blog info
		 * @param string $show
		 * @return string
		 */
		public static function get_bloginfo( $show )
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				$blog_id = get_current_blog_id();
				$current_blog_details = get_blog_details( array( 'blog_id' => $blog_id ) );
				
				if( $show == 'name' ) {$show = 'blogname';}
				
				return $current_blog_details->$show;
					
			}
			
			if( $show == 'siteurl' ) {$show = 'wpurl';}
			return get_bloginfo( $show );
		}
		
		public static function get_securityhash()
		{
			$hash = self::get_option( WPADGU_PREFIX.'security_hash');
			if( !empty( $hash ) )
			{
				return esc_html( $hash );
			} else {
				$new_hash = self::create_hash( 8 );
				self::update_option( WPADGU_PREFIX.'security_hash', $new_hash);
				return $new_hash;
			}
			
		}
		//create hash
		public static function create_hash( $length ) {
		    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		    $hash = array(); //remember to declare $pass as an array
		    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		    for ($i = 0; $i < $length; $i++) {
		        $n = rand(0, $alphaLength);
		        $hash[] = $alphabet[$n];
		    }
		    return implode($hash); //turn the array into a string
		}
		
		/**
		 * Checks if a blog exists and is not marked as deleted.
		 *
		 * @param  int $blog_id
		 * @param  int $site_id
		 * @return bool
		 */
		public static function blog_exists( $blog_id, $site_id = 0 ) {
			
			global $wpdb;
			static $cache = array ();
			
			$site_id = (int) $site_id;
			
			if ( 0 === $site_id )
				$site_id = get_current_site()->id;
				
				if ( empty ( $cache ) or empty ( $cache[ $site_id ] ) ) {
					
					if ( wp_is_large_network() ) // we do not test large sites.
						return TRUE;
						
						$query = "SELECT blog_id FROM $wpdb->blogs
						WHERE site_id = $site_id AND deleted = 0";
						
						$result = $wpdb->get_col( $query );
						
						// Make sure the array is always filled with something.
						if ( empty ( $result ) )
							$cache[ $site_id ] = array ( 'do not check again' );
							else
								$cache[ $site_id ] = $result;
				}
				
				return in_array( $blog_id, $cache[ $site_id ] );
		}
		
		public static function get_access( $permission, $is_admin=false )
		{
		    if( $permission && $permission == 'manage_options' )
		    {
		        if( function_exists( 'is_multisite' ) && is_multisite() )
		        {
		            $permission = 'manage_network_options';
		        }
		    }
		    $counter = 0;
		    
		    if( !$is_admin){
		        if( current_user_can( $permission ) === true )
		        {
		            $counter = 1;
		        }
		    } else {
		        if( current_user_can( $permission ) === true
		            && is_admin())
		        {
		            $counter = 2;
		        }
		    }
		    
		    if( $counter > 0 )
		    {
		        return true;
		    }
		    
		    return false;
		}
		
	}

}

//uninstall the plugin
if( !function_exists( 'wpadgu_uninstall' ) )
{
    function wpadgu_uninstall()
    {
        global $wpdb;
        
        //delete options
        $wpdb->query("DELETE FROM $wpdb->options WHERE
            option_name LIKE '%".WPADGU_PREFIX."%';");
        
        //delete sitemeta for network
        if( function_exists('is_multisite') && is_multisite() )
        {
            $wpdb->query("DELETE FROM $wpdb->sitemeta WHERE
                meta_key LIKE '%".WPADGU_PREFIX."%';");
        }
        
        //delete tables
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.WPADGU_PREFIX."ads");
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.WPADGU_PREFIX."blocked_ips");
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.WPADGU_PREFIX."nots");
    }
}
?>