<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea del carrito: un producto con su cantidad y precio unitarios.
 */
class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'product_id', 'cantidad', 'precio_unitario'];

    protected function casts(): array
    {
        return [
            'cantidad'        => 'integer',
            'precio_unitario' => 'decimal:2',
        ];
    }

    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Importe de la línea: precio unitario por cantidad. */
    public function subtotal(): float
    {
        return round((float) $this->precio_unitario * $this->cantidad, 2);
    }
}
