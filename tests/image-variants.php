<?php
// Isolated CI only: exercise the real image pipeline without customer media.
if (getenv('ONESTEP_CI') !== '1') WP_CLI::error('CI fixtures are forbidden outside isolated CI.');
$filename = hash('sha256', 'onestep-ci-image').'.png';
$directory = WP_CONTENT_DIR.'/uploads/legacy';
wp_mkdir_p($directory);
$image = imagecreatetruecolor(1600, 900);
imagefill($image, 0, 0, imagecolorallocate($image, 170, 40, 25));
imagepng($image, $directory.'/'.$filename);
imagedestroy($image);
$palette_name = hash('sha256', 'onestep-ci-palette').'.png';
$palette = imagecreate(302, 180);
imagecolorallocate($palette, 170, 40, 25);
imagepng($palette, $directory.'/'.$palette_name);
imagedestroy($palette);
$original_hash = hash_file('sha256', $directory.'/'.$filename);
include WP_PLUGIN_DIR.'/xdj-business/optimize-media.php';
if (empty(get_option('xdj_image_variants')[$palette_name][302])) WP_CLI::error('Palette PNG conversion failed.');
$variants = get_option('xdj_image_variants')[$filename] ?? [];
if (array_keys($variants) !== [320, 768, 1280]) WP_CLI::error('Unexpected variant widths.');
foreach ($variants as $width => $variant) {
    $file = $directory.'/optimized/'.$variant['file'];
    $size = wp_getimagesize($file);
    if (!$size || $size[0] !== $width || $size['mime'] !== 'image/webp') WP_CLI::error('Invalid WebP derivative.');
}
$attrs = xdj_image_attributes(content_url('/uploads/legacy/'.$filename), '100px', 320);
if (!str_contains($attrs['src'] ?? '', '-w320-v1.webp')) WP_CLI::error('Wrong selected image size.');
foreach ([320,768,1280] as $width) if (!str_contains($attrs['srcset'], ' '.$width.'w')) WP_CLI::error('Missing responsive source.');
if (xdj_image_attributes('https://example.invalid/image.png')) WP_CLI::error('External media must remain untouched.');
include WP_PLUGIN_DIR.'/xdj-business/optimize-media.php';
if (hash_file('sha256', $directory.'/'.$filename) !== $original_hash) WP_CLI::error('Original image was modified.');
WP_CLI::success('WebP variants, responsive selection, repeated execution and original preservation verified.');
