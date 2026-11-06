<?php

declare(strict_types=1);

$width = 1200;
$height = 630;

$image = imagecreatetruecolor($width, $height);

// Color palette
$bg = imagecolorallocate($image, 255, 253, 248); // #fffdf8 (kertas)
$terakota = imagecolorallocate($image, 140, 50, 27); // #8C321B
$emas = imagecolorallocate($image, 247, 192, 74); // #F7C04A
$mangsi = imagecolorallocate($image, 28, 27, 25); // #1c1b19
$abu = imagecolorallocate($image, 102, 97, 90); // #66615a
$border = imagecolorallocate($image, 221, 214, 201); // #ddd6c9

imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Border frame
imagesetthickness($image, 4);
imagerectangle($image, 20, 20, $width - 20, $height - 20, $emas);
imagesetthickness($image, 2);
imagerectangle($image, 28, 28, $width - 28, $height - 28, $terakota);

// Top accent bar
imagefilledrectangle($image, 30, 30, $width - 30, 42, $terakota);

// Load and place logo
$logoPath = __DIR__ . '/../public/assets/images/logo_rahina.png';
if (file_exists($logoPath)) {
    $logo = imagecreatefrompng($logoPath);
    if ($logo) {
        $logoSize = 160;
        $logoW = imagesx($logo);
        $logoH = imagesy($logo);
        $scale = min($logoSize / $logoW, $logoSize / $logoH);
        $destW = (int) ($logoW * $scale);
        $destH = (int) ($logoH * $scale);
        imagecopyresampled($image, $logo, 100, 180, 0, 0, $destW, $destH, $logoW, $logoH);
        imagedestroy($logo);
    }
}

// Load and place mascot halo on the right
$mascotPath = __DIR__ . '/../public/assets/images/maskots/halo.png';
if (file_exists($mascotPath)) {
    $mascot = imagecreatefrompng($mascotPath);
    if ($mascot) {
        $mascotW = imagesx($mascot);
        $mascotH = imagesy($mascot);
        $targetH = 460;
        $targetW = (int) ($mascotW * ($targetH / $mascotH));
        imagecopyresampled($image, $mascot, 820, 100, 0, 0, $targetW, $targetH, $mascotW, $mascotH);
        imagedestroy($mascot);
    }
}

// Draw title and text using built-in fonts or GD text
imagestring($image, 5, 290, 200, "RAHINA - KALENDER BALI & TANYA WARIGA AI", $terakota);
imagestring($image, 5, 290, 240, "Kearifan Tradisional Bali & Kecerdasan Buatan Modern", $mangsi);
imagestring($image, 4, 290, 290, "Perhitungan Wariga Presisi 2020 - 2035", $abu);
imagestring($image, 4, 290, 320, "Wewaran, 30 Wuku, Sasih, & Dewasa Ayu Lengkap", $abu);
imagestring($image, 4, 290, 350, "Asisten AI Berlandaskan Lontar Sundarigama & Tri Pramana", $abu);
imagestring($image, 4, 290, 380, "Notifikasi Ringkasan Harian WhatsApp Otomatis (07:00 WITA)", $abu);

// Footer bar
imagefilledrectangle($image, 30, $height - 80, $width - 30, $height - 30, $terakota);
imagestring($image, 4, 290, $height - 62, "Mugi Rahayu Sareng Sami  |  https://rahina.app", $emas);

// Save og-image.png
$ogPath = __DIR__ . '/../public/assets/images/og-image.png';
imagepng($image, $ogPath, 8);
imagedestroy($image);

echo "Generated public/assets/images/og-image.png (1200x630)\n";
