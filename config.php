<?php
/**
 * Configuración general del sitio.
 *
 * Las credenciales (base de datos y Mercado Pago) NO se guardan en este archivo: se definen en
 * `config.secrets.php`, que queda fuera del repositorio. Copie `config.secrets.example.php`
 * como `config.secrets.php` y complete los valores de cada servidor.
 */

if (is_file(__DIR__ . '/config.secrets.php')) {
    require __DIR__ . '/config.secrets.php';
}

/** Define una constante solo si el archivo de credenciales no la definió antes. */
function cfg(string $name, mixed $value): void
{
    if (!defined($name)) {
        define($name, $value);
    }
}

// ---------------------------------------------------------------
// Marca
// ---------------------------------------------------------------
cfg('BRAND_NAME', 'EduNova');
cfg('BRAND_TAGLINE', 'El sistema de aprendizaje con IA para toda la escuela');
cfg('CONTACT_EMAIL', 'ventas@edunova.co');
cfg('CONTACT_WHATSAPP', '573000000000'); // solo dígitos, con indicativo de país
cfg('SELLER_COUNTRY', 'España');         // país desde el que opera el comercio

// ---------------------------------------------------------------
// Entorno: producción cuando el sitio se sirve desde el dominio público.
// nginx redirige teachy.es → www.teachy.es, por eso el host canónico lleva www.
// ---------------------------------------------------------------
cfg('PROD_DOMAIN', 'teachy.es');
cfg('CANONICAL_HOST', 'www.teachy.es');
$__host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
cfg('IS_PRODUCTION', $__host === PROD_DOMAIN || str_ends_with($__host, '.' . PROD_DOMAIN));

// URL base (sin "/" final).
cfg('BASE_URL', IS_PRODUCTION ? 'https://' . CANONICAL_HOST : 'http://localhost:8080/teachy');

// ---------------------------------------------------------------
// Base de datos MySQL (los valores reales van en config.secrets.php)
// ---------------------------------------------------------------
cfg('DB_HOST', '127.0.0.1');
cfg('DB_PORT', 3306);
cfg('DB_NAME', 'teachy_db');
cfg('DB_USER', 'root');
cfg('DB_PASS', '');

// ---------------------------------------------------------------
// Mercado Pago (Colombia - COP). Credenciales en config.secrets.php.
// Panel: https://www.mercadopago.com.co/developers/panel/app
// ---------------------------------------------------------------
cfg('MP_PUBLIC_KEY', '');
cfg('MP_ACCESS_TOKEN', '');
cfg('MP_WEBHOOK_SECRET', '');
// URL que recibe las notificaciones de pago (solo se envía en producción, https).
cfg('MP_NOTIFICATION_URL', IS_PRODUCTION ? BASE_URL . '/pago/webhook.php' : '');
// En localhost se mantiene el pago simulado para no generar cobros reales durante pruebas.
cfg('MP_LIVE_ON_LOCALHOST', false);
// Nombre que verá el cliente en el extracto de su tarjeta (máx. 22 caracteres, sin tildes).
cfg('MP_STATEMENT_DESCRIPTOR', 'EDUNOVA');
cfg('CURRENCY', 'COP');

// ---------------------------------------------------------------
// Cuenta de administrador que crea install.php (valores en config.secrets.php)
// ---------------------------------------------------------------
cfg('ADMIN_EMAIL', '');
cfg('ADMIN_PASSWORD_HASH', '');

// ---------------------------------------------------------------
// Planes / licencias (los precios se editan desde Administración > Precios)
// ---------------------------------------------------------------
$PLANS = [
    'escuela' => [
        'code'        => 'escuela',
        'name'        => 'Licencia Escuela',
        'price'       => 4500000,
        'seats'       => 60,
        'campuses'    => 1,
        'months'      => 12,
        'description' => 'Para una institución educativa con una sede.',
        'features'    => [
            'Hasta 60 usuarios (docentes y directivos)',
            '1 sede educativa',
            'Planeación de clases y guías con IA',
            'Generador de evaluaciones y rúbricas',
            'Corrección asistida y reportes de desempeño',
            'Materiales accesibles e inclusivos',
            'Capacitación inicial en línea',
            'Soporte por correo y WhatsApp',
        ],
    ],
    'volumen' => [
        'code'        => 'volumen',
        'name'        => 'Licencia por Volumen',
        'price'       => 7500000,
        'seats'       => 250,
        'campuses'    => 5,
        'months'      => 12,
        'description' => 'Para redes de colegios, secretarías y grupos con varias sedes.',
        'features'    => [
            'Hasta 250 usuarios en todas las sedes',
            'Hasta 5 sedes o instituciones',
            'Todo lo incluido en la Licencia Escuela',
            'Panel de indicadores para directivos',
            'Libros y materiales personalizados (Studio)',
            'Plan de recuperación y seguimiento por estudiante',
            'Capacitación presencial o virtual por sede',
            'Gerente de cuenta dedicado y soporte prioritario',
        ],
    ],
];

// Roles disponibles para los usuarios de una licencia
$MEMBER_ROLES = [
    'docente'     => 'Docente',
    'coordinador' => 'Coordinador(a)',
    'directivo'   => 'Directivo(a)',
    'orientador'  => 'Orientador(a)',
    'admin'       => 'Administrador(a) de la licencia',
];

date_default_timezone_set('America/Bogota');
