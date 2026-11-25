<?php
/**
 * Administración > Facturación: datos del expedidor y reglas de las facturas.
 * Los cambios solo afectan a las facturas que se emitan después de guardarlos; las ya emitidas
 * quedan congeladas con los datos que tenían.
 */
require_once __DIR__ . '/../includes/pedidos.php';

$admin = require_admin();
$errors = [];
$s = invoice_settings(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'legal_name'  => trim((string) ($_POST['legal_name'] ?? '')),
        'tax_id'      => strtoupper(trim((string) ($_POST['tax_id'] ?? ''))),
        'address'     => trim((string) ($_POST['address'] ?? '')),
        'registry'    => trim((string) ($_POST['registry'] ?? '')),
        'email'       => strtolower(trim((string) ($_POST['email'] ?? ''))),
        'numbering'   => ($_POST['numbering'] ?? '') === 'series' ? 'series' : 'receipt',
        'series'      => strtoupper(trim((string) ($_POST['series'] ?? ''))),
        'next_number' => (int) preg_replace('/\D/', '', (string) ($_POST['next_number'] ?? '')),
        'regime'      => ($_POST['regime'] ?? '') === 'not_subject' ? 'not_subject' : 'subject',
        'vat_rate'    => parse_decimal((string) ($_POST['vat_rate'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if (mb_strlen($data['legal_name']) < 3) $errors[] = 'Escriba la razón social.';
    if ($data['address'] === '') $errors[] = 'Escriba el domicilio fiscal.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo de facturación no es válido.';
    if ($data['numbering'] === 'series') {
        if (!preg_match('/^[A-Z0-9]{1,10}$/', $data['series'])) $errors[] = 'La serie debe tener de 1 a 10 letras o números, sin espacios.';
        if ($data['next_number'] < 1) $errors[] = 'El próximo número debe ser 1 o mayor.';
    }
    if ($data['regime'] === 'subject' && ($data['vat_rate'] <= 0 || $data['vat_rate'] > 30)) {
        $errors[] = 'El tipo de IVA debe estar entre 0 y 30 %.';
    }
    if (mb_strlen($data['description']) < 5) $errors[] = 'Escriba la descripción de la operación.';

    if (!$errors) {
        save_invoice_settings($data, $admin['email']);
        log_event('invoice_settings', ['por' => $admin['email']] + $data);
        flash('success', 'Datos de facturación guardados. Se aplican a las facturas que se emitan desde ahora.');
        redirect('admin/facturacion.php');
    }
    $s = array_merge($s, $data);
}

$issued = (int) db()->query('SELECT COUNT(*) FROM orders WHERE invoice_data IS NOT NULL')->fetchColumn();
$pending = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'approved' AND invoice_data IS NULL")->fetchColumn();

$pageTitle = 'Facturación | Administración';
$extraCss = ['assets/css/portal.css'];
$adminSection = 'facturacion';
$adminTitle = 'Facturación';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <?php if ($errors): ?>
      <div class="alert alert-error">Revise los datos:<ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="alert alert-info">Facturas emitidas: <?= $issued ?> · Pedidos pagados con factura aún sin emitir: <?= $pending ?>.
      Cada factura se emite la primera vez que el cliente (o un administrador) la abre y desde ese momento no cambia.</div>

    <form class="card" method="post" novalidate>
      <?= csrf_field() ?>
      <h2>Datos del expedidor</h2>
      <p class="muted" style="margin-top:-6px">Son los que aparecen en la factura. Solo afectan a las facturas que se emitan después de guardarlos.
        <?php if ($s['updated_at']): ?><br>Última modificación: <?= fmt_date($s['updated_at'], true) ?> · <?= e($s['updated_by']) ?><?php endif; ?></p>

      <div class="form-grid" style="margin-top:18px">
        <div class="field"><label for="legal_name">Razón social</label>
          <input class="input" id="legal_name" name="legal_name" value="<?= e($s['legal_name']) ?>" required></div>
        <div class="field"><label for="tax_id">NIF</label>
          <input class="input" id="tax_id" name="tax_id" value="<?= e($s['tax_id']) ?>" autocomplete="off">
          <div class="hint">Mientras esté vacío no se emiten facturas.</div></div>

        <div class="field full"><label for="address">Domicilio fiscal</label>
          <input class="input" id="address" name="address" value="<?= e($s['address']) ?>" required placeholder="Calle, número, código postal, ciudad, país"></div>

        <div class="field"><label for="registry">Datos registrales <span class="muted">(opcional)</span></label>
          <input class="input" id="registry" name="registry" value="<?= e($s['registry']) ?>" placeholder="Registro Mercantil de..., tomo, folio, hoja"></div>
        <div class="field"><label for="email">Correo de facturación</label>
          <input class="input" id="email" name="email" type="email" value="<?= e($s['email']) ?>" required></div>

        <div class="field full"><label for="numbering">Numeración</label>
          <select class="input" id="numbering" name="numbering" data-numbering>
            <option value="receipt" <?= $s['numbering'] === 'receipt' ? 'selected' : '' ?>>Igual al número del comprobante de pago (CP-AAAA-NNNNN)</option>
            <option value="series" <?= $s['numbering'] === 'series' ? 'selected' : '' ?>>Serie propia correlativa</option>
          </select></div>
        <div class="field" data-series-field><label for="series">Serie</label>
          <input class="input" id="series" name="series" value="<?= e($s['series']) ?>" maxlength="10"></div>
        <div class="field" data-series-field><label for="next_number">Próximo número <span class="muted">(para continuar la numeración de tu contabilidad)</span></label>
          <input class="input" id="next_number" name="next_number" type="number" min="1" value="<?= (int) $s['next_number'] ?>">
          <div class="hint">La próxima factura será <strong data-next-preview><?= e($s['series'] . '-' . (int) $s['next_number']) ?></strong>.</div></div>

        <div class="field"><label for="regime">Régimen por defecto</label>
          <select class="input" id="regime" name="regime" data-regime>
            <option value="subject" <?= $s['regime'] === 'subject' ? 'selected' : '' ?>>Sujeta a IVA (IVA incluido en el precio)</option>
            <option value="not_subject" <?= $s['regime'] === 'not_subject' ? 'selected' : '' ?>>No sujeta (reglas de localización)</option>
          </select></div>
        <div class="field" data-vat-field><label for="vat_rate">Tipo de IVA (%)</label>
          <input class="input" id="vat_rate" name="vat_rate" inputmode="decimal" value="<?= e(rtrim(rtrim(number_format((float) $s['vat_rate'], 2, ',', ''), '0'), ',')) ?>"></div>

        <div class="field full"><label for="description">Descripción por defecto de la operación</label>
          <input class="input" id="description" name="description" value="<?= e($s['description']) ?>" required>
          <div class="hint">En la factura se completa con los usuarios, sedes, periodo y código de la licencia.</div></div>
      </div>

      <button class="btn btn-blue" style="margin-top:22px" type="submit">Guardar datos de facturación</button>
    </form>
  </div>
</main>
<script>
  (function () {
    var numbering = document.querySelector('[data-numbering]');
    var regime = document.querySelector('[data-regime]');
    var series = document.getElementById('series');
    var next = document.getElementById('next_number');
    var preview = document.querySelector('[data-next-preview]');
    function update() {
      document.querySelectorAll('[data-series-field]').forEach(function (el) { el.hidden = numbering.value !== 'series'; });
      document.querySelector('[data-vat-field]').hidden = regime.value !== 'subject';
      preview.textContent = (series.value.toUpperCase() || '?') + '-' + (parseInt(next.value, 10) || 1);
    }
    [numbering, regime, series, next].forEach(function (el) { el.addEventListener('input', update); });
    update();
  })();
</script>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
