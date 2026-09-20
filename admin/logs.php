<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();
$source = (string) ($_GET['source'] ?? '');
$sources = db()->query('SELECT DISTINCT source FROM payment_logs ORDER BY source')->fetchAll(PDO::FETCH_COLUMN);
$stmt = db()->prepare('SELECT * FROM payment_logs' . ($source !== '' ? ' WHERE source = ?' : '') . ' ORDER BY id DESC LIMIT 200');
$stmt->execute($source !== '' ? [$source] : []);
$logs = $stmt->fetchAll();

$pageTitle = 'Registro de pagos | Administración';
$extraCss = ['assets/css/portal.css'];
$adminSection = 'logs';
$adminTitle = 'Registro de pagos';
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>
    <div class="card">
      <div class="card-head">
        <h2>Eventos recientes</h2>
        <form method="get"><select class="input search-input" name="source" onchange="this.form.submit()">
          <option value="">Todos los eventos</option>
          <?php foreach ($sources as $s): ?><option value="<?= e($s) ?>" <?= $s === $source ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select></form>
      </div>
      <p class="muted" style="font-size:.9rem">Retornos desde Mercado Pago, notificaciones del webhook y errores. Útil para revisar un pago que no se activó.</p>
      <?php if (!$logs): ?><div class="empty">Sin eventos registrados.</div><?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Fecha</th><th>Evento</th><th>Detalle</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td style="white-space:nowrap"><?= fmt_date($log['created_at'], true) ?></td>
            <td><span class="chip <?= str_contains($log['source'], 'error') || $log['source'] === 'amount_mismatch' ? 'chip-rose' : 'chip-blue' ?>"><?= e($log['source']) ?></span></td>
            <td><code style="font-size:.8rem;word-break:break-all;color:var(--ink-2)"><?= e(mb_strimwidth((string) $log['payload'], 0, 600, '…')) ?></code></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
