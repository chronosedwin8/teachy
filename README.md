# Landing + venta de licencias + portal de clientes

Stack: PHP 8.2 (sin dependencias), MySQL 8, HTML/CSS/JS, Mercado Pago Checkout Pro (API REST).

## Instalación
1. Revisar `config.php` (BD, `BASE_URL`, marca, precios).
2. Abrir `http://localhost:8080/teachy/install.php` (o `php install.php`) para crear la BD `teachy_db`.
3. Abrir `http://localhost:8080/teachy/`.

## Entornos
`config.php` detecta el entorno por el dominio:
- **Producción** (`teachy.es` / `www.teachy.es`): `BASE_URL = https://teachy.es`, Mercado Pago real,
  `auto_return` y `notification_url` activos.
- **Local** (`localhost:8080/teachy`): pago simulado (sin cobros). Para probar el checkout real desde
  local, poner `MP_LIVE_ON_LOCALHOST = true` (cobra dinero real).

## Publicar en teachy.es
1. Subir el contenido de esta carpeta a la **raíz** del dominio (el sitio espera `https://teachy.es/`).
2. Certificado SSL activo (https es obligatorio para el retorno automático y el webhook).
3. Crear la base de datos en el hosting e importar `sql/schema.sql` (phpMyAdmin). Si el hosting asigna
   otro nombre de BD, quitar las líneas `CREATE DATABASE` / `USE` del archivo antes de importar.
4. Ajustar el bloque `IS_PRODUCTION` de base de datos en `config.php` con los datos del hosting.
5. PHP 8.1+ con extensiones `curl`, `pdo_mysql`, `mbstring`.

## Mercado Pago
En `config.php`:
- `MP_ACCESS_TOKEN` / `MP_PUBLIC_KEY`: credenciales de producción (cuenta Colombia, MCO).
- Webhook: `https://teachy.es/pago/webhook.php` (evento **Pagos**). Se envía también en cada cobro.
- `MP_WEBHOOK_SECRET`: clave secreta del webhook (Panel > Webhooks) para validar `x-signature`.
- `MP_STATEMENT_DESCRIPTOR`: texto del extracto de la tarjeta.

Integración **Checkout API** (pantalla de cobro propia, sin pantallas de Mercado Pago):
- Tarjeta: campos seguros (Secure Fields) de `sdk.mercadopago.com/js/v2`, con el estilo del sitio; el
  navegador genera un token y el servidor cobra con `POST /v1/payments`. Soporta cuotas y 3D Secure.
  El número de la tarjeta nunca llega al servidor.
- PSE: formulario propio con la lista de bancos de la API → redirección al portal del banco →
  regreso a `pago/retorno.php`.
- Efecty: recibo propio en `pago/pagar.php` (vence en 3 días).

## Flujo
1. `index.php#precios` → `checkout.php?plan=escuela|volumen` (crea la cuenta y el pedido).
2. `pago/pagar.php` (pantalla de cobro) → `pago/procesar.php` (cobro) / `pago/estado.php` (consulta).
3. `pago/retorno.php` verifica el pago contra la API (monto y moneda incluidos), activa la licencia y
   redirige al portal. `pago/webhook.php` hace lo mismo de forma asíncrona (idempotente).
4. `portal/` → licencias, código, vigencia, cupos; alta individual/masiva de usuarios, cambio de rol
   y sede, retiro de usuarios, exportación CSV, historial de pedidos y reintento de pago.

## Administración (`/admin/`)
El administrador inicia sesión en `portal/login.php` y entra directo al panel. La cuenta
`chronosedwin8@gmail.com` se crea con `sql/schema.sql` / `install.php` (solo se guarda el hash de la
contraseña). Permite:
- Resumen: ventas, pedidos por estado, licencias activas, vencimientos próximos.
- Pedidos: filtrar, sincronizar con Mercado Pago, aprobar manualmente, cancelar, eliminar y otorgar
  licencias sin pago (transferencias, convenios, cortesías).
- Licencias: editar cupos, sedes, estado y vencimiento; renovar 12 meses; gestionar sus usuarios.
- Usuarios: crear, dar/quitar permisos de administrador, restablecer contraseñas, eliminar y
  "Entrar como" un cliente para ver su portal.
- Registro de pagos: retornos, webhooks y errores de Mercado Pago.

## Estructura
```
config.php              Configuración (marca, BD, MP, planes, roles)
index.php               Landing page
checkout.php            Compra
pago/                   retorno, webhook, reintentar, simular (demo)
portal/                 login, logout, index (panel), licencia (usuarios)
includes/               bootstrap, mercadopago, plantillas (bloqueado por .htaccess)
assets/                 css, js, img
sql/schema.sql          Esquema de BD
```
