<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * 上传文件字段
 *
 * @since 1.0
 *
 * Class GF_POST_UPLOAD_INPUT
 */
class GF_POST_UPLOAD_INPUT extends GF_Field {

	/**
	 * Field type.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $type = 'post_upload_input';

	/**
	 * Get field button title.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return '直接上传文件';
	}

	/**
	 * Get this field's icon.
	 *
	 * @since 1.4
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--upload';
	}

	/**
	 * Get form editor button.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'standard_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	/**
	 * Get field settings in the form editor.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	 function get_form_editor_field_settings() {
		return array(
			// 'conditional_logic_field_setting',
			// 'error_message_setting',
			'label_setting',
			// 'label_placement_setting',
			// 'rules_setting',
			'file_size_setting',
			// 'description_setting',
		);
	}


	/**
	 * 输入字段的属性
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		$lead_id = absint( rgar( $entry, 'id' ) );

		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = absint( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;
		$class        = esc_attr( $class );

		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$tabindex        = $this->get_tabindex();
		$multiple_files  = $this->multipleFiles;
		$file_list_id    = 'gform_preview_' . $form_id . '_' . $id;

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin = $is_entry_detail || $is_form_editor;

		// Generate upload rules messages ( allowed extensions, max no. of files, max file size ).
		$upload_rules_messages = array();
		// Extensions.
		$allowed_extensions = ! empty( $this->allowedExtensions ) ? join( ',', GFCommon::clean_extensions( explode( ',', strtolower( $this->allowedExtensions ) ) ) ) : array();
		if ( ! empty( $allowed_extensions ) ) {
			$upload_rules_messages[] = esc_attr( sprintf( __( 'Accepted file types: %s', 'gravityforms' ), str_replace( ',', ', ', $allowed_extensions ) ) );
		}
		// File size.
		$max_upload_size = $this->maxFileSize > 0 ? $this->maxFileSize * 1048576 : wp_max_upload_size();
		// translators: %s is replaced with a numeric string representing the maximum file size
		$upload_rules_messages[] = esc_attr( sprintf( __( 'Max. file size: %s', 'gravityforms' ), GFCommon::format_file_size( $max_upload_size ) ) );
		// No. of files.
		$max_files = ( $multiple_files && $this->maxFiles > 0 ) ? $this->maxFiles : 0;
		if ( $max_files ) {
			// translators: %s is replaced with a numeric string representing the maximum number of files
			$upload_rules_messages[] = esc_attr( sprintf( __( 'Max. files: %s', 'gravityforms' ), $max_files ) );
		}

		$rules_messages = implode( ', ', $upload_rules_messages ) . '.';

		$rules_messages_id = empty( $rules_messages ) ? '' : "gfield_upload_rules_{$this->formId}_{$this->id}";
		$describedby       = $this->get_aria_describedby( array( $rules_messages_id ) );
		
		$upload = '';
		if ( $max_upload_size <= 2047 * 1048576 ) {
			//  MAX_FILE_SIZE > 2048MB fails. The file size is checked anyway once uploaded, so it's not necessary.
			$upload = sprintf( "<input type='hidden' name='MAX_FILE_SIZE' value='%d' />", $max_upload_size );
		}

		$live_validation_message_id = 'live_validation_message_' . $form_id . '_' . $id;

		// 这里搞个真正的input_id传递的值
		$upload .= sprintf( "<input type='hidden' name='input_%d' id='upload_now' value='%s' />", $id, $field_id, $value );

		$upload .= sprintf( "<input id='%s' type='file' class='%s' %s onchange='javascript:uploadFileByGfPost( this, %s );' {$tabindex} %s/>", $field_id, esc_attr( $class ), $describedby, esc_attr( $max_upload_size ), $disabled_text );

		$upload .= $rules_messages ? "<span class='gfield_description gform_fileupload_rules' id='{$rules_messages_id}'>{$rules_messages}</span>" : '';
		$upload .= "<div class='gfield_description validation_message gfield_validation_message validation_message--hidden-on-empty' id='{$live_validation_message_id}'></div>";
		$upload .= '<div id="progressBar777"></div>';
		

		if ( $is_entry_detail && ! empty( $value ) ) { // edit entry
			$file_urls      = $multiple_files ? json_decode( $value ) : array( $value );
			$upload_display = $multiple_files ? '' : "style='display:none'";
			$preview        = "<div id='upload_$id' {$upload_display}>$upload</div>";
			$preview .= sprintf( "<div id='%s' class='ginput_preview_list'></div>", $file_list_id );
			$preview .= sprintf( "<div id='preview_existing_files_%d'>", $id );

			foreach ( $file_urls as $file_index => $file_url ) {

				/**
				 * Allow for override of SSL replacement.
				 *
				 * By default Gravity Forms will attempt to determine if the schema of the URL should be overwritten for SSL.
				 * This is not ideal for all situations, particularly domain mapping. Setting $field_ssl to false will prevent
				 * the override.
				 *
				 * @since 2.1.1.23
				 *
				 * @param bool                $field_ssl True to allow override if needed or false if not.
				 * @param string              $file_url  The file URL in question.
				 * @param GF_Field_FileUpload $field     The field object for further context.
				 */
				$field_ssl = apply_filters( 'gform_secure_file_download_is_https', true, $file_url, $this );

				if ( GFCommon::is_ssl() && strpos( $file_url, 'http:' ) !== false && $field_ssl === true ) {
					$file_url = str_replace( 'http:', 'https:', $file_url );
				}
				$download_file_text  = esc_attr__( 'Download file', 'gravityforms' );
				$delete_file_text    = esc_attr__( 'Delete file', 'gravityforms' );
				$view_file_text      = esc_attr__( 'View file', 'gravityforms' );
				$file_index          = intval( $file_index );
				$file_url            = esc_attr( $file_url );
				$display_file_url    = GFCommon::truncate_url( $file_url );
				$file_url            = $this->get_download_url( $file_url );
				$preview .= "<div id='preview_file_{$file_index}' class='ginput_preview'>
								<a href='{$file_url}' target='_blank' aria-label='{$view_file_text}'>{$display_file_url}</a>
								<a href='{$file_url}' target='_blank' aria-label='{$download_file_text}' class='ginput_preview_control gform-icon gform-icon--circle-arrow-down'></a>
								<a href='javascript:void(0);' aria-label='{$delete_file_text}' onclick='DeleteFile({$lead_id},{$id},this);' onkeypress='DeleteFile({$lead_id},{$id},this);' class='ginput_preview_control gform-icon gform-icon--circle-delete'></a>
							</div>";
			}

			$preview .= '</div>';

			return $preview;
		} else {
			$input_name     = "input_{$id}";
			$uploaded_files = isset( GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] ) ? GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] : array();
			$file_infos     = $multiple_files ? $uploaded_files : RGFormsModel::get_temp_filename( $form_id, $input_name );

			if ( ! empty( $file_infos ) ) {
				$preview   = sprintf( "<div id='%s' class='ginput_preview_list'>", $file_list_id );
				$file_infos = $multiple_files ? $uploaded_files : array( $file_infos );
				foreach ( $file_infos as $file_info ) {

					if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
						$file_upload_markup = "<img alt='" . esc_attr__( 'Delete file', 'gravityforms' ) . "' class='gform_delete' src='" . GFCommon::get_base_url() . "/images/delete.png' onclick='gformDeleteUploadedFile({$form_id}, {$id}, this);' onkeypress='gformDeleteUploadedFile({$form_id}, {$id}, this);' /> <strong>" . esc_html( $file_info['uploaded_filename'] ) . '</strong>';
					} else {
						$file_upload_markup = sprintf( '<span class="gfield_fileupload_filename">%s</span>', esc_html( $file_info['uploaded_filename'] ) );
						// TODO: get file size $file_upload_markup .= sprintf( '<span class="gfield_fileupload_filesize">%s</span>', esc_html( $file_info['uploaded_filesize'] ) );
						$file_upload_markup .= '<span class="gfield_fileupload_progress gfield_fileupload_progress_complete"><span class="gfield_fileupload_progressbar"><span class="gfield_fileupload_progressbar_progress" style="width: 100%;"></span></span><span class="gfield_fileupload_percent">100%</span></span>';
						$file_upload_markup .= sprintf(
							'<button class="gform_delete_file gform-theme-button gform-theme-button--simple" onclick="gformDeleteUploadedFile( %d, %d, this );"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text">%s: %s</span></button>',
							$form_id,
							$id,
							esc_html__( 'Delete this file', 'gravityforms' ),
							esc_html( $file_info['uploaded_filename'] )
						);
					}

					/**
					 * Modify the HTML for the Multi-File Upload "preview."
					 *
					 * @since Unknown
					 *
					 * @param string $file_upload_markup The current HTML for the field.
					 * @param array  $file_info          Details about the file uploaded.
					 * @param int    $form_id            The current Form ID.
					 * @param int    $id                 The current Field ID.
					 */
					$file_upload_markup = apply_filters( 'gform_file_upload_markup', $file_upload_markup, $file_info, $form_id, $id );
					$preview            .= "<div class='ginput_preview'>{$file_upload_markup}</div>";
				}
				$preview .= '</div>';
				if ( ! $multiple_files ) {
					$upload = str_replace( " class='", " class='gform_hidden ", $upload );
				}

				return "<div class='ginput_container ginput_container_fileupload'>" . $upload . " {$preview}</div>";
			} else {

				$preview = $multiple_files ? sprintf( "<div id='%s' class='ginput_preview_list'></div>", $file_list_id ) : '';

				return "<div class='ginput_container ginput_container_fileupload'>$upload</div>" . $preview;
			}
		}
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 *
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @since 1.0
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {

		// Get the default HTML markup.
		$form_id = (int) rgar( $form, 'id' );

		$field_label = $this->get_field_label( $force_frontend_label, $value );

		$validation_message_id = 'validation_message_' . $form_id . '_' . $this->id;
		$validation_message    = ( $this->failed_validation && ! empty( $this->validation_message ) ) ? sprintf( "<div id='%s' class='gfield_description validation_message gfield_validation_message' aria-live='polite'>%s</div>", $validation_message_id, $this->validation_message ) : '';

		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		$required_div = $is_admin || $this->isRequired ? sprintf( "<span class='gfield_required'>%s</span>", $this->isRequired ? '*' : '' ) : '';

		$admin_buttons = $this->get_admin_buttons();

		$for_attribute = empty( $target_input_id ) ? '' : "for='{$target_input_id}'";

		$legend_wrapper       = '';
		$legend_wrapper_close = '';

		if ( method_exists( 'GF_Field', 'get_field_label_tag' ) ) {
			$label_tag = parent::get_field_label_tag( $form );
			if ( $is_form_editor && 'legend' === $label_tag ) {
				$legend_wrapper       = '<label class="gfield_label gform-field-label">';
				$legend_wrapper_close = '</label>';
			}
		} else {
			$label_tag = 'label';
		}

		// $input_content = '';

		// $invalid_attribute      = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		// $required_attribute     = $this->isRequired ? 'aria-required="true"' : '';

		if (!$is_form_editor) {
			$placeholder_attribute = $this->get_field_placeholder_attribute();
			// $input_content = "<div class='ginput_container ginput_container_text'>
			// <input class='large' type='text' name='input_{$this->id}' id='input_{$this->id}' value='{$value}' {$required_attribute} {$invalid_attribute} {$placeholder_attribute}/></div>";
		}

		$field_content = sprintf( "%s<$label_tag class='%s' $for_attribute >$legend_wrapper%s%s$legend_wrapper_close</$label_tag>{FIELD}%s", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $validation_message );
		
		return $field_content;
	}

	/**
	 * Overwrite the parent method to avoid the field upgrade from the credit card field class.
	 *
	 * @since 1.0
	 */
	public function post_convert_field() {
		GF_Field::post_convert_field();
	}
}

GF_Fields::register( new GF_POST_UPLOAD_INPUT() );
