<?php
/**
 * URL de retorno de Mercado Pago (back_urls). Verifica el pago directamente contra la API
 * (nunca confía en los parámetros de la URL) y redirige al portal de clientes.
 */
require_once __DIR__ . '/../includes/mercadopago.php';

$user = current_user();
$reference = (string) ($_GET['external_reference'] ?? $_SESSION['last_order'] ?? '');
$paymentId = (string) ($_GET['payment_id'] ?? $_GET['collection_id'] ?? '');
$order = $reference !== '' ? find_order_by_reference($reference) : null;

log_payment('return', $_GET);

if (!$order) {
    flash('error', 'No encontramos el pedido asociado al pago.');
    redirect($user ? 'portal/index.php' : 'portal/login.php');
}

if (!is_demo_mode()) {
    try {
        $payment = ($paymentId !== '' && $paymentId !== 'null') ? mp_get_payment($paymentId) : null;
        if (!$payment || ($payment['external_reference'] ?? '') !== $order['external_reference']) {
            $payment = mp_search_payment_by_reference($order['external_reference']);
        }
        if ($payment) {
            $order = apply_payment_to_order($payment) ?? $order;
        } elseif (($_GET['r'] ?? '') === 'failure') {
            db()->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status = 'pending'")->execute([$order['id']]);
            $order['status'] = 'cancelled';
        }
    } catch (Throwable $ex) {
        log_payment('return_error', ['order' => $order['id'], 'error' => $ex->getMessage()]);
    }
}

// Solo el dueño del pedido puede verlo en el portal
if (!$user || (int) $user['id'] !== (int) $order['user_id']) {
    flash('info', 'Inicie sesión para ver el estado de su compra.');
    $_SESSION['after_login'] = url('portal/index.php');
    redirect('portal/login.php');
}

switch ($order['status']) {
    case 'approved':
        $lic = db()->prepare('SELECT id FROM licenses WHERE order_id = ?');
        $lic->execute([$order['id']]);
        $licenseId = $lic->fetchColumn();
        flash('success', '¡Pago aprobado! Tu licencia ya está activa. Ahora puedes agregar a los usuarios de tu institución.');
        redirect($licenseId ? 'portal/licencia.php?id=' . $licenseId : 'portal/index.php');
    case 'pending':
    case 'in_process':
        if ($order['payment_type'] === 'efecty') {
            redirect('pago/pagar.php?ref=' . urlencode($order['external_reference']));
        }
        flash('warn', 'Tu pago está en proceso. Activaremos la licencia automáticamente apenas se confirme.');
        redirect('portal/index.php');
    default:
        flash('error', 'El pago no fue completado. Puedes intentarlo de nuevo desde tus pedidos.');
        redirect('portal/index.php?tab=pedidos');
}
