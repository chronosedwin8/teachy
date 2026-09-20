<?php
require_once __DIR__ . '/includes/mercadopago.php';

$planCode = (string) ($_GET['plan'] ?? $_POST['plan'] ?? 'escuela');
$selected = plan($planCode);
if (!$selected) {
    redirect('index.php#precios');
}
$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name        = trim((string) ($_POST['name'] ?? ''));
    $email       = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password    = (string) ($_POST['password'] ?? '');
    $phone       = trim((string) ($_POST['phone'] ?? ''));
    $institution = trim((string) ($_POST['institution'] ?? ''));
    $taxId       = trim((string) ($_POST['tax_id'] ?? ''));
    $city        = trim((string) ($_POST['city'] ?? ''));

    if ($user) {
        $name = $user['name'];
        $email = $user['email'];
    } else {
        if (mb_strlen($name) < 3) $errors[] = 'Escriba el nombre completo del responsable.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo electrónico no es válido.';
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== ($_POST['password2'] ?? '')) $errors[] = 'Las contraseñas no coinciden.';
        if (!$errors) {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Ya existe una cuenta con este correo. Inicie sesión para continuar con la compra.';
            }
        }
    }
    if (mb_strlen($institution) < 3) $errors[] = 'Escriba el nombre de la institución.';
    if (!preg_match('/^[0-9.\- ]{5,20}$/', $taxId)) $errors[] = 'Escriba un NIT o documento válido (solo números, puntos o guiones).';
    if (!preg_match('/^[0-9+ ()\-]{7,20}$/', $phone)) $errors[] = 'Escriba un teléfono de contacto válido.';
    if ($city === '') $errors[] = 'Escriba la ciudad.';
    if (empty($_POST['terms'])) $errors[] = 'Debe aceptar los términos y la política de tratamiento de datos.';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($user) {
                $pdo->prepare('UPDATE users SET phone = ?, institution = ?, tax_id = ?, city = ? WHERE id = ?')
                    ->execute([$phone, $institution, $taxId, $city, $user['id']]);
                $userId = (int) $user['id'];
            } else {
                $pdo->prepare('INSERT INTO users (name, email, password_hash, phone, institution, tax_id, city) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $institution, $taxId, $city]);
                $userId = (int) $pdo->lastInsertId();
            }

            $reference = 'ORD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
            $pdo->prepare('INSERT INTO orders (user_id, plan_code, amount, currency, external_reference, is_demo) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$userId, $selected['code'], $selected['price'], CURRENCY, $reference, is_demo_mode() ? 1 : 0]);
            $orderId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_payment('checkout_error', ['error' => $ex->getMessage()]);
            $errors[] = 'No fue posible registrar el pedido. Intente de nuevo.';
        }

        if (!$errors) {
            if (!$user) {
                login_user($userId);
            }
            $_SESSION['last_order'] = $reference;
            redirect('pago/pagar.php?ref=' . urlencode($reference));
        }
    }
}

$pageTitle = 'Comprar ' . $selected['name'] . ' | ' . BRAND_NAME;
$extraCss = ['assets/css/portal.css'];
include __DIR__ . '/includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <div class="portal-head">
      <div>
        <span class="eyebrow"><span class="dot"></span> Pago seguro</span>
        <h1>Finaliza tu compra</h1>
        <p>Completa los datos de tu institución y continúa al pago seguro.</p>
      </div>
    </div>

    <div class="checkout-grid">
      <form class="card" method="post" action="<?= url('checkout.php') ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="plan" value="<?= e($selected['code']) ?>">
        <?php include __DIR__ . '/includes/flash.php'; ?>
        <?php if (is_demo_mode()): ?>
          <div class="demo-banner">Modo demostración (entorno local): no se realizará ningún cobro.</div>
        <?php endif; ?>

        <?php if ($user): ?>
          <div class="alert alert-info">Comprando como <strong><?= e($user['name']) ?></strong> (<?= e($user['email']) ?>).
            <a href="<?= url('portal/logout.php') ?>">¿No eres tú?</a></div>
        <?php else: ?>
          <h2>1. Cuenta del responsable</h2>
          <p class="muted" style="font-size:.93rem">Con estos datos ingresarás al Portal de Clientes. ¿Ya tienes cuenta?
            <a href="<?= url('portal/login.php?next=' . urlencode('checkout.php?plan=' . $selected['code'])) ?>">Inicia sesión</a></p>
          <div class="form-grid" style="margin-bottom:30px">
            <div class="field"><label for="name">Nombre completo</label><input class="input" id="name" name="name" value="<?= old('name') ?>" required autocomplete="name"></div>
            <div class="field"><label for="email">Correo electrónico</label><input class="input" id="email" name="email" type="email" value="<?= old('email') ?>" required autocomplete="email"></div>
            <div class="field"><label for="password">Contraseña</label><input class="input" id="password" name="password" type="password" minlength="8" required autocomplete="new-password"><div class="hint">Mínimo 8 caracteres.</div></div>
            <div class="field"><label for="password2">Confirmar contraseña</label><input class="input" id="password2" name="password2" type="password" minlength="8" required autocomplete="new-password"></div>
          </div>
        <?php endif; ?>

        <h2><?= $user ? '' : '2. ' ?>Datos de la institución</h2>
        <div class="form-grid">
          <div class="field full"><label for="institution">Institución educativa / razón social</label><input class="input" id="institution" name="institution" value="<?= old('institution', $user['institution'] ?? '') ?>" required></div>
          <div class="field"><label for="tax_id">NIT o documento</label><input class="input" id="tax_id" name="tax_id" value="<?= old('tax_id', $user['tax_id'] ?? '') ?>" required placeholder="900.123.456-7"></div>
          <div class="field"><label for="phone">Teléfono / WhatsApp</label><input class="input" id="phone" name="phone" value="<?= old('phone', $user['phone'] ?? '') ?>" required autocomplete="tel" placeholder="300 123 4567"></div>
          <div class="field full"><label for="city">Ciudad</label><input class="input" id="city" name="city" value="<?= old('city', $user['city'] ?? '') ?>" required placeholder="Bogotá D.C."></div>
          <div class="field full">
            <label class="check-line"><input type="checkbox" name="terms" value="1" <?= !empty($_POST['terms']) ? 'checked' : '' ?>>
              <span>Acepto los términos y condiciones, autorizo el tratamiento de mis datos personales y entiendo que es una compra de comercio electrónico internacional a un comercio establecido en <?= e(SELLER_COUNTRY) ?>, sin impuestos de países de Latinoamérica incluidos.</span></label>
          </div>
        </div>
        <div style="margin-top:22px"><?php include __DIR__ . '/includes/intl_notice.php'; ?></div>
        <button class="btn btn-blue btn-block" style="margin-top:22px" type="submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
          Continuar al pago · <?= money($selected['price']) ?>
        </button>
        <p class="muted center" style="font-size:.85rem;margin:14px 0 0">En el siguiente paso eliges tarjeta, PSE o Efecty. Tus datos viajan cifrados.</p>
      </form>

      <aside class="card summary">
        <div class="plan-switch">
          <?php foreach (plans() as $code => $p): ?>
            <a class="<?= $code === $selected['code'] ? 'on' : '' ?>" href="<?= url('checkout.php?plan=' . $code) ?>"><?= e($p['name']) ?><small><?= money($p['price']) ?></small></a>
          <?php endforeach; ?>
        </div>
        <div class="plan-name"><?= e($selected['name']) ?></div>
        <p class="muted" style="font-size:.93rem"><?= e($selected['description']) ?></p>
        <div class="summary-row"><span>Usuarios incluidos</span><span><?= $selected['seats'] ?></span></div>
        <div class="summary-row"><span>Sedes</span><span><?= $selected['campuses'] ?></span></div>
        <div class="summary-row"><span>Vigencia</span><span><?= $selected['months'] ?> meses</span></div>
        <div class="summary-row"><span>Activación</span><span>Inmediata al aprobar el pago</span></div>
        <div class="summary-total"><span>Total</span><strong><?= money($selected['price']) ?></strong></div>
        <ul class="checks" style="margin-top:22px">
          <?php foreach (array_slice($selected['features'], 0, 5) as $feat): ?><li><?= e($feat) ?></li><?php endforeach; ?>
        </ul>
        <div class="pay-methods"><span>Visa</span><span>Mastercard</span><span>American Express</span><span>PSE</span><span>Efecty</span><span>Nequi</span></div>
        <div style="margin-top:18px"><?php $noticeCompact = true; include __DIR__ . '/includes/intl_notice.php'; $noticeCompact = false; ?></div>
      </aside>
    </div>
  </div>
</main>
<?php include __DIR__ . '/includes/site_footer.php'; ?>
