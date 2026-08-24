<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Producto del catálogo. Un producto pertenece a una categoría.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'nombre', 'slug', 'sku', 'marca', 'resumen', 'descripcion',
        'precio', 'precio_anterior', 'existencias', 'imagen', 'destacado', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio'          => 'decimal:2',
            'precio_anterior' => 'decimal:2',
            'existencias'     => 'integer',
            'destacado'       => 'boolean',
            'activo'          => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $producto) {
            if (blank($producto->slug)) {
                $producto->slug = Str::slug($producto->nombre);
            }
        });
    }

    /* ----------------------------------------------------------------------
     | Relaciones
     | -------------------------------------------------------------------- */

    /** Un producto pertenece a una categoría. */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Un producto aparece en muchas líneas de carrito. */
    public function lineasCarrito(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** Un producto aparece en muchas líneas de pedido. */
    public function lineasPedido(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Usuarios que tienen este producto en su lista de deseos.
     *
     * Es el otro extremo de la relación MUCHOS A MUCHOS declarada en
     * User::favoritos(), sobre la misma tabla pivote «favoritos». Tenerla
     * declarada en los dos modelos permite recorrer la relación en cualquier
     * dirección: los favoritos de un cliente, o cuánta gente quiere un producto.
     */
    public function seguidores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favoritos')->withTimestamps();
    }

    /* ----------------------------------------------------------------------
     | Scopes de consulta (usados por el buscador y los filtros)
     | -------------------------------------------------------------------- */

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeDestacados(Builder $query): Builder
    {
        return $query->where('destacado', true);
    }

    /**
     * Búsqueda por nombre, marca, SKU o descripción.
     *
     * El término se pasa como parámetro enlazado (bindings de PDO), por lo que
     * no es posible inyectar SQL a través del cuadro de búsqueda.
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return $query;
        }

        // El término viaja como parámetro enlazado, así que un comodín (%)
        // escrito por el usuario solo amplía la búsqueda: no puede romper la
        // consulta ni inyectar SQL.
        $patron = '%'.$termino.'%';

        return $query->where(function (Builder $q) use ($patron) {
            $q->where('nombre', 'like', $patron)
                ->orWhere('marca', 'like', $patron)
                ->orWhere('sku', 'like', $patron)
                ->orWhere('resumen', 'like', $patron);
        });
    }

    public function scopeDeCategoria(Builder $query, ?int $categoriaId): Builder
    {
        return $categoriaId
            ? $query->where('category_id', $categoriaId)
            : $query;
    }

    public function scopePrecioMinimo(Builder $query, $minimo): Builder
    {
        return is_numeric($minimo) ? $query->where('precio', '>=', (float) $minimo) : $query;
    }

    public function scopePrecioMaximo(Builder $query, $maximo): Builder
    {
        return is_numeric($maximo) ? $query->where('precio', '<=', (float) $maximo) : $query;
    }

    /**
     * Ordenamientos permitidos.
     *
     * Se usa una lista blanca: el valor que llega por la URL nunca se
     * interpola directamente en la consulta SQL.
     */
    public function scopeOrdenar(Builder $query, ?string $criterio): Builder
    {
        return match ($criterio) {
            'precio_asc'  => $query->orderBy('precio'),
            'precio_desc' => $query->orderByDesc('precio'),
            'nombre'      => $query->orderBy('nombre'),
            'antiguos'    => $query->oldest(),
            default       => $query->latest(),
        };
    }

    /* ----------------------------------------------------------------------
     | Ayudas
     | -------------------------------------------------------------------- */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hayExistencias(int $cantidad = 1): bool
    {
        return $this->existencias >= $cantidad;
    }

    public function tieneDescuento(): bool
    {
        return $this->precio_anterior !== null
            && (float) $this->precio_anterior > (float) $this->precio;
    }

    public function porcentajeDescuento(): int
    {
        if (! $this->tieneDescuento()) {
            return 0;
        }

        $anterior = (float) $this->precio_anterior;

        return (int) round((($anterior - (float) $this->precio) / $anterior) * 100);
    }

    /** Ruta pública de la imagen, con respaldo si el producto no tiene una. */
    public function urlImagen(): string
    {
        $imagen = $this->imagen ?: 'img/productos/sin-imagen.svg';

        // Los productos importados desde una tienda externa guardan la
        // dirección completa de la imagen en el sitio de origen; en ese caso
        // se devuelve tal cual, sin anteponer el dominio de esta tienda.
        if (Str::startsWith($imagen, ['http://', 'https://'])) {
            return $imagen;
        }

        return asset($imagen);
    }
}
