<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();
global $PLANS;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = (string) ($_POST['code'] ?? '');
    if (!isset($PLANS[$code])) {
        flash('error', 'Plan no válido.');
        redirect('admin/planes.php');
    }
    $before = plan($code);

    if (($_POST['action'] ?? '') === 'reset') {
        db()->prepare('DELETE FROM plan_settings WHERE code = ?')->execute([$code]);
        log_admin_change($code, $before, $PLANS[$code], $admin['email'], 'restaurado');
        flash('success', 'Se restauraron los valores originales de ' . $PLANS[$code]['name'] . '.');
        redirect('admin/planes.php');
    }

    $price = (int) preg_replace('/\D/', '', (string) ($_POST['price'] ?? ''));
    $data = [
        'name'        => trim((string) ($_POST['name'] ?? '')),
        'price'       => $price,
        'seats'       => (int) ($_POST['seats'] ?? 0),
        'campuses'    => (int) ($_POST['campuses'] ?? 0),
        'months'      => (int) ($_POST['months'] ?? 0),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'features'    => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($_POST['features'] ?? ''))))),
    ];

    if (mb_strlen($data['name']) < 3) $errors[] = 'El nombre del plan es demasiado corto.';
    if ($price < 5000 || $price > 50000000) $errors[] = 'El precio debe estar entre $5.000 y $50.000.000 COP (límites de los medios de pago).';
    if ($data['seats'] < 1 || $data['seats'] > 100000) $errors[] = 'Los usuarios incluidos deben estar entre 1 y 100.000.';
    if ($data['campuses'] < 1 || $data['campuses'] > 1000) $errors[] = 'Las sedes deben estar entre 1 y 1.000.';
    if ($data['months'] < 1 || $data['months'] > 60) $errors[] = 'La vigencia debe estar entre 1 y 60 meses.';
    if (!$data['features']) $errors[] = 'Escriba al menos una característica incluida.';

    if (!$errors) {
        db()->prepare('INSERT INTO plan_settings (code, data, updated_by) VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE data = VALUES(data), updated_by = VALUES(updated_by)')
            ->execute([$code, json_encode($data, JSON_UNESCAPED_UNICODE), $admin['email']]);
        log_admin_change($code, $before, $data, $admin['email'], 'actualizado');
        flash('success', $data['name'] . ' actualizado. El nuevo precio ya se muestra en el sitio.');
        redirect('admin/planes.php');
    }
    $editing = $code;
}

/** Deja constancia del cambio en el registro (visible en Administración > Registro de pagos). */
function log_admin_change(string $code, array $before, array $after, string $by, string $action): void
{
    db()->prepare('INSERT INTO payment_logs (source, payload) VALUES (?, ?)')->execute(['plan_change', json_encode([
        'plan' => $code, 'accion' => $action, 'por' => $by,
        'precio_antes' => $before['price'], 'precio_nuevo' => $after['price'],
        'usuarios_antes' => $before['seats'], 'usuarios_nuevo' => $after['seats'],
    ], JSON_UNESCAPED_UNICODE)]);
}

$meta = db()->query('SELECT code, updated_by, updated_at FROM plan_settings')->fetchAll(PDO::FETCH_UNIQUE);
$current = plans(true);

$pageTitle = 'Planes y precios | Administración';
$extraCss = ['assets/css/portal.css'];
$adminSection = 'planes';
$adminTitle = 'Planes y precios';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="alert alert-info">Los cambios se aplican de inmediato en la página de precios y en los pedidos nuevos.
      Las licencias ya vendidas conservan sus condiciones. Los pedidos que aún no se han pagado se cobran con el precio vigente.</div>

    <div class="detail-grid" style="grid-template-columns:1fr 1fr">
      <?php foreach ($current as $code => $p):
          $isEditing = ($editing ?? '') === $code;
          $v = fn(string $k, $default) => $isEditing ? ($_POST[$k] ?? $default) : $default; ?>
        <form class="card" method="post" novalidate>
          <?= csrf_field() ?><input type="hidden" name="code" value="<?= e($code) ?>">
          <div class="card-head">
            <h2><?= e($p['name']) ?></h2>
            <?php if (isset($meta[$code])): ?>
              <span class="chip chip-blue">Editado</span>
            <?php else: ?>
              <span class="chip chip-teal">Valores originales</span>
            <?php endif; ?>
          </div>
          <?php if (isset($meta[$code])): ?>
            <p class="muted" style="font-size:.85rem;margin-top:-8px">Última modificación: <?= fmt_date($meta[$code]['updated_at'], true) ?> · <?= e($meta[$code]['updated_by']) ?></p>
          <?php endif; ?>

          <div class="price-edit">
            <label for="price-<?= e($code) ?>">Precio (COP)</label>
            <div class="price-input"><span>$</span>
              <input class="input" id="price-<?= e($code) ?>" name="price" inputmode="numeric" data-price data-seats-target="seats-<?= e($code) ?>"
                     value="<?= e(number_format((int) preg_replace('/\D/', '', (string) $v('price', $p['price'])), 0, ',', '.')) ?>" required></div>
            <div class="hint" data-price-hint></div>
          </div>

          <div class="form-grid" style="margin-top:18px">
            <div class="field full"><label>Nombre del plan</label><input class="input" name="name" value="<?= e($v('name', $p['name'])) ?>" required></div>
            <div class="field"><label>Usuarios incluidos</label><input class="input" type="number" min="1" id="seats-<?= e($code) ?>" name="seats" value="<?= (int) $v('seats', $p['seats']) ?>" required></div>
            <div class="field"><label>Sedes</label><input class="input" type="number" min="1" name="campuses" value="<?= (int) $v('campuses', $p['campuses']) ?>" required></div>
            <div class="field"><label>Vigencia (meses)</label><input class="input" type="number" min="1" max="60" name="months" value="<?= (int) $v('months', $p['months']) ?>" required></div>
            <div class="field full"><label>Descripción corta</label><input class="input" name="description" value="<?= e($v('description', $p['description'])) ?>"></div>
            <div class="field full"><label>Características (una por línea)</label>
              <textarea class="input" name="features" rows="8"><?= e($isEditing ? (string) ($_POST['features'] ?? '') : implode("\n", $p['features'])) ?></textarea></div>
          </div>

          <div class="toolbar" style="margin-top:20px;justify-content:space-between">
            <button class="btn btn-blue" type="submit" name="action" value="save">Guardar cambios</button>
            <?php if (isset($meta[$code])): ?>
              <button class="btn btn-ghost btn-sm" type="submit" name="action" value="reset" formnovalidate
                      onclick="return confirm('¿Restaurar los valores originales de este plan?')">Restaurar originales</button>
            <?php endif; ?>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<style>
  .price-edit label { display: block; font-weight: 700; font-size: .9rem; margin-bottom: 7px; color: var(--ink-2); }
  .price-input { display: flex; align-items: center; gap: 8px; }
  .price-input span { font-size: 2rem; font-weight: 700; color: var(--slate); }
  .price-input .input { font-size: 2rem; font-weight: 700; padding: 10px 16px; letter-spacing: -.01em; }
  [data-price-hint] { margin-top: 8px; font-size: .85rem; font-weight: 600; color: var(--slate); }
  [data-price-hint] .warn { color: #a67400; display: block; }
</style>
<script>
  // Formato de miles mientras se escribe y avisos sobre límites de los medios de pago
  document.querySelectorAll('[data-price]').forEach(function (input) {
    var hint = input.closest('.price-edit').querySelector('[data-price-hint]');
    var seats = document.getElementById(input.dataset.seatsTarget);
    function update() {
      var n = parseInt(input.value.replace(/\D/g, ''), 10) || 0;
      input.value = n ? n.toLocaleString('es-CO') : '';
      var s = parseInt(seats.value, 10) || 1;
      var html = 'Equivale a $' + Math.round(n / s).toLocaleString('es-CO') + ' COP por usuario.';
      if (n > 8000000) html += '<span class="warn">Supera $8.000.000: Efecty no estará disponible para este plan.</span>';
      if (n > 5000000) html += '<span class="warn">Supera $5.000.000: las tarjetas Codensa no aceptan este valor.</span>';
      hint.innerHTML = html;
    }
    input.addEventListener('input', update);
    seats.addEventListener('input', update);
    update();
  });
</script>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
