<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'wpadgu_adblock' ) )
{
    class wpadgu_adblock
    {
        public $plugin_status;
        public $adblock_status;
        
        public function __construct()
        {
            $this->plugin_status = wpadgu_utility::get_option(WPADGU_PREFIX.'status');
            $this->adblock_status = wpadgu_utility::get_option(WPADGU_PREFIX.'adblock_status');
            
            add_action( 'init', array( $this, 'init' ) );
        }
        
        public function init()
        {
            if( $this->plugin_status=="enabled" && $this->adblock_status=="enabled"):
                if( isset($_GET['wpadgu_h']) && $_GET['wpadgu_h'] == 'true' )
                {
                    add_filter( 'the_content', array( $this, 'content'));
                    add_filter( 'post_link', array( $this, 'links'), 10, 3 );
                }
            endif; 
        }
        
        public function content( $content )
        {
            if( is_singular( 'post' ) || is_page() ):
                $splitter = " ";
                $exp = explode( $splitter, $content );
                
                if( sizeof( $exp ) > 50 )
                {
                    return '<div class="wpadgu_prepare_to_hide">'.$this->shorten_string($content, 50).'</div>'.$this->whitelist_us();
                }
                
            endif;
            return $content;
        }
        
        public function links( $url, $post, $leavename=false ) {
            $url = add_query_arg( 'wpadgu_h', 'true', $url );
            
            return $url;
        }
        
        
        public function whitelist_us()
        {
            $html = new wpadgu_html();
            $output[] = "<div class='wpadgu_whitelist_us'>";
            $output[] = "<center><img src='".plugins_url("/assets/img/lock-64.png", WPADGUPATH)."'><br /><br />";
            $output[] = __("Content Hidden! Please disable <strong>Adblock</strong> extension for our website from your internet browser to continue!", WPADGU_TRANS);
            $output[] = "<br /><br /><span class='wpadgu_smallfont'>".__("Do not forget to reload page using this button.", WPADGU_TRANS)."</span><br />";
            $output[] = $html->button(__("REFRESH", WPADGU_TRANS), "button", "wpadgu_refresh", "wpadgu_refresh", "refresh_2-16.png");
            $output[] = "</center>";
            $output[] = "</div>";
            
            $js = "<script>";
            $js .= "jQuery('#wpadgu_refresh').click(function()";
            $js .= "{";
            $js .= "window.location.href=\"".str_replace("?wpadgu_h=true", "", wpadgu_selfURL())."\"";
            $js.= "})";
            $js .= "</script>";
            
            $output[] = $js;
            return implode("\n", $output);
        }
        
        /*  Returns the first $wordsreturned out of $string.  If string
         contains fewer words than $wordsreturned, the entire string
         is returned.
         */
        public function shorten_string($string, $wordsreturned)
        {
            $retval = $string;      //  Just in case of a problem
            
            $array = explode(" ", $string);
            if (count($array)<=$wordsreturned)
            /*  Already short enough, return the whole thing
             */
            {
                $retval = $string;
            }
            else
            /*  Need to chop of some words
             */
            {
                array_splice($array, $wordsreturned);
                $retval = implode(" ", $array)." ...";
            }
            return $retval;
        }
        
        public function constants( $option )
        {
            $result = '';
            if( $option == 'ip' )
            {
                $_ip = wpadgu_agent::user_ip();
                $result = wpadgu_GetRealIP();
                if( $_ip != '' )
                {
                    $result = $_ip;
                }
            } else if( $option == 'country_name' )
            {
                //get country name
                $_country = wpadgu_agent::ip2country( wpadgu_agent::user_ip(), 'country_name' );
                $result = '';
                if( $_country != '' )
                {
                    $result = $_country;
                }
            } else if( $option == 'country_code' )
            {
                //get country code
                $_country_code = wpadgu_agent::ip2country( wpadgu_agent::user_ip() );
                $result = '';
                if( $_country_code != '' )
                {
                    $result = $_country_code;
                }
            } else if( $option == 'device' )
            {
                //get device
                $_device = wpadgu_agent::device();
                $result = '';
                if( $_device != '' )
                {
                    $result = $_device;
                }
            } else if( $option == 'ISP' )
            {
                //get user ISP
                $_ISP = wpadgu_agent::ISP();
                $result = '';
                if( $_ISP != '' )
                {
                    $result = $_ISP;
                }
            } else if( $option == 'bot' )
            {
                //check if bot
                $result = 'false';
                if( wpadgu_agent::is_bot() )
                {
                    $result = 'true';
                }
            } else if( $option == 'current_page' )
            {
                //get current page
                $result = '';
                if( isset( $_SERVER['REQUEST_URI'] ) && !empty( $_SERVER['REQUEST_URI'] ) )
                {
                    $result = $_SERVER['REQUEST_URI'];
                }
            } else if( $option == 'came_from' )
            {
                //get http referer (came from any website)
                $result = '';
                if( isset( $_SERVER['HTTP_REFERER'] ) && !empty( $_SERVER['HTTP_REFERER'] ) )
                {
                    $visitor_came_from = $_SERVER['HTTP_REFERER'];
                    $result = $this->get_host( $visitor_came_from );
                }
            }
            
            return $result;
        }
    }
    
    //run
    new wpadgu_adblock();
}
?>