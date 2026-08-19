# Documentación detallada — Take Tech CR

Curso: Tecnologías y Sistemas Web II (ITI-523) · Proyecto final

---

## Índice

1. [Descripción del proyecto](#1-descripción-del-proyecto)
2. [Instrucciones de instalación](#2-instrucciones-de-instalación)
3. [Manual de uso](#3-manual-de-uso)
4. [Arquitectura de la aplicación](#4-arquitectura-de-la-aplicación)
5. [Modelo de datos](#5-modelo-de-datos)
6. [Proceso de compra paso a paso](#6-proceso-de-compra-paso-a-paso)
7. [Cookies: productos vistos recientemente](#7-cookies-productos-vistos-recientemente)
8. [Reportes de ventas](#8-reportes-de-ventas)
9. [Consideraciones de seguridad](#9-consideraciones-de-seguridad) · [Correos](#99-correos-del-sistema) · [Idioma](#910-idioma-de-la-aplicación)
10. [Comunicación síncrona y asíncrona](#10-comunicación-síncrona-y-asíncrona)
11. [Configuración](#11-configuración)
12. [Solución de problemas](#12-solución-de-problemas)

---

## 1. Descripción del proyecto

**Take Tech CR** es una tienda virtual (ecommerce del tipo **B2C**, tienda online propia,
según la clasificación de la sesión 11) que vende productos de tecnología en Costa Rica:
laptops, celulares y tablets, audio, gaming, monitores y accesorios.

El sistema atiende a dos tipos de usuario:

- **Cliente:** navega el catálogo, se registra, arma su carrito, paga y consulta sus pedidos.
- **Administrador:** mantiene el catálogo, cambia el estado de los pedidos y genera los
  reportes de ventas.

Todo el dinero se maneja en **colones costarricenses**, con **IVA del 13%** (tarifa general
de Costa Rica) y costo de envío de ₡2 900, gratuito a partir de ₡75 000 de subtotal.

---

## 2. Instrucciones de instalación

### 2.1 Requisitos previos

1. **XAMPP** (o cualquier instalación de PHP 8.2 o superior).
   Descargue XAMPP del sitio oficial de Apache Friends e instálelo incluyendo Apache.
2. **Composer**, el gestor de dependencias de PHP. Descárguelo de `getcomposer.org` y
   marque la opción de instalación global para que quede en el PATH.
3. Un editor de código como **Visual Studio Code**.

Compruebe que todo quedó bien:

```bash
php -v
```

```bash
composer -V
```

```bash
php -m | grep -i sqlite
```

El último comando debe mostrar `pdo_sqlite` y `sqlite3`. Si no aparecen, abra
`C:\xampp\php\php.ini` y quite el `;` al inicio de las líneas
`extension=pdo_sqlite` y `extension=sqlite3`.

### 2.2 Instalación del proyecto

Descomprima el proyecto y, dentro de su carpeta, ejecute:

```bash
composer install
```

```bash
cp .env.example .env
```

En Windows con CMD el comando equivalente es `copy .env.example .env`.

```bash
php artisan key:generate
```

Este comando genera la `APP_KEY`, una cadena aleatoria única de cada instalación que
Laravel usa para **cifrar las sesiones y las cookies**. Sin ella la aplicación no arranca.

```bash
php artisan migrate:fresh --seed
```

Crea `database/database.sqlite`, ejecuta las 12 migraciones y carga los datos de
demostración.

### 2.3 Ejecución

**Opción A — servidor de desarrollo de Laravel (la más simple):**

```bash
php artisan serve
```

Abra <http://localhost:8000>.

**Opción B — Apache con XAMPP:**

1. Copie el proyecto en `C:\xampp\htdocs\taketech`.
2. Configure un VirtualHost cuyo `DocumentRoot` apunte a
   `C:\xampp\htdocs\taketech\public` (nunca a la raíz del proyecto: `public/` es el
   único directorio que debe quedar expuesto).
3. Inicie Apache desde el panel de control de XAMPP.

El detalle del VirtualHost y del certificado SSL está en
[SSL-Y-HOSTING.md](SSL-Y-HOSTING.md).

### 2.4 Ver la base de datos

Instale en VS Code la extensión **SQLite** de *alexcvzz*, abra la paleta de comandos
(`Ctrl+Shift+P`), escriba «SQLite: Open Database» y seleccione
`database/database.sqlite`. El explorador aparece en la barra lateral izquierda.

---

## 3. Manual de uso

### 3.1 Como cliente

| Paso | Acción |
|---|---|
| 1 | En la portada, explore por categoría o use el buscador del encabezado. |
| 2 | En `/productos` use el panel izquierdo para filtrar por **nombre**, **categoría** y **rango de precio**, y para ordenar los resultados. |
| 3 | Entre al producto para ver su descripción completa, precio, imagen y existencias. Al abrirlo, el producto se guarda en la cookie de «vistos recientemente». |
| 4 | Elija la cantidad con los botones **−** / **+** y presione **Agregar al carrito**. El producto se agrega **sin recargar la página** y el número del ícono del carrito se actualiza solo. |
| 5 | En `/carrito` puede cambiar cantidades (el cambio se guarda solo), eliminar líneas o vaciar el carrito. El **subtotal, el IVA y el envío** se recalculan en cada cambio. |
| 6 | Presione **Continuar con el pago**. Si no ha iniciado sesión, el sistema se lo pide; **el carrito no se pierde**, se traslada a su cuenta al ingresar. |
| 7 | Complete los datos de entrega, elija el método de pago y presione **Pagar**. |
| 8 | La pantalla de confirmación muestra el número de pedido, el **número de seguimiento**, el monto pagado y el botón para **descargar la factura en PDF**. Además recibe un **correo de confirmación con la factura adjunta**. |
| 9 | En `/mis-pedidos` está el historial con el estado de cada compra; en `/perfil` puede modificar sus datos personales y su contraseña. |
| 10 | Si olvidó su contraseña, en la pantalla de inicio de sesión tiene el enlace **«¿Olvidó su contraseña?»**, que le envía por correo un enlace temporal para crear una nueva. |

### 3.2 Como administrador

Ingrese con `admin@taketech.cr` / `Admin1234*`. Será enviado directamente a `/admin`.

| Sección | Qué permite |
|---|---|
| **Panel** | Ventas del mes, ticket promedio, IVA por declarar, ventas históricas, gráfico mensual, productos más vendidos y últimos pedidos. |
| **Productos** | Crear, editar, desactivar y eliminar productos; subir imágenes; controlar existencias y destacados. Un producto con ventas registradas **se desactiva en lugar de borrarse**, para no dañar las facturas ya emitidas. |
| **Categorías** | Crear, editar, activar/desactivar y eliminar categorías, con su ícono y su URL amigable. Una categoría con productos **se desactiva en lugar de borrarse**: al tener la llave foránea en cascada, borrarla arrastraría todos sus productos. |
| **Pedidos** | Buscar por número de pedido o de seguimiento, filtrar por estado, ver el detalle completo y **cambiar el estado** (pendiente → pagado → enviado → entregado). |
| **Reportes › Por mes** | Ventas mes a mes del año elegido, con gráfico, totales y productos más vendidos. Botón **Descargar PDF**. |
| **Reportes › Por cliente** | Ventas agrupadas por cliente en un rango de fechas, ordenadas de mayor a menor. Botón **Descargar PDF** general y uno individual por cliente. |

Para subir imágenes de productos hay que crear una vez el enlace simbólico de
almacenamiento:

```bash
php artisan storage:link
```

---

## 4. Arquitectura de la aplicación

El proyecto sigue el patrón **MVC** de Laravel, con una capa adicional de **servicios**
donde vive la lógica de negocio.

```
Petición HTTP
     │
     ▼
routes/web.php  ──►  Middleware  ──►  FormRequest       ──►  Controlador
                     (auth, admin,     (valida TODA la        (coordina, no
                      HTTPS, CSRF)      entrada del            calcula)
                                        usuario)
                                                                  │
                                                                  ▼
                                                            Servicio
                                              (CarritoService, PedidoService,
                                               ReporteService, pasarelas de pago)
                                                                  │
                                                                  ▼
                                                        Modelo Eloquent  ──►  SQLite
                                                                  │
                                                                  ▼
                                                          Vista Blade  ──►  HTML / PDF / JSON
```

### 4.1 ¿Por qué una capa de servicios?

Si el cálculo del total viviera dentro del controlador, no se podría probar sin hacer una
petición HTTP y habría que repetirlo en el carrito, en el checkout y en la factura.
Al ponerlo en `TotalesCarrito` existe **una sola fuente de verdad**, probada con pruebas
unitarias que corren en milisegundos.

### 4.2 Las pasarelas de pago y la POO

Este es el punto donde más se aplica la programación orientada a objetos vista en la
sesión 3:

- `App\Services\Pagos\PasarelaPago` es una **interfaz**: define el contrato
  (`identificador()`, `nombre()`, `procesar()`).
- `PasarelaTarjeta`, `PasarelaPayPal` y `PasarelaSinpe` son tres **clases** que
  implementan ese contrato, cada una con su propia lógica **encapsulada**.
- `GestorPasarelas` aplica el patrón **Factory**: recibe el nombre del método y devuelve
  el objeto correcto.
- `CheckoutController` **no sabe** con cuál está trabajando: solo llama a `procesar()`.
  Esto es **polimorfismo**.

Agregar una pasarela nueva (por ejemplo Tilopay) no obliga a modificar ni el controlador
ni las vistas: se crea la clase, se registra en `GestorPasarelas` y se habilita en
`config/tienda.php`.

---

## 5. Modelo de datos

### 5.1 Tablas propias del proyecto

| Tabla | Contenido |
|---|---|
| `users` | Usuarios. Se le agregaron `telefono`, `cedula`, `direccion`, `ciudad`, `provincia` y `es_admin`. |
| `categories` | Categorías del catálogo (`nombre`, `slug`, `icono`, `activa`). |
| `products` | Productos (`nombre`, `sku`, `precio`, `precio_anterior`, `existencias`, `imagen`, `destacado`, `activo`). |
| `carts` | Un carrito por usuario **o** por visitante anónimo (`token_sesion`). |
| `cart_items` | Líneas del carrito, con el precio del momento en que se agregó. |
| `orders` | **Tabla de compras.** Incluye `user_id`, `fecha_compra`, `total`, el desglose de impuesto y envío, el método de pago y el `numero_seguimiento`. |
| `order_items` | Detalle del pedido con copia del nombre, SKU y precio del producto. |
| `invoices` | **Tabla de facturas.** `numero_factura`, `user_id`, `fecha_emision` y montos. |
| `payments` | Transacciones devueltas por la pasarela. **Nunca guarda el número completo de la tarjeta ni el CVV.** |

### 5.2 Relaciones de Eloquent

```
User  1 ──── n  Order          (User::pedidos / Order::usuario)
User  1 ──── n  Invoice        (User::facturas)
User  1 ──── 1  Cart           (User::carrito)

Category 1 ── n  Product       (Category::productos / Product::categoria)

Cart  1 ──── n  CartItem       (Cart::lineas / CartItem::carrito)
CartItem n ── 1  Product       (CartItem::producto)

Order 1 ──── n  OrderItem      (Order::lineas / OrderItem::pedido)
Order 1 ──── 1  Invoice        (Order::factura / Invoice::pedido)
Order 1 ──── n  Payment        (Order::pagos / Order::pago = el último)
```

### 5.3 ¿Por qué se copian los datos del producto en `order_items`?

Porque una factura es un documento histórico. Si el administrador cambia el precio de una
laptop o la retira del catálogo, la factura emitida el mes pasado **no debe cambiar**.
Por eso `order_items` guarda `nombre_producto`, `sku`, `precio_unitario` y `subtotal` en
el momento de la compra, y su llave foránea usa `nullOnDelete()`.

### 5.4 ¿Por qué `orders` e `invoices` están separadas?

`orders` es el pedido comercial: puede estar pendiente o cancelarse. `invoices` es el
comprobante fiscal, que solo existe cuando el pago fue aprobado. Un pedido rechazado
nunca genera factura.

---

## 6. Proceso de compra paso a paso

El diagrama de caso de uso está en [DIAGRAMA-CASO-USO.md](DIAGRAMA-CASO-USO.md).
Internamente, `App\Services\PedidoService::procesarCompra()` hace todo esto **dentro de
una transacción de base de datos**:

| Paso | Acción | Por qué importa |
|---|---|---|
| 1 | Se resuelve la pasarela del método elegido. | Un método inválido falla antes de tocar la base de datos. |
| 2 | Se releen los productos con `lockForUpdate()` y se valida que estén activos y con existencias. | Evita vender inventario que ya no existe. |
| 3 | Se recalcula el total **con los precios de la base de datos**. | El navegador nunca decide cuánto se cobra. |
| 4 | Se crea el pedido en estado `pendiente` con su número de seguimiento. | |
| 5 | Se crean las líneas de detalle y se descuenta el inventario. | |
| 6 | Se cobra con la pasarela y se registra la transacción. | |
| 7 | Si el pago **se rechaza**, se lanza `PagoRechazadoException`. | La transacción se revierte: no queda pedido, ni detalle, ni inventario descontado. El carrito queda intacto para reintentar. |
| 8 | Si el pago **se aprueba**, el pedido pasa a `pagado`, se emite la factura y se vacía el carrito. | |

Este comportamiento está verificado por las pruebas
`test_si_la_tarjeta_es_rechazada_no_queda_ningun_pedido` y
`test_completa_la_compra_con_tarjeta`.

---

## 7. Cookies: productos vistos recientemente

Implementado en `App\Services\VistosRecientementeService`.

**¿Por qué una cookie y no la sesión?** El requisito es mostrarle al usuario los últimos
productos que visitó *mientras navega por la tienda*, incluso si no ha iniciado sesión y
aunque cierre y vuelva a abrir el navegador. La sesión se pierde al cerrarlo; la cookie
dura 30 días.

**Cómo funciona:**

1. Al abrir `/producto/{slug}`, el `CatalogoController` llama a `registrar($producto)`.
2. El id del producto se coloca **al inicio** de la lista, se eliminan duplicados y se
   recorta al máximo configurado (6 por omisión).
3. La lista se guarda como JSON con `Cookie::queue(...)`. Laravel la **cifra y firma**
   automáticamente con el middleware `EncryptCookies`, por lo que el usuario no puede
   manipular su contenido.
4. En la portada, el catálogo y el carrito se leen esos ids, se consultan los productos
   **activos** y se muestran en el orden en que se visitaron.

**Qué guarda la cookie:** únicamente identificadores numéricos. No contiene datos
personales. Al leerla se descarta cualquier valor que no sea un entero positivo, de modo
que una cookie manipulada no rompe la página ni llega a la consulta SQL.

---

## 8. Reportes de ventas

Los reportes viven en `App\Services\ReporteService` y solo consideran pedidos con
`estado_pago = aprobado`: un pedido rechazado o cancelado **no es una venta**.

### 8.1 Por mes

- Agrupa por mes del año seleccionado y devuelve **los 12 meses**, incluso los que
  tuvieron cero ventas, para que la tabla y el gráfico queden completos.
- Muestra pedidos, unidades, subtotal, IVA, envíos y total.
- En pantalla incluye un gráfico combinado (barras de monto + línea de pedidos) hecho con
  Chart.js. En el PDF, como DomPDF no ejecuta JavaScript, la distribución se dibuja con
  **barras de porcentaje en HTML/CSS**.

### 8.2 Por cliente

- Agrupa por cliente en un rango de fechas, ordenado de mayor a menor monto.
- Muestra cantidad de pedidos, promedio, total y fecha de la última compra.
- Además del PDF general, cada fila tiene un botón para generar el **reporte individual**
  del cliente, que incluye el detalle de los productos que compró.

### 8.3 Nota técnica

Las agrupaciones por mes usan la función `strftime()` de **SQLite**, que es el motor
definido para este proyecto. Si se migrara a MySQL habría que cambiarla por `MONTH()`
en `ventasPorMes()` y `aniosConVentas()`.

---

## 9. Consideraciones de seguridad

### 9.1 Validación de entradas

**Todas** las entradas del usuario se validan antes de llegar a la lógica de negocio,
mediante clases `FormRequest` en `app/Http/Requests/`:

| Clase | Valida |
|---|---|
| `RegistroRequest` | Nombre (solo letras), correo único, contraseña de 8+ con letras y números, aceptación de términos. |
| `InicioSesionRequest` | Credenciales **y** límite de 5 intentos fallidos. |
| `PerfilRequest` | Datos personales; el correo debe ser único ignorando el propio registro. |
| `ContrasenaRequest` | Exige la contraseña actual (`current_password`). |
| `CarritoRequest` | Cantidad entera entre 0/1 y 20. |
| `CheckoutRequest` | Datos de envío, provincia dentro de una lista, y los campos del método de pago elegido (`required_if`). |
| `ProductoRequest` | Datos del producto y la imagen (tipo y tamaño). |

Los filtros del catálogo que llegan por la URL (`q`, `categoria`, `min`, `max`, `orden`)
también se validan en el controlador.

### 9.2 Inyección SQL

- Todas las consultas usan **Eloquent** o el *query builder*, que trabajan con
  **consultas preparadas (PDO)**: el valor viaja como parámetro enlazado y nunca se
  concatena dentro del SQL.
- El ordenamiento usa una **lista blanca** (`Product::scopeOrdenar`): un valor arbitrario
  en la URL no puede llegar a la cláusula `ORDER BY`.
- Los rangos de precio se validan como `numeric` antes de usarse.

Verificado por `SeguridadTest::test_el_buscador_no_permite_inyeccion_sql`, que dispara
cuatro cargas clásicas (`' OR '1'='1`, `'; DROP TABLE products; --`, un `UNION SELECT` y
`admin'--`) y comprueba que las tablas siguen intactas.

### 9.3 XSS

- Blade escapa por omisión con `{{ $variable }}`; **no se usa `{!! !!}`** en ninguna
  vista con datos del usuario.
- La directiva propia `@precio()` también pasa por `e()`.
- Los datos que van a JavaScript se serializan con la directiva de JSON de Blade, que
  escapa el contenido.
- Además, el nombre del usuario se valida con una expresión regular que solo acepta
  letras y espacios (defensa en profundidad).

### 9.4 Contraseñas y datos sensibles

- Las contraseñas se cifran con **bcrypt** (cast `hashed` del modelo `User`) y nunca se
  guardan en texto plano. `$hidden` evita que salgan al serializar el modelo.
- De la tarjeta solo se almacenan **la marca y los últimos 4 dígitos**. El número
  completo y el CVV no llegan a la base de datos, tal como exige PCI-DSS.
- `bootstrap/app.php` declara `dontFlash` para `password`, `numero_tarjeta` y `cvv`: si
  un formulario falla, esos datos **no se guardan en la sesión** para repoblarlo.

### 9.5 Sesiones seguras

- Se llama a `session()->regenerate()` después de registrarse y de iniciar sesión
  (protección contra **fijación de sesión**).
- Al cerrar sesión se invalida la sesión y se regenera el token CSRF.
- El cierre de sesión es por **POST con token CSRF**, para que un enlace externo no pueda
  cerrar la sesión del usuario.
- Las cookies se cifran con la `APP_KEY`.

### 9.6 CSRF

Todos los formularios llevan `@csrf` y las peticiones AJAX envían el encabezado
`X-CSRF-TOKEN` que se lee de la etiqueta `<meta name="csrf-token">`.

### 9.7 Control de acceso

- Middleware `auth` para el área del cliente.
- Middleware propio `admin` (`VerificarAdministrador`) para todo `/admin`.
- Un usuario **solo puede ver sus propios pedidos y facturas**: se comprueba el
  `user_id` antes de mostrar cualquier pedido.
- El `CarritoService` solo busca líneas dentro del carrito de quien las pide, de modo que
  cambiar el id en la URL no permite tocar el carrito de otra persona.

### 9.8 HTTPS

El middleware `ForzarHttps` redirige HTTP a HTTPS en producción, agrega la cabecera
**HSTS** y envía `X-Content-Type-Options`, `X-Frame-Options` y `Referrer-Policy` en todas
las respuestas. Ver [SSL-Y-HOSTING.md](SSL-Y-HOSTING.md).

---

## 9.9 Correos del sistema

La tienda envía dos correos, ambos con el texto en español:

| Correo | Cuándo | Contenido |
|---|---|---|
| `App\Mail\ConfirmacionPedido` | Al completar una compra | Resumen del pedido, número de seguimiento, dirección de entrega y **la factura en PDF adjunta**. |
| `App\Notifications\RestablecerContrasena` | Al pedir recuperar la contraseña | Enlace temporal (60 minutos) para definir una contraseña nueva. |

Detalles importantes:

- **El correo de confirmación se envía fuera de la transacción y dentro de un
  `try/catch`.** El cobro ya se hizo, así que si el servidor de correo falla la compra
  igual se completa y el error queda registrado en el log. Está probado en
  `test_un_fallo_del_servidor_de_correo_no_rompe_la_compra`.
- **El correo nunca incluye datos de la tarjeta.**
- Al restablecer la contraseña se cambia también el `remember_token`, con lo que se
  cierra la sesión en cualquier otro dispositivo donde la cuenta siguiera abierta.
- El formulario de recuperación **responde lo mismo exista o no la cuenta**, para que
  nadie pueda usarlo como forma de averiguar qué correos están registrados.

### En desarrollo

`.env` trae `MAIL_MAILER=log`: los correos no salen a internet, se escriben completos en
`storage/logs/laravel.log`. Para probar la recuperación de contraseña, busque ahí el
enlace y ábralo en el navegador.

Para enviarlos de verdad, configure un SMTP en `.env`, por ejemplo:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=su-correo@gmail.com
MAIL_PASSWORD=su-contrasena-de-aplicacion
MAIL_FROM_ADDRESS=ventas@taketech.cr
```

## 9.10 Idioma de la aplicación

`APP_LOCALE=es` y la carpeta `lang/es/` contiene la traducción de los mensajes del
framework: validación (`validation.php`), autenticación (`auth.php`), recuperación de
contraseña (`passwords.php`), paginación (`pagination.php`) y las frases de las
plantillas de correo (`lang/es.json`).

Sin esos archivos Laravel mostraría los errores en inglés («The email field is
required.») dentro de una aplicación que está toda en español.

---

## 10. Comunicación síncrona y asíncrona

Tema de la sesión 11, aplicado en el proyecto:

**Síncrona (comportamiento normal de PHP).** El navegador envía el formulario, espera a
que el servidor procese y devuelve una página nueva. Se usa donde el usuario debe ver un
resultado definitivo: registro, inicio de sesión, y sobre todo el **checkout** (el
usuario debe esperar la respuesta de la pasarela antes de continuar).

**Asíncrona (Fetch API).** En `public/js/tienda.js`, el botón «Agregar al carrito»
intercepta el envío del formulario y manda la petición con `fetch()`. El servidor
responde en **JSON** con los totales actualizados y la página:

- no se recarga,
- muestra un aviso flotante (toast),
- actualiza el contador del carrito consultando `/carrito/contador`.

Si la petición asíncrona falla (por ejemplo, sin red), el JavaScript **vuelve al envío
tradicional del formulario** para que el usuario siempre pueda comprar.

El mismo controlador atiende ambos casos: `CarritoController::responder()` devuelve JSON
si la petición lo espera (`$peticion->expectsJson()`) y una redirección si no.

---

## 11. Configuración

Todos los parámetros de negocio están en `config/tienda.php` y se pueden cambiar desde
`.env` sin tocar el código:

| Variable | Por omisión | Qué controla |
|---|---|---|
| `TIENDA_NOMBRE` | Take Tech CR | Nombre de la tienda |
| `TIENDA_IMPUESTO_TASA` | `0.13` | Tarifa del IVA |
| `TIENDA_ENVIO_COSTO` | `2900` | Costo de envío |
| `TIENDA_ENVIO_GRATIS_DESDE` | `75000` | Subtotal a partir del cual el envío es gratis |
| `TIENDA_MONEDA_SIMBOLO` | `₡` | Símbolo de la moneda |
| `TIENDA_VISTOS_MAXIMO` | `6` | Cuántos productos vistos se recuerdan |
| `TIENDA_VISTOS_DIAS` | `30` | Duración de la cookie |
| `TIENDA_PRODUCTOS_POR_PAGINA` | `9` | Paginación del catálogo |
| `TIENDA_PAGOS_MODO` | `sandbox` | `sandbox` = pago simulado, `live` = producción |
| `PAYPAL_CLIENT_ID` / `PAYPAL_SECRET` | vacío | Credenciales de PayPal Developer |
| `PAYPAL_TIPO_CAMBIO` | `510` | Colones por dólar para el cobro en PayPal |
| `SINPE_NUMERO` | `8888-8888` | Número al que el cliente transfiere |
| `TIENDA_FORZAR_HTTPS` | `false` | Forzar HTTPS fuera de producción |

### Pasar la pasarela a producción

1. Cree una cuenta **PayPal Business** y, en <https://developer.paypal.com>, entre a
   **My Apps & Credentials** y cree una aplicación para obtener el **Client ID** y el
   **Secret** (hay un par de credenciales para *sandbox* y otro para producción).
2. Coloque esas credenciales en `.env` (nunca dentro del código).
3. Cambie `TIENDA_PAGOS_MODO=live`.
4. Reemplace el cuerpo del método `procesar()` de la pasarela correspondiente por la
   llamada HTTP real a la API. La interfaz `PasarelaPago` no cambia, así que **no hay que
   modificar controladores ni vistas**.

Para el mercado costarricense, las opciones más usadas por desarrolladores son
**Tilopay** (SINPE Móvil, tarjetas, Tasa Cero), **BAC Credomatic**, **Onvo Pay** y
**Pagadito**; PayPal conviene sobre todo para cobrar al exterior.

---

## 12. Solución de problemas

| Síntoma | Causa y solución |
|---|---|
| `could not find driver` | Falta la extensión SQLite. Descomente `extension=pdo_sqlite` y `extension=sqlite3` en `php.ini` y reinicie Apache. |
| `No application encryption key has been specified` | Ejecute `php artisan key:generate`. |
| `database file does not exist` | Ejecute `php artisan migrate:fresh --seed`. |
| Los estilos no cargan | Verifique que el `DocumentRoot` apunte a `public/` y no a la raíz del proyecto. |
| Las imágenes subidas no se ven | Ejecute `php artisan storage:link`. |
| Cambié `.env` y no surte efecto | Ejecute `php artisan config:clear`. |
| Una vista muestra contenido viejo | Ejecute `php artisan view:clear`. |
| Los reportes salen vacíos | Solo cuentan los pedidos con pago aprobado. Recargue los datos con `php artisan migrate:fresh --seed`. |
| No me llega el correo de recuperación | En desarrollo `MAIL_MAILER=log`: el correo se escribe en `storage/logs/laravel.log`. Busque ahí el enlace. |
| El enlace del correo apunta a otro dominio | Ajuste `APP_URL` en `.env` y ejecute `php artisan config:clear`. |
| Los mensajes de error salen en inglés | Verifique que exista la carpeta `lang/es/` y que `.env` tenga `APP_LOCALE=es`. |
