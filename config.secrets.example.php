<?php
/**
 * Plantilla de credenciales. Copie este archivo como `config.secrets.php` en cada servidor
 * y complete los valores. `config.secrets.php` NO se sube al repositorio.
 */

// Base de datos
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'nombre_de_la_base');
define('DB_USER', 'usuario');
define('DB_PASS', 'contraseña');



// Cuenta de administrador (el hash se genera con:
//   php -r "echo password_hash('SU_CONTRASEÑA', PASSWORD_BCRYPT, ['cost'=>12]);")
define('ADMIN_EMAIL', 'admin@ejemplo.com');
define('ADMIN_PASSWORD_HASH', '$2y$12$...');
