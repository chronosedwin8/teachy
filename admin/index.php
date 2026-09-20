<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();
$pdo = db();

$revenue = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE status = 'approved'")->fetchColumn();
$revenueMonth = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE status = 'approved' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
$byStatus = $pdo->query('SELECT status, COUNT(*) c FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$activeLicenses = (int) $pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 'active'")->fetchColumn();
$byPlan = $pdo->query("SELECT plan_code, COUNT(*) c FROM licenses WHERE status = 'active' GROUP BY plan_code")->fetchAll(PDO::FETCH_KEY_PAIR);
$customers = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
$members = (int) $pdo->query('SELECT COUNT(*) FROM license_members')->fetchColumn();
$seats = (int) $pdo->query("SELECT COALESCE(SUM(seats), 0) FROM licenses WHERE status = 'active'")->fetchColumn();
$expiring = $pdo->query("SELECT l.*, u.institution, u.email FROM licenses l JOIN users u ON u.id = l.user_id
                         WHERE l.status = 'active' AND l.expires_at < DATE_ADD(NOW(), INTERVAL 30 DAY) ORDER BY l.expires_at LIMIT 10")->fetchAll();
$recent = $pdo->query('SELECT o.*, u.name, u.email, u.institution FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 8')->fetchAll();

$pageTitle = 'Administración | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css'];
$adminSection = 'index';
$adminTitle = 'Resumen del negocio';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="stats">
      <div class="stat"><div class="ico-s" style="background:var(--teal-3)">💰</div><small>Ventas aprobadas</small><b style="font-size:1.45rem"><?= money($revenue) ?></b></div>
      <div class="stat"><div class="ico-s" style="background:var(--lav)">📅</div><small>Ventas este mes</small><b style="font-size:1.45rem"><?= money($revenueMonth) ?></b></div>
      <div class="stat"><div class="ico-s" style="background:var(--sun-2)">🔑</div><small>Licencias activas</small><b><?= $activeLicenses ?></b>
        <small><?php foreach (plans() as $code => $p): ?><?= e($p['name']) ?>: <?= (int) ($byPlan[$code] ?? 0) ?><br><?php endforeach; ?></small></div>
      <div class="stat"><div class="ico-s" style="background:var(--rose)">👥</div><small>Clientes · usuarios asignados</small><b><?= $customers ?> · <?= $members ?></b><small><?= $seats ?> cupos vendidos</small></div>
    </div>

    <div class="stats">
      <?php foreach (['approved', 'pending', 'in_process', 'rejected'] as $s): [$lbl, $cls] = status_label($s); ?>
        <a class="stat" href="<?= url('admin/pedidos.php?status=' . $s) ?>" style="color:inherit"><small>Pedidos <?= mb_strtolower($lbl) ?></small><b><?= (int) ($byStatus[$s] ?? 0) ?></b></a>
      <?php endforeach; ?>
    </div>

    <div class="detail-grid" style="grid-template-columns:1.6fr 1fr">
      <div class="card">
        <div class="card-head"><h2>Últimos pedidos</h2><a class="btn btn-outline btn-sm" href="<?= url('admin/pedidos.php') ?>">Ver todos</a></div>
        <?php if (!$recent): ?><div class="empty">Aún no hay pedidos.</div><?php else: ?>
        <div class="table-wrap"><table class="table">
          <thead><tr><th>Cliente</th><th>Plan</th><th>Valor</th><th>Estado</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $o): [$st, $cls] = status_label($o['status']); ?>
            <tr>
              <td><b><?= e($o['institution'] ?: $o['name']) ?></b><small><?= e($o['email']) ?> · <?= fmt_date($o['created_at'], true) ?></small></td>
              <td><?= e(plan($o['plan_code'])['name'] ?? $o['plan_code']) ?></td>
              <td style="white-space:nowrap"><?= money($o['amount']) ?></td>
              <td><span class="badge badge-<?= $cls ?>"><?= $st ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
        <?php endif; ?>
      </div>
      <div class="card">
        <h2>Vencen en 30 días</h2>
        <?php if (!$expiring): ?><div class="empty">Ninguna licencia vence pronto.</div><?php endif; ?>
        <?php foreach ($expiring as $l): ?>
          <a class="art-row" href="<?= url('admin/licencias.php?edit=' . $l['id']) ?>" style="color:inherit">
            <div class="grow"><?= e($l['institution'] ?: $l['email']) ?><small><?= e($l['license_key']) ?></small></div>
            <span class="chip chip-rose"><?= fmt_date($l['expires_at']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
