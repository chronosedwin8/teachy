<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$stmt = db()->prepare('SELECT l.*, o.external_reference, o.amount, o.paid_at, o.mp_payment_id, o.is_demo,
                              u.email AS owner_email, u.institution AS owner_institution
                       FROM licenses l JOIN orders o ON o.id = l.order_id JOIN users u ON u.id = l.user_id WHERE l.id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$license = $stmt->fetch();
// El titular gestiona su licencia; un administrador puede gestionar cualquiera
if (!$license || ((int) $license['user_id'] !== (int) $user['id'] && (int) $user['is_admin'] !== 1)) {
    flash('error', 'La licencia no existe o no pertenece a su cuenta.');
    redirect('portal/index.php');
}
$ownerEmail = strtolower($license['owner_email']);
$plan = plan($license['plan_code']);
$roles = member_roles();
$selfUrl = 'portal/licencia.php?id=' . $license['id'];
$errors = [];

function count_members(int $licenseId): int
{
    $c = db()->prepare('SELECT COUNT(*) FROM license_members WHERE license_id = ?');
    $c->execute([$licenseId]);
    return (int) $c->fetchColumn();
}

/** Valida y agrega un usuario a la licencia. Devuelve null si se agregó o el mensaje de error. */
function add_member(array $license, string $name, string $email, string $role, string $campus): ?string
{
    $email = strtolower(trim($email));
    $name = trim($name);
    if (mb_strlen($name) < 3) return "Nombre inválido para \"$email\".";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Correo inválido: \"$email\".";
    if (!array_key_exists($role, member_roles())) $role = 'docente';
    if (count_members((int) $license['id']) >= (int) $license['seats']) return 'No hay cupos disponibles en la licencia.';
    try {
        db()->prepare('INSERT INTO license_members (license_id, name, email, role, campus) VALUES (?, ?, ?, ?, ?)')
            ->execute([$license['id'], mb_substr($name, 0, 120), $email, $role, $campus !== '' ? mb_substr($campus, 0, 120) : null]);
    } catch (PDOException $ex) {
        if ($ex->errorInfo[1] === 1062) return "El correo $email ya está en la licencia.";
        throw $ex;
    }
    return null;
}

// ---- Exportar CSV ----
if (($_GET['export'] ?? '') === 'csv') {
    $rows = db()->prepare('SELECT name, email, role, campus, created_at FROM license_members WHERE license_id = ? ORDER BY name');
    $rows->execute([$license['id']]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="usuarios-' . $license['license_key'] . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel reconozca UTF-8
    fputcsv($out, ['Nombre', 'Correo', 'Rol', 'Sede', 'Fecha de registro'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [$r['name'], $r['email'], $roles[$r['role']] ?? $r['role'], $r['campus'], $r['created_at']], ';');
    }
    exit;
}

// ---- Acciones ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['add', 'bulk'], true) && $license['status'] !== 'active') {
        flash('error', 'La licencia no está activa; no es posible agregar usuarios.');
        redirect($selfUrl);
    }

    if ($action === 'add') {
        $err = add_member($license, (string) ($_POST['name'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['role'] ?? 'docente'), trim((string) ($_POST['campus'] ?? '')));
        if ($err === null) {
            flash('success', 'Usuario agregado a la licencia.');
            redirect($selfUrl);
        }
        $errors[] = $err;
    } elseif ($action === 'bulk') {
        $lines = preg_split('/\r\n|\r|\n/', (string) ($_POST['bulk'] ?? ''));
        $added = 0;
        foreach ($lines as $n => $line) {
            if (trim($line) === '') continue;
            $parts = array_map('trim', preg_split('/[;,\t]/', $line));
            $err = add_member($license, $parts[0] ?? '', $parts[1] ?? '', strtolower($parts[2] ?? 'docente'), $parts[3] ?? '');
            if ($err === null) {
                $added++;
            } else {
                $errors[] = 'Línea ' . ($n + 1) . ': ' . $err;
                if (str_starts_with($err, 'No hay cupos')) break;
            }
        }
        if ($added) flash('success', "Se agregaron $added usuario(s).");
        if (!$errors) redirect($selfUrl);
    } elseif ($action === 'update') {
        $role = (string) ($_POST['role'] ?? '');
        if (array_key_exists($role, $roles)) {
            db()->prepare('UPDATE license_members SET role = ?, campus = ? WHERE id = ? AND license_id = ?')
                ->execute([$role, trim((string) ($_POST['campus'] ?? '')) ?: null, (int) ($_POST['member_id'] ?? 0), $license['id']]);
            flash('success', 'Usuario actualizado.');
        }
        redirect($selfUrl);
    } elseif ($action === 'delete') {
        $m = db()->prepare('SELECT email FROM license_members WHERE id = ? AND license_id = ?');
        $m->execute([(int) ($_POST['member_id'] ?? 0), $license['id']]);
        $email = $m->fetchColumn();
        if ($email === $ownerEmail) {
            flash('error', 'No puede retirar al titular de la licencia.');
        } elseif ($email) {
            db()->prepare('DELETE FROM license_members WHERE id = ? AND license_id = ?')->execute([(int) $_POST['member_id'], $license['id']]);
            flash('success', 'Usuario retirado. El cupo quedó disponible.');
        }
        redirect($selfUrl);
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT * FROM license_members WHERE license_id = ?';
$params = [$license['id']];
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR campus LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$members = db()->prepare($sql . ' ORDER BY role = "admin" DESC, created_at DESC');
$members->execute($params);
$members = $members->fetchAll();

$used = count_members((int) $license['id']);
$available = max(0, (int) $license['seats'] - $used);
$pct = $license['seats'] ? min(100, round($used / $license['seats'] * 100)) : 0;
[$st, $cls] = status_label($license['status']);
$daysLeft = max(0, (int) ceil((strtotime($license['expires_at']) - time()) / 86400));

$pageTitle = ($plan['name'] ?? 'Licencia') . ' | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <p style="margin:0 0 16px"><?php if ((int) $license['user_id'] !== (int) $user['id']): ?><a href="<?= url('admin/licencias.php') ?>" style="font-weight:700">← Volver a licencias (administración)</a><?php else: ?><a href="<?= url('portal/index.php') ?>" style="font-weight:700">← Volver a mis licencias</a><?php endif; ?></p>
    <div class="portal-head">
      <div>
        <span class="badge badge-<?= $cls ?>"><?= $st ?></span>
        <h1 style="margin-top:10px"><?= e($plan['name'] ?? $license['plan_code']) ?></h1>
        <p><?= e($license['owner_institution']) ?><?= (int) $license['user_id'] !== (int) $user['id'] ? ' · ' . e($license['owner_email']) : '' ?></p>
      </div>
      <div class="toolbar">
        <a class="btn btn-outline btn-sm" href="<?= url($selfUrl . '&export=csv') ?>">Exportar CSV</a>
      </div>
    </div>

    <?php include __DIR__ . '/../includes/flash.php'; ?>

    <div class="stats">
      <div class="stat"><small>Usuarios incluidos</small><b><?= (int) $license['seats'] ?></b></div>
      <div class="stat"><small>Asignados</small><b><?= $used ?></b></div>
      <div class="stat"><small>Cupos disponibles</small><b style="color:var(--teal)"><?= $available ?></b></div>
      <div class="stat"><small>Días de vigencia</small><b><?= $daysLeft ?></b></div>
    </div>

    <div class="detail-grid">
      <div>
        <div class="card">
          <h2>Detalle de la licencia</h2>
          <p class="muted" style="font-size:.9rem;margin-bottom:10px">Código de licencia</p>
          <div class="key-box"><span><?= e($license['license_key']) ?></span><button type="button" data-copy="<?= e($license['license_key']) ?>">Copiar</button></div>
          <div class="dl" style="margin-top:18px">
            <div><span>Tipo</span><span><?= e($plan['name'] ?? $license['plan_code']) ?></span></div>
            <div><span>Sedes permitidas</span><span><?= (int) $license['campuses'] ?></span></div>
            <div><span>Inicio</span><span><?= fmt_date($license['starts_at']) ?></span></div>
            <div><span>Vencimiento</span><span><?= fmt_date($license['expires_at']) ?></span></div>
            <div><span>Pedido</span><span><?= e($license['external_reference']) ?></span></div>
            <div><span>Valor pagado</span><span><?= money($license['amount']) ?></span></div>
            <div><span>Fecha de pago</span><span><?= fmt_date($license['paid_at'], true) ?></span></div>
            <?php if ($license['mp_payment_id']): ?><div><span>Referencia de pago</span><span>#<?= e($license['mp_payment_id']) ?><?= $license['is_demo'] ? ' (demo)' : '' ?></span></div><?php endif; ?>
          </div>
          <div class="seat-bar" style="margin-top:18px;max-width:none">
            <div class="lbl"><span>Uso de cupos</span><span><?= $pct ?>%</span></div>
            <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
          </div>
        </div>

        <div class="card">
          <h2>Incluido en tu licencia</h2>
          <ul class="checks"><?php foreach ($plan['features'] ?? [] as $feat): ?><li><?= e($feat) ?></li><?php endforeach; ?></ul>
        </div>
      </div>

      <div>
        <?php if ($license['status'] === 'active'): ?>
        <div class="card">
          <div class="card-head">
            <h2>Agregar usuario</h2>
            <button class="btn btn-ghost btn-sm" type="button" data-toggle="bulkBox">Carga masiva</button>
          </div>
          <?php if ($available === 0): ?>
            <div class="alert alert-warn">Usaste todos los cupos de la licencia. Retira un usuario o adquiere otra licencia para agregar más.</div>
          <?php else: ?>
            <form method="post" novalidate>
              <?= csrf_field() ?><input type="hidden" name="action" value="add">
              <div class="form-grid">
                <div class="field"><label for="m_name">Nombre completo</label><input class="input" id="m_name" name="name" value="<?= ($_POST['action'] ?? '') === 'add' ? old('name') : '' ?>" required></div>
                <div class="field"><label for="m_email">Correo electrónico</label><input class="input" id="m_email" name="email" type="email" value="<?= ($_POST['action'] ?? '') === 'add' ? old('email') : '' ?>" required></div>
                <div class="field"><label for="m_role">Rol</label>
                  <select class="input" id="m_role" name="role">
                    <?php foreach ($roles as $k => $label): ?><option value="<?= e($k) ?>" <?= ($_POST['role'] ?? 'docente') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                  </select></div>
                <div class="field"><label for="m_campus">Sede <span class="muted">(opcional)</span></label><input class="input" id="m_campus" name="campus" value="<?= ($_POST['action'] ?? '') === 'add' ? old('campus') : '' ?>" placeholder="Sede principal"></div>
              </div>
              <button class="btn btn-blue" style="margin-top:18px" type="submit">+ Agregar a la licencia</button>
            </form>
          <?php endif; ?>

          <div id="bulkBox" <?= ($_POST['action'] ?? '') === 'bulk' ? '' : 'hidden' ?> style="margin-top:24px;padding-top:22px;border-top:1px solid var(--lav-2)">
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="action" value="bulk">
              <div class="field"><label for="bulk">Pegue un usuario por línea</label>
                <textarea class="input" id="bulk" name="bulk" placeholder="Ana Gómez, ana@colegio.edu.co, docente, Sede norte&#10;Luis Pérez; luis@colegio.edu.co; coordinador"><?= ($_POST['action'] ?? '') === 'bulk' ? old('bulk') : '' ?></textarea>
                <div class="hint">Formato: nombre, correo, rol (opcional), sede (opcional). Separe con coma, punto y coma o tabulación (puede copiar desde Excel). Roles: <?= e(implode(', ', array_keys($roles))) ?>.</div>
              </div>
              <button class="btn btn-primary btn-sm" style="margin-top:14px" type="submit">Importar usuarios</button>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-head">
            <h2>Usuarios de la licencia <span class="muted" style="font-size:1rem">(<?= $used ?>/<?= (int) $license['seats'] ?>)</span></h2>
            <form method="get" class="toolbar">
              <input type="hidden" name="id" value="<?= (int) $license['id'] ?>">
              <input class="input search-input" name="q" value="<?= e($q) ?>" placeholder="Buscar nombre, correo o sede">
            </form>
          </div>
          <?php if (!$members): ?>
            <div class="empty"><div class="big">👥</div><?= $q !== '' ? 'Sin resultados para la búsqueda.' : 'Aún no hay usuarios en esta licencia.' ?></div>
          <?php else: ?>
            <div class="table-wrap">
              <table class="table">
                <thead><tr><th>Usuario</th><th>Rol y sede</th><th>Agregado</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($members as $m): $isOwner = $m['email'] === $ownerEmail; ?>
                    <tr>
                      <td><div class="who"><span class="avatar"><?= e(mb_strtoupper(mb_substr($m['name'], 0, 1))) ?></span><div><b><?= e($m['name']) ?></b><?= $isOwner ? ' <span class="chip chip-blue">Titular</span>' : '' ?><small><?= e($m['email']) ?></small></div></div></td>
                      <td>
                        <form method="post" class="toolbar" style="flex-wrap:nowrap;gap:6px">
                          <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>">
                          <select class="input" name="role" style="padding:8px 10px;font-size:.88rem;min-width:130px" onchange="this.form.submit()">
                            <?php foreach ($roles as $k => $label): ?><option value="<?= e($k) ?>" <?= $m['role'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                          </select>
                          <input class="input" name="campus" value="<?= e($m['campus']) ?>" placeholder="Sede" style="padding:8px 10px;font-size:.88rem;min-width:110px" onchange="this.form.submit()">
                        </form>
                      </td>
                      <td style="white-space:nowrap"><?= fmt_date($m['created_at']) ?></td>
                      <td>
                        <?php if (!$isOwner): ?>
                          <form method="post" class="inline" data-confirm="¿Retirar a <?= e($m['name']) ?> de la licencia?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>">
                            <button class="icon-btn" type="submit">Retirar</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
