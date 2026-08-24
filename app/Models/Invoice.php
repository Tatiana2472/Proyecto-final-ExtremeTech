<?php

namespace App\Models;

use App\Models\Concerns\ConsecutivoAnual;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Factura emitida a partir de un pedido pagados.
 */
class Invoice extends Model
{
    use ConsecutivoAnual, HasFactory;

    protected $fillable = [
        'order_id', 'user_id', 'numero_factura', 'fecha_emision',
        'cliente_nombre', 'cliente_correo', 'cliente_cedula',
        'subtotal', 'impuesto', 'envio', 'total', 'moneda',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'datetime',
            'subtotal'      => 'decimal:2',
            'impuesto'      => 'decimal:2',
            'envio'         => 'decimal:2',
            'total'         => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Consecutivo de factura, por ejemplo FAC-2026-000014. */
    public static function siguienteNumero(): string
    {
        return static::siguienteConsecutivoAnual('numero_factura', 'FAC');
    }
}
