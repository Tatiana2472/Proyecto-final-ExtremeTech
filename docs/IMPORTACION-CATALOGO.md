# Importación de catálogo desde una API pública

Este módulo permite ampliar el catálogo de la tienda consumiendo la **Store
API** de un sitio hecho con WooCommerce, en lugar de escribir cada producto a
mano en `ProductSeeder`.

---

## 1. Por qué una API y no «scraping»

WooCommerce publica un servicio de solo lectura en:

```
https://SITIO/wp-json/wc/store/v1/products
```

Es el mismo servicio que el propio sitio usa para dibujar su vitrina. Eso
significa que:

| Punto | Situación |
|---|---|
| ¿Requiere usuario o contraseña? | No. Es público por diseño. |
| ¿Se salta alguna medida de seguridad? | No. No hay captcha, ni sesión, ni bloqueo que evadir. |
| ¿Se leen datos personales? | No. Solo nombre, precio, existencias e imagen de productos. |
| ¿Se altera el sitio de origen? | No. Solo se hacen peticiones `GET`. |

Esto es relevante porque la **Ley 9048** de Costa Rica (delitos informáticos)
sanciona el acceso *no autorizado* a un sistema. Consumir un recurso que el
sitio publica abiertamente y sin restricción no encaja en ese supuesto.

Aun así, el proyecto no se limita a lo mínimo legal y aplica tres medidas de
buena conducta:

1. **Se identifica.** El encabezado `User-Agent` dice quién hace la petición
   y para qué, en vez de disfrazarse de navegador.
2. **Pide despacio.** Hay una pausa configurable entre página y página
   (2 segundos por defecto) para no cargar el servidor ajeno.
3. **Consulta `robots.txt`.** Si el sitio desaconseja la ruta, el comando
   avisa y pide confirmación antes de continuar.

> **Nota sobre derechos de autor:** los datos de hecho (precio, existencias)
> no son obras protegidas. Las descripciones e imágenes sí pertenecen al
> sitio de origen, por lo que este catálogo se usa únicamente con fines
> académicos y no se redistribuye.

---

## 1.b Cuando la tienda bloquea el acceso: Cloudflare

Al probar `extremtech.cr` la respuesta no fue JSON sino una página de
desafío de **Cloudflare** (`/cdn-cgi/challenge-platform/`). Es decir: el
sitio tiene protección anti-bot activa.

**Decisión de diseño: no se evade esa protección.** Hacer que el programa se
haga pasar por un navegador para superar el desafío sí sería sortear una
medida de seguridad, y ahí es donde la Ley 9048 empieza a aplicar. La
diferencia entre «leer un recurso público» y «acceso no autorizado» es
exactamente esa.

En su lugar el proyecto ofrece un segundo origen: **DummyJSON**
(`--fuente=dummyjson`), una API pública y gratuita creada expresamente para
que los desarrolladores prueben aplicaciones. Se importan solo las
categorías de tecnología y los precios se convierten de dólares a colones.

## 2. Configuración

En el archivo `.env`:

```dotenv
CATALOGO_EXTERNO_URL=https://ejemplo.com
CATALOGO_EXTERNO_USER_AGENT="UTN-ITI523-ProyectoAcademico/1.0"
CATALOGO_EXTERNO_PAUSA=2
```

El resto de los parámetros (mapeo de categorías, existencias por defecto,
tiempo de espera) está en `config/catalogo_externo.php`.

---

## 3. Uso

```bash
# 1. Ver qué traería, SIN escribir en la base de datos.
php artisan catalogo:importar --fuente=dummyjson --simular --limite=10

# 2. Importar de verdad (una página, ~50 productos).
php artisan catalogo:importar

# 3. Traer más páginas y descargar las imágenes al servidor local,
#    útil para exponer el proyecto sin conexión a internet.
php artisan catalogo:importar --paginas=4 --imagenes

# 4. Volver a dejar solo el catálogo propio.
php artisan catalogo:importar --purgar
```

### Opciones

| Opción | Efecto |
|---|---|
| `--fuente=` | `woocommerce` (por defecto) o `dummyjson`. |
| `--url=` | Usa otra tienda sin tocar el `.env`. |
| `--paginas=N` | Cuántas páginas recorrer (50 productos por página). |
| `--limite=N` | Corta después de N productos. |
| `--categoria=N` | Solo una categoría del sitio de origen. |
| `--simular` | Muestra una tabla y no escribe nada. |
| `--imagenes` | Descarga las imágenes a `public/img/productos/externos/`. |
| `--variantes=N` | Genera N versiones por producto (capacidad o color). |
| `--purgar` | Borra los productos importados. |
| `--forzar` | Omite las confirmaciones interactivas. |

---

## 3.b Fotografías del catálogo

Los productos escritos a mano en el seeder usan ilustraciones SVG y los
importados traen fotografías. Para que el catálogo se vea homogéneo:

```bash
php artisan catalogo:imagenes --simular   # ver qué haría
php artisan catalogo:imagenes             # aplicar
php artisan catalogo:imagenes --restaurar # volver a las ilustraciones
```

Orden de prioridad para cada producto:

1. Una foto propia en `public/img/productos/propias/{slug}.jpg`
2. Una foto del CDN público de DummyJSON, según la categoría
3. La ilustración SVG que ya tenía

### Dos estilos posibles, no una mezcla

El catálogo original usa ilustraciones dibujadas a mano y los productos
importados traen fotografías. Mezclarlos se ve mal, así que hay que elegir
uno de los dos caminos:

**a) Ilustraciones para todo (recomendado)**

```bash
php artisan catalogo:imagenes --ilustraciones
```

Dibuja una ilustración para cada producto importado, en el mismo estilo del
catálogo original: degradado de fondo, silueta según la categoría y la marca
abajo. Las ilustraciones hechas a mano no se tocan.

Ventajas: todo el catálogo se ve igual, las imágenes quedan dentro del
proyecto y no dependen de internet, y no hay ninguna imagen de terceros.
Esto último importa el día de la exposición.

**b) Fotografías para todo**

```bash
php artisan catalogo:imagenes
```

Toma fotos del CDN público de DummyJSON. El problema: no publica fotos de
monitores ni de consolas, así que esos productos quedan sin cubrir y hay que
conseguir la foto por aparte. Y una foto genérica de un monitor cualquiera
en un producto que dice «LG UltraGear 24"» se nota.

En cualquiera de los dos casos, una foto propia guardada en
`public/img/productos/propias/{slug}.jpg` tiene prioridad sobre todo lo
demás, y `--restaurar` devuelve el catálogo original a sus ilustraciones.

### Variantes

Las tiendas reales publican el mismo modelo en varias capacidades:

```bash
php artisan catalogo:importar --fuente=dummyjson --variantes=3
```

Genera «iPhone 13 Pro», «iPhone 13 Pro 256 GB» y «iPhone 13 Pro 512 GB» con
precios escalonados. Es la forma honesta de ampliar el catálogo: no inventa
productos que no existen, replica el comportamiento de una tienda real.

---

## 4. Cómo funciona por dentro

```
ClienteTiendaExterna  \
                       >  ProductoExterno  ->  ImportadorCatalogo  ->  Product
ClienteDummyJson      /      (normaliza)        (traduce y guarda)     (Eloquent)
```

Las dos clases de origen cumplen la interfaz `FuenteDeProductos`, así que el
comando no sabe de dónde vienen los datos. Agregar un tercer origen es
escribir una clase más, sin tocar nada de lo existente.

La separación en tres clases no es decorativa:

- **`ClienteTiendaExterna`** es lo único que toca la red. Usa un *generador*
  (`yield`) para recorrer miles de productos sin cargarlos todos en memoria.
- **`ProductoExterno`** es un objeto `readonly` que aísla al resto del
  sistema del formato de WooCommerce. Para cambiar de proveedor solo se
  escribe otro método de fábrica.
- **`ImportadorCatalogo`** contiene las reglas de negocio y no toca la red,
  por eso se puede probar con pruebas unitarias puras.

### Detalles que suelen dar problemas

**Los precios vienen en la unidad mínima de la moneda.** WooCommerce devuelve
`"price": "18900000"` junto a `"currency_minor_unit": 2`. Eso son
₡189 000,00, no ₡18 900 000. Hay que dividir entre `10^minor_unit`.

**La importación es idempotente.** Cada producto recibe un SKU determinista
(`ET-4001`) y se guarda con `updateOrCreate` sobre esa columna única. Correr
el comando dos veces actualiza los precios en lugar de duplicar filas.

**Los slugs pueden chocar.** La columna `slug` es única; si el nombre
importado genera un slug que ya existe, se le agrega el identificador
externo.

**Se descartan productos sin precio.** Un producto «variable» o «consultar
precio» rompería el cálculo del carrito, así que no se importa.

---

## 5. Pruebas

`tests/Unit/ImportadorCatalogoTest.php` cubre la traducción de datos sin
tocar la red ni la base:

```bash
php artisan test --filter=ImportadorCatalogoTest
```

Verifica la conversión de precios, la limpieza de HTML, la detección de
descuentos y existencias, y el descarte de registros inválidos.
