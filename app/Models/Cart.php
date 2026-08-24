<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Carrito de compras de un usuario o de un visitante anónimos.
 */
class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'token_sesion'];

    /** El carrito pertenece a un usuario (puede ser nulo si es anónimo). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** El carrito tiene muchas líneas. */
    public function lineas(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** Cantidad total de unidades en el carrito. */
    public function cantidadArticulos(): int
    {
        return (int) $this->lineas()->sum('cantidad');
    }

    public function estaVacio(): bool
    {
        return $this->lineas()->count() === 0;
    }
}
