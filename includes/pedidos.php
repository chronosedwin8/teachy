<?php
/**
 * Pedidos y licencias.
 *
 * Los cobros se hacen con enlaces de pago de Mercado Pago (uno por plan), por lo que el sitio no
 * conecta con ninguna API de pagos: el pedido queda pendiente y se aprueba desde el panel de
 * administración cuando el pago se confirma en la cuenta de Mercado Pago.
 */
require_once __DIR__ . '/bootstrap.php';

/** Enlace de pago del plan (configurable en Administración > Precios). */
function plan_payment_link(string $planCode): string
{
    $plan = plan($planCode);
    $link = trim((string) ($plan['payment_link'] ?? ''));
    return filter_var($link, FILTER_VALIDATE_URL) ? $link : '';
}

function log_event(string $source, mixed $payload): void
{
    try {
        db()->prepare('INSERT INTO payment_logs (source, payload) VALUES (?, ?)')
            ->execute([$source, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    } catch (Throwable) {
        // El registro nunca debe interrumpir el flujo de compra.
    }
}

function find_order_by_reference(string $reference): ?array
{
    if ($reference === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM orders WHERE external_reference = ?');
    $stmt->execute([$reference]);
    return $stmt->fetch() ?: null;
}

/**
 * Cambia el estado de un pedido. Al aprobarlo crea (o reactiva) su licencia; al cancelarlo o
 * reembolsarlo la suspende. Es idempotente: aprobar dos veces no duplica la licencia.
 */
function set_order_status(array $order, string $status, string $detail = ''): array
{
    $allowed = ['pending', 'in_process', 'approved', 'rejected', 'cancelled', 'refunded'];
    if (!in_array($status, $allowed, true)) {
        return $order;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE orders SET status = ?, mp_status_detail = ?,
                       paid_at = IF(? = "approved" AND paid_at IS NULL, NOW(), paid_at) WHERE id = ?')
            ->execute([$status, $detail !== '' ? $detail : $order['mp_status_detail'], $status, $order['id']]);

        if ($status === 'approved') {
            create_license_for_order((int) $order['id']);
            $pdo->prepare("UPDATE licenses SET status = 'active' WHERE order_id = ? AND status = 'suspended' AND expires_at > NOW()")
                ->execute([$order['id']]);
        } elseif (in_array($status, ['cancelled', 'refunded'], true)) {
            $pdo->prepare("UPDATE licenses SET status = 'suspended' WHERE order_id = ?")->execute([$order['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return find_order_by_reference($order['external_reference']) ?? $order;
}

/** Crea la licencia de un pedido aprobado (si aún no existe). */
function create_license_for_order(int $orderId): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    $plan = $order ? plan($order['plan_code']) : null;
    if (!$order || !$plan) {
        return;
    }

    $exists = $pdo->prepare('SELECT id FROM licenses WHERE order_id = ?');
    $exists->execute([$orderId]);
    if ($exists->fetch()) {
        return;
    }

    // La vigencia arranca el día del pago cuando se conoce; si no, en el momento de la activación
    $inicio = $order['paid_at'] ?: date('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO licenses (user_id, order_id, plan_code, license_key, seats, campuses, status, starts_at, expires_at)
                   VALUES (?, ?, ?, ?, ?, ?, "active", ?, DATE_ADD(?, INTERVAL ? MONTH))')
        ->execute([$order['user_id'], $orderId, $plan['code'], generate_license_key($plan['code']),
            $plan['seats'], $plan['campuses'], $inicio, $inicio, $plan['months']]);

    // El comprador queda registrado como primer usuario (administrador) de la licencia
    $licenseId = (int) $pdo->lastInsertId();
    $u = $pdo->prepare('SELECT name, email, institution FROM users WHERE id = ?');
    $u->execute([$order['user_id']]);
    if ($buyer = $u->fetch()) {
        $pdo->prepare('INSERT IGNORE INTO license_members (license_id, name, email, role, campus) VALUES (?, ?, ?, "admin", ?)')
            ->execute([$licenseId, $buyer['name'], $buyer['email'], $buyer['institution']]);
    }
}

function generate_license_key(string $planCode): string
{
    $prefix = $planCode === 'volumen' ? 'VOL' : 'ESC';
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $parts = [];
    for ($p = 0; $p < 3; $p++) {
        $chunk = '';
        for ($i = 0; $i < 4; $i++) {
            $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $parts[] = $chunk;
    }
    return $prefix . '-' . implode('-', $parts);
}

/**
 * Ajusta el valor de un pedido aún no pagado al precio vigente del plan (por si el administrador
 * cambió el precio después de crearse el pedido). No toca pedidos aprobados ni otorgados a mano.
 */
function reprice_unpaid_order(array $order): array
{
    $plan = plan($order['plan_code']);
    if (!$plan || str_starts_with($order['external_reference'], 'MAN-')
        || !in_array($order['status'], ['pending', 'rejected', 'cancelled'], true)
        || (float) $order['amount'] === (float) $plan['price']) {
        return $order;
    }
    db()->prepare('UPDATE orders SET amount = ? WHERE id = ?')->execute([$plan['price'], $order['id']]);
    $order['amount'] = $plan['price'];
    return $order;
}

// ---------------------------------------------------------------
// Comprobante y factura
// ---------------------------------------------------------------

/** Número del comprobante de pago. */
function receipt_number(array $order): string
{
    $paidAt = $order['paid_at'] ?: $order['created_at'];
    return 'CP-' . date('Y', strtotime($paidAt)) . '-' . str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT);
}

/** Interpreta un número escrito como 4.500,25 · 4500,25 · 4500.25 · 4.500 */
function parse_decimal(string $value): float
{
    $value = preg_replace('/[^0-9.,]/', '', $value);
    if (str_contains($value, ',')) {
        $value = str_replace(['.', ','], ['', '.'], $value);
    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
        $value = str_replace('.', '', $value);
    }
    return (float) $value;
}

/** Importe con dos decimales en formato español: 1.234.567,89 */
function money_exact(float $amount, string $currency): string
{
    $txt = number_format($amount, 2, ',', '.');
    return $currency === 'EUR' ? $txt . ' €' : $txt . ' ' . $currency;
}

/**
 * Ajustes de facturación (Administración > Facturación). Los valores de config.php son los
 * predeterminados; lo guardado en el panel (tabla app_settings) los reemplaza.
 */
function invoice_settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }
    $cache = [
        'legal_name'  => COMPANY_LEGAL_NAME,
        'tax_id'      => COMPANY_TAX_ID,
        'address'     => trim(COMPANY_ADDRESS . (COMPANY_CITY !== '' ? ' ' . COMPANY_CITY : '') . ', ' . COMPANY_COUNTRY . '.', ' ,'),
        'registry'    => '',
        'email'       => SUPPORT_EMAIL,
        'numbering'   => 'receipt',   // receipt = mismo número del comprobante · series = serie propia correlativa
        'series'      => 'A',
        'next_number' => 1,
        'regime'      => (float) VAT_RATE > 0 ? 'subject' : 'not_subject',
        'vat_rate'    => (float) VAT_RATE > 0 ? (float) VAT_RATE : 21.0,
        'description' => 'Licencia de uso de la plataforma ' . BRAND_NAME,
        'updated_by'  => null,
        'updated_at'  => null,
    ];
    try {
        $row = db()->query("SELECT value, updated_by, updated_at FROM app_settings WHERE name = 'invoice'")->fetch();
        if ($row) {
            $saved = json_decode((string) $row['value'], true) ?: [];
            $cache = array_merge($cache, array_intersect_key($saved, $cache));
            $cache['updated_by'] = $row['updated_by'];
            $cache['updated_at'] = $row['updated_at'];
        }
    } catch (PDOException) {
        // Tabla aún no creada (instalación pendiente): se usan los valores de config.php
    }
    return $cache;
}

function save_invoice_settings(array $data, string $by): void
{
    $keys = ['legal_name', 'tax_id', 'address', 'registry', 'email', 'numbering', 'series', 'next_number', 'regime', 'vat_rate', 'description'];
    db()->prepare("INSERT INTO app_settings (name, value, updated_by) VALUES ('invoice', ?, ?)
                   ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by)")
        ->execute([json_encode(array_intersect_key($data, array_flip($keys)), JSON_UNESCAPED_UNICODE), $by]);
    invoice_settings(true);
}

/** Datos del emisor que se imprimen en la factura y en el comprobante. */
function invoice_issuer(): array
{
    $s = invoice_settings();
    return [
        'name'     => $s['legal_name'] !== '' ? $s['legal_name'] : BRAND_NAME,
        'brand'    => BRAND_NAME,
        'tax_id'   => $s['tax_id'],
        'address'  => $s['address'],
        'registry' => $s['registry'],
        'country'  => COMPANY_COUNTRY,
        'email'    => $s['email'] !== '' ? $s['email'] : SUPPORT_EMAIL,
        'web'      => preg_replace('#^https?://#', '', BASE_URL),
    ];
}

/** Campos obligatorios que faltan para emitir la factura (vacío = se puede emitir). */
function invoice_missing_fields(array $order): array
{
    $s = invoice_settings();
    $missing = [];
    if ($s['legal_name'] === '') $missing[] = 'razón social del emisor';
    if ($s['tax_id'] === '') $missing[] = 'NIF del emisor';
    if ($s['address'] === '') $missing[] = 'domicilio fiscal del emisor';
    if (trim((string) ($order['institution'] ?: $order['buyer_name'])) === '') $missing[] = 'nombre o razón social del cliente';
    if (trim((string) $order['tax_id']) === '') $missing[] = 'NIT o documento del cliente';
    if (trim((string) $order['address']) === '' || trim((string) $order['city']) === '' || trim((string) $order['country']) === '') {
        $missing[] = 'dirección, ciudad y país del cliente';
    }
    return $missing;
}

/**
 * Calcula la factura de un pedido aprobado. El precio cobrado se entiende IVA incluido, así que la
 * base imponible se obtiene dividiendo el total entre (1 + tipo) y la cuota es la diferencia; el
 * total de la factura coincide siempre con lo pagado.
 * Con serie propia, el número definitivo se asigna al emitirla (issued_invoice()).
 */
function build_invoice(array $order): array
{
    $s = invoice_settings();
    $plan = plan($order['plan_code']);
    $paidAt = $order['paid_at'] ?: $order['created_at'];
    $total = round((float) $order['amount'], 2);
    $rate = $s['regime'] === 'subject' ? (float) $s['vat_rate'] : 0.0;
    $base = round($total / (1 + $rate / 100), 2);
    $vat = round($total - $base, 2);
    $eurRate = (float) ($order['eur_rate'] ?: EUR_RATE_DEFAULT);
    $currency = $order['currency'] ?: CURRENCY;

    $seats = (int) ($order['seats'] ?? $plan['seats'] ?? 0);
    $campuses = (int) ($order['campuses'] ?? $plan['campuses'] ?? 1);
    $detail = rtrim($s['description'], '. ') . " (servicio prestado por vía electrónica) para $seats usuarios y $campuses sede(s).";
    if (!empty($order['starts_at'])) {
        $detail .= ' Periodo: ' . date('d/m/Y', strtotime($order['starts_at'])) . ' a ' . date('d/m/Y', strtotime($order['expires_at'])) . '.';
    } else {
        $detail .= ' Duración: ' . (int) ($plan['months'] ?? 12) . ' meses.';
    }
    if (!empty($order['license_key'])) {
        $detail .= ' Código de licencia: ' . $order['license_key'] . '.';
    }

    $useSeries = $s['numbering'] === 'series';
    return [
        'number'         => $useSeries ? $s['series'] . '-' . (int) $s['next_number'] : receipt_number($order),
        'series'         => $useSeries ? $s['series'] : 'CP',
        'receipt'        => receipt_number($order),
        'issued_on'      => date('Y-m-d', strtotime($paidAt)),
        'operation_on'   => date('Y-m-d', strtotime($paidAt)),
        'reference'      => $order['external_reference'],
        'payment_method' => $order['payment_method'] === 'manual' ? 'Transferencia / acuerdo directo' : 'Enlace de pago en línea',
        'issuer'         => invoice_issuer(),
        'customer'       => [
            'name'    => $order['institution'] ?: $order['buyer_name'],
            'tax_id'  => (string) $order['tax_id'],
            'address' => (string) $order['address'],
            'city'    => (string) $order['city'],
            'country' => (string) $order['country'],
            'contact' => (string) $order['buyer_name'],
            'email'   => (string) $order['buyer_email'],
        ],
        'lines'          => [[
            'concept'   => $plan['name'] ?? $order['plan_code'],
            'detail'    => $detail,
            'qty'       => 1,
            'unit_base' => $base,
            'base'      => $base,
        ]],
        'currency'       => $currency,
        'vat_rate'       => $rate,
        'base'           => $base,
        'vat'            => $vat,
        'total'          => $total,
        'eur_rate'       => $eurRate,
        'vat_eur'        => $eurRate > 0 ? round($vat / $eurRate, 2) : null,
        'total_eur'      => $eurRate > 0 ? round($total / $eurRate, 2) : null,
        'exemption_note' => $rate > 0 ? '' : VAT_EXEMPTION_NOTE,
    ];
}

/**
 * Devuelve la factura emitida del pedido. La primera vez que todos los datos obligatorios están
 * completos se emite y se congela en orders.invoice_data: desde ese momento no cambia aunque el
 * cliente edite su perfil o se modifiquen los ajustes de facturación.
 * Con serie propia, el número se toma del contador y se incrementa en la misma transacción, de
 * modo que la numeración es correlativa y sin saltos. Si aún no se puede emitir devuelve null.
 */
function issued_invoice(array $order): ?array
{
    if (!empty($order['invoice_data'])) {
        $data = json_decode((string) $order['invoice_data'], true);
        if (is_array($data)) {
            return $data;
        }
    }
    if ($order['status'] !== 'approved' || invoice_missing_fields($order)) {
        return null;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT invoice_data FROM orders WHERE id = ? FOR UPDATE');
        $lock->execute([$order['id']]);
        $existing = $lock->fetchColumn();
        if ($existing) {                       // otra petición la emitió mientras tanto
            $pdo->commit();
            return json_decode((string) $existing, true);
        }

        $pdo->query("SELECT value FROM app_settings WHERE name = 'invoice' FOR UPDATE")->fetchAll();
        $settings = invoice_settings(true);
        $invoice = build_invoice($order);
        $invoice['created_at'] = date('Y-m-d H:i:s');

        $pdo->prepare('UPDATE orders SET invoice_data = ? WHERE id = ?')
            ->execute([json_encode($invoice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $order['id']]);
        if ($settings['numbering'] === 'series') {
            $settings['next_number'] = (int) $settings['next_number'] + 1;
            save_invoice_settings($settings, (string) $settings['updated_by']);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $invoice;
}
