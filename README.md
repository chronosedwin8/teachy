# EduNova — landing, venta de licencias y portal de clientes

PHP 8.1+ sin dependencias · MySQL · HTML/CSS/JS · Mercado Pago **Checkout API** (pantalla de cobro propia).

## Entornos
`config.php` detecta el entorno por el dominio:
- **Producción** (`teachy.es` / `www.teachy.es`): `BASE_URL = https://www.teachy.es`, cobros reales.
- **Local** (`localhost:8080/teachy`): pagos simulados, sin cobros. Para probar cobros reales desde
  local, definir `MP_LIVE_ON_LOCALHOST = true` (cobra dinero de verdad).

Las credenciales viven en `config.secrets.php` (fuera del repositorio). Copiar
`config.secrets.example.php` y completar: base de datos, Mercado Pago y la cuenta de administrador
(`ADMIN_EMAIL` + `ADMIN_PASSWORD_HASH`, generado con `password_hash`).

## Instalación local
1. Copiar `config.secrets.example.php` → `config.secrets.php` y completarlo.
2. `php install.php` (crea la base y las tablas, aplica migraciones y crea el administrador).
3. Abrir `http://localhost:8080/teachy/`.

## Mercado Pago
- Credenciales de producción de una cuenta de Colombia (MCO), moneda COP.
- Webhook: `https://www.teachy.es/pago/webhook.php`, evento **Pagos**. También funciona en
  `https://teachy.es/pago/webhook.php`. `MP_WEBHOOK_SECRET` valida la firma `x-signature`.
- `MP_STATEMENT_DESCRIPTOR`: texto que verá el cliente en el extracto de su tarjeta.
- Medios: tarjeta (Secure Fields + 3D Secure + cuotas), PSE (47 bancos) y Efecty (recibo a 3 días).
  El número de la tarjeta nunca llega al servidor: el navegador lo convierte en un token.

## Flujo de compra
1. `index.php#precios` → `checkout.php?plan=escuela|volumen` (crea la cuenta y el pedido).
2. `pago/pagar.php` (pantalla de cobro propia) → `pago/procesar.php` (cobra) y `pago/estado.php` (consulta).
3. `pago/retorno.php` verifica el pago contra la API (monto y moneda), activa la licencia y lleva al
   portal. `pago/webhook.php` hace lo mismo de forma asíncrona; procesar dos veces el mismo pago no
   duplica la licencia.
4. `portal/` → licencias, código, vigencia y cupos; alta individual o masiva de usuarios, cambio de
   rol y sede, retiro, exportación CSV, historial de pedidos y reintento de pago.

## Administración (`/admin/`)
Se entra por `portal/login.php` con una cuenta marcada como administradora.
- **Resumen**: ventas, pedidos por estado, licencias activas y vencimientos próximos.
- **Pedidos**: filtrar, sincronizar con Mercado Pago, aprobar manualmente, cancelar, eliminar y
  otorgar licencias sin pago (transferencias, convenios, cortesías).
- **Licencias**: editar cupos, sedes, estado y vencimiento; renovar 12 meses; gestionar sus usuarios.
- **Precios**: editar precio, usuarios, sedes, vigencia y características de cada plan.
- **Usuarios**: crear, dar o quitar permisos, restablecer contraseñas, eliminar y "Entrar como".
- **Registro de pagos**: retornos, webhooks, cambios de precio y errores.

## Despliegue en producción (teachy.es)
Servidor: EC2 Debian 11 con CloudPanel · nginx + PHP-FPM 8.4 · MySQL.
El servidor aloja más sitios: trabajar solo dentro de `/home/teachy` y del vhost `www.teachy.es.conf`.

```
Repositorio:  /home/teachy/repo                 (git clone de GitHub)
Sitio:        /home/teachy/htdocs/www.teachy.es
Credenciales: <sitio>/config.secrets.php        (600, fuera del repositorio)
Despliegue:   sudo -u teachy bash /home/teachy/deploy.sh
Logs:         /home/teachy/logs/{nginx,php}
Respaldos:    /home/teachy/backups/pre-deploy
```

Para publicar cambios: `git push` y ejecutar `deploy.sh` en el servidor. El script hace
`git reset --hard origin/main`, copia los archivos sin `.git` ni credenciales, ajusta permisos
(750 directorios / 640 archivos) y ejecuta `install.php`.

### Ajustes de nginx aplicados al vhost
nginx no lee `.htaccess`, por eso el vhost incluye estos bloques (marcados con `EDUNOVA-`):
- Bloqueo de `/includes/`, `/sql/`, `config*.php`, `install.php`, `README.md` y extensiones
  `.sql|.md|.bak|.log|.sh|.ini`.
- Cabeceras `X-Content-Type-Options`, `X-Frame-Options` y `Referrer-Policy`.
- `teachy.es` redirige a `www.teachy.es`, salvo `/pago/webhook.php`, que se atiende sin redirección
  para que las notificaciones lleguen por cualquiera de los dos dominios.

Si CloudPanel regenera el vhost, hay que volver a aplicar esos bloques.

## Estructura
```
config.php                Configuración (marca, entorno, planes, roles)
config.secrets.php        Credenciales del servidor (no versionado)
index.php                 Landing page
checkout.php              Datos del comprador y creación del pedido
pago/                     pagar (pantalla de cobro), procesar, estado, retorno, webhook
portal/                   login, logout, panel del cliente, detalle de licencia
admin/                    resumen, pedidos, licencias, precios, usuarios, registro
includes/                 bootstrap, mercadopago, plantillas compartidas
assets/                   css, js, img
sql/schema.sql            Esquema de la base de datos
install.php               Crea tablas, migraciones y cuenta de administrador
```
