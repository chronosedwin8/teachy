<?php
/**
 * Instalador: crea las tablas y aplica las migraciones pendientes.
 * Uso recomendado en el servidor: `php install.php` por línea de comandos.
 * Desde el navegador solo se permite en local (en producción se bloquea).
 */
require_once __DIR__ . '/config.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$local || IS_PRODUCTION) {
        http_response_code(403);
        exit('Instalación permitida solo desde localhost o por línea de comandos.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT);
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

    // Si la base ya existe (hosting), se usa directamente; si no, se intenta crear (entorno local).
    try {
        $pdo = new PDO($dsn . ';dbname=' . DB_NAME, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');
        echo 'Base de datos ' . DB_NAME . " creada.\n";
    }

    $sql = (string) file_get_contents(__DIR__ . '/sql/schema.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    // CREATE DATABASE / USE se omiten: la base ya está seleccionada en la conexión
    $statements = array_filter($statements, fn($s) => !preg_match('/^(CREATE DATABASE|USE)\b/i', $s));
    $inserts = array_filter($statements, fn($s) => stripos($s, 'INSERT') === 0);

    foreach (array_diff_key($statements, $inserts) as $statement) {
        $pdo->exec($statement);
    }

    // Migraciones para bases de datos creadas con versiones anteriores
    $migrations = [
        ['users', 'is_admin', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER city'],
        ['orders', 'payment_type', 'VARCHAR(40) NULL AFTER payment_method'],
        ['orders', 'mp_resource_url', 'VARCHAR(600) NULL AFTER payment_type'],
        ['orders', 'mp_expires_at', 'DATETIME NULL AFTER mp_resource_url'],
    ];
    foreach ($migrations as [$table, $column, $definition]) {
        $exists = $pdo->query('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ' . $pdo->quote(DB_NAME)
            . ' AND TABLE_NAME = ' . $pdo->quote($table) . ' AND COLUMN_NAME = ' . $pdo->quote($column))->fetchColumn();
        if (!$exists) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Migración: columna $table.$column agregada.\n";
        }
    }

    foreach ($inserts as $statement) {
        $pdo->exec($statement);
    }

    // Cuenta de administrador (definida en config.secrets.php, nunca en el repositorio)
    if (ADMIN_EMAIL !== '' && ADMIN_PASSWORD_HASH !== '') {
        $pdo->prepare('INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)
                       ON DUPLICATE KEY UPDATE is_admin = 1, password_hash = VALUES(password_hash)')
            ->execute(['Administrador', strtolower(ADMIN_EMAIL), ADMIN_PASSWORD_HASH]);
        echo 'Administrador: ' . ADMIN_EMAIL . "\n";
    }

    echo 'OK: base de datos ' . DB_NAME . " lista.\n";
    echo 'Sitio: ' . BASE_URL . "/\n";
} catch (Throwable $e) {
    if (!$cli) {
        http_response_code(500);
    }
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
