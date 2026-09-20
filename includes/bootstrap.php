<?php
/**
 * Arranque común: configuración, sesión, conexión a BD y utilidades.
 */
require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('edu_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => str_starts_with(BASE_URL, 'https://'),
    ]);
    session_start();
}

/** Convierte a UTF-8 cualquier entrada enviada con otra codificación (p. ej. Windows-1252). */
function normalize_input(array $data): array
{
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            $data[$k] = normalize_input($v);
        } elseif (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
            $data[$k] = mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
        }
    }
    return $data;
}
$_POST = normalize_input($_POST);
$_GET = normalize_input($_GET);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('No fue posible conectar con la base de datos. Ejecute ' . BASE_URL . '/install.php');
        }
    }
    return $pdo;
}

/** Escapa texto para HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

function money(float|int $amount): string
{
    return '$' . number_format((float) $amount, 0, ',', '.') . ' ' . CURRENCY;
}

/**
 * Planes vigentes: valores por defecto de config.php combinados con los cambios hechos desde
 * el panel de administración (tabla plan_settings).
 */
function plans(bool $refresh = false): array
{
    global $PLANS;
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = $PLANS;
        try {
            foreach (db()->query('SELECT code, data FROM plan_settings') as $row) {
                if (isset($cache[$row['code']])) {
                    $override = json_decode($row['data'], true) ?: [];
                    $cache[$row['code']] = array_merge($cache[$row['code']], array_intersect_key($override, $cache[$row['code']]));
                }
            }
        } catch (PDOException) {
            // Tabla aún no creada (instalación pendiente): se usan los valores de config.php
        }
    }
    return $cache;
}

function plan(string $code): ?array
{
    return plans()[$code] ?? null;
}

function member_roles(): array
{
    global $MEMBER_ROLES;
    return $MEMBER_ROLES;
}

/** Pago simulado: sin credenciales, o en localhost salvo que se habilite MP_LIVE_ON_LOCALHOST. */
function is_demo_mode(): bool
{
    if (trim(MP_ACCESS_TOKEN) === '') {
        return true;
    }
    return !IS_PRODUCTION && !MP_LIVE_ON_LOCALHOST;
}

// ---------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión expiró. Vuelva atrás y recargue la página.');
    }
}

// ---------------------------------------------------------------
// Mensajes flash
// ---------------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

// ---------------------------------------------------------------
// Autenticación
// ---------------------------------------------------------------
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$userId]);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('info', 'Inicie sesión para acceder al portal de clientes.');
        redirect('portal/login.php');
    }
    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && (int) $user['is_admin'] === 1;
}

function require_admin(): array
{
    $user = require_login();
    if ((int) $user['is_admin'] !== 1) {
        http_response_code(403);
        flash('error', 'No tiene permisos para acceder a la administración.');
        redirect('portal/index.php');
    }
    return $user;
}

/** Id del administrador que está viendo el portal como otro usuario (o null). */
function impersonator_id(): ?int
{
    return isset($_SESSION['impersonator_id']) ? (int) $_SESSION['impersonator_id'] : null;
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function status_label(string $status): array
{
    return match ($status) {
        'approved'   => ['Aprobado', 'ok'],
        'pending'    => ['Pendiente', 'warn'],
        'in_process' => ['En proceso', 'warn'],
        'rejected'   => ['Rechazado', 'bad'],
        'cancelled'  => ['Cancelado', 'bad'],
        'refunded'   => ['Reembolsado', 'bad'],
        'active'     => ['Activa', 'ok'],
        'suspended'  => ['Suspendida', 'bad'],
        'expired'    => ['Vencida', 'bad'],
        default      => [ucfirst($status), 'warn'],
    };
}

function fmt_date(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '—';
    }
    $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $ts = strtotime($date);
    $out = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('g:i a', $ts) : $out;
}
