<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido {{ $pedido->numero_pedido }}</title>
</head>
{{--
    Los correos se maquetan con estilos en línea porque muchos clientes de
    correo (Outlook, Gmail) ignoran las hojas de estilo externas.
--}}
<body style="margin:0; padding:0; background:#f4f6f9; font-family: Arial, Helvetica, sans-serif; color:#23303f;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="max-width:600px; background:#ffffff; border-radius:10px; overflow:hidden;">

                {{-- Encabezado --}}
                <tr>
                    <td style="background:#10161d; padding:22px 28px; border-bottom:4px solid #ff7a1a;">
                        <span style="color:#ffffff; font-size:24px; font-weight:bold; letter-spacing:-0.5px;">take</span><span
                              style="color:#7dd3fc; font-size:24px; font-weight:bold; letter-spacing:-0.5px;">tech</span>
                        <span style="display:inline-block; background:#ff7a1a; color:#ffffff; font-size:11px;
                                     font-weight:bold; padding:2px 7px; border-radius:4px;">CR</span>
                        <span style="display:inline-block; background:#0ea5e9; color:#ffffff; font-size:9px;
                                     font-weight:bold; letter-spacing:0.6px; padding:2px 8px; margin-left:8px;">
                            LO MEJOR EN TECNOLOGÍA
                        </span>
                    </td>
                </tr>

                {{-- Mensaje principal --}}
                <tr>
                    <td style="padding:28px 28px 8px;">
                        <h1 style="margin:0 0 10px; font-size:20px; color:#10161d;">
                            ¡Gracias por su compra, {{ \Illuminate\Support\Str::of($pedido->envio_nombre)->before(' ') }}!
                        </h1>
                        <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                            Recibimos su pedido y el pago quedó
                            <strong>{{ $pedido->estaPagado() ? 'aprobado' : 'pendiente de confirmación' }}</strong>.
                            Le adjuntamos la factura en PDF.
                        </p>
                    </td>
                </tr>

                {{-- Datos del pedido --}}
                <tr>
                    <td style="padding:0 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f7f9fc; border:1px solid #e3e8ef; border-radius:8px;">
                            <tr>
                                <td style="padding:14px 16px; font-size:13px;">
                                    <strong style="color:#6b7889;">N.º de pedido:</strong> {{ $pedido->numero_pedido }}<br>
                                    <strong style="color:#6b7889;">N.º de seguimiento:</strong> {{ $pedido->numero_seguimiento }}<br>
                                    <strong style="color:#6b7889;">Fecha de compra:</strong>
                                    {{ $pedido->fecha_compra?->translatedFormat('d/m/Y h:i a') }}<br>
                                    <strong style="color:#6b7889;">Método de pago:</strong> {{ $pedido->etiquetaMetodoPago() }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Detalle --}}
                <tr>
                    <td style="padding:22px 28px 0;">
                        <h2 style="margin:0 0 10px; font-size:15px; color:#10161d;">Detalle de la compra</h2>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">
                            <tr>
                                <th align="left" style="padding:8px; background:#10161d; color:#fff; font-size:11px;">PRODUCTO</th>
                                <th align="center" style="padding:8px; background:#10161d; color:#fff; font-size:11px;">CANT.</th>
                                <th align="right" style="padding:8px; background:#10161d; color:#fff; font-size:11px;">IMPORTE</th>
                            </tr>
                            @foreach ($pedido->lineas as $linea)
                                <tr style="background: {{ $loop->even ? '#f7f9fc' : '#ffffff' }};">
                                    <td style="padding:8px; border-bottom:1px solid #e3e8ef;">{{ $linea->nombre_producto }}</td>
                                    <td align="center" style="padding:8px; border-bottom:1px solid #e3e8ef;">{{ $linea->cantidad }}</td>
                                    <td align="right" style="padding:8px; border-bottom:1px solid #e3e8ef;">
                                        {{ \App\Support\Moneda::formato($linea->subtotal) }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                {{-- Totales --}}
                <tr>
                    <td style="padding:14px 28px 0;" align="right">
                        <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:13px; width:260px;">
                            <tr>
                                <td style="padding:4px 6px; color:#6b7889;">Subtotal</td>
                                <td align="right" style="padding:4px 6px;">{{ \App\Support\Moneda::formato($pedido->subtotal) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 6px; color:#6b7889;">{{ config('tienda.impuesto.nombre') }}</td>
                                <td align="right" style="padding:4px 6px;">{{ \App\Support\Moneda::formato($pedido->impuesto) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 6px; color:#6b7889;">Envío</td>
                                <td align="right" style="padding:4px 6px;">
                                    {{ (float) $pedido->envio === 0.0 ? 'Gratis' : \App\Support\Moneda::formato($pedido->envio) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 6px; border-top:2px solid #0ea5e9; font-weight:bold; color:#10161d;">TOTAL</td>
                                <td align="right" style="padding:8px 6px; border-top:2px solid #0ea5e9; font-weight:bold; font-size:15px; color:#10161d;">
                                    {{ \App\Support\Moneda::formato($pedido->total) }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Dirección de entrega --}}
                <tr>
                    <td style="padding:18px 28px 0;">
                        <h2 style="margin:0 0 8px; font-size:15px; color:#10161d;">Entrega</h2>
                        <p style="margin:0; font-size:13px; line-height:1.6; color:#4c5a6b;">
                            {{ $pedido->envio_nombre }}<br>
                            {{ $pedido->envio_direccion }}<br>
                            {{ $pedido->envio_ciudad }}, {{ $pedido->envio_provincia }}<br>
                            Tel. {{ $pedido->envio_telefono }}
                        </p>
                    </td>
                </tr>

                {{-- Botón --}}
                <tr>
                    <td align="center" style="padding:26px 28px;">
                        <a href="{{ route('pedidos.detalle', $pedido) }}"
                           style="display:inline-block; background:#0277b5; color:#ffffff; text-decoration:none;
                                  padding:12px 26px; border-radius:6px; font-size:14px; font-weight:bold;">
                            Ver mi pedido
                        </a>
                    </td>
                </tr>

                {{-- Pie --}}
                <tr>
                    <td style="background:#f7f9fc; padding:18px 28px; font-size:11px; color:#8c98a8; line-height:1.6;">
                        {{ config('tienda.nombre') }} · {{ config('tienda.direccion') }}<br>
                        {{ config('tienda.telefono') }} · {{ config('tienda.correo') }}<br>
                        Este es un correo automático; por seguridad nunca le pediremos los datos de su tarjeta por este medio.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
