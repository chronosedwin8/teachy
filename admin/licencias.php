<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['license_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $seats = (int) ($_POST['seats'] ?? 0);
        $campuses = (int) ($_POST['campuses'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $expires = (string) ($_POST['expires_at'] ?? '');
        $used = $pdo->prepare('SELECT COUNT(*) FROM license_members WHERE license_id = ?');
        $used->execute([$id]);
        $used = (int) $used->fetchColumn();

        if ($seats < 1) $errors[] = 'Los cupos deben ser al menos 1.';
        if ($seats < $used) $errors[] = "La licencia ya tiene $used usuarios asignados; no puede tener menos cupos.";
        if ($campuses < 1) $errors[] = 'Las sedes deben ser al menos 1.';
        if (!in_array($status, ['active', 'suspended', 'expired'], true)) $errors[] = 'Estado inválido.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) $errors[] = 'Fecha de vencimiento inválida.';
        if (!$errors) {
            $pdo->prepare('UPDATE licenses SET seats = ?, campuses = ?, status = ?, expires_at = ? WHERE id = ?')
                ->execute([$seats, $campuses, $status, $expires . ' 23:59:59', $id]);
            flash('success', 'Licencia actualizada.');
            redirect('admin/licencias.php');
        }
        $_GET['edit'] = $id;
    } elseif ($action === 'renew') {
        $pdo->prepare("UPDATE licenses SET expires_at = DATE_ADD(GREATEST(expires_at, NOW()), INTERVAL 12 MONTH), status = 'active' WHERE id = ?")->execute([$id]);
        flash('success', 'Licencia renovada por 12 meses.');
        redirect('admin/licencias.php');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM licenses WHERE id = ?')->execute([$id]);
        flash('success', 'Licencia eliminada junto con sus usuarios asignados.');
        redirect('admin/licencias.php');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT l.*, u.name, u.email, u.institution, o.external_reference,
               (SELECT COUNT(*) FROM license_members m WHERE m.license_id = l.id) AS used
        FROM licenses l JOIN users u ON u.id = l.user_id JOIN orders o ON o.id = l.order_id WHERE 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (l.license_key LIKE ? OR u.email LIKE ? OR u.institution LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$licenses = $pdo->prepare($sql . ' ORDER BY l.created_at DESC LIMIT 300');
$licenses->execute($params);
$licenses = $licenses->fetchAll();
$editId = (int) ($_GET['edit'] ?? 0);

$pageTitle = 'Licencias | Administración';
$extraCss = ['assets/css/portal.css'];
$adminSection = 'licencias';
$adminTitle = 'Licencias';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="card">
      <div class="card-head">
        <h2>Licencias (<?= count($licenses) ?>)</h2>
        <form method="get"><input class="input search-input" name="q" value="<?= e($q) ?>" placeholder="Código, correo o institución"></form>
      </div>
      <?php if (!$licenses): ?><div class="empty">No hay licencias.</div><?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Licencia</th><th>Cliente</th><th>Cupos</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($licenses as $l): [$st, $cls] = status_label($l['status']); ?>
          <tr>
            <td><b><?= e($l['license_key']) ?></b><small><?= e(plan($l['plan_code'])['name'] ?? $l['plan_code']) ?> · <?= e($l['external_reference']) ?></small></td>
            <td><b><?= e($l['institution'] ?: $l['name']) ?></b><small><?= e($l['email']) ?></small></td>
            <td style="white-space:nowrap"><?= (int) $l['used'] ?> / <?= (int) $l['seats'] ?><small><?= (int) $l['campuses'] ?> sede(s)</small></td>
            <td style="white-space:nowrap"><?= fmt_date($l['starts_at']) ?><small>vence <?= fmt_date($l['expires_at']) ?></small></td>
            <td><span class="badge badge-<?= $cls ?>"><?= $st ?></span></td>
            <td>
              <div class="toolbar" style="gap:6px;flex-wrap:nowrap">
                <a class="icon-btn" href="<?= url('portal/licencia.php?id=' . $l['id']) ?>">Usuarios</a>
                <a class="icon-btn" href="?edit=<?= (int) $l['id'] ?>#edit">Editar</a>
                <form method="post" class="inline" data-confirm="¿Renovar esta licencia por 12 meses más?"><?= csrf_field() ?><input type="hidden" name="action" value="renew"><input type="hidden" name="license_id" value="<?= (int) $l['id'] ?>"><button class="icon-btn">Renovar</button></form>
                <form method="post" class="inline" data-confirm="¿Eliminar la licencia y todos sus usuarios asignados? Esta acción no se puede deshacer."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="license_id" value="<?= (int) $l['id'] ?>"><button class="icon-btn">Eliminar</button></form>
              </div>
            </td>
          </tr>
          <?php if ($editId === (int) $l['id']): ?>
          <tr id="edit"><td colspan="6" style="background:var(--bg-soft)">
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="license_id" value="<?= (int) $l['id'] ?>">
              <div class="form-grid" style="grid-template-columns:repeat(4,1fr) auto;align-items:end">
                <div class="field"><label>Cupos (usuarios)</label><input class="input" type="number" min="1" name="seats" value="<?= (int) ($_POST['seats'] ?? $l['seats']) ?>"></div>
                <div class="field"><label>Sedes</label><input class="input" type="number" min="1" name="campuses" value="<?= (int) ($_POST['campuses'] ?? $l['campuses']) ?>"></div>
                <div class="field"><label>Vence</label><input class="input" type="date" name="expires_at" value="<?= e($_POST['expires_at'] ?? substr($l['expires_at'], 0, 10)) ?>"></div>
                <div class="field"><label>Estado</label><select class="input" name="status">
                  <?php foreach (['active', 'suspended', 'expired'] as $s): ?><option value="<?= $s ?>" <?= ($_POST['status'] ?? $l['status']) === $s ? 'selected' : '' ?>><?= status_label($s)[0] ?></option><?php endforeach; ?>
                </select></div>
                <button class="btn btn-primary" type="submit">Guardar</button>
              </div>
            </form>
          </td></tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
