<?php
/**
 * Configuración general del sitio.
 *
 * Las credenciales (base de datos y cuenta de administrador) NO se guardan en este archivo: van en
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
cfg('CONTACT_EMAIL', 'gestion@grupologiclatam.com');
cfg('CONTACT_WHATSAPP', ''); // vacío = no se muestra teléfono/WhatsApp en el sitio
cfg('SELLER_COUNTRY', 'España');         // país desde el que opera el comercio

// ---------------------------------------------------------------
// Datos legales del titular del sitio (aparecen en los documentos legales).
// IMPORTANTE: complete la razón social, el NIF y la dirección antes de solicitar
// la revisión del dominio en Paddle o cualquier otra pasarela.
// ---------------------------------------------------------------
cfg('COMPANY_LEGAL_NAME', 'Grupo Logic SC');
cfg('COMPANY_TAX_ID', '');                // NIF / CIF (pendiente de confirmar)
cfg('COMPANY_ADDRESS', 'Calle Santa Isabel, 52'); // calle y número
cfg('COMPANY_CITY', '28012 Madrid');      // código postal y ciudad (el país va en COMPANY_COUNTRY)
cfg('SUPPORT_EMAIL', CONTACT_EMAIL);      // correo de atención al cliente
cfg('LEGAL_EMAIL', CONTACT_EMAIL);        // correo para asuntos legales y privacidad
cfg('REFUND_DAYS', 14);                   // días para solicitar la devolución
cfg('LEGAL_UPDATED', '2026-10-04');       // última actualización de los documentos

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
// Cobros: se usan enlaces de pago de Mercado Pago, uno por plan.
// El enlace de cada plan se define en $PLANS y se puede cambiar desde
// Administración > Precios sin tocar este archivo.
// ---------------------------------------------------------------
cfg('CURRENCY', 'COP');

// ---------------------------------------------------------------
// Facturación (España, RD 1619/2012). Los precios de los planes se entienden IVA incluido:
// la factura desglosa base imponible y cuota a partir del total cobrado.
// ---------------------------------------------------------------
cfg('COMPANY_COUNTRY', 'España');          // país del domicilio fiscal del emisor
cfg('VAT_RATE', 21);                       // tipo de IVA (%). 0 = la factura indica operación no sujeta
cfg('VAT_EXEMPTION_NOTE', 'Operación no sujeta a IVA por reglas de localización (art. 69.Uno.2.º de la Ley 37/1992 del IVA).');
// COP por 1 EUR. La cuota de IVA debe expresarse también en euros (art. 10 RD 1619/2012).
// Se usa solo si el pedido no tiene su propio tipo de cambio (se registra al aprobar el pago).
cfg('EUR_RATE_DEFAULT', 4500);

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
        'months'       => 12,
        'payment_link' => 'https://mpago.li/1NqjH7q',
        'description'  => 'Para una institución educativa con una sede.',
        'features'    => [
            'Hasta 60 usuarios (docentes y directivos)',
            '1 sede educativa',
            'Planeación de clases y guías con IA',
            'Generador de evaluaciones y rúbricas',
            'Corrección asistida y reportes de desempeño',
            'Materiales accesibles e inclusivos',
            'Capacitación inicial en línea',
            'Soporte por correo electrónico',
        ],
    ],
    'volumen' => [
        'code'        => 'volumen',
        'name'        => 'Licencia por Volumen',
        'price'       => 7500000,
        'seats'       => 250,
        'campuses'    => 5,
        'months'       => 12,
        'payment_link' => 'https://mpago.li/2ZDsqn6',
        'description'  => 'Para redes de colegios, secretarías y grupos con varias sedes.',
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
