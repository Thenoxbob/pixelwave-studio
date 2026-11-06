<?php
// Illustrations « pixel art » géométriques reprises de la charte graphique Figma.
// $code : valeur de produit.visuel ; $classe : classe CSS facultative
$code ??= 'defaut';
$formes = [
    'neon'     => ['#1f2340', '<rect y="130" width="320" height="70" fill="#3ddbc4"/><rect x="200" y="40" width="44" height="44" fill="#ff8a5b"/><rect x="250" y="84" width="16" height="16" fill="#ff8a5b"/>'],
    'nocturne' => ['#0b0f1d', '<g fill="#3ddbc4"><rect x="0" y="90" width="27" height="110"/><rect x="27" y="62" width="27" height="138"/><rect x="54" y="44" width="27" height="156"/><rect x="81" y="30" width="27" height="170"/><rect x="108" y="44" width="27" height="156"/><rect x="135" y="62" width="27" height="138"/><rect x="162" y="90" width="27" height="110"/><rect x="189" y="116" width="27" height="84"/><rect x="216" y="90" width="27" height="110"/><rect x="243" y="62" width="27" height="138"/><rect x="270" y="44" width="27" height="156"/><rect x="297" y="30" width="27" height="170"/></g><rect x="200" y="4" width="54" height="54" fill="#ff8a5b"/><rect x="254" y="0" width="27" height="6" fill="#ff8a5b"/>'],
    'forge'    => ['#ff8a5b', '<rect x="105" y="70" width="110" height="80" fill="#2b1206"/><rect x="140" y="40" width="40" height="30" fill="#2b1206"/><rect x="90" y="150" width="140" height="14" fill="#2b1206"/>'],
    'hiver'    => ['#ff8a5b', '<rect x="40" y="40" width="22" height="22" fill="#eef0f7"/><rect x="230" y="30" width="14" height="14" fill="#eef0f7"/><rect x="150" y="90" width="18" height="18" fill="#eef0f7"/><rect y="160" width="320" height="40" fill="#eef0f7"/><rect x="90" y="120" width="60" height="40" fill="#2b1206"/>'],
    'abyss'    => ['#2a2f55', '<circle cx="160" cy="105" r="60" fill="#3ddbc4"/><rect y="150" width="320" height="50" fill="#0b0f1d"/>'],
    'starlit'  => ['#2a2f55', '<rect x="60" y="40" width="12" height="12" fill="#ffd166"/><rect x="240" y="26" width="14" height="14" fill="#eef0f7"/><rect x="140" y="70" width="18" height="18" fill="#ffd166"/><rect x="110" y="120" width="96" height="44" fill="#ff8a5b"/><rect y="164" width="320" height="36" fill="#1f2340"/>'],
    'galaxy'   => ['#1f2340', '<circle cx="110" cy="100" r="38" fill="#ff8a5b"/><circle cx="220" cy="70" r="18" fill="#3ddbc4"/><rect x="250" y="140" width="10" height="10" fill="#eef0f7"/><rect x="40" y="40" width="8" height="8" fill="#ffd166"/>'],
    'retro'    => ['#1f2340', '<rect y="70" width="320" height="18" fill="#ff8a5b"/><rect y="96" width="320" height="18" fill="#ffd166"/><rect y="122" width="320" height="18" fill="#3ddbc4"/>'],
    'defaut'   => ['#1f2340', '<rect x="140" y="80" width="40" height="40" fill="#343a63"/>'],
];
[$fond, $dessin] = $formes[$code] ?? $formes['defaut'];
?>
<svg class="visuel <?= e($classe ?? '') ?>" viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <rect width="320" height="200" fill="<?= $fond ?>"/><?= $dessin ?>
</svg>
