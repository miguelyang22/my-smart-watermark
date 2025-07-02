<?php
function msw_apply_watermark($source_path, $output_path) {
    $watermark_type = get_option('msw_watermark_type', 'text');
    $image = @imagecreatefromstring(file_get_contents($source_path));
    if (!$image) return;

    $width = imagesx($image);
    $height = imagesy($image);

    $canvas = imagecreatetruecolor($width, $height);
    imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

    if ($watermark_type === 'image') {
        $watermark_path = get_option('msw_watermark_image');
        if (!$watermark_path || !file_exists($watermark_path)) return;

        $wm_img = @imagecreatefrompng($watermark_path);
        if (!$wm_img) return;

        $wm_w = imagesx($wm_img);
        $wm_h = imagesy($wm_img);

        // 调整透明度 & 旋转图像
        $opacity = 40; // 0~100
        $angle = 45;

        $rotated = imagerotate($wm_img, $angle, imageColorAllocateAlpha($wm_img, 0, 0, 0, 127));
        imagealphablending($rotated, true);
        imagesavealpha($rotated, true);
        $rotated_w = imagesx($rotated);
        $rotated_h = imagesy($rotated);

        // 平铺
        for ($y = -$rotated_h; $y < $height + $rotated_h; $y += $rotated_h + 100) {
            for ($x = -$rotated_w; $x < $width + $rotated_w; $x += $rotated_w + 100) {
                msw_imagecopymerge_alpha($canvas, $rotated, $x, $y, 0, 0, $rotated_w, $rotated_h, $opacity);
            }
        }

        imagedestroy($wm_img);
        imagedestroy($rotated);
    } else {
        // 文字水印
        $font = MSW_FONT_FILE;
        $text = "cjfuntravel.com\nC姐玩泰大\n版权所有";
        $angle = 45;
        $font_size = 20;
        $color = imagecolorallocatealpha($canvas, 255, 255, 255, 75); // 半透明白

        // 重复绘制斜角文字
        for ($y = -200; $y < $height + 200; $y += 150) {
            for ($x = -200; $x < $width + 200; $x += 300) {
                imagettftext($canvas, $font_size, $angle, $x, $y, $color, $font, $text);
            }
        }
    }

    imagejpeg($canvas, $output_path, 85);
    imagedestroy($canvas);
    imagedestroy($image);
}

// 支持 imagecopymerge 的 alpha 替代版本
function msw_imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
    $pct = max(0, min(100, $pct));
    $tmp = imagecreatetruecolor($src_w, $src_h);
    imagealphablending($tmp, false);
    imagesavealpha($tmp, true);

    imagecopy($tmp, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
    imagecopy($tmp, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

    imagecopymerge($dst_im, $tmp, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    imagedestroy($tmp);
}
