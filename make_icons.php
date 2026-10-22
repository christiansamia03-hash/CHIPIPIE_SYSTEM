<?php
// Generate all required PWA/home-screen icons from logo.png
$src = __DIR__ . '/assets/img/logo.png';
$sizes = [
    ['file' => 'assets/img/apple-touch-icon.png',     'w' => 180, 'h' => 180],
    ['file' => 'assets/img/icon-192.png',             'w' => 192, 'h' => 192],
    ['file' => 'assets/img/icon-512.png',             'w' => 512, 'h' => 512],
    ['file' => 'assets/img/icon-48.png',              'w' => 48,  'h' => 48],
    ['file' => 'assets/img/icon-72.png',              'w' => 72,  'h' => 72],
    ['file' => 'assets/img/icon-96.png',              'w' => 96,  'h' => 96],
    ['file' => 'assets/img/icon-144.png',             'w' => 144, 'h' => 144],
];

$srcImg = imagecreatefrompng($src);
if (!$srcImg) {
    die("Cannot load source image: $src\n");
}

$sw = imagesx($srcImg);
$sh = imagesy($srcImg);

foreach ($sizes as $s) {
    $dest = __DIR__ . '/' . $s['file'];
    $w = $s['w'];
    $h = $s['h'];
    $out = imagecreatetruecolor($w, $h);
    // Transparent background
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefilledrectangle($out, 0, 0, $w, $h, $transparent);
    imagealphablending($out, true);
    imagecopyresampled($out, $srcImg, 0, 0, 0, 0, $w, $h, $sw, $sh);
    imagesavealpha($out, true);
    imagepng($out, $dest, 9);
    imagedestroy($out);
    echo "Created: {$s['file']} ({$w}x{$h})\n";
}

imagedestroy($srcImg);
echo "Done.\n";
