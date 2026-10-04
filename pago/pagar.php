<?php
/**
 * Pantalla de pago: muestra el resumen del pedido y el enlace de pago del plan.
 * El cobro se completa en el enlace de pago de Mercado Pago; al confirmarse, el pedido se
 * aprueba desde Administración > Pedidos y la licencia queda activa.
 */
require_once __DIR__ . '/../includes/pedidos.php';

$user = require_login();
$order = find_order_by_reference((string) ($_GET['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    flash('error', 'Pedido no encontrado.');
    redirect('portal/index.php?tab=pedidos');
}
if ($order['status'] === 'approved') {
    $lic = db()->prepare('SELECT id FROM licenses WHERE order_id = ?');
    $lic->execute([$order['id']]);
    $licenseId = $lic->fetchColumn();
    flash('success', 'Este pedido ya está pagado y su licencia está activa.');
    redirect($licenseId ? 'portal/licencia.php?id=' . $licenseId : 'portal/index.php');
}

$order = reprice_unpaid_order($order);
$plan = plan($order['plan_code']);
$link = plan_payment_link($order['plan_code']);
$informado = in_array($order['status'], ['in_process'], true);

$pageTitle = 'Pagar ' . ($plan['name'] ?? 'licencia') . ' | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css', 'assets/css/pago.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="portal-head">
      <div>
        <span class="eyebrow"><span class="dot"></span> Pago de tu licencia</span>
        <h1>Completa tu pago</h1>
        <p>Pedido <?= e($order['external_reference']) ?> · <?= e($plan['name'] ?? '') ?></p>
      </div>
      <div class="secure-pill">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
        Pago seguro
      </div>
    </div>

    <div class="checkout-grid">
      <div>
        <?php include __DIR__ . '/../includes/flash.php'; ?>

        <?php if ($informado): ?>
          <div class="alert alert-warn">Registramos que ya realizaste el pago de este pedido. Estamos verificándolo:
            tu licencia se activará en cuanto lo confirmemos y te avisaremos por correo.</div>
        <?php endif; ?>

        <div class="card pay-card" style="padding:32px">
          <?php if ($link === ''): ?>
            <div class="alert alert-error">Este plan no tiene un enlace de pago configurado. Escríbenos a
              <?= e(CONTACT_EMAIL) ?> y te ayudamos a completar la compra.</div>
          <?php else: ?>
            <h2>Paga <?= money($order['amount']) ?></h2>
            <p class="muted">Al continuar se abrirá la pasarela de pago, donde puedes pagar con tarjeta de crédito o débito,
              PSE (débito desde tu cuenta bancaria) o en efectivo.</p>

            <a class="btn btn-blue btn-block pay-btn" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">
              Ir a pagar <?= money($order['amount']) ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>

            <ol class="pay-steps">
              <li>Pulsa el botón y completa el pago en la pasarela.</li>
              <li>Vuelve a esta página y confirma con el botón <strong>"Ya realicé el pago"</strong>.</li>
              <li>Verificamos el pago y activamos tu licencia. Recibirás un aviso y la verás en tu portal.</li>
            </ol>

            <?php if (!$informado): ?>
              <form method="post" action="<?= url('pago/aviso.php') ?>" data-confirm="¿Confirmas que ya realizaste el pago de este pedido?">
                <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($order['external_reference']) ?>">
                <button class="btn btn-outline btn-block" type="submit">Ya realicé el pago</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>

          <div class="pay-foot">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
            <span>El pago se procesa en la pasarela; <?= e(BRAND_NAME) ?> no recibe ni almacena los datos de tu tarjeta.
              ¿Dudas con tu pago? Escríbenos a <?= e(CONTACT_EMAIL) ?>.</span>
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
        <div class="pay-methods"><span>Tarjetas</span><span>PSE</span><span>Efectivo</span></div>
        <div style="margin-top:18px"><?php $noticeCompact = true; include __DIR__ . '/../includes/intl_notice.php'; $noticeCompact = false; ?></div>
      </aside>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
