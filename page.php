<?php
// 插件后台设置页面：浮水印设置页面

function msw_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>浮水印设置</h1>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('msw_settings_action', 'msw_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">水印类型</th>
                    <td>
                        <select name="msw_watermark_type">
                            <option value="text" <?php selected(get_option('msw_watermark_type'), 'text'); ?>>文字水印</option>
                            <option value="image" <?php selected(get_option('msw_watermark_type'), 'image'); ?>>图像水印</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">图像水印上传</th>
                    <td>
                        <?php
                        $image_path = get_option('msw_watermark_image');
                        if ($image_path && file_exists($image_path)) {
                            echo '<img src="' . content_url(str_replace(WP_CONTENT_DIR, '', $image_path)) . '" width="200" style="margin-bottom:10px;"><br>';
                            echo '<label><input type="checkbox" name="msw_remove_watermark_image" value="1"> 删除当前图像水印</label><br>';
                        }
                        ?>
                        <input type="file" name="msw_image_upload" accept="image/png">
                        <p class="description">建议使用透明 PNG 格式，插件将自动缩放并重复平铺。</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">新上传图片自动加水印</th>
                    <td>
                        <label>
                            <input type="checkbox" name="msw_auto_apply" value="1" <?php checked(get_option('msw_auto_apply'), '1'); ?>>
                            启用
                        </label>
                        <p class="description">启用后，所有新上传的图片都会自动生成水印版本。</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('保存设置'); ?>
        </form>
    </div>
    <?php

    // 保存逻辑
    if (isset($_POST['msw_settings_nonce']) && wp_verify_nonce($_POST['msw_settings_nonce'], 'msw_settings_action')) {
        // 保存水印类型
        update_option('msw_watermark_type', sanitize_text_field($_POST['msw_watermark_type']));

        // 保存自动浮水印开关
        update_option('msw_auto_apply', isset($_POST['msw_auto_apply']) ? '1' : '0');

        // 删除旧图像水印
        if (!empty($_POST['msw_remove_watermark_image'])) {
            $old = get_option('msw_watermark_image');
            if ($old && file_exists($old)) {
                unlink($old);
            }
            delete_option('msw_watermark_image');
        }

        // 上传新图像水印
        if (!empty($_FILES['msw_image_upload']['tmp_name'])) {
            $upload_dir = WP_CONTENT_DIR . '/uploads/msw-watermarks/';
            if (!file_exists($upload_dir)) {
                wp_mkdir_p($upload_dir);
            }

            $filename = 'watermark_' . time() . '.png';
            $dest = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['msw_image_upload']['tmp_name'], $dest)) {
                update_option('msw_watermark_image', $dest);
            }
        }

        echo '<div class="updated"><p>设置已保存。</p></div>';
    }
}
