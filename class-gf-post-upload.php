<?php

defined( 'ABSPATH' ) || die();

GFForms::include_payment_addon_framework();

/**
 * Gravity Forms Gravity Forms UPLOAD FILE
 *
 * @since     1.0
 * @package   GravityForms
 * @author    2
 * @copyright Copyright (c) 2019, 2
 */
class GF_POST_UPLOAD extends GFAddOn {
	
	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @var    WP_POST_FIELD $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;
	
	/**
	 * Defines the plugin slug.
	 *                                                       
	 * @since  1.0
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformspostupload';


	public function init()
	{
		parent::init();
		add_action('wp_ajax_gf_post_upload', array($this, 'gfpost_ajax_upload'));
		add_action('wp_ajax_nopriv_gf_post_upload', array( $this, 'gfpost_ajax_upload' ) );
		add_action('gform_enqueue_scripts', array( $this, 'custom_gf_post_upload_enqueue_scripts' ), 10, 2 );
	}

	public function custom_gf_post_upload_enqueue_scripts($form)
	{
		$wppost_fields = GFAPI::get_fields_by_type( $form, array( 'post_upload_input' ) );
		if ( empty ( $wppost_fields ) ) {
			return;
		}
		wp_enqueue_script('custom_gf_post_upload_enqueue_scripts', plugins_url('/js/gfpost-upload.js', __FILE__), array(), time() . '', true);

		wp_localize_script(
			'custom_gf_post_upload_enqueue_scripts',
			'ajaxurl',
			[admin_url( 'admin-ajax.php' )]
		);
	}


	/**
	 * Configure the survey results page.
	 *
	 * @return array
	 */
	public function get_results_page_config() {
		return array(
			'title'        => 'GFPOST上传文件',
			'capabilities' => array( 'gravityforms_wppost_gf_results' ),
			'callbacks'    => array(
				'fields'      => array( $this, 'results_fields' ),
				'filters'     => array( $this, 'results_filters' )
			)
		);
	}

	/**
	 * Update the results page filters depending on how the grading for this form has been configured.
	 *
	 * @param array $filters The current filters.
	 * @param array $form The current form.
	 *
	 * @return array
	 */
	public function results_filters( $filters, $form ) {
		$unwanted_filters = array( 'post_upload_input' );
		if ( empty( $unwanted_filters ) ) {
			return $filters;
		}

		foreach ( $filters as $key => $filter ) {
			if ( in_array( $filter['key'], $unwanted_filters ) ) {
				unset( $filters[ $key ] );
			}
		}

		return $filters;
	}

	/**
	 * Get all the quiz fields for the current form.
	 *
	 * @param array $form The current form object.
	 *
	 * @return GF_Field[]
	 */
	public function results_fields( $form ) {
		return GFAPI::get_fields_by_type( $form, array( 'gravityformswpfield' ) );
	}

    /**
	 * Register AJAX callbacks.
	 *
	 * @since  1.0
	 */
	public function init_ajax()
	{
		parent::init_ajax();
		// ajax的url是：admin_url('admin-ajax.php?action=gf_post_upload')
	}

	/**
	 * AJAX callback for uploading a file.
	 *
	 * @since  1.0
	 */
	public function gfpost_ajax_upload()
	{
		$form_id = rgpost('form_id');
		$field_id = rgpost('field_id');
		$entry_id = rgpost('entry_id');
		$nonce = rgpost('nonce');

		// 获取form-data上传的文件
		$file = $_FILES['gfpost_file'];		
		// 校验
		// if (!wp_verify_nonce($nonce, 'gf_post_upload')) {
		// 	wp_send_json_error(array('message' => __('Invalid nonce.', 'gravityformspostupload')));
		// }

		// if (!current_user_can('gravityforms_edit_entries')) {
		// 	wp_send_json_error(array('message' => __('You do not have permission to upload files.', 'gravityformspostupload')));
		// }

		// $entry = GFAPI::get_entry($entry_id);
		// if (is_wp_error($entry)) {
		// 	wp_send_json_error(array('message' => __('Entry not found.', 'gravityformspostupload')));
		// }

		// $form = GFAPI::get_form($form_id);
		// if (is_wp_error($form)) {
		// 	wp_send_json_error(array('message' => __('Form not found.', 'gravityformspostupload')));
		// }

		// $field = GFAPI::get_field($form, $field_id);
		// if (is_wp_error($field)) {
		// 	wp_send_json_error(array('message' => __('Field not found.', 'gravityformspostupload')));
		// }

		$result = $this->upload_file($form_id, $file);

		wp_send_json_success($result);

		return true;
	}

	public function upload_file( $form_id, $file ) {
		GFCommon::log_debug( __METHOD__ . '(): Uploading file: ' . $file['name'] );
		$target = GFFormsModel::get_file_upload_path( $form_id, $file['name'] );
		if ( ! $target ) {
			GFCommon::log_debug( __METHOD__ . '(): FAILED (Upload folder could not be created.)' );
		}
		GFCommon::log_debug( __METHOD__ . '(): Upload folder is ' . print_r( $target, true ) );

		if ( move_uploaded_file( $file['tmp_name'], $target['path'] ) ) {
			GFCommon::log_debug( __METHOD__ . '(): File ' . $file['tmp_name'] . ' successfully moved to ' . $target['path'] . '.' );

			return array('message' => __('File uploaded.', 'gravityformspostupload'), 'data' => $target['url']);
		} else {
			GFCommon::log_debug( __METHOD__ . '(): FAILED (Temporary file ' . $file['tmp_name'] . ' could not be copied to ' . $target['path'] . '.)' );
			return array('message' => __('FAILED (Temporary file could not be copied.)', 'gravityformspostupload'), 'data' => (object)[]);
		}
	}

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since  1.0
	 *
	 * @return WP_POST_FIELD $_instance An instance of the WP_POST_FIELD class
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * 上传输入框
	 *
	 * @since 1.0
	 */
	public function pre_init() {
		parent::pre_init();
		require_once 'includes/class-gf-post-upload-input.php';
	}

}
