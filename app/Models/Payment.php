<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transacción registrada por la pasarela de pago.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'metodo', 'estado', 'monto', 'moneda', 'id_transaccion',
        'tarjeta_marca', 'tarjeta_ultimos4', 'correo_pagador', 'mensaje', 'procesado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto'        => 'decimal:2',
            'procesado_en' => 'datetime',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function fueAprobado(): bool
    {
        return $this->estado === 'aprobado';
    }
}
