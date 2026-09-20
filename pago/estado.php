<?php
/** Estado actual de un pedido (JSON). Consulta Mercado Pago si el pago sigue pendiente. */
require_once __DIR__ . '/../includes/mercadopago.php';

header('Content-Type: application/json; charset=utf-8');
$user = current_user();
$order = $user ? find_order_by_reference((string) ($_GET['ref'] ?? '')) : null;
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    http_response_code(404);
    exit(json_encode(['status' => 'error']));
}

// Refrescar desde la API como máximo cada 4 segundos por sesión
if (!is_demo_mode() && $order['mp_payment_id'] && in_array($order['status'], ['pending', 'in_process'], true)
    && (time() - ($_SESSION['last_status_check'] ?? 0)) >= 4) {
    $_SESSION['last_status_check'] = time();
    try {
        if ($payment = mp_get_payment((string) $order['mp_payment_id'])) {
            $order = apply_payment_to_order($payment) ?? $order;
            $detail = (string) ($payment['status_detail'] ?? '');
        }
    } catch (Throwable) {
    }
}

echo json_encode([
    'status'   => $order['status'],
    'message'  => payment_message($order['status'], $detail ?? (string) $order['mp_status_detail']),
    'redirect' => url('pago/retorno.php?external_reference=' . urlencode($order['external_reference'])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
