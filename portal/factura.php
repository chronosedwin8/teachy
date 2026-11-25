<?php
/**
 * Factura de un pedido aprobado (documento independiente del comprobante de pago).
 * Lleva el mismo número que el comprobante y los datos que exige el art. 6 del
 * Reglamento de facturación español (RD 1619/2012). El precio cobrado se entiende IVA incluido.
 *
 * La factura se congela la primera vez que se emite (ver issued_invoice()).
 * Se descarga en PDF con el diálogo de impresión del navegador.
 */
require_once __DIR__ . '/../includes/pedidos.php';

$user = require_login();
$stmt = db()->prepare('SELECT o.*, u.name AS buyer_name, u.email AS buyer_email, u.institution, u.tax_id, u.city,
                              u.address, u.country, l.license_key, l.seats, l.campuses, l.starts_at, l.expires_at
                       FROM orders o
                       JOIN users u ON u.id = o.user_id
                       LEFT JOIN licenses l ON l.order_id = o.id
                       WHERE o.external_reference = ?');
$stmt->execute([(string) ($_GET['ref'] ?? '')]);
$order = $stmt->fetch();
$isOwner = $order && (int) $order['user_id'] === (int) $user['id'];

if (!$order || (!$isOwner && !is_admin())) {
    flash('error', 'Factura no encontrada.');
    redirect('portal/index.php?tab=pedidos');
}
if ($order['status'] !== 'approved' && empty($order['invoice_data'])) {
    flash('info', 'La factura estará disponible cuando se confirme el pago de este pedido.');
    redirect('portal/index.php?tab=pedidos');
}

$invoice = issued_invoice($order);
$preview = false;
if (!$invoice) {
    $missing = invoice_missing_fields($order);
    if (!is_admin()) {
        $customerMissing = array_filter($missing, fn($m) => str_contains($m, 'cliente'));
        if ($customerMissing) {
            flash('info', 'Para emitir la factura completa los datos fiscales de la institución: ' . implode(', ', $customerMissing) . '.');
            redirect('portal/index.php?tab=cuenta');
        }
        flash('info', 'Tu factura se está preparando y estará disponible en breve. El comprobante de pago ya puedes descargarlo.');
        redirect('portal/index.php?tab=pedidos');
    }
    // El administrador ve un borrador (no se guarda ni tiene validez) con lo que falta
    $invoice = build_invoice($order);
    $preview = true;
}

$iss = $invoice['issuer'];
$cus = $invoice['customer'];
$cur = $invoice['currency'];
$hasVat = (float) $invoice['vat_rate'] > 0;
$pct = rtrim(rtrim(number_format((float) $invoice['vat_rate'], 2, ',', ''), '0'), ',');

$pageTitle = 'Factura ' . $invoice['number'] . ' | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css', 'assets/css/comprobante.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="receipt-actions">
      <a href="<?= url($isOwner ? 'portal/index.php?tab=pedidos' : 'admin/pedidos.php') ?>">← Volver a <?= $isOwner ? 'mis pedidos' : 'pedidos' ?></a>
      <span class="receipt-actions-btns">
        <a class="btn btn-outline btn-sm" href="<?= url('portal/comprobante.php?ref=' . urlencode($order['external_reference'])) ?>">Ver comprobante</a>
        <?php if (!$preview): ?>
          <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Descargar factura (PDF)
          </button>
        <?php endif; ?>
      </span>
    </div>

    <?php if ($preview): ?>
      <div class="invoice-draft-note">
        <strong>Borrador visible solo para administradores.</strong> La factura no se ha emitido porque faltan datos obligatorios:
        <?= e(implode('; ', $missing)) ?>. Complételos en <a href="<?= url('admin/facturacion.php') ?>">Administración &gt; Facturación</a>
        (datos del expedidor) o en el perfil del cliente. Cuando estén completos se emitirá y quedará fija.
      </div>
    <?php endif; ?>

    <article class="receipt invoice<?= $preview ? ' is-draft' : '' ?>">
      <header class="receipt-head">
        <div class="receipt-brand">
          <?php include __DIR__ . '/../includes/logo.php'; ?>
          <div>
            <strong><?= e($iss['name']) ?></strong>
            <?php if ($iss['brand'] !== $iss['name']): ?><span>Marca comercial <?= e($iss['brand']) ?></span><?php endif; ?>
            <span>NIF <?= e($iss['tax_id'] !== '' ? $iss['tax_id'] : '—') ?></span>
            <span><?= e($iss['address']) ?></span>
            <?php if (!empty($iss['registry'])): ?><span><?= e($iss['registry']) ?></span><?php endif; ?>
            <span><?= e($iss['email']) ?> · <?= e($iss['web']) ?></span>
          </div>
        </div>
        <div class="receipt-id">
          <h1 class="invoice-title">FACTURA</h1>
          <strong>Nº <?= e($invoice['number']) ?></strong>
          <span>Serie <?= e($invoice['series']) ?></span>
          <span>Fecha de expedición: <?= date('d/m/Y', strtotime($invoice['issued_on'])) ?></span>
          <span>Fecha de la operación: <?= date('d/m/Y', strtotime($invoice['operation_on'])) ?></span>
          <span class="badge badge-ok">Pagada</span>
        </div>
      </header>

      <section class="receipt-parties">
        <div>
          <h3>Destinatario</h3>
          <p>
            <strong><?= e($cus['name']) ?></strong><br>
            NIT <?= e($cus['tax_id'] !== '' ? $cus['tax_id'] : '—') ?><br>
            <?= e(trim($cus['address'] . ($cus['city'] !== '' ? ', ' . $cus['city'] : ''), ', ')) ?><br>
            <?= e($cus['country']) ?><br>
            <?= e($cus['email']) ?>
          </p>
        </div>
        <div>
          <h3>Pago</h3>
          <p>
            Comprobante de pago: <strong><?= e($invoice['receipt'] ?? $invoice['number']) ?></strong><br>
            Referencia del pedido: <?= e($invoice['reference']) ?><br>
            Medio de pago: <?= e($invoice['payment_method']) ?><br>
            Estado: <strong>Pagada en su totalidad</strong>
          </p>
        </div>
      </section>

      <table class="receipt-table">
        <thead>
          <tr><th>Concepto</th><th class="num">Cant.</th><th class="num">Precio unitario<br>(sin IVA)</th><th class="num">IVA</th><th class="num">Base imponible</th></tr>
        </thead>
        <tbody>
          <?php foreach ($invoice['lines'] as $line): ?>
            <tr>
              <td><strong><?= e($line['concept']) ?></strong><small><?= e($line['detail']) ?></small></td>
              <td class="num"><?= (int) $line['qty'] ?></td>
              <td class="num"><?= money_exact((float) $line['unit_base'], $cur) ?></td>
              <td class="num"><?= $pct ?> %</td>
              <td class="num"><?= money_exact((float) $line['base'], $cur) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <section class="receipt-totals">
        <div class="receipt-detail">
          <h3>Desglose de impuestos</h3>
          <dl>
            <div><dt>Base imponible</dt><dd><?= money_exact((float) $invoice['base'], $cur) ?></dd></div>
            <div><dt>Cuota de IVA (<?= $pct ?> %)</dt><dd><?= money_exact((float) $invoice['vat'], $cur) ?></dd></div>
            <?php if ($hasVat && $invoice['vat_eur'] !== null): ?>
              <div><dt>Cuota de IVA (<?= $pct ?> %) en euros</dt><dd><?= money_exact((float) $invoice['vat_eur'], 'EUR') ?></dd></div>
            <?php endif; ?>
            <div><dt>Total factura</dt><dd><?= money_exact((float) $invoice['total'], $cur) ?></dd></div>
          </dl>
          <?php if ($invoice['eur_rate']): ?>
            <p class="invoice-fx">Tipo de cambio aplicado: 1 EUR = <?= number_format((float) $invoice['eur_rate'], 4, ',', '.') ?> <?= e($cur) ?>
              (fecha de devengo <?= date('d/m/Y', strtotime($invoice['operation_on'])) ?>).
              Equivalente total: <?= money_exact((float) $invoice['total_eur'], 'EUR') ?>.</p>
          <?php endif; ?>
          <?php if (!$hasVat && $invoice['exemption_note'] !== ''): ?>
            <p class="invoice-fx"><?= e($invoice['exemption_note']) ?></p>
          <?php endif; ?>
        </div>
        <div class="receipt-sum">
          <span>Total pagado (IVA incluido)</span>
          <strong><?= money_exact((float) $invoice['total'], $cur) ?></strong>
        </div>
      </section>

      <footer class="receipt-foot">
        <p><strong>Operación de comercio electrónico.</strong> Esta factura corresponde a un servicio prestado por vía electrónica
          y contratado a distancia a través de <?= e($iss['web']) ?>, conforme a la Ley 34/2002 de servicios de la sociedad de la
          información y de comercio electrónico. El precio pagado es el precio final e incluye el IVA desglosado en este documento.</p>
        <p><strong>Tributación.</strong> <?= e($iss['name']) ?> tiene su domicilio fiscal en <?= e($iss['country']) ?>, donde
          declara e ingresa ante la Agencia Estatal de Administración Tributaria la totalidad de los impuestos derivados de esta
          operación. La empresa no tiene establecimiento permanente en países de Latinoamérica ni obligación de liquidar o
          recaudar impuestos en ellos; este documento no incluye impuestos de dichos países.</p>
        <p>Factura expedida conforme al Real Decreto 1619/2012, por el que se aprueba el Reglamento por el que se regulan las
          obligaciones de facturación. Para cualquier aclaración escriba a <?= e($iss['email']) ?>.</p>
      </footer>
    </article>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
