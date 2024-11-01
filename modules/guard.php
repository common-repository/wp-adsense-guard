<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'wpadgu_guard' ) )
{
    class wpadgu_guard
    {
        public 
                $print_js,
                $country,
                $ip,
                $wpadgu_userhas,
                $iBot;
        
        public function __construct()
        {
           add_action('init', array($this, 'init'));
           
        }
        
        public function init()
        {
            $this->wpadgu_userhas = $_COOKIE['wpadgu_userhas'];
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
        
        public function guard_status()
        {
            if( wpadgu_utility::get_option(WPADGU_PREFIX.'guard_status') != 'enabled' ){ return false; }
            return true;
        }
        
        public function guard_settings()
        {
            $settings = array();
            
            //Maximum Clicks
            $settings['max_clicks'] = "4";
            $max_click = wpadgu_utility::get_option(WPADGU_PREFIX.'max_clicks');
            if( $max_click != '' )
            {
                $settings['max_clicks'] = (int)$max_click;
            }
            
            //Blacklist IPs
            $blacklist_ips = wpadgu_utility::get_option(WPADGU_PREFIX.'blacklist_ips');
            $settings['blacklist_ips'] = "";
            if( $blacklist_ips!="" )
            {
                $settings['blacklist_ips'] = explode(",", $blacklist_ips);
            }
            
            //Blacklist Countries
            $blacklist_countries = wpadgu_utility::get_option(WPADGU_PREFIX.'blacklist_countries');
            $settings['blacklist_countries'] = "";
            if( $blacklist_countries != "" )
            {
                $settings['blacklist_countries'] = explode(",", $blacklist_countries);
            }
            
            //Blacklist Bots
            $blacklist_bots = wpadgu_utility::get_option(WPADGU_PREFIX.'blacklist_bots');
            $settings['blacklist_bots'] = "";
            if( $blacklist_bots != "" )
            {
                $settings['blacklist_bots'] = explode(",", $blacklist_bots);
            }
            
            return $settings;
        }
        
        public function is_bad()
        {
           
           // echo $this->constants( 'ISP' );
            $settings = $this->guard_settings();
            $cookiename = 'wpadgu_userhas';
            //echo $this->wpadgu_userhas."<br />".$settings['max_clicks'];
            if( !is_array( $settings ) ){ return; }
            
            //check guard status
            if( !$this->guard_status() ){ return; }
            
            //check IP
            if( wpadgu_utility::get_option(WPADGU_PREFIX.'ban_strategy') == 'cookie_db')
            {
                if( $this->is_ban_ip() )
                {
                    return true;
                }
            }
            //check country
            if( $settings['blacklist_countries']!='' && is_array( $settings['blacklist_countries']) )
            {
                if( in_array( $this->constants( 'country_code' ), $settings['blacklist_countries'] ) ){return true;}
            }
           
            //check max clicks
            $cookiename = 'wpadgu_userhas';
            if( defined( 'WPADGU_COOKIE_CLICKS_NAME' ) ){ $cookiename = WPADGU_COOKIE_CLICKS_NAME; }
            
            if( $settings['max_clicks'] > 0 )
            {
                if( isset( $_COOKIE[$cookiename] ) && $_COOKIE[$cookiename] > $settings['max_clicks'] ){ return true; }
            }
            
            return false;
        }
        
        
        public function is_ban_ip()
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'blocked_ips';
            $ipaddress = $this->constants('ip');
            
            $query = $wpdb->prepare( "SELECT COUNT(id) FROM $dbTable WHERE ipaddress=%s", $ipaddress);
            $vars = $wpdb->get_var( $query );
            
            if( $vars > 0 ){ return true; } 
            
            return false;
        }
        
        public static function countall()
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'blocked_ips';
            
            $Query = "SELECT count(id) FROM {$dbTable}";
            
            return $wpdb->get_var( $Query );
        }
        
        public static function delete( $id=false )
        {
            if( !$id && !isset( $_GET['id']) ){ wp_die(); exit;}
            
            if( isset( $_GET['id'] ) ){ $id = (int) sanitize_text_field( $_GET['id']); }
            
            if( !isset( $_GET['_nonce'])
                || false === wp_verify_nonce( $_GET['_nonce'], 'wpadgu-delete-guard-log') ) {wp_die(); exit;}
            
            global $wpdb;
            
            $wpdb->delete($wpdb->prefix.WPADGU_PREFIX.'blocked_ips', array( "id" => $id ) );
            
            if( self::countall() == 0 )
            {
                ?>
                <script>
				jQuery("#wpadgu_guard_log").html("<span style='color:#c9c9c9; size:9pt;'><?php echo __("Log is empty!", WPADGU_TRANS);?></span>"); 
                </script>
                <?php 
            }
            
            exit;
        }
       
    }
    
}
?>