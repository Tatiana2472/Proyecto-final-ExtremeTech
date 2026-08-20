# Documento de pruebas — ExtremTech

Entregable «Pruebas unitarias para verificar la funcionalidad del sistema».

---

## 1. Resumen

| Dato | Valor |
|---|---|
| Herramienta | PHPUnit 11 (integrado en Laravel 12) |
| Total de pruebas | **210** |
| Total de aserciones | **639** |
| Resultado | **210 pasan, 0 fallan** |
| Duración aproximada | 15 segundos |
| Base de datos usada | SQLite **en memoria** (`:memory:`) |

### Cómo ejecutarlas

```bash
php artisan test
```

Solo las unitarias:

```bash
php artisan test --testsuite=Unit
```

Solo un archivo:

```bash
php artisan test tests/Feature/CheckoutTest.php
```

Una prueba concreta:

```bash
php artisan test --filter=test_completa_la_compra_con_tarjeta
```

> Las pruebas usan una base de datos SQLite en memoria y el *trait* `RefreshDatabase`,
> que crea las tablas antes de cada prueba y las descarta al terminar. Por eso
> **nunca modifican** `database/database.sqlite` ni los datos de demostración.

---

## 2. Distribución

### 2.1 Pruebas unitarias (43)

Prueban una clase aislada, sin peticiones HTTP.

| Archivo | Pruebas | Qué verifica |
|---|---:|---|
| `tests/Unit/TotalesCarritoTest.php` | 8 | Cálculo automático del total: IVA, envío, envío gratis, redondeo. |
| `tests/Unit/PasarelaTarjetaTest.php` | 12 | Validación de tarjeta (Luhn), vencimiento, CVV, marca, rechazos. |
| `tests/Unit/PasarelasAlternativasTest.php` | 11 | PayPal (conversión de moneda), SINPE Móvil y el selector de pasarelas. |
| `tests/Unit/CarritoServiceTest.php` | 12 | Agregar, actualizar, eliminar, vaciar, límites de inventario y adopción del carrito. |

### 2.2 Pruebas de integración / funcionales (167)

Hacen peticiones HTTP reales contra la aplicación.

| Archivo | Pruebas | Qué verifica |
|---|---:|---|
| `tests/Feature/AutenticacionTest.php` | 18 | Registro, inicio y cierre de sesión, bcrypt, límite de intentos, rutas protegidas. |
| `tests/Feature/RecuperarContrasenaTest.php` | 12 | Recuperación de contraseña por correo: envío, token, caducidad y reutilización. |
| `tests/Feature/CatalogoTest.php` | 15 | Portada, listado, detalle, búsqueda, filtros, orden y paginación. |
| `tests/Feature/VistosRecientementeTest.php` | 10 | Cookies de productos vistos recientemente. |
| `tests/Feature/CarritoTest.php` | 15 | Carrito por HTTP y por AJAX, totales y aislamiento entre usuarios. |
| `tests/Feature/CheckoutTest.php` | 22 | Proceso de compra completo con los tres métodos de pago. |
| `tests/Feature/CorreoConfirmacionTest.php` | 6 | Correo de confirmación de compra con la factura adjunta. |
| `tests/Feature/PerfilTest.php` | 12 | Datos personales y cambio de contraseña. |
| `tests/Feature/PedidoTest.php` | 12 | Historial, detalle, permisos y factura en PDF. |
| `tests/Feature/CategoriaAdminTest.php` | 12 | Mantenimiento de categorías desde el panel. |
| `tests/Feature/ReporteVentasTest.php` | 17 | Reportes por mes y por cliente, en pantalla y en PDF. |
| `tests/Feature/SeguridadTest.php` | 16 | Inyección SQL, XSS, CSRF, sesiones y control de acceso. |

---

## 3. Cobertura de los requisitos del proyecto

| Requisito del documento | Pruebas que lo verifican |
|---|---|
| Registro de usuarios nuevos | `registra_un_usuario_nuevo_y_lo_autentica`, `no_permite_registrar_un_correo_repetido`, `valida_los_datos_del_registro` |
| Inicio y cierre de sesión | `un_cliente_inicia_sesion_con_credenciales_correctas`, `no_inicia_sesion_con_contrasena_incorrecta`, `cierra_la_sesion_correctamente` |
| Perfil: modificar datos y ver historial | `actualiza_los_datos_personales`, `cambia_la_contrasena`, `el_historial_muestra_los_pedidos_del_usuario` |
| Categorización de productos | `la_portada_muestra_categorias_y_destacados`, `filtra_por_categoria` |
| Lista con descripción, precio e imágenes | `el_detalle_muestra_descripcion_precio_e_imagen` |
| Búsqueda y filtrado por nombre, categoría y precio | `busca_productos_por_nombre`, `busca_productos_por_marca_y_por_sku`, `filtra_por_rango_de_precio`, `combina_busqueda_categoria_y_precio` |
| Carrito: agregar, eliminar, actualizar | `agrega_un_producto_al_carrito`, `actualiza_la_cantidad_de_una_linea`, `elimina_una_linea_del_carrito`, `vacia_el_carrito` |
| Cálculo automático del total con impuestos y envío | `calcula_el_impuesto_el_envio_y_el_total`, `el_envio_es_gratis_al_alcanzar_el_monto_minimo`, `el_carrito_muestra_el_desglose_del_total` |
| Pasarela de pago | `completa_la_compra_con_tarjeta`, `completa_la_compra_con_paypal`, `completa_la_compra_con_sinpe_movil` |
| Tabla de compra con usuario, fecha y monto | `completa_la_compra_con_tarjeta` (comprueba `user_id`, `fecha_compra`, `total`) |
| Factura | `emite_la_factura_al_pagar`, `descarga_la_factura_en_pdf` |
| Confirmación con número de seguimiento | `la_confirmacion_muestra_el_seguimiento_y_el_monto` |
| Cookies de productos vistos recientemente | Los 10 casos de `VistosRecientementeTest` |
| Reportes de ventas por mes y por cliente en PDF | `genera_el_reporte_por_mes_en_pdf`, `genera_el_reporte_por_cliente_en_pdf`, `genera_el_reporte_individual_de_un_cliente_en_pdf` |
| Validación de entradas del usuario | `valida_los_datos_del_registro`, `valida_la_cantidad_enviada`, `valida_los_datos_de_envio`, `valida_el_anio_del_reporte` |
| Prevención de inyección SQL | `el_buscador_no_permite_inyeccion_sql`, `los_filtros_numericos_rechazan_texto_arbitrario`, `rechaza_un_criterio_de_orden_no_permitido` |
| Prevención de XSS | `escapa_el_html_en_los_nombres_de_los_productos`, `escapa_el_termino_de_busqueda_reflejado_en_la_pagina`, `escapa_el_nombre_de_la_categoria` |
| Sesiones seguras y cifrado de contraseñas | `la_contrasena_se_guarda_cifrada_con_bcrypt`, `regenera_el_identificador_de_sesion_al_iniciar_sesion` |
| Manejo adecuado de datos sensibles | `solo_guarda_los_ultimos_cuatro_digitos_de_la_tarjeta`, `registra_el_pago_sin_guardar_datos_sensibles`, `no_devuelve_los_datos_de_la_tarjeta_al_formulario_tras_un_rechazo` |
| Notificación al cliente por correo | `envia_el_correo_de_confirmacion_al_completar_la_compra`, `el_correo_adjunta_la_factura_en_pdf` |
| Recuperación de contraseña | `envia_el_correo_con_el_enlace`, `restablece_la_contrasena_con_un_token_valido`, `el_token_no_se_puede_reutilizar` |
| Mantenimiento del catálogo (categorías) | `crea_una_categoria`, `actualiza_una_categoria`, `una_categoria_con_productos_se_desactiva_en_lugar_de_borrarse` |

---

## 4. Casos de prueba destacados

Estos son los casos más importantes, con su entrada y su resultado esperado.

### CP-01 · Cálculo del total con impuestos y envío

**Archivo:** `tests/Unit/TotalesCarritoTest.php`

| Entrada | Esperado | Resultado |
|---|---|---|
| Subtotal ₡50 000, 2 artículos | IVA ₡6 500 · envío ₡2 900 · **total ₡59 400** | ✔ |
| Subtotal ₡75 000 (alcanza el mínimo) | IVA ₡9 750 · **envío ₡0** · total ₡84 750 | ✔ |
| Subtotal ₡60 000 | Faltan **₡15 000** para el envío gratis | ✔ |
| Carrito vacío | Todo en ₡0, sin cobrar envío | ✔ |
| Subtotal negativo (`-5000`) | Se trata como ₡0 | ✔ |
| Subtotal ₡33 333,33 | IVA ₡4 333,33 (redondeo a 2 decimales) | ✔ |

### CP-02 · Validación de la tarjeta con el algoritmo de Luhn

**Archivo:** `tests/Unit/PasarelaTarjetaTest.php`

| Entrada | Esperado | Resultado |
|---|---|---|
| `4111111111111111` | Válida · marca **Visa** | ✔ |
| `5500005555555559` | Válida · marca **MasterCard** | ✔ |
| `378282246310005` | Válida · marca **American Express** | ✔ |
| `4111111111111112` | Inválida (dígito verificador incorrecto) | ✔ |
| Vencimiento del año pasado | Rechazo: «La tarjeta está vencida» | ✔ |
| CVV `12` o `12345` | Rechazo: CVV inválido | ✔ |
| `4000000000000002` | Rechazo: «Tarjeta declinada por el banco emisor» | ✔ |
| Monto ₡0 | Rechazo: el monto debe ser mayor que cero | ✔ |

### CP-03 · No se guardan datos sensibles de la tarjeta

**Archivo:** `tests/Unit/PasarelaTarjetaTest.php` y `tests/Feature/CheckoutTest.php`

| Verificación | Resultado |
|---|---|
| Solo se guardan la marca (`Visa`) y los últimos 4 dígitos (`1111`) | ✔ |
| El número completo **no aparece** en lo que se persiste | ✔ |
| El número y el CVV **no quedan** en la sesión al fallar el formulario | ✔ |
| Los datos de envío sí se conservan para no obligar a reescribirlos | ✔ |

### CP-04 · Compra completa con tarjeta

**Archivo:** `tests/Feature/CheckoutTest.php`

**Entrada:** cliente autenticado, 2 unidades de un producto de ₡20 000, tarjeta
`4111 1111 1111 1111`.

| Verificación | Esperado | Resultado |
|---|---|---|
| Pedido creado | subtotal ₡40 000 · IVA ₡5 200 · envío ₡2 900 · **total ₡48 100** | ✔ |
| Identificación del usuario | `user_id` del cliente | ✔ |
| Fecha de compra | registrada | ✔ |
| Estado | `pagado` / `aprobado` | ✔ |
| Número de seguimiento | generado, empieza con `TS` | ✔ |
| Detalle del pedido | nombre, SKU, cantidad y subtotal correctos | ✔ |
| Factura | emitida con número `FAC-…`, nombre y cédula del cliente | ✔ |
| Inventario | descontado de 10 a 7 unidades | ✔ |
| Carrito | queda vacío | ✔ |

### CP-05 · Pago rechazado: la compra se revierte por completo

**Archivo:** `tests/Feature/CheckoutTest.php`

**Entrada:** mismo carrito, tarjeta `4000 0000 0000 0002`.

| Verificación | Esperado | Resultado |
|---|---|---|
| Pedidos en la base de datos | **0** | ✔ |
| Líneas de detalle | **0** | ✔ |
| Facturas | **0** | ✔ |
| Inventario | intacto en 10 unidades | ✔ |
| Carrito | intacto, listo para reintentar | ✔ |
| Mensaje al usuario | explica el motivo del rechazo | ✔ |

### CP-06 · El total no se puede alterar desde el navegador

**Archivo:** `tests/Feature/CheckoutTest.php`

| Entrada | Esperado | Resultado |
|---|---|---|
| El formulario envía `total=1`, `subtotal=1` | El pedido se guarda con **₡48 100**, calculado por el servidor | ✔ |
| Se manipula `precio_unitario` en `cart_items` a `1` | Se cobra el precio real del catálogo (₡20 000) | ✔ |

### CP-07 · Cookies de productos vistos recientemente

**Archivo:** `tests/Feature/VistosRecientementeTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Se abre un producto | La cookie `productos_vistos` contiene su id | ✔ |
| Se abre un segundo producto | El más reciente queda **de primero** | ✔ |
| Se vuelve a abrir uno ya visto | No se duplica; sube al primer lugar | ✔ |
| Máximo configurado en 3 y se visita un cuarto | Se descarta el más antiguo | ✔ |
| Navegación por portada, catálogo y carrito | Aparece la franja «Vistos recientemente» | ✔ |
| Sin productos vistos | La sección **no** se muestra | ✔ |
| Producto que se está viendo | No aparece en su propia lista | ✔ |
| Cookie manipulada (`["<script>", -5, "abc"]`) | Se ignora sin romper la página | ✔ |
| Cookie que no es JSON | Se ignora sin romper la página | ✔ |
| Producto desactivado en la cookie | No se muestra | ✔ |

### CP-08 · Prevención de inyección SQL

**Archivo:** `tests/Feature/SeguridadTest.php`

| Carga enviada al buscador | Esperado | Resultado |
|---|---|---|
| `' OR '1'='1` | Respuesta 200, sin efecto | ✔ |
| `'; DROP TABLE products; --` | Las tablas siguen existiendo | ✔ |
| `1' UNION SELECT password FROM users --` | Sin fuga de datos | ✔ |
| `admin'--` | Sin efecto | ✔ |
| `orden=precio; DROP TABLE products` | Error de validación (lista blanca) | ✔ |
| `min=0 OR 1=1` | Error de validación (debe ser numérico) | ✔ |

### CP-09 · Prevención de XSS

**Archivo:** `tests/Feature/SeguridadTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Producto llamado `<script>alert("xss")</script>Laptop` | Se muestra escapado (`&lt;script&gt;`), no se ejecuta | ✔ |
| Resumen con `<img src=x onerror=alert(1)>` | Escapado | ✔ |
| Término de búsqueda `"><script>alert(1)</script>` | Escapado al reflejarse en la página | ✔ |
| Nombre de categoría con `<script>` | Escapado | ✔ |
| Nombre de usuario con `<script>` | Escapado en el perfil | ✔ |

### CP-10 · Control de acceso y privacidad de los pedidos

**Archivos:** `tests/Feature/PedidoTest.php`, `CarritoTest.php`, `SeguridadTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Un usuario abre el pedido de otro | **403 Prohibido** | ✔ |
| Un usuario descarga la factura de otro | **403 Prohibido** | ✔ |
| Un usuario abre la confirmación de un pedido ajeno | **403 Prohibido** | ✔ |
| Un usuario intenta borrar una línea del carrito de otro | La línea ajena queda intacta | ✔ |
| Un cliente entra a `/admin` o a los reportes | **403 Prohibido** | ✔ |
| Un cliente intenta crear o borrar productos | **403 Prohibido**, el catálogo no cambia | ✔ |
| El administrador entra a todo lo anterior | **200 OK** | ✔ |
| Un visitante entra al área de clientes | Redirección a iniciar sesión | ✔ |

### CP-11 · Límite de intentos de inicio de sesión

**Archivo:** `tests/Feature/AutenticacionTest.php`

| Entrada | Esperado | Resultado |
|---|---|---|
| 5 intentos fallidos y un sexto con la contraseña **correcta** | Se bloquea: «Demasiados intentos fallidos» | ✔ |

### CP-12 · Reportes de ventas

**Archivo:** `tests/Feature/ReporteVentasTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| 2 ventas en enero (₡100 000 y ₡50 000) y 1 en marzo (₡75 000) | Enero: 2 pedidos / ₡150 000 · Marzo: 1 / ₡75 000 · Febrero: 0 | ✔ |
| Se devuelven los meses sin ventas | 12 filas, con ceros donde corresponde | ✔ |
| Pedido con pago **rechazado** | **No** se cuenta como venta | ✔ |
| Ventas por cliente | Agrupadas y ordenadas de mayor a menor monto | ✔ |
| Promedio por cliente | ₡50 000 en 2 pedidos → ₡25 000 | ✔ |
| Rango de fechas | Solo incluye las ventas dentro del rango | ✔ |
| PDF por mes | 200 OK · `application/pdf` · el archivo empieza con `%PDF-` | ✔ |
| PDF por cliente | 200 OK · `application/pdf` · `%PDF-` | ✔ |
| PDF individual de cliente | 200 OK · `application/pdf` · `%PDF-` | ✔ |
| Sin ventas | El ticket promedio es 0 (no hay división entre cero) | ✔ |

### CP-13 · El carrito del visitante no se pierde al iniciar sesión

**Archivos:** `tests/Unit/CarritoServiceTest.php`, `tests/Feature/AutenticacionTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Visitante agrega 2 unidades y luego inicia sesión | El carrito queda asociado a su cuenta con las 2 unidades | ✔ |
| El usuario ya tenía 1 unidad y como visitante agregó 4 | Se conserva la cantidad **mayor** (4) | ✔ |
| El carrito anónimo | Se elimina después de trasladarlo | ✔ |

### CP-14 · Control de inventario

**Archivos:** `tests/Unit/CarritoServiceTest.php`, `tests/Feature/CheckoutTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Agregar 5 unidades de un producto con 3 en existencia | Error: «Solo quedan 3 unidades» | ✔ |
| Agregar 999 unidades | Se limita al máximo de 20 por línea | ✔ |
| El inventario baja mientras el producto está en el carrito | La compra se cancela y el inventario no se altera | ✔ |
| El producto se desactiva mientras está en el carrito | La compra se cancela | ✔ |
| Producto agotado en el catálogo | El botón «Agregar al carrito» está deshabilitado | ✔ |

### CP-15 · Carrito asíncrono (AJAX)

**Archivo:** `tests/Feature/CarritoTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| `POST /carrito/agregar/{producto}` esperando JSON | 200 con `exito: true` y los totales recalculados | ✔ |
| Cantidad mayor que las existencias | **422** con `exito: false` y el motivo | ✔ |
| `GET /carrito/contador` | JSON con la cantidad de artículos | ✔ |

### CP-16 · Correo de confirmación de compra

**Archivo:** `tests/Feature/CorreoConfirmacionTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Compra completada | Se envía `ConfirmacionPedido` al correo del cliente | ✔ |
| Pago rechazado | **No** se envía ningún correo | ✔ |
| Contenido del correo | Incluye pedido, seguimiento, productos y total (₡48 100) | ✔ |
| Adjunto | Un solo archivo `FAC-….pdf` con tipo `application/pdf` | ✔ |
| Datos sensibles | El número de tarjeta **no** aparece en el correo | ✔ |
| El servidor de correo está caído | La compra **igual se completa**; el fallo queda en el log | ✔ |

### CP-17 · Recuperación de contraseña

**Archivo:** `tests/Feature/RecuperarContrasenaTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Correo registrado | Se envía la notificación con el enlace | ✔ |
| Correo **no** registrado | Mismo mensaje de éxito, sin enviar nada (no se revela qué cuentas existen) | ✔ |
| Token válido | La contraseña se cambia y redirige a iniciar sesión | ✔ |
| Token inventado | Error y la contraseña **no** cambia | ✔ |
| Reutilizar el mismo enlace | Falla la segunda vez | ✔ |
| Contraseña débil o sin confirmar | Error de validación | ✔ |
| Contraseña nueva | Se guarda cifrada con bcrypt (`$2y$…`) | ✔ |

### CP-18 · Mantenimiento de categorías

**Archivo:** `tests/Feature/CategoriaAdminTest.php`

| Escenario | Esperado | Resultado |
|---|---|---|
| Un cliente entra a `/admin/categorias` | **403 Prohibido** | ✔ |
| Crear una categoría | Se guarda con su slug, ícono y estado | ✔ |
| No se indica la URL amigable | Se genera del nombre (`Cámaras y Vídeo` → `camaras-y-video`) | ✔ |
| Slug repetido | Error de validación | ✔ |
| Ícono con formato inválido | Error de validación | ✔ |
| Desactivar una categoría | Deja de verse en la tienda (404 al navegarla) | ✔ |
| Eliminar una categoría **sin** productos | Se borra | ✔ |
| Eliminar una categoría **con** productos | Se desactiva; ni ella ni sus productos se pierden | ✔ |

---

## 5. Pruebas manuales complementarias

Estas se verificaron en el navegador porque dependen de JavaScript, que PHPUnit no ejecuta:

| # | Prueba | Resultado |
|---|---|---|
| M-01 | Los botones **−** / **+** cambian la cantidad respetando el mínimo y el máximo | ✔ |
| M-02 | «Agregar al carrito» funciona **sin recargar la página** y el contador del encabezado pasa a la cantidad correcta | ✔ |
| M-03 | Aparece el aviso flotante (toast) de confirmación | ✔ |
| M-04 | Al cambiar el método de pago solo se muestran y se exigen los campos de ese método | ✔ |
| M-05 | El número de tarjeta se formatea en grupos de 4 mientras se escribe | ✔ |
| M-06 | El botón «Pagar» se desactiva al enviarse, evitando el doble cobro | ✔ |
| M-07 | Vaciar el carrito y eliminar un producto piden confirmación | ✔ |
| M-08 | El gráfico de ventas de Chart.js se dibuja sin errores en la consola | ✔ |
| M-09 | Los tres PDF se descargan y abren correctamente, con el símbolo ₡ y las tildes bien | ✔ |
| M-10 | Diseño responsive verificado en 375 px (móvil), 768 px (tableta) y 1280 px (escritorio) | ✔ |
| M-11 | El correo de recuperación llega con el enlace correcto y todo el texto en español | ✔ |
| M-12 | El correo de confirmación llega con la factura PDF adjunta | ✔ |
| M-13 | Los mensajes de validación se muestran en español | ✔ |

---

## 6. Salida de la última ejecución

```
PASS  Tests\Unit\CarritoServiceTest              (12 pruebas)
PASS  Tests\Unit\PasarelaTarjetaTest             (12 pruebas)
PASS  Tests\Unit\PasarelasAlternativasTest       (11 pruebas)
PASS  Tests\Unit\TotalesCarritoTest              ( 8 pruebas)
PASS  Tests\Feature\AutenticacionTest            (18 pruebas)
PASS  Tests\Feature\CarritoTest                  (15 pruebas)
PASS  Tests\Feature\CatalogoTest                 (15 pruebas)
PASS  Tests\Feature\CategoriaAdminTest           (12 pruebas)
PASS  Tests\Feature\CheckoutTest                 (22 pruebas)
PASS  Tests\Feature\CorreoConfirmacionTest       ( 6 pruebas)
PASS  Tests\Feature\PedidoTest                   (12 pruebas)
PASS  Tests\Feature\PerfilTest                   (12 pruebas)
PASS  Tests\Feature\RecuperarContrasenaTest      (12 pruebas)
PASS  Tests\Feature\ReporteVentasTest            (17 pruebas)
PASS  Tests\Feature\SeguridadTest                (16 pruebas)
PASS  Tests\Feature\VistosRecientementeTest      (10 pruebas)

Tests:    210 passed (639 assertions)
Duration: 13.11s
```
