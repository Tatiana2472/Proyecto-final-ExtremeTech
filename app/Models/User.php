<?php

namespace App\Models;

use App\Notifications\RestablecerContrasena;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario de la tienda (cliente o administrador).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'cedula',
        'direccion',
        'ciudad',
        'provincia',
        'es_admin',
    ];

    /**
     * Nunca se exponen la contraseña ni el token de "recordarme".
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Laravel aplica bcrypt automáticamente al asignar la contraseña.
            'password' => 'hashed',
            'es_admin' => 'boolean',
        ];
    }

    /* ----------------------------------------------------------------------
     | Relaciones
     | -------------------------------------------------------------------- */

    /** Un usuario tiene muchos pedidos. */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Order::class)->latest('fecha_compra');
    }

    /** Un usuario tiene muchas facturas. */
    public function facturas(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** Un usuario tiene un carrito activo. */
    public function carrito(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Lista de deseos: los productos que el usuario marcó como favoritos.
     *
     * Relación MUCHOS A MUCHOS a través de la tabla pivote «favoritos». Es de
     * muchos a muchos porque un usuario guarda varios productos y un mismo
     * producto lo guardan varios usuarios; el lado opuesto es
     * Product::seguidores().
     *
     * withTimestamps() hace que al marcar un favorito se guarden created_at y
     * updated_at en la pivote, lo que permite ordenarlos por fecha.
     */
    public function favoritos(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favoritos')
            ->withTimestamps()
            ->latest('favoritos.created_at');
    }

    /** ¿El usuario ya tiene este producto en su lista de deseos? */
    public function tieneFavorito(Product $producto): bool
    {
        return $this->favoritos()->whereKey($producto->getKey())->exists();
    }

    /* ----------------------------------------------------------------------
     | Notificaciones
     | -------------------------------------------------------------------- */

    /**
     * Se sobrescribe para enviar el correo de recuperación en español.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasena($token));
    }

    /* ----------------------------------------------------------------------
     | Ayudas
     | -------------------------------------------------------------------- */

    public function esAdministrador(): bool
    {
        return (bool) $this->es_admin;
    }

    /** Total gastado en pedidos efectivamente pagados. */
    public function totalComprado(): float
    {
        return (float) $this->pedidos()->where('estado_pago', 'aprobado')->sum('total');
    }
}
