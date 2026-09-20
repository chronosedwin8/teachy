<?php
/**
 * Pantalla de pago propia (Checkout API). Los campos de la tarjeta son "Secure Fields" de Mercado Pago:
 * iframes aislados con el estilo del sitio, de modo que los datos de la tarjeta nunca pasan por este servidor.
 */
require_once __DIR__ . '/../includes/mercadopago.php';

$user = require_login();
$order = find_order_by_reference((string) ($_GET['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    flash('error', 'Pedido no encontrado.');
    redirect('portal/index.php?tab=pedidos');
}
if ($order['status'] === 'approved') {
    redirect('pago/retorno.php?external_reference=' . urlencode($order['external_reference']));
}
$plan = plan($order['plan_code']);
$live = !is_demo_mode();
$banks = $live ? mp_pse_banks() : ['1007' => 'BANCOLOMBIA', '1051' => 'BANCO DAVIVIENDA', '1001' => 'BANCO DE BOGOTA', '1507' => 'NEQUI'];
[$first, $last] = array_pad(explode(' ', trim($user['name']), 2), 2, '');

// Recibo Efecty vigente
$voucher = $order['payment_type'] === 'efecty' && $order['status'] === 'pending'
    && (!$order['mp_expires_at'] || strtotime($order['mp_expires_at']) > time());
// Un recibo ya emitido conserva su valor; en cualquier otro caso se cobra el precio vigente
if (!$voucher) {
    $order = reprice_unpaid_order($order);
}

$departments = ['Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá', 'Casanare',
    'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño',
    'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre', 'Tolima',
    'Valle del Cauca', 'Vaupés', 'Vichada'];
$idTypes = ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'NIT' => 'NIT', 'Otro' => 'Otro'];

function id_fields(string $prefix, array $idTypes, array $user): string
{
    $isNit = !empty($user['tax_id']) && preg_match('/-/', (string) $user['tax_id']);
    ob_start(); ?>
    <div class="field"><label for="<?= $prefix ?>_idt">Tipo de documento</label>
      <select class="input" id="<?= $prefix ?>_idt" name="id_type">
        <?php foreach ($idTypes as $k => $v): ?><option value="<?= $k ?>"<?= ($isNit && $k === 'NIT') ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field"><label for="<?= $prefix ?>_idn">Número de documento</label>
      <input class="input" id="<?= $prefix ?>_idn" name="id_number" inputmode="numeric" autocomplete="off" required
             value="<?= e($isNit ? preg_replace('/\D/', '', explode('-', (string) $user['tax_id'])[0]) : '') ?>"></div>
    <?php return ob_get_clean();
}

$pageTitle = 'Pagar ' . ($plan['name'] ?? 'licencia') . ' | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css', 'assets/css/pago.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="portal-head">
      <div>
        <span class="eyebrow"><span class="dot"></span> Pago seguro</span>
        <h1>Completa tu pago</h1>
        <p>Pedido <?= e($order['external_reference']) ?> · <?= e($plan['name'] ?? '') ?></p>
      </div>
      <div class="secure-pill">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
        Conexión cifrada
      </div>
    </div>

    <div class="checkout-grid">
      <div>
        <?php include __DIR__ . '/../includes/flash.php'; ?>
        <?php if (!$live): ?>
          <div class="demo-banner">Modo demostración (entorno local): no se cobra dinero y no se piden datos reales de tarjeta. En <?= e(PROD_DOMAIN) ?> esta misma pantalla cobra con Mercado Pago.</div>
        <?php endif; ?>

        <?php if ($voucher): ?>
          <div class="card voucher">
            <div class="voucher-head">
              <span class="method-logo efecty">Efecty</span>
              <span class="badge badge-warn">Pendiente de pago</span>
            </div>
            <h2>Tu recibo está listo</h2>
            <p class="muted">Paga en cualquier punto Efecty antes del <strong><?= fmt_date($order['mp_expires_at'], true) ?></strong>. Tu licencia se activará automáticamente cuando se registre el pago (puede tardar hasta 2 horas).</p>
            <div class="voucher-grid">
              <div><small>Valor a pagar</small><b><?= money($order['amount']) ?></b></div>
              <div><small>Convenio</small><b>Mercado Pago</b></div>
              <div><small>Referencia de pago</small><b><?= e($order['mp_payment_id']) ?></b></div>
            </div>
            <div class="toolbar">
              <?php if ($order['mp_resource_url']): ?><a class="btn btn-primary" href="<?= e($order['mp_resource_url']) ?>" target="_blank" rel="noopener">Ver / imprimir recibo</a><?php endif; ?>
              <button class="btn btn-outline" type="button" data-toggle="payMethods">Pagar con otro medio</button>
            </div>
          </div>
        <?php endif; ?>

        <div class="card pay-card" id="payMethods" <?= $voucher ? 'hidden' : '' ?>>
          <div class="method-tabs" role="tablist">
            <button class="method-tab active" type="button" data-method="card" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></svg>
              <span>Tarjeta<small>Crédito o débito</small></span></button>
            <button class="method-tab" type="button" data-method="pse" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 20h18"/></svg>
              <span>PSE<small>Débito bancario</small></span></button>
            <button class="method-tab" type="button" data-method="efecty" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/></svg>
              <span>Efecty<small>Pago en efectivo</small></span></button>
          </div>

          <div class="pay-error" id="payError" role="alert" hidden></div>

          <!-- ============ TARJETA ============ -->
          <form class="method-panel active" id="form-card" data-method="card" novalidate>
            <div class="card-visual" id="cardVisual">
              <div class="cv-top"><span class="cv-chip"></span><img id="cvBrand" alt="" hidden></div>
              <div class="cv-number">•••• •••• •••• ••••</div>
              <div class="cv-bottom"><span id="cvName">NOMBRE DEL TITULAR</span><span><?= e(BRAND_NAME) ?></span></div>
            </div>
            <div class="form-grid">
              <div class="field full"><label>Número de la tarjeta</label>
                <?php if ($live): ?><div class="input secure-field" id="cardNumber"></div>
                <?php else: ?><div class="input secure-field demo-field">4509 •••• •••• 3704 <small>(demo)</small></div><?php endif; ?></div>
              <div class="field"><label>Vencimiento</label>
                <?php if ($live): ?><div class="input secure-field" id="expirationDate"></div>
                <?php else: ?><div class="input secure-field demo-field">11/30</div><?php endif; ?></div>
              <div class="field"><label>Código de seguridad</label>
                <?php if ($live): ?><div class="input secure-field" id="securityCode"></div>
                <?php else: ?><div class="input secure-field demo-field">•••</div><?php endif; ?></div>
              <div class="field full"><label for="cardholder">Nombre del titular (como aparece en la tarjeta)</label>
                <input class="input" id="cardholder" name="cardholder" autocomplete="cc-name" required value="<?= e(mb_strtoupper($user['name'])) ?>"></div>
              <?= id_fields('card', $idTypes, $user) ?>
              <div class="field full" id="installmentsField"><label for="installments">Cuotas</label>
                <select class="input" id="installments" name="installments"><option value="1">1 cuota de <?= money($order['amount']) ?></option></select>
                <div class="hint" id="installmentsHint">Las cuotas disponibles dependen de tu banco. Ingresa el número de la tarjeta para verlas.</div></div>
            </div>
            <button class="btn btn-blue btn-block pay-btn" type="submit">Pagar <?= money($order['amount']) ?></button>
          </form>

          <!-- ============ PSE ============ -->
          <form class="method-panel" id="form-pse" data-method="pse" novalidate>
            <p class="method-note">Serás dirigido al portal seguro de tu banco para autorizar el débito. Al terminar, regresarás aquí automáticamente.</p>
            <div class="form-grid">
              <div class="field full"><label for="bank">Banco</label>
                <select class="input" id="bank" name="bank" required><option value="">Selecciona tu banco</option>
                  <?php foreach ($banks as $id => $bankName): ?><option value="<?= e((string) $id) ?>"><?= e($bankName) ?></option><?php endforeach; ?>
                </select></div>
              <div class="field full"><label>Tipo de persona</label>
                <div class="segmented">
                  <label><input type="radio" name="entity_type" value="individual" checked><span>Persona natural</span></label>
                  <label><input type="radio" name="entity_type" value="association"><span>Persona jurídica</span></label>
                </div></div>
              <div class="field"><label for="pse_first">Nombres / razón social</label><input class="input" id="pse_first" name="first_name" required value="<?= e($first) ?>"></div>
              <div class="field"><label for="pse_last">Apellidos</label><input class="input" id="pse_last" name="last_name" value="<?= e($last) ?>"></div>
              <?= id_fields('pse', $idTypes, $user) ?>
              <div class="field"><label for="pse_phone">Celular</label><input class="input" id="pse_phone" name="phone" inputmode="tel" required value="<?= e($user['phone']) ?>"></div>
              <div class="field"><label for="pse_address">Dirección</label><input class="input" id="pse_address" name="address" required placeholder="Calle 100 # 10-20"></div>
              <div class="field"><label for="pse_city">Ciudad</label><input class="input" id="pse_city" name="city" required value="<?= e($user['city']) ?>"></div>
              <div class="field"><label for="pse_state">Departamento</label>
                <select class="input" id="pse_state" name="state" required><option value="">Selecciona</option>
                  <?php foreach ($departments as $d): ?><option><?= e($d) ?></option><?php endforeach; ?>
                </select></div>
            </div>
            <button class="btn btn-blue btn-block pay-btn" type="submit">Continuar al banco</button>
          </form>

          <!-- ============ EFECTY ============ -->
          <form class="method-panel" id="form-efecty" data-method="efecty" novalidate>
            <p class="method-note">Generaremos un recibo para pagar en efectivo en cualquier punto Efecty del país. Tienes 3 días para pagarlo; la licencia se activa cuando se registre el pago.</p>
            <div class="form-grid">
              <div class="field"><label for="ef_first">Nombres</label><input class="input" id="ef_first" name="first_name" required value="<?= e($first) ?>"></div>
              <div class="field"><label for="ef_last">Apellidos</label><input class="input" id="ef_last" name="last_name" required value="<?= e($last) ?>"></div>
              <?= id_fields('ef', $idTypes, $user) ?>
            </div>
            <button class="btn btn-primary btn-block pay-btn" type="submit">Generar recibo de pago</button>
          </form>

          <div style="padding:0 32px"><?php include __DIR__ . '/../includes/intl_notice.php'; ?></div>
          <div class="pay-foot">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
            <span>Tus datos de pago se cifran y se procesan de forma segura. <?= e(BRAND_NAME) ?> no almacena los datos de tu tarjeta.</span>
          </div>
        </div>
      </div>

      <aside class="card summary">
        <div class="plan-name"><?= e($plan['name'] ?? '') ?></div>
        <p class="muted" style="font-size:.93rem"><?= e($plan['description'] ?? '') ?></p>
        <div class="summary-row"><span>Institución</span><span><?= e($user['institution']) ?></span></div>
        <div class="summary-row"><span>Usuarios incluidos</span><span><?= (int) ($plan['seats'] ?? 0) ?></span></div>
        <div class="summary-row"><span>Sedes</span><span><?= (int) ($plan['campuses'] ?? 0) ?></span></div>
        <div class="summary-row"><span>Vigencia</span><span><?= (int) ($plan['months'] ?? 12) ?> meses</span></div>
        <div class="summary-total"><span>Total</span><strong><?= money($order['amount']) ?></strong></div>
        <ul class="checks" style="margin-top:22px">
          <?php foreach (array_slice($plan['features'] ?? [], 0, 4) as $feat): ?><li><?= e($feat) ?></li><?php endforeach; ?>
        </ul>
        <div class="pay-methods"><span>Visa</span><span>Mastercard</span><span>Amex</span><span>Diners</span><span>PSE</span><span>Efecty</span></div>
        <div style="margin-top:18px"><?php $noticeCompact = true; include __DIR__ . '/../includes/intl_notice.php'; $noticeCompact = false; ?></div>
      </aside>
    </div>
  </div>

  <!-- Ventana de verificación 3D Secure del banco -->
  <div class="tds-overlay" id="tdsOverlay" hidden>
    <div class="tds-box">
      <div class="tds-head"><b>Verificación de tu banco</b><small>Confirma la compra con el código que te envió tu banco.</small></div>
      <iframe name="tdsFrame" id="tdsFrame" title="Verificación 3D Secure"></iframe>
      <form id="tdsForm" method="post" target="tdsFrame"><input type="hidden" name="creq" id="tdsCreq"></form>
    </div>
  </div>

  <div class="pay-loading" id="payLoading" hidden><div class="spinner"></div><b id="payLoadingText">Procesando tu pago…</b><small>No cierres ni recargues esta página.</small></div>
</main>

<script>
window.PAY = <?= json_encode([
    'live'      => $live,
    'publicKey' => MP_PUBLIC_KEY,
    'amount'    => (float) $order['amount'],
    'ref'       => $order['external_reference'],
    'csrf'      => csrf_token(),
    'process'   => url('pago/procesar.php'),
    'status'    => url('pago/estado.php?ref=' . urlencode($order['external_reference'])),
    'money'     => ['symbol' => '$', 'currency' => CURRENCY],
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<?php if ($live): ?>
<script src="https://www.mercadopago.com/v2/security.js" view="checkout" output="mpDeviceId"></script>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<?php endif; ?>
<script src="<?= url('assets/js/pago.js') ?>?v=1"></script>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
