<?php 
/**
 * Plugin Name: WP Adsense Guard
 * Plugin URI: http://technoyer.com/adsguard
 * Description: Wordpress ads manager and ads protection from invalid clicks, adblock and unwanted bots. Create unlimited ads for adsense, affiliates and custom banners. If you are using <strong>Google Adsense</strong>, The plugin will help you to follow the adsense guidelines.
 * Author: Technoyer Solutions Ltd.
 * Author URI: http://technoyer.com
 * Version: 1.0
 * Network: true
 * License: GPL V3
 * Domain Path: /languages
 * Text Domain: wp-adsense-guard
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//Include Config
include 'config.php';

if( !class_exists( 'wpadgu_plugin' ) )
{
    class wpadgu_plugin
    {
        //create only one instance
        public static function init()
        {
            static $instance = null;
            
            if( !$instance ) { $instance = new wpadgu_plugin(); }
            
            return $instance;
        }
        
        //options
        public 
            $version,
            $version_type,
            $slug,
            $prefix,
            $html,
            $path,
            $dir,
            $menu_icon,
            $admin_url,
            $docs_url,
            $available_columns=array(),
            $donate_url,
            $split_words,
            $min_words,
            $can_see_ads,
            $default_ad_turn,
            $security_hash,
            $start_tag_desc,
            $end_tag_desc;
            
        //class contructor
        public function __construct()
        {
            //set plugin version and version type
            $this->version = "1.0";
            $this->version_type = "free";
            if( defined( 'WPADGU_VERSION' ) && WPADGU_VERSION!="" ){$this->version = WPADGU_VERSION;}
            if( defined( 'WPADGU_VERSION_TYPE' ) && WPADGU_VERSION_TYPE!="" ){$this->version_type = WPADGU_VERSION_TYPE;}
            
            //set the plugin slug and prefix
            $this->slug = "wp-adsense-guard";
            $this->prefix = "wpadgu";
            if( defined( 'WPADGU_SLUG' ) && WPADGU_SLUG!="" ){ $this->slug = WPADGU_SLUG; }
            if( defined( 'WPADGU_PREFIX' ) && WPADGU_PREFIX!="" ){ $this->prefix = WPADGU_PREFIX; }
            
            //set the plugin path and directory
            $this->path = __FILE__;
            $this->dir = dirname( __FILE__ );
            if( defined( 'WPADGU_PATH' ) && WPADGU_PATH!="" ){ $this->path = WPADGU_PATH; }
            if( defined( 'WPADGU_DIR' ) && WPADGU_DIR!="" ){ $this->dir = WPADGU_DIR; }
            
            //set the plugin URLs
            $this->admin_url = admin_url("admin.php?page=". esc_js( $this->slug));
            if( is_multisite() )
            {
                $this->admin_url = network_admin_url("admin.php?page=". esc_js( $this->slug));
            }
            
            $this->docs_url = "http://technoyer.com/adsguard";
            $this->donate_url = "http://technoyer.com/adsguard/pro.php";
            
            //Install and active the plugin
            register_activation_hook( $this->path , array( $this, 'activate' ) );
            
            //Deactivate the plugin
            register_deactivation_hook ( $this->path, array ( $this , 'de_activate' ) );
            
            //UnInstall the plugin
            register_uninstall_hook ( $this->path, 'wpadgu_uninstall' );
            
            //Include CSS and JS files
            add_action ( 'admin_enqueue_scripts' , array ($this , 'include_scrips_and_css' ) );
            add_action ( 'wp_enqueue_scripts' , array ($this , 'frontend_scripts_styles' ) );
            
            //Add Menu
            $ms_hook = is_multisite() ? 'network_' : '';
            if( is_admin() ){add_action( $ms_hook.'admin_menu' , array( $this , 'menu' ));}
            
            //menu icon
            $this->menu_icon = plugins_url("/assets/img/adsguard_icon.png", $this->path);
            
            //security hash
            $this->security_hash = wpadgu_utility::get_option(WPADGU_PREFIX.'security_hash');
            
            //avaiable db table columns
            $this->available_columns= array(
                "id",
                "ad_title",
                "ad_code",
                "ad_position",
                "ad_alignment",
                "ad_pages",
                "ad_margin",
                "ad_frame",
                "ad_frame_label_title",
                "ad_border_color",
                "unique_code",
                "lastupdate",
                "admin_id",
                "blog_id",
            );
            
            $this->min_words = (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'min_words') ) );
            $this->split_words = 250;
            $this->default_ad_turn = 1;
            $this->start_tag_desc = "";
            $this->end_tag_desc = "";
            
            add_action( 'init', array( $this, 'filter_ads') ) ;
            add_action( 'wp_footer', array( $this, 'place_ad_footer') ) ;
            
            add_shortcode( 'wpadgu_ads', array( $this, 'shortcodes') );
            
            //Guard Actions
            add_action('wp_ajax_wpadgu_calc_click', array( $this, 'wpadgu_calc_click' ) );
            add_action('wp_ajax_nopriv_wpadgu_calc_click', array( $this, 'wpadgu_calc_click' ) );
            add_action('wp_ajax_wpadgu_click_banip', array( $this, 'wpadgu_click_banip' ) );
            add_action('wp_ajax_nopriv_wpadgu_click_banip', array( $this, 'wpadgu_click_banip' ) );
            add_action('wp_ajax_wpadgu_adblock_detected', array( $this, 'wpadgu_adblock_detected' ) );
            add_action('wp_ajax_nopriv_wpadgu_adblock_detected', array( $this, 'wpadgu_adblock_detected' ) );
            
            //front end JS
            add_action("wp_footer", array($this, "tester"), 10 );
            
            //cronjob hourly event
            add_action( 'wpadgu_hourly_event', 'wpadgu_hourly' );
            
            add_action('wp_ajax_wpadgu_delete_note', 'wpadgu_nots::delete' );
            add_action('wp_ajax_ignore_post_guidelines_alert', 'wpadgu_nots::ignore_post_guidelines_alert' );
            add_action('wp_ajax_wpadgu_delete_guard_log', 'wpadgu_guard::delete' );
            
            //enable shortcodes in widgets
            add_filter('widget_text','do_shortcode');
        }
        
        //css and scripts
        public function include_scrips_and_css()
        {
            if( !is_rtl() )
            {
                wp_enqueue_style ($this->slug."_css", plugins_url('/assets/css/backend_style.css', $this->path));
            } else {
                wp_enqueue_style ($this->slug."_css", plugins_url('/assets/css/backend_style_rtl.css', $this->path));
            }
            
        }
        
        //the frontend JS and CSS
        public function frontend_scripts_styles()
        {
            
            if( !is_rtl() )
            {
                wp_enqueue_style ($this->slug."_frontend_css", plugins_url('/assets/css/css.css', $this->path));
            } else {
                wp_enqueue_style ($this->slug."_frontend_css", plugins_url('/assets/css/css_rtl.css', $this->path));
            }
            
            if( isset( $_GET['p']) && $_GET['p'] == $this->slug )
            {
                wp_enqueue_style ($this->slug."_frontend_css", plugins_url('/assets/css/flags.css', $this->path));
            }
            wp_enqueue_script ("wpadgu_clicks_cookie", plugins_url($this->slug.'/assets/js/js.cookie.js'), '', '', true);
            //wp_enqueue_script ("wpadgu_adblock", plugins_url($this->slug.'/assets/js/fuckAdblock.js'), '', '', true);
            wp_enqueue_script ("wpadgu_blockAdblock", plugins_url($this->slug.'/assets/js/blockAdblock.js'), '', '', false);
            wp_enqueue_script ("wpadgu_technoadblock", plugins_url($this->slug.'/assets/js/wpadgu-adblock.js'), '', '', false);
            wp_enqueue_script ("wpadgu_clicks", plugins_url($this->slug.'/assets/js/wpadgu-clicks.js'), '', '', true);
            wp_enqueue_script ("wpadgu_frontend", plugins_url($this->slug.'/assets/js/wpadgu-frontend.js'), '', '', true);
           /* wp_localize_script( "wpadgu_frontend",'wpadgu', array(
                'ajaxurl' => admin_url('admin-ajax.php') )
                );*/
            wp_localize_script(
                'wpadgu_clicks', //id
                'wpadguclicks', // The name using which data will be fetched at the JS side
                array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( "wpadgu-click" ),
                    'ip' => wpadgu_agent::user_ip(),
                    'country' => wpadgu_agent::ip2country(),
                    'max_clicks' => wpadgu_utility::get_option(WPADGU_PREFIX.'max_clicks'),
                    'cookie_expiration' => wpadgu_utility::get_option(WPADGU_PREFIX.'cookie_expiration'),
                    'ban_duration' => wpadgu_utility::get_option(WPADGU_PREFIX.'ban_duration'),
                    'dateline' => time(),
                ) // all data that are being passed to the js file
                );
            
            $browser_array = wpadgu_agent::getBrowser();
            $browser = '';
            if( is_array( $browser_array ) )
            {
                $browser = $browser_array['name'];
            }
            
            $adblock_action = wpadgu_utility::get_option($this->prefix.'adblock_action');
            $option_url = wpadgu_utility::get_option($this->prefix.'adblock_redirect_url');
            $delay = wpadgu_utility::get_option($this->prefix.'adblock_action_delay');
            
            $redirect_url = '';
            if( !isset( $_GET['wpadgu_h'] ) || $_GET['wpadgu_h'] !='true' )
            {
                if( !empty( $adblock_action ) && $adblock_action=='redirect' )
                {
                    if( !empty($option_url) && filter_var($option_url, FILTER_VALIDATE_URL) )
                    {
                        $redirect_url = $option_url;
                    }
                } 
            }
            
            if( $adblock_action == 'message' )
            {
                add_action('wp_footer', array( $this, 'popup_adblock_detected'), 10);
            }
            
            //prevent redirect loop
            if( $adblock_action == 'redirect' && $option_url==wpadgu_selfURL()){return;}
            
                wp_localize_script(
                    'wpadgu_technoadblock', //id
                    'wpadgubadblock', // The name using which data will be fetched at the JS side
                    array(
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'nonce' => wp_create_nonce( "wpadgu-adblock" ),
                        'ip' => wpadgu_agent::user_ip(),
                        'country' => wpadgu_agent::ip2country(),
                        'browser' => $browser,
                        'adblockstatus' => wpadgu_utility::get_option(WPADGU_PREFIX.'adblock_status'),
                        'adblockaction' => wpadgu_utility::get_option(WPADGU_PREFIX.'adblock_action'),
                        'dateline' => time(),
                        'redirect_url' => $redirect_url,
                        'option_url' => $option_url,
                        'delay' => $delay,
                    ) // all data that are being passed to the js file
                    );
            
            
        }
        
        //the adblock popup message
        public function popup_adblock_detected()
        {
            $message = __("Oops! Adblock Detected! Please support us, disable it for us.", WPADGU_TRANS);
            $adblock_action_message = wpadgu_utility::get_option($this->prefix.'adblock_message');
            $adblock_action_delay = wpadgu_utility::get_option($this->prefix.'adblock_action_delay');
            if( !empty( $adblock_action_message ) ){ $message = $adblock_action_message; }
            
            $output[] = '<div id="wpadgu_adblockModal" class="wpadgu_modal" style="display:none;">';
            $output[] = '<div class="wpadgu_modal-content">';
            $output[] = '<div class="wpadgu_modal-header"><h2>'.__("AdBlock Detected!", WPADGU_TRANS).'</h2>';
            $output[] = '<span class="wpadgu_close">&times;</span>';
            $output[] = '</div>';
            $output[] = '<div class="wpadgu_modal-body">';
            $output[] = '<h4>'.$message.'</h4>';
            $output[] = '</div>';
            $output[] = '</div>';
            $output[] = '</div>';
            
            echo implode("\n", $output);
            
            ?>
            <script>
var wpadgu_modal = document.getElementById('wpadgu_adblockModal');
var wpadgu_btn = document.getElementById("wpadgu_myBtn");
var wpadgu_span = document.getElementsByClassName("wpadgu_close")[0];
/**
setTimeout(function()
{
	wpadgu_modal.style.display = "block";
}, <?php echo $adblock_action_delay*1000;?>);
*/
	
wpadgu_span.onclick = function() {
wpadgu_modal.style.display = "none";
}
window.onclick = function(event) {
if (event.target == wpadgu_modal) {
	wpadgu_modal.style.display = "none";
}
}
</script>
            <?php 
        }
        //plugin actions and filters
        public function tester()
        {
           ?>
			
           <?php 
        }
        
        //menu
        //Menu
        public function menu()
        {
            if( true == current_user_can( 'manage_options' ) )
            {
                $count = wpadgu_nots::count();
                $extra_nots = '';
                if( $count > 0 )
                {
                    $extra_nots = "<span class='update-plugins wpadgu_nots count-1'><span class='update-count'>{$count}<span></span>";
                }
                add_menu_page(__('WP Ads Guard', WPADGU_TRANS),
                    __('WP Ads Guard', WPADGU_TRANS).$extra_nots,
                    'manage_options',
                    $this->slug,
                    '',
                    $this->menu_icon);
                
                //Dashboard
                add_submenu_page($this->slug,
                    __('WP Ads Guard Dashboard',WPADGU_TRANS),
                    __('Dashboard',WPADGU_TRANS),
                    'manage_options',
                    $this->slug,
                    array ($this,'dashboard'));
                
                //Ads
                add_submenu_page($this->slug,
                    __('WP Ads Guard - Ads Management',WPADGU_TRANS),
                    __('Ads Management',WPADGU_TRANS),
                    'manage_options',
                    $this->slug.'-ads',
                    array ($this,'dashboard_ads'));
                
                //Settings
                add_submenu_page($this->slug,
                    __('WP Ads Guard Settings',WPADGU_TRANS),
                    __('Settings',WPADGU_TRANS),
                    'manage_options',
                    $this->slug.'-settings',
                    array ($this,'dashboard_settings'));
                
               
                if( function_exists( 'wpadgu_is_pro') && !wpadgu_is_pro() )
                {
                    add_submenu_page($this->slug,
                        __('Go to Premium',WPADGU_TRANS),
                        "<span style='color:#86CCF9'>".__('Go to Premium',WPADGU_TRANS)."</span>",
                        'manage_options',
                        $this->slug.'-pro',
                        array ($this,'dashboard_pro')); 
                }
                do_action($this->prefix.'admin_menu');
                
            }
        }
        
        //activate plugin
        public function activate()
        {
            //install options and DB tables
            wpadgu_install();
            //shedule events
            if (! wp_next_scheduled ( 'wpadgu_hourly_event' )) {
                wp_schedule_event(time(), 'hourly', 'wpadgu_hourly_event' );
            }
            
            do_action('wpadgu_activate');
        }
        
        //deactivate plugin
        public function de_activate()
        {
            wp_clear_scheduled_hook( 'wpadgu_hourly' );
        }
        
        public function dashboard_pro()
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            $html = new wpadgu_html();
            $html->header(__("Go to Premium", WPADGU_TRANS) );
            
            echo "<hr>";
            echo '<div class="wpadgu_pro">';
            
            echo "<table border=0 width=100% cellspacing=0>";
            echo "<tr><td style='width:60%' valign=top>";
            echo "<h2>".__("What is new in the pro version?", WPADGU_TRANS)."</h2>"; echo "<hr>";
            echo "<h3>".__("Adsense Content Guidelines Checker", WPADGU_TRANS)."</h3>";
           
            echo "<ul>";
            
            echo "<li><span style='color:green; font-weight:bold; font-size:18px;'>&radic;</span> <strong>".__("Plagiarism Checker", WPADGU_TRANS)."</strong>: <br />";
            echo __("For perfectly content for Google Adsense, You should be sure that your content is unique, So we have added this tool to the plugin to help you to detect the copied content before showing ads.", WPADGU_TRANS);
            echo "</li>";
            
            echo "<li><span style='color:green; font-weight:bold; font-size:18px;'>&radic;</span> <strong>".__('Spam Ads', WPADGU_TRANS)."</strong>: <br />";
            echo __("Yes, No limits for Google Adsense ad units per page, But we should be careful, So the plugin detects the too many ads compared to the original post content, then send notification to you, You can fix this issue or ignore it.", WPADGU_TRANS);
            echo "</li>";
            
            echo "<li><span style='color:green; font-weight:bold; font-size:18px;'>&radic;</span> <strong>".__("Illegal Content Checker", WPADGU_TRANS)."</strong>: <br />";
            echo __("Automatic check in order to detect any illegal content, for example: alcohol content, adult content, software cracks content, weapons (sell/buy) content, hackers content and encourage to click content, Then plugin send notification to you to fix this or ignore it.", WPADGU_TRANS);
            echo "</li>";
            
            echo "</ul>";
            echo "<hr>";
            
            echo "<h3>".__("Invalid Clicks (fraud clicks or scam clicks) Detecting", WPADGU_TRANS)."</h3>";
            echo "<ul>";
            
            echo "<li><span style='color:green; font-weight:bold; font-size:18px;'>&radic;</span> <strong>".__("Block Bad Bots (crawlers)", WPADGU_TRANS)."</strong>: <br />";
            echo __("Some crawlers, or bots while it visit your pages, it tries to open the Google Adsense link or Google Adsense iframe (which contains ads), So sometime these bots (crawler) cause invalid clicks which affect directly your monthly earnings, The plugin lets you to block these bots (crawler), If it's blocked, The ads will disappear.", WPADGU_TRANS);
            echo "</li>";
            echo "</ul>";
            
            echo "<h3>".__("Adblocker Detection", WPADGU_TRANS)."</h3>";
            echo "<ul>";
            
            echo "<li><span style='color:green; font-weight:bold; font-size:18px;'>&radic;</span> <strong>".__("Hide Content", WPADGU_TRANS)."</strong>: <br />";
            echo __("If the plugin detects Adblock was installed in the visitor's browser, You have the option to hide the post or page content until the visitor disable Adblock extension for your website.", WPADGU_TRANS);
            echo "</li>";
            echo "</ul>";
            
            echo "<center>";
            echo "<a href='".wpadgu_create_donate_url()."' target=_blank><img src='".plugins_url('/assets/img/image091.png', $this->path)."'></a>";
            echo "</center>";
            echo "</td>";
            
            echo "<td valign=top>";
            echo "<center><a href='https://www.youtube.com/watch?v=juC7k4JT4Lk' target=_blank><img src='".plugins_url('/assets/img/plag_ad.png', $this->path)."'></a><br />";
            echo "<a href='https://www.youtube.com/watch?v=vvu5xjSMUX8' target=_blank><img src='".plugins_url('/assets/img/hide_ad.png', $this->path)."'></a><br />";
            echo "</center>";
            echo "</td></tr></table>";
            echo '</div>';
            
            $html->footer();
        }
        //main dashboard for admin area
        public function dashboard()
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            $html = new wpadgu_html();
            $html->header(__("WP Ads Guard Dashboard", WPADGU_TRANS) );
            $html->tabs();
            $html->footer();
            
            if( isset($_GET['p']) && $_GET['p'] == 'test' )
            {
                $adsene = new wpadgu_adsense();
                $strings = $adsene->split_post(1);
                $i=0;
                foreach ( $strings as $words )
                {
                    $i++;
                      if( $i==1 )
                      {
                          $string = $words;
                      }
                }
                //$string = "Often beginners ask us: Why should I use WordPress? Isn’t my old site good enough";
                //echo $string;
                $response = $adsene->api_body( '"'.$string.'"' );
                //echo $response; exit;
                $results = json_decode($response['body'], true);
                echo $results['error']['code']."<br>";
                echo $results['error']['message']."<hr>";
                echo "<pre>";
                //var_dump($results); exit;
                echo '<pre>';
                echo "<pre>";var_dump($results['searchInformation']); echo "</pre><hr>";
                echo "Total Results: ".$results['searchInformation']['totalResults']."<hr>";
                
                foreach ($results['items'] as $itme )
                {
                    echo "Link: ".$itme["link"]."<br />";  
                }
                echo "<hr>";
                echo "<pre>";
                var_dump( $results );
                echo '</pre>';
                
                /*
                $url = wpadgu_encodeString('https://www.vogue.com/article/joan-didion-self-respect-essay-1961', $this->security_hash);
                $string = wpadgu_encodeString('we no longer answer the telephone, because someone might want something; that we could say no without drowning in self-reproach is an idea', $this->security_hash);
                
                echo "<a href='".$this->admin_url."&wpadgu_url=$url&post_id=16&wpadgu_nonce=sadas#".md5($string)."' target=_blank>Doos Hena</a>";
                */
                
            } else if ( !isset( $_GET['p'] ) )
            {
                //check access
                if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;} 
                
                global $wpdb;
                
                $dbTable = $wpdb->prefix.WPADGU_PREFIX.'blocked_ips';
                $count_log = $wpdb->get_var( "SELECT count(id) FROM {$dbTable} ORDER BY id DESC");
                
                echo "<div id='wpadgu_results' style='height:0px; width:0px;'></div>";
                echo "<table border=0 width=100% cellspacing=2>";
                echo "<tr><td width=40% valign=top>";
                echo "<h4>".__("Notifications", WPADGU_TRANS)."</h4><hr>";
                echo wpadgu_nots::nots_list(); 
                echo "</td>";
                echo "<td valign=top style='min-width:30%;max-width:40%;'>";
                echo "<h4>".__("Guard Log", WPADGU_TRANS)." <font class='wpadgu_smallfont'>[{$count_log}]</font></h4><hr>";
                echo $this->dashboard_log( $count_log );
                echo "</td>";
                echo "<td valign=top style='max-width:250px; padding:2px;'>";
                echo wpadgu_help::tuts();
                if( !wpadgu_is_pro() )
                {
                    echo wpadgu_help::products( wpadgu_create_donate_url( 'plugin_dashboard' ) );
                }
                echo "</td>";
                echo  "</tr></table>";
            }
        }
        
        //the LOG dashboard //html
        public function dashboard_log( $count )
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'blocked_ips';
            
            $results = $wpdb->get_results( "SELECT * FROM {$dbTable} ORDER BY id DESC");
            
            if( null!=$results )
            {
                $css_style = '';
                if( $count >= 10 ) {$css_style = "overflow-y:scroll;height:650px;";}
                
                $output = "<div style='{$css_style};padding:8px;' id='wpadgu_guard_log'>";
                $output .= "<table border=0 width=100% cellspacing=0>";
                
                $ajax_url = add_query_arg( array(
                    "action" => "wpadgu_delete_guard_log",
                    "_nonce" => wp_create_nonce( 'wpadgu-delete-guard-log')
                ), "admin-ajax.php");
                
                $js = "<script>".PHP_EOL;
                $js .= "jQuery(document).ready(function(){".PHP_EOL;
                foreach ( $results as $row )
                {
                    $flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($row->country_code).'" alt="'.$row->country_code.'" /> ';
                    
                    $summary = "<strong>".__("Clicks", WPADGU_TRANS)."</strong>: ".$row->clicks;
                    $expire = "<strong>".__("Exp. Date", WPADGU_TRANS)."</strong>: ".@date('d M Y', $row->when_unblock)." <font color=green>".@date('h:i A', $row->when_unblock)."</font><br />";
                    
                    $tools = "<span id='wpadgu_log_del".$row->id."' style='cursor:pointer'><img src='".plugins_url('/assets/img/delete.png', WPADGUPATH)."'></span>";
                    
                    $time = "n/a";
                    if( $row->dateline!='' )
                    {
                        $time = wpadgu_ago( $row->dateline )." ".__("ago", WPADGU_TRANS);   
                    }
                    $output .= "<tr class='wpadgu_underTD' id='wpadgu_guard_log_row".$row->id."' style='box-shadow: 0 1px 1px rgba(0, 0, 0, 0.125);'><td width=20>{$flag}</td><td width=80%>".$row->ipaddress." <span class='wpadgu_nots_time'>".$time."</span><br /><font class='wpadgu_smallfont'>{$summary}<br />{$expire}</font></td><td>{$tools}</td></tr>";
                    
                    $js .= "jQuery('#wpadgu_log_del".$row->id."').click( function( $ ){".PHP_EOL; 
                    $js .= "if (confirm('".__("Are you sure?!", WPADGU_TRANS)."')) {".PHP_EOL;
                    $js .= "jQuery('#wpadgu_guard_log_row".$row->id."').hide(500);".PHP_EOL;
                    $js .= "jQuery('#wpadgu_results').load('".$ajax_url."&id=".$row->id."');".PHP_EOL;
                    $js .= "}".PHP_EOL;
                    $js .= "return false".PHP_EOL;
                    $js .= "});".PHP_EOL;
                }
                
                $js .= "});".PHP_EOL;
                $js .= "</script>".PHP_EOL;
                $output .= "</table></div>";
                $output .= $js;
            } else {
                $output = "<span style='color:#c9c9c9; size:9pt;'>".__("Log is empty!", WPADGU_TRANS)."</span>";
            }
            
            return $output;
        }
        
        //the plugin settings modification dasboard //HTML
        public function dashboard_settings()
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            $html = new wpadgu_html();
            $html->header(__("WP Ads Guard Settings", WPADGU_TRANS) );
            
            if( isset($_GET['p']) && $_GET['p'] == 'options_updated' )
            {
                wpadgu_notices(true,__("Settings Saved.", WPADGU_TRANS) );
            }
            
            $html->tabs("settings");
            
            $default_css_class = '';
            $pro_feature = '';
            if( !wpadgu_is_pro() )
            {
                $default_css_class = 'wpadgu_disabled_div';  
                $pro_feature = "<span class='wpadgu_pro'>".__("Pro Feature!", WPADGU_TRANS)."</span>";
            }
            
            $output[] = "<form action='".$this->admin_url."-settings&p=save' method='post'>";
            $output[] = "<input type='hidden' name='_nonce' value='".wp_create_nonce('save-settings-'.$this->prefix)."'>";
            $output[] = "<table border=0 width=100% cellspacing=0>";
            $output[] = "<tbody>";
            //Plugin Stats
            $plugin_status = wpadgu_utility::get_option($this->prefix.'status');
            $plugin_status_enabled = "selected"; $plugin_status_disabled = "";
            if( $plugin_status == 'disabled' )
            {
                $plugin_status_enabled = ""; $plugin_status_disabled = "selected";
            }
            $output[] = "<tr class='wpadgu_underTD wpadgu_grad'><td style='width:25%'>".__("Plugin Stats", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='status'>";
            $output[] = "<option value='enabled' {$plugin_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$plugin_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select>";
            $output[] = "</td></tr>";
            //Minimum Words
            $min_words= (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'min_words') ) );
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Minimum Words in post/page", WPADGU_TRANS)."</td>";
            $output[] = "<td><input type='text' name='min_words' value='$min_words' style='width:50px;'> ".__("Words", WPADGU_TRANS);
            $output[] = "</td></tr>";
            //Default Margin Size
            $default_margin = (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'default_margin') ) );
            if( $default_margin ==0 or empty( $default_margin ) ){$default_margin = "12";}
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Default Margin Size", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<input type='text' name='default_margin' value='{$default_margin}' style='width:45px;'> ".__("px", WPADGU_TRANS);
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("Its recommended to be +12 px .", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //Desktop Status
            $desktop_status = wpadgu_utility::get_option($this->prefix.'desktop_status');
            $desktop_status_enabled = "selected"; $desktop_status_disabled = "";
            if( $desktop_status == 'disabled' )
            {
                $desktop_status_enabled = ""; $desktop_status_disabled = "selected";
            }
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Show Ads on Desktop Devices", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='desktop_status'>";
            $output[] = "<option value='enabled' {$desktop_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$desktop_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select>";
            $output[] = "</td></tr>";
            //SmartPhones Status
            $phones_status = wpadgu_utility::get_option($this->prefix.'phones_status');
            $phones_status_enabled = "selected"; $phones_status_disabled = "";
            if( $phones_status == 'disabled' )
            {
                $phones_status_enabled = ""; $phones_status_disabled = "selected";
            }
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Show Ads on Smart Phones", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='phones_status'>";
            $output[] = "<option value='enabled' {$phones_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$phones_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select>";
            $output[] = "</td></tr>";
            //AdsenseChecker Status
            $adsense_status = wpadgu_utility::get_option($this->prefix.'adsense_checker');
            $adsense_status_enabled = "selected"; $adsense_status_disabled = "";
            if( $adsense_status == 'disabled' )
            {
                $adsense_status_enabled = ""; $adsense_status_disabled = "selected";
            }
            $output[] = "<tr class='wpadgu_headTD'><td colspan=2>".__("Adsense Options", WPADGU_TRANS)."</td></tr>";
            $output[] = "<tr class='wpadgu_underTD $default_css_class'><td style='width:25%'>".__("Adsense Content Guidelines Checker", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='adsense_checker'>";
            $output[] = "<option value='enabled' {$adsense_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$adsense_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select><br /><span class='wpadgu_smallfont'>".__("If you want the plugin check every post before show ads and make it compatible with Adsense guidelines.", WPADGU_TRANS)."</span>";
            /*$adsense_external_links = '';
            if( wpadgu_utility::get_option(WPADGU_PREFIX.'adsense_external_links') == 'checked' )
            {
                $adsense_external_links = 'checked';
            }
            $output[] = "<br /><input type='checkbox' name='adsense_external_links' value='checked' id='external' {$adsense_external_links}><label for='external'>".__("Also, Check external links!", WPADGU_TRANS)."</label>";
            */ 
            $output[] = "$pro_feature</td></tr>";
            //CopiedContent Status
            $unique_content_status = wpadgu_utility::get_option($this->prefix.'unique_content_checker');
            $unique_content_status_enabled = "selected"; $unique_content_status_disabled = "";
            if( $unique_content_status == 'disabled' )
            {
                $unique_content_status_enabled = ""; $unique_content_status_disabled = "selected";
            }
            
            $output[] = "<tr class='wpadgu_underTD $default_css_class'><td style='width:25%'>".__("Unique Content Checker For New Posts", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='unique_content_checker'>";
            $output[] = "<option value='enabled' {$unique_content_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$unique_content_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select><br /><span class='wpadgu_smallfont'>".__("Check every new post for copied content.", WPADGU_TRANS)."</span>";
            $output[] = "$pro_feature</td></tr>";
            $output[] = "<tr class='wpadgu_underTD $default_css_class'><td style='width:25%'>".__("Google Custom Search API", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<table border='0' width=100% cellspacing=0>";
            //API Key
            $google_apikey = wpadgu_utility::get_option($this->prefix.'google_apikey');
            $get_it_apikey = "<a href='https://developers.google.com/custom-search/json-api/v1/overview' target=_blank>".__("Get It", WPADGU_TRANS)."</a>";
            $output[] = "<tr class='wpadgu_underTD'><td>".__("API Key", WPADGU_TRANS).": <br />";
            $output[] = "<input type='text' name='google_apikey' value='{$google_apikey}' style='width:350px;'> {$get_it_apikey}<br />";
            //Engin ID
            $google_engine_id = wpadgu_utility::get_option($this->prefix.'google_engine_id');
            $get_it_engine = "<a href='https://support.google.com/customsearch/answer/2649143?hl=en' target=_blank>".__("Get It", WPADGU_TRANS)."</a>";
            $output[] = __("Engin ID", WPADGU_TRANS).": <br />";
            $output[] = "<input type='text' name='google_engine_id' value='{$google_engine_id}' style='width:350px;'> {$get_it_engine}";
            $output[] = "<br /><img src='".plugins_url('/assets/img/play-16.png', WPADGUPATH)."'> <a href='http://technoyer.com/adsguard/custom-api' target=_blank><span class='wpadgu_smallfont'>".__("Watch Video", WPADGU_TRANS)."</span></a><br />";
            //Search Slices
            $slices = wpadgu_utility::get_option($this->prefix.'slices');
            $splitter_maxlength = 150;
            if( defined( 'WPADGU_SLICE_LETTERES' ) ){$splitter_maxlength = WPADGU_SLICE_LETTERES;}
            $output[] = __("Slices", WPADGU_TRANS).": <br />";
            $output[] = "<input type='text' name='slices' value='{$slices}' style='width:50px;'><br />";
            $output[] = "<font class='wpadgu_smallfont'>".__("Every slice = one google search query, and you have maximum 100 queries per day for free google custom search API.", WPADGU_TRANS)."<br />";
            $output[] = __("Slice contains ", WPADGU_TRANS).$splitter_maxlength." ".__("Letters", WPADGU_TRANS)."</font>";
            $output[] = "$pro_feature</td></tr>";
            $output[] = "</table>";
            $output[] = "</td></tr>";
            //Guard Status
            $guard_status = wpadgu_utility::get_option($this->prefix.'guard_status');
            $guard_status_enabled = "selected"; $guard_status_disabled = "";
            if( $guard_status == 'disabled' )
            {
                $guard_status_enabled = ""; $guard_status_disabled = "selected";
            }
            $output[] = "<tr class='wpadgu_headTD'><td colspan=2>".__("Guard Options", WPADGU_TRANS)."</td></tr>";
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Guard Status", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='guard_status'>";
            $output[] = "<option value='enabled' {$guard_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$guard_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select>";
            $output[] = "</td></tr>";
            //Maximum Clicks
            $max_clicks = (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'max_clicks') ) );
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Maximum Clicks", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<input type='text' name='max_clicks' value='{$max_clicks}' style='width:45px;'> ".__("per 1 hour", WPADGU_TRANS);
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("Maximum clicks for one IP per hour before hiding ads for it.", WPADGU_TRANS)."</span>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("0 for disable this option.", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //Cookie Expire
            $cookie_expiration= esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'cookie_expiration') ) );
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Cookie Expiration", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<input type='text' name='cookie_expiration' value='{$cookie_expiration}' style='width:250px;'>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("In Hours", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //Ban Duration
            $ban_duration= esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'ban_duration') ) );
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Ban Duration", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<input type='text' name='ban_duration' value='{$ban_duration}' style='width:250px;'>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("In Hours", WPADGU_TRANS)."</span>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("They will not see the ads.", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //Ban Strategy
            $banSt_arr = array(
                __("User Cookie", WPADGU_TRANS) => 'cookie',
                __("User Cookie & Database", WPADGU_TRANS) => 'cookie_db',
            );
            
            $ban_strategy= esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'ban_strategy') ) );
            $banSt = '';
            foreach ( $banSt_arr as $key => $value )
            {
                $banSt .= "<option value='".$value."' ";
                if( $ban_strategy == $value ){$banSt .= "selected";}
                $banSt .= ">".$key."</option>";
            }
            
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Ban Strategy", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<select name='ban_strategy'>";
            $output[] = $banSt;
            $output[] = "</select>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("You are free to choose! But it is recommended to let it depends on both cookie and database.", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //Blacklist Countries
            $blacklist_countries= esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'blacklist_countries') ) );
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Blacklisted Countries", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<input type='text' name='blacklist_countries' value='{$blacklist_countries}' style='width:250px;'>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("Comma Separated in ISO ALPHA-2 e.g: US,UK,EG .", WPADGU_TRANS)."</span>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("They will not see the ads.", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //Blacklist Bots
            $blacklist_bots= esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'blacklist_bots') ) );
            $output[] = "<tr class='wpadgu_underTD $default_css_class'><td style='width:25%'>".__("Blacklisted Bots", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
            $output[] = "<input type='text' name='blacklist_bots' value='{$blacklist_bots}' style='width:250px;'>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("Comma Separated e.g: amazonaws.com,ahrefs.com .", WPADGU_TRANS)."</span>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("Do not add the google bots to the blacklist.", WPADGU_TRANS)."</span>";
            $output[] = "$pro_feature</td></tr>";
            //Adblock Status
            $adblock_status = wpadgu_utility::get_option($this->prefix.'adblock_status');
            $adblock_status_enabled = "selected"; $adblock_status_disabled = "";
            if( $adblock_status == 'disabled' )
            {
                $adblock_status_enabled = ""; $adblock_status_disabled = "selected";
            }
            $output[] = "<tr class='wpadgu_headTD'><td colspan=2>".__("Adblock Options", WPADGU_TRANS)."</td></tr>";
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Adblock Detection Status", WPADGU_TRANS)."</td>";
            $output[] = "<td><select name='adblock_status'>";
            $output[] = "<option value='enabled' {$adblock_status_enabled}>".__("Enabled", WPADGU_TRANS)."</option>";
            $output[] = "<option value='disabled' {$adblock_status_disabled}>".__("Disabled", WPADGU_TRANS)."</option>";
            $output[] = "</select>";
            $output[] = "</td></tr>";
            //Adblock Action
            $adblock_action = wpadgu_utility::get_option($this->prefix.'adblock_action');
            $adblock_message = wpadgu_utility::get_option($this->prefix.'adblock_message'); $adblock_message = esc_js(strip_tags( $adblock_message,"<strong><h3><u><i>"));
            $adblock_redirect_url = wpadgu_utility::get_option($this->prefix.'adblock_redirect_url'); $adblock_redirect_url = esc_html( esc_js( $adblock_redirect_url ) );
            $adblock_action_hidecontent = "";
            $adblock_action_message = "";
            $adblock_action_redirect = "";
            if( $adblock_action == 'hidecontent' )
            {
                $adblock_action_hidecontent = "checked";
            } else  if( $adblock_action == 'message' )
            {
                $adblock_action_message= "checked";
            } else  if( $adblock_action == 'redirect' )
            {
                $adblock_action_redirect= "checked";
            }
            
            if( empty($adblock_action) ){$adblock_action_hidecontent = "checked";}
            
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Adblock Detection Action", WPADGU_TRANS)."</td>";
            $output[] = "<td>";
           
            $output[] = "<span class='$default_css_class'><input type='radio' name='adblock_action' value='hidecontent' id='hidecontent' {$adblock_action_hidecontent}><label for='hidecontent'>".__("Hide Content", WPADGU_TRANS)."</lable><br />";
            $output[] = "&nbsp;- <span class='wpadgu_smallfont'>".__("Show some of the content and hide the rest of it in order to tell him to disable Adblock for this site.", WPADGU_TRANS)."</span> $pro_feature</span><hr>";
            
            $output[] = "<input type='radio' name='adblock_action' value='message' id='message' {$adblock_action_message}><label for='message'>".__("Show Message to Visitor", WPADGU_TRANS)."</lable><br />";
            $output[] = "&nbsp;- <span class='wpadgu_smallfont'>".__("This message will appear instead of the ad. You can write a custom message.", WPADGU_TRANS)."</span><br />";
            $output[] = "<i>".__("Custom Message", WPADGU_TRANS)."</i>: <br /><textarea name='adblock_message' style='width:350px;height:150px;'>{$adblock_message}</textarea>";
            $output[] = "<hr>";
            
            $output[] = "<input type='radio' name='adblock_action' value='redirect' id='reidrect' {$adblock_action_redirect}><label for='reidrect'>".__("Redirect Visitor to URL", WPADGU_TRANS)."</lable><br />";
            $output[] = "&nbsp;- <span class='wpadgu_smallfont'>".__("The visitor will be redirect to the URL which you added.", WPADGU_TRANS)."</span><br />";
            $output[] = "<i>".__("Custom URL", WPADGU_TRANS)."</i>: <br /><input type='text' name='adblock_redirect_url' value='{$adblock_redirect_url}' style='width:250px'><hr>";
            $output[] = "</td></tr>";
            
            $adblock_action_delay = (int)wpadgu_utility::get_option($this->prefix.'adblock_action_delay');
            $output[] = "<tr class='wpadgu_underTD'><td style='width:25%'>".__("Delay Before Taking Action", WPADGU_TRANS)."</td>";
            $output[] = "<td><input type='text' name='adblock_action_delay' value='{$adblock_action_delay}' style='width:50px;'> <span class='wpadgu_smallfont'>".__("In Seconds", WPADGU_TRANS)."</span>";
            $output[] = "<br /><span class='wpadgu_smallfont'>".__("0 (zero) for disable this option.", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            //submit
            $output[] = "<tr class='wpadgu_headTD'><td colspan=2><input type='submit' class='button' value='".__("Save", WPADGU_TRANS)."'></td></tr>";
            $output[] = "</tbody>";
            $output[] = "</table>";
            $output[] = "</form>";
            
            if( isset($_GET['p']) && $_GET['p'] == 'save' )
            {
                //Save Settings
                if( empty( $_POST['_nonce'])
                    || false === wp_verify_nonce($_POST['_nonce'], 'save-settings-'.$this->prefix ) )
                {
                    wp_die( __("You have no premissions!", WPADGU_TRANS)); exit;
                }

                //continue
                if( isset($_POST['status']) ){wpadgu_utility::update_option($this->prefix.'status', sanitize_text_field( $_POST['status']) );}
                if( isset($_POST['min_words']) ){wpadgu_utility::update_option($this->prefix.'min_words', (int)sanitize_text_field( $_POST['min_words']) );}
                if( isset($_POST['default_margin']) ){wpadgu_utility::update_option($this->prefix.'default_margin', (int)sanitize_text_field( $_POST['default_margin']) );}
                if( isset($_POST['desktop_status']) ){wpadgu_utility::update_option($this->prefix.'desktop_status', sanitize_text_field( $_POST['desktop_status']) );}
                if( isset($_POST['phones_status']) ){wpadgu_utility::update_option($this->prefix.'phones_status', sanitize_text_field( $_POST['phones_status']) );}
                //if( isset($_POST['adsense_external_links']) ){wpadgu_utility::update_option($this->prefix.'adsense_external_links', sanitize_text_field( $_POST['adsense_external_links']) );} else {wpadgu_utility::update_option($this->prefix.'adsense_external_links','');}
                if( isset($_POST['guard_status']) ){wpadgu_utility::update_option($this->prefix.'guard_status', sanitize_text_field( $_POST['guard_status']) );}
                if( isset($_POST['max_clicks']) ){wpadgu_utility::update_option($this->prefix.'max_clicks', (int)sanitize_text_field( $_POST['max_clicks']) );}
                if( isset($_POST['cookie_expiration']) ){wpadgu_utility::update_option($this->prefix.'cookie_expiration', (int)sanitize_text_field( $_POST['cookie_expiration']) );}
                if( isset($_POST['ban_duration']) ){wpadgu_utility::update_option($this->prefix.'ban_duration', (int)sanitize_text_field( $_POST['ban_duration']) );}
                if( isset($_POST['ban_strategy']) ){wpadgu_utility::update_option($this->prefix.'ban_strategy', sanitize_text_field( $_POST['ban_strategy']) );}
                if( isset($_POST['blacklist_countries']) ){wpadgu_utility::update_option($this->prefix.'blacklist_countries', sanitize_text_field( strtoupper($_POST['blacklist_countries'])) );}
                if( isset($_POST['adblock_status']) ){wpadgu_utility::update_option($this->prefix.'adblock_status', sanitize_text_field( $_POST['adblock_status']) );}
                if( isset($_POST['adblock_action']) ){wpadgu_utility::update_option($this->prefix.'adblock_action', sanitize_text_field( $_POST['adblock_action']) );}
                if( isset($_POST['adblock_message']) ){wpadgu_utility::update_option($this->prefix.'adblock_message', sanitize_text_field( $_POST['adblock_message']) );}
                if( isset($_POST['adblock_redirect_url']) ){wpadgu_utility::update_option($this->prefix.'adblock_redirect_url', sanitize_text_field( $_POST['adblock_redirect_url']) );}
                if( isset($_POST['adblock_action_delay']) ){wpadgu_utility::update_option($this->prefix.'adblock_action_delay', (int)sanitize_text_field( $_POST['adblock_action_delay']) );}
                
                
                echo __("Saving ...", WPADGU_TRANS);
                
                wpadgu_redirect_js($this->admin_url.'-settings&p=options_updated');
           }else {
               echo "<table border=0 width=100% cellspacing=0>";
               echo "<tr><td style='max-width:80%' valign=top>";
                echo implode("\n", $output);
                echo "</td>";
                echo "<td width=250 valign=top>";
                echo wpadgu_help::tuts();
                if( !wpadgu_is_pro() )
                {
                    echo wpadgu_help::products( wpadgu_create_donate_url( 'plugin_settings' ) );
                }
                echo "</td></tr>";
                echo '</table>';
           }
            
            $html->footer();
        }
        
        //ads management
        public function dashboard_ads()
        {
            //ini_set('display_errors', 'on');
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            $html = new wpadgu_html();
            $html->header(__("WP Ads Guard Dashboard", WPADGU_TRANS) );
            $html->tabs('ads');
            
            if( !$_POST )
            {
                if( !isset( $_GET['p'] ) || empty( $_GET['p'] ) )
                {
                    echo $this->add_button();
                    echo $this->ad_form();
                    $this->ads_list();
                } else
                if( isset( $_GET['p']) && $_GET['p'] == 'edit' && isset( $_GET['id'] ) && isset( $_GET['_nonce'] ) )
                {
                    $id = esc_html( esc_js( $_GET['id'] ) );
                    $id = (int)$id;
                    if( $id<=0 ){wp_die(__("No Ads!", WPADGU_TRANS));  exit;}
                    
                    if( false === wp_verify_nonce( $_GET['_nonce'], 'edit-ad-'.$id) ){wp_die(__("No Ads!", WPADGU_TRANS));  exit;}
                    
                    $ad_details = $this->get_ad_by_id($id);
                    $params = array();
                    $params["id"] = $id;
                    $params["ad_title"] = sanitize_text_field( $ad_details['ad_title'] );
                    $params["ad_code"] = wpadgu_decodeString($ad_details['ad_code'], $this->security_hash);
                    $params["ad_position"] = sanitize_text_field(trim( $ad_details['ad_position'] ));
                    $params["ad_alignment"] = sanitize_text_field(trim( $ad_details['ad_alignment'] ));
                    $params["ad_pages"] = "";
                    if( isset($ad_details['ad_pages']) && $ad_details['ad_pages']!='' )
                    {
                        $params["ad_pages"] = explode("|", $ad_details['ad_pages']);
                    }
                    $params["ad_margin"] = sanitize_text_field(trim( $ad_details['ad_margin'] ));
                    $params["ad_frame"] = sanitize_text_field(trim( $ad_details['ad_frame'] ));
                    $params["ad_frame_label_title"] = sanitize_text_field(trim( $ad_details['ad_frame_label_title'] ));
                    $params["ad_border_color"] = sanitize_text_field(trim( $ad_details['ad_border_color'] ));
                    $params["unique_code"] = sanitize_text_field(trim( $ad_details['unique_code'] ));
                    $params["ad_turn"] = sanitize_text_field(trim( $ad_details['ad_turn'] ));
                    $params["ad_status"] = sanitize_text_field(trim( $ad_details['ad_status'] ));
                    
                    echo $this->ad_form($params);
                } else
                    if( isset( $_GET['p']) && $_GET['p'] == 'delete' && isset( $_GET['id'] ) && isset( $_GET['_nonce'] ) )
                    {
                        $id = esc_html( esc_js( $_GET['id'] ) );
                        $id = (int)$id;
                        if( $id<=0 ){wp_die(__("No Ads!", WPADGU_TRANS));  exit;}
                        
                        if( false === wp_verify_nonce( $_GET['_nonce'], 'delete-ad-'.$id) ){wp_die(__("No Ads!", WPADGU_TRANS));  exit;}
                        
                        global $wpdb;
                        
                        if( $this->get_ad_by_id($id) !='' )
                        {
                        
                            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
                            $wpdb->delete($dbTable, array( "id"=>$id) );
                        
                            wpadgu_redirect_js($this->admin_url.'-ads&msg=addeleted'); exit;
                        } else {
                            wp_die(__("No Ads!", WPADGU_TRANS));  exit;
                        }
                    }
            } else {
                
                if( isset( $_GET['p']) && $_GET['p'] == 'save' )
                {
                    $params["ad_title"] = sanitize_text_field(trim( $_POST['ad_title'] ));
                    $params["ad_code"] = wpadgu_encodeString($_POST['ad_code'], $this->security_hash);
                    $params["ad_position"] = sanitize_text_field(trim( $_POST['position'] ));
                    $params["ad_alignment"] = sanitize_text_field(trim( $_POST['alignment'] ));
                    $params["ad_pages"] = "";
                    if( isset($_POST['pages']) && $_POST['pages']!='' )
                    {
                        $params["ad_pages"] = implode("|", $_POST['pages']);
                    }
                    $params["ad_margin"] = sanitize_text_field(trim( $_POST['margin'] ));
                    if( isset($_POST['frame'] ) )
                    {
                        $params["ad_frame"] = sanitize_text_field(trim( $_POST['frame'] ));
                    } else { $params["ad_frame"] = '';}
                    $params["ad_frame_label_title"] = sanitize_text_field(trim( $_POST['frame_label_title'] ));
                    $params["ad_border_color"] = sanitize_text_field(trim( $_POST['border_color'] ));
                    $params["ad_status"] = sanitize_text_field(trim( $_POST['ad_status'] ));
                    $params["unique_code"] = wpadgu_utility::create_hash(12);
                    if( isset( $_POST['unique_code']) && !empty( $_POST['unique_code'] ) )
                    {
                        $params['unique_code'] = sanitize_text_field( $_POST['unique_code'] );
                    }
                    $ad_id = '';
                    if( isset($_POST['ad_id']) ){$ad_id = sanitize_text_field( $_POST['ad_id']); }
                    
                    $this->save_ad( $params, $ad_id );
                    echo __("Saving, Please wait...", WPADGU_TRANS);
                    
                    if( $ad_id > 0 )
                    {
                        wpadgu_redirect_js($this->admin_url.'-ads&p=edit&id='.$ad_id.'&_nonce='.wp_create_nonce('edit-ad-'.$ad_id).'&msg=adupdated');
                    } else 
                    {
                        wpadgu_redirect_js($this->admin_url.'-ads');
                    }
                    
                }
                
            }
            
            
            $html->footer();
        }
        
        //get ad by its ID
        public function get_ad_by_id( $id, $what_selected=false)
        {
            if( !$id || $id <= 0 ){return;}
            
            $id = esc_html( esc_js( $id ) );
            
            global $wpdb;
            $wpdb->show_errors( true );
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            if( !$what_selected || !in_array($what_selected, $this->available_columns ) ){ $what_selected = "*"; }
            
            $query = "SELECT $what_selected FROM {$dbTable} WHERE id='$id' Limit 1";
            
            $row = $wpdb->get_row( $query );
            if( null!==$row )
            {
                $params["id"] = $row->id;
                $params["ad_title"] = $row->ad_title;
                $params["ad_code"] = $row->ad_code;
                $params["ad_position"] = $row->ad_position;
                $params["ad_alignment"] = $row->ad_alignment;
                $params["ad_pages"] = $row->ad_pages;
                $params["ad_margin"] = $row->ad_margin;
                $params["ad_frame"] = $row->ad_frame;
                $params["ad_frame_label_title"] = $row->ad_frame_label_title;
                $params["ad_border_color"] = $row->ad_border_color;
                $params["unique_code"] = $row->unique_code;
                $params["lastupdate"] = $row->lastupdate;
                $params["admin_id"] = $row->admin_id;
                $params["blog_id"] = $row->blog_id;
                $params["ad_turn"] = $row->ad_turn;
                $params["min_words"] = $row->min_words;
                $params["ad_status"] = $row->ad_status;
                
                return $params;
            }
            
            return;
        }
        
        //save or update ad
        public function save_ad( $params, $ad_id=false )
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            global $wpdb;
            
            $min_words= (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'min_words') ) );
            $ad_turn = $this->default_ad_turn;
            if( $params["ad_position"]=='inside_post' )
            {
                $ads_count = $this->ads_counter( 'WHERE ad_position=%s', 'inside_post' );
                if( $ads_count > 0 )
                {
                    $ad_turn = $ads_count+1;
                }
            }
            
            if( isset( $_POST['ad_turn'] ) ){$ad_turn = (int) sanitize_text_field( $_POST['ad_turn'] );}
          //  echo $ads_count; exit;
            //$wpdb->show_errors( true); 
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            //get the admin ID
            $user = wp_get_current_user();
            //get the current blog ID
            $blog_id = 0;
            if( function_exists( 'is_multisite' ) && is_multisite() ) {$blog_id = get_current_blog_id();}
            
            if( isset( $ad_id ) && $ad_id > 0 )
            {
                $wpdb->update( $dbTable, 
                    array(
                        "ad_title" => $params["ad_title"],
                        "ad_code" => $params["ad_code"],
                        "ad_position" => $params["ad_position"],
                        "ad_alignment" => $params["ad_alignment"],
                        "ad_pages" => $params["ad_pages"],
                        "ad_margin" => $params["ad_margin"],
                        "ad_frame" => $params["ad_frame"],
                        "ad_frame_label_title" => $params["ad_frame_label_title"],
                        "ad_border_color" => $params["ad_border_color"],
                        "lastupdate" => time(),
                        "admin_id" => $user->ID,
                        "blog_id" => $blog_id,
                        "min_words" => $min_words,
                        "ad_turn" => $ad_turn,
                        "ad_status" => $params["ad_status"],
                        
                    ),
                    array( "id" => $ad_id ) );  
            } else {
                $wpdb->insert( $dbTable,
                    array(
                        "ad_title" => $params["ad_title"],
                        "ad_code" => $params["ad_code"],
                        "ad_position" => $params["ad_position"],
                        "ad_alignment" => $params["ad_alignment"],
                        "ad_pages" => $params["ad_pages"],
                        "ad_margin" => $params["ad_margin"],
                        "ad_frame" => $params["ad_frame"],
                        "ad_frame_label_title" => $params["ad_frame_label_title"],
                        "ad_border_color" => $params["ad_border_color"],
                        "unique_code" => $params["unique_code"],
                        "lastupdate" => time(),
                        "admin_id" => $user->ID,
                        "blog_id" => $blog_id,
                        "min_words" => $min_words,
                        "ad_turn" => $ad_turn,
                        "ad_status" => $params["ad_status"],
                    ));
                
                $this->enable_ad_for_posts( $wpdb->insert_id );
            }
        }
        
        //customize ads for every post/page
        public function enable_ad_for_posts( $ad_id )
        {
            $posts = get_posts();
            
            if( is_array( $posts ) )
            {
                foreach( $posts as $post )
                {
                    $meta_option = get_post_meta($post->ID, WPADGU_PREFIX.'ads', true );
                    if( !empty( $meta_option ) )
                    {
                        if( is_array( $meta_option ) )
                        {
                            $new_ad[] = $ad_id;
                            $new_value = array_merge($meta_option,$new_ad);  
                            
                            update_post_meta($post->ID, WPADGU_PREFIX.'ads', $new_value);
                        }
                    }
                }
            }
        }
        
        //manage list
        public function add_button()
        {
            global $wpdb;
            
            $html = new wpadgu_html();
            $ads = array();
          
            $output[] = "<form action='".$this->admin_url."-ads&p=choose' method=post>";
            $output[] = "<table border=0 width=100% cellspacing=0>";
            $output[] = "<tr class='wpadgu_headTD wpadgu_grad'><td>";
            $output[] = "<input type='button' class='button' id='wpadgu_add_ad_btn' value='".__("+ New Ad", WPADGU_TRANS)."'>";
            $output[] = "</td></tr>";
            $output[] = "</table>";
            $output[] = "</form>";
            
            $js = "<script>";
            $js .= "jQuery('#wpadgu_add_ad_btn').click(function(){";
            $js .= "jQuery('#wpadgu_ads_editor').show();";
            $js .= "});";
            $js .= "</script>";
            
            $output[] = $js;
            
            return implode("\n", $output);
        }
        
        //ads list
        public function ads_list()
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $wpdb->show_errors(true);
            $query = "SELECT id,ad_title,lastupdate,admin_id,ad_position,unique_code FROM {$dbTable} WHERE ad_position!='' ORDER BY id DESC";
            $query_rows = "SELECT COUNT(*) FROM {$dbTable} WHERE ad_position!='' ORDER BY id DESC";
            if( function_exists( 'is_multisite' ) && is_multisite() )
            {
                $blog_id = get_current_blog_id();
                $query = "SELECT id,ad_title,lastupdate,admin_id,ad_position FROM {$dbTable} WHERE blog_id='$blog_id' and ad_position!='' ORDER BY id DESC";
                $query_rows = "SELECT COUNT(*) FROM {$dbTable} WHERE blog_id='$blog_id' and ad_position!='' ORDER BY id DESC";
            }
            
            $results = $wpdb->get_results( $query );
            if( null!==$results )
            {
                $list = '';
                foreach ($results as $row)
                {
                    $lastupdate = "N/A";
                    if( $row->lastupdate!='' )
                    {
                        $lastupdate = wpadgu_ago( $row->lastupdate );
                    }
                    
                    $admin = "N/A";
                    if( $row->admin_id > 0 )
                    {
                        $user = get_user_by('id', $row->admin_id);
                        $admin = $user->display_name;
                    }
                    //nonce
                    $edit_nonce = wp_create_nonce('edit-ad-'.$row->id);
                    $delete_nonce = wp_create_nonce('delete-ad-'.$row->id);
                    
                    //confirm message
                    $confirmMsg = __("Are you want to delete this ad?!", WPADGU_TRANS);
                    
                    //handle position
                    $ad_position = esc_js( $row->ad_position );
                    $position = str_replace("_", " ", $ad_position);
                    $position = ucwords( $position );
                    $unique_code= esc_html( esc_js( $row->unique_code ) );
                    
                    $tools = "<div class='row-actions'>";
                    $tools .= "<span class='edit'><a href='".$this->admin_url."-ads&p=edit&id=".(int)$row->id."&_nonce={$edit_nonce}' aria-label='Edit'>".__("Edit", WPADGU_TRANS)."</a></span> | ";
                    $tools .= "<span class='trash'><a onclick=\"return confirm('$confirmMsg')\" href='".$this->admin_url."-ads&p=delete&id=".(int)$row->id."&_nonce={$delete_nonce}' aria-label='Delete'>".__("Delete", WPADGU_TRANS)."</a></span>";
                    $tools .= "</div>";
                    
                    $list .= "<tr class='wpadgu_underTD'><td width=30% height=45><strong>".esc_js( $row->ad_title )."</strong>";
                    $list .= "<br /><span class='wpadgu_smallfont'>(".__("Last Update", WPADGU_TRANS).") <i>{$lastupdate}</i> (".__("By", WPADGU_TRANS).") <i>{$admin}</i></td>";
                    $list .= "<td width=10%>{$tools}</td>";
                    $list .= "<td>{$position}</td>";
                    $list .= "<td><div class='wpadgu_shortcode'><pre>[wpadgu_ads unique_code=\"$unique_code\"]</pre></div></td>";
                    $list .= "</tr>";
                    
                }
                
                $output[] = "<table border=0 cellspacing=0 width=100%>";
                $output[] = $list;
                $output[] = "<tr class='wpadgu_headTD wpadgu_grad'>";
                $output[] = "<td colspan=3>".__("All Ads", WPADGU_TRANS)." [".$wpdb->get_var( $query_rows )."]</td>";
                $output[] = "<td>".__("Shortcode", WPADGU_TRANS)."</td>";
                $output[] = "</tr>";
                $output[] = "</table>";
                
                echo "<table border=0 width=100% cellspacing=0>";
                echo "<tr><td style='max-width:80%' valign=top>";
                echo implode("\n", $output);
                echo "</td>";
                echo "<td width=250 valign=top>";
                echo wpadgu_help::tuts();
                if( !wpadgu_is_pro() )
                {
                    echo wpadgu_help::products( wpadgu_create_donate_url( 'plugin_adslist' ) );
                }
                echo "</td></tr>";
                echo '</table>';
            } else {
                echo "<p align=center><h3 style='color:#5D5D5D'>".__("No Ads Found!", WPADGU_TRANS)."</h3><br />";
                echo "<font style='color:#5D5D5D'>".__("Try to add new one.", WPADGU_TRANS)."</font></p>";
            }
        }
        
        /**
         * Place ad code before and after the title
         * @param string $title
         * @return string
         */
        public function place_ad_title( $title )
        {
            global $wpdb;
            
            //echo $this->min_words."<hr>".$words_count."<hr>";
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $adpositions[] = "before_title";
            $adpositions[] = "after_title";
            
            $adpositions_array = implode("','", $adpositions);
            $query = "SELECT
            id,ad_code,ad_pages,ad_position,ad_margin,ad_frame,
            ad_frame_label_title,ad_border_color,ad_alignment,ad_pages,
            unique_code,ad_turn,min_words,ad_status
            FROM {$dbTable} WHERE ad_position IN ('".$adpositions_array."')";
            
            $results = $wpdb->get_results( $query );
            
            $ad = '';
            if( null!==$results )
            {
                foreach ( $results as $row )
                {
                    $ad_code = wpadgu_decodeString($row->ad_code, $this->security_hash);
                    $ad_code = stripslashes( $ad_code );
                    
                    $ad_settings['id'] = esc_html( esc_js( $row->id ) );
                    $ad_settings['unique_code'] = esc_html( esc_js( $row->unique_code ) );
                    $ad_settings['ad_margin'] = esc_html( esc_js( $row->ad_margin ) );
                    $ad_settings['ad_frame'] = esc_html( esc_js( $row->ad_frame ) );
                    $ad_settings['ad_frame_label_title'] = esc_html( esc_js( $row->ad_frame_label_title ) );
                    $ad_settings['ad_border_color'] = esc_html( esc_js( $row->ad_border_color ) );
                    $ad_settings['ad_alignment'] = esc_html( esc_js( $row->ad_alignment ) );
                    $ad_settings['ad_turn'] = esc_html( esc_js( $row->ad_turn ) );
                    $ad_settings['ad_pages'] = esc_html( esc_js( $row->ad_pages ) );
                    $ad_settings['ad_status'] = esc_html( esc_js( $row->ad_status ) );
                    $ad_settings['ad_position'] = esc_html( esc_js( $row->ad_position) );
                    
                    $ad = $this->design_ad( $ad_code, $ad_settings );
                    
                    //place the right ad in the right place
                    if( true!=$this->check_ad_page( $ad_settings ) ) {return $title;}
                    if( $ad_settings['ad_status']=="disabled"){ return $title;}
                    
                    if( $row->ad_position == 'before_title' ){$content = $ad.$title;}
                    else if( $row->ad_position == 'after_title' ){$content = $title.$ad;}
                }
            }
            
            return $content;
        }
        
        /**
         * place ad code before, after and inside content
         * @param string $content
         * @return string
         */
        public function place_ad_inside_content( $content )
        {
            global $wpdb;
            
            $words_count = wpadgu_words_count( sanitize_text_field( $content ) );
            //$min_words = (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'min_words') ) );
            
            if( $this->min_words > $words_count ){ return $content; }
            
            //echo $this->min_words."<hr>".$words_count."<hr>";
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $adpositions[] = "inside_post";
            $adpositions[] = "before_post";
            $adpositions[] = "after_post";
            
            $adpositions_array = implode("','", $adpositions);
            
            $query = "SELECT 
                        id,ad_code,ad_pages,ad_position,ad_margin,ad_frame,
                        ad_frame_label_title,ad_border_color,ad_alignment,ad_pages,
                        unique_code,ad_turn,min_words,ad_status
                        FROM {$dbTable} WHERE ad_position='inside_post' and ad_status!='disabled' and ad_status!='shortcode'";
            
            $results = $wpdb->get_results( $query );
            
            $ad = '';
            if( null!==$results )
            {
                foreach ( $results as $row )
                {
                    $ad_code = wpadgu_decodeString($row->ad_code, $this->security_hash);
                    $ad_code = stripslashes( $ad_code );
                    
                    $ad_settings['id'] = esc_html( esc_js( $row->id ) );
                    $ad_settings['unique_code'] = esc_html( esc_js( $row->unique_code ) );
                    $ad_settings['ad_margin'] = esc_html( esc_js( $row->ad_margin ) );
                    $ad_settings['ad_frame'] = esc_html( esc_js( $row->ad_frame ) );
                    $ad_settings['ad_frame_label_title'] = esc_html( esc_js( $row->ad_frame_label_title ) );
                    $ad_settings['ad_border_color'] = esc_html( esc_js( $row->ad_border_color ) );
                    $ad_settings['ad_alignment'] = esc_html( esc_js( $row->ad_alignment ) );
                    $ad_settings['ad_turn'] = esc_html( esc_js( $row->ad_turn ) );
                    $ad_settings['ad_pages'] = esc_html( esc_js( $row->ad_pages ) );
                    $ad_settings['ad_status'] = esc_html( esc_js( $row->ad_status ) );
                    $ad_settings['ad_position'] = esc_html( esc_js( $row->ad_position) );
                    
                    $ad = $this->design_ad( $ad_code, $ad_settings );
                    
                    //place the right ad in the right place
                    if( $ad_settings['ad_status']=="disabled"){ return $content;}
                    
                    //if( $row->ad_position == 'before_post' ){$content = $ad.$content;}
                    if( $this->check_ad_page( $ad_settings ) ) {
                        if( $row->ad_position == 'inside_post' ){$content = $this->place_inside_post( $content, $ad,$row->ad_turn);}
                    }
                    //else if( $row->ad_position == 'after_post' ){$content = $content.$ad;}
                }
            }
            
            //echo sizeof( explode(' ', strip_tags(str_replace(',', '', $content))));
            return $content;
        }
        
        //Before content ads
        public function place_ad_before_content( $content )
        {
            global $wpdb;
            
            $words_count = wpadgu_words_count( sanitize_text_field( $content ) );
            //$min_words = (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'min_words') ) );
            
            if( $this->min_words > $words_count ){ return $content; }
            
            //echo $this->min_words."<hr>".$words_count."<hr>";
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $adpositions[] = "inside_post";
            $adpositions[] = "before_post";
            $adpositions[] = "after_post";
            
            $adpositions_array = implode("','", $adpositions);
            $query = "SELECT
            id,ad_code,ad_pages,ad_position,ad_margin,ad_frame,
            ad_frame_label_title,ad_border_color,ad_alignment,ad_pages,
            unique_code,ad_turn,min_words,ad_status
            FROM {$dbTable} WHERE ad_position='before_post' and ad_status!='disabled'";
            
            $results = $wpdb->get_results( $query );
            
            $ad = '';
            if( null!==$results )
            {
                foreach ( $results as $row )
                {
                    $ad_code = wpadgu_decodeString($row->ad_code, $this->security_hash);
                    $ad_code = stripslashes( $ad_code );
                    
                    $ad_settings['id'] = esc_html( esc_js( $row->id ) );
                    $ad_settings['unique_code'] = esc_html( esc_js( $row->unique_code ) );
                    $ad_settings['ad_margin'] = esc_html( esc_js( $row->ad_margin ) );
                    $ad_settings['ad_frame'] = esc_html( esc_js( $row->ad_frame ) );
                    $ad_settings['ad_frame_label_title'] = esc_html( esc_js( $row->ad_frame_label_title ) );
                    $ad_settings['ad_border_color'] = esc_html( esc_js( $row->ad_border_color ) );
                    $ad_settings['ad_alignment'] = esc_html( esc_js( $row->ad_alignment ) );
                    $ad_settings['ad_turn'] = esc_html( esc_js( $row->ad_turn ) );
                    $ad_settings['ad_pages'] = esc_html( esc_js( $row->ad_pages ) );
                    $ad_settings['ad_status'] = esc_html( esc_js( $row->ad_status ) );
                    $ad_settings['ad_position'] = esc_html( esc_js( $row->ad_position) );
                    
                    $ad = $this->design_ad( $ad_code, $ad_settings );
                    
                    //place the right ad in the right place
                    if( true!=$this->check_ad_page( $ad_settings ) ) {return $content;}
                    if( $ad_settings['ad_status']=="disabled"){ return $content;}
                    
                    if( $row->ad_position == 'before_post' ){$content = $ad.$content;}
                    
                    //if( $row->ad_position == 'inside_post' ){$content = $this->place_inside_post( $content, $ad,$row->ad_turn);}
                    //else if( $row->ad_position == 'after_post' ){$content = $content.$ad;}
                }
            }
            
            return $content;
        }
        
        //After content ads
        public function place_ad_after_content( $content )
        {
            global $wpdb;
            
            $words_count = wpadgu_words_count( sanitize_text_field( $content ) );
            //$min_words = (int)esc_html( esc_js( wpadgu_utility::get_option($this->prefix.'min_words') ) );
            
            if( $this->min_words > $words_count ){ return $content; }
            
            //echo $this->min_words."<hr>".$words_count."<hr>";
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $adpositions[] = "inside_post";
            $adpositions[] = "before_post";
            $adpositions[] = "after_post";
            
            $adpositions_array = implode("','", $adpositions);
            $query = "SELECT
            id,ad_code,ad_pages,ad_position,ad_margin,ad_frame,
            ad_frame_label_title,ad_border_color,ad_alignment,ad_pages,
            unique_code,ad_turn,min_words,ad_status
            FROM {$dbTable} WHERE ad_position='after_post' and ad_status!='disabled'";
            
            $results = $wpdb->get_results( $query );
            
            $ad = '';
            if( null!==$results )
            {
                foreach ( $results as $row )
                {
                    $ad_code = wpadgu_decodeString($row->ad_code, $this->security_hash);
                    $ad_code = stripslashes( $ad_code );
                    
                    $ad_settings['id'] = esc_html( esc_js( $row->id ) );
                    $ad_settings['unique_code'] = esc_html( esc_js( $row->unique_code ) );
                    $ad_settings['ad_margin'] = esc_html( esc_js( $row->ad_margin ) );
                    $ad_settings['ad_frame'] = esc_html( esc_js( $row->ad_frame ) );
                    $ad_settings['ad_frame_label_title'] = esc_html( esc_js( $row->ad_frame_label_title ) );
                    $ad_settings['ad_border_color'] = esc_html( esc_js( $row->ad_border_color ) );
                    $ad_settings['ad_alignment'] = esc_html( esc_js( $row->ad_alignment ) );
                    $ad_settings['ad_turn'] = esc_html( esc_js( $row->ad_turn ) );
                    $ad_settings['ad_pages'] = esc_html( esc_js( $row->ad_pages ) );
                    $ad_settings['ad_status'] = esc_html( esc_js( $row->ad_status ) );
                    $ad_settings['ad_position'] = esc_html( esc_js( $row->ad_position) );
                    
                    $ad = $this->design_ad( $ad_code, $ad_settings );
                    
                    //place the right ad in the right place
                    if( true!=$this->check_ad_page( $ad_settings ) ) {return $content;}
                    if( $ad_settings['ad_status']=="disabled"){ return $content;}
                    
                    //if( $row->ad_position == 'before_post' ){$content = $ad.$content;}
                    
                    //if( $row->ad_position == 'inside_post' ){$content = $this->place_inside_post( $content, $ad,$row->ad_turn);}
                    if( $row->ad_position == 'after_post' ){$content = $content.$ad;}
                }
            }
            
            return $content;
        }
        
        /**
         * Place ad code inside content itself
         * @param string $content
         * @param string $ad
         * @param integer $ad_turn
         * @return string
         */
        public function place_inside_post( $content,$ad,$ad_turn )
        {
            
            if( $ad_turn < 1 ){return $content;}
            $ads_counter = $this->ads_counter( 'where ad_position=%s and ad_status!="disabled" and ad_status!="shortcode"', 'inside_post' );
            
            /*$parse = $this->find_parase( $content );
            
            $half_parase = sizeof( $parse );
                     
            if( $half_parase > 0 ):
                if( $ad_turn < 1 ){return $content;}
                
                while( sizeof( $parse ) > $half_parase )
                {
                    array_pop( $parse );
                }
                
                
                $splitter = 2;
                if( $ads_counter > 1 ){$splitter = $ads_counter;}
                $splitter = $splitter+1;
                
                if( $splitter >= $half_parase ){ $splitter = $ads_counter; }
                
                $split = 0;
                
                if (!empty($parse)) {
                    //echo "<hr>".($half_parase/($splitter*$ad_turn))."</hr>";
                    if( !isset($parse[$half_parase/($splitter*$ad_turn)]) ){return $content;}
                    $split = $parse[ $half_parase/($splitter*$ad_turn)];
                }
                
                $content = substr($content, 0, $split) . $ad . substr($content, $split);
               // echo "<hr>".$half_parase."</hr>";
                
            endif;
            */
            
            $closing_p = '</p>';
            $paragraphs = explode( $closing_p, $content );
            
            $paragraph_id = $ad_turn;
            /*if( sizeof($paragraphs)>= $ads_counter )
            {
                $paragraph_id = ceil( sizeof($paragraphs)/$ads_counter )*$ad_turn;   
            }
            */
            $i=0;
            foreach ($paragraphs as $index => $paragraph) {
                $i++;
                // Only add closing tag to non-empty paragraphs
                if ( trim( $paragraph ) ) {
                    // Adding closing markup now, rather than at implode, means insertion
                    // is outside of the paragraph markup, and not just inside of it.
                    $paragraphs[$index] .= $closing_p;
                }
                // + 1 allows for considering the first paragraph as #1, not #0.
                if ( $paragraph_id == $index + 1  ) {
                    $paragraphs[$index] .= $ad;
                }
            }
            return implode( '', $paragraphs );
            
            return $content;
            
        }
        
        /**
         * Split content in order to add the ad code inside it
         * @param string $content
         * @return mixed
         */
        public function find_parase( $content )
        {
            //$my_content = $this->skip_tags($this->start_tag_desc, $this->end_tag_desc, $content);
            $split_char = "<p";
            if( strpos( $content, $split_char ) === false ){$split_char = "<br";}
            if( strpos( $content, $split_char ) === false ){return $content;}
            
            
            $content= strtolower($content);
            $pos = -1;
            $total = array();
            while( strpos( $content, $split_char, $pos+1 ) !==false )
            {
                $pos = strpos( $content, $split_char, $pos+1 );
                $total[] = $pos;
            }
            
            return $total;
        
        }
        
        /**
         * add filter to the content
         */
        public function filter_ads()
        {
            add_filter('the_content', array( $this,'place_ad_before_content' ));
            add_filter('the_content', array( $this,'place_ad_inside_content' ));
            add_filter('the_content', array( $this,'place_ad_after_content' ));
            
            if( is_single() || is_page() )
            {
                add_filter('the_title', array( $this,'place_ad_title' ),10);
            }
        }
        
        
        /**
         * Check if the ad should show in the current screen or not
         * @param array $params
         * @return void|boolean
         */
        public function check_ad_page( $params )
        {
            if( !$params || !is_array( $params ) ){return;}
            
            $pages = explode("|", $params['ad_pages'] );
            
            if( is_home() && !in_array( 'homepage', $pages ) ){ return false; }
            if( is_singular( 'post' ) && !in_array( 'posts', $pages ) ){ return false; }
            if( is_page() && !in_array( 'pages', $pages ) ){ return false; }
            if( is_search() && !in_array( 'search', $pages ) ){ return false; }
            if( (is_category()) && !in_array( 'categories', $pages ) ){ return false; }
            if( is_tag() && !in_array( 'tags', $pages ) ){ return false; }
            if( is_404() && !in_array( 'error404', $pages ) ){ return false; }
            
            return true;
            
        }
        
        /**
         * create the wordpress shortcodes
         * @param  $atts
         * @return void|void|unknown
         */
        public function shortcodes( $atts )
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $query = "SELECT ad_code,unique_code,id,ad_margin,ad_frame,ad_frame_label_title,ad_border_color,ad_alignment,ad_turn,ad_status,ad_position,ad_pages
                 FROM {$dbTable} WHERE unique_code='".sanitize_text_field($atts['unique_code'])."'";
            
            $results = $wpdb->get_results( $query );
            
            $ad_settings= array();
            if( null!==$results )
            {
                
                foreach ( $results as $row )
                {
                    $ad_settings['ad_code'] = $row->ad_code;
                    $ad_settings['unique_code'] = $row->unique_code;
                    $ad_settings['id'] = $row->id;
                    $ad_settings['ad_margin'] = $row->ad_margin;
                    $ad_settings['ad_frame'] = $row->ad_frame;
                    $ad_settings['ad_frame_label_title'] = $row->ad_frame_label_title;
                    $ad_settings['ad_border_color'] = $row->ad_border_color;
                    $ad_settings['ad_alignment'] = $row->ad_alignment;
                    $ad_settings['ad_turn'] = $row->ad_turn;
                    $ad_settings['ad_status'] = $row->ad_status;
                    $ad_settings['ad_position'] = $row->ad_position;
                    $ad_settings['ad_pages'] = $row->ad_pages;
                }
            }
            
            if( is_array( $ad_settings )  && isset( $ad_settings['ad_code'] ))
            {
                
                   // $ad_array = shortcode_atts( $ad, $atts );
                $ad_code = wpadgu_decodeString($ad_settings['ad_code'], $this->security_hash);
                $ad_code = stripslashes( $ad_code );
                $thead = $this->design_ad($ad_code, $ad_settings);
                
                //place the right ad in the right place
                if( $this->check_ad_page( $ad_settings ) && $ad_settings['ad_status']!="disabled" ) {
                
                    return $thead;
                }
               
            }
        }
        
        /**
         * count all ads
         * @param string $where
         * @param string $args
         * @return number
         */
        public function ads_counter( $where=false, $args=false )
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $query = $wpdb->prepare("SELECT count(*) FROM {$dbTable} $where", $args);
            
            return (int)$wpdb->get_var( $query );
        }
        
        /**
         * Design the ad to show based on its settings
         * @param string $ad_code
         * @param array $ad_settings
         * @return void|unknown
         */
        public function design_ad( $ad_code, $ad_settings )
        {
            //if( isset($_GET['wpadgu_h']) && $_GET['wpadgu_h'] == 'true'){return;}
            
            if( $this->is_ad_enabled_for_single_post( $ad_settings['id'] ) )
            {
                $output = '';
                $padding = 0;
                $align = 'auto';
                if( empty($ad_code) ){ return; }
                if( !is_array($ad_settings) ){ return; }
                
                if( $ad_settings['ad_margin'] != '' ){$padding = $ad_settings['ad_margin'];}
                $css_padding = "margin:".$padding."px;";
                
                if( $ad_settings['ad_alignment'] != '' ){$align = $ad_settings['ad_alignment'];}
                //clear:left;
                if( $align == 'center' ){ $css_margin = "text-align:center;"; }
                else if( $align == 'left' ){ $css_margin = "text-align:left;"; }
                else if( $align == 'left_wrap' ){ $css_margin = "float:left"; }
                else if( $align == 'right' ){ $css_margin = "text-align:right;"; }
                else if( $align == 'right_wrap' ){ $css_margin = "float:right;"; }
                else { $css_margin = "text-align:center;"; }
                
                
                $display = '';
                /*if( $ad_settings['is_responsive']!="checked" )
                {
                    $display = "display:inline-block;";   
                }
                */
                $output = $this->start_tag_desc;
                $output .= "<div id='main_container_wpadgu".$ad_settings['unique_code']."' "; 
                $output .= " style='width:auto;{$display}{$css_padding}{$css_margin}'>"; 
                $output .= "<div class='wpadgu_ad' id='main_container_wpadgu".$ad_settings['unique_code']."'"; 
                
                $output .= ">";
                
                if( $ad_settings['ad_frame'] == 'checked' )
                {
                    $ad_frame_title = __("Ads", WPADGU_TRANS);
                    if( $ad_settings['ad_frame_label_title']!='' ){$ad_frame_title = esc_html( esc_js( $ad_settings['ad_frame_label_title'] ) );}
                    
                    $output .= "<span style='padding:2px; font-size:8pt; color:gray;'>".$ad_frame_title." </span><br />";
                }
                
                $output .= "<div id='wpadgu_ad".$ad_settings['unique_code']."' class='wpadgu_ad_container' ";
                
                $css_border = '';
                if( $ad_settings['ad_frame'] == 'checked' )
                {
                    $border_color = '';
                    if( $ad_settings['ad_border_color']!='' ){$border_color = $ad_settings['ad_border_color'];}
                    
                    if( $border_color!='' )
                    {
                        $css_border = "border:solid 1px {$border_color};";
                    }
                }
                $output .= " style='{$css_border}'";
                $output .= ">";
                
                $output .= $ad_code;
                
                
                
                $output .= "</div>";
                $output .= "</div>";
                
                if( current_user_can( 'administrator' ) )
                {
                    $output .= "<div style='padding:2px;'>";
                    $output .= "<a class='wpadgu_button_small' style='padding:2px; max-heigth:25px; min-width:70px;' href='".$this->admin_url."-ads&p=edit&id=".$ad_settings['id']."&_nonce=".wp_create_nonce('edit-ad-'.$ad_settings['id'])."'>";
                    $output .= "<img src='".plugins_url('/assets/img/edit-16.png', $this->path)."'> Edit Ad </a></div>";
                }
                
                $output .= "</div>";
                
                $output .= $this->end_tag_desc;
                
                $guard = new wpadgu_guard();
               
                
                if( !$guard->is_bad() )
                {
                    if( $ad_settings['ad_position'] == 'footer' && $align == 'center' )
                    {
                        return "<center>".$output."</center>";
                    }
                
                    return $output;
                }
            }
            
        }
        
        /**
         * Ads Counter by position
         * @param  $position
         * @return count (int)
         */
        public function ads_count_by_position( $position )
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$dbTable} WHERE ad_position=%s", $position));
            
            return $count;
        }
        
        public function is_ad_enabled_for_single_post( $id )
        {
           // check device
            if( !$this->show_based_on_device() ){ return false; }
            
            if( is_singular( 'post' ) || is_page() )
            {
                $post_id = (int)get_the_ID();
                if( !$post_id || $post_id == 0 ){return;}
                
                $post_meta = get_post_meta($post_id, WPADGU_PREFIX.'ads', true);
                
                if( $post_meta == 'none' ){ return false; }
                
                if( is_array( $post_meta) && !in_array( $id, $post_meta) )
                {
                    return false;
                }
            }
            
            return true;
        }
        
        public function show_based_on_device()
        {
            $user_device = wpadgu_agent::device();
            $desktop_options = wpadgu_utility::get_option(WPADGU_PREFIX.'desktop_status');
            $smartphones_options = wpadgu_utility::get_option(WPADGU_PREFIX.'phones_status');
            
            if( $user_device == 'Computer' && $desktop_options!='enabled' ){return false;}
            else if( $user_device != 'Computer' && $smartphones_options!='enabled' ){return false;}
            
            return true;
        }
        
        /**
         * Place ad code in footer
         */
        public function place_ad_footer()
        {
            global $wpdb;
            
            
            //echo $this->min_words."<hr>".$words_count."<hr>";
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'ads';
            
            $query = "SELECT
            id,ad_code,ad_pages,ad_position,ad_margin,ad_frame,
            ad_frame_label_title,ad_border_color,ad_alignment,ad_pages,
            unique_code,ad_turn,min_words,ad_status
            FROM {$dbTable} WHERE ad_position='footer'";
            
            $results = $wpdb->get_results( $query );
            
            $ad = '';
            if( null!==$results )
            {
                foreach ( $results as $row )
                {
                    $ad_code = wpadgu_decodeString($row->ad_code, $this->security_hash);
                    $ad_code = stripslashes( $ad_code );
                    
                    $ad_settings['id'] = esc_html( esc_js( $row->id ) );
                    $ad_settings['unique_code'] = esc_html( esc_js( $row->unique_code ) );
                    $ad_settings['ad_margin'] = esc_html( esc_js( $row->ad_margin ) );
                    $ad_settings['ad_frame'] = esc_html( esc_js( $row->ad_frame ) );
                    $ad_settings['ad_frame_label_title'] = esc_html( esc_js( $row->ad_frame_label_title ) );
                    $ad_settings['ad_border_color'] = esc_html( esc_js( $row->ad_border_color ) );
                    $ad_settings['ad_alignment'] = esc_html( esc_js( $row->ad_alignment ) );
                    $ad_settings['ad_turn'] = esc_html( esc_js( $row->ad_turn ) );
                    $ad_settings['ad_pages'] = esc_html( esc_js( $row->ad_pages ) );
                    $ad_settings['ad_status'] = esc_html( esc_js( $row->ad_status ) );
                    $ad_settings['ad_position'] = esc_html( esc_js( $row->ad_position) );
                   
                    //place the right ad in the right place
                    if( true==$this->check_ad_page( $ad_settings ) 
                        && $ad_settings['ad_status']!="disabled")
                    {
                        echo $this->design_ad( $ad_code, $ad_settings );
                    }
                    
                }
            }
            
        }
        //ads form
        public function ad_form( $params=false )
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            $ad_status = array(
                "enabled" => __("Enabled", WPADGU_TRANS),
                "disabled" => __("Disabled", WPADGU_TRANS),
                "shortcode" => __("Shortcode Only", WPADGU_TRANS),
            );
            
            $status_list = '';
            foreach ($ad_status as $key => $value)
            {
                $status_list .= "<input type='radio' name='ad_status' value='".$key."' id='wpadgu_status_".$key."' ";
                if( $params && $params['ad_status'] == $key ){ $status_list.= "checked";}
                else {
                    if( $key=='enabled' ){ $status_list .= "checked";}
                }
                $status_list .= "><label for='wpadgu_status_".$key."'>".$value."</label><br />";
                /*$status_list.= "<option value='".$key."' ";
                if( $params && $params['ad_status'] == $key ){ $status_list.= "selected";}
                $status_list.= ">".$value."</option>";
                */
            }
            
            $positions = array( 
                "before_post" => __("Before Content", WPADGU_TRANS),
                "inside_post" => __("Inside Content", WPADGU_TRANS),
                "after_post" => __("After Content", WPADGU_TRANS),
                "before_title" => __("Before Title", WPADGU_TRANS),
                "after_title" => __("After Title", WPADGU_TRANS),
                "footer" => __("Footer", WPADGU_TRANS),
                "widget" => __("Widget", WPADGU_TRANS),
            );
           
            $positions_list = '';
            foreach ($positions as $key => $value)
            {
                
                $positions_list .= "<option value='".$key."' ";
                if( $params && $params['ad_position'] == $key ){ $positions_list .= "selected";}
                $positions_list .= ">".$value."</option>";
            }
            
            $aligns = array(
                "center" => __("Center", WPADGU_TRANS),
                "left" => __("Just Left", WPADGU_TRANS),
                "left_wrap" => __("Left and Wrap", WPADGU_TRANS),
                "right" => __("Just Right", WPADGU_TRANS),
                "right_wrap" => __("Right and Wrap", WPADGU_TRANS),
            );
            
            $aligns_list = '';
            foreach ($aligns as $key => $value)
            {
                $aligns_list .= "<option value='".$key."' ";
                if( $params && $params['ad_alignment'] == $key ){ $aligns_list .= "selected";}
                $aligns_list .= ">".$value."</option>";
            }
            
            if( !$params || ( isset($params) && !is_array($params['ad_pages']) ) )
            {
                $homepage_selected = '';
                $posts_selected = "checked";
                $pages_selected= "checked";
                $search_selected= "checked";
                $categories_selected= "checked";
                $tags_selected= "";
                $error404_selected= "";
            }
            
            if( $params && is_array($params['ad_pages']) )
            {
               //default is empty
                $homepage_selected = '';
                $posts_selected = "";
                $pages_selected= "";
                $search_selected= "";
                $categories_selected= "";
                $tags_selected= "";
                $error404_selected= "";
                //absolute
                if( in_array( 'homepage', $params['ad_pages']) ){$homepage_selected = "checked";}
                if( in_array( 'posts', $params['ad_pages']) ){$posts_selected = "checked";}
                if( in_array( 'pages', $params['ad_pages']) ){$pages_selected = "checked";}
                if( in_array( 'search', $params['ad_pages']) ){$search_selected = "checked";}
                if( in_array( 'categories', $params['ad_pages']) ){$categories_selected = "checked";}
                if( in_array( 'tags', $params['ad_pages']) ){$tags_selected = "checked";}
                if( in_array( 'error404', $params['ad_pages']) ){$error404_selected = "checked";}
            }
            
            
            $margin = wpadgu_utility::get_option($this->prefix.'default_margin');
            if( $params && $params['ad_margin'] != '' )
            {
                $margin = (int)$params['ad_margin'];
            }
            
            $frame_label_title = __("Advertisements", WPADGU_TRANS);
            if( $params && $params['ad_frame_label_title'] != '' )
            {
                $frame_label_title = sanitize_text_field( $params['ad_frame_label_title'] );
            }
            
            $frame = '';
            if( $params && $params['ad_frame'] == 'checked' )
            {
                $frame = "checked";
            }
            
            $border_color = '';
            if( $params && $params['ad_border_color'] != '' )
            {
                $border_color = $params['ad_border_color'];
            }
            
            $css_display = 'display:none;';
            if( $params && $params['id']> 0 ){ $css_display = "display:;"; }
            
            $output[] = "<div id='wpadgu_ads_editor' style='{$css_display}'>";
            $output[] = "<form action='".$this->admin_url."-ads&p=save' method='post'>";
            $output[] = "<input type='hidden' name='_nonce' value='".wp_create_nonce('ads-form-'.$this->prefix)."'>";
            $output[] = "<input type='hidden' name='ad_id' value='".(int)$params['id']."'>";
            $output[] = "<table border=0 width=100% cellspacing=0>";
            $output[] = "<tbody>";
           
            $output[] = "<tr class='wpadgu_headTD'><td  colspan=4>".__("Ads Editor", WPADGU_TRANS)."</td></tr>";
            //shortcode
            if( isset( $params ) && $params['id']!='' )
            {
                $output[] = "<tr class='wpadgu_headTD'><td  colspan=4>";
                $output[] = __("Shortcode", WPADGU_TRANS)."<br />";
                $output[] = "<div class='wpadgu_shortcode'><pre>[wpadgu_ads unique_code=\"".$params['unique_code']."\"]";
                $output[] = "</pre></div>";
                $output[] = "</td></tr>";
                
                $output[] = "<tr class='wpadgu_headTD'><td  colspan=4>";
                $output[] = __("PHP Shortcode", WPADGU_TRANS)."<br />";
                $output[] = "<div class='wpadgu_shortcode'><pre>do_shortcode('[wpadgu_ads unique_code=\"".$params['unique_code']."\"]');";
                $output[] = "</pre></div>";
                $output[] = "</td></tr>";
            }
            //Status
            $output[] = "<tr class='wpadgu_underTD'><td style='width:6%'>";
            $output[] = __("Current Status", WPADGU_TRANS)."</td><td colspan=3>".$status_list."";
            $output[] = "</td>";
            //Ad name
            $output[] = "<tr class='wpadgu_underTD'><td colspan=4>".__("Ad name (title)", WPADGU_TRANS).":<br />";
            $output[] = "<input type='text' name='ad_title' style='width:40%;' value='".$params['ad_title']."'>";
            $output[] = "</td></tr>";
            //AdCode
            $output[] = "<tr class='wpadgu_underTD'><td colspan=4>".__("Place your code here", WPADGU_TRANS).":<br />";
            $output[] = "<textarea name='ad_code' style='width:40%;height:130px;'>".stripslashes($params['ad_code'])."</textarea>";
            $output[] = "</td></tr>";
            //Position
            $output[] = "<tr class='wpadgu_underTD'><td style='width:6%'>";
            $output[] = __("Position", WPADGU_TRANS)."</td><td style='width:15%'><select name='position' id='wpadgu_position'>".$positions_list."</select>";
            $output[] = "</td>";
            //Alignment
            $output[] = "<td style='width:6%'>";
            $output[] = __("Alignment", WPADGU_TRANS)."</td><td><select name='alignment'>".$aligns_list."</select>";
            $output[] = "</td></tr>";
            //split words
            $ad_turn_list = '';
            foreach(range(1,15) as $num) {
                $ad_turn_list .= "<option value='".$num."' ";
                if( $params['ad_turn'] == $num ){ $ad_turn_list .= "selected";}
                $ad_turn_list .= ">".$num."</option>";
            }
            
            $ad_turn_td_display = 'none';
            if( $params && $params['ad_position'] == 'inside_post' ){ $ad_turn_td_display=''; }
            
            $output[] = "<tr style='display:$ad_turn_td_display' class='wpadgu_underTD wpadgu_position_choose_tempcolor' id='wpadgu_position_choose'><td style='width:10%'>";
            $output[] = __("Display order", WPADGU_TRANS)."</td><td colspan=3>";
            $output[] = "<select name='ad_turn'>{$ad_turn_list}</select> ".__("Paragraphs", WPADGU_TRANS)."<br />";
            $output[] = "<span class='wpadgu_smallfont'>".__("It means, How many paragraphs will appear before the ad", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            
            //Pages
            $output[] = "<tr class='wpadgu_underTD'><td style='width:6%'>";
            $output[] = __("Pages", WPADGU_TRANS)."</td><td colspan=3>";
            $output[] = "<input type='checkbox' name='pages[]' value='homepage' {$homepage_selected} id='homepage'><label for='homepage'>".__("Home", WPADGU_TRANS)." <font class='wpadgu_smallfont'> [".__("Widget & Shortcode", WPADGU_TRANS)."]</font></label><br />";
            $output[] = "<input type='checkbox' name='pages[]' value='posts' {$posts_selected} id='posts'><label for='posts'>".__("Posts", WPADGU_TRANS)."</label>&nbsp;&nbsp; -<span class='wpadgu_smallfont'>".__("Post exception can be done through post editing", WPADGU_TRANS)."</span><br />";
            $output[] = "<input type='checkbox' name='pages[]' value='pages' {$pages_selected} id='pages'><label for='pages'>".__("Pages", WPADGU_TRANS)."</label>&nbsp;&nbsp; -<span class='wpadgu_smallfont'>".__("Page exception can be done through Page editing", WPADGU_TRANS)."</span><br />";
            $output[] = "<input type='checkbox' name='pages[]' value='search' {$search_selected} id='search'><label for='search'>".__("Search Results", WPADGU_TRANS)." <font class='wpadgu_smallfont'> [".__("Widget & Shortcode", WPADGU_TRANS)."]</font></label><br />";
            $output[] = "<input type='checkbox' name='pages[]' value='categories' {$categories_selected} id='categories'><label for='categories'>".__("Categories", WPADGU_TRANS)." <font class='wpadgu_smallfont'> [".__("Widget & Shortcode", WPADGU_TRANS)."]</font></label><br />";
            $output[] = "<input type='checkbox' name='pages[]' value='tags' {$tags_selected} id='tags'><label for='tags'>".__("Tags", WPADGU_TRANS)." <font class='wpadgu_smallfont'> [".__("Widgets & Shortcode", WPADGU_TRANS)."]</font></label><br />";
            $output[] = "<input type='checkbox' name='pages[]' value='error404' {$error404_selected} id='error404'><label for='error404'>".__("404", WPADGU_TRANS)." <font class='wpadgu_smallfont'> [".__("Widget & Shortcode", WPADGU_TRANS)."]</font></label><br />";
            $output[] = "</td></tr>";
            //Margin
            $output[] = "<tr class='wpadgu_underTD'><td style='width:10%'>";
            $output[] = __("Margin (Padding)", WPADGU_TRANS)."</td><td colspan=3>";
            $output[] = "<input type='text' name='margin' value='{$margin}' style='width:40px;'> PX";
            $output[] = "</td></tr>";
            //Display Frame
            $output[] = "<tr class='wpadgu_underTD'><td style='width:10%'>";
            $output[] = __("MISLABELING", WPADGU_TRANS)."</td><td colspan=3>";
            $output[] = "<input type='checkbox' name='frame' value='checked' id='frame' $frame><label for='frame'>".__("Display Frame", WPADGU_TRANS)."</label><br />";
            $output[] = "</td></tr>";
            
            $output[] = "<tr class='wpadgu_underTD'><td style='width:10%'>";
            $output[] = __("Label Title", WPADGU_TRANS)."</td><td colspan=3>";
            $output[] = "<input type='text' name='frame_label_title' value='{$frame_label_title}'><br />";
            $output[] = "<span class='wpadgu_smallfont'>".__("Leave it blank to disable title appearing", WPADGU_TRANS)."</span>";
            $output[] = "</td></tr>";
            
            $output[] = "<tr class='wpadgu_underTD'><td style='width:10%'>";
            $output[] = __("Label Border", WPADGU_TRANS)."</td><td colspan=3>";
            $output[] = "<input type='text' name='border_color' value='{$border_color}' placeholder='#EFEFEF'><br /><font class='wpadgu_smallfont'>".__("Empty = Disable this choice.", WPADGU_TRANS)."</font><br />";
            $output[] = "</td></tr>";
            
            //submit
            $output[] = "<tr class='wpadgu_headTD'><td colspan=4>";
            $output[] = "<input type='submit' class='button' value='".__("Save", WPADGU_TRANS)."'>";
            $output[] = "<input type='button' class='button' id='wpadgu_close_ad_btn' value='".__("Close", WPADGU_TRANS)."'>";
            $output[] = "</td></tr>";
            
            $output[] = "</tbody></table></form></div>";
            
            $js = "<script>";
            $js .= "jQuery('#wpadgu_close_ad_btn').click(function(){";
            if( isset( $params ) && $params['id']!='' )
            {
                $js .= "jQuery('#wpadgu_ads_editor').click(function(){";
                $js .= "window.location.href=\"".$this->admin_url."-ads\";";
                $js .= "});";
            } else {
                $js .= "jQuery('#wpadgu_ads_editor').hide();";
            }
            $js .= "});";
            
            $js .= "if(jQuery( '#wpadgu_position' ).val() == 'inside_post'){";
            $js .= "jQuery('#wpadgu_position_choose').show();";
            $js .= "setTimeout(function(){";
            $js .= "jQuery('#wpadgu_position_choose').removeClass('wpadgu_position_choose_tempcolor');";
            $js .= "},1500);";
            $js .= "}";
            $js .= "jQuery('#wpadgu_position').on('change', function() {";
            $js .= "if(this.value == 'inside_post'){";
            $js .= "jQuery('#wpadgu_position_choose').show(300);";
            $js .= "setTimeout(function(){";
            $js .= "jQuery('#wpadgu_position_choose').removeClass('wpadgu_position_choose_tempcolor');";
            $js .= "},1500);";
            $js .= "} else {";
            $js .= "jQuery('#wpadgu_position_choose').hide();";
            $js .= "}";
            $js .= "});";
            $js .= "</script>";
            
            $output[] = $js;
            return implode("\n", $output);
        }
        
        
        ///////////////////////////
        //////// GUARD ACTIONS ///
        //////////////////////////
        
        //php calc clicks for admin-ajax
        public function wpadgu_calc_click()
        {
            $cookiename = 'wpadgu_userhas';
            if( defined( 'WPADGU_COOKIE_CLICKS_NAME' ) ){ $cookiename = WPADGU_COOKIE_CLICKS_NAME; }
            
            $current_value = 0;
            if( isset($_COOKIE[$cookiename]) ) {$current_value = (int)$_COOKIE[$cookiename];}
            
            setcookie('wpadgu_userhas', $current_value+1, time()+3600);
           
            
            exit;
        }
        
        //php calc clicks for admin-ajax
        public function wpadgu_click_banip()
        {
            global $wpdb;
           
            if( !isset($_POST['_nonce']) 
                || false === wp_verify_nonce( $_POST['_nonce'], 'wpadgu-click')) {wp_die(); exit;}
            
                
            if( !isset( $_POST['ip'])
                || !isset( $_POST['country'])
                || !isset( $_POST['wpadgu_clicks_count'])
                || !isset( $_POST['cookie_expiration'])
                || !isset( $_POST['ban_duration'])
                || !isset( $_POST['dateline'])
                ) {wp_die(); exit;}
            
            
            $ipaddress = sanitize_text_field( $_POST['ip']);
            $country = sanitize_text_field( $_POST['country']);
            $wpadgu_clicks_count = (int)sanitize_text_field( $_POST['wpadgu_clicks_count']);
            $cookie_expiration = sanitize_text_field( $_POST['cookie_expiration']);
            $ban_duration = sanitize_text_field( $_POST['ban_duration']);
            $dateline = sanitize_text_field( $_POST['dateline']);
            
            if( !filter_var( $ipaddress, FILTER_VALIDATE_IP ) ){wp_die(); exit;}
            if( $wpadgu_clicks_count <= 0 ){wp_die(); exit;}
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'blocked_ips';
            $data = array(
                'ipaddress' => $ipaddress,
                'country_code' => $country,
                'clicks' => $wpadgu_clicks_count,
                'when_unblock' => $dateline+($ban_duration*60*60),
                'dateline' => $dateline,
            );
            
            $query = $wpdb->prepare( "SELECT COUNT(id) FROM $dbTable WHERE ipaddress=%s", $ipaddress);
            $count = $wpdb->get_var( $query );
            
            if( $count > 0 )
            {
                $wpdb->update($dbTable, $data, array("ipaddress"=>$ipaddress));
            } else {
                $wpdb->insert($dbTable, $data);
                
                wpadgu_nots::push( __("IP Address Blocked!", WPADGU_TRANS), 
                    "guard", 
                    __("IP Address", WPADGU_TRANS)." ".$ipaddress." ".__("blocked because of maximum clicks.", WPADGU_TRANS), "warning");
            }
            
            
            exit;
        }
        
        ////////////////////////////////////
        //////////// ADBLOCK ACTIONS //////
        //////////////////////////////////
        public function wpadgu_adblock_detected()
        {
           if( !isset($_POST['_nonce'])
               || false === wp_verify_nonce( $_POST['_nonce'], 'wpadgu-adblock')) {wp_die(); exit;}
                
              
            $data = array(
                "ip" => sanitize_text_field( $_POST['ip']),
                "country" => sanitize_text_field( $_POST['country']),
                "adblockaction" => sanitize_text_field( $_POST['adblockaction']),
                "browser" => sanitize_text_field( $_POST['browser']),
                "dateline" => sanitize_text_field( $_POST['dateline']),
            );
             
        }
        
        
    }
    
    //run plugin
    wpadgu_plugin::init();
}

//schedules
function wpadgu_hourly()
{
    global $wpdb;

    $dbTable = $wpdb->prefix.WPADGU_PREFIX.'blocked_ips';
    $dbTable_nots = $wpdb->prefix.WPADGU_PREFIX.'nots';
    
    $day = time() + (24*60*60);
    $two_days = time() + (2*24*60*60);
    $three_days = time() + (3*24*60*60);
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM $dbTable WHERE when_unblock < %s", time() ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM $dbTable WHERE dateline > %s", $two_days ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM $dbTable_nots WHERE dateline > %s", $three_days ) );
}

//action links
if( function_exists( 'wpadgu_plugin_action_links' ) )
{
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'wpadgu_plugin_action_links' );
}


if( !function_exists( 'wpadgu_load_textdomain' ) )
{
    add_action( 'plugins_loaded', 'wpadgu_load_textdomain' );

    function wpadgu_load_textdomain() {
        load_plugin_textdomain( WPADGU_SLUG, false, basename( dirname( __FILE__ ) ) . '/languages' );
    }
}
?>