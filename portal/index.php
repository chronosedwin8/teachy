<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$tab = in_array($_GET['tab'] ?? '', ['licencias', 'pedidos', 'comprar', 'cuenta'], true) ? $_GET['tab'] : 'licencias';
$errors = [];

// ---- Actualizar datos de la cuenta ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $institution = trim((string) ($_POST['institution'] ?? ''));
        $taxId = trim((string) ($_POST['tax_id'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        if (mb_strlen($name) < 3) $errors[] = 'El nombre es demasiado corto.';
        if (mb_strlen($institution) < 3) $errors[] = 'Escriba el nombre de la institución.';
        if (!$errors) {
            db()->prepare('UPDATE users SET name = ?, phone = ?, institution = ?, tax_id = ?, city = ? WHERE id = ?')
                ->execute([$name, $phone, $institution, $taxId, $city, $user['id']]);
            flash('success', 'Datos actualizados.');
            redirect('portal/index.php?tab=cuenta');
        }
    } elseif ($action === 'password') {
        $row = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $row->execute([$user['id']]);
        if (!password_verify((string) ($_POST['current'] ?? ''), (string) $row->fetchColumn())) {
            $errors[] = 'La contraseña actual no es correcta.';
        } elseif (strlen((string) ($_POST['new'] ?? '')) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? '')) {
            $errors[] = 'Las contraseñas nuevas no coinciden.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash((string) $_POST['new'], PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Contraseña actualizada.');
            redirect('portal/index.php?tab=cuenta');
        }
    }
    $tab = 'cuenta';
}

// Marcar como vencidas las licencias cuya vigencia terminó
db()->prepare("UPDATE licenses SET status = 'expired' WHERE user_id = ? AND status = 'active' AND expires_at < NOW()")->execute([$user['id']]);

$licenses = db()->prepare('SELECT l.*, (SELECT COUNT(*) FROM license_members m WHERE m.license_id = l.id) AS used
                           FROM licenses l WHERE l.user_id = ? ORDER BY l.created_at DESC');
$licenses->execute([$user['id']]);
$licenses = $licenses->fetchAll();

$orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

$totalSeats = array_sum(array_column($licenses, 'seats'));
$usedSeats = array_sum(array_column($licenses, 'used'));
$activeCount = count(array_filter($licenses, fn($l) => $l['status'] === 'active'));
$pendingOrders = array_filter($orders, fn($o) => in_array($o['status'], ['pending', 'in_process'], true));

$pageTitle = 'Mi portal | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="portal-head">
      <div>
        <span class="eyebrow"><span class="dot"></span> Portal de clientes</span>
        <h1>Hola, <?= e(explode(' ', $user['name'])[0]) ?></h1>
        <p><?= e($user['institution'] ?: $user['email']) ?></p>
      </div>
      <div class="toolbar">
        <a class="btn btn-outline btn-sm" href="?tab=comprar">Comprar otra licencia</a>
        <a class="btn btn-ghost btn-sm" href="<?= url('portal/logout.php') ?>">Cerrar sesión</a>
      </div>
    </div>

    <?php include __DIR__ . '/../includes/flash.php'; ?>

    <div class="stats">
      <div class="stat"><div class="ico-s" style="background:var(--lav)">🔑</div><small>Licencias activas</small><b><?= $activeCount ?></b></div>
      <div class="stat"><div class="ico-s" style="background:var(--teal-3)">👥</div><small>Usuarios asignados</small><b><?= $usedSeats ?></b></div>
      <div class="stat"><div class="ico-s" style="background:var(--sun-2)">🎟️</div><small>Cupos disponibles</small><b><?= max(0, $totalSeats - $usedSeats) ?></b></div>
      <div class="stat"><div class="ico-s" style="background:var(--rose)">🧾</div><small>Pedidos</small><b><?= count($orders) ?></b></div>
    </div>

    <nav class="portal-tabs">
      <a class="<?= $tab === 'licencias' ? 'on' : '' ?>" href="?tab=licencias">Mis licencias</a>
      <a class="<?= $tab === 'pedidos' ? 'on' : '' ?>" href="?tab=pedidos">Pedidos y pagos</a>
      <a class="<?= $tab === 'comprar' ? 'on' : '' ?>" href="?tab=comprar">Comprar licencia</a>
      <a class="<?= $tab === 'cuenta' ? 'on' : '' ?>" href="?tab=cuenta">Mi cuenta</a>
    </nav>

    <?php if ($tab === 'licencias'): ?>
      <?php if ($pendingOrders): ?>
        <div class="alert alert-warn">Tienes <?= count($pendingOrders) ?> pedido(s) con pago pendiente. La licencia se activará al confirmarse el pago. <a href="?tab=pedidos">Ver pedidos</a></div>
      <?php endif; ?>

      <?php if (!$licenses): ?>
        <div class="card empty">
          <div class="big">🔑</div>
          <h2>Aún no tienes licencias activas</h2>
          <p>Compra una licencia para empezar, o espera a que se apruebe un pago en curso.</p>
          <a class="btn btn-blue" href="?tab=comprar">Comprar una licencia</a>
        </div>
      <?php endif; ?>

      <?php foreach ($licenses as $l): $p = plan($l['plan_code']); [$st, $cls] = status_label($l['status']); $pct = $l['seats'] ? min(100, round($l['used'] / $l['seats'] * 100)) : 0; ?>
        <a class="license-card" href="<?= url('portal/licencia.php?id=' . $l['id']) ?>" style="color:inherit">
          <div class="l-ico" style="background:<?= $l['plan_code'] === 'volumen' ? 'var(--lav)' : 'var(--teal-3)' ?>"><?= $l['plan_code'] === 'volumen' ? '🏛️' : '🏫' ?></div>
          <div>
            <h3><?= e($p['name'] ?? $l['plan_code']) ?> <span class="badge badge-<?= $cls ?>" style="vertical-align:middle;margin-left:6px"><?= $st ?></span></h3>
            <div class="meta">
              <span><?= e($l['license_key']) ?></span>
              <span>Vence: <?= fmt_date($l['expires_at']) ?></span>
              <span><?= (int) $l['campuses'] ?> sede(s)</span>
            </div>
            <div class="seat-bar">
              <div class="lbl"><span><?= (int) $l['used'] ?> de <?= (int) $l['seats'] ?> usuarios</span><span><?= $pct ?>%</span></div>
              <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
            </div>
          </div>
          <span class="btn btn-primary btn-sm">Gestionar usuarios →</span>
        </a>
      <?php endforeach; ?>

    <?php elseif ($tab === 'pedidos'): ?>
      <div class="card">
        <div class="card-head"><h2>Historial de pedidos</h2></div>
        <?php if (!$orders): ?>
          <div class="empty">No hay pedidos registrados.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>Referencia</th><th>Producto</th><th>Fecha</th><th>Valor</th><th>Estado</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($orders as $o): [$st, $cls] = status_label($o['status']); ?>
                  <tr>
                    <td><b><?= e($o['external_reference']) ?></b><?php if ($o['mp_payment_id']): ?><small>Pago MP #<?= e($o['mp_payment_id']) ?></small><?php endif; ?></td>
                    <td><?= e(plan($o['plan_code'])['name'] ?? $o['plan_code']) ?><?php if ($o['is_demo']): ?><small>Pago simulado (demo)</small><?php endif; ?></td>
                    <td><?= fmt_date($o['created_at'], true) ?></td>
                    <td><?= money($o['amount']) ?></td>
                    <td><span class="badge badge-<?= $cls ?>"><?= $st ?></span></td>
                    <td>
                      <?php if (in_array($o['status'], ['pending', 'rejected', 'cancelled'], true)): ?>
                        <a class="btn btn-blue btn-sm" href="<?= url('pago/pagar.php?ref=' . urlencode($o['external_reference'])) ?>">
                          <?= $o['payment_type'] === 'efecty' && $o['status'] === 'pending' ? 'Ver recibo' : 'Pagar' ?></a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    <?php elseif ($tab === 'comprar'): ?>
      <div class="card" style="margin-bottom:22px">
        <h2>Compra una licencia</h2>
        <p class="muted" style="margin:0">Elige el plan que necesitas. Como ya iniciaste sesión, solo confirmarás los datos de tu
          institución y pasarás a la pantalla de pago (tarjeta, PSE o Efecty). Puedes comprar las licencias que quieras: cada
          compra suma una licencia independiente a tu cuenta.</p>
      </div>

      <div class="price-grid" style="margin-top:0">
        <?php foreach (plans() as $code => $p): $featured = $code === 'volumen'; ?>
          <div class="price-card<?= $featured ? ' featured' : '' ?>">
            <?php if ($featured): ?><span class="ribbon">Mejor valor por usuario</span><?php endif; ?>
            <div class="plan-ico" style="background:<?= $featured ? 'var(--lav)' : 'var(--teal-3)' ?>;font-size:1.5rem"><?= $featured ? '🏛️' : '🏫' ?></div>
            <h3><?= e($p['name']) ?></h3>
            <p class="plan-desc"><?= e($p['description']) ?></p>
            <div class="price"><span class="amount">$<?= number_format($p['price'], 0, ',', '.') ?></span><span class="cur"><?= e(CURRENCY) ?> / año</span></div>
            <div class="price-note"><?= (int) $p['seats'] ?> usuarios · <?= (int) $p['campuses'] ?> sede(s) · <?= (int) $p['months'] ?> meses</div>
            <ul class="checks">
              <?php foreach ($p['features'] as $feat): ?><li><?= e($feat) ?></li><?php endforeach; ?>
            </ul>
            <a class="btn <?= $featured ? 'btn-blue' : 'btn-primary' ?> btn-block" href="<?= url('checkout.php?plan=' . $code) ?>">Comprar y pagar</a>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="max-width:940px;margin:26px auto 0"><?php include __DIR__ . '/../includes/intl_notice.php'; ?></div>

    <?php else: ?>
      <div class="detail-grid">
        <form class="card" method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="password">
          <h2>Cambiar contraseña</h2>
          <div class="field" style="margin-bottom:14px"><label>Contraseña actual</label><input class="input" type="password" name="current" required autocomplete="current-password"></div>
          <div class="field" style="margin-bottom:14px"><label>Nueva contraseña</label><input class="input" type="password" name="new" minlength="8" required autocomplete="new-password"></div>
          <div class="field" style="margin-bottom:20px"><label>Confirmar nueva contraseña</label><input class="input" type="password" name="new2" minlength="8" required autocomplete="new-password"></div>
          <button class="btn btn-primary btn-block">Actualizar contraseña</button>
        </form>
        <form class="card" method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="profile">
          <h2>Datos de la cuenta y de la institución</h2>
          <div class="form-grid">
            <div class="field"><label>Nombre del responsable</label><input class="input" name="name" value="<?= e($user['name']) ?>" required></div>
            <div class="field"><label>Correo electrónico</label><input class="input" value="<?= e($user['email']) ?>" disabled></div>
            <div class="field full"><label>Institución / razón social</label><input class="input" name="institution" value="<?= e($user['institution']) ?>" required></div>
            <div class="field"><label>NIT o documento</label><input class="input" name="tax_id" value="<?= e($user['tax_id']) ?>"></div>
            <div class="field"><label>Teléfono</label><input class="input" name="phone" value="<?= e($user['phone']) ?>"></div>
            <div class="field full"><label>Ciudad</label><input class="input" name="city" value="<?= e($user['city']) ?>"></div>
          </div>
          <button class="btn btn-primary" style="margin-top:20px">Guardar cambios</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
