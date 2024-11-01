<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'wpadgu_help' ) )
{
    class wpadgu_help
    {
        public function tuts()
        {
            $output[] = "<div class='wpadgu_help'>";
            $output[] ="<center><img src='".plugins_url("/assets/img/help_banner.png", WPADGUPATH)."'></center>";
            $output[] = "<table border=0 cellspacing=0 style='width:250px; margin:0 auto;'>";
            //widget ad
            $output[] = "<tr class='wpadgu_underTD'>";
            $output[] = "<td width='32' style='padding:2px;'><img src='".plugins_url('/assets/img/video-player-32.png', WPADGUPATH)."'></td>";
            $output[] = "<td><a href='http://technoyer.com/adsguard/widget-ad' target=_blank><strong>".__("How to create widget ad?", WPADGU_TRANS)."</strong></a></td>";
            $output[] = "</tr>";
            //blacklisted bot
            $output[] = "<tr class='wpadgu_underTD'>";
            $output[] = "<td width='32' style='padding:2px;'><img src='".plugins_url('/assets/img/article-32.png', WPADGUPATH)."'></td>";
            $output[] = "<td><a href='http://technoyer.com/adsguard/block-bots' target=_blank><strong>".__("Why should I block some bots?", WPADGU_TRANS)."</strong></a></td>";
            $output[] = "</tr>";
            //custom search API
            $output[] = "<tr class='wpadgu_underTD'>";
            $output[] = "<td width='32' style='padding:2px;'><img src='".plugins_url('/assets/img/video-player-32.png', WPADGUPATH)."'></td>";
            $output[] = "<td><a href='http://technoyer.com/adsguard/custom-api' target=_blank><strong>".__("How can I get the custom search API?", WPADGU_TRANS)."</strong></a></td>";
            $output[] = "</tr>";
            $output[] = "</table>";
            $output[] ="</div>";
            
            return implode("\n", $output);
        }
        
        public function products( $donate_url )
        {
            $output[] = "<div class='wpadgu_help'>";
            
            $output[] = "<center><a href='".$donate_url."' target=_blank><span class='wpadgu_smallfont'>".__("Remove Ads")."</span></a><br />";
            $output[] = "<a href='$donate_url' target=_blank><img src='".plugins_url('/assets/img/adsguardpro_banner.png', WPADGUPATH)."'></a></center>";
            
            $output[] = "<center><a href='".$donate_url."' target=_blank><span class='wpadgu_smallfont'>".__("Remove Ads")."</span></a><br />";
            $output[] = "<a href='http://technoyer.com/bravo/?utm_source=".wpadgu_getDomainUrl(wpadgu_selfURL())."&utm_medium=adsguard_trial&utm_content=help_sidebar' target=_blank><img src='".plugins_url('/assets/img/bravo_banner.png', WPADGUPATH)."'></a></center>";
            
            $output[] ="</div>";
            
            return implode("\n", $output);
        }
    }
    
}

?>