<?php
/**
 * Webhook de notificaciones de Mercado Pago (configurar en MP_NOTIFICATION_URL o en el panel de MP).
 * Soporta el formato nuevo (type/data.id) y el IPN clásico (topic/id).
 */
require_once __DIR__ . '/../includes/mercadopago.php';

$body = json_decode(file_get_contents('php://input') ?: '', true) ?: [];
log_payment('webhook', ['query' => $_GET, 'body' => $body]);

$type = (string) ($body['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '');
$dataId = (string) ($body['data']['id'] ?? $_GET['data_id'] ?? $_GET['data.id'] ?? $_GET['id'] ?? '');

if (is_demo_mode() || $dataId === '') {
    http_response_code(200);
    exit('ok');
}

// La firma se calcula con el data.id de la URL (PHP lo expone como data_id)
if (!mp_valid_webhook_signature((string) ($_GET['data_id'] ?? $dataId))) {
    http_response_code(401);
    exit('invalid signature');
}

try {
    if ($type === 'payment') {
        $payment = mp_get_payment($dataId);
        if ($payment) {
            apply_payment_to_order($payment);
        }
    } elseif ($type === 'merchant_order') {
        [$status, $mo] = mp_request('GET', '/merchant_orders/' . rawurlencode($dataId));
        if ($status === 200) {
            foreach ($mo['payments'] ?? [] as $p) {
                if ($payment = mp_get_payment((string) $p['id'])) {
                    apply_payment_to_order($payment);
                }
            }
        }
    }
    http_response_code(200);
    echo 'ok';
} catch (Throwable $ex) {
    log_payment('webhook_error', ['id' => $dataId, 'error' => $ex->getMessage()]);
    http_response_code(500); // Mercado Pago reintentará la notificación
    echo 'error';
}
