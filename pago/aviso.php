<?php
/** El cliente informa que ya pagó su pedido: queda en verificación para el administrador. */
require_once __DIR__ . '/../includes/pedidos.php';

$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('portal/index.php?tab=pedidos');
}
csrf_check();

$order = find_order_by_reference((string) ($_POST['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    flash('error', 'Pedido no encontrado.');
    redirect('portal/index.php?tab=pedidos');
}

if ($order['status'] === 'pending') {
    set_order_status($order, 'in_process', 'El cliente informó el pago el ' . date('d/m/Y H:i'));
    log_event('pago_informado', ['pedido' => $order['external_reference'], 'usuario' => $user['email'], 'valor' => $order['amount']]);
}

flash('success', 'Gracias. Verificaremos tu pago y activaremos la licencia; te avisaremos por correo.');
redirect('pago/pagar.php?ref=' . urlencode($order['external_reference']));
