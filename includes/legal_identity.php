<?php
/** Bloque de identificación del titular del sitio, común a los documentos legales. */
$datosPendientes = COMPANY_LEGAL_NAME === '' || COMPANY_TAX_ID === '' || COMPANY_ADDRESS === '';
?>
<?php if ($datosPendientes && is_admin()): ?>
  <div class="alert alert-warn">Aviso solo visible para administradores: complete la razón social, el NIF y la dirección
    en <code>config.php</code> (<code>COMPANY_LEGAL_NAME</code>, <code>COMPANY_TAX_ID</code>, <code>COMPANY_ADDRESS</code>,
    <code>COMPANY_CITY</code>) antes de solicitar la revisión del dominio en la pasarela de pago.</div>
<?php endif; ?>
<div class="legal-identity">
  <h2>Identificación del titular</h2>
  <ul>
    <?php if (COMPANY_LEGAL_NAME !== ''): ?><li><strong>Titular:</strong> <?= e(COMPANY_LEGAL_NAME) ?> (marca comercial <?= e(BRAND_NAME) ?>)</li>
    <?php else: ?><li><strong>Titular:</strong> <?= e(BRAND_NAME) ?></li><?php endif; ?>
    <?php if (COMPANY_TAX_ID !== ''): ?><li><strong>NIF:</strong> <?= e(COMPANY_TAX_ID) ?></li><?php endif; ?>
    <?php if (COMPANY_ADDRESS !== ''): ?><li><strong>Domicilio:</strong> <?= e(COMPANY_ADDRESS) ?><?= COMPANY_CITY !== '' ? ', ' . e(COMPANY_CITY) : '' ?></li>
    <?php elseif (COMPANY_CITY !== ''): ?><li><strong>Domicilio:</strong> <?= e(COMPANY_CITY) ?></li><?php endif; ?>
    <li><strong>País desde el que se opera:</strong> <?= e(SELLER_COUNTRY) ?></li>
    <li><strong>Sitio web:</strong> <a href="<?= e(BASE_URL) ?>"><?= e(preg_replace('#^https?://#', '', BASE_URL)) ?></a></li>
    <li><strong>Atención al cliente:</strong> <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a></li>
  </ul>
</div>
