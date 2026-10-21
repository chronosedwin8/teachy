<?php
/**
 * Comprobante de pago de un pedido aprobado. Lo ve el titular del pedido y cualquier administrador.
 * Está preparado para imprimirse o guardarse como PDF desde el navegador.
 */
require_once __DIR__ . '/../includes/pedidos.php';

$user = require_login();
$stmt = db()->prepare('SELECT o.*, u.name AS buyer_name, u.email AS buyer_email, u.institution, u.tax_id, u.city, u.phone,
                              l.license_key, l.seats, l.campuses, l.starts_at, l.expires_at
                       FROM orders o
                       JOIN users u ON u.id = o.user_id
                       LEFT JOIN licenses l ON l.order_id = o.id
                       WHERE o.external_reference = ?');
$stmt->execute([(string) ($_GET['ref'] ?? '')]);
$order = $stmt->fetch();

if (!$order || ((int) $order['user_id'] !== (int) $user['id'] && !is_admin())) {
    flash('error', 'Comprobante no encontrado.');
    redirect('portal/index.php?tab=pedidos');
}
if ($order['status'] !== 'approved') {
    flash('info', 'El comprobante estará disponible cuando se confirme el pago de este pedido.');
    redirect('portal/index.php?tab=pedidos');
}

$plan = plan($order['plan_code']);
$paidAt = $order['paid_at'] ?: $order['created_at'];
$number = 'CP-' . date('Y', strtotime($paidAt)) . '-' . str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT);
$received = $order['amount_received'] !== null ? (float) $order['amount_received'] : null;
$fees = $received !== null ? (float) $order['amount'] - $received : null;

$pageTitle = 'Comprobante ' . $number . ' | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css', 'assets/css/comprobante.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="receipt-actions">
      <a href="<?= url('portal/index.php?tab=pedidos') ?>">← Volver a mis pedidos</a>
      <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5h20v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
        Imprimir o guardar en PDF
      </button>
    </div>

    <article class="receipt">
      <header class="receipt-head">
        <div class="receipt-brand">
          <?php include __DIR__ . '/../includes/logo.php'; ?>
          <div>
            <strong><?= e(COMPANY_LEGAL_NAME !== '' ? COMPANY_LEGAL_NAME : BRAND_NAME) ?></strong>
            <?php if (COMPANY_LEGAL_NAME !== ''): ?><span>Marca comercial <?= e(BRAND_NAME) ?></span><?php endif; ?>
            <?php if (COMPANY_TAX_ID !== ''): ?><span>NIT <?= e(COMPANY_TAX_ID) ?></span><?php endif; ?>
            <?php if (COMPANY_ADDRESS !== '' || COMPANY_CITY !== ''): ?>
              <span><?= e(trim(COMPANY_ADDRESS . ' ' . COMPANY_CITY)) ?></span>
            <?php endif; ?>
            <span><?= e(SUPPORT_EMAIL) ?> · <?= e(preg_replace('#^https?://#', '', BASE_URL)) ?></span>
          </div>
        </div>
        <div class="receipt-id">
          <span class="receipt-kind">Comprobante de pago</span>
          <strong><?= e($number) ?></strong>
          <span>Emitido el <?= fmt_date(date('Y-m-d')) ?></span>
          <span class="badge badge-ok">Pagado</span>
        </div>
      </header>

      <section class="receipt-parties">
        <div>
          <h3>Cliente</h3>
          <p>
            <strong><?= e($order['institution'] ?: $order['buyer_name']) ?></strong><br>
            <?php if ($order['tax_id']): ?>NIT <?= e($order['tax_id']) ?><br><?php endif; ?>
            <?php if ($order['city']): ?><?= e($order['city']) ?><br><?php endif; ?>
            <?= e($order['buyer_name']) ?><br>
            <?= e($order['buyer_email']) ?>
          </p>
        </div>
        <div>
          <h3>Pedido</h3>
          <p>
            Referencia: <strong><?= e($order['external_reference']) ?></strong><br>
            Fecha del pedido: <?= fmt_date($order['created_at']) ?><br>
            Fecha del pago: <strong><?= fmt_date($paidAt) ?></strong><br>
            Medio de pago: <?= e($order['payment_method'] === 'manual' ? 'Transferencia / acuerdo directo' : 'Enlace de pago en línea') ?>
          </p>
        </div>
      </section>

      <table class="receipt-table">
        <thead>
          <tr><th>Concepto</th><th>Cant.</th><th>Valor unitario</th><th>Total</th></tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <strong><?= e($plan['name'] ?? $order['plan_code']) ?></strong>
              <small>
                Licencia de uso de la plataforma por <?= (int) ($order['seats'] ?? $plan['seats'] ?? 0) ?> usuarios
                y <?= (int) ($order['campuses'] ?? $plan['campuses'] ?? 1) ?> sede(s).
                <?php if ($order['starts_at']): ?>
                  Vigencia: <?= fmt_date($order['starts_at']) ?> al <?= fmt_date($order['expires_at']) ?>.
                <?php else: ?>
                  Vigencia: <?= (int) ($plan['months'] ?? 12) ?> meses.
                <?php endif; ?>
                <?php if ($order['license_key']): ?><br>Código de licencia: <?= e($order['license_key']) ?><?php endif; ?>
              </small>
            </td>
            <td class="num">1</td>
            <td class="num"><?= money($order['amount']) ?></td>
            <td class="num"><?= money($order['amount']) ?></td>
          </tr>
        </tbody>
      </table>

      <section class="receipt-totals">
        <div class="receipt-detail">
          <h3>Detalle</h3>
          <dl>
            <div><dt>Valor de la licencia</dt><dd><?= money($order['amount']) ?></dd></div>
            <?php if ($received !== null): ?>
              <div><dt>Total recibido el <?= e(mb_strtolower(fmt_date($paidAt))) ?></dt><dd><?= money($received) ?></dd></div>
              <?php if ($fees > 0): ?>
                <div class="muted-row"><dt>Comisiones de la pasarela de pago</dt><dd>−<?= money($fees) ?></dd></div>
              <?php endif; ?>
            <?php endif; ?>
            <div><dt>Estado</dt><dd>Pago confirmado</dd></div>
          </dl>
          <p class="receipt-note">Las comisiones de la pasarela de pago las descuenta el proveedor del servicio de pagos
            y no afectan el valor pagado por el cliente ni las condiciones de la licencia.</p>
        </div>
        <div class="receipt-sum">
          <span>Total pagado</span>
          <strong><?= money($order['amount']) ?></strong>
          <?php if ($received !== null): ?>
            <span class="receipt-sum-sub">Total recibido el <?= e(mb_strtolower(fmt_date($paidAt))) ?>:
              <strong><?= money($received) ?></strong></span>
          <?php endif; ?>
        </div>
      </section>

      <footer class="receipt-foot">
        <p>Este documento acredita el pago de la licencia indicada y su activación en el portal de clientes.
          No constituye factura electrónica ni documento tributario.</p>
        <p>Comercio electrónico internacional operado desde <?= e(SELLER_COUNTRY) ?>. Precio final; no incluye ni recauda
          impuestos de países de Latinoamérica. Condiciones del servicio en
          <?= e(preg_replace('#^https?://#', '', BASE_URL)) ?>/terminos.php y política de devoluciones en
          <?= e(preg_replace('#^https?://#', '', BASE_URL)) ?>/reembolsos.php.</p>
        <p>Para cualquier aclaración sobre este comprobante escriba a <?= e(SUPPORT_EMAIL) ?>.</p>
      </footer>
    </article>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
