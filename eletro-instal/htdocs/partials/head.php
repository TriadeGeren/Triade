<?php
/** <head> comum. Defina antes: $title, $desc (opcional), $bodyClass (opcional), $extraHead (opcional) */
$title = $title ?? 'Eletro Instal — Equipamentos e serviços elétricos';
$desc  = $desc ?? 'Cabos, disjuntores, quadros, iluminação e serviços de instalação elétrica. Compre online com a Eletro Instal.';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="#05070d">
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
  <link rel="preload" href="assets/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/archivo-wdth.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/css/style.css?v=1">
<?= $extraHead ?? '' ?>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '', ENT_QUOTES, 'UTF-8') ?>">
