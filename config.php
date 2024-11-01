<?php 
/**
 * WP Adsense Guard Config File
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//set plugin version
if( !defined( 'WPADGU_VERSION' ) ) { define( 'WPADGU_VERSION', "1.0" ); }
if( !defined( 'WPADGU_VERSION_TYPE' ) ) { define( 'WPADGU_VERSION_TYPE', "free" ); } //pro // free

//set the plugin slug & prefix
if( !defined( 'WPADGU_SLUG' ) ){ define( 'WPADGU_SLUG', "wp-adsense-guard" ); }
if( !defined( 'WPADGU_PREFIX' ) ){ define( 'WPADGU_PREFIX', "wpadgu" ); }

//set the plugin path and directory
if( !defined( 'WPADGU_PATH' ) ){ define( 'WPADGUPATH', __FILE__ ); }
if( !defined( 'WPADGU_DIR' ) ){ define( 'WPADGU_DIR', dirname(__FILE__) ); }

//translation slug
if( !defined( 'WPADGU_TRANS' ) ){ define( 'WPADGU_TRANS', 'wpadgu' ); }

//cookies
if( !defined( 'WPADGU_COOKIE_CLICKS_NAME' ) ){ define( 'WPADGU_COOKIE_CLICKS_NAME', 'wpadgu_userhas' ); }

//path to GEOip
if(!defined( 'WPADGU_GEOPATH' )){define ('WPADGU_GEOPATH', WPADGU_DIR."/includes/GeoIP.dat");}

//split string to count max ads 
if(!defined( 'WPADGU_SPLITTER_MAX_ADS' )){define ('WPADGU_SPLITTER_MAX_ADS', 150);}

//Google Custom Search Slice Letters Count
if(!defined( 'WPADGU_SLICE_LETTERES' )){define ('WPADGU_SLICE_LETTERES', "100");}

//donate URL
if( !defined( 'WPADGU_DONATE_URL' )){define('WPADGU_DONATE_URL', 'http://technoyer.com/adsguard/pro.php');}

//includes
include 'includes/functions.php';
include 'includes/encriptor.php';
include 'includes/help.php';
include 'includes/base.php';
include 'includes/html.php';
include 'includes/geoip.php';
include 'includes/agent.php';
include 'includes/badwords.php';
include 'includes/dictionary.php';
include 'modules/guard.php';
include 'modules/nots.php';
include 'modules/adblock.php';
?>