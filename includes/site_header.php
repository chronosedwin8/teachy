<?php
/** Cabecera pública. Variables opcionales: $pageTitle, $pageDescription, $extraCss (array). */
$pageTitle = $pageTitle ?? (BRAND_NAME . ' | ' . BRAND_TAGLINE);
$pageDescription = $pageDescription ?? 'Plataforma con inteligencia artificial para planear, enseñar, evaluar y acompañar el aprendizaje en toda la institución educativa.';
$user = current_user();
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#0F151A">
<link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=1">
<?php foreach (($extraCss ?? []) as $css): ?>
<link rel="stylesheet" href="<?= url($css) ?>?v=1">
<?php endforeach; ?>
</head>
<body>
<?php if (impersonator_id()): ?>
<div style="background:#FFC744;color:#0F151A;text-align:center;padding:8px 16px;font-weight:700;font-size:.9rem">
  Estás viendo el portal como <?= e($user['email'] ?? '') ?>.
  <a href="<?= url('admin/volver.php') ?>" style="color:#0F151A;text-decoration:underline">Volver a la administración</a>
</div>
<?php endif; ?>
<header class="nav" id="nav">
  <div class="container nav-inner">
    <a class="logo" href="<?= url('index.php') ?>" aria-label="<?= e(BRAND_NAME) ?> inicio">
      <?php include __DIR__ . '/logo.php'; ?>
      <span><?= e(BRAND_NAME) ?></span>
    </a>
    <ul class="nav-links">
      <li><a href="<?= url('index.php#ecosistema') ?>">Ecosistema</a></li>
      <li><a href="<?= url('index.php#sistema') ?>">Soluciones</a></li>
      <li><a href="<?= url('index.php#studio') ?>">Studio</a></li>
      <li><a href="<?= url('index.php#precios') ?>">Precios</a></li>
      <li><a href="<?= url('index.php#faq') ?>">Preguntas</a></li>
    </ul>
    <div class="nav-actions">
      <span class="lang-pill hide-m" title="Idioma">ES</span>
      <?php if ($user && (int) $user['is_admin'] === 1): ?>
        <a class="btn btn-ghost btn-sm hide-m" href="<?= url('admin/index.php') ?>">Administración</a>
      <?php endif; ?>
      <?php if ($user): ?>
        <a class="btn btn-ghost btn-sm hide-m" href="<?= url('portal/index.php') ?>">Mi portal</a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm hide-m" href="<?= url('portal/login.php') ?>">Entrar</a>
      <?php endif; ?>
      <a class="btn btn-primary btn-sm" href="<?= url('index.php#precios') ?>">Comprar licencia</a>
      <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </div>
</header>
