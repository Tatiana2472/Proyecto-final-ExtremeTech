<?php

/*
|--------------------------------------------------------------------------
| Configuración de la tienda virtual
|--------------------------------------------------------------------------
|
| Todos los parámetros de negocio (impuestos, costos de envío, pasarelas de
| pago, cookies) viven acá para no dejar "números mágicos" repartidos en los
| controladores. Cada valor se puede sobreescribir desde el archivo .env.
|
*/

return [

    'nombre'  => env('TIENDA_NOMBRE', 'ExtremeTech'),
    'lema'    => env('TIENDA_LEMA', 'Tecnología que rinde, a precio que conviene'),
    'correo'  => env('TIENDA_CORREO', 'ventas@extremetechcr.com'),
    'telefono' => env('TIENDA_TELEFONO', '+506 4350-2222'),
    'direccion' => env('TIENDA_DIRECCION', 'Centro Comercial Plaza Heredia, Local 4E, Heredia, Costa Rica'),
    /*
    | Fuerza HTTPS aunque APP_ENV no sea production. Útil para probar el
    | certificado SSL en un entorno de pruebas (staging).
    */
    'forzar_https' => (bool) env('TIENDA_FORZAR_HTTPS', false),

    /*
    | Moneda de la tienda. Costa Rica usa colones (CRC) y no acostumbra
    | mostrar decimales en los precios de venta al público.
    */
    'moneda' => [
        'codigo'    => env('TIENDA_MONEDA', 'CRC'),
        'simbolo'   => env('TIENDA_MONEDA_SIMBOLO', '₡'),
        'decimales' => (int) env('TIENDA_MONEDA_DECIMALES', 0),
    ],

    /*
    | Impuesto al Valor Agregado. En Costa Rica la tarifa general es 13%.
    */
    'impuesto' => [
        'nombre' => env('TIENDA_IMPUESTO_NOMBRE', 'IVA'),
        'tasa'   => (float) env('TIENDA_IMPUESTO_TASA', 0.13),
    ],

    /*
    | Costos de envío. Si el subtotal supera "gratis_desde" el envío es
    | gratuito; así el cálculo del total sigue siendo automático.
    */
    'envio' => [
        'costo'        => (float) env('TIENDA_ENVIO_COSTO', 2900),
        'gratis_desde' => (float) env('TIENDA_ENVIO_GRATIS_DESDE', 75000),
    ],

    /*
    | Productos vistos recientemente (se guardan en una cookie del navegador).
    */
    'vistos_recientemente' => [
        'cookie' => 'productos_vistos',
        'maximo' => (int) env('TIENDA_VISTOS_MAXIMO', 6),
        'dias'   => (int) env('TIENDA_VISTOS_DIAS', 30),
    ],

    /*
    | Pasarelas de pago habilitadas en el checkout.
    |
    | El proyecto trabaja en modo "sandbox" (simulado): la clase
    | App\Services\Pagos\PasarelaTarjeta valida el número de tarjeta con el
    | algoritmo de Luhn y genera un identificador de transacción, sin enviar
    | datos reales a ningún banco. Para pasar a producción se cambia
    | TIENDA_PAGOS_MODO=live y se colocan las credenciales reales.
    */
    'pagos' => [
        'modo' => env('TIENDA_PAGOS_MODO', 'sandbox'),

        'metodos' => [
            'tarjeta' => [
                'etiqueta'    => 'Tarjeta de crédito / débito',
                'descripcion' => 'Visa, MasterCard o American Express',
                'habilitado'  => true,
            ],
            'paypal' => [
                'etiqueta'    => 'PayPal',
                'descripcion' => 'Pague con su cuenta PayPal',
                'habilitado'  => true,
            ],
            'sinpe' => [
                'etiqueta'    => 'SINPE Móvil',
                'descripcion' => 'Transferencia desde su teléfono',
                'habilitado'  => true,
            ],
        ],

        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID', ''),
            'secret'    => env('PAYPAL_SECRET', ''),
            'moneda'    => env('PAYPAL_MONEDA', 'USD'),
            // Tipo de cambio usado para convertir el total de colones a
            // dólares cuando se cobra por PayPal.
            'tipo_cambio' => (float) env('PAYPAL_TIPO_CAMBIO', 510),
        ],

        'sinpe' => [
            'numero' => env('SINPE_NUMERO', '8888-8888'),
        ],
    ],

    /*
    | Paginación del catálogo.
    */
    'productos_por_pagina' => (int) env('TIENDA_PRODUCTOS_POR_PAGINA', 9),
];