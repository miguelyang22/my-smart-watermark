<?php
/*
Plugin Name: My Smart Watermark
Description: 自動為前台圖片加上斜角多行浮水印（保留原圖），首訪生成、後續快取。
Version: 1.1
Author: Miguel
*/

define('MSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MSW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MSW_CACHE_DIR', WP_CONTENT_DIR . '/uploads/msw-cache/');
define('MSW_FONT_FILE', MSW_PLUGIN_DIR . 'font/NotoSansTC-Regular.ttf');
define('MSW_WATERMARK_IMAGE', get_option('msw_watermark_image'));

// 啟用插件時建立資料夾
register_activation_hook(__FILE__, function () {
    if (!file_exists(MSW_CACHE_DIR)) {
        mkdir(MSW_CACHE_DIR, 0755, true);
    }
    $wm_dir = WP_CONTENT_DIR . '/uploads/msw-watermarks/';
    if (!file_exists($wm_dir)) {
        mkdir($wm_dir, 0755, true);
    }
});

// 加入管理選單
add_action('admin_menu', function () {
    add_menu_page(
        '浮水印设置',
        '浮水印设置',
        'manage_options',
        'msw-settings',
        'msw_render_settings_page'
    );
    require_once MSW_PLUGIN_DIR . 'page.php'; // 引入設定頁面邏輯
});

// 將圖片替換為帶浮水印的快取版本（前台啟用時）
if (get_option('msw_enable_frontend_replace') === '1') {
    add_filter('the_content', 'msw_replace_img_to_watermarked');
}

function msw_replace_img_to_watermarked($content) {
    return preg_replace_callback('/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/i', function ($matches) {
        $original_url = $matches[1];

        // 路徑處理
        $parsed = parse_url($original_url);
        if (!isset($parsed['path'])) return $matches[0];

        $file_path = $_SERVER['DOCUMENT_ROOT'] . $parsed['path'];
        if (!file_exists($file_path)) return $matches[0];

        $cached_path = MSW_CACHE_DIR . md5($file_path . '-' . get_option('msw_watermark_type')) . '.jpg';
        $cached_url = content_url('/uploads/msw-cache/' . basename($cached_path));

        if (!file_exists($cached_path)) {
            require_once MSW_PLUGIN_DIR . 'watermark.php';
            msw_apply_watermark($file_path, $cached_path);
        }

        return str_replace($original_url, $cached_url, $matches[0]);
    }, $content);
}

// 自動監聽新圖片上傳，生成水印版快取
add_action('add_attachment', function ($post_ID) {
    if (get_option('msw_auto_apply') !== '1') return;

    $file = get_attached_file($post_ID);
    $mime = mime_content_type($file);
    if (strpos($mime, 'image/') !== 0) return;

    $cached_path = MSW_CACHE_DIR . md5($file . '-' . get_option('msw_watermark_type')) . '.jpg';
    if (!file_exists($cached_path)) {
        require_once MSW_PLUGIN_DIR . 'watermark.php';
        msw_apply_watermark($file, $cached_path);
    }
});
