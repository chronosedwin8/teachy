<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $isSelf = $targetId === (int) $admin['id'];

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($name) < 3) $errors[] = 'Escriba el nombre.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if (!$errors) {
            try {
                $pdo->prepare('INSERT INTO users (name, email, password_hash, institution, tax_id, phone, city, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), trim((string) ($_POST['institution'] ?? '')) ?: null,
                        trim((string) ($_POST['tax_id'] ?? '')) ?: null, trim((string) ($_POST['phone'] ?? '')) ?: null,
                        trim((string) ($_POST['city'] ?? '')) ?: null, !empty($_POST['is_admin']) ? 1 : 0]);
                flash('success', "Usuario $email creado.");
                redirect('admin/usuarios.php');
            } catch (PDOException $ex) {
                if ($ex->errorInfo[1] !== 1062) throw $ex;
                $errors[] = 'Ya existe un usuario con ese correo.';
            }
        }
    } elseif ($action === 'toggle_admin') {
        if ($isSelf) {
            flash('error', 'No puede quitarse a sí mismo los permisos de administrador.');
        } else {
            $pdo->prepare('UPDATE users SET is_admin = 1 - is_admin WHERE id = ?')->execute([$targetId]);
            flash('success', 'Permisos actualizados.');
        }
        redirect('admin/usuarios.php');
    } elseif ($action === 'password') {
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $targetId]);
            flash('success', 'Contraseña restablecida.');
        }
        redirect('admin/usuarios.php');
    } elseif ($action === 'impersonate') {
        if (!$isSelf) {
            $exists = $pdo->prepare('SELECT id FROM users WHERE id = ?');
            $exists->execute([$targetId]);
            if ($exists->fetchColumn()) {
                $adminId = (int) $admin['id'];
                login_user($targetId);
                $_SESSION['impersonator_id'] = $adminId;
                redirect('portal/index.php');
            }
        }
        redirect('admin/usuarios.php');
    } elseif ($action === 'delete') {
        if ($isSelf) {
            flash('error', 'No puede eliminar su propia cuenta.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
            flash('success', 'Usuario eliminado junto con sus pedidos y licencias.');
        }
        redirect('admin/usuarios.php');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$sql = "SELECT u.*,
          (SELECT COUNT(*) FROM licenses l WHERE l.user_id = u.id AND l.status = 'active') AS licenses,
          (SELECT COALESCE(SUM(amount), 0) FROM orders o WHERE o.user_id = u.id AND o.status = 'approved') AS spent
        FROM users u WHERE 1";
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.email LIKE ? OR u.name LIKE ? OR u.institution LIKE ? OR u.tax_id LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
$users = $pdo->prepare($sql . ' ORDER BY u.is_admin DESC, u.created_at DESC LIMIT 300');
$users->execute($params);
$users = $users->fetchAll();

$pageTitle = 'Usuarios | Administración';
$extraCss = ['assets/css/portal.css'];
$adminSection = 'usuarios';
$adminTitle = 'Usuarios y clientes';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="card">
      <div class="card-head">
        <h2>Usuarios (<?= count($users) ?>)</h2>
        <div class="toolbar">
          <form method="get"><input class="input search-input" name="q" value="<?= e($q) ?>" placeholder="Correo, nombre, institución o NIT"></form>
          <button class="btn btn-blue btn-sm" type="button" data-toggle="newUser">+ Nuevo usuario</button>
        </div>
      </div>

      <div id="newUser" <?= $errors ? '' : 'hidden' ?> style="background:var(--bg-soft);border-radius:18px;padding:22px;margin-bottom:22px">
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="create">
          <div class="form-grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="field"><label>Nombre</label><input class="input" name="name" value="<?= old('name') ?>" required></div>
            <div class="field"><label>Correo</label><input class="input" type="email" name="email" value="<?= old('email') ?>" required></div>
            <div class="field"><label>Contraseña</label><input class="input" type="text" name="password" minlength="8" required autocomplete="off"></div>
            <div class="field"><label>Institución</label><input class="input" name="institution" value="<?= old('institution') ?>"></div>
            <div class="field"><label>NIT</label><input class="input" name="tax_id" value="<?= old('tax_id') ?>"></div>
            <div class="field"><label>Teléfono</label><input class="input" name="phone" value="<?= old('phone') ?>"></div>
            <div class="field"><label>Ciudad</label><input class="input" name="city" value="<?= old('city') ?>"></div>
            <div class="field" style="align-self:end"><label class="check-line"><input type="checkbox" name="is_admin" value="1"> <span>Administrador</span></label></div>
          </div>
          <button class="btn btn-primary" style="margin-top:16px" type="submit">Crear usuario</button>
        </form>
      </div>

      <div class="table-wrap"><table class="table">
        <thead><tr><th>Usuario</th><th>Institución</th><th>Licencias</th><th>Compras</th><th>Último acceso</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $self = (int) $u['id'] === (int) $admin['id']; ?>
          <tr>
            <td><div class="who"><span class="avatar" <?= $u['is_admin'] ? 'style="background:var(--sun-2);color:#7a5600"' : '' ?>><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
              <div><b><?= e($u['name']) ?></b><?= $u['is_admin'] ? ' <span class="chip chip-sun">Admin</span>' : '' ?><?= $self ? ' <span class="chip chip-blue">Tú</span>' : '' ?><small><?= e($u['email']) ?></small></div></div></td>
            <td><?= e($u['institution'] ?: '—') ?><small><?= e(trim(($u['tax_id'] ?? '') . ' ' . ($u['city'] ?? ''))) ?></small></td>
            <td><?= (int) $u['licenses'] ?></td>
            <td style="white-space:nowrap"><?= money($u['spent']) ?></td>
            <td style="white-space:nowrap"><?= fmt_date($u['last_login_at'], true) ?></td>
            <td>
              <div class="toolbar" style="gap:6px;flex-wrap:nowrap">
                <?php if (!$self): ?>
                  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="impersonate"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><button class="icon-btn">Entrar como</button></form>
                  <form method="post" class="inline" data-confirm="<?= $u['is_admin'] ? '¿Quitar permisos de administrador?' : '¿Dar permisos de administrador a este usuario?' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_admin"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><button class="icon-btn"><?= $u['is_admin'] ? 'Quitar admin' : 'Hacer admin' ?></button></form>
                <?php endif; ?>
                <button class="icon-btn" type="button" data-toggle="pw<?= (int) $u['id'] ?>">Contraseña</button>
                <?php if (!$self): ?>
                  <form method="post" class="inline" data-confirm="¿Eliminar a <?= e($u['email']) ?> con todos sus pedidos y licencias? No se puede deshacer."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><button class="icon-btn">Eliminar</button></form>
                <?php endif; ?>
              </div>
              <form method="post" id="pw<?= (int) $u['id'] ?>" hidden class="toolbar" style="margin-top:8px;flex-wrap:nowrap;gap:6px">
                <?= csrf_field() ?><input type="hidden" name="action" value="password"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input class="input" name="password" type="text" minlength="8" placeholder="Nueva contraseña" style="padding:8px 10px;font-size:.88rem" autocomplete="off" required>
                <button class="icon-btn" type="submit">Guardar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
