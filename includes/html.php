<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'wpadgu_html' ) )
{
    class wpadgu_html
    {
        public $style_path;
        public $plugin;
        
        public function __construct()
        {
           //Include CSS and JS files
            add_action ( 'admin_enqueue_scripts' , array ($this , 'include_scrips_and_css' ) );
            add_action ( 'wp_enqueue_scripts' , array ($this , 'include_scrips_and_css_frontend' ) );
        }
        
        //HTML Button Standard
        public function button($value, $type="", $id="", $name="", $icon="")
        {
            if(!empty($icon))
            {
                $value = "<img src='".plugins_url("assets/img/".$icon, WPADGUPATH)."'> ".$value;
            }
            $output[] = '<button class="wpadgu_button_button" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
            return implode("\n", $output);
        }
        
        //HTML Button Small
        public function button_small($value, $type="", $id="", $name="", $icon="")
        {
            if(!empty($icon))
            {
                $value = "<img src='".plugins_url("assets/img/".$icon, WPADGUPATH)."'> ".$value;
            }
            $output[] = '<button class="wpadgu_button_small" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
            return implode("\n", $output);
        }
        
        //header
        public function header($title, $desc=false)
        {
            ?>
            <div class="wrap">
            <h1><?php echo esc_js( $title ); ?><div class="wpadgu_desc"></h1>
            <?php
            if( $desc )
            {
                ?>
                <div class="description"><?php echo esc_js( $desc );?></div>
                <?php    
            }
        }
        
        public function footer()
        {
            ?> </div> <?php    
        }
        
        public function tabs( $active_tab=false )
        {
            if( !$active_tab ){ $active_tab = "dashboard"; }
            ?>
            <h2 class="nav-tab-wrapper">  
                <a href="?page=<?php echo WPADGU_SLUG;?>" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Dashboard</a>  
                <a href="?page=<?php echo WPADGU_SLUG;?>-ads" class="nav-tab <?php echo $active_tab == 'ads' ? 'nav-tab-active' : ''; ?>">Ads Manager</a>  
                <a href="?page=<?php echo WPADGU_SLUG;?>-settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>  
            </h2>  
            <?php 
        }
        
    }
}