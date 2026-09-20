<?php
/**
 * Integración con Mercado Pago (Checkout Pro) mediante la API REST.
 * Documentación: https://www.mercadopago.com.co/developers/es/reference
 */
require_once __DIR__ . '/bootstrap.php';

const MP_API = 'https://api.mercadopago.com';

/** Llamada HTTP a la API de Mercado Pago. Devuelve [statusCode, body decodificado]. */
function mp_request(string $method, string $path, ?array $body = null, array $extraHeaders = []): array
{
    $ch = curl_init(MP_API . $path);
    $headers = array_merge([
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'Accept: application/json',
    ], $extraHeaders);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('Error de conexión con Mercado Pago: ' . $error);
    }
    return [$status, json_decode($raw, true) ?? []];
}

/** Datos comunes de un cobro directo (Checkout API) para un pedido. */
function mp_base_payment(array $order, array $plan, array $user): array
{
    $payload = [
        'transaction_amount' => (float) $order['amount'],
        'description'        => BRAND_NAME . ' - ' . $plan['name'] . ' (12 meses)',
        'external_reference' => $order['external_reference'],
        'metadata'           => ['order_id' => (int) $order['id'], 'plan' => $plan['code']],
        'additional_info'    => [
            'ip_address' => client_ip(),
            'items'      => [[
                'id'          => $plan['code'],
                'title'       => BRAND_NAME . ' - ' . $plan['name'],
                'description' => $plan['description'],
                'category_id' => 'education',
                'quantity'    => 1,
                'unit_price'  => (float) $order['amount'],
            ]],
        ],
    ];
    if (MP_NOTIFICATION_URL !== '') {
        $payload['notification_url'] = MP_NOTIFICATION_URL;
    }
    return $payload;
}

/** Crea un pago en Mercado Pago (tarjeta, PSE o Efecty). Lanza excepción si la API lo rechaza. */
function mp_create_payment(array $payload, string $idempotencyKey, string $deviceId = ''): array
{
    $headers = ['X-Idempotency-Key: ' . $idempotencyKey];
    if ($deviceId !== '' && preg_match('/^[A-Za-z0-9_\-:.]{8,200}$/', $deviceId)) {
        $headers[] = 'X-meli-session-id: ' . $deviceId;
    }
    [$status, $data] = mp_request('POST', '/v1/payments', $payload, $headers);
    if ($status < 200 || $status >= 300 || empty($data['id'])) {
        log_payment('payment_error', ['http' => $status, 'response' => $data, 'ref' => $payload['external_reference'] ?? null]);
        throw new MpApiException($data['message'] ?? ('HTTP ' . $status), $data);
    }
    return $data;
}

final class MpApiException extends RuntimeException
{
    public function __construct(string $message, public readonly array $response = [])
    {
        parent::__construct($message);
    }
}

/** Cancela un pago pendiente (p. ej. un recibo Efecty o PSE sin pagar al cambiar de medio). */
function mp_cancel_payment(string $paymentId): void
{
    if (ctype_digit($paymentId)) {
        mp_request('PUT', '/v1/payments/' . $paymentId, ['status' => 'cancelled']);
    }
}

/** Bancos disponibles para PSE (se guardan en caché 12 horas). */
function mp_pse_banks(): array
{
    $cache = sys_get_temp_dir() . '/mp_pse_banks_' . md5(MP_ACCESS_TOKEN) . '.json';
    if (is_file($cache) && filemtime($cache) > time() - 43200) {
        return json_decode((string) file_get_contents($cache), true) ?: [];
    }
    $banks = [];
    try {
        [$status, $methods] = mp_request('GET', '/v1/payment_methods');
        if ($status === 200) {
            foreach ($methods as $m) {
                if (($m['id'] ?? '') === 'pse') {
                    foreach ($m['financial_institutions'] ?? [] as $fi) {
                        $banks[(string) $fi['id']] = $fi['description'];
                    }
                }
            }
            asort($banks, SORT_NATURAL | SORT_FLAG_CASE);
            @file_put_contents($cache, json_encode($banks, JSON_UNESCAPED_UNICODE));
        }
    } catch (Throwable $ex) {
        log_payment('banks_error', ['error' => $ex->getMessage()]);
    }
    return $banks;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    // Detrás de un proxy/CDN de confianza el hosting suele exponer la IP real en X-Forwarded-For
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = $first;
        }
    }
    return $ip;
}

/** Mensaje amigable para el resultado de un pago con tarjeta. */
function payment_message(string $status, string $detail): string
{
    return match ($detail) {
        'accredited'                           => '¡Pago aprobado!',
        'pending_contingency'                  => 'Estamos procesando tu pago. En menos de 2 días hábiles te confirmaremos el resultado.',
        'pending_review_manual'                => 'Tu pago está en revisión. Te avisaremos apenas se confirme.',
        'cc_rejected_bad_filled_card_number'   => 'Revisa el número de la tarjeta.',
        'cc_rejected_bad_filled_date'          => 'Revisa la fecha de vencimiento.',
        'cc_rejected_bad_filled_security_code' => 'Revisa el código de seguridad de la tarjeta.',
        'cc_rejected_bad_filled_other'         => 'Revisa los datos de la tarjeta.',
        'cc_rejected_blacklist',
        'cc_rejected_high_risk'                => 'El pago fue rechazado por seguridad. Intenta con otro medio de pago.',
        'cc_rejected_call_for_authorize'       => 'Debes autorizar este pago con tu banco. Llámalo y luego intenta de nuevo.',
        'cc_rejected_card_disabled'            => 'La tarjeta no está activa. Llama a tu banco para activarla o usa otra tarjeta.',
        'cc_rejected_duplicated_payment'       => 'Ya hiciste un pago por este valor. Si necesitas volver a pagar, usa otra tarjeta u otro medio.',
        'cc_rejected_insufficient_amount'      => 'La tarjeta no tiene fondos o cupo suficiente.',
        'cc_rejected_invalid_installments'     => 'La tarjeta no acepta ese número de cuotas.',
        'cc_rejected_max_attempts'             => 'Llegaste al límite de intentos permitidos. Usa otra tarjeta u otro medio de pago.',
        'cc_rejected_card_type_not_allowed'    => 'Este tipo de tarjeta no está permitido. Usa otra tarjeta.',
        default => $status === 'approved' ? '¡Pago aprobado!'
            : (in_array($status, ['pending', 'in_process'], true) ? 'Tu pago está en proceso.' : 'El banco no aprobó el pago. Intenta con otra tarjeta u otro medio de pago.'),
    };
}

/**
 * Actualiza el valor de un pedido aún no pagado al precio vigente del plan (por si el administrador
 * cambió el precio después de crearse el pedido). No toca pedidos aprobados ni en proceso.
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

function mp_get_payment(string $paymentId): ?array
{
    if (!ctype_digit($paymentId)) {
        return null;
    }
    [$status, $data] = mp_request('GET', '/v1/payments/' . $paymentId);
    return $status === 200 ? $data : null;
}

/** Busca el pago más reciente asociado a una referencia externa. */
function mp_search_payment_by_reference(string $reference): ?array
{
    [$status, $data] = mp_request('GET', '/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . urlencode($reference));
    if ($status !== 200 || empty($data['results'])) {
        return null;
    }
    // Priorizar un pago aprobado si existe
    foreach ($data['results'] as $p) {
        if (($p['status'] ?? '') === 'approved') {
            return $p;
        }
    }
    return $data['results'][0];
}

function log_payment(string $source, mixed $payload): void
{
    try {
        db()->prepare('INSERT INTO payment_logs (source, payload) VALUES (?, ?)')
            ->execute([$source, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    } catch (Throwable) {
        // El registro nunca debe interrumpir el flujo de pago.
    }
}

function find_order_by_reference(string $reference): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE external_reference = ?');
    $stmt->execute([$reference]);
    return $stmt->fetch() ?: null;
}

/**
 * Aplica un pago (verificado contra la API de MP, o simulado en modo demo) a su pedido.
 * Es idempotente: puede llamarse varias veces con el mismo pago (retorno + webhook).
 * Devuelve el pedido actualizado o null si no corresponde a ningún pedido.
 */
function apply_payment_to_order(array $payment, bool $isDemo = false): ?array
{
    $reference = (string) ($payment['external_reference'] ?? '');
    $order = $reference !== '' ? find_order_by_reference($reference) : null;
    if (!$order) {
        return null;
    }

    $status = (string) ($payment['status'] ?? 'pending');
    $allowed = ['pending', 'in_process', 'approved', 'rejected', 'cancelled', 'refunded'];
    if ($status === 'authorized' || $status === 'in_mediation') {
        $status = 'in_process';
    } elseif ($status === 'charged_back') {
        $status = 'refunded';
    } elseif (!in_array($status, $allowed, true)) {
        $status = 'pending';
    }

    // Validar que el monto y la moneda pagados coincidan con el pedido
    if ($status === 'approved' && !$isDemo) {
        $paid = (float) ($payment['transaction_amount'] ?? 0);
        $currency = (string) ($payment['currency_id'] ?? '');
        if (abs($paid - (float) $order['amount']) > 0.5 || $currency !== $order['currency']) {
            log_payment('amount_mismatch', ['order' => $order['id'], 'payment' => $payment['id'] ?? null, 'paid' => $paid, 'currency' => $currency]);
            $status = 'in_process';
        }
    }

    // Un pedido aprobado no retrocede a pendiente por una notificación tardía de otro intento
    if ($order['status'] === 'approved' && in_array($status, ['pending', 'in_process', 'rejected'], true)) {
        return $order;
    }
    // Un pedido aprobado solo cambia por su propio pago (reembolso o contracargo), nunca por otro
    // intento (p. ej. un recibo Efecty que vence después de pagar con tarjeta).
    $incomingId = isset($payment['id']) ? (string) $payment['id'] : '';
    if ($order['status'] === 'approved' && !$isDemo && $incomingId !== '' && $incomingId !== (string) $order['mp_payment_id']) {
        if ($status === 'approved') {
            log_payment('duplicate_payment', ['order' => $order['id'], 'payment' => $incomingId]);
        }
        return $order;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE orders SET status = ?, mp_payment_id = ?, mp_status_detail = ?, payment_method = ?,
                       paid_at = IF(? = "approved" AND paid_at IS NULL, NOW(), paid_at) WHERE id = ?')
            ->execute([
                $status,
                isset($payment['id']) ? (string) $payment['id'] : $order['mp_payment_id'],
                $payment['status_detail'] ?? null,
                $payment['payment_method_id'] ?? null,
                $status,
                $order['id'],
            ]);

        if ($status === 'approved') {
            create_license_for_order((int) $order['id']);
            $pdo->prepare("UPDATE licenses SET status = 'active' WHERE order_id = ? AND status = 'suspended' AND expires_at > NOW()")
                ->execute([$order['id']]);
        } elseif (in_array($status, ['refunded', 'cancelled'], true)) {
            $pdo->prepare("UPDATE licenses SET status = 'suspended' WHERE order_id = ?")->execute([$order['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return find_order_by_reference($reference);
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

    $key = generate_license_key($plan['code']);
    $pdo->prepare('INSERT INTO licenses (user_id, order_id, plan_code, license_key, seats, campuses, status, starts_at, expires_at)
                   VALUES (?, ?, ?, ?, ?, ?, "active", NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH))')
        ->execute([$order['user_id'], $orderId, $plan['code'], $key, $plan['seats'], $plan['campuses'], $plan['months']]);

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

/** Valida la firma x-signature de un webhook de Mercado Pago (si hay clave configurada). */
function mp_valid_webhook_signature(string $dataId): bool
{
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    // Sin clave configurada, o notificación IPN clásica (no firmada): la seguridad se basa en
    // consultar el pago directamente a la API; nunca se confía en el contenido de la notificación.
    if (MP_WEBHOOK_SECRET === '' || $signature === '') {
        return true;
    }
    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    $ts = $v1 = '';
    foreach (explode(',', $signature) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 'ts') $ts = $v;
        if ($k === 'v1') $v1 = $v;
    }
    if ($ts === '' || $v1 === '') {
        return false;
    }
    $manifest = 'id:' . strtolower($dataId) . ';request-id:' . $requestId . ';ts:' . $ts . ';';
    return hash_equals(hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET), $v1);
}
