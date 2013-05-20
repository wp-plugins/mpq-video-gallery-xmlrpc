=== MPQ Video Gallery XMLRPC ===
Contributor: zhouyibhic
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VLFPQMLSD9MJC
Tags: Gallery, Photo, XMLRPC
Requires at least: 3.3
Tested up to: 3.3.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin creates functions for Gallery Plugin which can be XMLRPC invoked remotely.

== Description ==

 Basically this plugin creates XMLRPC interfaces for Gallery Plugin. 
 Thanks for the author of Extended API, Michael Grosser,  since this plugin has reused the design. 
 NOTE: API functions (xmlrpc revoke functions) are all prefixed with 'i_gllrxmlrpc'.


== Installation ==
0. Gallery plugin has to be installed as the pre-requisite condition. You can find Gallery Plugin here, http://wordpress.org/extend/plugins/gallery-plugin/
1. unzip the plugin
2. Upload plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. In administrator portal, click Settings->Gallery Plugin XMLRPC Interface to enable/disable the api functions.

Warning: Debug Error log is being appended into the WP_CONTENT_DIR . '/debug.log'; Make sure that in production environment, trun off the debug directive in wp-includes/default-constants.php  

== Frequently Asked Questions ==

= Exmaple to invoke the xmlrpc api in PHP=
//this example demo how to invoke the api to delete a image in the album
set_time_limit(0);
require_once("IXR_Library.php.inc");
 
$client->debug = true; // Set it to false in Production Environment
 
// Create the client object
$client = new IXR_Client('{zonename}','/xmlrpc.php?delete_images[]={imageID}');
 
 $username = "{admin login name}"; 
 $password = "{password}";
 
 $function_args = array(array('',$username,$password,'{postID}', array()));
 $params = array($username,$password,"i_gllrxmlrpc_editpost", $function_args); 
 
 // Run a query To Read Posts From Wordpress
 $ret = $client->query('gllrxmlrpc_extapi.callGllrMethod', $params);
 if (!$ret) {
 	die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
 }
 
 $myresponse = $client->getResponse();
 print_r($myresponse);

= Exmaple to invoke the xmlrpc api in java=
client = new XMLRPCClient(WordPress.currentBlog.getUrl(),
					WordPress.currentBlog.getHttpuser(),
					WordPress.currentBlog.getHttppassword());

Object[] result = null;
//
Map<String, String> gallery_type = new HashMap<String, String>();
gallery_type.put("post_type", "gallery");
//String[] return_fields = {"post_title", "post_type"};


Object[] fArgs = {"", WordPress.currentBlog.getUsername(),WordPress.currentBlog.getPassword(),gallery_type};
Object[] funcArgs = {fArgs};
//
Object[] params = { 
		WordPress.currentBlog.getUsername(),
		WordPress.currentBlog.getPassword(),
		"i_gllrxmlrpc_metaweblog_getposts",
		funcArgs };
try {
	result = (Object[]) client.call("gllrxmlrpc_extapi.callGllrMethod", params);
} catch (XMLRPCException e) {
	errorMsg = e.getMessage();
}

= Exmaple to invoke the delete post api in php=
<?php
set_time_limit(0);
require_once("IXR_Library.php.inc");
 
$client->debug = true; // Set it to fase in Production Environment
 
// Create the client object
$client = new IXR_Client('hushanqi.localhost','/xmlrpc.php?XDEBUG_SESSION_START=ECLIPSE_DBGP&KEY=13505726480152');
 
 $username = "{user name}"; 
 $password = "{password}";
 
 $function_args = array(array('',$username,$password,'99'));
  
 $params = array($username,$password,"i_gllrxmlrpc_deletepost", $function_args); 
 
 // Run a query To Read Posts From Wordpress
 if (!$client->query('gllrxmlrpc_extapi.callGllrMethod', $params)) {
 	die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
 }
 
 $myresponse = $client->getResponse();
 print_r($myresponse);
 die();
 ?>

== Changelog ==

= 0.3 =
* add new interface i_gllrxmlrpc_metaweblog_getPost
* new php example of delete post 

= 0.2 =
* add new interface i_gllrxmlrpc_metaweblog_getposts to be compliant with metaweblog standard for getposts api.
* bug fix in i_gllrxmlrpc_newpost
* new example in Java code

= 0.1 =
* initial version
