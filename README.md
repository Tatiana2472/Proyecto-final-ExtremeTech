# Take Tech CR — Tienda virtual

**Curso:** Tecnologías y Sistemas Web II (ITI-523)
**Proyecto final** · Valor 25% · Docente: Ing. Milena Vargas Blanco

Tienda virtual completa de productos de tecnología desarrollada con **PHP + Laravel 12**,
**SQLite** como base de datos y **Bootstrap 5** en el frontend.

---

## 1. Descripción del proyecto

Take Tech CR es el comercio electrónico de una tienda de tecnología costarricense.
Permite a un visitante navegar el catálogo, registrarse, armar su carrito, pagar en línea
y dar seguimiento a sus pedidos; y le da a la administración un panel con mantenimiento
del catálogo, gestión de pedidos y reportes de ventas en PDF.

Los precios están en **colones costarricenses (₡)** e incluyen el cálculo automático del
**IVA del 13%** y del **costo de envío** (gratis en compras superiores a ₡75 000).

### Funcionalidades

| # | Funcionalidad | Dónde verla |
|---|---|---|
| 1 | Registro de usuarios nuevos | `/registro` |
| 2 | Inicio y cierre de sesión seguros | `/ingresar` |
| 2b | Recuperación de contraseña por correo | `/olvide-mi-contrasena` |
| 3 | Perfil con edición de datos personales | `/perfil` |
| 4 | Historial de pedidos del usuario | `/mis-pedidos` |
| 5 | Catálogo por categorías | `/categoria/{slug}` |
| 6 | Detalle del producto (descripción, precio, imagen, existencias) | `/producto/{slug}` |
| 7 | Búsqueda y filtrado por nombre, categoría y precio | `/productos` |
| 8 | Carrito: agregar, actualizar y eliminar | `/carrito` |
| 9 | Cálculo automático del total (IVA + envío) | `/carrito` y `/checkout` |
| 10 | Pasarela de pago: tarjeta, PayPal y SINPE Móvil | `/checkout` |
| 11 | Factura con usuario, fecha y monto | tabla `invoices` |
| 12 | Confirmación con número de seguimiento | `/confirmacion/{pedido}` |
| 12b | Correo de confirmación con la factura adjunta | se envía al completar la compra |
| 13 | **Cookies** de productos vistos recientemente | franja en portada, catálogo y carrito |
| 14 | Reportes de ventas **por mes** en PDF | `/admin/reportes/por-mes` |
| 15 | Reportes de ventas **por cliente** en PDF | `/admin/reportes/por-cliente` |
| 16 | Factura descargable en PDF | `/mis-pedidos/{id}/factura` |
| 17 | Panel de administración e inventario | `/admin` |
| 18 | Mantenimiento de categorías | `/admin/categorias` |

---

## 2. Requisitos

| Componente | Versión mínima | Notas |
|---|---|---|
| PHP | 8.2 | Con las extensiones `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `dom`, `fileinfo` |
| Composer | 2.x | Gestor de dependencias de PHP |
| Servidor web | Apache (XAMPP) o el servidor de desarrollo de Laravel | |
| SQLite | incluido en PHP | La base de datos es un solo archivo |

> No se necesita Node.js ni npm: Bootstrap, Bootstrap Icons y Chart.js están incluidos
> en `public/vendor/`, por lo que el proyecto funciona sin conexión a internet.

Verifique su entorno con:

```bash
php -v && composer -V && php -m | grep -i sqlite
```

---

## 3. Instalación

```bash
composer install
```

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

```bash
php artisan migrate:fresh --seed
```

El último comando crea el archivo `database/database.sqlite` con las tablas y carga
los datos de demostración: 6 categorías, 28 productos, 5 usuarios y un año de
historial de ventas para que los reportes tengan información desde el inicio.

### Ejecutar la aplicación

```bash
php artisan serve
```

Abra <http://localhost:8000>.

Si prefiere Apache con XAMPP, apunte el `DocumentRoot` a la carpeta `public/` del
proyecto. Los detalles están en [docs/SSL-Y-HOSTING.md](docs/SSL-Y-HOSTING.md).

---

## 4. Cuentas de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@taketech.cr` | `Admin1234*` |
| Cliente | `maria@example.com` | `Cliente1234*` |
| Cliente | `carlos@example.com` | `Cliente1234*` |
| Cliente | `ana@example.com` | `Cliente1234*` |
| Cliente | `luis@example.com` | `Cliente1234*` |

## 5. Tarjetas de prueba (modo sandbox)

La pasarela trabaja en modo simulado: **no se envían datos a ningún banco real**.

| Dato | Valor | Resultado |
|---|---|---|
| Tarjeta aprobada | `4111 1111 1111 1111` | Pago aprobado |
| Tarjeta declinada | `4000 0000 0000 0002` | «Tarjeta declinada por el banco emisor» |
| Fondos insuficientes | `4000 0000 0000 9995` | Pago rechazado |
| Vencimiento / CVV | cualquier fecha futura y 3 dígitos | |
| PayPal aprobado | cualquier correo válido | Pago aprobado |
| PayPal rechazado | `rechazado@algo.com` | Pago rechazado |
| SINPE Móvil | comprobante de 6 dígitos o más | Pago aprobado |

---

## 6. Pruebas

```bash
php artisan test
```

**210 pruebas / 639 aserciones, todas en verde.** El detalle de cada prueba está en
[docs/PRUEBAS-UNITARIAS.md](docs/PRUEBAS-UNITARIAS.md).

Las pruebas corren contra una base SQLite en memoria, por lo que **no modifican** la
base de datos de desarrollo.

### Sobre los correos en desarrollo

El proyecto viene con `MAIL_MAILER=log`: los correos (confirmación de compra y
recuperación de contraseña) **no se envían de verdad**, se escriben completos en
`storage/logs/laravel.log`. Ahí puede leerlos y copiar el enlace de recuperación.
Para enviarlos de verdad, configure un SMTP en `.env`.

---

## 7. Documentación

| Documento | Contenido |
|---|---|
| [docs/DOCUMENTACION.md](docs/DOCUMENTACION.md) | Manual de uso, arquitectura, modelo de datos y seguridad |
| [docs/DIAGRAMA-CASO-USO.md](docs/DIAGRAMA-CASO-USO.md) | Diagrama de caso de uso del proceso de compra |
| [docs/PRUEBAS-UNITARIAS.md](docs/PRUEBAS-UNITARIAS.md) | Documento de pruebas unitarias y de integración |
| [docs/SSL-Y-HOSTING.md](docs/SSL-Y-HOSTING.md) | Certificado SSL gratuito y despliegue en hosting |

---

## 8. Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/          Controladores (tienda y panel /admin)
│   ├── Middleware/           VerificarAdministrador, ForzarHttps
│   └── Requests/             Validación de todas las entradas del usuario
├── Mail/                     ConfirmacionPedido (correo con factura adjunta)
├── Notifications/            RestablecerContrasena (correo de recuperación)
├── Models/                   Category, Product, Cart, CartItem, Order,
│                             OrderItem, Invoice, Payment, User
├── Services/
│   ├── Pagos/                Pasarelas de pago (interfaz + 3 implementaciones)
│   ├── CarritoService.php    Lógica del carrito
│   ├── PedidoService.php     Proceso de compra transaccional
│   ├── ReporteService.php    Consultas de los reportes de ventas
│   ├── TotalesCarrito.php    Cálculo de subtotal, IVA, envío y total
│   └── VistosRecientementeService.php   Cookies de productos vistos
├── Support/Moneda.php        Formato de montos en colones
config/tienda.php             IVA, envío, moneda, pasarelas, cookies
lang/es/                      Mensajes de validación y de correo en español
database/
├── migrations/               9 migraciones propias + las de Laravel
├── factories/ y seeders/     Datos de demostración
public/
├── css/tienda.css            Estilos propios
├── js/tienda.js              Carrito asíncrono (fetch) e interacciones
├── img/productos/            Imágenes del catálogo (SVG)
└── vendor/                   Bootstrap 5, Bootstrap Icons, Chart.js
resources/views/              Vistas Blade (tienda, admin, correos y PDF)
tests/
├── Unit/                     4 archivos: totales, pasarelas, carrito
└── Feature/                  12 archivos: auth, recuperación de contraseña,
                              catálogo, cookies, carrito, checkout, correos,
                              perfil, pedidos, categorías, reportes, seguridad
```

---

## 9. Tecnologías utilizadas

- **Backend:** PHP 8.2 · Laravel 12 (MVC, Eloquent ORM, migraciones, Blade)
- **Base de datos:** SQLite
- **Frontend:** HTML5 · CSS3 · Bootstrap 5.3 · JavaScript (Fetch API)
- **Gráficos:** Chart.js
- **PDF:** barryvdh/laravel-dompdf
- **Pruebas:** PHPUnit 11
- **Servidor web:** Apache (XAMPP) o `php artisan serve`
- **Control de versiones:** Git / GitHub

---

## 10. Autores

| Nombre | Carné |
|---|---|
| _(completar)_ | |
| _(completar)_ | |
| _(completar)_ | |
| _(completar)_ | |
