<?php

declare(strict_types=1);

$srcPath = __DIR__ . '/../public/assets/images/logo_rahina.png';
if (!file_exists($srcPath)) {
    echo "Source logo not found at $srcPath\n";
    exit(1);
}

$srcImg = imagecreatefrompng($srcPath);
if (!$srcImg) {
    echo "Failed to load source image\n";
    exit(1);
}

imagealphablending($srcImg, false);
imagesavealpha($srcImg, true);

$srcW = imagesx($srcImg);
$srcH = imagesy($srcImg);

/**
 * Resize PNG with aspect ratio centered in a square canvas.
 */
function createSquareIcon(GdImage $src, int $srcW, int $srcH, int $targetSize): GdImage
{
    $dst = imagecreatetruecolor($targetSize, $targetSize);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $targetSize, $targetSize, $transparent);
    imagealphablending($dst, true);

    $scale = min($targetSize / $srcW, $targetSize / $srcH);
    $newW = (int) round($srcW * $scale);
    $newH = (int) round($srcH * $scale);
    $dstX = (int) round(($targetSize - $newW) / 2);
    $dstY = (int) round(($targetSize - $newH) / 2);

    imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    return $dst;
}

$sizes = [
    16 => __DIR__ . '/../public/favicon-16x16.png',
    32 => __DIR__ . '/../public/favicon-32x32.png',
    48 => __DIR__ . '/../public/favicon-48x48.png',
    180 => __DIR__ . '/../public/apple-touch-icon.png',
    192 => __DIR__ . '/../public/android-chrome-192x192.png',
    512 => __DIR__ . '/../public/android-chrome-512x512.png',
];

$icoSizes = [16, 32, 48];
$pngDataArray = [];

foreach ($sizes as $size => $path) {
    $icon = createSquareIcon($srcImg, $srcW, $srcH, $size);
    imagepng($icon, $path, 9);
    echo "Generated $path ($size x $size)\n";

    if (in_array($size, $icoSizes, true)) {
        ob_start();
        imagepng($icon, null, 9);
        $pngDataArray[$size] = ob_get_clean();
    }
    imagedestroy($icon);
}

// Generate favicon.ico containing 16x16, 32x32, 48x48
$numImages = count($pngDataArray);
$header = pack('vvv', 0, 1, $numImages);
$entries = '';
$dataOffset = 6 + ($numImages * 16);
$allImageData = '';

foreach ($pngDataArray as $size => $pngBytes) {
    $bytesLen = strlen($pngBytes);
    // Width and height in ICO: 256 is represented as 0
    $w = $size >= 256 ? 0 : $size;
    $h = $size >= 256 ? 0 : $size;

    $entries .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $bytesLen, $dataOffset);
    $allImageData .= $pngBytes;
    $dataOffset += $bytesLen;
}

$icoContent = $header . $entries . $allImageData;
file_put_contents(__DIR__ . '/../public/favicon.ico', $icoContent);
echo "Generated public/favicon.ico with multi-resolution PNG chunks\n";

// Also create public/favicon.png for quick modern browser fallback
copy(__DIR__ . '/../public/favicon-32x32.png', __DIR__ . '/../public/favicon.png');
echo "Generated public/favicon.png\n";

imagedestroy($srcImg);
