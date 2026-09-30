# EduNova — landing, venta de licencias y portal de clientes

PHP 8.1+ sin dependencias · MySQL · HTML/CSS/JS · cobros con **enlaces de pago** (uno por plan).

## Entornos
`config.php` detecta el entorno por el dominio:
- **Producción** (`teachy.es` / `www.teachy.es`): `BASE_URL = https://www.teachy.es`, cobros reales.
- **Local** (`localhost:8080/teachy`): mismo comportamiento; los enlaces de pago son los reales.

Las credenciales viven en `config.secrets.php` (fuera del repositorio). Copiar
`config.secrets.example.php` y completar: base de datos y la cuenta de administrador
(`ADMIN_EMAIL` + `ADMIN_PASSWORD_HASH`, generado con `password_hash`).

## Instalación local
1. Copiar `config.secrets.example.php` → `config.secrets.php` y completarlo.
2. `php install.php` (crea la base y las tablas, aplica migraciones y crea el administrador).
3. Abrir `http://localhost:8080/teachy/`.

## Cobros con enlaces de pago
El sitio no se conecta con ninguna API de pagos. Cada plan tiene su propio **enlace de pago** con el
valor fijo del cobro, definido en `$PLANS[<plan>]['payment_link']` (`config.php`) y editable desde
**Administración > Precios** sin tocar código.

Importante: el enlace lleva el importe fijo, así que al cambiar el precio de un plan hay que generar
un enlace nuevo por ese valor y pegarlo en el mismo panel.

## Flujo de compra
1. `index.php#precios` (o el portal) → `checkout.php?plan=escuela|volumen`: crea la cuenta si hace
   falta, registra el pedido como **pendiente** y ajusta el valor al precio vigente.
2. `pago/pagar.php?ref=…`: resumen del pedido y botón que abre el enlace de pago del plan.
3. El cliente paga y pulsa **"Ya realicé el pago"** (`pago/aviso.php`): el pedido pasa a *en proceso*
   y queda a la vista del administrador.
4. **Administración > Pedidos**: al confirmar el pago en la cuenta de Mercado Pago, se pulsa
   **Aprobar**; eso crea la licencia y la activa. "Otorgar licencia" hace lo mismo sin pedido previo.
5. `portal/` → licencias, código, vigencia y cupos; alta individual o masiva de usuarios, cambio de
   rol y sede, retiro, exportación CSV e historial de pedidos.

## Administración (`/admin/`)
Se entra por `portal/login.php` con una cuenta marcada como administradora.
- **Resumen**: ventas, pedidos por estado, licencias activas y vencimientos próximos.
- **Pedidos**: filtrar, aprobar (confirmando el pago), cancelar, eliminar y otorgar licencias sin
  pedido previo (transferencias, convenios, cortesías).
- **Licencias**: editar cupos, sedes, estado y vencimiento; renovar 12 meses; gestionar sus usuarios.
- **Precios**: editar precio, **enlace de pago**, usuarios, sedes, vigencia y características.
- **Usuarios**: crear, dar o quitar permisos, restablecer contraseñas, eliminar y "Entrar como".
- **Registro de pagos**: avisos de pago de los clientes, cambios de precio y errores.

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
- `teachy.es` redirige a `www.teachy.es`.

Si CloudPanel regenera el vhost, hay que volver a aplicar esos bloques.

## Estructura
```
config.php                Configuración (marca, entorno, planes, roles)
config.secrets.php        Credenciales del servidor (no versionado)
index.php                 Landing page
checkout.php              Datos del comprador y creación del pedido
pago/                     pagar (enlace de cobro), aviso (el cliente informa su pago)
portal/                   login, logout, panel del cliente, detalle de licencia
admin/                    resumen, pedidos, licencias, precios, usuarios, registro
includes/                 bootstrap, pedidos (licencias), plantillas compartidas
assets/                   css, js, img
sql/schema.sql            Esquema de la base de datos
install.php               Crea tablas, migraciones y cuenta de administrador
```
