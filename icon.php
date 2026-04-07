<?php

$size = max(16, min(1024, intval($_GET['size'] ?? 192)));

header('Cache-Control: public, max-age=31536000, immutable');

// Try GD first
if (function_exists('imagecreatetruecolor')) {
    header('Content-Type: image/png');

    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    $blue = imagecolorallocate($img, 0x25, 0x63, 0xEB);
    $white = imagecolorallocate($img, 255, 255, 255);
    $darkBlue = imagecolorallocate($img, 0x1D, 0x4E, 0xD8);

    // Fill background
    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $blue);

    // Rounded corners effect - draw arcs at corners
    $r = (int)($size * 0.18);
    if ($r > 2) {
        $bg = imagecolorallocatealpha($img, 0, 0, 0, 127); // transparent
        // Clear corners
        imagefilledrectangle($img, 0, 0, $r, $r, $bg);
        imagefilledrectangle($img, $size - $r - 1, 0, $size - 1, $r, $bg);
        imagefilledrectangle($img, 0, $size - $r - 1, $r, $size - 1, $bg);
        imagefilledrectangle($img, $size - $r - 1, $size - $r - 1, $size - 1, $size - 1, $bg);
        // Draw filled arcs for rounded corners
        imagefilledarc($img, $r, $r, $r * 2, $r * 2, 180, 270, $blue, IMG_ARC_PIE);
        imagefilledarc($img, $size - $r - 1, $r, $r * 2, $r * 2, 270, 360, $blue, IMG_ARC_PIE);
        imagefilledarc($img, $r, $size - $r - 1, $r * 2, $r * 2, 90, 180, $blue, IMG_ARC_PIE);
        imagefilledarc($img, $size - $r - 1, $size - $r - 1, $r * 2, $r * 2, 0, 90, $blue, IMG_ARC_PIE);
    }

    // Draw a small book icon above text
    $bookW = (int)($size * 0.3);
    $bookH = (int)($size * 0.25);
    $bookX = (int)(($size - $bookW) / 2);
    $bookY = (int)($size * 0.18);
    // Book body
    imagefilledrectangle($img, $bookX, $bookY, $bookX + $bookW, $bookY + $bookH, $white);
    // Book spine
    $spineX = (int)($bookX + $bookW * 0.48);
    imagefilledrectangle($img, $spineX, $bookY, $spineX + (int)($bookW * 0.04), $bookY + $bookH, $darkBlue);
    // Book pages lines
    for ($i = 1; $i <= 3; $i++) {
        $lineY = $bookY + (int)($bookH * 0.2 * $i + $bookH * 0.1);
        $lineX1 = $bookX + (int)($bookW * 0.1);
        $lineX2 = $spineX - (int)($bookW * 0.05);
        if ($lineY < $bookY + $bookH - 2) {
            imageline($img, $lineX1, $lineY, $lineX2, $lineY, $blue);
        }
    }

    // Draw "PDF" text using scaling trick
    // Built-in font 5: each char ~9x15px, "PDF" = 27x15
    $baseCharW = 9;
    $baseCharH = 15;
    $textStr = 'PDF';
    $baseTextW = strlen($textStr) * $baseCharW;

    $targetTextW = (int)($size * 0.45);
    $scale = $targetTextW / $baseTextW;

    $tmpW = (int)(ceil($size / $scale));
    $tmpH = (int)(ceil($size / $scale));
    $tmp = imagecreatetruecolor($tmpW, $tmpH);
    $tmpBg = imagecolorallocate($tmp, 0x25, 0x63, 0xEB);
    $tmpWhite = imagecolorallocate($tmp, 255, 255, 255);
    imagefill($tmp, 0, 0, $tmpBg);
    imagecolortransparent($tmp, $tmpBg);

    $textX = (int)(($tmpW - $baseTextW) / 2);
    $textY = (int)($tmpH * 0.62);
    imagestring($tmp, 5, $textX, $textY, $textStr, $tmpWhite);

    // Scale text onto main image (only the text portion)
    $srcY = (int)($tmpH * 0.55);
    $srcH = $baseCharH + 4;
    $dstY = (int)($size * 0.58);
    $dstH = (int)($srcH * $scale);
    imagecopyresampled(
        $img, $tmp,
        0, $dstY, 0, $srcY,
        $size, $dstH, $tmpW, $srcH
    );
    imagedestroy($tmp);

    imagepng($img);
    imagedestroy($img);
    exit;
}

// SVG fallback if GD is not available
header('Content-Type: image/svg+xml');
$r = round($size * 0.18);
$fontSize = round($size * 0.3);
$bookY = round($size * 0.2);
$bookH = round($size * 0.22);
$bookW = round($size * 0.28);
$bookX = round(($size - $bookW) / 2);

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
  <rect width="100%" height="100%" rx="{$r}" fill="#2563eb"/>
  <rect x="{$bookX}" y="{$bookY}" width="{$bookW}" height="{$bookH}" rx="2" fill="white"/>
  <line x1="{$bookX}" y1="{$bookY}" x2="{$bookX}" y2="{$bookY}" stroke="#1d4ed8" stroke-width="2"/>
  <text x="50%" y="72%" font-family="-apple-system,sans-serif" font-size="{$fontSize}" font-weight="bold" fill="white" text-anchor="middle" dominant-baseline="middle">PDF</text>
</svg>
SVG;
