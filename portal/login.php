<?php
require_once __DIR__ . '/../includes/bootstrap.php';

/** Solo permite redirecciones internas tras el inicio de sesión. */
function safe_next(?string $next): string
{
    $next = (string) $next;
    if ($next === '') {
        return 'portal/index.php';
    }
    if (str_starts_with($next, BASE_URL . '/')) {
        return $next;
    }
    $path = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
    if ($path !== '' && str_starts_with($next, $path . '/') && !str_starts_with($next, '//')) {
        return 'http' . (str_starts_with(BASE_URL, 'https') ? 's' : '') . '://' . parse_url(BASE_URL, PHP_URL_HOST)
            . (parse_url(BASE_URL, PHP_URL_PORT) ? ':' . parse_url(BASE_URL, PHP_URL_PORT) : '') . $next;
    }
    if (preg_match('#^[a-z0-9_\-/]+\.php(\?[^\s]*)?$#i', $next) && !str_contains($next, '..')) {
        return $next;
    }
    return 'portal/index.php';
}

if (!empty($_GET['next'])) {
    $_SESSION['after_login'] = (string) $_GET['next'];
}
if (current_user()) {
    redirect(empty($_SESSION['after_login']) && is_admin() ? 'admin/index.php' : safe_next($_SESSION['after_login'] ?? ''));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    // Limitar intentos por sesión para frenar ataques de fuerza bruta
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0);
    if ($_SESSION['login_attempts'] >= 8 && (time() - ($_SESSION['login_last'] ?? 0)) < 300) {
        $errors[] = 'Demasiados intentos. Espere unos minutos e intente de nuevo.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash, is_admin FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
            unset($_SESSION['login_attempts'], $_SESSION['login_last']);
            $next = empty($_SESSION['after_login']) && $row['is_admin'] ? 'admin/index.php' : safe_next($_SESSION['after_login'] ?? '');
            unset($_SESSION['after_login']);
            login_user((int) $row['id']);
            redirect($next);
        }
        $_SESSION['login_attempts']++;
        $_SESSION['login_last'] = time();
        $errors[] = 'Correo o contraseña incorrectos.';
    }
}

$pageTitle = 'Portal de clientes | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css'];
include __DIR__ . '/../includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="auth-wrap">
      <div class="auth-title">
        <span class="eyebrow"><span class="dot"></span> Portal de clientes</span>
        <h1>Bienvenido de nuevo</h1>
        <p class="muted">Ingresa para gestionar tus licencias y usuarios.</p>
      </div>
      <form class="card" method="post" novalidate>
        <?= csrf_field() ?>
        <?php include __DIR__ . '/../includes/flash.php'; ?>
        <div class="field" style="margin-bottom:16px"><label for="email">Correo electrónico</label><input class="input" id="email" name="email" type="email" value="<?= old('email') ?>" required autocomplete="email" autofocus></div>
        <div class="field" style="margin-bottom:24px"><label for="password">Contraseña</label><input class="input" id="password" name="password" type="password" required autocomplete="current-password"></div>
        <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
      </form>
      <p class="auth-alt">¿Aún no tienes una licencia? <a href="<?= url('index.php#precios') ?>">Ver precios</a></p>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
