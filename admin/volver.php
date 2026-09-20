<?php
/** Termina la sesión "entrar como" y regresa a la cuenta del administrador. */
require_once __DIR__ . '/../includes/bootstrap.php';

$adminId = impersonator_id();
if ($adminId === null) {
    redirect('portal/index.php');
}
unset($_SESSION['impersonator_id']);
login_user($adminId);
flash('success', 'Volviste a tu cuenta de administrador.');
redirect('admin/usuarios.php');
