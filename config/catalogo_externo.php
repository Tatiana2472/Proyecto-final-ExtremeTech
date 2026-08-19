<?php

/*
|--------------------------------------------------------------------------
| Importación de catálogo desde una tienda externa
|--------------------------------------------------------------------------
|
| El catálogo se puede alimentar desde la API pública de una tienda hecha
| con WooCommerce. Esa API («Store API») es de solo lectura, no requiere
| llaves y es la misma que el propio sitio usa para pintar su vitrina, así
| que consumirla no implica saltarse ninguna medida de seguridad.
|
| Todos los valores se pueden sobreescribir desde .env para no dejar la
| dirección de un tercero incrustada en el código fuente.
|
*/

return [

    /*
    | Dirección base del sitio (sin barra final) y ruta del recurso.
    | Ejemplo del endpoint completo:
    |   https://ejemplo.com/wp-json/wc/store/v1/products?page=1&per_page=50
    */
    'base_url' => rtrim((string) env('CATALOGO_EXTERNO_URL', ''), '/'),
    'ruta_productos' => '/wp-json/wc/store/v1/products',

    /*
    | Prefijo del SKU con el que se marcan los productos importados. Sirve
    | para actualizarlos sin duplicar y para poder borrarlos después sin
    | tocar los productos propios del seeder.
    */
    'prefijo_sku' => env('CATALOGO_EXTERNO_PREFIJO', 'ET'),

    /*
    | Buenas prácticas al consumir un servicio ajeno:
    |  - identificarse con un User-Agent real en lugar de disfrazarse de
    |    navegador,
    |  - pedir de a poco y esperar entre peticiones para no cargar el
    |    servidor del otro sitio.
    */
    'user_agent' => env(
        'CATALOGO_EXTERNO_USER_AGENT',
        'UTN-ITI523-ProyectoAcademico/1.0 (+contacto: estudiante@est.utn.ac.cr)'
    ),
    'por_pagina' => (int) env('CATALOGO_EXTERNO_POR_PAGINA', 50),
    'pausa_segundos' => (float) env('CATALOGO_EXTERNO_PAUSA', 2.0),
    'timeout_segundos' => (int) env('CATALOGO_EXTERNO_TIMEOUT', 20),
    'reintentos' => (int) env('CATALOGO_EXTERNO_REINTENTOS', 2),

    /*
    | Existencias. La Store API no siempre publica la cantidad exacta en
    | inventario, solo si hay o no hay. Cuando no la publica se usa este
    | valor para que el carrito tenga con qué trabajar.
    */
    'existencias_por_defecto' => (int) env('CATALOGO_EXTERNO_EXISTENCIAS', 10),

    /*
    | Mapeo de categorías.
    |
    | La tienda externa tiene sus propias categorías; este proyecto tiene
    | seis. Se recorre la lista en orden y gana la primera cuyo texto
    | aparezca en la categoría externa o, si ahí no hay coincidencia, en el
    | nombre del producto. Si nada coincide se usa 'categoria_por_defecto'.
    |
    | El orden importa: un «monitor gamer» debe quedar en Monitores y no en
    | Gaming, por eso 'monitores' va antes.
    */
    'mapeo_categorias' => [
        'monitores' => ['monitor', 'pantalla', 'display'],
        'laptops' => ['laptop', 'portatil', 'notebook', 'macbook', 'ultrabook'],
        'celulares-y-tablets' => [
            'celular', 'smartphone', 'telefono', 'tablet', 'ipad', 'iphone',
            'reloj', 'smartwatch',
            // Nombres en inglés, que es como los publica DummyJSON.
            'smartphone', 'tablet', 'watch',
        ],
        'audio' => [
            'audio', 'audifono', 'auricular', 'parlante', 'microfono',
            'headset', 'bocina', 'sonido', 'diadema',
            'headphone', 'earbud', 'speaker',
        ],
        'gaming' => [
            'gaming', 'gamer', 'consola', 'playstation', 'xbox', 'nintendo',
            'videojuego', 'juego',
        ],
        'accesorios' => [
            'accesorio', 'teclado', 'mouse', 'cable', 'memoria', 'ssd',
            'disco', 'red', 'componente', 'fuente', 'case', 'silla',
            'accessor', 'keyboard', 'charger',
        ],
    ],

    'categoria_por_defecto' => 'accesorios',

    /*
    |------------------------------------------------------------------
    | Fotografías del catálogo (php artisan catalogo:imagenes)
    |------------------------------------------------------------------
    |
    | Los productos escritos a mano en el seeder usan ilustraciones SVG.
    | Este mapeo permite asignarles fotografías reales del CDN público de
    | DummyJSON, para que todo el catálogo se vea homogéneo.
    |
    | Formato: slug local => categorías de DummyJSON de donde tomar fotos.
    | Las categorías locales que no aparecen aquí conservan su ilustración,
    | porque DummyJSON no publica fotos de monitores ni de consolas.
    */
    'fotos' => [
        'laptops' => ['laptops'],
        'celulares-y-tablets' => ['smartphones', 'tablets'],
        'audio' => ['mobile-accessories'],
        'accesorios' => ['mobile-accessories'],
    ],

    /*
    | Carpeta donde el usuario puede colocar sus propias fotografías. Si
    | existe un archivo con el nombre del slug del producto (por ejemplo
    | monitor-lg-ultragear-24-165hz.jpg), tiene prioridad sobre todo lo
    | demás. Es la salida para las categorías sin fuente de fotos.
    */
    'fotos_propias' => 'img/productos/propias',

    /*
    | Origen alternativo: DummyJSON.
    |
    | API pública y gratuita creada expresamente para que los desarrolladores
    | prueben aplicaciones. Se usa cuando la tienda real bloquea el acceso
    | automatizado (por ejemplo, con Cloudflare), en lugar de intentar
    | esquivar esa protección.
    |
    | Solo se importan las categorías de tecnología: el catálogo completo
    | trae también ropa, muebles y abarrotes.
    */
    'dummyjson' => [
        'url' => env('CATALOGO_DUMMYJSON_URL', 'https://dummyjson.com'),
        'categorias' => [
            'laptops',
            'smartphones',
            'tablets',
            'mobile-accessories',
        ],

        /*
        | Rango de precio aceptable en colones. Es una malla de seguridad:
        | si una categoría del origen trae algo que no corresponde a una
        | tienda de tecnología (relojes de lujo, vehículos), el precio lo
        | delata y el producto se descarta.
        */
        'precio_minimo' => 3000,
        'precio_maximo' => 1800000,

        /*
        | Traducciones. La API está en inglés y este catálogo es en español,
        | así que NO se copia su texto: la descripción se arma en español a
        | partir de los campos estructurados, que son un conjunto cerrado de
        | valores y por eso se pueden traducir de forma confiable.
        */
        'tipos' => [
            'laptops' => 'Computadora portátil',
            'smartphones' => 'Teléfono inteligente',
            'tablets' => 'Tableta',
            'mobile-accessories' => 'Accesorio para dispositivos móviles',
        ],

        'garantias' => [
            '1 month warranty' => '1 mes de garantía',
            '3 months warranty' => '3 meses de garantía',
            '6 months warranty' => '6 meses de garantía',
            '1 year warranty' => '1 año de garantía',
            '2 years warranty' => '2 años de garantía',
            '5 years warranty' => '5 años de garantía',
            'Lifetime warranty' => 'garantía de por vida',
            'No warranty' => 'sin garantía',
        ],

        'envios' => [
            'Ships overnight' => 'Envío al día siguiente',
            'Ships in 1-2 business days' => 'Envío en 1 a 2 días hábiles',
            'Ships in 3-5 business days' => 'Envío en 3 a 5 días hábiles',
            'Ships in 1 week' => 'Envío en 1 semana',
            'Ships in 2 weeks' => 'Envío en 2 semanas',
            'Ships in 1 month' => 'Envío en 1 mes',
        ],

        /*
        | Variantes. Las tiendas reales publican el mismo modelo en varias
        | capacidades o colores. Generarlas amplía el catálogo de forma
        | realista (con marcas repetidas, como en una tienda de verdad).
        | Cada entrada es: etiqueta => multiplicador de precio.
        */
        'variantes' => [
            'laptops' => [
                '8 GB RAM / 512 GB SSD' => 1.00,
                '16 GB RAM / 1 TB SSD' => 1.22,
                '32 GB RAM / 2 TB SSD' => 1.55,
            ],
            'celulares-y-tablets' => [
                '128 GB' => 1.00,
                '256 GB' => 1.14,
                '512 GB' => 1.32,
            ],
            'predeterminado' => [
                'Negro' => 1.00,
                'Blanco' => 1.00,
                'Azul' => 1.04,
            ],
        ],

        'devoluciones' => [
            '7 days return policy' => 'devolución dentro de 7 días',
            '30 days return policy' => 'devolución dentro de 30 días',
            '60 days return policy' => 'devolución dentro de 60 días',
            '90 days return policy' => 'devolución dentro de 90 días',
            'No return policy' => 'sin política de devolución',
        ],
        // Los precios vienen en dólares. Se usa el mismo tipo de cambio
        // configurado para PayPal, para no tener dos verdades distintas.
        'tipo_cambio' => (float) env('PAYPAL_TIPO_CAMBIO', 510),
    ],

];
