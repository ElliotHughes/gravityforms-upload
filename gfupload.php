<?php
/*
Plugin Name: Gravity Forms 上传文件
Description: 上传文件
Version: 0.0.1
Author: GFTL
*/
defined( 'ABSPATH' ) || die();

/**
 * Path to PPCP root folder.
 *
 * @since 2.0
 */
define( 'GF_POST_UPLOAD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// After Gravity Forms is loaded, load the Add-On.
add_action( 'gform_loaded', array( 'GF_POST_UPLOAD_Bootstrap', 'load_addon' ), 5 );

class GF_POST_UPLOAD_Bootstrap
{

	/**
	 * Loads the required files.
	 *
	 * @since  1.0
	 */
	public static function load_addon()
	{
		require_once GF_POST_UPLOAD_PLUGIN_PATH . 'class-gf-post-upload.php';
		GFAddOn::register( 'GF_POST_UPLOAD' );
	}

}

/**
 * Returns an instance of the WP_POST class
 *
 * @since  1.0
 *
 * @return GF_POST_UPLOAD|bool An instance of the WP_POST_FIELD class
 */
function gf_post_upload()
{
	return class_exists( 'GF_POST_UPLOAD' ) ? WP_POST_FIELD::get_instance() : false;
}
