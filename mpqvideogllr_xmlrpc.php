<?php
/*
Plugin Name: MPQ Video Gallery XMLRPC
Plugin URI: 
Description: This plugin creates functions for MPQ Video Gallery Plugin which can be XMLRPC invoked remotely. Basically it creates XMLRPC interfaces for MPQ Video Gallery Plugin. Thanks for the author of Extended API, Michael Grosser,  since this plugin has reused its design. API functions (xmlrpc functions) are all prefixed with 'i_mpqvideogllrxmlrpc'. 
Author: Peidong Hu
Version: 0.3
Author URI: bigtester.com
*/

/*
 Copyright 2012 Peidong Hu @ Montreal Prot QA ( peidong@bigtester.com )
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
*/
//Check the WP version - Requires 3.3+
define("MPQ_VIDEO_GALLERY_XMLRPC_FOLDERNAME",     "mpqvideogallery-xmlrpc");
require_once ABSPATH ."wp-content/plugins/".MPQ_VIDEO_GALLERY_XMLRPC_FOLDERNAME."/mpqvideogllr_xmlrpc.php";
include_once(ABSPATH . WPINC . '/class-IXR.php');
global $wp_version;
$exit_msg = 'API Requires WordPress 3.3.1 or newer. You are currently running WordPress ' . $wp_version . '. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>';
if (version_compare($wp_version, "3.3", "<"))
{
    exit($exit_msg);
}


//edit post will reuse the existing wordpress xmlrpc call.

/* MetaWeblog API functions
 * specs on wherever Dave Winer wants them to be
*/

/**
 * Create a new MPQ Video Gallery post. Note: this function only handle MPQ Video Gallery post_type. For the other post_type, please use the wordpress embeded xmlrpc call
 *
 * The 'content_struct' argument must contain:
 *  - title
 *  - post_type
 
 *
 * Also, it can optionally contain:
 *  - description
 *  - mt_excerpt
 *  - mt_text_more
 *  - mt_keywords
 *  - mt_tb_ping_urls
 *  - categories 
 *  - wp_slug
 *  - wp_password
 *  - wp_page_parent_id
 *  - wp_page_order
 *  - wp_author_id
 *  - post_status | page_status - can be 'draft', 'private', 'publish', or 'pending'
 *  - mt_allow_comments - can be 'open' or 'closed'
 *  - mt_allow_pings - can be 'open' or 'closed'
 *  - date_created_gmt
 *  - dateCreated
 *
 *
 * @param array $args Method parameters. Contains:
 *  - blog_id
 *  - username
 *  - password
 *  - content_struct
 *  - publish
 * @return int
 */
function i_mpqvideogllrxmlrpc_newpost($args) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 4 ) )
	{
			return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );
	}

		mpqvideogllrxmlrpc_escape( $args );

		$blog_id        = (int) $args[0];
		$username       = $args[1];
		$password       = $args[2];
		$content_struct = $args[3];

		//if ( ! $user = $this->login( $username, $password ) )
			//return $this->error;

		//do_action( 'xmlrpc_call', 'wp.newPost' );

		unset( $content_struct['ID'] );

		return mpqvideogllrxmlrpc_insert_post( $user, $content_struct );
}


/**
 * Edit a post for any registered post type.
 *
 * The $content_struct parameter only needs to contain fields that
 * should be changed. All other fields will retain their existing values.
 *
 * @since 3.3.1
 *
 * @param array $args Method parameters. Contains:
 *  - int     $blog_id
 *  - string  $username
 *  - string  $password
 *  - int     $post_id
 *  - array   $content_struct
 * @return true on success
 */
function i_mpqvideogllrxmlrpc_editpost( $args ) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 5 ) )
		return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );

	mpqvideogllrxmlrpc_escape( $args );

	$blog_id        = (int) $args[0];
	$username       = $args[1];
	$password       = $args[2];
	$post_id        = (int) $args[3];
	$content_struct = $args[4];

	
	$post = get_post( $post_id, ARRAY_A );

	if ( empty( $post['ID'] ) )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );

	// convert the date field back to IXR form
	$post['post_date'] = mpqvideogllrxmlrpc_convert_date( $post['post_date'] );

	// ignore the existing GMT date if it is empty or a non-GMT date was supplied in $content_struct,
	// since _insert_post will ignore the non-GMT date if the GMT date is set
	if ( $post['post_date_gmt'] == '0000-00-00 00:00:00' || isset( $content_struct['post_date'] ) )
		unset( $post['post_date_gmt'] );
	else
		$post['post_date_gmt'] = mpqvideogllrxmlrpc_convert_date( $post['post_date_gmt'] );

	mpqvideogllrxmlrpc_escape( $post );
	$merged_content_struct = array_merge( $post, $content_struct );

	$retval = mpqvideogllrxmlrpc_insert_post( $user, $merged_content_struct );
	if ( $retval instanceof IXR_Error )
		return $retval;

	return true;
}


/**
 * Delete a post for any registered post type.
 *
 * @since 3.3.1
 *
 * @uses wp_delete_post()
 * @param array $args Method parameters. Contains:
 *  - int     $blog_id
 *  - string  $username
 *  - string  $password
 *  - int     $post_id
 * @return true on success
 */
function i_mpqvideogllrxmlrpc_deletepost( $args ) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 4 ) )
		return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );

	mpqvideogllrxmlrpc_escape( $args );

	$blog_id    = (int) $args[0];
	$username   = $args[1];
	$password   = $args[2];
	$post_id    = (int) $args[3];

	
	$post = wp_get_single_post( $post_id, ARRAY_A );
	if ( empty( $post['ID'] ) )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );

	$post_type = get_post_type_object( $post['post_type'] );
	if ( ! current_user_can( $post_type->cap->delete_post, $post_id ) )
		return new IXR_Error( 401, __( 'Sorry, you are not allowed to delete this post.' ) );

	$result = wp_delete_post( $post_id );

	if ( ! $result )
		return new IXR_Error( 500, __( 'The post cannot be deleted.' ) );

	return true;
}


/**
 * Uploads a file, following your settings.
 *
 * Adapted from a patch by Johann Richard.
 *
 * @link http://mycvs.org/archives/2004/06/30/file-upload-to-wordpress-in-ecto/
 *
 * @since 3.3.1
 *
 * @param array $args Method parameters.
 * @return array
 */
function i_mpqvideogllrxmlrpc_uploadvideo($args) {
	global $wpdb;

	$blog_ID     = (int) $args[0];
	$username  = $wpdb->escape($args[1]);
	$password   = $wpdb->escape($args[2]);
	$data        = $args[3];

	$name = sanitize_file_name( $data['name'] );
	$type = $data['type'];
	$bits = $data['bits'];

	logIO('O', '(MW) Received '.strlen($bits).' bytes');

	

	if ( !current_user_can('upload_files') ) {
		logIO('O', '(MW) User does not have upload_files capability');
		return  new IXR_Error(401, __('You are not allowed to upload files to this site.'));
	}

	
	$upload = mpqvideogllrxmlrpc_upload_bits($name, ABSPATH ."wp-content/plugins/".MPQ_VIDEO_GALLERY_FOLDERNAME."/upload/files", $bits, $type);
	
	if ( ! empty($upload['error']) ) {
		$errorString = sprintf(__('Could not write file %1$s (%2$s)'), $name, $upload['error']);
		logIO('O', '(MW) ' . $errorString);
		return new IXR_Error(500, $errorString);
	}
	
	return array( 'file' => $upload['name'], 'filename'=>$upload['file'], 'type' => $upload['type'] );
}


/**
 * Retrieve a post.
 *
 * @since 3.3.1
 *
 * The optional $fields parameter specifies what fields will be included
 * in the response array. This should be a list of field names. 'post_id' will
 * always be included in the response regardless of the value of $fields.
 *
 * Instead of, or in addition to, individual field names, conceptual group
 * names can be used to specify multiple fields. The available conceptual
 * groups are 'post' (all basic fields), 'taxonomies', 'custom_fields',
 * and 'enclosure'.
 *
 * @uses wp_get_single_post()
 * @param array $args Method parameters. Contains:
 *  - int     $blog_id this parameter is desperated, can always leave empty.
 *  - string  $username
 *  - string  $password
 *  - int	  $post_id
 *  - array   $fields optional
 * @return array contains (based on $fields parameter):
 *  - 'post_id'
 *  - 'post_title'
 *  - 'post_date'
 *  - 'post_date_gmt'
 *  - 'post_modified'
 *  - 'post_modified_gmt'
 *  - 'post_status'
 *  - 'post_type'
 *  - 'post_name'
 *  - 'post_author'
 *  - 'post_password'
 *  - 'post_excerpt'
 *  - 'post_content'
 *  - 'link'
 *  - 'comment_status'
 *  - 'ping_status'
 *  - 'sticky'
 *  - 'custom_fields'
 *  - 'terms'
 *  - 'categories'
 *  - 'tags'
 *  - 'enclosure'
 */
function i_mpqvideogllrxmlrpc_getpost( $args ) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 4 ) )
		return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );

	mpqvideogllrxmlrpc_escape( $args );

	$blog_id            = (int) $args[0];
	$username           = $args[1];
	$password           = $args[2];
	$post_id            = (int) $args[3];

	$fields = $args[4];
	
	$post = wp_get_single_post( $post_id, ARRAY_A );

	if ( empty( $post['ID'] ) )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );

	$post_type = get_post_type_object( $post['post_type'] );
	if ( ! current_user_can( $post_type->cap->edit_posts, $post_id ) )
		return new IXR_Error( 401, __( 'Sorry, you cannot edit this post.' ) );

	return mpqvideogllrxmlrpc_prepare_post( $post, $fields );
}

/**
 * Retrieve a post.
 *
 * @since 3.3.1
 *
 * The optional $fields parameter specifies what fields will be included
 * in the response array. This should be a list of field names. 'post_id' will
 * always be included in the response regardless of the value of $fields.
 *
 * Instead of, or in addition to, individual field names, conceptual group
 * names can be used to specify multiple fields. The available conceptual
 * groups are 'post' (all basic fields), 'taxonomies', 'custom_fields',
 * and 'enclosure'.
 *
 * @uses wp_get_single_post()
 * @param array $args Method parameters. Contains:
 *  - int     $blog_id this parameter is desperated, can always leave empty.
 *  - string  $username
 *  - string  $password
 *  - int	  $post_id
 * @return array contains (based on $fields parameter):
 *  - 'post_id'
 *  - 'post_title'
 *  - 'post_date'
 *  - 'post_date_gmt'
 *  - 'post_modified'
 *  - 'post_modified_gmt'
 *  - 'post_status'
 *  - 'post_type'
 *  - 'post_name'
 *  - 'post_author'
 *  - 'post_password'
 *  - 'post_excerpt'
 *  - 'post_content'
 *  - 'link'
 *  - 'comment_status'
 *  - 'ping_status'
 *  - 'sticky'
 *  - 'custom_fields'
 *  - 'terms'
 *  - 'categories'
 *  - 'tags'
 *  - 'enclosure'
 */
function i_mpqvideogllrxmlrpc_metaweblog_getpost( $args ) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 4 ) )
		return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );

	mpqvideogllrxmlrpc_escape( $args );

	$blog_id            = (int) $args[0];
	$username           = $args[1];
	$password           = $args[2];
	$post_id            = (int) $args[3];

	
	$post = wp_get_single_post( $post_id, ARRAY_A );

	if ( empty( $post['ID'] ) )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );

	$post_type = get_post_type_object( $post['post_type'] );
	if ( ! current_user_can( $post_type->cap->edit_posts, $post_id ) )
		return new IXR_Error( 401, __( 'Sorry, you cannot edit this post.' ) );

	return metaweblog_mpqvideogllrxmlrpc_prepare_post( $post );
}

/**
 * Retrieve posts.
 *
 * @since 3.3.1
 *
 * The optional $filter parameter modifies the query used to retrieve posts.
 * Accepted keys are 'post_type', 'post_status', 'number', 'offset',
 * 'orderby', and 'order'.
 *
 * The optional $fields parameter specifies what fields will be included
 * in the response array.
 *
 * @uses wp_get_recent_posts()
 * @see i_mpqvideogllrxmlrpc_getpost() for more on $fields
 * @see get_posts() for more on $filter values
 *
 * @param array $args Method parameters. Contains:
 *  - int     $blog_id
 *  - string  $username
 *  - string  $password
 *  - array   $filter optional
 *  - array   $fields optional
 * @return array contains a collection of posts.
 */
function i_mpqvideogllrxmlrpc_getposts( $args ) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 3 ) )
		return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );

	mpqvideogllrxmlrpc_escape( $args );

	$blog_id    = (int) $args[0];
	$username   = $args[1];
	$password   = $args[2];
	$filter     = isset( $args[3] ) ? $args[3] : array();

	$fields = $args[4];
	
	$query = array();

	if ( isset( $filter['post_type'] ) ) {
		$post_type = get_post_type_object( $filter['post_type'] );
		if ( ! ( (bool) $post_type ) )
			return new IXR_Error( 403, __( 'The post type specified is not valid' ) );

		if ( ! current_user_can( $post_type->cap->edit_posts ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit posts in this post type' ));

		$query['post_type'] = $filter['post_type'];
	}

	if ( isset( $filter['post_status'] ) )
		$query['post_status'] = $filter['post_status'];

	if ( isset( $filter['number'] ) )
		$query['numberposts'] = absint( $filter['number'] );

	if ( isset( $filter['offset'] ) )
		$query['offset'] = absint( $filter['offset'] );

	if ( isset( $filter['orderby'] ) ) {
		$query['orderby'] = $filter['orderby'];

		if ( isset( $filter['order'] ) )
			$query['order'] = $filter['order'];
	}

	$posts_list = wp_get_recent_posts( $query );

	if ( ! $posts_list )
		return array();

	// holds all the posts data
	$struct = array();

	foreach ( $posts_list as $post ) {
		$post_type = get_post_type_object( $post['post_type'] );
		if ( ! current_user_can( $post_type->cap->edit_posts, $post['ID'] ) )
			continue;

		$struct[] = mpqvideogllrxmlrpc_prepare_post( $post, $fields );
	}

	return $struct;
}

/**
 * Retrieve posts.
 *
 * @since 3.3.1
 *
 * The optional $filter parameter modifies the query used to retrieve posts.
 * Accepted keys are 'post_type', 'post_status', 'number', 'offset',
 * 'orderby', 'post_title', and 'order'.
 *
 * The optional $fields parameter specifies what fields will be included
 * in the response array.
 *
 * @uses wp_get_recent_posts()
 * @see i_mpqvideogllrxmlrpc_getpost() for more on $fields
 * @see get_posts() for more on $filter values
 *
 * @param array $args Method parameters. Contains:
 *  - int     $blog_id
 *  - string  $username
 *  - string  $password
 *  - array   $filter optional
 *  - array   $fields optional
 * @return array contains a collection of posts.
 */
function i_mpqvideogllrxmlrpc_metaweblog_getposts( $args ) {
	if ( ! mpqvideogllrxmlrpc_minimum_args( $args, 3 ) )
		return new IXR_Error( 400, __( 'Insufficient arguments passed to this XML-RPC method.' ) );

	mpqvideogllrxmlrpc_escape( $args );

	$blog_id    = (int) $args[0];
	$username   = $args[1];
	$password   = $args[2];
	$filter     = isset( $args[3] ) ? $args[3] : array();

	$fields = $args[4];

	$query = array();

	if ( isset( $filter['post_type'] ) ) {
		$post_type = get_post_type_object( $filter['post_type'] );
		if ( ! ( (bool) $post_type ) )
			return new IXR_Error( 403, __( 'The post type specified is not valid' ) );

		if ( ! current_user_can( $post_type->cap->edit_posts ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit posts in this post type' ));

		$query['post_type'] = $filter['post_type'];
	}

	if ( isset( $filter['post_status'] ) )
		$query['post_status'] = $filter['post_status'];

	if ( isset( $filter['number'] ) )
		$query['numberposts'] = absint( $filter['number'] );

	if ( isset( $filter['offset'] ) )
		$query['offset'] = absint( $filter['offset'] );
	
	if ( isset( $filter['post_title'] ) )
		$query['post_title'] =  $filter['post_title'] ;
	
	if ( isset( $filter['orderby'] ) ) {
		$query['orderby'] = $filter['orderby'];

		if ( isset( $filter['order'] ) )
			$query['order'] = $filter['order'];
	}

	$posts_list = wp_get_recent_posts( $query );

	if ( ! $posts_list )
		return array();

	// holds all the posts data
	$struct = array();

	foreach ( $posts_list as $post ) {
		$post_type = get_post_type_object( $post['post_type'] );
		if ( ! current_user_can( $post_type->cap->edit_posts, $post['ID'] ) )
			continue;

		$struct[] = metaweblog_mpqvideogllrxmlrpc_prepare_post( $post );
	}

	return $struct;
}
/**
 * Prepares post data for return in an XML-RPC object.
 *
 * @access protected
 *
 * @param array $post The unprepared post data
 * @param array $fields The subset of post type fields to return
 * @return array The prepared post data
 */
function mpqvideogllrxmlrpc_prepare_post( $post, $fields=array("post_id")) {
	
	if (!isset($fields) || empty($fields))
		$fields = array("post_id");
	// holds the data for this post. built up based on $fields
	$_post = array( 'post_id' => strval( $post['ID'] ) );

	// prepare common post fields
	$post_fields = array(
			'post_title'        => $post['post_title'],
			'post_date'         => mpqvideogllrxmlrpc_convert_date( $post['post_date'] ),
			'post_date_gmt'     => mpqvideogllrxmlrpc_convert_date_gmt( $post['post_date_gmt'], $post['post_date'] ),
			'post_modified'     => mpqvideogllrxmlrpc_convert_date( $post['post_modified'] ),
			'post_modified_gmt' => mpqvideogllrxmlrpc_convert_date_gmt( $post['post_modified_gmt'], $post['post_modified'] ),
			'post_status'       => $post['post_status'],
			'post_type'         => $post['post_type'],
			'post_name'         => $post['post_name'],
			'post_author'       => $post['post_author'],
			'post_password'     => $post['post_password'],
			'post_excerpt'      => $post['post_excerpt'],
			'post_content'      => $post['post_content'],
			'link'              => post_permalink( $post['ID'] ),
			'comment_status'    => $post['comment_status'],
			'ping_status'       => $post['ping_status'],
			'sticky'            => ( $post['post_type'] === 'post' && is_sticky( $post['ID'] ) ),
	);

	// Thumbnail
	$post_fields['post_thumbnail'] = array();
	$thumbnail_id = get_post_thumbnail_id( $post['ID'] );
	if ( $thumbnail_id ) {
		$thumbnail_size = current_theme_supports('post-thumbnail') ? 'post-thumbnail' : 'thumbnail';
		$post_fields['post_thumbnail'] = mpqvideogllrxmlrpc_prepare_media_item( get_post( $thumbnail_id ), $thumbnail_size );
	}

	// Consider future posts as published
	if ( $post_fields['post_status'] === 'future' )
		$post_fields['post_status'] = 'publish';

	// Fill in blank post format
	$post_fields['post_format'] = get_post_format( $post['ID'] );
	if ( empty( $post_fields['post_format'] ) )
		$post_fields['post_format'] = 'standard';

	// Merge requested $post_fields fields into $_post
	if ( in_array( 'post', $fields ) ) {
		$_post = array_merge( $_post, $post_fields );
	} else {
		$requested_fields = array_intersect_key( $post_fields, array_flip( $fields ) );
		$_post = array_merge( $_post, $requested_fields );
	}

	$all_taxonomy_fields = in_array( 'taxonomies', $fields );

	if ( $all_taxonomy_fields || in_array( 'terms', $fields ) ) {
		$post_type_taxonomies = get_object_taxonomies( $post['post_type'], 'names' );
		$terms = wp_get_object_terms( $post['ID'], $post_type_taxonomies );
		$_post['terms'] = array();
		foreach ( $terms as $term ) {
			$_post['terms'][] = mpqvideogllrxmlrpc_prepare_term( $term );
		}
	}

	if ( in_array( 'custom_fields', $fields ) )
		$_post['custom_fields'] = mpqvideogllrxmlrpc_get_custom_fields( $post['ID'] );

	if ( in_array( 'enclosure', $fields ) ) {
		$_post['enclosure'] = array();
		$enclosures = (array) get_post_meta( $post['ID'], 'enclosure' );
		if ( ! empty( $enclosures ) ) {
			$encdata = explode( "\n", $enclosures[0] );
			$_post['enclosure']['url'] = trim( htmlspecialchars( $encdata[0] ) );
			$_post['enclosure']['length'] = (int) trim( $encdata[1] );
			$_post['enclosure']['type'] = trim( $encdata[2] );
		}
	}

	//return apply_filters( 'xmlrpc_prepare_post', $_post, $post, $fields );
	return $_post;
}

/**
 * Prepares post data for return in an XML-RPC object.
 *
 * @access protected
 *
 * @param array $post The unprepared post data
 * @param array $fields The subset of post type fields to return
 * @return array The prepared post data
 */
function metaweblog_mpqvideogllrxmlrpc_prepare_post( $post) {
	
	/////////////////////////
	$entry = $post;
	//if ( !current_user_can( 'edit_post', $entry['ID'] ) )
	//	continue;

	$post_date = mysql2date('Ymd\TH:i:s', $entry['post_date'], false);
	$post_date_gmt = mysql2date('Ymd\TH:i:s', $entry['post_date_gmt'], false);

	// For drafts use the GMT version of the date
	if ( $entry['post_status'] == 'draft' )
		$post_date_gmt = get_gmt_from_date( mysql2date( 'Y-m-d H:i:s', $entry['post_date'] ), 'Ymd\TH:i:s' );

	$categories = array();
	$catids = wp_get_post_categories($entry['ID']);
	foreach( $catids as $catid )
		$categories[] = get_cat_name($catid);

	$tagnames = array();
	$tags = wp_get_post_tags( $entry['ID'] );
	if ( !empty( $tags ) ) {
		foreach ( $tags as $tag ) {
			$tagnames[] = $tag->name;
		}
		$tagnames = implode( ', ', $tagnames );
	} else {
		$tagnames = '';
	}

	$post = get_extended($entry['post_content']);
	$link = post_permalink($entry['ID']);

	// Get the post author info.
	$author = get_userdata($entry['post_author']);

	$allow_comments = ('open' == $entry['comment_status']) ? 1 : 0;
	$allow_pings = ('open' == $entry['ping_status']) ? 1 : 0;

	// Consider future posts as published
	if ( $entry['post_status'] === 'future' )
		$entry['post_status'] = 'publish';

	// Get post format
	$post_format = get_post_format( $entry['ID'] );
	if ( empty( $post_format ) )
		$post_format = 'standard';
	
	$attachments = get_posts(array(
			"showposts"			=> -1,
			"what_to_show"	=> "posts",
			"post_status"		=> "inherit",
			"post_type"			=> "attachment",
			"orderby"				=> "post_modified",
			"order"					=> "DESC",
			"post_parent"		=> $entry['ID']
	));
	
	$uploaddirs = wp_upload_dir();
	$uploadurl = $uploaddirs["baseurl"];
	if( count( $attachments ) > 0 ) {
		//$image_desc = "<br/>";
		foreach( $attachments as $attachment ) {
			$image_attributes = gllr_video_get_thumbimage_src( $attachment->ID, 'thumbnail' );
			$image_attributes_large = gllr_video_get_thumbimage_src( $attachment->ID, 'large' );
			
			$thumbnail_large[$attachment->ID]['thumbnail_attributes'] = $image_attributes;
			$thumbnail_large[$attachment->ID]['thumbnail_url'] = $image_attributes_large[0];
			$video_fullurl = $uploadurl."/".get_post_meta( $attachment->ID, "_wp_attached_file", true );
			$thumbnail_large[$attachment->ID]['video_url'] = $video_fullurl;
			$image_desc .= '<a href="'.$video_fullurl.'"><img class="alignnone" alt="image" src="'.$image_attributes_large[0].'" /></a>';
		}
	}
	
	/*$posts = get_posts(array(
			"showposts"			=> -1,
			"what_to_show"	=> "posts",
			"post_status"		=> "inherit",
			"post_type"			=> "attachment",
			"orderby"				=> $gllr_video_options['order_by'],
			"order"					=> $gllr_video_options['order'],
			"post_parent"		=> $post->ID
	));
	if( count( $posts ) > 0 ) {
		$playerroot = plugins_url().'/'.MPQ_VIDEO_GALLERY_FOLDERNAME.'/jwplayer/';
		$uploaddirs = wp_upload_dir();
		$uploadurl = $uploaddirs["baseurl"];
		$count_image_block = 0;
		foreach( $posts as $attachment ) {
			$key = "gllr_video_image_text";
			$link_key = "gllr_video_link_url";
			$image_attributes = gllr_video_get_thumbimage_src( $attachment->ID, 'photo-thumb' );
			$image_attributes_large = gllr_video_get_thumbimage_src( $attachment->ID, 'large' );
			$image_attributes_full = gllr_video_get_thumbimage_src( $attachment->ID, 'full' );
			if( ( $url_for_link = get_post_meta( $attachment->ID, $link_key, true ) ) != "" ) {
				$image_desc .= '<object id="player'.$attachment->ID.'" width="'.$gllr_video_options['gllr_video_custom_size_px'][1][0].'" height="'. $gllr_video_options['gllr_video_custom_size_px'][1][1].'" type="application/x-shockwave-flash" name="player'.$attachment->ID.'" data="'.plugins_url().'/'.MPQ_VIDEO_GALLERY_FOLDERNAME.'/jwplayer/'.
				'player.swf">
				<param name="allowfullscreen" value="true">
				<param name="allowscriptaccess" value="always">
				<param name="flashvars" value="'.$url_for_link.'&autostart=false&image='.$image_attributes[0].'">'.
				'</object>';
	
			} else {
	
	
				$image_desc .= '<object id="player'.$attachment->ID.'" width="'.$gllr_video_options['gllr_video_custom_size_px'][1][0].'" height="'.$gllr_video_options['gllr_video_custom_size_px'][1][1].'" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9.0.115" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'.
				'<param value="'.plugins_url().'/'.MPQ_VIDEO_GALLERY_FOLDERNAME.'/jwplayer/'.'player.swf" name="movie">'.
				'<param value="true" name="allowfullscreen">'.
				'<param value="always" name="allowscriptaccess">'.
				'<param value="file='.$uploadurl."/".get_post_meta( $attachment->ID, "_wp_attached_file", true ).'&fullscreen=true&controlbar=bottom&image='.$image_attributes[0].'" name="flashvars">'.
				'<embed width="'.$gllr_video_options['gllr_video_custom_size_px'][1][0].'" height="'.$gllr_video_options['gllr_video_custom_size_px'][1][1].'" flashvars="file='.$uploadurl."/".get_post_meta( $attachment->ID, "_wp_attached_file", true ).'&fullscreen=true&controlbar=bottom&image='.$image_attributes[0].'" allowscriptaccess="always" allowfullscreen="true" src="'.plugins_url().'/'.MPQ_VIDEO_GALLERY_FOLDERNAME.'/jwplayer/'.'player.swf" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" name="player'.$attachment->ID.'">'.
				'</object>';
			}
		}
	}*/
	
	
	$struct = array(
			'dateCreated' => new IXR_Date($post_date),
			'userid' => $entry['post_author'],
			'postid' => (string) $entry['ID'],
			'description' => $post['main'].$image_desc,
			'title' => $entry['post_title'],
			'link' => $link,
			'permaLink' => $link,
			// commented out because no other tool seems to use this
	// 'content' => $entry['post_content'],
			'categories' => $categories,
			'mt_excerpt' => $entry['post_excerpt'],
			'mt_text_more' => $post['extended'],
			'mt_allow_comments' => $allow_comments,
			'mt_allow_pings' => $allow_pings,
			'mt_keywords' => $tagnames,
			'wp_slug' => $entry['post_name'],
			'wp_password' => $entry['post_password'],
			'wp_author_id' => (string) $author->ID,
			'wp_author_display_name' => $author->display_name,
			'date_created_gmt' => new IXR_Date($post_date_gmt),
			'post_status' => $entry['post_status'],
			'custom_fields' => mpqvideogllrxmlrpc_get_custom_fields($entry['ID']),
			'wp_post_format' => $post_format,
			'post_type' => $entry['post_type'],
			'thumbnails' => $thumbnail_large
	);

	return $struct;
	
}
/**
 * Retrieve custom fields for post.
 *
 * @since 2.5.0
 *
 * @param int $post_id Post ID.
 * @return array Custom fields, if exist.
 */
function mpqvideogllrxmlrpc_get_custom_fields($post_id) {
	$post_id = (int) $post_id;

	$custom_fields = array();

	foreach ( (array) has_meta($post_id) as $meta ) {
		// Don't expose protected fields.
		if ( ! current_user_can( 'edit_post_meta', $post_id , $meta['meta_key'] ) )
			continue;

		$custom_fields[] = array(
				"id"    => $meta['meta_id'],
				"key"   => $meta['meta_key'],
				"value" => $meta['meta_value']
		);
	}

	return $custom_fields;
}

/**
 * Prepares term data for return in an XML-RPC object.
 *
 * @access protected
 *
 * @param array|object $term The unprepared term data
 * @return array The prepared term data
 */
 function mpqvideogllrxmlrpc_prepare_term( $term ) {
	$_term = $term;
	if ( ! is_array( $_term) )
		$_term = get_object_vars( $_term );

	// For Intergers which may be largeer than XMLRPC supports ensure we return strings.
	$_term['term_id'] = strval( $_term['term_id'] );
	$_term['term_group'] = strval( $_term['term_group'] );
	$_term['term_taxonomy_id'] = strval( $_term['term_taxonomy_id'] );
	$_term['parent'] = strval( $_term['parent'] );

	// Count we are happy to return as an Integer because people really shouldn't use Terms that much.
	$_term['count'] = intval( $_term['count'] );

	return $_term;
}
/**
 * Prepares media item data for return in an XML-RPC object.
 *
 * @access protected
 *
 * @param object $media_item The unprepared media item data
 * @param string $thumbnail_size The image size to use for the thumbnail URL
 * @return array The prepared media item data
 */
function mpqvideogllrxmlrpc_prepare_media_item( $media_item, $thumbnail_size = 'thumbnail' ) {
	$_media_item = array(
			'attachment_id'    => strval( $media_item->ID ),
			'date_created_gmt' => $this->_convert_date_gmt( $media_item->post_date_gmt, $media_item->post_date ),
			'parent'           => $media_item->post_parent,
			'link'             => wp_get_attachment_url( $media_item->ID ),
			'title'            => $media_item->post_title,
			'caption'          => $media_item->post_excerpt,
			'description'      => $media_item->post_content,
			'metadata'         => wp_get_attachment_metadata( $media_item->ID ),
	);

	$thumbnail_src = image_downsize( $media_item->ID, $thumbnail_size );
	if ( $thumbnail_src )
		$_media_item['thumbnail'] = $thumbnail_src[0];
	else
		$_media_item['thumbnail'] = $_media_item['link'];

	return $_media_item;
}
function mpqvideogllrxmlrpc_escape(&$array) {
	global $wpdb;

	if (!is_array($array)) {
		return($wpdb->escape($array));
	} else {
		foreach ( (array) $array as $k => $v ) {
			if ( is_array($v) ) {
				mpqvideogllrxmlrpc_escape($array[$k]);
			} else if ( is_object($v) ) {
				//skip
			} else {
				$array[$k] = $wpdb->escape($v);
			}
		}
	}
}

/**
 * Create a file in the upload folder with given content.
 *
 * If there is an error, then the key 'error' will exist with the error message.
 * If success, then the key 'file' will have the unique file path, the 'url' key
 * will have the link to the new file. and the 'error' key will be set to false.
 *
 * This function will not move an uploaded file to the upload folder. It will
 * create a new file with the content in $bits parameter. If you move the upload
 * file, read the content of the uploaded file, and then you can give the
 * filename and content to this function, which will add it to the upload
 * folder.
 *
 * The permissions will be set on the new file automatically by this function.
 *
 * @since 2.0.0
 *
 * @param string $name
 * @param null $deprecated Never used. Set to null.
 * @param mixed $bits File content
 * @param string $time Optional. Time formatted in 'yyyy/mm'.
 * @return array
 */
function mpqvideogllrxmlrpc_upload_bits( $name, $path, $bits, $type, $time = null ) {
	
	if ( empty( $name ) )
		return array( 'error' => __( 'Empty filename' ) );

	$wp_filetype = wp_check_filetype( $name );
	if ( !$wp_filetype['ext'] )
		return array( 'error' => __( 'Invalid file type' ) );

	$upload['path'] = $path;
	$upload['error'] = false;
	
		$upload_bits_error = apply_filters( 'wp_upload_bits', array( 'name' => $name, 'bits' => $bits, 'time' => $time ) );
	if ( !is_array( $upload_bits_error ) ) {
		$upload[ 'error' ] = $upload_bits_error;
		return $upload;
	}

	$filename = wp_unique_filename( $upload['path'], $name );

	$new_file = $upload['path'] . "/$filename";
	if ( ! wp_mkdir_p( dirname( $new_file ) ) ) {
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), dirname( $new_file ) );
		return array( 'error' => $message );
	}

	$ifp = @ fopen( $new_file, 'wb' );
	if ( ! $ifp )
		return array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) );

	@fwrite( $ifp, $bits );
	fclose( $ifp );
	clearstatcache();

	// Set correct file permissions
	$stat = @ stat( dirname( $new_file ) );
	$perms = $stat['mode'] & 0007777;
	$perms = $perms & 0000666;
	@ chmod( $new_file, $perms );
	clearstatcache();
	require_once ABSPATH ."wp-content/plugins/".MPQ_VIDEO_GALLERY_FOLDERNAME."/lib/gllr_video_processor.php";
	$convered_f = convert2flv( $upload['path'],  $filename);
	if ($convered_f)
	{
		$new_file = $convered_f;
		$name = $name.".flv";
		$wp_filetype = wp_check_filetype( $name );
		$type = $wp_filetype['type'];
	}
	// Compute the URL
	//$url = $upload['url'] . "/$filename";

	return array( 'file' => $new_file, 'name' => $name, 'type' =>$type, 'error' => false );
}


function mpqvideogllrxmlrpc_convert_date( $date ) {
	if ( $date === '0000-00-00 00:00:00' ) {
			return new IXR_Date( '00000000T00:00:00Z' );
		}
		return new IXR_Date( mysql2date( 'Ymd\TH:i:s', $date, false ) );
}
/**
 * Convert a WordPress GMT date string to an IXR_Date object.
 *
 * @access protected
 *
 * @param string $date_gmt
 * @param string $date
 * @return IXR_Date
 */
function mpqvideogllrxmlrpc_convert_date_gmt( $date_gmt, $date ) {
	if ( $date !== '0000-00-00 00:00:00' && $date_gmt === '0000-00-00 00:00:00' ) {
		return new IXR_Date( get_gmt_from_date( mysql2date( 'Y-m-d H:i:s', $date, false ), 'Ymd\TH:i:s' ) );
	}
	return mpqvideogllrxmlrpc_convert_date( $date_gmt );
}

function mpqvideogllrxmlrpc_minimum_args( $args, $count ) {
	if ( count( $args ) < $count ) {
		return false;
	}

	return true;
}

function mpqvideogllrxmlrpc_insert_post( $user, $content_struct ) {
	$defaults = array( 'post_status' => 'draft', 'post_type' => 'post', 'post_author' => 0,
			'post_password' => '', 'post_excerpt' => '', 'post_content' => '', 'post_title' => '' );

	$post_data = wp_parse_args( $content_struct, $defaults );

	$post_type = get_post_type_object( $post_data['post_type'] );
	if ( ! $post_type )
		return new IXR_Error( 403, __( 'Invalid post type' ) );

	$update = ! empty( $post_data['ID'] );

	if ( $update ) {
		if ( ! get_post( $post_data['ID'] ) )
			return new IXR_Error( 401, __( 'Invalid post ID.' ) );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_data['ID'] ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );
		if ( $post_data['post_type'] != get_post_type( $post_data['ID'] ) )
			return new IXR_Error( 401, __( 'The post type may not be changed.' ) );
	} else {
		if ( ! current_user_can( $post_type->cap->edit_posts ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to post on this site.' ) );
	}

	switch ( $post_data['post_status'] ) {
		case 'draft':
		case 'pending':
			break;
		case 'private':
			if ( ! current_user_can( $post_type->cap->publish_posts ) )
				return new IXR_Error( 401, __( 'Sorry, you are not allowed to create private posts in this post type' ) );
			break;
		case 'publish':
		case 'future':
			if ( ! current_user_can( $post_type->cap->publish_posts ) )
				return new IXR_Error( 401, __( 'Sorry, you are not allowed to publish posts in this post type' ) );
			break;
		default:
			$post_data['post_status'] = 'draft';
		break;
	}

	if ( ! empty( $post_data['post_password'] ) && ! current_user_can( $post_type->cap->publish_posts ) )
		return new IXR_Error( 401, __( 'Sorry, you are not allowed to create password protected posts in this post type' ) );

	$post_data['post_author'] = absint( $post_data['post_author'] );
	if ( ! empty( $post_data['post_author'] ) && $post_data['post_author'] != $user->ID ) {
		if ( ! current_user_can( $post_type->cap->edit_others_posts ) )
			return new IXR_Error( 401, __( 'You are not allowed to create posts as this user.' ) );

		$author = get_userdata( $post_data['post_author'] );

		if ( ! $author )
			return new IXR_Error( 404, __( 'Invalid author ID.' ) );
	} else {
		$post_data['post_author'] = $user->ID;
	}

	if ( isset( $post_data['comment_status'] ) && $post_data['comment_status'] != 'open' && $post_data['comment_status'] != 'closed' )
		unset( $post_data['comment_status'] );

	if ( isset( $post_data['ping_status'] ) && $post_data['ping_status'] != 'open' && $post_data['ping_status'] != 'closed' )
		unset( $post_data['ping_status'] );

	// Do some timestamp voodoo
	if ( ! empty( $post_data['post_date_gmt'] ) ) {
		// We know this is supposed to be GMT, so we're going to slap that Z on there by force
		$dateCreated = rtrim( $post_data['post_date_gmt']->getIso(), 'Z' ) . 'Z';
	} elseif ( ! empty( $post_data['post_date'] ) ) {
		$dateCreated = $post_data['post_date']->getIso();
	}

	if ( ! empty( $dateCreated ) ) {
		$post_data['post_date'] = get_date_from_gmt( iso8601_to_datetime( $dateCreated ) );
		$post_data['post_date_gmt'] = iso8601_to_datetime( $dateCreated, 'GMT' );
	}

	if ( ! isset( $post_data['ID'] ) )
		$post_data['ID'] = get_default_post_to_edit( $post_data['post_type'], true )->ID;
	$post_ID = $post_data['ID'];

	if ( $post_data['post_type'] == 'post' ) {
		// Private and password-protected posts cannot be stickied.
		if ( $post_data['post_status'] == 'private' || ! empty( $post_data['post_password'] ) ) {
			// Error if the client tried to stick the post, otherwise, silently unstick.
			if ( ! empty( $post_data['sticky'] ) )
				return new IXR_Error( 401, __( 'Sorry, you cannot stick a private post.' ) );
			if ( $update )
				unstick_post( $post_ID );
		} elseif ( isset( $post_data['sticky'] ) )  {
			if ( ! current_user_can( $post_type->cap->edit_others_posts ) )
				return new IXR_Error( 401, __( 'Sorry, you are not allowed to stick this post.' ) );
			if ( $post_data['sticky'] )
				stick_post( $post_ID );
			else
				unstick_post( $post_ID );
		}
	}

	if ( isset( $post_data['post_thumbnail'] ) ) {
		// empty value deletes, non-empty value adds/updates
		if ( ! $post_data['post_thumbnail'] )
			delete_post_thumbnail( $post_ID );
		elseif ( ! set_post_thumbnail( $post_ID, $post_data['post_thumbnail'] ) )
		return new IXR_Error( 404, __( 'Invalid attachment ID.' ) );
		unset( $content_struct['post_thumbnail'] );
	}

	if ( isset( $post_data['custom_fields'] ) )
		mpqvideogllrxmlrpc_set_custom_fields( $post_ID, $post_data['custom_fields'] );

	if ( isset( $post_data['terms'] ) || isset( $post_data['terms_names'] ) ) {
		$post_type_taxonomies = get_object_taxonomies( $post_data['post_type'], 'objects' );

		// accumulate term IDs from terms and terms_names
		$terms = array();

		// first validate the terms specified by ID
		if ( isset( $post_data['terms'] ) && is_array( $post_data['terms'] ) ) {
			$taxonomies = array_keys( $post_data['terms'] );

			// validating term ids
			foreach ( $taxonomies as $taxonomy ) {
				if ( ! array_key_exists( $taxonomy , $post_type_taxonomies ) )
					return new IXR_Error( 401, __( 'Sorry, one of the given taxonomies is not supported by the post type.' ) );

				if ( ! current_user_can( $post_type_taxonomies[$taxonomy]->cap->assign_terms ) )
					return new IXR_Error( 401, __( 'Sorry, you are not allowed to assign a term to one of the given taxonomies.' ) );

				$term_ids = $post_data['terms'][$taxonomy];
				foreach ( $term_ids as $term_id ) {
					$term = get_term_by( 'id', $term_id, $taxonomy );

					if ( ! $term )
						return new IXR_Error( 403, __( 'Invalid term ID' ) );

					$terms[$taxonomy][] = (int) $term_id;
				}
			}
		}

		// now validate terms specified by name
		if ( isset( $post_data['terms_names'] ) && is_array( $post_data['terms_names'] ) ) {
			$taxonomies = array_keys( $post_data['terms_names'] );

			foreach ( $taxonomies as $taxonomy ) {
				if ( ! array_key_exists( $taxonomy , $post_type_taxonomies ) )
					return new IXR_Error( 401, __( 'Sorry, one of the given taxonomies is not supported by the post type.' ) );

				if ( ! current_user_can( $post_type_taxonomies[$taxonomy]->cap->assign_terms ) )
					return new IXR_Error( 401, __( 'Sorry, you are not allowed to assign a term to one of the given taxonomies.' ) );

				// for hierarchical taxonomies, we can't assign a term when multiple terms in the hierarchy share the same name
				$ambiguous_terms = array();
				if ( is_taxonomy_hierarchical( $taxonomy ) ) {
					$tax_term_names = get_terms( $taxonomy, array( 'fields' => 'names', 'hide_empty' => false ) );

					// count the number of terms with the same name
					$tax_term_names_count = array_count_values( $tax_term_names );

					// filter out non-ambiguous term names
					$ambiguous_tax_term_counts = array_filter( $tax_term_names_count, 'mpqvideogllrxmlrpc__is_greater_than_one' );

					$ambiguous_terms = array_keys( $ambiguous_tax_term_counts );
				}

				$term_names = $post_data['terms_names'][$taxonomy];
				foreach ( $term_names as $term_name ) {
					if ( in_array( $term_name, $ambiguous_terms ) )
						return new IXR_Error( 401, __( 'Ambiguous term name used in a hierarchical taxonomy. Please use term ID instead.' ) );

					$term = get_term_by( 'name', $term_name, $taxonomy );

					if ( ! $term ) {
						// term doesn't exist, so check that the user is allowed to create new terms
						if ( ! current_user_can( $post_type_taxonomies[$taxonomy]->cap->edit_terms ) )
							return new IXR_Error( 401, __( 'Sorry, you are not allowed to add a term to one of the given taxonomies.' ) );

						// create the new term
						$term_info = wp_insert_term( $term_name, $taxonomy );
						if ( is_wp_error( $term_info ) )
							return new IXR_Error( 500, $term_info->get_error_message() );

						$terms[$taxonomy][] = (int) $term_info['term_id'];
					} else {
						$terms[$taxonomy][] = (int) $term->term_id;
					}
				}
			}
		}

		$post_data['tax_input'] = $terms;
		unset( $post_data['terms'], $post_data['terms_names'] );
	} else {
		// do not allow direct submission of 'tax_input', clients must use 'terms' and/or 'terms_names'
		unset( $post_data['tax_input'], $post_data['post_category'], $post_data['tags_input'] );
	}

	if ( isset( $post_data['post_format'] ) ) {
		$format = set_post_format( $post_ID, $post_data['post_format'] );

		if ( is_wp_error( $format ) )
			return new IXR_Error( 500, $format->get_error_message() );

		unset( $post_data['post_format'] );
	}

	// Handle enclosures
	$enclosure = isset( $post_data['enclosure'] ) ? $post_data['enclosure'] : null;
	mpqvideogllrxmlrpc_add_enclosure_if_new( $post_ID, $enclosure );

	mpqvideogllrxmlrpc_attach_uploads( $post_ID, $post_data['post_content'] );

	//$post_data = apply_filters( 'xmlrpc_wp_insert_post_data', $post_data, $content_struct );
	//remove the img tags in post_content field.
	$post_data['post_content'] = preg_replace("/<img[^>]+\>/i", "", $post_data['post_content']);
	//
	$post_ID = wp_insert_post( $post_data, true );
	if ( is_wp_error( $post_ID ) )
		return new IXR_Error( 500, $post_ID->get_error_message() );

	if ( ! $post_ID )
		return new IXR_Error( 401, __( 'Sorry, your entry could not be posted. Something wrong happened.' ) );

	return strval( $post_ID );
}

function  mpqvideogllrxmlrpc__is_greater_than_one( $count ) {
	return $count > 1;
}

/**
 * Set custom fields for post.
 *
 * @since 2.5.0
 *
 * @param int $post_id Post ID.
 * @param array $fields Custom fields.
 */
function mpqvideogllrxmlrpc_set_custom_fields($post_id, $fields) {
	$post_id = (int) $post_id;

	foreach ( (array) $fields as $meta ) {
		if ( isset($meta['id']) ) {
			$meta['id'] = (int) $meta['id'];
			$pmeta = get_metadata_by_mid( 'post', $meta['id'] );
			if ( isset($meta['key']) ) {
				$meta['key'] = stripslashes( $meta['key'] );
				if ( $meta['key'] != $pmeta->meta_key )
					continue;
				$meta['value'] = stripslashes_deep( $meta['value'] );
				if ( current_user_can( 'edit_post_meta', $post_id, $meta['key'] ) )
					update_metadata_by_mid( 'post', $meta['id'], $meta['value'] );
			} elseif ( current_user_can( 'delete_post_meta', $post_id, $pmeta->meta_key ) ) {
				delete_metadata_by_mid( 'post', $meta['id'] );
			}
		} elseif ( current_user_can( 'add_post_meta', $post_id, stripslashes( $meta['key'] ) ) ) {
			add_post_meta( $post_id, $meta['key'], $meta['value'] );
		}
	}
}

function mpqvideogllrxmlrpc_attach_uploads( $post_ID, $post_content ) {
	global $wpdb;

	// find any unattached files
	$attachments = $wpdb->get_results( "SELECT ID, guid FROM {$wpdb->posts} WHERE post_parent = '0' AND post_type = 'attachment'" );
	if ( is_array( $attachments ) ) {
		foreach ( $attachments as $file ) {
			if ( !empty($post_content) && !empty($file->guid) && strpos( $post_content, $file->guid ) !== false )
				$wpdb->update($wpdb->posts, array('post_parent' => $post_ID), array('ID' => $file->ID) );
		}
	}
}

function mpqvideogllrxmlrpc_add_enclosure_if_new($post_ID, $enclosure) {
	if ( is_array( $enclosure ) && isset( $enclosure['url'] ) && isset( $enclosure['length'] ) && isset( $enclosure['type'] ) ) {

		$encstring = $enclosure['url'] . "\n" . $enclosure['length'] . "\n" . $enclosure['type'];
		$found = false;
		foreach ( (array) get_post_custom($post_ID) as $key => $val) {
			if ($key == 'enclosure') {
				foreach ( (array) $val as $enc ) {
					if ($enc == $encstring) {
						$found = true;
						break 2;
					}
				}
			}
		}
		if (!$found)
			add_post_meta( $post_ID, 'enclosure', $encstring );
	}
}


//for fix the bug in function mpqvideogllr_save_postdata. the global $post is not initialized in xmlrpc call context. it will override the value of the function parameter $post.
//this function will be hooked to save_post action. 
//the image parameter name is change from 'undefined' to 'mpqvideogllrxmlrpc_images' for the xmlrpc calls. 
//the web UI image upload still use the 'undefined' url parameter.
add_action( 'save_post', 'mpqvideogllrxmlrpc_mpqvideogllr_save_images', 1, 2 ); // save custom data from admin
 
if ( ! function_exists ( 'mpqvideogllrxmlrpc_mpqvideogllr_save_images' ) ) {
	function mpqvideogllrxmlrpc_mpqvideogllr_save_images( $post_id, $post ) {
		global $wpdb;
		$key = "mpqvideogllr_image_text";

		if( isset( $_REQUEST['mpqvideogllrxmlrpc_images'] ) && ! empty( $_REQUEST['mpqvideogllrxmlrpc_images'] ) ) {
			$array_file_name = $_REQUEST['mpqvideogllrxmlrpc_images'];
			$uploadFile = array();
			$newthumb = array();
			$time = current_time('mysql');

			$uploadDir =  wp_upload_dir( $time );

			while( list( $key, $val ) = each( $array_file_name ) ) {
				$imagename = $val;
				$uploadFile[] = $uploadDir["path"] ."/" . $imagename;
			}
			reset( $array_file_name );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			while( list( $key, $val ) = each( $array_file_name ) ) {
				$file_name = $val;
				if ( copy ( ABSPATH ."wp-content/plugins/".MPQ_VIDEO_GALLERY_FOLDERNAME."/upload/files/".$file_name, $uploadFile[$key] ) ) {
					unlink( ABSPATH ."wp-content/plugins/".MPQ_VIDEO_GALLERY_FOLDERNAME."/upload/files/".$file_name );
					$overrides = array('test_form' => false );

					$file = $uploadFile[$key];
					$filename = basename( $file );

					$wp_filetype	= wp_check_filetype( $filename, null );
					$attachment		= array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => $filename,
							'post_content' => '',
							'post_status' => 'inherit'
					);
					$attach_id = wp_insert_attachment( $attachment, $file );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_parent = %d WHERE ID = %d", $post->ID, $attach_id ) );
				}
			}
		}
	}
}

//////////////above phu created

//Add a filter for XML RPC Methods
add_filter( 'xmlrpc_methods', 'createMpqVideoGllrXmlRpcMethods' );

/**
 * Generate the Response
 *
 * @param methods Array - list of existing XMLRPC methods
 * @return methods Array - list of updated XMLRPC methods
 */
function createMpqVideoGllrXmlRpcMethods($methods)
{
    $functions = get_defined_functions();

    $wp_functions = $functions['user'];
    $methods[get_option('mpqvideogllrxmlrpc_namespace') . '.callMpqVideoGllrMethod'] = 'mpqvideogllrxmlrpc_wpext_response';
    return $methods;
}

/**
 * Generate the Response
 *
 * @param Array (username, password, wp method name, arguments for method)
 * @return Mixed (response from WP method)
 */
function mpqvideogllrxmlrpc_wpext_response($params)
{
    //Separate Params from Request
    $username = $params[0];
    $password = $params[1];
    $method   = $params[2];
    $args     = $params[3];

    // List of Allowed WP Functions
    $mpqvideogllrxmlrpc_allowed_functions = get_option('mpqvideogllrxmlrpc_allowed_functions');
	
    global $wp_xmlrpc_server;
    // Let's run a check to see if credentials are okay
    if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
            return $wp_xmlrpc_server->error;
    }    

    if (function_exists($method) && in_array($method, $mpqvideogllrxmlrpc_allowed_functions))
    {
        try
        {
            if (!empty($args))
                return call_user_func_array($method,$args);
        } catch (Exception $e) {
            return new IXR_Error( 401, __( 'This is not working.' ) );
        }
        
    } else {
	return new IXR_Error( 401, __( 'Sorry, the method ' . $method . ' does not exist or is not allowed.' ) );
    }
}

/*
 * Add a Settings page for this Plugin.
 */
add_action('admin_menu', 'extapi_mpqvideogllrxmlrpc_create_menu');
function extapi_mpqvideogllrxmlrpc_create_menu()
{
    add_options_page( 'Settings for MPQ Video Gallery Plugin XMLRPC', 'MPQ Video Gallery XMLRPC', 'administrator', 'mpqvideogllrxmlrpc_extapisettings', 'extapi_mpqvideogllrxmlrpc_settings_page');
}

/*
 * Register the custom options for this plugin.
 */
add_action( 'admin_init', 'extapi_mpqvideogllrxmlrpc_register_settings' );
function extapi_mpqvideogllrxmlrpc_register_settings()
{
    //register settings
	register_setting( 'mpqvideogllrxmlrpc_extapi_settings', 'mpqvideogllrxmlrpc_extapi_installed');
    register_setting( 'mpqvideogllrxmlrpc_extapi_settings', 'mpqvideogllrxmlrpc_allowed_functions' );
    register_setting( 'mpqvideogllrxmlrpc_extapi_settings', 'mpqvideogllrxmlrpc_namespace', 'mpqvideogllrxmlrpc_validate_namespace' );
}

/*
 * If the user deletes the namespace, set it back to the default.
 */
function mpqvideogllrxmlrpc_validate_namespace($input)
{
    $input = trim($input);
	if (empty($input))
            $input = 'mpqvideogllrxmlrpc_extapi';
        
	return $input;
}


/*
 * Function to display the settings page.
 */
function extapi_mpqvideogllrxmlrpc_settings_page()
{
	global $mpqvideogllrxmlrpc_extapi_available_functions;
    include('mpqvideogllrxmlrpc_settings_page.php');
}

/**
 * Run this when the plugin is activated. This will make sure options
 * are setup.
 */
register_activation_hook(__FILE__,'extapi_mpqvideogllrxmlrpc_install');
function extapi_mpqvideogllrxmlrpc_install()
{
    //Make sure settings are registered
    extapi_mpqvideogllrxmlrpc_register_settings();	
}

/** Check and see if we need to run setup. We're doing this
 *  here instead of the register_activation_hook to ensure two things:
 *  1. WordPress is fully loaded.
 *  2. If the plugin was activated in schema.php, it doesn't call the hook
 */
add_action('wp_loaded', 'mpqvideogllrxmlrpc_verify_install');
function mpqvideogllrxmlrpc_verify_install()
{
	if (!get_option('mpqvideogllrxmlrpc_extapi_installed'))
	{
		mpqvideogllrxmlrpc_setup_options();
	}
}

function mpqvideogllrxmlrpc_setup_options()
{
    //Setup Default Namespace
    $namespace = get_option('mpqvideogllrxmlrpc_namespace');
    if (empty($namespace))
        update_option('mpqvideogllrxmlrpc_namespace','mpqvideogllrxmlrpc_extapi');

    //Setup Default Allowed Functions
    $mpqvideogllrxmlrpc_allowed_functions = get_option('mpqvideogllrxmlrpc_allowed_functions');
    if (empty($mpqvideogllrxmlrpc_allowed_functions))
    {
        $mpqvideogllrxmlrpc_allowed_functions = array();
        $functions = get_defined_functions();
        foreach ($functions['user'] as $function)
        {
            $mpqvideogllrxmlrpc_allowed_functions[] = $function;
        }
        update_option('mpqvideogllrxmlrpc_allowed_functions',$mpqvideogllrxmlrpc_allowed_functions);
    }
	
	update_option('mpqvideogllrxmlrpc_extapi_installed',true);
}

function mpqvideogllrxmlrpc_logError($data)
{
	if (is_object($data) || is_array($data))
		$data = print_r($data, true);

	$data .= "\r\n";
	//NOTE, this is the wordpress standard error logging file. errors will be appended into this file.
	$log_file = WP_CONTENT_DIR . '/debug.log';

	$fh = debug_fopen($log_file, "a+");
	debug_fwrite($fh,$data);
	debug_fclose($fh);
}

// we need to set the defined functions here
// since others will be available in the template that we don't want.
$mpqvideogllrxmlrpc_defined_functions = get_defined_functions();
//print_r($mpqvideogllrxmlrpc_defined_functions['user']);
foreach ($mpqvideogllrxmlrpc_defined_functions['user'] as $func)
{
	if (strpos($func,'i_mpqvideogllrxmlrpc')===0)
	{
		$mpqvideogllrxmlrpc_extapi_available_functions[] = $func;
	}
}