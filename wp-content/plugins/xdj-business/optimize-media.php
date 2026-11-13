<?php
if (!defined('WP_CLI') || !WP_CLI) exit;
$root = WP_CONTENT_DIR.'/uploads/legacy';
$destination = $root.'/optimized';
if (!wp_mkdir_p($destination)) WP_CLI::error('Cannot create optimized media directory.');
$manifest = get_option('xdj_image_variants', []);
$created = 0;
foreach (glob($root.'/*') as $path) {
    $name = basename($path);
    if (!preg_match('/^(?:[a-f0-9]{64}|[a-f0-9-]+-poster)\.(?:jpe?g|png|webp)$/i', $name)) continue;
    $dimensions = wp_getimagesize($path);
    if (!$dimensions) { WP_CLI::warning('Unreadable source image: '.$name); continue; }
    $variants = [];
    foreach ([320, 768, 1280] as $width) {
        $width = min($width, $dimensions[0]);
        if (isset($variants[$width])) continue;
        $file = pathinfo($name, PATHINFO_FILENAME).'-w'.$width.'-v1.webp';
        $target = $destination.'/'.$file;
        if (!is_file($target) || !wp_getimagesize($target)) {
            $editor = wp_get_image_editor($path);
            if (is_wp_error($editor)) WP_CLI::error($editor->get_error_message());
            $editor->set_quality(82);
            if ($width < $dimensions[0]) {
                $resized = $editor->resize($width, null, false);
                if (is_wp_error($resized)) WP_CLI::error($resized->get_error_message());
            }
            if ($width === $dimensions[0] && extension_loaded('gd')) {
                // GD cannot encode palette PNGs directly as WebP when no resize occurred.
                $bitmap = imagecreatefromstring(file_get_contents($path));
                if (!$bitmap) WP_CLI::error('Cannot decode source: '.$name);
                if (!imageistruecolor($bitmap)) imagepalettetotruecolor($bitmap);
                imagealphablending($bitmap, false);
                imagesavealpha($bitmap, true);
                $saved = imagewebp($bitmap, $target, 82);
                imagedestroy($bitmap);
                if (!$saved) WP_CLI::error('Cannot encode WebP: '.$name);
            } else {
                $saved = $editor->save($target, 'image/webp');
                if (is_wp_error($saved)) WP_CLI::error($saved->get_error_message());
            }
            $created++;
        }
        $size = wp_getimagesize($target);
        if (!$size) WP_CLI::error('Invalid generated image: '.$file);
        $variants[$size[0]] = ['file' => $file, 'width' => $size[0], 'height' => $size[1]];
    }
    $manifest[$name] = $variants;
}
update_option('xdj_image_variants', $manifest, false);
WP_CLI::success('Image sources: '.count($manifest).'; new WebP derivatives: '.$created.'. Originals retained.');
