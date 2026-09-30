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

    $pdo->prepare('INSERT INTO licenses (user_id, order_id, plan_code, license_key, seats, campuses, status, starts_at, expires_at)
                   VALUES (?, ?, ?, ?, ?, ?, "active", NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH))')
        ->execute([$order['user_id'], $orderId, $plan['code'], generate_license_key($plan['code']),
            $plan['seats'], $plan['campuses'], $plan['months']]);

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
