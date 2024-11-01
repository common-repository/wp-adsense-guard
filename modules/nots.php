<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'wpadgu_nots' ) )
{
    class wpadgu_nots
    {
        public function __construct()
        {
            
        }
        
        public static function nots_list()
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'nots';
            
            $Query = "SELECT * FROM {$dbTable} ORDER by id DESC";
            
            $results = $wpdb->get_results( $Query );
            if( null!=$results )
            {
                $css_style = '';
                if( self::countall() >= 10 ) {$css_style = "overflow-y:scroll;height:650px;";}
                
                $output = '<div id="wpadgu_notsdiv" style="'.$css_style.'">';
               // $output .= "<table border=0 width=100% cellspacing=0>";
                
                $js = "<script>".PHP_EOL;
                $js .= "jQuery(document).ready(function(){".PHP_EOL;
                
                foreach ( $results as $row )
                {
                    $css_not_style = '';
                    if( $row->is_read == 1 ){$css_not_style = 'background:#E1EBF1';}
                    
                    $icon = 'not_emailed.png';
                    $icon_title = __("Did not send to email", WPADGU_TRANS);
                    if( $row->is_emailed == 1 ){$icon= 'emailed.png'; $icon_title = __("Sent to email", WPADGU_TRANS);}
                    
                    $title = esc_js( $row->note_title );
                    $desc = esc_js ( $row->note_desc );
                    $desc = wpadgu_bbcode_postid( $desc );
                    $dateline = esc_js( $row->dateline );
                    
                    $time = 'n/a';
                    if( $dateline!='' ) {$time = wpadgu_ago( $dateline )." ".__("ago", WPADGU_TRANS); }
                    
                    $level_css_class = 'wpadgu_nots_info';
                    if( $row->note_level == 'warning')
                    {
                        $level_css_class = 'wpadgu_nots_warning';
                    } else if( $row->note_level == 'success')
                    {
                        $level_css_class = 'wpadgu_nots_success';
                    } else if( $row->note_level == 'error')
                    {
                        $level_css_class = 'wpadgu_nots_error';
                    }
                    
                    $tools = '';
                    $ignored = '';
                    if( in_array($row->note_category, array( 'guidelines_content', 'guidelines_maxads' ) ) && (int)$row->post_id > 0 )
                    {
                        if( $row->note_category == 'guidelines_content' ){ $meta_key = WPADGU_PREFIX.'adsense_alerts'; }
                        else if( $row->note_category == 'guidelines_maxads' ){ $meta_key = WPADGU_PREFIX.'max_ads'; }
                        
                        $ignored = "<span id='wpadgu_alread_ignored".$row->id."' style='display:none;'><img src='".plugins_url("/assets/img/ignore-16.png", WPADGUPATH)."'> <span class='wpadgu_nots_ignored'>".__("Ignored", WPADGU_TRANS)."</span></span>";
                        
                        $tools = "<span id='wpadgu_nots_tools".$row->id."'><span class='wpadgu_nots_tools' id='wpadgu_nots_tools_fix{$row->id}'>".__("Fix it", WPADGU_TRANS)."</span> . ";
                        $tools .= "<span class='wpadgu_nots_tools' id='wpadgu_nots_tools_ignore{$row->id}'>".__("Ignore", WPADGU_TRANS)."</span></span> ";
                        
                        if( isset( $meta_key ) && !empty( $meta_key ) )
                        {
                            $meta_option = get_post_meta($row->post_id, $meta_key, true);
                            
                            if( $meta_option == 'fixed' )
                            {
                                $tools = "<img src='".plugins_url("/assets/img/fixed-16.png", WPADGUPATH)."'> <span class='wpadgu_nots_fixed'>".__("Fixed", WPADGU_TRANS)."</span>";
                            } else if( $meta_option == 'ignored' )
                            {
                                $tools = "<img src='".plugins_url("/assets/img/ignore-16.png", WPADGUPATH)."'> <span class='wpadgu_nots_ignored'>".__("Ignored", WPADGU_TRANS)."</span>";
                            }
                            
                        }
                    }
                    
                    $output .= '<div id="wpadgu_not_log_row'.$row->id.'" class="wpadgu_nots_notice '.$level_css_class.'" style="'.$css_not_style.'"><p><img title="'.$icon_title.'" src="'.plugins_url('/assets/img/'.$icon, WPADGUPATH).'"> <strong>'.$title.'</strong> <span class="wpadgu_nots_time">'.$time.'</span><br />'.$desc.' <br />'.$tools.$ignored.'</p><div class="wpadgu_del" id="wpadgu_del_note'.$row->id.'" title="'.__("Delete Notification", WPADGU_TRANS).'">X</div></div>';
                    
                    $user = wp_get_current_user();
                    $userid = $user->ID;
                    
                    $wpdb->update( $dbTable, array(
                        "is_read" => 0,
                        "read_by" => $userid,
                        "read_time" => time()
                    ), array("id" => $row->id , "is_read" => 1 ));
                    
                    $ajax_url = add_query_arg( array(
                        "action" => "wpadgu_delete_note",
                        "_nonce" => wp_create_nonce( 'wpadgu-delete-note')
                    ), "admin-ajax.php");
                    //Delete
                    $js .= "jQuery('#wpadgu_del_note".$row->id."').click( function( $ ){".PHP_EOL;
                    $js .= "if (confirm('".__("Are you sure?!", WPADGU_TRANS)."')) {".PHP_EOL;
                    $js .= "jQuery('#wpadgu_not_log_row".$row->id."').hide(500);".PHP_EOL;
                    $js .= "jQuery('#wpadgu_results').load('".$ajax_url."&id=".$row->id."');".PHP_EOL;
                    $js .= "}".PHP_EOL;
                    $js .= "return false".PHP_EOL;
                    $js .= "});".PHP_EOL;
                    //Fix
                    if( (int)$row->post_id > 0)
                    {
                        $fix_url = add_query_arg(
                            array( 
                                "wpadgu_action" => 'fix_content',
                                "wpadgu_category" => $row->note_category
                            )
                            , admin_url( 'post.php?post='.$row->post_id.'&action=edit'));
                        
                        $js .= "jQuery('#wpadgu_nots_tools_fix".$row->id."').click( function( $ ){".PHP_EOL;
                        $js .= "window.location.href='".$fix_url."';";
                        $js .= "});".PHP_EOL;
                    }
                    
                    //Ignore
                    if( (int)$row->post_id > 0)
                    {
                        $ajax_ignore_url = add_query_arg(
                            array(
                                "action" => 'ignore_post_guidelines_alert',
                                "_nonce" => wp_create_nonce('ignore-post-guidelines-alert'),
                                "post_id" => $row->post_id,
                                "category" => $row->note_category,
                                "note_id" => $row->id,
                            )
                            , admin_url( 'admin-ajax.php'));
                        
                        $js .= "jQuery('#wpadgu_nots_tools_ignore".$row->id."').click( function( $ ){".PHP_EOL;
                        $js .= "jQuery('#wpadgu_results').load('".$ajax_ignore_url."');".PHP_EOL;
                        $js .= "});".PHP_EOL;
                    }
                }
                
                $js .= "});".PHP_EOL;
                $js .= "</script>".PHP_EOL;
                
                $output .= "</div>";
                $output .= $js;
            } else {
                $output = "<span style='color:#c9c9c9; size:9pt;'>".__("No notifications!", WPADGU_TRANS)."</span>";
            }
            
            return $output;
        }
        
        public static function ignore_post_guidelines_alert()
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            if( isset( $_GET['post_id']) && isset( $_GET['category'] ) && isset( $_GET['note_id'] ))
            {
                if( !isset( $_GET['_nonce'])
                    || false===wp_verify_nonce($_GET['_nonce'], 'ignore-post-guidelines-alert'))
                {
                    wp_die(); exit;
                }
                
                $category = sanitize_text_field( $_GET['category'] );
                $post_id = (int)$_GET['post_id'];
                $id = (int)$_GET['note_id'];
                
                if( in_array( $category, array( 'guidelines_content', 'guidelines_maxads' ) ) && $post_id > 0 )
                {
                    if( $category == 'guidelines_content' ){ $meta_key = WPADGU_PREFIX.'adsense_alerts'; }
                    else if( $category == 'guidelines_maxads' ){ $meta_key = WPADGU_PREFIX.'max_ads'; }
                    
                    if( isset( $meta_key ) && !empty( $meta_key ) )
                    {
                        update_post_meta($post_id, $meta_key, 'ignored');
                        
                        ?>
                        <script>
						jQuery('#wpadgu_nots_tools<?php echo $id?>').hide();
						jQuery('#wpadgu_alread_ignored<?php echo $id?>').show();
                        </script>
                        <?php
                    }
                }
            }
            
            exit;
        }
        
        public static function count( $is_read="1" )
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'nots';
            
            $Query = $wpdb->prepare( "SELECT count(id) FROM {$dbTable} WHERE is_read=%s", $is_read );
            
            return $wpdb->get_var( $Query );
        }
        
        public static function countall()
        {
            global $wpdb;
            
            $dbTable = $wpdb->prefix.WPADGU_PREFIX.'nots';
            
            $Query = "SELECT count(id) FROM {$dbTable}";
            
            return $wpdb->get_var( $Query );
        }
        
        public static function delete( $id=false )
        {
            //check access
            if( !wpadgu_utility::get_access( 'manage_options', true ) ){wp_die( __("Access Denied!", WPADGU_TRANS)); exit;}
            
            if( !$id && !isset( $_GET['id']) ){ wp_die(); exit;}
            
            if( isset( $_GET['id'] ) ){ $id = (int) sanitize_text_field( $_GET['id']); }
            
            if( !isset( $_GET['_nonce'])
                || false === wp_verify_nonce( $_GET['_nonce'], 'wpadgu-delete-note') ) {wp_die(); exit;}
                
                global $wpdb;
                
                $wpdb->delete($wpdb->prefix.WPADGU_PREFIX.'nots', array( "id" => $id ) );
                
                if( self::countall() == 0 )
                {
                    ?>
                    <script>
					jQuery("#wpadgu_notsdiv").html("<span style='color:#c9c9c9; size:9pt;'><?php echo __("No notifications!", WPADGU_TRANS);?></span>");
                    </script>
                    <?php 
                }
                
                exit;
        }
        
        public static function push( $title, $category, $desc, $level, $send_email=false )
        {
               $title = sanitize_text_field( $title );
               $category = sanitize_text_field( $category );
               $desc = sanitize_text_field(  ( $desc ) );
               $level = sanitize_text_field( $level );
               
               $email = wpadgu_utility::get_bloginfo( 'admin_email' );
               
               $is_emailed = 0;
               if( $send_email ){
                   
                   add_filter( 'wp_mail_content_type', function()
                       {
                           return "text/html";
                        });
                   
                   $message = "<strong>".__("Hello", WPADGU_TRANS)."</strong><br />";
                   $message .= $desc."<br /><hr>";
                   $message .= "<i>".__("This message sent via AdsGuard Plugin", WPADGU_TRANS)."</i>";
                   
                   wp_mail( $email, "[AdsGuard] ".$title, wpadgu_bbcode_postid( $message )); 
                   
                   $is_emailed = 1;
               }
               
               global $wpdb;
               
               $post_id = '0';
               if( is_singular( 'post' ) || is_page() )
               {
                    $post_id = get_the_ID();   
               }
               
               $wpdb->insert( $wpdb->prefix.WPADGU_PREFIX.'nots', array(
                  "note_title" => $title, 
                  "note_category" => $category, 
                  "note_level" => $level, 
                  "note_desc" => $desc, 
                  "is_read" => 1, 
                  "note_title" => $title, 
                  "is_emailed" => $is_emailed, 
                  "post_id" => $post_id, 
                  "dateline" => time(), 
               ));
        }
    }
    
}