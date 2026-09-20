<?php
/**
 * Aviso de comercio electrónico internacional. Se muestra en todos los puntos de pago.
 * Variable opcional: $noticeCompact (true = versión breve para resúmenes laterales).
 */
$noticeCompact = $noticeCompact ?? false;
?>
<div class="intl-notice<?= $noticeCompact ? ' compact' : '' ?>">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 010 20M12 2a15 15 0 000 20"/></svg>
  <div>
    <b>Compra de comercio electrónico internacional</b>
    <?php if ($noticeCompact): ?>
      <span>Venta realizada por <?= e(BRAND_NAME) ?> desde <?= e(SELLER_COUNTRY) ?>. El precio es el valor final a pagar; no incluye ni recauda impuestos de países de Latinoamérica.</span>
    <?php else: ?>
      <span><?= e(BRAND_NAME) ?> es un comercio electrónico establecido en <?= e(SELLER_COUNTRY) ?> que vende licencias digitales a clientes de otros países.
        El precio publicado es el valor final a pagar. <?= e(BRAND_NAME) ?> no incluye, no recauda ni asume impuestos, tasas o políticas tributarias de países de Latinoamérica.
        Si la legislación del país del comprador exige algún impuesto, retención o declaración sobre esta compra, su cumplimiento corresponde al comprador.</span>
    <?php endif; ?>
  </div>
</div>
