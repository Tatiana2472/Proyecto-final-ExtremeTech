# Funcionalidad del sistema y reparto del trabajo — ExtremTech

Curso: Tecnologías y Sistemas Web II (ITI-523) · Proyecto final · Valor 25 %
Docente: Ing. Milena Vargas Blanco
Integrantes: Anguar · Tatiana · Camila · Daniel

---

## 1. Qué hace el sistema

ExtremTech es una tienda virtual de productos de tecnología. Resuelve las dos caras de
una tienda en línea: la del visitante que compra y la de la administración que gestiona
el negocio.

Un visitante navega el catálogo, busca y filtra productos, arma su carrito sin necesidad
de registrarse, crea su cuenta al momento de pagar y da seguimiento a sus pedidos con su
factura en PDF. La administración entra a un panel aparte donde mantiene el catálogo,
cambia el estado de los pedidos y consulta reportes de ventas.

Está construido con **PHP 8.2 y Laravel 12** siguiendo el patrón MVC, con **SQLite** como
base de datos y **Bootstrap 5** en el frontend. La lógica de negocio vive en una capa de
servicios separada de los controladores, lo que permite probarla sin levantar el servidor.

---

## 2. Módulos

| Módulo | Qué resuelve |
|---|---|
| Catálogo | Portada, listado paginado, búsqueda por nombre o marca, filtros por categoría y rango de precio, ficha de producto y productos vistos recientemente. |
| Carrito | Agregar, modificar y eliminar sin recargar la página mediante Fetch API. El carrito del visitante anónimo se conserva y se traslada a su cuenta al registrarse. |
| Cuentas | Registro, inicio de sesión con límite de intentos, recuperación de contraseña por correo y edición del perfil. |
| Compra y pagos | Formulario de envío y tres pasarelas simuladas: tarjeta con validación Luhn, PayPal con conversión de moneda y SINPE Móvil. |
| Pedidos y facturación | Número de pedido y de seguimiento, historial, factura electrónica en PDF y correo de confirmación con la factura adjunta. |
| Administración | Panel con indicadores, mantenimiento de productos y categorías, y gestión del estado de los pedidos. |
| Reportes de ventas | Ventas por mes y por cliente, con gráficos en pantalla mediante Chart.js y exportación a PDF. |
| Importación de catálogo | Consumo de una API externa para poblar el catálogo con productos e imágenes reales, mediante comandos de consola. |
| Seguridad | Protección CSRF, escape de HTML, consultas preparadas, contraseñas cifradas, control de acceso al panel y HTTPS forzado en producción. |

---

## 3. Flujo de compra

El recorrido central del sistema. Cada compra ocurre dentro de una transacción de base de
datos: si el pago se rechaza, no queda ningún registro a medias ni existencias descontadas.

1. **Catálogo** — el cliente busca y elige un producto.
2. **Carrito** — se agrega sin recargar la página y se calculan los totales.
3. **Sesión** — inicia sesión o crea su cuenta; el carrito se conserva.
4. **Pago** — elige método y la pasarela autoriza o rechaza el cobro.
5. **Factura** — se emite la factura y se envía por correo en PDF.

---

## 4. Reparto del trabajo

El proyecto se dividió en cuatro áreas de responsabilidad. Cada integrante desarrolló su
módulo completo — modelo, lógica, vistas y pruebas — y participó en la integración final.

### Anguar — Datos, API externa y catálogo

- Integración con la **API externa** para importar productos reales, incluida la
  traducción de los datos ajenos al modelo de la tienda.
- Comandos de consola para importar el catálogo y asignar imágenes de forma automática
  y repetible.
- **Sistema de imágenes** del catálogo: fotografías propias, fotografías del CDN externo
  e ilustraciones vectoriales de respaldo.
- Diseño del **modelo de datos**: migraciones, relaciones de Eloquent y datos de
  demostración.
- Módulo de **catálogo**: búsqueda, filtros, ordenamientos, ficha de producto y productos
  vistos recientemente mediante cookies.
- Coordinación del repositorio e integración de los módulos del equipo.

> En el proyecto: `app/Services/Catalogo/` · `app/Console/Commands/` · `app/Models/` ·
> `database/` · `CatalogoController` · `docs/IMPORTACION-CATALOGO.md`

### Tatiana — Diseño, frontend y despliegue

- **Identidad visual** de la tienda: logotipo, paleta, tipografía y criterios de
  composición aplicados a todas las pantallas.
- Maquetación completa con **Bootstrap 5**, incluida la adaptación a celular y tableta.
- Plantillas Blade de la tienda y del panel: layouts, componentes reutilizables y
  pantallas de error.
- **JavaScript del cliente**: carrito asíncrono con Fetch API, avisos flotantes, contador
  del encabezado y validaciones del formulario de pago.
- Diseño de los **documentos PDF**: factura electrónica y reportes de ventas.
- **Publicación en línea**: despliegue del sitio, certificado SSL y redirección forzada
  a HTTPS.

> En el proyecto: `resources/views/` · `public/css/tienda.css` · `public/js/tienda.js` ·
> `resources/views/pdf/` · `ForzarHttps` · `docs/SSL-Y-HOSTING.md`

### Daniel — Compra, pagos y facturación

- Servicio del **carrito de compras**: control de existencias, cálculo automático de
  totales y traslado del carrito anónimo al iniciar sesión.
- **Proceso de compra** completo, con validación de los datos de envío y recálculo de los
  montos en el servidor.
- Las tres **pasarelas de pago** bajo una misma interfaz: tarjeta con algoritmo de Luhn,
  PayPal con conversión de moneda y SINPE Móvil.
- **Emisión de pedidos y facturas** dentro de una transacción, con reversión completa si
  el pago se rechaza.
- Generación de la **factura en PDF** y envío del correo de confirmación con el documento
  adjunto.

> En el proyecto: `app/Services/Pagos/` · `PedidoService` · `CarritoService` ·
> `CheckoutController` · `app/Mail/ConfirmacionPedido`

### Camila — Cuentas, panel y reportes

- **Autenticación**: registro, inicio y cierre de sesión, límite de intentos fallidos y
  recuperación de contraseña por correo.
- **Perfil del cliente**: edición de datos personales y cambio de contraseña con
  verificación de la anterior.
- **Panel de administración**: mantenimiento de productos y categorías, y gestión del
  estado de los pedidos.
- **Reportes de ventas** por mes y por cliente, con las consultas agregadas, los gráficos
  en pantalla y la exportación a PDF.
- Control de acceso al área administrativa y reglas de validación de los formularios del
  sistema.

> En el proyecto: `app/Http/Controllers/Auth/` · `app/Http/Controllers/Admin/` ·
> `ReporteService` · `VerificarAdministrador` · `app/Http/Requests/`

---

## 5. Resumen por módulo

| Módulo | Responsable | Pruebas |
|---|---|---:|
| Catálogo, búsqueda y vistos recientemente | Anguar | 25 |
| Importación desde API externa e imágenes | Anguar | 21 |
| Diseño, interfaz y documentos PDF | Tatiana | — |
| Despliegue, SSL y HTTPS | Tatiana | — |
| Carrito de compras | Daniel | 41 |
| Pagos, pedidos y facturación | Daniel | 71 |
| Cuentas, perfil y recuperación | Camila | 42 |
| Panel de administración y reportes | Camila | 29 |
| Seguridad de la aplicación | Todo el equipo | 16 |
| **Total** | | **245** |

El proyecto cuenta con **245 pruebas automatizadas** y 721 aserciones, ejecutadas con
PHPUnit 11 sobre una base de datos en memoria. Cada integrante escribió las pruebas de su
propio módulo.

El diseño y el despliegue no se verifican con PHPUnit sino por revisión visual en
distintos dispositivos y por la comprobación del certificado en el sitio publicado; los
encabezados de seguridad que envía la aplicación sí están cubiertos en las 16 pruebas de
seguridad.
