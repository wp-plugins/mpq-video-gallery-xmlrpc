=== MPQ Video Gallery XMLRPC ===
Contributor: zhouyibhic
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VLFPQMLSD9MJC
Tags: Gallery, Video, XMLRPC
Requires at least: 3.3
Tested up to: 3.3.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin creates functions for Clean Video Gallery Plugin which can be XMLRPC invoked remotely.

== Description ==

 Basically this plugin creates XMLRPC interfaces for Clean Video Gallery Plugin. 
  NOTE: API functions (xmlrpc revoke functions) are all prefixed with 'i_gllrxmlrpc'.


== Installation ==
0. Clean Video Gallery plugin has to be installed as the pre-requisite condition. You can find Clean Video Gallery Plugin here, http://wordpress.org/extend/plugins/clean-video-gallery/
1. unzip the plugin
2. Upload plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. In administrator portal, click Settings->Video Gallery Plugin XMLRPC Interface to enable/disable the api functions.

Warning: Debug Error log is being appended into the WP_CONTENT_DIR . '/debug.log'; Make sure that in production environment, trun off the debug directive in wp-includes/default-constants.php  

== Frequently Asked Questions ==

= Exmaple to invoke the xmlrpc api in PHP=
//this example demo how to query the clean video gallery posts
set_time_limit(0);
require_once("IXR_Library.php.inc");
 
$client->debug = true; // Set it to false in Production Environment
 
// Create the client object
$client = new IXR_Client('{hostname}','/xmlrpc.php?XDEBUG_SESSION_START=ECLIPSE_DBGP&KEY=13522981788714');
 
$username = "{admin login name}";
 
$password = "{password}";

 
 $function_args = array(array('',$username,$password, array('post_type'=>'galleryvideo')));
 $params = array($username,$password,"i_mpqvideogllrxmlrpc_metaweblog_getposts", $function_args); 
 
 // Run a query To Read Posts From Wordpress
 $ret = $client->query('mpqvideogllrxmlrpc_extapi.callMpqVideoGllrMethod', $params);
 if (!$ret) {
 	die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
 }
 
 $myresponse = $client->getResponse();
 print_r($myresponse);
 die();


== Changelog ==

= 0.1 =
* initial version
