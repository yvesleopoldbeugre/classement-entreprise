<?php
// Génère public/og-image.png (1200x630) — carte de partage de marque ClassementCI.

$W = 1200; $H = 630;
$font = '/Library/Fonts/Arial Unicode.ttf';

$im = imagecreatetruecolor($W, $H);
imageantialias($im, true);

// --- Dégradé vertical indigo (#4f46e5 -> #3730a3) ---
[$r1,$g1,$b1] = [79, 70, 229];
[$r2,$g2,$b2] = [55, 48, 163];
for ($y = 0; $y < $H; $y++) {
    $t = $y / $H;
    $c = imagecolorallocate($im,
        (int)round($r1 + ($r2-$r1)*$t),
        (int)round($g1 + ($g2-$g1)*$t),
        (int)round($b1 + ($b2-$b1)*$t));
    imageline($im, 0, $y, $W, $y, $c);
}

// Couleurs
$white   = imagecolorallocate($im, 255, 255, 255);
$indigo  = imagecolorallocate($im, 79, 70, 229);
$indigo1 = imagecolorallocate($im, 199, 210, 254); // indigo-200 (tagline)
$indigo2 = imagecolorallocate($im, 165, 180, 252); // indigo-300 (domaine)
$amber   = imagecolorallocate($im, 251, 191, 36);  // étoiles

// --- Rectangle arrondi (badge blanc) ---
function roundedRect($im, $x, $y, $x2, $y2, $rad, $color) {
    imagefilledrectangle($im, $x+$rad, $y, $x2-$rad, $y2, $color);
    imagefilledrectangle($im, $x, $y+$rad, $x2, $y2-$rad, $color);
    imagefilledellipse($im, $x+$rad, $y+$rad, $rad*2, $rad*2, $color);
    imagefilledellipse($im, $x2-$rad, $y+$rad, $rad*2, $rad*2, $color);
    imagefilledellipse($im, $x+$rad, $y2-$rad, $rad*2, $rad*2, $color);
    imagefilledellipse($im, $x2-$rad, $y2-$rad, $rad*2, $rad*2, $color);
}

// Badge 130x130 à (90,90) avec ★ indigo
$bx = 90; $by = 90; $bs = 130;
roundedRect($im, $bx, $by, $bx+$bs, $by+$bs, 30, $white);
// ★ centré dans le badge
$star = '★';
$box = imagettfbbox(78, 0, $font, $star);
$sw = $box[2] - $box[0]; $sh = $box[1] - $box[7];
imagettftext($im, 78, 0, (int)($bx + ($bs-$sw)/2 - $box[0]), (int)($by + ($bs+$sh)/2 - 6), $indigo, $font, $star);

// Nom du site à côté du badge
imagettftext($im, 30, 0, 250, 145, $white, $font, 'notetaboite.com');
imagettftext($im, 15, 0, 252, 178, $indigo1, $font, 'CÔTE D’IVOIRE');

// --- Titre principal ---
imagettftext($im, 74, 0, 90, 330, $white, $font, 'Classement des');
imagettftext($im, 74, 0, 90, 425, $white, $font, 'entreprises');

// Étoiles décoratives
imagettftext($im, 40, 0, 620, 425, $amber, $font, '★★★★★');

// --- Sous-titre ---
imagettftext($im, 31, 0, 90, 500, $indigo1, $font, 'Notées par ceux qui y ont réellement travaillé.');
imagettftext($im, 25, 0, 90, 555, $indigo2, $font, 'Avis vérifiés · Score bayésien · Fiable dès le 1er avis');

imagepng($im, __DIR__ . '/../public/og-image.png', 9);
imagedestroy($im);
echo "OK\n";
