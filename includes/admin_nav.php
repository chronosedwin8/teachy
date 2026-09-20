<?php
/** Navegación del panel de administración. Requiere $adminSection. */
$sections = [
    'index'     => 'Resumen',
    'pedidos'   => 'Pedidos',
    'licencias' => 'Licencias',
    'planes'    => 'Precios',
    'usuarios'  => 'Usuarios',
    'logs'      => 'Registro de pagos',
];
?>
<div class="portal-head">
  <div>
    <span class="eyebrow"><span class="dot" style="background:var(--sun)"></span> Administración</span>
    <h1><?= e($adminTitle ?? 'Panel de administración') ?></h1>
    <p><?= IS_PRODUCTION ? 'Producción · ' . e(PROD_DOMAIN) : 'Entorno local · pagos simulados' ?></p>
  </div>
  <div class="toolbar">
    <a class="btn btn-ghost btn-sm" href="<?= url('portal/logout.php') ?>">Cerrar sesión</a>
  </div>
</div>
<nav class="portal-tabs">
  <?php foreach ($sections as $key => $label): ?>
    <a class="<?= $adminSection === $key ? 'on' : '' ?>" href="<?= url('admin/' . $key . '.php') ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php include __DIR__ . '/flash.php'; ?>
