<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de confirmación de compra que recibe el cliente.
 *
 * Lleva adjunta la factura en PDF, generada al vuelo con la misma plantilla
 * que se usa para descargarla desde la web.
 */
class ConfirmacionPedido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $pedido)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Su pedido %s en %s',
                $this->pedido->numero_pedido,
                config('tienda.nombre')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmacion-pedido',
            with: ['pedido' => $this->pedido],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        // Un pedido sin factura (pago pendiente) se envía sin adjunto.
        if (! $this->pedido->factura) {
            return [];
        }

        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.factura', [
                    'pedido'  => $this->pedido,
                    'factura' => $this->pedido->factura,
                ])->setPaper('letter')->output(),
                $this->pedido->factura->numero_factura.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
