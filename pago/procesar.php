<?php
/**
 * Procesa el cobro desde la pantalla de pago propia (Checkout API de Mercado Pago).
 * Recibe el token de la tarjeta generado en el navegador (los datos de la tarjeta nunca llegan aquí),
 * o los datos de PSE / Efecty. Responde JSON.
 */
require_once __DIR__ . '/../includes/mercadopago.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $code = 422): never
{
    respond(['status' => 'error', 'message' => $message], $code);
}

$user = current_user();
if (!$user) {
    fail('Tu sesión expiró. Recarga la página e inicia sesión.', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
    fail('La sesión expiró. Recarga la página.', 419);
}

$order = find_order_by_reference((string) ($_POST['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    fail('Pedido no encontrado.', 404);
}
$plan = plan($order['plan_code']);
if ($order['status'] === 'approved') {
    respond(['status' => 'approved', 'redirect' => url('pago/retorno.php?external_reference=' . urlencode($order['external_reference']))]);
}

// Límite de intentos por sesión para frenar pruebas masivas de tarjetas
$_SESSION['pay_attempts'] = array_filter($_SESSION['pay_attempts'] ?? [], fn($t) => $t > time() - 3600);
if (count($_SESSION['pay_attempts']) >= 8) {
    fail('Superaste el número de intentos permitidos. Espera una hora o escríbenos por WhatsApp.', 429);
}
$_SESSION['pay_attempts'][] = time();

$method = (string) ($_POST['method'] ?? '');
$idTypes = ['CC', 'CE', 'NIT', 'Otro'];
$idType = (string) ($_POST['id_type'] ?? '');
$idNumber = preg_replace('/[^0-9A-Za-z]/', '', (string) ($_POST['id_number'] ?? ''));
if (!in_array($idType, $idTypes, true) || strlen($idNumber) < 5 || strlen($idNumber) > 20) {
    fail('Revisa el tipo y número de documento.');
}
$returnUrl = url('pago/retorno.php?external_reference=' . urlencode($order['external_reference']));

/** Guarda los datos del intento y aplica el resultado al pedido. */
function save_attempt(array $order, array $payment, string $type): array
{
    db()->prepare('UPDATE orders SET payment_type = ?, mp_resource_url = ?, mp_expires_at = ? WHERE id = ?')->execute([
        $type,
        $payment['transaction_details']['external_resource_url'] ?? null,
        !empty($payment['date_of_expiration']) ? date('Y-m-d H:i:s', strtotime($payment['date_of_expiration'])) : null,
        $order['id'],
    ]);
    return apply_payment_to_order($payment, str_starts_with((string) ($payment['id'] ?? ''), 'DEMO')) ?? $order;
}

// Si había un recibo Efecty o PSE sin pagar, se anula al elegir otro intento para evitar cobros dobles
if (!is_demo_mode() && $order['mp_payment_id'] && in_array($order['status'], ['pending', 'in_process'], true)
    && in_array($order['payment_type'], ['efecty', 'pse'], true)) {
    try {
        mp_cancel_payment($order['mp_payment_id']);
    } catch (Throwable) {
        // Si no se puede anular, el blindaje de apply_payment_to_order evita afectar un pedido aprobado.
    }
}

// El valor a cobrar siempre es el precio vigente del plan (el recibo anterior, si existía, ya se anuló)
$order = reprice_unpaid_order($order);

$idempotency = 'pay-' . $order['external_reference'] . '-' . bin2hex(random_bytes(8));
$deviceId = (string) ($_POST['device_id'] ?? '');

try {
    // ------------------------------------------------------------------ TARJETA
    if ($method === 'card') {
        $name = trim((string) ($_POST['cardholder'] ?? ''));
        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');
        $installments = max(1, min(48, (int) ($_POST['installments'] ?? 1)));
        if (mb_strlen($name) < 3) fail('Escribe el nombre como aparece en la tarjeta.');

        if (is_demo_mode()) {
            $payment = ['id' => 'DEMO' . random_int(100000, 999999), 'status' => 'approved', 'status_detail' => 'accredited',
                'external_reference' => $order['external_reference'], 'payment_method_id' => 'demo_card'];
        } else {
            $token = (string) ($_POST['token'] ?? '');
            $pmId = (string) ($_POST['payment_method_id'] ?? '');
            if (!preg_match('/^[a-f0-9]{20,64}$/i', $token) || !preg_match('/^[a-z_]{2,30}$/', $pmId)) {
                fail('No pudimos validar la tarjeta. Revisa los datos e intenta de nuevo.');
            }
            $payload = mp_base_payment($order, $plan, $user) + [
                'token'                => $token,
                'installments'         => $installments,
                'payment_method_id'    => $pmId,
                'statement_descriptor' => substr(preg_replace('/[^A-Z0-9 ]/', '', strtoupper(MP_STATEMENT_DESCRIPTOR)), 0, 22),
                'three_d_secure_mode'  => 'optional',
                'payer'                => [
                    'email'          => $user['email'],
                    'first_name'     => $first,
                    'last_name'      => $last,
                    'identification' => ['type' => $idType, 'number' => $idNumber],
                ],
            ];
            if (ctype_digit((string) ($_POST['issuer_id'] ?? ''))) {
                $payload['issuer_id'] = (int) $_POST['issuer_id'];
            }
            $payment = mp_create_payment($payload, $idempotency, $deviceId);
        }

        $updated = save_attempt($order, $payment, 'card');
        $status = (string) $payment['status'];
        $detail = (string) ($payment['status_detail'] ?? '');

        if ($status === 'pending' && $detail === 'pending_challenge' && !empty($payment['three_ds_info']['external_resource_url'])) {
            respond(['status' => 'challenge', 'url' => $payment['three_ds_info']['external_resource_url'],
                'creq' => $payment['three_ds_info']['creq'] ?? '']);
        }
        if ($updated['status'] === 'approved') {
            respond(['status' => 'approved', 'redirect' => $returnUrl]);
        }
        if (in_array($status, ['pending', 'in_process'], true)) {
            respond(['status' => 'in_process', 'message' => payment_message($status, $detail), 'redirect' => $returnUrl]);
        }
        respond(['status' => 'rejected', 'message' => payment_message($status, $detail)]);
    }

    // ------------------------------------------------------------------ PSE
    if ($method === 'pse') {
        $bank = (string) ($_POST['bank'] ?? '');
        $entity = ($_POST['entity_type'] ?? '') === 'association' ? 'association' : 'individual';
        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $phone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? ''));
        $street = trim((string) ($_POST['address'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? ''));
        if (mb_strlen($first) < 2 || ($entity === 'individual' && mb_strlen($last) < 2)) fail('Escribe nombres y apellidos (o la razón social).');
        if (strlen($phone) < 7) fail('Escribe un teléfono válido.');
        if (mb_strlen($street) < 5 || $city === '' || $state === '') fail('Completa la dirección, la ciudad y el departamento.');

        if (is_demo_mode()) {
            $payment = ['id' => 'DEMO' . random_int(100000, 999999), 'status' => 'approved', 'status_detail' => 'accredited',
                'external_reference' => $order['external_reference'], 'payment_method_id' => 'pse'];
            save_attempt($order, $payment, 'pse');
            respond(['status' => 'redirect', 'url' => $returnUrl]);
        }
        if (!array_key_exists($bank, mp_pse_banks())) fail('Selecciona tu banco.');

        $payload = mp_base_payment($order, $plan, $user) + [
            'payment_method_id'   => 'pse',
            'callback_url'        => $returnUrl,
            'transaction_details' => ['financial_institution' => $bank],
            'payer'               => [
                'email'          => $user['email'],
                'entity_type'    => $entity,
                'first_name'     => mb_substr($first, 0, 60),
                'last_name'      => mb_substr($last !== '' ? $last : $first, 0, 60),
                'identification' => ['type' => $idType, 'number' => $idNumber],
                'phone'          => ['area_code' => '57', 'number' => $phone],
                'address'        => [
                    'zip_code'      => preg_replace('/\D/', '', (string) ($_POST['zip'] ?? '')) ?: '000000',
                    'street_name'   => mb_substr($street, 0, 100),
                    'street_number' => '0',
                    'neighborhood'  => mb_substr(trim((string) ($_POST['neighborhood'] ?? '')) ?: $city, 0, 60),
                    'city'          => mb_substr($city, 0, 60),
                    'federal_unit'  => mb_substr($state, 0, 60),
                ],
            ],
        ];
        $payment = mp_create_payment($payload, $idempotency, $deviceId);
        save_attempt($order, $payment, 'pse');
        $bankUrl = $payment['transaction_details']['external_resource_url'] ?? '';
        if ($bankUrl === '') fail('PSE no devolvió el enlace del banco. Intenta de nuevo o usa otro medio.');
        respond(['status' => 'redirect', 'url' => $bankUrl]);
    }

    // ------------------------------------------------------------------ EFECTY
    if ($method === 'efecty') {
        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        if (mb_strlen($first) < 2 || mb_strlen($last) < 2) fail('Escribe nombres y apellidos.');

        if (is_demo_mode()) {
            $payment = ['id' => 'DEMO' . random_int(100000, 999999), 'status' => 'pending', 'status_detail' => 'pending_waiting_payment',
                'external_reference' => $order['external_reference'], 'payment_method_id' => 'efecty',
                'date_of_expiration' => date('c', strtotime('+3 days'))];
        } else {
            $payload = mp_base_payment($order, $plan, $user) + [
                'payment_method_id'  => 'efecty',
                'date_of_expiration' => date('Y-m-d\TH:i:s.000P', strtotime('+3 days')),
                'payer'              => [
                    'email'          => $user['email'],
                    'first_name'     => mb_substr($first, 0, 60),
                    'last_name'      => mb_substr($last, 0, 60),
                    'identification' => ['type' => $idType, 'number' => $idNumber],
                ],
            ];
            $payment = mp_create_payment($payload, $idempotency, $deviceId);
        }
        save_attempt($order, $payment, 'efecty');
        respond(['status' => 'voucher', 'redirect' => url('pago/pagar.php?ref=' . urlencode($order['external_reference']))]);
    }

    fail('Selecciona un medio de pago.');
} catch (MpApiException $ex) {
    $cause = $ex->response['cause'][0]['description'] ?? '';
    $msg = match (true) {
        str_contains($ex->getMessage(), 'financial_institution') => 'El banco seleccionado no está disponible. Elige otro.',
        str_contains($ex->getMessage(), 'identification')        => 'Revisa el tipo y número de documento.',
        str_contains($ex->getMessage(), 'amount')                => 'Este medio de pago no admite el valor de la licencia. Elige otro medio.',
        default                                                  => 'No pudimos procesar el pago' . ($cause ? " ($cause)" : '') . '. Intenta de nuevo o usa otro medio.',
    };
    fail($msg);
} catch (Throwable $ex) {
    log_payment('payment_exception', ['ref' => $order['external_reference'], 'error' => $ex->getMessage()]);
    fail('No pudimos conectar con la pasarela de pagos. Intenta de nuevo en unos minutos.', 502);
}
