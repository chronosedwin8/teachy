<?php
require_once __DIR__ . '/../includes/mercadopago.php';

$admin = require_admin();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([(int) ($_POST['order_id'] ?? 0)]);
    $order = $stmt->fetch();

    if ($action === 'grant') {
        // Licencia otorgada manualmente (cortesía, transferencia, convenio…)
        $u = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $u->execute([strtolower(trim((string) ($_POST['email'] ?? '')))]);
        $userId = $u->fetchColumn();
        $p = plan((string) ($_POST['plan'] ?? ''));
        $amount = (float) preg_replace('/[^0-9]/', '', (string) ($_POST['amount'] ?? '0'));
        if (!$userId) $errors[] = 'No existe un usuario con ese correo. Créelo primero en la sección Usuarios.';
        if (!$p) $errors[] = 'Seleccione un plan válido.';
        if (!$errors) {
            $ref = 'MAN-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $pdo->prepare('INSERT INTO orders (user_id, plan_code, amount, currency, external_reference, payment_method, mp_status_detail) VALUES (?, ?, ?, ?, ?, "manual", ?)')
                ->execute([$userId, $p['code'], $amount, CURRENCY, $ref, 'Otorgada por ' . $admin['email']]);
            apply_payment_to_order(['status' => 'approved', 'external_reference' => $ref, 'payment_method_id' => 'manual', 'status_detail' => 'Otorgada por ' . $admin['email']], true);
            flash('success', 'Licencia otorgada correctamente (' . $ref . ').');
            redirect('admin/pedidos.php');
        }
    } elseif (!$order) {
        flash('error', 'Pedido no encontrado.');
        redirect('admin/pedidos.php');
    } elseif ($action === 'approve') {
        apply_payment_to_order(['status' => 'approved', 'external_reference' => $order['external_reference'], 'payment_method_id' => $order['payment_method'] ?: 'manual', 'status_detail' => 'Aprobado manualmente por ' . $admin['email']], true);
        flash('success', 'Pedido aprobado y licencia activada.');
        redirect('admin/pedidos.php');
    } elseif ($action === 'cancel') {
        apply_payment_to_order(['status' => 'cancelled', 'external_reference' => $order['external_reference'], 'status_detail' => 'Cancelado por ' . $admin['email']], true);
        flash('success', 'Pedido cancelado. Si tenía licencia, quedó suspendida.');
        redirect('admin/pedidos.php');
    } elseif ($action === 'sync') {
        if (is_demo_mode()) {
            flash('warn', 'En el entorno local no se consulta Mercado Pago (pagos simulados).');
        } else {
            try {
                $payment = $order['mp_payment_id'] && ctype_digit($order['mp_payment_id']) ? mp_get_payment($order['mp_payment_id']) : null;
                $payment = $payment ?: mp_search_payment_by_reference($order['external_reference']);
                if ($payment) {
                    $updated = apply_payment_to_order($payment);
                    flash('success', 'Sincronizado con Mercado Pago: ' . status_label($updated['status'] ?? $payment['status'])[0] . '.');
                } else {
                    flash('info', 'Mercado Pago no registra pagos para este pedido.');
                }
            } catch (Throwable $ex) {
                flash('error', 'Error consultando Mercado Pago: ' . $ex->getMessage());
            }
        }
        redirect('admin/pedidos.php');
    } elseif ($action === 'delete') {
        if ($order['status'] === 'approved') {
            flash('error', 'No se puede eliminar un pedido aprobado; cancélelo primero.');
        } else {
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$order['id']]);
            flash('success', 'Pedido eliminado.');
        }
        redirect('admin/pedidos.php');
    }
}

$status = (string) ($_GET['status'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT o.*, u.name, u.email, u.institution, l.id AS license_id
        FROM orders o JOIN users u ON u.id = o.user_id LEFT JOIN licenses l ON l.order_id = o.id WHERE 1';
$params = [];
if ($status !== '') { $sql .= ' AND o.status = ?'; $params[] = $status; }
if ($q !== '') {
    $sql .= ' AND (o.external_reference LIKE ? OR u.email LIKE ? OR u.institution LIKE ? OR o.mp_payment_id LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
$orders = $pdo->prepare($sql . ' ORDER BY o.created_at DESC LIMIT 300');
$orders->execute($params);
$orders = $orders->fetchAll();
$users = $pdo->query('SELECT email, institution FROM users ORDER BY email')->fetchAll();

$pageTitle = 'Pedidos | Administración';
$extraCss = ['assets/css/portal.css'];
$adminSection = 'pedidos';
$adminTitle = 'Pedidos y pagos';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="card">
      <div class="card-head">
        <h2>Pedidos (<?= count($orders) ?>)</h2>
        <div class="toolbar">
          <form method="get" class="toolbar">
            <select class="input search-input" name="status" onchange="this.form.submit()">
              <option value="">Todos los estados</option>
              <?php foreach (['approved', 'pending', 'in_process', 'rejected', 'cancelled', 'refunded'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= status_label($s)[0] ?></option>
              <?php endforeach; ?>
            </select>
            <input class="input search-input" name="q" value="<?= e($q) ?>" placeholder="Referencia, correo, institución, pago MP">
          </form>
          <button class="btn btn-blue btn-sm" type="button" data-toggle="grantBox">+ Otorgar licencia</button>
        </div>
      </div>

      <div id="grantBox" <?= $errors ? '' : 'hidden' ?> style="background:var(--bg-soft);border-radius:18px;padding:22px;margin-bottom:22px">
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="grant">
          <div class="form-grid" style="grid-template-columns:2fr 1.2fr 1fr auto;align-items:end">
            <div class="field"><label>Correo del cliente</label>
              <input class="input" name="email" list="userEmails" required value="<?= old('email') ?>">
              <datalist id="userEmails"><?php foreach ($users as $u): ?><option value="<?= e($u['email']) ?>"><?= e($u['institution']) ?></option><?php endforeach; ?></datalist></div>
            <div class="field"><label>Plan</label>
              <select class="input" name="plan"><?php foreach (plans() as $code => $p): ?><option value="<?= $code ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Valor recibido (COP)</label><input class="input" name="amount" value="<?= old('amount', '0') ?>" inputmode="numeric"></div>
            <button class="btn btn-primary" type="submit">Otorgar</button>
          </div>
          <div class="hint" style="margin-top:8px;font-size:.82rem;color:var(--slate-2)">Crea un pedido aprobado y activa la licencia de inmediato (pagos por transferencia, convenios o cortesías).</div>
        </form>
      </div>

      <?php if (!$orders): ?><div class="empty">No hay pedidos con esos filtros.</div><?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Pedido</th><th>Cliente</th><th>Plan</th><th>Valor</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): [$st, $cls] = status_label($o['status']); ?>
          <tr>
            <td><b><?= e($o['external_reference']) ?></b>
              <small><?= fmt_date($o['created_at'], true) ?></small>
              <?php if ($o['mp_payment_id']): ?><small>MP #<?= e($o['mp_payment_id']) ?> · <?= e($o['payment_method']) ?></small><?php endif; ?>
              <?php if ($o['mp_status_detail']): ?><small><?= e($o['mp_status_detail']) ?></small><?php endif; ?></td>
            <td><b><?= e($o['institution'] ?: $o['name']) ?></b><small><?= e($o['email']) ?></small></td>
            <td><?= e(plan($o['plan_code'])['name'] ?? $o['plan_code']) ?><?= $o['is_demo'] ? '<small>Simulado</small>' : '' ?></td>
            <td style="white-space:nowrap"><?= money($o['amount']) ?></td>
            <td><span class="badge badge-<?= $cls ?>"><?= $st ?></span></td>
            <td>
              <div class="toolbar" style="gap:6px;flex-wrap:nowrap">
                <?php if ($o['license_id']): ?><a class="icon-btn" href="<?= url('portal/licencia.php?id=' . $o['license_id']) ?>">Licencia</a><?php endif; ?>
                <?php foreach ([['sync', 'Sincronizar MP', ''], ['approve', 'Aprobar', '¿Aprobar este pedido manualmente y activar la licencia?'], ['cancel', 'Cancelar', '¿Cancelar este pedido? La licencia quedará suspendida.'], ['delete', 'Eliminar', '¿Eliminar definitivamente este pedido?']] as [$act, $label, $confirm]):
                    if ($act === 'approve' && $o['status'] === 'approved') continue;
                    if ($act === 'cancel' && in_array($o['status'], ['cancelled', 'refunded'], true)) continue;
                    if ($act === 'delete' && $o['status'] === 'approved') continue;
                    if ($act === 'sync' && str_starts_with($o['external_reference'], 'MAN-')) continue; ?>
                  <form method="post" class="inline" <?= $confirm ? 'data-confirm="' . e($confirm) . '"' : '' ?>>
                    <?= csrf_field() ?><input type="hidden" name="action" value="<?= $act ?>"><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                    <button class="icon-btn" type="submit"><?= $label ?></button>
                  </form>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
