let $form
let formId

function uploadFileByGfPost(that, fileSizeAllow) {
    var files = that.files; // 获取文件对象数组
    // 对files进行操作，例如显示文件名
    if (files.length > 0) {
        if (files[0].size > fileSizeAllow) {
            alert('文件大小超过限制');
            // 清空文件
            that.value = '';
            return;
        }
        const formData = new FormData();
        formData.append('gfpost_file', files[0]);  // 添加文件对象到 FormData 对象
        // 添加form_id
        formData.append('form_id', formId);
        formData.append('action', 'gf_post_upload');

        jQuery.ajax({
          url: ajaxurl[0],
          type: 'POST',
          data: formData,
          processData: false,  // 告诉 jQuery 不要处理数据
          contentType: false,  // 告诉 jQuery 不要设置 contentType
          xhr: function() {  // 获取 AJAX 的 XMLHttpRequest 对象
            var xhr = jQuery.ajaxSettings.xhr();
            if (xhr.upload) {  // 如果上传属性可用
              xhr.upload.addEventListener('progress', function(e) {  // 进度事件监听
                if (e.lengthComputable) {
                    const progress = Math.round((e.loaded / e.total) * 100);
                    console.log(progress + '%');
                    // jQuery('#progressBar').attr({value:e.loaded, max:e.total});  // 更新进度条
                    jQuery('#progressBar777').show()
                    jQuery('#progressBar777').html(progress + '%')
                }
              }, false);
              xhr.upload.su
            }
            return xhr;
          },
          success: function(data) {
            if (data.data.data) {
                jQuery('#upload_now').val(data.data.data);
                jQuery('#progressBar777').hide()
                jQuery('#progressBar777').html('0%')
            }
          }
        });
    }
}


jQuery(document).ready(function($) {
    if(typeof ajaxurl != 'undefined'){ 
        console.log(ajaxurl[0])
    }
    $form = jQuery("form[data-formid]");
    formId = $form.attr("data-formid");
});