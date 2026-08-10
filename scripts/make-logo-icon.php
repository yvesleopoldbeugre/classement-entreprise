<?php

// Génère public/logo-icon.png (512x512) — icône carrée pour les consoles SSO
// (Google, LinkedIn, Facebook…). Coins transparents (rendu app-icon), étoile blanche.

$S = 512;
$font = '/Library/Fonts/Arial Unicode.ttf';

$im = imagecreatetruecolor($S, $S);
imagesavealpha($im, true);
imagealphablending($im, false);
imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127)); // transparent
imagealphablending($im, true);

$indigo = imagecolorallocate($im, 79, 70, 229);   // #4f46e5
$white = imagecolorallocate($im, 255, 255, 255);

// Rectangle arrondi plein (quasi plein cadre).
function roundedRect($im, $x, $y, $x2, $y2, $rad, $color): void
{
    imagefilledrectangle($im, $x + $rad, $y, $x2 - $rad, $y2, $color);
    imagefilledrectangle($im, $x, $y + $rad, $x2, $y2 - $rad, $color);
    foreach ([[$x + $rad, $y + $rad], [$x2 - $rad, $y + $rad], [$x + $rad, $y2 - $rad], [$x2 - $rad, $y2 - $rad]] as [$cx, $cy]) {
        imagefilledellipse($im, $cx, $cy, $rad * 2, $rad * 2, $color);
    }
}

roundedRect($im, 0, 0, $S - 1, $S - 1, 112, $indigo);

// Étoile blanche centrée.
$star = '★';
$taille = 300;
$box = imagettfbbox($taille, 0, $font, $star);
$w = $box[2] - $box[0];
$h = $box[1] - $box[7];
imagettftext(
    $im,
    $taille,
    0,
    (int) (($S - $w) / 2 - $box[0]),
    (int) (($S + $h) / 2 - 10),
    $white,
    $font,
    $star,
);

imagepng($im, __DIR__.'/../public/logo-icon.png', 9);
imagedestroy($im);
echo "OK\n";
