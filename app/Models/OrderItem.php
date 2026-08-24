<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de detalle de un pedidos.
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'nombre_producto', 'sku',
        'precio_unitario', 'cantidad', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'subtotal'        => 'decimal:2',
            'cantidad'        => 'integer',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Puede ser nulo si el producto se eliminó del catálogo. */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
